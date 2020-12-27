<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: moderation.inc.php 21052 2009-11-09 10:12:34Z monkey $
*/

if(!empty($tid)) {
	$moderate = array($tid);
}

if(!defined('IN_DISCUZ') || CURSCRIPT != 'topicadmin') {
	exit('Access Denied');
}

if(empty($operations)) {
	$operations = array();
}

if($operations && $operations != array_intersect($operations, array('delete', 'highlight', 'open', 'close', 'stick', 'digest', 'bump', 'down', 'recommend', 'type', 'move')) || (!$allowdelpost && in_array('delete', $operations)) || (!$allowstickthread && in_array('stick', $operations))) {
	showmessage('admin_moderate_invalid');
}

$threadlist = $loglist = array();
if($tids = implodeids($moderate)) {
	$query = $db->query("SELECT * FROM {$tablepre}threads WHERE tid IN ($tids) AND fid='$fid' AND displayorder>='0' AND digest>='0' LIMIT $tpp");
	while($thread = $db->fetch_array($query)) {
		$thread['lastposterenc'] = rawurlencode($thread['lastposter']);
		$thread['dblastpost'] = $thread['lastpost'];
		$thread['lastpost'] = dgmdate("$dateformat $timeformat", $thread['lastpost'] + $timeoffset * 3600);
		$threadlist[$thread['tid']] = $thread;
		$tid = empty($tid) ? $thread['tid'] : $tid;
	}
}

if(empty($threadlist)) {
	showmessage('admin_moderate_invalid');
}

$modpostsnum = count($threadlist);
$single = $modpostsnum == 1 ? TRUE : FALSE;

switch($frommodcp) {
	case '1':
		$referer = "modcp.php?action=threads&fid=$fid&op=threads&do=".($frommodcp == 1 ? '' : 'list');
		break;
	case '2':
		$referer = "modcp.php?action=forums&op=recommend".($show ? "&show=$show" : '')."&fid=$fid";
		break;
	default:
		$referer = "forumdisplay.php?fid=$fid&".rawurldecode($listextra);
		break;
}

