<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magic_renew.inc.php 19412 2009-08-29 01:48:51Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(submitcheck('usesubmit')) {

	if(empty($pid)) {
		showmessage('magics_info_nonexistence');
	}

	$post = getpostinfo($pid, 'pid', array('p.tid', 'p.fid', 'p.authorid', 'first', 'anonymous'));
	checkmagicperm($magicperm['forum'], $post['fid']);

	$query = $db->query("SELECT username FROM {$tablepre}members WHERE uid='$post[authorid]'");
	$author = daddslashes($db->result($query, 0), 1);
	$thread = getpostinfo($post['tid'], 'tid', array('tid', 'subject', 'author', 'replies', 'lastposter'));

	if($post['first']) {
		$lastposter = $thread['replies'] > 0 ? $thread['lastposter'] : $author;
		$db->query("UPDATE {$tablepre}posts SET anonymous='0' WHERE tid='$post[tid]' AND first='1'");
		updatemagicthreadlog($post['tid'], $magicid, $magic['identifier'], '0', '1');
	} else {
		$lastposter = $author;
		$author = $thread['author'];
		$db->query("UPDATE {$tablepre}posts SET anonymous='0' WHERE pid='$pid'");
	}

	$query = $db->query("SELECT lastpost FROM {$tablepre}forums WHERE fid='$post[fid]'");
	$forum['lastpost'] = explode("\t", $db->result($query, 0));

	if($thread['subject'] == $forum['lastpost'][1] && ($forum['lastpost'][3] == '' && $post['anonymous'])) {
		$lastpost = "$thread[tid]\t$thread[subject]\t$timestamp\t$lastposter";
		$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost' WHERE fid='$post[fid]'", 'UNBUFFERED');
	}

	$db->query("UPDATE {$tablepre}threads SET author='$author', lastposter='$lastposter', moderated='1' WHERE tid='$post[tid]'");

	usemagic($magicid, $magic['num']);
	updatemagiclog($magicid, '2', '1', '0', '0', $pid);
	
	if($thread['authorid'] != $discuz_uid) {
		sendnotice($thread['authorid'], 'magic_thread_anonymous', 'systempm');
	}
	
	showmessage('magics_operation_succeed', '', 1);

}

function showmagic() {
	global $pid, $lang;
	magicshowtype($lang['option'], 'top');
	magicshowsetting($lang['target_pid'], 'pid', $pid, 'text');
	magicshowtype('', 'bottom');
}

?>