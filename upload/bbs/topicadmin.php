<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: topicadmin.php 20962 2009-11-04 02:55:39Z zhaoxiongfei $
*/

define('CURSCRIPT', 'topicadmin');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/post.func.php';
require_once DISCUZ_ROOT.'./include/misc.func.php';

$discuz_action = 201;
$modpostsnum = 0;
$resultarray = $thread = array();

if(!$discuz_uid || !$forum['ismoderator']) {
	showmessage('admin_nopermission', NULL, 'HALTED');
}

$frommodcp = !empty($frommodcp) ? intval($frommodcp) : 0;

/*
if($forum['type'] == 'forum') {
	$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fid\">$forum[name]</a>";
	$navtitle = strip_tags($forum['name']);
} else {
	$fup = $db->fetch_first("SELECT fid, name FROM {$tablepre}forums WHERE fid='$forum[fup]'");
	$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fup[fid]\">$fup[name]</a> &raquo; <a href=\"forumdisplay.php?fid=$fid\">$forum[name]</a> ";
	$navtitle = strip_tags($fup['name']).' - '.strip_tags($forum['name']);
}
*/

if(!empty($tid)) {

	$thread = $db->fetch_first("SELECT * FROM {$tablepre}threads WHERE tid='$tid' AND fid='$fid' AND displayorder>='0'");
	if(!$thread) {
		showmessage('thread_nonexistence');
	}

	$navigation .= " &raquo; <a href=\"viewthread.php?tid=$tid\">$thread[subject]</a> ";
	$navtitle .= ' - '.$thread['subject'].' - ';

	if(($thread['special'] && in_array($action, array('copy', 'split', 'merge'))) || ($thread['digest'] == '-1' && !in_array($action, array('delpost', 'banpost', 'getip')))) {
		showmessage('special_noaction');
	}
}
// Reason P.M. Preprocess Start
//$reasonpmcheck = $reasonpm == 2 || $reasonpm == 3 ? 'checked="checked" disabled' : '';
if(($reasonpm == 2 || $reasonpm == 3) || !empty($sendreasonpm)) {
	$forumname = strip_tags($forum['name']);
	$sendreasonpm = 1;
} else {
	$sendreasonpm = 0;
}
// End

$postcredits = $forum['postcredits'] ? $forum['postcredits'] : $creditspolicy['post'];
$replycredits = $forum['replycredits'] ? $forum['replycredits'] : $creditspolicy['reply'];
$digestcredits = $forum['digestcredits'] ? $forum['digestcredits'] : $creditspolicy['digest'];
$postattachcredits = $forum['postattachcredits'] ? $forum['postattachcredits'] : $creditspolicy['postattach'];
$handlekey = 'mods';