if(!submitcheck('modsubmit')) {
	if($optgroup == 1 && $single) {
		$stickcheck  = $digestcheck = array();
		empty($threadlist[$tid]['displayorder']) ? $stickcheck[0] ='selected="selected"' : $stickcheck[$threadlist[$tid]['displayorder']] = 'selected="selected"';
		empty($threadlist[$tid]['digest']) ? $digestcheck[0] = 'selected="selected"' : $digestcheck[$threadlist[$tid]['digest']] = 'selected="selected"';
		$string = sprintf('%02d', $threadlist[$tid]['highlight']);
		$stylestr = sprintf('%03b', $string[0]);
		for($i = 1; $i <= 3; $i++) {
			$stylecheck[$i] = $stylestr[$i - 1] ? 1 : 0;
		}
		$colorcheck = $string[1];
		$forum['modrecommend'] = is_array($forum['modrecommend']) ? $forum['modrecommend'] : array();
	} elseif($optgroup == 2) {
		require_once DISCUZ_ROOT.'./include/forum.func.php';
		$forumselect = forumselect(FALSE, 0, $single ? $threadlist[$tid]['fid'] : 0);
		$typeselect = typeselect($single ? $threadlist[$tid]['typeid'] : 0);
	} elseif($optgroup == 4 && $single) {
		$closecheck = array();
		empty($threadlist[$tid]['closed']) ? $closecheck[0] = 'checked="checked"' : $closecheck[1] = 'checked="checked"';
	}

	$defaultcheck[$operation] = 'checked="checked"';
	$imgattach = array();
	if(count($threadlist) == 1 && $operation == 'recommend') {
		$query = $db->query("SELECT a.*, af.description FROM {$tablepre}attachments a LEFT JOIN {$tablepre}attachmentfields af ON a.aid=af.aid WHERE a.tid='$tid' AND a.isimage IN ('1', '-1')");
		while($row = $db->fetch_array($query)) {
			$imgattach[] = $row;
		}
		$query = $db->query("SELECT * FROM {$tablepre}forumrecommend WHERE tid='$tid'");
		if($oldthread = $db->fetch_array($query)) {
			$threadlist[$tid]['subject'] = $oldthread['subject'];
			$selectposition[$oldthread['position']] = ' selected="selected"';
			$selectattach = $oldthread['aid'];
		} else {
			$selectattach = $imgattach[0]['aid'];
			$selectposition[0] = ' selected="selected"';
		}
	}
	include template('topicadmin');

} else {

	$moderatetids = implodeids(array_keys($threadlist));
	checkreasonpm();
	$stampstatus = 0;

	if(empty($operations)) {
		showmessage('admin_nonexistence');
	} else {
		$posts = $images = array();
		foreach($operations as $operation) {

			if(in_array($operation, array('stick', 'highlight', 'digest', 'recommend'))) {
				if(empty($posts)) {
					$query = $db->query("SELECT * FROM {$tablepre}posts WHERE tid IN ($moderatetids) AND first='1'");
					while($post = $db->fetch_array($query)) {
						$post['message'] = messagecutstr($post['message'], 200);
						$posts[$post['tid']] = $post;
					}
				}
			}

			$updatemodlog = TRUE;
			if($operation == 'stick') {
				$sticklevel = intval($sticklevel);
				if($sticklevel < 0 || $sticklevel > 3 || $sticklevel > $allowstickthread) {
					showmessage('undefined_action');
				}
				$expiration = checkexpiration($expirationstick);
				$expirationstick = $sticklevel ? $expirationstick : 0;

				$forumstickthreads = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='forumstickthreads'");
				$forumstickthreads = isset($forumstickthreads) ? unserialize($forumstickthreads) : array();

				$db->query("UPDATE {$tablepre}threads SET displayorder='$sticklevel', moderated='1' WHERE tid IN ($moderatetids)");
				$delkeys = array_keys($threadlist);
				foreach($delkeys as $k) {
					unset($forumstickthreads[$k]);
				}
				$forumstickthreads = serialize($forumstickthreads);
				$db->query("UPDATE {$tablepre}settings SET value='$forumstickthreads' WHERE variable='forumstickthreads'");

				$stickmodify = 0;
				foreach($threadlist as $thread) {
					$stickmodify = (in_array($thread['displayorder'], array(2, 3)) || in_array($sticklevel, array(2, 3))) && $sticklevel != $thread['displayorder'] ? 1 : $stickmodify;
				}

				if($globalstick && $stickmodify) {
					require_once DISCUZ_ROOT.'./include/cache.func.php';
					updatecache('globalstick');
				}

				$modaction = $sticklevel ? ($expiration ? 'EST' : 'STK') : 'UST';
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($moderatetids) AND action IN ('STK', 'UST', 'EST', 'UES')", 'UNBUFFERED');

				if($sticklevel > 0) {
					send_thread_feed('thread_pin', $threadlist);
				}
				$stampstatus = 1;
			} elseif($operation == 'highlight') {
				if(!$allowhighlightthread) {
					showmessage('undefined_action');
				}
				$expiration = checkexpiration($expirationhighlight);
				$stylebin = '';
				for($i = 1; $i <= 3; $i++) {
					$stylebin .= empty($highlight_style[$i]) ? '0' : '1';
				}

				$highlight_style = bindec($stylebin);
				if($highlight_style < 0 || $highlight_style > 7 || $highlight_color < 0 || $highlight_color > 8) {
					showmessage('undefined_action', NULL, 'HALTED');
				}

				$db->query("UPDATE {$tablepre}threads SET highlight='$highlight_style$highlight_color', moderated='1' WHERE tid IN ($moderatetids)", 'UNBUFFERED');
				if($db->fetch_first("SELECT * FROM {$tablepre}forumrecommend WHERE tid IN ($moderatetids)")) {
					$db->query("UPDATE {$tablepre}forumrecommend SET highlight='$highlight_style$highlight_color' WHERE tid IN ($moderatetids)", 'UNBUFFERED');
				}

				$modaction = ($highlight_style + $highlight_color) ? ($expiration ? 'EHL' : 'HLT') : 'UHL';
				$expiration = $modaction == 'UHL' ? 0 : $expiration;
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($moderatetids) AND action IN ('HLT', 'UHL', 'EHL', 'UEH')", 'UNBUFFERED');

				if($highlight_style > 0) {
					send_thread_feed('thread_highlight', $threadlist);
				}
			} elseif($operation == 'digest') {
				$digestlevel = intval($digestlevel);
				if($digestlevel < 0 || $digestlevel > 3 || $digestlevel > $allowdigestthread) {
					showmessage('undefined_action');
				}
				$expiration = checkexpiration($expirationdigest);
				$expirationdigest = $digestlevel ? $expirationdigest : 0;

				$db->query("UPDATE {$tablepre}threads SET digest='$digestlevel', moderated='1' WHERE tid IN ($moderatetids)");

				foreach($threadlist as $thread) {
					if($thread['digest'] != $digestlevel) {
						$digestpostsadd = ($thread['digest'] > 0 && $digestlevel == 0) || ($thread['digest'] == 0 && $digestlevel > 0) ? 'digestposts=digestposts+\''.($digestlevel == 0 ? '-' : '+').'1\'' : '';
						updatecredits($thread['authorid'], $digestcredits, $digestlevel - $thread['digest'], $digestpostsadd);
					}
				}

				$modaction = $digestlevel ? ($expiration ? 'EDI' : 'DIG') : 'UDG';
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($moderatetids) AND action IN ('DIG', 'UDI', 'EDI', 'UED')", 'UNBUFFERED');

				if($digestlevel > 0) {
					send_thread_feed('thread_digest', $threadlist);
				}
				$stampstatus = 2;
			} elseif($operation == 'recommend') {
				if(!$allowrecommendthread) {
					showmessage('undefined_action');
				}
				$modrecommend = $forum['modrecommend'] ? unserialize($forum['modrecommend']) : array();
				$imgw = $modrecommend['imagewidth'] ? intval($modrecommend['imagewidth']) : 200;
				$imgh = $modrecommend['imageheight'] ? intval($modrecommend['imageheight']) : 150;
				$expiration = checkexpiration($expirationrecommend);
				$db->query("UPDATE {$tablepre}threads SET moderated='1' WHERE tid IN ($moderatetids)");
				$modaction = $isrecommend ? 'REC' : 'URE';
				$thread = daddslashes($thread, 1);

				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($moderatetids) AND action IN ('REC')", 'UNBUFFERED');
				if($isrecommend) {
					$addthread = $comma = '';
					$oldrecommendlist = array();
					$query = $db->query("SELECT * FROM {$tablepre}forumrecommend WHERE tid IN ($moderatetids)");
					while($row = $db->fetch_array($query)) {
						if($row['aid']) {
							@unlink(DISCUZ_ROOT.'./forumdata/imagecaches/'.intval($row['aid']).'_'.$imgw.'_'.$imgh.'.jpg');
						}
						$oldrecommendlist[$row['tid']] = $row;
					}
					foreach($threadlist as $thread) {
						if(count($threadlist) > 1) {
							if($oldrecommendlist[$thread['tid']]) {
								$oldthread = $oldrecommendlist[$thread['tid']];
								$reducetitle = $oldthread['subject'];
								$selectattach = $oldthread['aid'];
								$typeid = $oldthread['typeid'];
								$position = $oldthread['position'];
							} else {
								$reducetitle = $thread['subject'];
								$typeid = 0;
								$position = 0;
							}
						} else {
							empty($reducetitle) && $reducetitle = $thread['subject'];
							$typeid = $selectattach ? 1 : 0;
							empty($position) && $position = 0;
						}
						if($selectattach) {
							$key = authcode($selectattach."\t".$imgw."\t".$imgh, 'ENCODE', $_DCACHE['settings']['authkey']);
							$filename = 'image.php?aid='.$selectattach.'&size='.$imgw.'x'.$imgh.'&key='.rawurlencode($key);
						} else {
							$selectattach = 0;
							$filename = '';
						}

						$addthread .= $comma."('$thread[fid]', '$thread[tid]', '$typeid', '0', '".addslashes($reducetitle)."', '".addslashes($thread['author'])."', '$thread[authorid]', '$discuz_uid', '$expiration', '$position', '$selectattach', '$filename', '$thread[highlight]')";
						$comma = ', ';
						$reducetitle = '';
					}
					if($addthread) {
						$db->query("REPLACE INTO {$tablepre}forumrecommend (fid, tid, typeid, displayorder, subject, author, authorid, moderatorid, expiration, position, aid, filename, highlight) VALUES $addthread");
					}

					send_thread_feed('thread_recommend', $threadlist);
					$stampstatus = 3;
				} else {
					$db->query("DELETE FROM {$tablepre}forumrecommend WHERE fid='$fid' AND tid IN ($moderatetids)");
				}

			} elseif($operation == 'bump') {
				if(!$allowbumpthread) {
					showmessage('undefined_action');
				}
				$modaction = 'BMP';
				$thread = $threadlist;
				$thread = array_pop($thread);
				$thread['subject'] = addslashes($thread['subject']);
				$thread['lastposter'] = addslashes($thread['lastposter']);

				$db->query("UPDATE {$tablepre}threads SET lastpost='$timestamp', moderated='1' WHERE tid IN ($moderatetids)");
				$db->query("UPDATE {$tablepre}forums SET lastpost='$thread[tid]\t$thread[subject]\t$timestamp\t$thread[lastposter]' WHERE fid='$fid'");

				$forum['threadcaches'] && deletethreadcaches($thread['tid']);
			} elseif($operation == 'down') {
				if(!$allowbumpthread) {
					showmessage('undefined_action');
				}
				$modaction = 'DWN';
				$downtime = $timestamp - 86400 * 730;
				$db->query("UPDATE {$tablepre}threads SET lastpost='$downtime', moderated='1' WHERE tid IN ($moderatetids)");

				$forum['threadcaches'] && deletethreadcaches($thread['tid']);
			} elseif($operation == 'delete') {
				if(!$allowdelpost) {
					showmessage('undefined_action');
				}
				$stickmodify = 0;
				foreach($threadlist as $thread) {
					if($thread['digest']) {
						updatecredits($thread['authorid'], $digestcredits, -$thread['digest'], 'digestposts=digestposts-1');
					}
					if(in_array($thread['displayorder'], array(2, 3))) {
						$stickmodify = 1;
					}
				}

				$losslessdel = $losslessdel > 0 ? $timestamp - $losslessdel * 86400 : 0;

				//Update members' credits and post counter
				$uidarray = $tuidarray = $ruidarray = array();
				$query = $db->query("SELECT first, authorid, dateline FROM {$tablepre}posts WHERE tid IN ($moderatetids)");
				while($post = $db->fetch_array($query)) {
					if($post['dateline'] < $losslessdel) {
						$uidarray[] = $post['authorid'];
					} else {
						if($post['first']) {
							$tuidarray[] = $post['authorid'];
						} else {
							$ruidarray[] = $post['authorid'];
						}
					}
				}

				if($uidarray) {
					updatepostcredits('-', $uidarray, array());
				}
				if($tuidarray) {
					updatepostcredits('-', $tuidarray, $postcredits);
				}
				if($ruidarray) {
					updatepostcredits('-', $ruidarray, $replycredits);
				}
				$modaction = 'DEL';

				if($forum['recyclebin']) {

					$db->query("UPDATE {$tablepre}threads SET displayorder='-1', digest='0', moderated='1' WHERE tid IN ($moderatetids)");
					$db->query("UPDATE {$tablepre}posts SET invisible='-1' WHERE tid IN ($moderatetids)");

				} else {

					$auidarray = array();

					$query = $db->query("SELECT uid, attachment, dateline, thumb, remote FROM {$tablepre}attachments WHERE tid IN ($moderatetids)");
					while($attach = $db->fetch_array($query)) {
						dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
						if($attach['dateline'] > $losslessdel) {
							$auidarray[$attach['uid']] = !empty($auidarray[$attach['uid']]) ? $auidarray[$attach['uid']] + 1 : 1;
						}
					}

					if($auidarray) {
						updateattachcredits('-', $auidarray, $postattachcredits);
					}

					foreach(array('threads', 'threadsmod', 'relatedthreads', 'posts', 'polls', 'polloptions', 'trades', 'activities', 'activityapplies', 'debates', 'debateposts', 'attachments', 'favorites', 'typeoptionvars', 'forumrecommend', 'postposition') as $value) {
						$db->query("DELETE FROM {$tablepre}$value WHERE tid IN ($moderatetids)", 'UNBUFFERED');
					}

					$updatemodlog = FALSE;
				}

				if($globalstick && $stickmodify) {
					require_once DISCUZ_ROOT.'./include/cache.func.php';
					updatecache('globalstick');
				}

				updateforumcount($fid);
			} elseif($operation == 'close') {
				if(!$allowclosethread) {
					showmessage('undefined_action');
				}
				$expiration = checkexpiration($expirationclose);
				$modaction = $expiration ? 'ECL' : 'CLS';

				$db->query("UPDATE {$tablepre}threads SET closed='1', moderated='1' WHERE tid IN ($moderatetids)");
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($moderatetids) AND action IN ('CLS','OPN','ECL','UCL','EOP','UEO')", 'UNBUFFERED');
			} elseif($operation == 'open') {
				if(!$allowclosethread) {
					showmessage('undefined_action');
				}
				$expiration = checkexpiration($expirationopen);
				$modaction = $expiration ? 'EOP' : 'OPN';

				$db->query("UPDATE {$tablepre}threads SET closed='0', moderated='1' WHERE tid IN ($moderatetids)");
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($moderatetids) AND action IN ('CLS','OPN','ECL','UCL','EOP','UEO')", 'UNBUFFERED');
			} elseif($operation == 'move') {
				if(!$allowmovethread) {
					showmessage('undefined_action');
				}
				$toforum = $db->fetch_first("SELECT f.fid, f.name, f.modnewposts, f.allowpostspecial, ff.threadplugin FROM {$tablepre}forums f LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid WHERE f.fid='$moveto' AND f.status='1' AND f.type<>'group'");
				if(!$toforum) {
					showmessage('admin_move_invalid');
				} elseif($fid == $toforum['fid']) {
					continue;
				} else {
					$moveto = $toforum['fid'];
					$modnewthreads = (!$allowdirectpost || $allowdirectpost == 1) && $toforum['modnewposts'] ? 1 : 0;
					$modnewreplies = (!$allowdirectpost || $allowdirectpost == 2) && $toforum['modnewposts'] ? 1 : 0;
					if($modnewthreads || $modnewreplies) {
						showmessage('admin_move_have_mod');
					}
				}

				if($adminid == 3) {
					if($accessmasks) {
						$accessadd1 = ', a.allowview, a.allowpost, a.allowreply, a.allowgetattach, a.allowpostattach';
						$accessadd2 = "LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid='$moveto'";
					}
					$priv = $db->fetch_first("SELECT ff.postperm, m.uid AS istargetmod $accessadd1
							FROM {$tablepre}forumfields ff
							$accessadd2
							LEFT JOIN {$tablepre}moderators m ON m.fid='$moveto' AND m.uid='$discuz_uid'
							WHERE ff.fid='$moveto'");
					if((($priv['postperm'] && !in_array($groupid, explode("\t", $priv['postperm']))) || ($accessmasks && ($priv['allowview'] || $priv['allowreply'] || $priv['allowgetattach'] || $priv['allowpostattach']) && !$priv['allowpost'])) && !$priv['istargetmod']) {
						showmessage('admin_move_nopermission');
					}
				}

				$moderate = array();
				$stickmodify = 0;
				$toforumallowspecial = array(
					1 => $toforum['allowpostspecial'] & 1,
					2 => $toforum['allowpostspecial'] & 2,
					3 => isset($extcredits[$creditstransextra[2]]) && ($toforum['allowpostspecial'] & 4),
					4 => $toforum['allowpostspecial'] & 8,
					5 => $toforum['allowpostspecial'] & 16,
					127 => $threadplugins ? unserialize($toforum['threadplugin']) : array(),
				);
				foreach($threadlist as $tid => $thread) {
					$allowmove = 0;
					if(!$thread['special']) {
						$allowmove = 1;
					} else {
						if($thread['special'] != 127) {
							$allowmove = $toforum['allowpostspecial'] ? $toforumallowspecial[$thread['special']] : 0;
						} else {
							if($toforumallowspecial[127]) {
								$message = $db->result_first("SELECT message FROM {$tablepre}posts WHERE tid='$thread[tid]' AND first='1'");
								$sppos = strrpos($message, chr(0).chr(0).chr(0));
								$specialextra = substr($message, $sppos + 3);
								$allowmove = in_array($specialextra, $toforumallowspecial[127]);
							} else {
								$allowmove = 0;
							}
						}
					}

					if($allowmove) {
						$moderate[] = $tid;
						if(in_array($thread['displayorder'], array(2, 3))) {
							$stickmodify = 1;
						}
						if($type == 'redirect') {
							$thread = daddslashes($thread, 1);
							$db->query("INSERT INTO {$tablepre}threads (fid, readperm, iconid, author, authorid, subject, dateline, lastpost, lastposter, views, replies, displayorder, digest, closed, special, attachment)
								VALUES ('$thread[fid]', '$thread[readperm]', '$thread[iconid]', '".addslashes($thread['author'])."', '$thread[authorid]', '".addslashes($thread['subject'])."', '$thread[dateline]', '$thread[dblastpost]', '".addslashes($thread['lastposter'])."', '0', '0', '0', '0', '$thread[tid]', '0', '0')");
						}
					}
				}

				if(!$moderatetids = implode(',', $moderate)) {
					showmessage('admin_moderate_invalid');
				}

				$displayorderadd = $adminid == 3 ? ', displayorder=\'0\'' : '';
				$db->query("UPDATE {$tablepre}threads SET fid='$moveto', moderated='1' $displayorderadd WHERE tid IN ($moderatetids)");
				$db->query("UPDATE {$tablepre}posts SET fid='$moveto' WHERE tid IN ($moderatetids)");

				if($globalstick && $stickmodify) {
					require_once DISCUZ_ROOT.'./include/cache.func.php';
					updatecache('globalstick');
				}
				$modaction = 'MOV';

				updateforumcount($moveto);
				updateforumcount($fid);
			} elseif($operation == 'type') {
				if(!$allowedittypethread) {
					showmessage('undefined_action');
				}
				if(!isset($forum['threadtypes']['types'][$typeid]) && ($typeid != 0 || $forum['threadtypes']['required'])) {
					showmessage('admin_type_invalid');
				}

				$db->query("UPDATE {$tablepre}threads SET typeid='$typeid', moderated='1' WHERE tid IN ($moderatetids)");
				$modaction = 'TYP';
			}

			if($updatemodlog) {
				updatemodlog($moderatetids, $modaction, $expiration);
			}

			updatemodworks($modaction, $modpostsnum);
			foreach($threadlist as $thread) {
				modlog($thread, $modaction);
			}

			if($sendreasonpm) {
				include_once language('modactions');
				$modaction = $modactioncode[$modaction];
				foreach($threadlist as $thread) {
					sendreasonpm('thread', $operation == 'move' ? 'reason_move' : 'reason_moderate');
				}
			}

			procreportlog($moderatetids, '', $operation == 'delete');

			if($stampstatus) {
				set_stamp($stampstatus);
			}

		}

		showmessage('admin_succeed', $referer);
	}

}

function checkexpiration($expiration) {
	global $operation, $timestamp, $timeoffset;
	if(!empty($expiration) && in_array($operation, array('recommend', 'stick', 'digest', 'highlight', 'close'))) {
		$expiration = strtotime($expiration) - $timeoffset * 3600 + date('Z');
		if(gmdate('Ymd', $expiration + $timeoffset * 3600) <= gmdate('Ymd', $timestamp + $timeoffset * 3600) || ($expiration > $timestamp + 86400 * 180)) {
			showmessage('admin_expiration_invalid');
		}
	} else {
		$expiration = 0;
	}
	return $expiration;
}

function set_stamp($typeid) {
	global $tablepre, $db, $_DCACHE, $moderatetids, $expiration;
	if(array_key_exists($typeid, $_DCACHE['stamptypeid'])) {
		$db->query("UPDATE {$tablepre}threads SET ".buildbitsql('status', 5, TRUE)." WHERE tid IN ($moderatetids)");
		updatemodlog($moderatetids, 'SPA', $expiration, 0, $_DCACHE['stamptypeid'][$typeid]);
	}
}

function send_thread_feed($type, $threadlist) {
	global $tablepre, $db;
	include DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
	$arg = $data = array();
	$arg['type'] = $type;
	$user_digest = array();
	foreach($threadlist as $key => $val) {
		if($type == 'thread_pin') {
			if($val['displayorder'] != 0) continue;
		} elseif($type == 'thread_highlight') {
			if($val['highlight'] != 0) continue;
		} elseif($type == 'thread_digest') {
			if($val['digest'] != 0) continue;
		}
		if($type == 'user_digest' && $dzfeed_limit['user_digest']) {
			$user_digest[$val['authorid']]++;
		}
		$arg['fid'] = $val['fid'];
		$arg['typeid'] = $val['typeid'];
		$arg['sortid'] = $val['sortid'];
		$arg['uid'] = $val['authorid'];
		$arg['username'] = addslashes($val['author']);
		$data['title']['actor'] = $val['authorid'] ? "<a href=\"space.php?uid={$val[authorid]}\" target=\"_blank\">{$val[author]}</a>" : $val['author'];
		$data['title']['forum'] = "<a href=\"forumdisplay.php?fid={$val[fid]}\" target=\"_blank\">".$_DCACHE['forums'][$val['fid']]['name'].'</a>';
		$data['title']['operater'] = "<a href=\"space.php?uid={$GLOBALS[discuz_uid]}\" target=\"_blank\">{$GLOBALS[discuz_userss]}</a>";
		$data['title']['subject'] = "<a href=\"viewthread.php?tid={$val[tid]}\" target=\"_blank\">{$val[subject]}</a>";
		add_feed($arg, $data);
	}
	if($type == 'user_digest' && is_array($dzfeed_limit['user_digest']) && ($uids = implodeids(array_keys($user_digest)))) {
		$query = $db->query("SELECT uid, username, digestposts FROM {$tablepre}members WHERE uid IN ($uids)");
		while($row = $db->fetch_array($query)) {
			$send_feed = false;
			foreach($dzfeed_limit['user_digest'] as $val) {
				if($row['digestposts'] < $val && ($row['digestposts'] + $user_digest[$row['uid']]) > $val) {
					$send_feed = true;
					$count = $val;
				}
			}
			if($send_feed) {
				$arg = $data = array();
				$arg['type'] = 'user_digest';
				$arg['uid'] = $row['uid'];
				$arg['username'] = addslashes($row['username']);
				$data['title']['actor'] = "<a href=\"space.php?uid={$row[uid]}\" target=\"_blank\">{$row[username]}</a>";
				$data['title']['count'] = $count;
				add_feed($arg, $data);
			}
		}
	}
}

?>