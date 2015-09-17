<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: moderate.inc.php 20987 2009-11-05 05:19:56Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

if($op == 'members') {


	$filter = isset($filter) ? intval($filter) : 0;
	$filtercheck = array( $filter => 'selected');

	if(submitcheck('dosubmit', 1) || submitcheck('modsubmit')) {

		if(empty($mod) || !in_array($mod, array('invalidate', 'validate', 'delete'))) {
			showmessage('modcp_noaction');
		}

		$list = array();
		if($moderate && is_array($moderate)) {
			foreach($moderate as $val) {
				if(is_numeric($val) && $val) {
					$list[] = $val;
				}
			}
		}

		if(submitcheck('dosubmit', 1)) {

			$handlekey = 'mods';
			include template('modcp_moderate_float');
			dexit();

		} elseif ($uids = implodeids($list)) {

			$members = $uidarray = array();

			$query = $db->query("SELECT v.*, m.uid, m.username, m.email, m.regdate FROM {$tablepre}validating v, {$tablepre}members m
				WHERE v.uid IN ($uids) AND m.uid=v.uid AND m.groupid='8' AND status='$filter'");
			while($member = $db->fetch_array($query)) {
				$members[$member['uid']] = $member;
				$uidarray[] = $member['uid'];
			}

			if($uids = implodeids($uidarray)) {

				$reason = dhtmlspecialchars(trim($reason));

				if($mod == 'delete') {
					$db->query("DELETE FROM {$tablepre}members WHERE uid IN ($uids)");
					$db->query("DELETE FROM {$tablepre}memberfields WHERE uid IN ($uids)");
					$db->query("DELETE FROM {$tablepre}validating WHERE uid IN ($uids)");
				}

				if($mod == 'validate') {
					$newgroupid = $db->result_first("SELECT groupid FROM {$tablepre}usergroups WHERE creditshigher<=0 AND 0<creditslower LIMIT 1");
					$db->query("UPDATE {$tablepre}members SET adminid='0', groupid='$newgroupid' WHERE uid IN ($uids)");
					$db->query("DELETE FROM {$tablepre}validating WHERE uid IN ($uids)");
				}

				if($mod == 'invalidate') {
					$db->query("UPDATE {$tablepre}validating SET moddate='$timestamp', admin='$discuz_user', status='1', remark='$reason' WHERE uid IN ($uids)");
				}

				if($sendemail) {
					foreach($members as $uid => $member) {
						$member['regdate'] = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $member['regdate'] + $_DCACHE['settings']['timeoffset'] * 3600);
						$member['submitdate'] = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $member['submitdate'] + $_DCACHE['settings']['timeoffset'] * 3600);
						$member['moddate'] = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);
						$member['operation'] = $mod;
						$member['remark'] = $reason ? $reason : 'N/A';
						sendmail("$member[username] <$member[email]>", 'moderate_member_subject', 'moderate_member_message');
					}
				}
			}

			showmessage('modcp_mod_succeed', "{$cpscript}?action=$action&op=$op&filter=$filter");

		} else {
			showmessage('modcp_moduser_invalid');
		}

	} else {

		$count =  array(0, 0, 0);
		$query = $db->query("SELECT status, COUNT(*) AS count FROM {$tablepre}validating GROUP BY status");
		while($num = $db->fetch_array($query)) {
			$count[$num['status']] = $num['count'];
		}

		$page = max(1, intval($page));
		$memberperpage = 20;
		$start_limit = ($page - 1) * $memberperpage;

		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}validating WHERE status='0'");
		$multipage = multi($db->result($query, 0), $memberperpage, $page, "{$cpscript}?action=$action&op=$op&fid=$fid&filter=$filter");

		$vuids = '0';
		$memberlist = array();
		$query = $db->query("SELECT m.uid, m.username, m.groupid, m.email, m.regdate, m.regip, v.message, v.submittimes, v.submitdate, v.moddate, v.admin, v.remark
				FROM {$tablepre}validating v, {$tablepre}members m
				WHERE v.status='$filter' AND m.uid=v.uid ORDER BY v.submitdate DESC LIMIT $start_limit, $memberperpage");
		while($member = $db->fetch_array($query)) {
			if($member['groupid'] != 8) {
				$vuids .= ','.$member['uid'];
				continue;
			}
			$member['regdate'] = gmdate("$dateformat $timeformat", $member['regdate'] + $timeoffset * 3600);
			$member['submitdate'] = gmdate("$dateformat $timeformat", $member['submitdate'] + $timeoffset * 3600);
			$member['moddate'] = $member['moddate'] ? gmdate("$dateformat $timeformat", $member['moddate'] + $timeoffset * 3600) : $lang['none'];
			$member['message'] = dhtmlspecialchars($member['message']);
			$member['admin'] = $member['admin'] ? "<a href=\"space.php?username=".rawurlencode($member['admin'])."\" target=\"_blank\">$member[admin]</a>" : $lang['none'];
			$memberlist[] = $member;
		}

		if($vuids) {
			$db->query("DELETE FROM {$tablepre}validating WHERE uid IN ($vuids)", 'UNBUFFERED');
		}

		return true;
	}
}

