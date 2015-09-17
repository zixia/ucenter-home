<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: misc.php 21333 2010-01-06 06:47:34Z cnteacher $
*/

define('NOROBOT', TRUE);
define('CURSCRIPT', 'misc');

require_once './include/common.inc.php';
require_once './include/post.func.php';

$feed = array();

if($action == 'maxpages') {

	$pages = intval($pages);
	if(empty($pages)) {
		showmessage('undefined_action', NULL, 'HALTED');
	} else {
		showmessage('max_pages');
	}

} elseif($action == 'paysucceed') {

	showmessage('payonline_succeed', $url);

} elseif($action == 'nav') {

	require_once DISCUZ_ROOT.'./include/forumselect.inc.php';
	exit;

} elseif($action == 'customtopics') {

	if(!submitcheck('keywordsubmit', 1)) {

		if($_DCOOKIE['customkw']) {
			$customkwlist = array();
			foreach(@explode("\t", trim($_DCOOKIE['customkw'])) as $key => $keyword) {
				$keyword = dhtmlspecialchars(trim(stripslashes($keyword)));
				$customkwlist[$key]['keyword'] = $keyword;
				$customkwlist[$key]['url'] = '<a href="topic.php?keyword='.rawurlencode($keyword).'" target="_blank">'.$keyword.'</a> ';
			}
		}

		include template('customtopics');

	} else {

		if(!empty($delete) && is_array($delete)) {
			$keywords = implode("\t", array_diff(explode("\t", $_DCOOKIE['customkw']), $delete));
		} else {
			$keywords = $_DCOOKIE['customkw'];
		}

		if($newkeyword = cutstr(dhtmlspecialchars(preg_replace("/[\s\|\t\,\'\<\>]/", '', $newkeyword)), 20)) {
			if($_DCOOKIE['customkw']) {
				if(!preg_match("/(^|\t)".preg_quote($newkeyword, '/')."($|\t)/i", $keywords)) {
					if(count(explode("\t", $keywords)) >= $qihoo['maxtopics']) {
						$keywords = substr($keywords, (strpos($keywords, "\t") + 1))."\t".$newkeyword;
					} else {
						$keywords .= "\t".$newkeyword;
					}
				}
			} else {
				$keywords = $newkeyword;
			}
		}

		dsetcookie('customkw', stripslashes($keywords), 315360000);
		showmessage('customtopics_updated', $indexname);

	}

} elseif($action == 'attachcredit') {

	if($formhash != FORMHASH) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	$aid = intval($aid);
	$attach = $db->fetch_first("SELECT tid, filename FROM {$tablepre}attachments WHERE aid='$aid'");
	$thread = $db->fetch_first("SELECT fid FROM {$tablepre}threads WHERE tid='$attach[tid]' AND displayorder>='0'");
	$forum = $db->fetch_first("SELECT getattachcredits FROM {$tablepre}forumfields WHERE fid='$thread[fid]'");
	$getattachcredits = $forum['getattachcredits'] ? unserialize($forum['getattachcredits']) : $creditspolicy['getattach'];

	checklowerlimit($getattachcredits, -1);
	updatecredits($discuz_uid, $getattachcredits, -1);

	$policymsg = $p = '';
	foreach($getattachcredits as $id => $policy) {
		$policymsg .= $p.($extcredits[$id]['img'] ? $extcredits[$id]['img'].' ' : '').$extcredits[$id]['title'].' '.$policy.' '.$extcredits[$id]['unit'];
		$p = ', ';
	}

	$ck = substr(md5($aid.$timestamp.md5($authkey)), 0, 8);
	$aidencode = aidencode($aid);
	showmessage('attachment_credit', "attachment.php?aid=$aidencode&ck=$ck", '', 1);

} elseif($action == 'attachpay') {

	$aid = intval($aid);
	$aidencode = aidencode($aid);
	if(!$aid) {
		showmessage('undefined_action', NULL, 'HALTED');
	} elseif(!isset($extcredits[$creditstransextra[1]])) {
		showmessage('credits_transaction_disabled');
	} elseif(!$discuz_uid) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	} else {
		$attach = $db->fetch_first("SELECT a.tid, a.pid, a.uid, a.price, a.filename, af.description, a.readperm, m.username AS author FROM {$tablepre}attachments a LEFT JOIN {$tablepre}attachmentfields af ON a.aid=af.aid LEFT JOIN {$tablepre}members m ON a.uid=m.uid WHERE a.aid='$aid'");
		if($attach['price'] <= 0) {
			showmessage('undefined_action', NULL, 'HALTED');
		}
	}

	if($attach['readperm'] && $attach['readperm'] > $readaccess) {
		showmessage('attachment_forum_nopermission', NULL, 'NOPERM');
	}

	if(($balance = ${'extcredits'.$creditstransextra[1]} - $attach['price']) < ($minbalance = 0)) {
		showmessage('credits_balance_insufficient');
	}

	$sidauth = rawurlencode(authcode($sid, 'ENCODE', $authkey));

	if($db->result_first("SELECT COUNT(*) FROM {$tablepre}attachpaymentlog WHERE aid='$aid' AND uid='$discuz_uid'")) {
		showmessage('attachment_yetpay', "attachment.php?aid=$aidencode", '', 1);
	}

	$discuz_action = 81;

	$attach['netprice'] = round($attach['price'] * (1 - $creditstax));

	if(!submitcheck('paysubmit')) {
		include template('attachpay');
	} else {
		$updateauthor = 1;
		if($maxincperthread > 0) {
			if(($db->result_first("SELECT SUM(netamount) FROM {$tablepre}attachpaymentlog WHERE aid='$aid'")) > $maxincperthread) {
				$updateauthor = 0;
			}
		}
		if($updateauthor) {
			updatecredits($attach['uid'], array($creditstransextra[1] => $attach['netprice']));
		}
		updatecredits($discuz_uid, array($creditstransextra[1] => $attach['price']), -1);
		$db->query("INSERT INTO {$tablepre}attachpaymentlog (uid, aid, authorid, dateline, amount, netamount)
			VALUES ('$discuz_uid', '$aid', '$attach[uid]', '$timestamp', '$attach[price]', '$attach[netprice]')");

		$aidencode = aidencode($aid);
		showmessage('attachment_buy', "attachment.php?aid=$aidencode", '', 1);
	}

} elseif($action == 'viewattachpayments') {

	$discuz_action = 82;

	$loglist = array();
	$query = $db->query("SELECT a.*, m.username FROM {$tablepre}attachpaymentlog a
		LEFT JOIN {$tablepre}members m USING (uid)
		WHERE aid='$aid' ORDER BY dateline");
	while($log = $db->fetch_array($query)) {
		$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
		$loglist[] = $log;
	}

	include template('attachpay_view');

} elseif($action == 'getonlines') {

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}sessions", 0);
	showmessage($num);

} elseif($action == 'swfupload') {

	if($operation == 'config' && $discuz_uid) {

		$swfhash = md5(substr(md5($_DCACHE['settings']['authkey']), 8).$discuz_uid);
		include_once language('swfupload');
		$imageexts = array('jpg','jpeg','gif','png','bmp');
		if($attachextensions !== '') {
			$attachextensions = str_replace(' ', '', $attachextensions);
			$exts = explode(',', $attachextensions);
			if($type == 'image') {
				$exts = array_intersect($imageexts, $exts);
			}
			$attachextensions = '*.'.implode(',*.', $exts);
		} else {
			$attachextensions = $type == 'image' ? '*.'.implode(',*.', $imageexts) : '*.*';
		}
		$depict = $type == 'image' ? "Image File " : 'All Support Formats ';
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><parameter><allowsExtend><extend depict=\"$depict\">$attachextensions</extend></allowsExtend><language>$xmllang</language><config><userid>$discuz_uid</userid><hash>$swfhash</hash><maxupload>$maxattachsize</maxupload></config></parameter>";

	} elseif($operation == 'upload') {

		$uid = intval($_POST['uid']);
		$aid = 0;
		$isimage = 0;
		$simple = !empty($simple) ? $simple : 0;
		$groupid = intval($db->result_first("SELECT groupid FROM {$tablepre}members WHERE uid='$uid'"));
		@include DISCUZ_ROOT.'./forumdata/cache/usergroup_'.$groupid.'.php';
		$swfhash = md5(substr(md5($_DCACHE['settings']['authkey']), 8).$uid);
		$statusid = -1;
		if(!$_FILES['Filedata']['error'] && $_POST['hash'] == $swfhash) {
			require_once './include/post.func.php';
			$attachments = attach_upload('Filedata');
			if($attachments) {
				if(is_array($attachments)) {
					$attach = $attachments[0];
					$isimage = $attach['isimage'];
					if(!$simple) {
						require_once DISCUZ_ROOT.'include/chinese.class.php';
						$c = new Chinese('utf8', $charset);
						$attach['name'] = addslashes($c->Convert(urldecode($attach['name'])));
						if($type != 'image' && $isimage) $isimage = -1;
					} elseif($simple == 1 && $type != 'image' && $isimage) {
						$isimage = -1;
					} elseif($simple == 2 && $type == 'image' && !$isimage) {
						dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
						echo "DISCUZUPLOAD|1|4|0|0|";
						exit;
					}
					$db->query("INSERT INTO {$tablepre}attachments (tid, pid, dateline, readperm, price, filename, filetype, filesize, attachment, downloads, isimage, uid, thumb, remote, width)
						VALUES ('0', '0', '$timestamp', '0', '0', '$attach[name]', '$attach[type]', '$attach[size]', '$attach[attachment]', '0', '$isimage', '$uid', '$attach[thumb]', '$attach[remote]', '$attach[width]')");
					$aid = $db->insert_id();
					$statusid = 0;
					$uploadtag = 'upload';
					if(!$attachid) {
						$uploadtag = 'swfupload';
					}
					write_statlog('', 'action='.$uploadtag, '', '', 'forumstat.php');
				} else {
					$statusid = $attachments;
				}
			} else {
				$statusid = 9;
			}
		} else {
			$statusid = 10;
		}
		if($simple == 1) {
			echo "DISCUZUPLOAD|$statusid|$aid|$isimage";
		} elseif($simple == 2) {
			echo "DISCUZUPLOAD|".($type == 'image' ? '1' : '0')."|$statusid|$aid|$isimage|$attach[attachment]";
		} else {
			echo $aid;
		}
	}
	exit;

} elseif($action == 'upload') {

	$allowpostattach = $forum['allowpostattach'] != -1 && ($forum['allowpostattach'] == 1 || (!$forum['postattachperm'] && $allowpostattach) || ($forum['postattachperm'] && forumperm($forum['postattachperm'])));
	$attachextensions = $forum['attachextensions'] ? $forum['attachextensions'] : $attachextensions;
	if($attachextensions) {
		$imgexts = explode(',', str_replace(' ', '', $attachextensions));
		$imgexts = array_intersect(array('jpg','jpeg','gif','png','bmp'), $imgexts);
		$imgexts = implode(', ', $imgexts);
	} else {
		$imgexts = 'jpg, jpeg, gif, png, bmp';
	}
	$allowpostimg = $allowpostattach && $imgexts;
	if(!$allowpostimg) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	include template('upload');

} elseif($action == 'imme_binding' && $discuz_uid) {

	if(isemail($id)) {
		$msn = $db->result_first("SELECT msn FROM {$tablepre}memberfields WHERE uid='$discuz_uid'");
		$msn = explode("\t", $msn);
		$id = dhtmlspecialchars(substr($id, 0, strpos($id, '@')));
		$msn = "$msn[0]\t$id";
		$db->query("UPDATE {$tablepre}memberfields SET msn='$msn' WHERE uid='$discuz_uid'");
		showmessage('msn_binding_succeed', 'memcp.php');
	} else {
		if($result == 'Declined') {
			dheader("Location: memcp.php");
		} else {
			showmessage('Binding Failed. Visit <a href="http://im.live.cn/imme/index.htm" target="_blank">MSN IMME</a> Q&A, please.');
		}
	}

} elseif($action == 'imme_cancelbinding' && $discuz_uid) {

	$msn = $db->result_first("SELECT msn FROM {$tablepre}memberfields WHERE uid='$discuz_uid'");
	$msn = explode("\t", $msn);
	$db->query("UPDATE {$tablepre}memberfields SET msn='$msn[0]' WHERE uid='$discuz_uid'");
	dheader("Location: http://settings.messenger.live.com/applications/websettings.aspx");

} else {

	if(empty($forum['allowview'])) {
		if(!$forum['viewperm'] && !$readaccess) {
			showmessage('group_nopermission', NULL, 'NOPERM');
		} elseif($forum['viewperm'] && !forumperm($forum['viewperm'])) {
			showmessage('forum_nopermission', NULL, 'NOPERM');
		}
	}

	$thread = $db->fetch_first("SELECT * FROM {$tablepre}threads WHERE tid='$tid' AND displayorder>='0'");
	if($thread['readperm'] && $thread['readperm'] > $readaccess && !$forum['ismoderator'] && $thread['authorid'] != $discuz_uid) {
		showmessage('thread_nopermission', NULL, 'NOPERM');
	}

	if($forum['password'] && $forum['password'] != $_DCOOKIE['fidpw'.$fid]) {
		showmessage('forum_passwd', "forumdisplay.php?fid=$fid");
	}


	if(!$thread) {
		showmessage('thread_nonexistence');
	}

	if($forum['type'] == 'forum') {
		$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fid\">$forum[name]</a> &raquo; <a href=\"viewthread.php?tid=$tid\">$thread[subject]</a> ";
		$navtitle = strip_tags($forum['name']).' - '.$thread['subject'];
	} elseif($forum['type'] == 'sub') {
		$fup = $db->fetch_first("SELECT name, fid FROM {$tablepre}forums WHERE fid='$forum[fup]'");
		$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fup[fid]\">$fup[name]</a> &raquo; <a href=\"forumdisplay.php?fid=$fid\">$forum[name]</a> &raquo; <a href=\"viewthread.php?tid=$tid\">$thread[subject]</a> ";
		$navtitle = strip_tags($fup['name']).' - '.strip_tags($forum['name']).' - '.$thread['subject'];
	}

}

if($action == 'votepoll' && submitcheck('pollsubmit', 1)) {

	if(!$allowvote) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	} elseif(!empty($thread['closed'])) {
		showmessage('thread_poll_closed', NULL, 'NOPERM');
	} elseif(empty($pollanswers)) {
		showmessage('thread_poll_invalid', NULL, 'NOPERM');
	}

	$pollarray = $db->fetch_first("SELECT maxchoices, expiration FROM {$tablepre}polls WHERE tid='$tid'");
	if(!$pollarray) {
		showmessage('undefined_action', NULL, 'HALTED');
	} elseif($pollarray['expiration'] && $pollarray['expiration'] < $timestamp) {
		showmessage('poll_overdue', NULL, 'NOPERM');
	} elseif($pollarray['maxchoices'] && $pollarray['maxchoices'] < count($pollanswers)) {
		showmessage('poll_choose_most', NULL, 'NOPERM');
	}

	$voterids = $discuz_uid ? $discuz_uid : $onlineip;

	$polloptionid = array();
	$query = $db->query("SELECT polloptionid, voterids FROM {$tablepre}polloptions WHERE tid='$tid'");
	while($pollarray = $db->fetch_array($query)) {
		if(strexists("\t".$pollarray['voterids']."\t", "\t".$voterids."\t")) {
			showmessage('thread_poll_voted', NULL, 'NOPERM');
		}
		$polloptionid[] = $pollarray['polloptionid'];
	}

	$polloptionids = '';
	foreach($pollanswers as $key => $id) {
		if(!in_array($id, $polloptionid)) {
			showmessage('undefined_action', NULL, 'HALTED');
		}
		unset($polloptionid[$key]);
		$polloptionids[] = $id;
	}

	$pollanswers = implode('\',\'', $polloptionids);

	$db->query("UPDATE {$tablepre}polloptions SET votes=votes+1, voterids=CONCAT(voterids,'$voterids\t') WHERE polloptionid IN ('$pollanswers')", 'UNBUFFERED');
	$db->query("UPDATE {$tablepre}threads SET lastpost='$timestamp' WHERE tid='$tid'", 'UNBUFFERED');

	updatecredits($discuz_uid, $creditspolicy['votepoll']);

	if($customaddfeed & 4) {
		$feed['icon'] = 'poll';
		$feed['title_template'] = 'feed_thread_votepoll_title';
		$feed['title_data'] = array(
			'subject' => "<a href=\"{$boardurl}viewthread.php?tid=$tid\">$thread[subject]</a>",
			'author' => "<a href=\"space.php?uid=$thread[authorid]\">$thread[author]</a>"
		);
		postfeed($feed);
	}

	$pid = $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$tid' AND first='1'");

	if(!empty($inajax)) {
		showmessage('thread_poll_succeed', "viewthread.php?tid=$tid&viewpid=$pid&inajax=1");
	} else {
		showmessage('thread_poll_succeed', "viewthread.php?tid=$tid");
	}

} elseif($action == 'viewvote') {

	require_once DISCUZ_ROOT.'./include/post.func.php';

	$polloptionid = is_numeric($polloptionid) ? $polloptionid : '';

	$overt = $db->result_first("SELECT overt FROM {$tablepre}polls WHERE tid='$tid'");

	$polloptions = array();
	$query = $db->query("SELECT polloptionid, polloption FROM {$tablepre}polloptions WHERE tid='$tid'");
	while($options = $db->fetch_array($query)) {
		if(empty($polloptionid)) {
			$polloptionid = $options['polloptionid'];
		}
		$options['polloption'] = preg_replace("/\[url=(https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
			"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $options['polloption']);
		$polloptions[] = $options;
	}

	$arrvoterids = array();
	if($overt || $adminid == 1) {
		$voterids = '';
		$voterids = $db->result_first("SELECT voterids FROM {$tablepre}polloptions WHERE polloptionid='$polloptionid'");
		$arrvoterids = explode("\t", trim($voterids));
	}

	if(!empty($arrvoterids)) {
		$arrvoterids = array_slice($arrvoterids, -100);
	}
	$voterlist = $voter = array();
	if($voterids = implodeids($arrvoterids)) {
		$query = $db->query("SELECT uid, username FROM {$tablepre}members WHERE uid IN ($voterids)");
		while($voter = $db->fetch_array($query)) {
			$voterlist[] = $voter;
		}
	}
	include template('viewthread_poll_voters');

} elseif($action == 'rate' && $pid) {

	if(!$raterange) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	} elseif($modratelimit && $adminid == 3 && !$forum['ismoderator']) {
		showmessage('thread_rate_moderator_invalid', NULL, 'HALTED');
	}

	$reasonpmcheck = $reasonpm == 2 || $reasonpm == 3 ? 'checked="checked" disabled' : '';
	if(($reasonpm == 2 || $reasonpm == 3) || !empty($sendreasonpm)) {
		$forumname = strip_tags($forum['name']);
		$sendreasonpm = 1;
	} else {
		$sendreasonpm = 0;
	}

	foreach($raterange as $id => $rating) {
		$maxratetoday[$id] = $rating['mrpd'];
	}

	$query = $db->query("SELECT extcredits, SUM(ABS(score)) AS todayrate FROM {$tablepre}ratelog
		WHERE uid='$discuz_uid' AND dateline>=$timestamp-86400
		GROUP BY extcredits");
	while($rate = $db->fetch_array($query)) {
		$maxratetoday[$rate['extcredits']] = $raterange[$rate['extcredits']]['mrpd'] - $rate['todayrate'];
	}

	$post = $db->fetch_first("SELECT * FROM {$tablepre}posts WHERE pid='$pid' AND invisible='0' AND authorid<>'0'");
	if(!$post || $post['tid'] != $thread['tid'] || !$post['authorid']) {
		showmessage('undefined_action', NULL, 'HALTED');
	} elseif(!$forum['ismoderator'] && $karmaratelimit && $timestamp - $post['dateline'] > $karmaratelimit * 3600) {
		showmessage('thread_rate_timelimit', NULL, 'HALTED');
	} elseif($post['authorid'] == $discuz_uid || $post['tid'] != $tid) {
		showmessage('thread_rate_member_invalid', NULL, 'HALTED');
	} elseif($post['anonymous']) {
		showmessage('thread_rate_anonymous', NULL, 'HALTED');
	} elseif($post['status'] & 1) {
		showmessage('thread_rate_banned', NULL, 'HALTED');
	}

	$allowrate = TRUE;
	if(!$dupkarmarate) {
		$query = $db->query("SELECT pid FROM {$tablepre}ratelog WHERE uid='$discuz_uid' AND pid='$pid' LIMIT 1");
		if($db->num_rows($query)) {
			showmessage('thread_rate_duplicate', NULL, 'HALTED');
		}
	}

	$discuz_action = 71;

	$page = intval($page);

	require_once DISCUZ_ROOT.'./include/misc.func.php';

	if(!submitcheck('ratesubmit')) {

		$referer = $boardurl.'viewthread.php?tid='.$tid.'&page='.$page.'#pid'.$pid;

		$ratelist = array();
		foreach($raterange as $id => $rating) {
			if(isset($extcredits[$id])) {
				$ratelist[$id] = '';
				$rating['max'] = $rating['max'] < $maxratetoday[$id] ? $rating['max'] : $maxratetoday[$id];
				$rating['min'] = -$rating['min'] < $maxratetoday[$id] ? $rating['min'] : -$maxratetoday[$id];
				$offset = abs(ceil(($rating['max'] - $rating['min']) / 10));
				if($rating['max'] > $rating['min']) {
					for($vote = $rating['max']; $vote >= $rating['min']; $vote -= $offset) {
						$ratelist[$id] .= $vote ? '<li>'.($vote > 0 ? '+'.$vote : $vote).'</li>' : '';
					}
				}
			}
		}

		include template('rate');

	} else {

		checkreasonpm();

		$rate = $ratetimes = 0;
		$creditsarray = array();
		foreach($raterange as $id => $rating) {
			$score = intval(${'score'.$id});
			if(isset($extcredits[$id]) && !empty($score)) {
				if(abs($score) <= $maxratetoday[$id]) {
					if($score > $rating['max'] || $score < $rating['min']) {
						showmessage('thread_rate_range_invalid');
					} else {
						$creditsarray[$id] = $score;
						$rate += $score;
						$ratetimes += ceil(max(abs($rating['min']), abs($rating['max'])) / 5);
					}
				} else {
					showmessage('thread_rate_ctrl');
				}
			}
		}

		if(!$creditsarray) {
			showmessage('thread_rate_range_invalid', NULL, 'HALTED');
		}

		updatecredits($post['authorid'], $creditsarray);

		$db->query("UPDATE {$tablepre}posts SET rate=rate+($rate), ratetimes=ratetimes+$ratetimes WHERE pid='$pid'");
		if($post['first']) {
			$threadrate = intval(@($post['rate'] + $rate) / abs($post['rate'] + $rate));
			$db->query("UPDATE {$tablepre}threads SET rate='$threadrate' WHERE tid='$tid'");

			$send_feed = false;
			if(is_array($dzfeed_limit['thread_rate'])) foreach($dzfeed_limit['thread_rate'] as $val) {
				if($post['rate'] < $val && ($post['rate'] + $rate) > $val) {
					$send_feed = true;
					$count = $val;
				}
			}
			if($send_feed) {
				$arg = $data = array();
				$arg['type'] = 'thread_rate';
				$arg['fid'] = $post['fid'];
				$arg['typeid'] = $thread['typeid'];
				$arg['sortid'] = $thread['sortid'];
				$arg['uid'] = $thread['authorid'];
				$arg['username'] = addslashes($thread['author']);
				$data['title']['actor'] = $thread['authorid'] ? "<a href=\"space.php?uid={$thread[authorid]}\" target=\"_blank\">{$thread[author]}</a>" : $thread['author'];
				$data['title']['forum'] = "<a href=\"forumdisplay.php?fid={$thread[fid]}\" target=\"_blank\">".$forum['name'].'</a>';
				$data['title']['count'] = $count;
				$data['title']['subject'] = "<a href=\"viewthread.php?tid={$thread[tid]}\" target=\"_blank\">{$thread[subject]}</a>";
				add_feed($arg, $data);
			}
		} else {
			$send_feed = false;
			if(is_array($dzfeed_limit['post_rate'])) foreach($dzfeed_limit['post_rate'] as $val) {
				if($post['rate'] < $val && ($post['rate'] + $rate) > $val) {
					$send_feed = true;
					$count = $val;
				}
			}
			if($send_feed) {
				$arg = $data = array();
				$arg['type'] = 'post_rate';
				$arg['fid'] = $post['fid'];
				$arg['typeid'] = $thread['typeid'];
				$arg['sortid'] = $thread['sortid'];
				$arg['uid'] = $thread['authorid'];
				$arg['username'] = addslashes($thread['author']);
				$data['title']['actor'] = $thread['authorid'] ? "<a href=\"space.php?uid={$thread[authorid]}\" target=\"_blank\">{$thread[author]}</a>" : $thread['author'];
				$data['title']['thread'] = "<a href=\"viewthread.php?tid={$thread[tid]}\" target=\"_blank\">{$thread[subject]}</a>";
				$data['title']['count'] = $count;
				add_feed($arg, $data);
			}
		}

		require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
		$sqlvalues = $comma = '';
		$sqlreason = censor(trim($reason));
		$sqlreason = cutstr(dhtmlspecialchars($sqlreason), 40);
		foreach($creditsarray as $id => $addcredits) {
			$sqlvalues .= "$comma('$pid', '$discuz_uid', '$discuz_user', '$id', '$timestamp', '$addcredits', '$sqlreason')";
			$comma = ', ';
		}
		$db->query("INSERT INTO {$tablepre}ratelog (pid, uid, username, extcredits, dateline, score, reason)
			VALUES $sqlvalues", 'UNBUFFERED');

		include_once DISCUZ_ROOT.'./include/post.func.php';
		$forum['threadcaches'] && @deletethreadcaches($tid);

		$reason = dhtmlspecialchars(censor(trim($reason)));
		if($sendreasonpm) {
			$ratescore = $slash = '';
			foreach($creditsarray as $id => $addcredits) {
				$ratescore .= $slash.$extcredits[$id]['title'].' '.($addcredits > 0 ? '+'.$addcredits : $addcredits).' '.$extcredits[$id]['unit'];
				$slash = ' / ';
			}
			sendreasonpm('post', 'rate_reason');
		}

		$logs = array();
		foreach($creditsarray as $id => $addcredits) {
			$logs[] = dhtmlspecialchars("$timestamp\t$discuz_userss\t$adminid\t$post[author]\t$id\t$addcredits\t$tid\t$thread[subject]\t$reason");
		}
		writelog('ratelog', $logs);

		showmessage('thread_rate_succeed', dreferer());
	}
} elseif($action == 'removerate' && $pid) {

	if(!$forum['ismoderator'] || !$raterange) {
		showmessage('undefined_action');
	}

	$reasonpmcheck = $reasonpm == 2 || $reasonpm == 3 ? 'checked="checked" disabled' : '';
	if(($reasonpm == 2 || $reasonpm == 3) || !empty($sendreasonpm)) {
		$forumname = strip_tags($forum['name']);
		$sendreasonpm = 1;
	} else {
		$sendreasonpm = 0;
	}

	foreach($raterange as $id => $rating) {
		$maxratetoday[$id] = $rating['mrpd'];
	}

	$post = $db->fetch_first("SELECT * FROM {$tablepre}posts WHERE pid='$pid' AND invisible='0' AND authorid<>'0'");
	if(!$post || $post['tid'] != $thread['tid'] || !$post['authorid']) {
		showmessage('undefined_action');
	}

	$discuz_action = 71;

	require_once DISCUZ_ROOT.'./include/misc.func.php';

	if(!submitcheck('ratesubmit')) {

		$referer = $boardurl.'viewthread.php?tid='.$tid.'&page='.$page.'#pid'.$pid;
		$ratelogs = array();
		$query = $db->query("SELECT * FROM {$tablepre}ratelog WHERE pid='$pid' ORDER BY dateline");
		while($ratelog = $db->fetch_array($query)) {
			$ratelog['dbdateline'] = $ratelog['dateline'];
			$ratelog['dateline'] = dgmdate("$dateformat $timeformat", $ratelog['dateline'] + $timeoffset * 3600);
			$ratelog['scoreview'] = $ratelog['score'] > 0 ? '+'.$ratelog['score'] : $ratelog['score'];
			$ratelogs[] = $ratelog;
		}

		include template('rate');

	} else {

		checkreasonpm();

		if(!empty($logidarray)) {

			if($sendreasonpm) {
				$ratescore = $slash = '';
			}

			$query = $db->query("SELECT * FROM {$tablepre}ratelog WHERE pid='$pid'");
			$rate = $ratetimes = 0;
			$logs = array();
			while($ratelog = $db->fetch_array($query)) {
				if(in_array($ratelog['uid'].' '.$ratelog['extcredits'].' '.$ratelog['dateline'], $logidarray)) {
					$rate += $ratelog['score'] = -$ratelog['score'];
					$ratetimes += ceil(max(abs($rating['min']), abs($rating['max'])) / 5);
					updatecredits($post['authorid'], array($ratelog['extcredits'] => $ratelog['score']));
					$db->query("DELETE FROM {$tablepre}ratelog WHERE pid='$pid' AND uid='$ratelog[uid]' AND extcredits='$ratelog[extcredits]' AND dateline='$ratelog[dateline]'", 'UNBUFFERED');
					$logs[] = dhtmlspecialchars("$timestamp\t$discuz_userss\t$adminid\t$ratelog[username]\t$ratelog[extcredits]\t$ratelog[score]\t$tid\t$thread[subject]\t$reason\tD");
					if($sendreasonpm) {
						$ratescore .= $slash.$extcredits[$ratelog['extcredits']]['title'].' '.($ratelog['score'] > 0 ? '+'.$ratelog['score'] : $ratelog['score']).' '.$extcredits[$ratelog['extcredits']]['unit'];
						$slash = ' / ';
					}
				}
			}
			writelog('ratelog', $logs);

			if($sendreasonpm) {
				sendreasonpm('post', 'rate_removereason');
			}

			$db->query("UPDATE {$tablepre}posts SET rate=rate+($rate), ratetimes=ratetimes-$ratetimes WHERE pid='$pid'");
			if($post['first']) {
				$threadrate = @intval(@($post['rate'] + $rate) / abs($post['rate'] + $rate));
				$db->query("UPDATE {$tablepre}threads SET rate='$threadrate' WHERE tid='$tid'");
			}

		}

		showmessage('thread_rate_removesucceed', dreferer());

	}

} elseif($action == 'viewratings' && $pid) {

	$queryr = $db->query("SELECT * FROM {$tablepre}ratelog WHERE pid='$pid' ORDER BY dateline DESC");
	$queryp = $db->query("SELECT p.* ".($bannedmessages ? ", m.groupid " : '').
		" FROM {$tablepre}posts p ".
		($bannedmessages ? "LEFT JOIN {$tablepre}members m ON m.uid=p.authorid" : '').
		" WHERE p.pid='$pid' AND p.invisible='0'");

	if(!($db->num_rows($queryr)) || !($db->num_rows($queryp))) {
		showmessage('thread_rate_log_nonexistence');
	}

	$post = $db->fetch_array($queryp);
	if($post['tid'] != $thread['tid']) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	$loglist = $logcount = array();
	while($log = $db->fetch_array($queryr)) {
		$logcount[$log['extcredits']] += $log['score'];
		$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
		$log['score'] = $log['score'] > 0 ? '+'.$log['score'] : $log['score'];
		$log['reason'] = dhtmlspecialchars($log['reason']);
		$loglist[] = $log;
	}

	include template('rate_view');

} elseif($action == 'viewwarning' && $uid) {

	if(!($warnuser = $db->result_first("SELECT username FROM {$tablepre}members WHERE uid='$uid'"))) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	$query = $db->query("SELECT * FROM {$tablepre}warnings WHERE authorid='$uid'");

	if(!($warnnum = $db->num_rows($query))) {
		showmessage('thread_warning_nonexistence');
	}

	$warning = array();
	while($warning = $db->fetch_array($query)) {
		$warning['dateline'] = dgmdate("$dateformat $timeformat", $warning['dateline'] + $timeoffset * 3600);
		$warning['reason'] = dhtmlspecialchars($warning['reason']);
		$warnings[] = $warning;
	}

	$discuz_action = 73;

	include template('warn_view');

} elseif($action == 'pay') {

	if(!isset($extcredits[$creditstransextra[1]])) {
		showmessage('credits_transaction_disabled');
	} elseif($thread['price'] <= 0 || $thread['special'] <> 0) {
		showmessage('undefined_action', NULL, 'HALTED');
	} elseif(!$discuz_uid) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	}

	if(($balance = ${'extcredits'.$creditstransextra[1]} - $thread['price']) < ($minbalance = 0)) {
		showmessage('credits_balance_insufficient');
	}

	if($db->result_first("SELECT COUNT(*) FROM {$tablepre}paymentlog WHERE tid='$tid' AND uid='$discuz_uid'")) {
		showmessage('credits_buy_thread', 'viewthread.php?tid='.$tid);
	}

	$discuz_action = 81;

	$thread['netprice'] = floor($thread['price'] * (1 - $creditstax));

	if(!submitcheck('paysubmit')) {

		include template('pay');

	} else {

		$updateauthor = true;
		if($maxincperthread > 0) {
			if(($db->result_first("SELECT SUM(netamount) FROM {$tablepre}paymentlog WHERE tid='$tid'")) > $maxincperthread) {
				$updateauthor = false;
			}
		}
		if($updateauthor) {
			updatecredits($thread['authorid'], array($creditstransextra[1] => $thread['netprice']));
		}
		updatecredits($discuz_uid, array($creditstransextra[1] => $thread['price']), -1);
		$db->query("INSERT INTO {$tablepre}paymentlog (uid, tid, authorid, dateline, amount, netamount)
			VALUES ('$discuz_uid', '$tid', '$thread[authorid]', '$timestamp', '$thread[price]', '$thread[netprice]')");

		showmessage('thread_pay_succeed', "viewthread.php?tid=$tid");

	}

} elseif($action == 'viewpayments') {

	$discuz_action = 82;

	$loglist = array();
	$query = $db->query("SELECT p.*, m.username FROM {$tablepre}paymentlog p
		LEFT JOIN {$tablepre}members m USING (uid)
		WHERE tid='$tid' ORDER BY dateline");
	while($log = $db->fetch_array($query)) {
		$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
		$loglist[] = $log;
	}

	include template('pay_view');

} elseif($action == 'report') {

	if(!$reportpost) {
		showmessage('thread_report_disabled');
	}

	if(!$discuz_uid) {
		showmessage('not_loggedin', NULL, 'NOPERM');
	}

	if(!$thread || !is_numeric($pid)) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	$discuz_action = 123;

	$floodctrl = $floodctrl * 3;
	if($timestamp - $lastpost < $floodctrl) {
		showmessage('thread_report_flood_ctrl');
	}

	if($db->result_first("SELECT id FROM {$tablepre}reportlog WHERE pid='$pid' AND uid='$discuz_uid'")) {
		showmessage('thread_report_existence');
	}

	if(!submitcheck('reportsubmit')) {

		include template('reportpost');
		exit;

	} else {

		$type = intval($type) ? 1 : 0;
		$reason = cutstr(dhtmlspecialchars($reason), 40);

		$db->query("INSERT INTO {$tablepre}reportlog (fid, pid, uid, username, type, reason, dateline)
			VALUES ('$fid', '$pid', '$discuz_uid', '$discuz_user', '$type', '$reason', '$timestamp')");
		$db->query("UPDATE {$tablepre}forums SET modworks='1' WHERE fid='$fid'");
		$db->query("UPDATE {$tablepre}members SET lastpost='$timestamp' WHERE uid='$discuz_uid'");

		showmessage('thread_report_succeed', "viewthread.php?tid=$tid");

	}

} elseif($action == 'viewthreadmod' && $tid) {

	include_once language('modactions');
	$loglist = array();
	$query = $db->query("SELECT * FROM {$tablepre}threadsmod WHERE tid='$tid' ORDER BY dateline DESC");

	while($log = $db->fetch_array($query)) {
		$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
		$log['expiration'] = !empty($log['expiration']) ? gmdate("$dateformat", $log['expiration'] + $timeoffset * 3600) : '';
		$log['status'] = empty($log['status']) ? 'style="text-decoration: line-through" disabled' : '';
		if(!$modactioncode[$log['action']] && preg_match('/S(\d\d)/', $log['action'], $a) || $log['action'] == 'SPA') {
			@include_once DISCUZ_ROOT.'./forumdata/cache/cache_stamps.php';
			if($log['action'] == 'SPA') {
				$log['action'] = 'SPA'.$log['stamp'];
				$stampid = $log['stamp'];
			} else {
				$stampid = intval($a[1]);
			}
			$modactioncode[$log['action']] = $modactioncode['SPA'].' '.$_DCACHE['stamps'][$stampid]['text'];
		}
		if($log['magicid']) {
			@include_once DISCUZ_ROOT.'./forumdata/cache/cache_magics.php';
			$log['magicname'] = $_DCACHE['magics'][$log['magicid']]['name'];
		}
		$loglist[] = $log;
	}

	if(empty($loglist)) {
		showmessage('threadmod_nonexistence');
	}

	include template('viewthread_mod');

} elseif($action == 'bestanswer' && $tid && $pid && submitcheck('bestanswersubmit')) {

	$forward = 'viewthread.php?tid='.$tid;

	$post = $db->fetch_first("SELECT authorid, first FROM {$tablepre}posts WHERE pid='$pid' and tid='$tid'");

	if(!($thread['special'] == 3 && $post && ($forum['ismoderator'] || $thread['authorid'] == $discuz_uid) && $post['authorid'] != $thread['authorid'] && $post['first'] == 0 && $discuz_uid != $post['authorid'])) {
		showmessage('reward_cant_operate');
	} elseif($post['authorid'] == $thread['authorid']) {
		showmessage('reward_cant_self');
	} elseif($thread['price'] < 0) {
		showmessage('reward_repeat_selection');
	}
	$thread['netprice'] = ceil($price * ( 1 + $creditstax) );
	$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[2]=extcredits$creditstransextra[2]+$thread[price] WHERE uid='$post[authorid]'");
	$db->query("DELETE FROM {$tablepre}rewardlog WHERE tid='$tid' and answererid='$post[authorid]'");
	$db->query("UPDATE {$tablepre}rewardlog SET answererid='$post[authorid]' WHERE tid='$tid' and authorid='$thread[authorid]'");
	$thread['price'] = '-'.$thread['price'];
	$db->query("UPDATE {$tablepre}threads SET price='$thread[price]' WHERE tid='$tid'");
	$db->query("UPDATE {$tablepre}posts SET dateline=$thread[dateline]+1 WHERE pid='$pid'");

	$thread['dateline'] = gmdate("$dateformat $timeformat", $thread['dateline'] + $timeoffset * 3600);
	if($discuz_uid != $thread['authorid']) {
		sendnotice($thread['authorid'], 'reward_question', 'threads');
	}
	sendnotice($post['authorid'], 'reward_bestanswer', 'threads');

	showmessage('reward_completion', $forward);

} elseif($action == 'activityapplies') {

	if(!$discuz_uid) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	if(submitcheck('activitysubmit')) {
		$expiration = $db->result_first("SELECT expiration FROM {$tablepre}activities WHERE tid='$tid'");
		if($expiration && $expiration < $timestamp) {
			showmessage('activity_stop', NULL, 'NOPERM');
		}

		$query = $db->query("SELECT applyid FROM {$tablepre}activityapplies WHERE tid='$tid' and username='$discuz_user'");
		if($db->num_rows($query)) {
			showmessage('activity_repeat_apply', NULL, 'NOPERM');
		}
		$payvalue = intval($payvalue);
		$payment = $payment ? $payvalue : -1;
		$message = cutstr(dhtmlspecialchars($message), 200);
		$contact = cutstr(dhtmlspecialchars($contact), 200);

		$db->query("INSERT INTO {$tablepre}activityapplies (tid, username, uid, message, verified, dateline, payment, contact)
			VALUES ('$tid', '$discuz_user', '$discuz_uid', '$message', '0', '$timestamp', '$payment', '$contact')");

		if($customaddfeed & 4) {
			$feed['icon'] = 'activity';
			$feed['title_template'] = 'feed_reply_activity_title';
			$feed['title_data'] = array(
				'subject' => "<a href=\"{$boardurl}viewthread.php?tid=$tid\">$thread[subject]</a>"
			);
			postfeed($feed);
		}

		showmessage('activity_completion', "viewthread.php?tid=$tid&viewpid=$pid");
	}

} elseif($action == 'activityapplylist') {

	$isactivitymaster = $thread['authorid'] == $discuz_uid || $alloweditactivity;
	$activity = $db->fetch_first("SELECT * FROM {$tablepre}activities WHERE tid='$tid'");
	if(!$activity || $thread['special'] != 4 || !$isactivitymaster) {
		showmessage('undefined_action');
	}

	if(!submitcheck('applylistsubmit')) {
		$sqlverified = $isactivitymaster ? '' : 'AND verified=1';

		if(!empty($uid) && $isactivitymaster) {
			$sqlverified .= " AND uid='$uid'";
		}

		$applylist = array();
		$query = $db->query("SELECT applyid, username, uid, message, verified, dateline, payment, contact FROM {$tablepre}activityapplies WHERE tid='$tid' $sqlverified ORDER BY dateline DESC");
		while($activityapplies = $db->fetch_array($query)) {
			$activityapplies['dateline'] = dgmdate("$dateformat $timeformat", $activityapplies['dateline'] + $timeoffset * 3600);
			$applylist[] = $activityapplies;
		}

		$activity['starttimefrom'] = dgmdate("$dateformat $timeformat", $activity['starttimefrom'] + $timeoffset * 3600);
		$activity['starttimeto'] = $activity['starttimeto'] ? dgmdate("$dateformat $timeformat", $activity['starttimeto'] + $timeoffset * 3600) : 0;
		$activity['expiration'] = $activity['expiration'] ? dgmdate("$dateformat $timeformat", $activity['expiration'] + $timeoffset * 3600) : 0;

		$applynumbers = $db->result_first("SELECT COUNT(*) FROM {$tablepre}activityapplies WHERE tid='$tid' AND verified=1");

		include template('activity_applylist');
	} else {
		if(empty($applyidarray)) {
			showmessage('activity_choice_applicant', "viewthread.php?tid=$tid&do=viewapplylist");
		} else {
			$uidarray = array();
			$ids = implode('\',\'', $applyidarray);
			$query=$db->query("SELECT a.uid FROM {$tablepre}activityapplies a RIGHT JOIN {$tablepre}members m USING(uid) WHERE a.applyid IN ('$ids')");
			while($uid = $db->fetch_array($query)) {
				$uidarray[] = $uid['uid'];
			}
			$activity_subject = $thread['subject'];
			if($operation == 'delete') {
				$db->query("DELETE FROM {$tablepre}activityapplies WHERE applyid IN ('$ids')", 'UNBUFFERED');

				sendnotice(implode(',', $uidarray), 'activity_delete', 'threads');
				showmessage('activity_delete_completion', "viewthread.php?tid=$tid&do=viewapplylist");
			} else {
				$db->query("UPDATE {$tablepre}activityapplies SET verified=1 WHERE applyid IN ('$ids')", 'UNBUFFERED');

				sendnotice(implode(',', $uidarray), 'activity_apply', 'threads');
				showmessage('activity_auditing_completion', "viewthread.php?tid=$tid&do=viewapplylist");
			}
		}
	}

} elseif($action == 'activityexport') {

	$activity = $db->fetch_first("SELECT a.*, p.message FROM {$tablepre}activities a LEFT JOIN {$tablepre}posts p ON p.tid=a.tid AND p.first='1' WHERE a.tid='$tid'");
	if(!$activity || $thread['special'] != 4 || $thread['authorid'] != $discuz_uid && !$alloweditactivity) {
		showmessage('undefined_action');
	}

	$activity['starttimefrom'] = dgmdate("$dateformat $timeformat", $activity['starttimefrom'] + $timeoffset * 3600, 0);
	$activity['starttimeto'] = $activity['starttimeto'] ? dgmdate("$dateformat $timeformat", $activity['starttimeto'] + $timeoffset * 3600, 0) : 0;
	$activity['expiration'] = $activity['expiration'] ? dgmdate("$dateformat $timeformat", $activity['expiration'] + $timeoffset * 3600, 0) : 0;

	$applynumbers = $db->result_first("SELECT COUNT(*) FROM {$tablepre}activityapplies WHERE tid='$tid' AND verified=1");

	$applylist = array();
	$query = $db->query("SELECT applyid, username, uid, message, verified, dateline, payment, contact FROM {$tablepre}activityapplies WHERE tid='$tid' ORDER BY dateline DESC");
	while($apply = $db->fetch_array($query)) {
		$apply['dateline'] = dgmdate("$dateformat $timeformat", $apply['dateline'] + $timeoffset *3600, 0);
		$applylist[] = $apply;
	}

	$filename = "activity_{$tid}.csv";

	ob_end_clean();
	header('Content-Encoding: none');
	header('Content-Type: '.('application/octet-stream'));
	header('Content-Disposition: '.('attachment; ').'filename='.$filename);
	header('Pragma: no-cache');
	header('Expires: 0');
	if(strtoupper($charset) == 'UTF-8') {
		echo chr(0xEF).chr(0xBB).chr(0xBF);
	}
	include template('activity_export');

} elseif($action == 'tradeorder') {

	$trades = array();
	$query=$db->query("SELECT * FROM {$tablepre}trades WHERE tid='$tid' ORDER BY displayorder");

	if($thread['authorid'] != $discuz_uid && !$allowedittrade) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	if(!submitcheck('tradesubmit')) {

		$stickcount = 0;$trades = $tradesstick = array();
		while($trade = $db->fetch_array($query)) {
			$stickcount = $trade['displayorder'] > 0 ? $stickcount + 1 : $stickcount;
			$trade['displayorderview'] = $trade['displayorder'] < 0 ? 128 + $trade['displayorder'] : $trade['displayorder'];
			if($trade['expiration']) {
				$trade['expiration'] = ($trade['expiration'] - $timestamp) / 86400;
				if($trade['expiration'] > 0) {
					$trade['expirationhour'] = floor(($trade['expiration'] - floor($trade['expiration'])) * 24);
					$trade['expiration'] = floor($trade['expiration']);
				} else {
					$trade['expiration'] = -1;
				}
			}
			if($trade['displayorder'] < 0) {
				$trades[] = $trade;
			} else {
				$tradesstick[] = $trade;
			}
		}
		$trades = array_merge($tradesstick, $trades);
		include template('trade_displayorder');

	} else {

		$count = 0;
		while($trade = $db->fetch_array($query)) {
			$displayordernew = abs(intval($displayorder[$trade['pid']]));
			$displayordernew = $displayordernew > 128 ? 0 : $displayordernew;
			if($stick[$trade['pid']]) {
				$count++;
				$displayordernew = $displayordernew == 0 ? 1 : $displayordernew;
			}
			if(!$stick[$trade['pid']] || $displayordernew > 0 && $tradestick < $count) {
				$displayordernew = -1 * (128 - $displayordernew);
			}
			$db->query("UPDATE {$tablepre}trades SET displayorder='".$displayordernew."' WHERE tid='$tid' AND pid='$trade[pid]'");
		}

		showmessage('trade_displayorder_updated', "viewthread.php?tid=$tid");

	}

} elseif($action == 'debatevote') {

	if(!empty($thread['closed'])) {
		showmessage('thread_poll_closed');
	}

	if(!$discuz_uid) {
		showmessage('debate_poll_nopermission');
	}

	$isfirst = empty($pid) ? TRUE : FALSE;

	$debate = $db->fetch_first("SELECT uid, endtime, affirmvoterids, negavoterids FROM {$tablepre}debates WHERE tid='$tid'");

	if(empty($debate)) {
		showmessage('debate_nofound');
	}

	$feed = array();
	if($customaddfeed & 4) {
		$feed['icon'] = 'debate';
		$feed['title_template'] = 'feed_thread_debatevote_title';
		$feed['title_data'] = array(
			'subject' => "<a href=\"{$boardurl}viewthread.php?tid=$tid\">$thread[subject]</a>",
			'author' => "<a href=\"space.php?uid=$thread[authorid]\">$thread[author]</a>"
		);
	}

	if($isfirst) {
		$stand = intval($stand);

		if($stand == 1 || $stand == 2) {
			if(strpos("\t".$debate['affirmvoterids'], "\t$discuz_uid\t") !== FALSE || strpos("\t".$debate['negavoterids'], "\t$discuz_uid\t") !== FALSE) {
				showmessage('debate_poll_voted');
			} elseif($debate['uid'] == $discuz_uid) {
				showmessage('debate_poll_myself');
			} elseif($debate['endtime'] && $debate['endtime'] < $timestamp) {
				showmessage('debate_poll_end');
			}
		}
		if($stand == 1) {
			$db->query("UPDATE {$tablepre}debates SET affirmvotes=affirmvotes+1 WHERE tid='$tid'");
			$db->query("UPDATE {$tablepre}debates SET affirmvoterids=CONCAT(affirmvoterids, '$discuz_uid\t') WHERE tid='$tid'");
		} elseif($stand == 2) {
			$db->query("UPDATE {$tablepre}debates SET negavotes=negavotes+1 WHERE tid='$tid'");
			$db->query("UPDATE {$tablepre}debates SET negavoterids=CONCAT(negavoterids, '$discuz_uid\t') WHERE tid='$tid'");
		}

		if($feed) {
			postfeed($feed);
		}
		showmessage('debate_poll_succeed');
	}

	$debatepost = $db->fetch_first("SELECT stand, voterids, uid FROM {$tablepre}debateposts WHERE pid='$pid' AND tid='$tid'");
	if(empty($debatepost)) {
		showmessage('debate_nofound');
	}
	$debate = array_merge($debate, $debatepost);
	unset($debatepost);

	if($debate['uid'] == $discuz_uid) {
		showmessage('debate_poll_myself', "viewthread.php?tid=$tid");
	} elseif(strpos("\t".$debate['voterids'], "\t$discuz_uid\t") !== FALSE) {
		showmessage('debate_poll_voted', "viewthread.php?tid=$tid");
	} elseif($debate['endtime'] && $debate['endtime'] < $timestamp) {
		showmessage('debate_poll_end', "viewthread.php?tid=$tid");
	}




	/*
	if($isfirst) {
		$sqladd = $debate['stand'] == 1 ? 'affirmvotes=affirmvotes+1' : ($debate['stand'] == 2 ? 'negavotes=negavotes+1' : '');
		if($sqladd) {
			$db->query("UPDATE {$tablepre}debates SET $sqladd WHERE tid='$tid'");
		}
		unset($sqladd);
	}
	*/

	$db->query("UPDATE {$tablepre}debateposts SET voters=voters+1, voterids=CONCAT(voterids, '$discuz_uid\t') WHERE pid='$pid'");

	if($feed) {
		postfeed($feed);
	}
	showmessage('debate_poll_succeed', "viewthread.php?tid=$tid");

} elseif($action == 'debateumpire') {

	$debate = $db->fetch_first("SELECT * FROM {$tablepre}debates WHERE tid='$tid'");

	if(empty($debate)) {
		showmessage('debate_nofound');
	}elseif(!empty($thread['closed']) && $timestamp - $debate['endtime'] > 3600) {
		showmessage('debate_umpire_edit_invalid');
	} elseif($discuz_userss != $debate['umpire']) {
		showmessage('debate_umpire_nopermission');
	}

	$debate = array_merge($debate, $thread);

	if(!submitcheck('umpiresubmit')) {
		$query = $db->query("SELECT SUM(dp.voters) as voters, dp.stand, m.uid, m.username FROM {$tablepre}debateposts dp
			LEFT JOIN {$tablepre}members m ON m.uid=dp.uid
			WHERE dp.tid='$tid' AND dp.stand<>0
			GROUP BY m.uid
			ORDER BY voters DESC
			LIMIT 30");
		$candidate = $candidates = array();
		while($candidate = $db->fetch_array($query)) {
			$candidate['username'] = dhtmlspecialchars($candidate['username']);
			$candidates[$candidate['username']] = $candidate;
		}
		$winnerchecked = array($debate['winner'] => ' checked="checked"');

		list($debate['bestdebater']) = preg_split("/\s/", $debate['bestdebater']);

		include template('debate_umpire');
	} else {
		if(empty($bestdebater)) {
			showmessage('debate_umpire_nofound_bestdebater');
		} elseif(empty($winner)) {
			showmessage('debate_umpire_nofound_winner');
		} elseif(empty($umpirepoint)) {
			showmessage('debate_umpire_nofound_point');
		}
		$bestdebateruid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$bestdebater' LIMIT 1");
		if(!$bestdebateruid) {
			showmessage('debate_umpire_bestdebater_invalid');
		}
		if(!$bestdebaterstand = $db->result_first("SELECT stand FROM {$tablepre}debateposts WHERE tid='$tid' AND uid='$bestdebateruid' AND stand>'0' AND uid<>'$debate[uid]' AND uid<>'$discuz_uid' LIMIT 1")) {
			showmessage('debate_umpire_bestdebater_invalid');
		}
		$arr = $db->fetch_first("SELECT SUM(voters) as voters, COUNT(*) as replies FROM {$tablepre}debateposts WHERE tid='$tid' AND uid='$bestdebateruid'");
		$bestdebatervoters = $arr['voters'];
		$bestdebaterreplies = $arr['replies'];

		$umpirepoint = dhtmlspecialchars($umpirepoint);
		$bestdebater = dhtmlspecialchars($bestdebater);
		$winner = intval($winner);
		$db->query("UPDATE {$tablepre}threads SET closed='1' WHERE tid='$tid'");
		$db->query("UPDATE {$tablepre}debates SET umpirepoint='$umpirepoint', winner='$winner', bestdebater='$bestdebater\t$bestdebateruid\t$bestdebaterstand\t$bestdebatervoters\t$bestdebaterreplies', endtime='$timestamp' WHERE tid='$tid'");
		showmessage('debate_umpire_comment_succeed', 'viewthread.php?tid='.$tid);
	}

} elseif($action == 'recommend') {

	dsetcookie('discuz_recommend', '', -1, 0);
	if(!$recommendthread['status'] || !$allowrecommend) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	if($db->fetch_first("SELECT * FROM {$tablepre}memberrecommend WHERE recommenduid='$discuz_uid' AND tid='$tid'")) {
		showmessage('recommend_duplicate');
	}

	$recommendcount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}memberrecommend WHERE recommenduid='$discuz_uid' AND dateline>$timestamp-86400");
	if($recommendthread['daycount'] && $recommendcount >= $recommendthread['daycount']) {
		showmessage('recommend_outoftimes');
	}

	if($thread['authorid'] == $discuz_uid && !$recommendthread['ownthread']) {
		showmessage('recommend_self_disallow');
	}
	$allowrecommend = intval($do == 'add' ? $allowrecommend : -$allowrecommend);
	if($do == 'add') {
		$heatadd = 'recommend_add=recommend_add+1';
	} else {
		$heatadd = 'recommend_sub=recommend_sub+1';
	}

	$db->query("UPDATE {$tablepre}threads SET heats=heats+'".(abs($allowrecommend) * $heatthread['recommend'])."', recommends=recommends+'$allowrecommend', $heatadd WHERE tid='$tid'");
	$db->query("INSERT INTO {$tablepre}memberrecommend (tid, recommenduid, dateline) VALUES ('$tid', '$discuz_uid', '$timestamp')");

	dsetcookie('discuz_recommend', 1, 43200, 0);
	$allowrecommend = $allowrecommend > 0 ? '+'.$allowrecommend : $allowrecommend;
	if($recommendthread['daycount']) {
		$daycount = $recommendthread['daycount'] - $recommendcount;
		showmessage('recommend_daycount_succed');
	} else {
		showmessage('recommend_succed');
	}

} elseif($action == 'removeindexheats') {

	if($adminid != 1) {
		showmessage('undefined_action', NULL, 'HALTED');
	}
	$db->query("UPDATE {$tablepre}threads SET heats=0 WHERE tid='$tid'");
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache('heats');
	dheader('Location: '.dreferer());

}

?>