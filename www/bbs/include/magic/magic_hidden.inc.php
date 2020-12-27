<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magic_hidden.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(submitcheck('usesubmit')) {

	if(empty($pid)) {
		showmessage('magics_info_nonexistence');
	}

	$post = getpostinfo($pid, 'pid', array('p.tid', 'p.fid', 'p.author', 'p.authorid', 'first', 'p.dateline', 'anonymous'));
	checkmagicperm($magicperm['forum'], $post['fid']);

	if($post['authorid'] != $discuz_uid) {
		showmessage('magics_operation_nopermission');
	}

	$thread = getpostinfo($post['tid'], 'tid', array('tid', 'subject', 'author', 'replies', 'lastposter'));

	if($post['first']) {
		$author = '';
		$lastposter = $thread['replies'] > 0 ? $thread['lastposter'] : '';
		$db->query("UPDATE {$tablepre}posts SET anonymous='1' WHERE tid='$post[tid]' AND first='1'");
		updatemagicthreadlog($post['tid'], $magicid, $magic['identifier'], '0', '1');
	} else {
		$author = $thread['author'];
		$lastposter = '';
		$db->query("UPDATE {$tablepre}posts SET anonymous='1' WHERE pid='$pid'");
	}

	$query = $db->query("SELECT lastpost FROM {$tablepre}forums WHERE fid='$post[fid]'");
	$forum['lastpost'] = explode("\t", $db->result($query, 0));

	if($post['dateline'] == $forum['lastpost'][2] && ($post['author'] == $forum['lastpost'][3] || ($forum['lastpost'][3] == '' && $post['anonymous']))) {
		$lastpost = "$thread[tid]\t$thread[subject]\t$timestamp\t$lastposter";
		$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost' WHERE fid='$post[fid]'", 'UNBUFFERED');
	}

	$db->query("UPDATE {$tablepre}threads SET author='$author', lastposter='$lastposter', moderated='1' WHERE tid='$post[tid]'");

	usemagic($magicid, $magic['num']);
	updatemagiclog($magicid, '2', '1', '0', '0', $pid);
	showmessage('magics_operation_succeed', '', 1);

}

function showmagic() {
	global $pid, $lang;
	magicshowtype($lang['option'], 'top');
	magicshowsetting($lang['target_pid'], 'pid', $pid, 'text');
	magicshowtype('', 'bottom');
}

?>