if(empty($modforums['fids'])) {
	return false;
} elseif ($fid && ($forum['type'] == 'group' || !$forum['ismoderator'])) {
	return false;
} else {
	if($fid) {
		$modfidsadd = "fid='$fid'";
	} elseif($adminid == 1) {
		$modfidsadd = "";
	} else {
		$modfidsadd = "fid in ($modforums[fids])";
	}
}

$updatestat = false;

$op = !in_array($op , array('replies', 'threads')) ? 'threads' : $op;
$mod = !in_array($mod , array('delete', 'ignore', 'validate')) ? 'ignore' : $mod;

$filter = !empty($filter) ? -3 : 0;
$filtercheck = array($filter => 'selected="selected"');

$pstat = $filter == -3 ? -3 : -2;

$tpp = 10;
$page = max(1, intval($page));
$start_limit = ($page - 1) * $tpp;

$postlist = array();

$modpost = array('validate' => 0, 'delete' => 0, 'ignore' => 0);
$moderation = array('validate' => array(), 'delete' => array(), 'ignore' => array());

require_once DISCUZ_ROOT.'./include/post.func.php';

if(submitcheck('dosubmit', 1) || submitcheck('modsubmit')) {

	$list = array();
	if($moderate && is_array($moderate)) {
		foreach($moderate as $val) {
			if(is_numeric($val) && $val) {
				$moderation[$mod][] = $val;
			}
		}
	}

	if(submitcheck('dosubmit', 1)) {

		$handlekey = 'mods';
		$list = $moderation[$mod];
		include template('modcp_moderate_float');
		dexit();

	} else {

		$updatestat = $op == 'replies' ? 1 : 2;
		$modpost = array(
			'ignore' => count($moderation['ignore']),
			'delete' => count($moderation['delete']),
			'validate' => count($moderation['validate'])
		);
	}
}