if($action == 'moderate') {

	require_once DISCUZ_ROOT.'./include/moderation.inc.php';

} elseif($action == 'delpost' && $allowdelpost) {

	$modpostsnum = count($topiclist);
	if(!($deletepids = implodeids($topiclist))) {
		showmessage('admin_delpost_invalid');
	} elseif(!$allowdelpost || !$tid) {
		showmessage('admin_nopermission', NULL, 'HALTED');
	}  else {
		$query = $db->query("SELECT pid FROM {$tablepre}posts WHERE pid IN ($deletepids) AND first='1'");
		if($db->num_rows($query)) {
			dheader("location: {$boardurl}topicadmin.php?action=moderate&operation=delete&optgroup=3&fid=$fid&moderate[]=$thread[tid]&inajax=yes".($infloat ? "&infloat=yes&handlekey=$handlekey" : ''));
		}
	}

	if(!submitcheck('modsubmit')) {

		$deleteid = '';
		foreach($topiclist as $id) {
			$deleteid .= '<input type="hidden" name="topiclist[]" value="'.$id.'" />';
		}

		include template('topicadmin_action');

	} else {

		checkreasonpm();

		$pids = 0;
		$posts = $uidarray = $puidarray = $auidarray = array();
		$losslessdel = $losslessdel > 0 ? $timestamp - $losslessdel * 86400 : 0;
		$query = $db->query("SELECT pid, authorid, dateline, message, first FROM {$tablepre}posts WHERE pid IN ($deletepids) AND tid='$tid'");
		while($post = $db->fetch_array($query)) {
			if(!$post['first']) {
				$posts[] = $post;
				$pids .= ','.$post['pid'];
				if($post['dateline'] < $losslessdel) {
					$uidarray[] = $post['authorid'];
				} else {
					$puidarray[] = $post['authorid'];
				}
				$modpostsnum ++;
			}
		}

		if($uidarray) {
			updatepostcredits('-', $uidarray, array());
		}
		if($puidarray) {
			updatepostcredits('-', $puidarray, $replycredits);
		}
		$query = $db->query("SELECT uid, attachment, thumb, remote FROM {$tablepre}attachments WHERE pid IN ($pids)");
		while($attach = $db->fetch_array($query)) {
			if(in_array($attach['uid'], $puidarray)) {
				$auidarray[$attach['uid']] = !empty($auidarray[$attach['uid']]) ? $auidarray[$attach['uid']] + 1 : 1;
			}
			dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
		}
		if($auidarray) {
			updateattachcredits('-', $auidarray, $postattachcredits);
		}

		$logs = array();
		$query = $db->query("SELECT r.extcredits, r.score, p.authorid, p.author FROM {$tablepre}ratelog r LEFT JOIN {$tablepre}posts p ON r.pid=p.pid WHERE r.pid IN ($pids)");
		while($author = $db->fetch_array($query)) {
			if($author['score'] > 0) {
				$db->query("UPDATE {$tablepre}members SET extcredits{$author[extcredits]}=extcredits{$author[extcredits]}-($author[score]) WHERE uid = $author[authorid]");
				$author[score] = $extcredits[$id]['title'].' '.-$author['score'].' '.$extcredits[$id]['unit'];
				$logs[] = dhtmlspecialchars("$timestamp\t$discuz_userss\t$adminid\t$author[author]\t$author[extcredits]\t$author[score]\t$thread[tid]\t$thread[subject]\t$delpostsubmit");
			}
		}
		if(!empty($logs)) {
			writelog('ratelog', $logs);
			unset($logs);
		}

		$db->query("DELETE FROM {$tablepre}ratelog WHERE pid IN ($pids)");
		$db->query("DELETE FROM {$tablepre}attachments WHERE pid IN ($pids)");
		$db->query("DELETE FROM {$tablepre}attachmentfields WHERE pid IN ($pids)");
		$db->query("DELETE FROM {$tablepre}posts WHERE pid IN ($pids)");
		getstatus($thread['status'], 1) && $db->query("DELETE FROM {$tablepre}postposition WHERE pid IN ($pids)");

		if($thread['special']) {
			$db->query("DELETE FROM {$tablepre}trades WHERE pid IN ($pids)");
		}

		updatethreadcount($tid, 1);
		updateforumcount($fid);

		$forum['threadcaches'] && deletethreadcaches($thread['tid']);

		$modaction = 'DLP';

		$resultarray = array(
		'redirect'	=> "viewthread.php?tid=$tid&page=$page",
		'reasonpm'	=> ($sendreasonpm ? array('data' => $posts, 'var' => 'post', 'item' => 'reason_delete_post') : array()),
		'modtids'	=> 0,
		'modlog'	=> $thread
		);

		procreportlog('', $pids, TRUE);

	}

} elseif($action == 'refund' && $allowrefund && $thread['price'] > 0) {

	if(!isset($extcredits[$creditstransextra[1]])) {
		showmessage('credits_transaction_disabled');
	}

	if($thread['special'] != 0) {
		showmessage('special_refundment_invalid');
	}

	if(!submitcheck('modsubmit')) {

		$payment = $db->fetch_first("SELECT COUNT(*) AS payers, SUM(netamount) AS netincome FROM {$tablepre}paymentlog WHERE tid='$tid'");

		include template('topicadmin_action');

	} else {

		$modaction = 'RFD';
		$modpostsnum ++;

		checkreasonpm();

		$totalamount = 0;
		$amountarray = array();

		$logarray = array();
		$query = $db->query("SELECT * FROM {$tablepre}paymentlog WHERE tid='$tid'");
		while($log = $db->fetch_array($query)) {
			$totalamount += $log['amount'];
			$amountarray[$log['amount']][] = $log['uid'];
		}

		$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[1]=extcredits$creditstransextra[1]-$totalamount WHERE uid='$thread[authorid]'");
		$db->query("UPDATE {$tablepre}threads SET price='-1', moderated='1' WHERE tid='$thread[tid]'");

		foreach($amountarray as $amount => $uidarray) {
			$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[1]=extcredits$creditstransextra[1]+$amount WHERE uid IN (".implode(',', $uidarray).")");
		}

		$db->query("UPDATE {$tablepre}paymentlog SET amount='0', netamount='0' WHERE tid='$tid'");

		$resultarray = array(
		'redirect'	=> "viewthread.php?tid=$tid",
		'reasonpm'	=> ($sendreasonpm ? array('data' => array($thread), 'var' => 'thread', 'item' => 'reason_moderate') : array()),
		'modtids'	=> $thread['tid'],
		'modlog'	=> $thread
		);

	}

} elseif($action == 'repair' && $allowrepairthread) {

	$replies = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0'") - 1;

	$query = $db->query("SELECT a.aid FROM {$tablepre}posts p, {$tablepre}attachments a WHERE a.tid='$tid' AND a.pid=p.pid AND p.invisible='0' LIMIT 1");
	$attachment = $db->num_rows($query) ? 1 : 0;

	$firstpost  = $db->fetch_first("SELECT pid, subject, rate FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' ORDER BY dateline LIMIT 1");
	$firstpost['subject'] = addslashes(cutstr($firstpost['subject'], 79));
	@$firstpost['rate'] = $firstpost['rate'] / abs($firstpost['rate']);

	$lastpost  = $db->fetch_first("SELECT author, dateline FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' ORDER BY dateline DESC LIMIT 1");

	$db->query("UPDATE {$tablepre}threads SET subject='$firstpost[subject]', replies='$replies', lastpost='$lastpost[dateline]', lastposter='".addslashes($lastpost['author'])."', rate='$firstpost[rate]', attachment='$attachment' WHERE tid='$tid'", 'UNBUFFERED');
	$db->query("UPDATE {$tablepre}posts SET first='1', subject='$firstpost[subject]' WHERE pid='$firstpost[pid]'", 'UNBUFFERED');
	$db->query("UPDATE {$tablepre}posts SET first='0' WHERE tid='$tid' AND pid<>'$firstpost[pid]'", 'UNBUFFERED');
	showmessage('admin_repair_succeed');

} elseif($action == 'getip' && $allowviewip) {
	$member = $db->fetch_first("SELECT m.adminid, p.first, p.useip FROM {$tablepre}posts p
				LEFT JOIN {$tablepre}members m ON m.uid=p.authorid
				WHERE pid='$pid' AND tid='$tid'");
	if(!$member) {
		showmessage('thread_nonexistence', NULL, 'HALTED');
	} elseif(($member['adminid'] == 1 && $adminid > 1) || ($member['adminid'] == 2 && $adminid > 2)) {
		showmessage('admin_getip_nopermission', NULL, 'HALTED');
	} elseif($member['first'] && $thread['digest'] == '-1') {
		showmessage('special_noaction');
	}

	$member['iplocation'] = convertip($member['useip']);

	include template('topicadmin_getip');

} elseif($action == 'split' && $allowsplitthread) {

	if(!submitcheck('modsubmit')) {

		require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

		$replies = $thread['replies'];
		if($replies <= 0) {
			showmessage('admin_split_invalid');
		}

		$postlist = array();
		$query = $db->query("SELECT * FROM {$tablepre}posts WHERE tid='$tid' ORDER BY dateline");
		while($post = $db->fetch_array($query)) {
			$post['message'] = discuzcode($post['message'], $post['smileyoff'], $post['bbcodeoff'], sprintf('%00b', $post['htmlon']), $forum['allowsmilies'], $forum['allowbbcode'], $forum['allowimgcode'], $forum['allowhtml']);
			$postlist[] = $post;
		}
		include template('topicadmin_action');

	} else {

		if(!trim($subject)) {
			showmessage('admin_split_subject_invalid');
		} elseif(!($nos = explode(',', $split))) {
			showmessage('admin_split_new_invalid');
		}

		sort($nos);
		$maxno = $nos[count($nos) - 1];
		$maxno = $maxno > $thread['replies'] + 1 ? $thread['replies'] + 1 : $maxno;
		$maxno = max(1, intval($maxno));
		$query = $db->query("SELECT pid FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' ORDER BY dateline LIMIT $maxno");
		$i = 1;
		$pids = array();
		while($post = $db->fetch_array($query)) {
			if(in_array($i, $nos)) {
				$pids[] = $post['pid'];
			}
			$i++;
		}
		if(!($pids = implode(',',$pids))) {
			showmessage('admin_split_new_invalid');
		}

		$modaction = 'SPL';

		checkreasonpm();

		$subject = dhtmlspecialchars($subject);
		$db->query("INSERT INTO {$tablepre}threads (fid, subject) VALUES ('$fid', '$subject')");
		$newtid = $db->insert_id();

		$db->query("UPDATE {$tablepre}posts SET tid='$newtid' WHERE pid IN ($pids)");
		$db->query("UPDATE {$tablepre}attachments SET tid='$newtid' WHERE pid IN ($pids)");

		$splitauthors = array();
		$query = $db->query("SELECT pid, tid, authorid, subject, dateline FROM {$tablepre}posts WHERE tid='$newtid' AND invisible='0' GROUP BY authorid ORDER BY dateline");
		while($splitauthor = $db->fetch_array($query)) {
			$splitauthor['subject'] = $subject;
			$splitauthors[] = $splitauthor;
		}

		$db->query("UPDATE {$tablepre}posts SET first='1', subject='$subject' WHERE pid='".$splitauthors[0]['pid']."'", 'UNBUFFERED');

		$fpost = $db->fetch_first("SELECT pid, author, authorid, dateline FROM {$tablepre}posts WHERE tid='$tid' ORDER BY dateline LIMIT 1");
		$db->query("UPDATE {$tablepre}threads SET author='".addslashes($fpost['author'])."', authorid='$fpost[authorid]', dateline='$fpost[dateline]', moderated='1' WHERE tid='$tid'");
		$db->query("UPDATE {$tablepre}posts SET subject='".addslashes($thread['subject'])."' WHERE pid='$fpost[pid]'");

		$fpost = $db->fetch_first("SELECT author, authorid, dateline, rate FROM {$tablepre}posts WHERE tid='$newtid' ORDER BY dateline ASC LIMIT 1");
		$db->query("UPDATE {$tablepre}threads SET author='".addslashes($fpost['author'])."', authorid='$fpost[authorid]', dateline='$fpost[dateline]', rate='".intval(@($fpost['rate'] / abs($fpost['rate'])))."', moderated='1' WHERE tid='$newtid'");

		updatethreadcount($tid);
		updatethreadcount($newtid);
		updateforumcount($fid);

		$forum['threadcaches'] && deletethreadcaches($thread['tid']);

		$modpostsnum++;
		$resultarray = array(
		'redirect'	=> "forumdisplay.php?fid=$fid",
		'reasonpm'	=> ($sendreasonpm ? array('data' => $splitauthors, 'var' => 'thread', 'item' => 'reason_moderate') : array()),
		'modtids'	=> $thread['tid'].','.$newtid,
		'modlog'	=> array($thread, array('tid' => $newtid, 'subject' => $subject))
		);

	}

} elseif($action == 'merge' && $allowmergethread) {

	if(!submitcheck('modsubmit')) {

		include template('topicadmin_action');

	} else {

		$modaction = 'MRG';

		checkreasonpm();

		$other = $db->fetch_first("SELECT tid, fid, authorid, subject, views, replies, dateline, special FROM {$tablepre}threads WHERE tid='$othertid' AND displayorder>='0'");
		if(!$other) {
			showmessage('admin_merge_nonexistence');
		} elseif($other['special']) {
			showmessage('special_noaction');
		}
		if($othertid == $tid || ($adminid == 3 && $other['fid'] != $forum['fid'])) {
			showmessage('admin_merge_invalid');
		}

		$other['views'] = intval($other['views']);
		$other['replies']++;

		$firstpost = $db->fetch_first("SELECT pid, fid, authorid, author, subject, dateline FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' ORDER BY dateline LIMIT 1");

		$db->query("UPDATE {$tablepre}posts SET tid='$tid' WHERE tid='$othertid'");
		$postsmerged = $db->affected_rows();

		$db->query("UPDATE {$tablepre}attachments SET tid='$tid' WHERE tid='$othertid'");
		$db->query("DELETE FROM {$tablepre}threads WHERE tid='$othertid'");
		$db->query("DELETE FROM {$tablepre}threadsmod WHERE tid='$othertid'");

		$db->query("UPDATE {$tablepre}posts SET first=(pid='$firstpost[pid]'), fid='$firstpost[fid]' WHERE tid='$tid'");
		$db->query("UPDATE {$tablepre}threads SET authorid='$firstpost[authorid]', author='".addslashes($firstpost['author'])."', subject='".addslashes($firstpost['subject'])."', dateline='$firstpost[dateline]', views=views+$other[views], replies=replies+$other[replies], moderated='1' WHERE tid='$tid'");

		if($fid == $other['fid']) {
			$db->query("UPDATE {$tablepre}forums SET threads=threads-1 WHERE fid='$fid'");
		} else {
			$db->query("UPDATE {$tablepre}forums SET threads=threads-1, posts=posts-$postsmerged WHERE fid='$other[fid]'");
			$db->query("UPDATE {$tablepre}forums SET posts=$posts+$postsmerged WHERE fid='$fid'");
		}

		$forum['threadcaches'] && deletethreadcaches($thread['tid']);

		$modpostsnum ++;
		$resultarray = array(
		'redirect'	=> "forumdisplay.php?fid=$fid",
		'reasonpm'	=> ($sendreasonpm ? array('data' => array($thread), 'var' => 'thread', 'item' => 'reason_merge') : array()),
		'modtids'	=> $thread['tid'],
		'modlog'	=> array($thread, $other)
		);

	}

} elseif($action == 'copy' && $allowcopythread && $thread) {

	if(!submitcheck('modsubmit')) {
		require_once DISCUZ_ROOT.'./include/forum.func.php';
		$forumselect = forumselect();
		include template('topicadmin_action');

	} else {

		$modaction = 'CPY';
		checkreasonpm();

		$toforum = $db->fetch_first("SELECT fid, name, modnewposts FROM {$tablepre}forums WHERE fid='$copyto' AND status='1' AND type<>'group'");
		if(!$toforum) {
			showmessage('admin_copy_invalid');
		} else {
			$modnewthreads = (!$allowdirectpost || $allowdirectpost == 1) && $toforum['modnewposts'] ? 1 : 0;
			$modnewreplies = (!$allowdirectpost || $allowdirectpost == 2) && $toforum['modnewposts'] ? 1 : 0;
			if($modnewthreads || $modnewreplies) {
				showmessage('admin_copy_hava_mod');
			}
		}


		$thread['tid'] = '';
		$thread['fid'] = $copyto;
		$thread['dateline'] = $thread['lastpost'] = $timestamp;
		$thread['lastposter'] = $thread['author'];
		$thread['views'] = $thread['replies'] = $thread['highlight'] = $thread['digest'] = 0;
		$thread['rate'] = $thread['displayorder'] = $thread['attachment'] = 0;

		$db->query("INSERT INTO {$tablepre}threads VALUES ('".implode("', '", daddslashes($thread, 1))."')");
		$threadid = $db->insert_id();

		if($post = $db->fetch_first("SELECT * FROM {$tablepre}posts WHERE tid='$tid' AND first=1 LIMIT 1")) {
			$post['pid'] = '';
			$post['tid'] = $threadid;
			$post['fid'] = $copyto;
			$post['dateline'] = $timestamp;
			$post['attachment'] = 0;
			$post['invisible'] = $post['rate'] = $post['ratetimes'] = 0;
			$db->query("INSERT INTO {$tablepre}posts VALUES  ('".implode("', '", daddslashes($post, 1))."')");
		}

		updatepostcredits('+', $post['authorid'], '');

		updateforumcount($copyto);
		updateforumcount($fid);

		$modpostsnum ++;
		$resultarray = array(
		'redirect'	=> "forumdisplay.php?fid=$fid",
		'reasonpm'	=> ($sendreasonpm ? array('data' => array($thread), 'var' => 'thread', 'item' => 'reason_copy') : array()),
		'modtids'	=> $thread['tid'],
		'modlog'	=> array($thread, $other)
		);
	}

} elseif($action == 'removereward' && $allowremovereward) {

	if(!is_array($thread) || $thread['special'] != '3') {
		showmessage('reward_end');
	}

	$modaction = 'RMR';
	$answererid = $db->result_first("SELECT answererid FROM {$tablepre}rewardlog WHERE tid='$thread[tid]'");
	$rewardprice = abs($thread['price']);

	if($thread['price'] < 0) {
		$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[2]=extcredits$creditstransextra[2]-'$rewardprice' WHERE uid='$answererid'", 'UNBUFFERED');
	}

	$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[2]=extcredits$creditstransextra[2]+'$rewardprice' WHERE uid='$thread[authorid]'", 'UNBUFFERED');
	$db->query("UPDATE {$tablepre}threads SET special='0', price='0' WHERE tid='$thread[tid]'", 'UNBUFFERED');
	$db->query("DELETE FROM {$tablepre}rewardlog WHERE tid='$thread[tid]'", 'UNBUFFERED');

	showmessage('admin_succeed');

} elseif($action == 'banpost' && $allowbanpost) {

	$modpostsnum = count($topiclist);
	if(!($banpids = implodeids($topiclist))) {
		showmessage('admin_banpost_invalid');
	} elseif(!$allowbanpost || !$tid) {
		showmessage('admin_nopermission', NULL, 'HALTED');
	}

	$posts = array();$banstatus = 0;
	$query = $db->query("SELECT pid, first, authorid, status, dateline, message FROM {$tablepre}posts WHERE pid IN ($banpids) AND tid='$tid'");
	while($post = $db->fetch_array($query)) {
		if($post['first'] && $thread['digest'] == '-1') {
			showmessage('special_noaction');
		}
		$banstatus = ($post['status'] & 1) || $banstatus;
		$posts[] = $post;
	}

	if(!submitcheck('modsubmit')) {

		$banid = $checkunban = $checkban = '';
		foreach($topiclist as $id) {
			$banid .= '<input type="hidden" name="topiclist[]" value="'.$id.'" />';
		}

		$banstatus ? $checkunban = 'checked="checked"' : $checkban = 'checked="checked"';

		include template('topicadmin_action');

	} else {

		$banned = intval($banned);
		$modaction = $banned ? 'BNP' : 'UBN';

		checkreasonpm();

		$pids = $comma = '';
		foreach($posts as $k => $post) {
			if($banned) {
				$db->query("UPDATE {$tablepre}posts SET status=status|1 WHERE pid='$post[pid]'", 'UNBUFFERED');
			} else {
				$db->query("UPDATE {$tablepre}posts SET status=status^1 WHERE pid='$post[pid]' AND status=status|1", 'UNBUFFERED');
			}
			$pids .= $comma.$post['pid'];
			$comma = ',';
		}

		$resultarray = array(
		'redirect'	=> "viewthread.php?tid=$tid&page=$page",
		'reasonpm'	=> ($sendreasonpm ? array('data' => $posts, 'var' => 'post', 'item' => 'reason_ban_post') : array()),
		'modtids'	=> 0,
		'modlog'	=> $thread
		);

		procreportlog('', $pids);

	}

} elseif($action == 'warn' && $allowwarnpost) {

	if(!($warnpids = implodeids($topiclist))) {
		showmessage('admin_warn_invalid');
	} elseif(!$allowbanpost || !$tid) {
		showmessage('admin_nopermission', NULL, 'HALTED');
	}

	$posts = $authors = array();
	$authorwarnings = $warningauthor = $warnstatus = '';
	$query = $db->query("SELECT p.pid, p.authorid, p.author, p.status, p.dateline, p.message, m.adminid FROM {$tablepre}posts p LEFT JOIN {$tablepre}members m ON p.authorid=m.uid WHERE pid IN ($warnpids) AND p.tid='$tid'");
	while($post = $db->fetch_array($query)) {
		if($post['adminid'] == 0 || $post['adminid'] == -1) {
			$warnstatus = ($post['status'] & 2) || $warnstatus;
			$authors[$post['authorid']] = 1;
			$posts[] = $post;
		}
	}

	if(!$posts) {
		showmessage('admin_warn_nopermission', NULL, 'HALTED');
	}
	$authorcount = count(array_keys($authors));
	$modpostsnum = count($posts);

	if($modpostsnum == 1 || $authorcount == 1) {
		$authorwarnings = $db->result_first("SELECT COUNT(*) FROM {$tablepre}warnings WHERE authorid='{$posts[0][authorid]}'");
		$warningauthor = $posts[0]['author'];
	}

	if(!submitcheck('modsubmit')) {

		$warnpid = $checkunwarn = $checkwarn = '';
		foreach($topiclist as $id) {
			$warnpid .= '<input type="hidden" name="topiclist[]" value="'.$id.'" />';
		}

		$warnstatus ? $checkunwarn = 'checked="checked"' : $checkwarn = 'checked="checked"';

		include template('topicadmin_action');

	} else {

		$warned = intval($warned);
		$modaction = $warned ? 'WRN' : 'UWN';

		checkreasonpm();

		$pids = $comma = '';
		foreach($posts as $k => $post) {
			if($post['adminid'] == 0) {
				if($warned && !($post['status'] & 2)) {
					$db->query("UPDATE {$tablepre}posts SET status=status|2 WHERE pid='$post[pid]'", 'UNBUFFERED');
					$reason = cutstr(dhtmlspecialchars($reason), 40);
					$db->query("INSERT INTO {$tablepre}warnings (pid, operatorid, operator, authorid, author, dateline, reason) VALUES ('$post[pid]', '$discuz_uid', '$discuz_user', '$post[authorid]', '".addslashes($post['author'])."', '$timestamp', '$reason')", 'UNBUFFERED');
					$authorwarnings = $db->result_first("SELECT COUNT(*) FROM {$tablepre}warnings WHERE authorid='$post[authorid]' AND dateline>=$timestamp-$warningexpiration*86400");
					if($authorwarnings >= $warninglimit) {
						$member = $db->fetch_first("SELECT adminid, groupid FROM {$tablepre}members WHERE uid='$post[authorid]'");
						if($member && $member['groupid'] != 4) {
							$banexpiry = $timestamp + $warningexpiration * 86400;
							$groupterms = array();
							$groupterms['main'] = array('time' => $banexpiry, 'adminid' => $member['adminid'], 'groupid' => $member['groupid']);
							$groupterms['ext'][4] = $banexpiry;
					  		$db->query("UPDATE {$tablepre}members SET groupid='4', groupexpiry='".groupexpiry($groupterms)."' WHERE uid='$post[authorid]'");
					  		$db->query("UPDATE {$tablepre}memberfields SET groupterms='".addslashes(serialize($groupterms))."' WHERE uid='$post[authorid]'");
					  	}
					}
					$pids .= $comma.$post['pid'];
					$comma = ',';
				} elseif(!$warned && ($post['status'] & 2)) {
					$db->query("UPDATE {$tablepre}posts SET status=status^2 WHERE pid='$post[pid]' AND status=status|2", 'UNBUFFERED');
					$db->query("DELETE FROM {$tablepre}warnings WHERE pid='$post[pid]'", 'UNBUFFERED');
					$pids .= $comma.$post['pid'];
					$comma = ',';
				}
			}
		}

		$resultarray = array(
		'redirect'	=> "viewthread.php?tid=$tid&page=$page",
		'reasonpm'	=> ($sendreasonpm ? array('data' => $posts, 'var' => 'post', 'item' => 'reason_warn_post') : array()),
		'modtids'	=> 0,
		'modlog'	=> $thread
		);

		procreportlog('', $pids);

	}

} elseif($action == 'stamp' && $allowstampthread) {

	@include_once DISCUZ_ROOT.'./forumdata/cache/cache_stamps.php';

	if(!submitcheck('modsubmit')) {

		include template('topicadmin_action');

	} else {

		$modaction = $stamp !== '' ? 'SPA' : 'SPD';
		checkreasonpm();

		$db->query("UPDATE {$tablepre}threads SET moderated='1', ".buildbitsql('status', 5, $stamp !== '')." WHERE tid='$tid'");

		$resultarray = array(
		'redirect'	=> "viewthread.php?tid=$tid&page=$page",
		'reasonpm'	=> ($sendreasonpm ? array('data' => array($thread), 'var' => 'thread', 'item' => $stamp !== '' ? 'reason_stamp_update' : 'reason_stamp_delete') : array()),
		'modaction'	=> $stamp !== '' ? 'S'.sprintf('%02d', $stamp) : 'SPD',
		'modlog'	=> $thread
		);
		$modpostsnum = 1;

		updatemodlog($tid, $modaction);
		$db->query("UPDATE {$tablepre}threadsmod SET stamp='$stamp' WHERE tid='$tid' ORDER BY dateline DESC LIMIT 1");

	}

} else {

	showmessage('undefined_action', NULL, 'HALTED');

}

if($resultarray) {

	if($resultarray['modtids']) {
		updatemodlog($resultarray['modtids'], $modaction, $resultarray['expiration']);
	}

	updatemodworks($modaction, $modpostsnum);
	if(is_array($resultarray['modlog'])) {
		if(isset($resultarray['modlog']['tid'])) {
			modlog($resultarray['modlog'], $modaction);
		} else {
			foreach($resultarray['modlog'] as $thread) {
				modlog($thread, $modaction);
			}
		}
	}

	if($resultarray['reasonpm']) {
		include language('modactions');
		$modaction = $modactioncode[$modaction];
		foreach($resultarray['reasonpm']['data'] as ${$resultarray['reasonpm']['var']}) {
			sendreasonpm($resultarray['reasonpm']['var'], $resultarray['reasonpm']['item']);
		}
	}

	showmessage((isset($resultarray['message']) ? $resultarray['message'] : 'admin_succeed'), $resultarray['redirect']);

}

?>