if($op == 'replies') {

	if(submitcheck('modsubmit')) {

		$pmlist = array();
		if($ignorepids = implodeids($moderation['ignore'])) {
			$db->query("UPDATE {$tablepre}posts SET invisible='-3' WHERE pid IN ($ignorepids) AND invisible='-2' AND first='0' AND ".($modfidsadd ? $modfidsadd : '1'));
		}

		if($deletepids = implodeids($moderation['delete'])) {
			$query = $db->query("SELECT pid, authorid, tid, message FROM {$tablepre}posts WHERE pid IN ($deletepids) AND invisible='$pstat' AND first='0' AND ".($modfidsadd ? $modfidsadd : '1'));
			$pids = '0';
			while($post = $db->fetch_array($query)) {
				$pids .= ','.$post['pid'];
				if($reason != '' && $post['authorid'] && $post['authorid'] != $discuz_uid) {
					$pmlist[] = array(
						'act' => 'modreplies_delete',
						'authorid' => $post['authorid'],
						'tid' => $post['tid'],
						'post' =>  messagecutstr($post['message'], 30)
					);
				}
			}

			if($pids) {
				$query = $db->query("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE pid IN ($pids)");
				while($attach = $db->fetch_array($query)) {
					dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
				}
				$db->query("DELETE FROM {$tablepre}attachments WHERE pid IN ($pids)", 'UNBUFFERED');
				$db->query("DELETE FROM {$tablepre}attachmentfields WHERE pid IN ($pids)", 'UNBUFFERED');
				$db->query("DELETE FROM {$tablepre}posts WHERE pid IN ($pids)", 'UNBUFFERED');
				$db->query("DELETE FROM {$tablepre}trades WHERE pid IN ($pids)", 'UNBUFFERED');
			}
			updatemodworks('DLP', count($moderation['delete']));
		}

		$repliesmod = 0;
		if($validatepids = implodeids($moderation['validate'])) {

			$threads = $lastpost = $attachments = $pidarray = $authoridarray = array();
			$query = $db->query("SELECT t.lastpost, p.pid, p.fid, p.tid, p.authorid, p.author, p.dateline, p.attachment, p.message, p.anonymous, ff.replycredits
				FROM {$tablepre}posts p
				LEFT JOIN {$tablepre}forumfields ff ON ff.fid=p.fid
				LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
				WHERE p.pid IN ($validatepids) AND p.invisible='$pstat' AND p.first='0' AND ".($modfidsadd ? "p.{$modfidsadd}" : '1'));

			while($post = $db->fetch_array($query)) {
				$repliesmod ++;
				$pidarray[] = $post['pid'];
				if($post['replycredits']) {
					updatepostcredits('+', $post['authorid'], unserialize($post['replycredits']));
				} else {
					$authoridarray[] = $post['authorid'];
				}

				$threads[$post['tid']]['posts']++;
				$threads[$post['tid']]['lastpostadd'] = $post['dateline'] > $post['lastpost'] && $post['dateline'] > $lastpost[$post['tid']] ?
				", lastpost='$post[dateline]', lastposter='".($post['anonymous'] && $post['dateline'] != $post['lastpost'] ? '' : addslashes($post[author]))."'" : '';
				$threads[$post['tid']]['attachadd'] = $threads[$post['tid']]['attachadd'] || $post['attachment'] ? ', attachment=\'1\'' : '';

				$pm = 'pm_'.$post['pid'];
				if($reason != '' && $post['authorid'] && $post['authorid'] != $discuz_uid) {
					$pmlist[] = array(
						'act' => 'modreplies_validate',
						'authorid' => $post['authorid'],
						'tid' => $post['tid'],
						'post' =>  messagecutstr($post['message'], 30)
					);
				}
			}

			if($authoridarray) {
				updatepostcredits('+', $authoridarray, $creditspolicy['reply']);
			}

			foreach($threads as $tid => $thread) {
				$db->query("UPDATE {$tablepre}threads SET replies=replies+$thread[posts] $thread[lastpostadd] $thread[attachadd] WHERE tid='$tid'", 'UNBUFFERED');
			}
			if($fid) {
				updateforumcount($fid);
			} else {
				$fids = array_keys($modforums['list']);
				foreach($fids as $f) {
					updateforumcount($f);
				}
			}

			if(!empty($pidarray)) {
				$db->query("UPDATE {$tablepre}posts SET invisible='0' WHERE pid IN (0,".implode(',', $pidarray).")");
				$repliesmod = $db->affected_rows();
				updatemodworks('MOD', $repliesmod);
			} else {
				updatemodworks('MOD', 1);
			}
		}

		if($pmlist) {
			$reason = dhtmlspecialchars($reason);
			foreach($pmlist as $pm) {
				$post = $pm['post'];
				$tid = intval($pm['tid']);
				sendnotice($pm['authorid'], $pm['act'], 'systempm');
			}
		}

		showmessage('modcp_mod_succeed', "{$cpscript}?action=$action&op=$op&filter=$filter&fid=$fid");
	}

	$attachlist = array();

	require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
	require_once DISCUZ_ROOT.'./include/attachment.func.php';

	$ppp = 10;
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $ppp;

	$modcount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE invisible='$pstat' AND first='0' AND ".($modfidsadd ? $modfidsadd : '1'));
	$multipage = multi($modcount, $ppp, $page, "{$cpscript}?action=$action&op=$op&filter=$filter&fid=$fid");

	if($modcount) {
		$query = $db->query("SELECT f.name AS forumname, f.allowsmilies, f.allowhtml, f.allowbbcode, f.allowimgcode,
			p.pid, p.fid, p.tid, p.author, p.authorid, p.subject, p.dateline, p.message, p.useip, p.attachment,
			p.htmlon, p.smileyoff, p.bbcodeoff, t.subject AS tsubject
			FROM {$tablepre}posts p
			LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
			LEFT JOIN {$tablepre}forums f ON f.fid=p.fid
			WHERE p.invisible='$pstat' AND p.first='0' AND ".($modfidsadd ? "p.{$modfidsadd}" : '1')."
			ORDER BY p.dateline DESC LIMIT $start_limit, $ppp");

		while($post = $db->fetch_array($query)) {
			$post['id'] = $post['pid'];
			$post['dateline'] = gmdate("$dateformat $timeformat", $post['dateline'] + $timeoffset * 3600);
			$post['subject'] = $post['subject'] ? '<b>'.$post['subject'].'</b>' : '<i>'.$lang['nosubject'].'</i>';
			$post['message'] = nl2br(dhtmlspecialchars($post['message']));

			if($post['attachment']) {
				$queryattach = $db->query("SELECT aid, filename, filetype, filesize, attachment, isimage, remote FROM {$tablepre}attachments WHERE pid='$post[pid]'");
				while($attach = $db->fetch_array($queryattach)) {
					$attachurl = $attach['remote'] ? $ftp['attachurl'] : $attachurl;
					$attach['url'] = $attach['isimage']
					? " $attach[filename] (".sizecount($attach['filesize']).")<br /><br /><img src=\"$attachurl/$attach[attachment]\" onload=\"if(this.width > 400) {this.resized=true; this.width=400;}\">"
					: "<a href=\"$attachurl/$attach[attachment]\" target=\"_blank\">$attach[filename]</a> (".sizecount($attach['filesize']).")";
					$post['message'] .= "<br /><br />File: ".attachtype(fileext($attach['filename'])."\t".$attach['filetype']).$attach['url'];
				}
			}
			$postlist[] = $post;
		}
	}


} else {

	if(submitcheck('modsubmit')) {

		if($ignoretids = implodeids($moderation['ignore'])) {
			$db->query("UPDATE {$tablepre}threads SET displayorder='-3' WHERE tid IN ($ignoretids) AND displayorder='-2' AND ".($modfidsadd ? $modfidsadd : '1'));
		}

		$threadsmod = 0;
		$pmlist = array();
		$reason = trim($reason);

		if($ids = implodeids($moderation['delete'])) {
			$deletetids = '0';
			$recyclebintids = '0';
			$query = $db->query("SELECT tid, fid, authorid, subject FROM {$tablepre}threads WHERE tid IN ($ids) AND displayorder='$pstat' AND ".($modfidsadd ? $modfidsadd : '1'));
			while($thread = $db->fetch_array($query)) {
				if($modforums['recyclebins'][$thread['fid']]) {
					$recyclebintids .= ','.$thread['tid'];
				} else {
					$deletetids .= ','.$thread['tid'];
				}

				if($reason != '' && $thread['authorid'] && $thread['authorid'] != $discuz_uid) {
					$pmlist[] = array(
						'act' => 'modthreads_delete',
						'authorid' => $thread['authorid'],
						'thread' => $thread['subject']
					);
				}
			}

			if($recyclebintids) {
				$db->query("UPDATE {$tablepre}threads SET displayorder='-1', moderated='1' WHERE tid IN ($recyclebintids)");
				updatemodworks('MOD', $db->affected_rows());

				$db->query("UPDATE {$tablepre}posts SET invisible='-1' WHERE tid IN ($recyclebintids)");
				updatemodlog($recyclebintids, 'DEL');
			}

			$query = $db->query("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE tid IN ($deletetids)");
			while($attach = $db->fetch_array($query)) {
				dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
			}

			$db->query("DELETE FROM {$tablepre}threads WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}posts WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}polloptions WHERE tid IN ($deletetids)");
			$db->query("DELETE FROM {$tablepre}polls WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}trades WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}attachments WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}attachmentfields WHERE tid IN ($deletetids)", 'UNBUFFERED');
		}

		if($validatetids = implodeids($moderation['validate'])) {

			$tids = $comma = $comma2 = '';
			$authoridarray = $moderatedthread = array();
			$query = $db->query("SELECT t.fid, t.tid, t.authorid, t.subject, t.author, t.dateline, ff.postcredits FROM {$tablepre}threads t
				LEFT JOIN {$tablepre}forumfields ff ON ff.fid=t.fid
				WHERE t.tid IN ($validatetids) AND t.displayorder='$pstat' AND ".($modfidsadd ? "t.{$modfidsadd}" : '1'));
			while($thread = $db->fetch_array($query)) {
				$tids .= $comma.$thread['tid'];
				$comma = ',';
				if($thread['postcredits']) {
					updatepostcredits('+', $thread['authorid'], $thread['postcredits']);
				} else {
					$authoridarray[] = $thread['authorid'];
				}

				$validatedthreads[] = $thread;

				if($reason != '' && $thread['authorid'] && $thread['authorid'] != $discuz_uid) {
					$pmlist[] = array(
						'act' => 'modthreads_validate',
						'authorid' => $thread['authorid'],
						'tid' => $thread['tid'],
						'thread' => $thread['subject']
					);
				}
			}

			if($tids) {

				if($authoridarray) {
					updatepostcredits('+', $authoridarray, $creditspolicy['post']);
				}

				$db->query("UPDATE {$tablepre}posts SET invisible='0' WHERE tid IN ($tids)");
				$db->query("UPDATE {$tablepre}threads SET displayorder='0', moderated='1' WHERE tid IN ($tids)");
				$threadsmod = $db->affected_rows();

				if($fid) {
					updateforumcount($fid);
				} else {
					$fids = array_keys($modforums['list']);
					foreach($fids as $f) {
						updateforumcount($f);
					}
				}
				updatemodworks('MOD', $threadsmod);
				updatemodlog($tids, 'MOD');

			}
		}

		if($pmlist) {
			$reason = dhtmlspecialchars($reason);
			foreach($pmlist as $pm) {
				$threadsubject = $pm['thread'];
				$tid = intval($pm['tid']);
				sendnotice($pm['authorid'], $pm['act'], 'systempm');
			}
		}

		showmessage('modcp_mod_succeed', "{$cpscript}?action=$action&op=$op&filter=$filter&fid=$fid");

	}

	$modcount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE ".($modfidsadd ? " $modfidsadd AND " : '')." displayorder='$pstat'");
	$multipage = multi($modcount, $tpp, $page, "{$cpscript}?action=$action&op=$op&filter=$filter&fid=$fid");

	if($modcount) {

		$query = $db->query("SELECT t.tid, t.fid, t.author, t.sortid, t.authorid, t.subject as tsubject, t.dateline, t.attachment,
			p.pid, p.message, p.useip, p.attachment
			FROM {$tablepre}threads t
			LEFT JOIN {$tablepre}posts p ON p.tid=t.tid AND p.first = 1
			WHERE ".($modfidsadd ? " t.{$modfidsadd} AND " : '')." t.displayorder='$pstat'
			ORDER BY t.dateline DESC LIMIT $start_limit, $tpp");

		while($thread = $db->fetch_array($query)) {

			$thread['id'] = $thread['tid'];

			if($thread['authorid'] && $thread['author'] != '') {
				$thread['author'] = "<a href=\"space.php?uid=$thread[authorid]\" target=\"_blank\">$thread[author]</a>";
			} elseif($thread['authorid']) {
				$thread['author'] = "<a href=\"space.php?uid=$thread[authorid]\" target=\"_blank\">UID $thread[uid]</a>";
			} else {
				$thread['author'] = 'guest';
			}

			$thread['dateline'] = gmdate("$dateformat $timeformat", $thread['dateline'] + $timeoffset * 3600);

			$thread['message'] = nl2br(dhtmlspecialchars($thread['message']));

			if($thread['attachment']) {
				require_once DISCUZ_ROOT.'./include/attachment.func.php';

				$queryattach = $db->query("SELECT aid, filename, filetype, filesize, attachment, isimage, remote FROM {$tablepre}attachments WHERE tid='$thread[tid]'");
				while($attach = $db->fetch_array($queryattach)) {
					$attachurl = $attach['remote'] ? $ftp['attachurl'] : $attachurl;
					$attach['url'] = $attach['isimage']
					? " $attach[filename] (".sizecount($attach['filesize']).")<br /><br /><img src=\"$attachurl/$attach[attachment]\" onload=\"if(this.width > 400) {this.resized=true; this.width=400;}\">"
					: "<a href=\"$attachurl/$attach[attachment]\" target=\"_blank\">$attach[filename]</a> (".sizecount($attach['filesize']).")";
					$thread['attach'] .= "<br /><br />$lang[attachment]: ".attachtype(fileext($thread['filename'])."\t".$attach['filetype']).$attach['url'];
				}
			} else {
				$thread['attach'] = '';
			}

			$optiondata = $optionlist = array();
			if($thread['sortid']) {
				if(@include_once DISCUZ_ROOT.'./forumdata/cache/threadsort_'.$thread['sortid'].'.php') {
					$sortquery = $db->query("SELECT optionid, value FROM {$tablepre}typeoptionvars WHERE tid='$thread[tid]'");
					while($option = $db->fetch_array($sortquery)) {
						$optiondata[$option['optionid']] = $option['value'];
					}

					foreach($_DTYPE as $optionid => $option) {
						$optionlist[$option['identifier']]['title'] = $_DTYPE[$optionid]['title'];
						if($_DTYPE[$optionid]['type'] == 'checkbox') {
							$optionlist[$option['identifier']]['value'] = '';
							foreach(explode("\t", $optiondata[$optionid]) as $choiceid) {
								$optionlist[$option['identifier']]['value'] .= $_DTYPE[$optionid]['choices'][$choiceid].'&nbsp;';
							}
						} elseif(in_array($_DTYPE[$optionid]['type'], array('radio', 'select'))) {
							$optionlist[$option['identifier']]['value'] = $_DTYPE[$optionid]['choices'][$optiondata[$optionid]];
						} elseif($_DTYPE[$optionid]['type'] == 'image') {
							$maxwidth = $_DTYPE[$optionid]['maxwidth'] ? 'width="'.$_DTYPE[$optionid]['maxwidth'].'"' : '';
							$maxheight = $_DTYPE[$optionid]['maxheight'] ? 'height="'.$_DTYPE[$optionid]['maxheight'].'"' : '';
							$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? "<a href=\"$optiondata[$optionid]\" target=\"_blank\"><img src=\"$optiondata[$optionid]\"  $maxwidth $maxheight border=\"0\"></a>" : '';
						} elseif($_DTYPE[$optionid]['type'] == 'url') {
							$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? "<a href=\"$optiondata[$optionid]\" target=\"_blank\">$optiondata[$optionid]</a>" : '';
						} elseif($_DTYPE[$optionid]['type'] == 'textarea') {
							$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? nl2br($optiondata[$optionid]) : '';
						} else {
							$optionlist[$option['identifier']]['value'] = $optiondata[$optionid];
						}
					}
				}

				foreach($optionlist as $option) {
					$thread['sortinfo'] .= '<br />'.$option['title'].' '.$option['value'];
				}
			} else {
				$thread['sortinfo'] = '';
			}

			$postlist[] = $thread;
		}
	}

}

?>