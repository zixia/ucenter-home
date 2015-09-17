<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magic_top.inc.php 19071 2009-08-12 03:22:17Z tiger $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(submitcheck('usesubmit')) {

	if(empty($tid)) {
		showmessage('magics_info_nonexistence');
	}

	$post = getpostinfo($tid, 'tid', array('fid', 'dateline'));
	checkmagicperm($magicperm['forum'], $post['fid']);
	
	$firstsofa = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threadsmod WHERE magicid='$magicid' AND tid='$tid'");
	
	if($firstsofa >= 1) {
		showmessage('magics_SOFA_message', '', 1);
	}
	
	$sofamessage = $lang['SOFA_message'];
	$dateline = $post['dateline'] + 1;
	$db->query("INSERT INTO {$tablepre}posts (fid, tid, first, author, authorid, dateline, message, useip, usesig)
			VALUES ('$post[fid]', '$tid', '0', '$discuz_user', '$discuz_uid', '$dateline', '$sofamessage', '$onlineip', '1')");

	$db->query("UPDATE {$tablepre}threads SET replies=replies+1, moderated='1' WHERE tid='$tid'", 'UNBUFFERED');
	$db->query("UPDATE {$tablepre}forums SET posts=posts+1, todayposts=todayposts+1 WHERE fid='$post[fid]'", 'UNBUFFERED');

	usemagic($magicid, $magic['num']);
	updatemagiclog($magicid, '2', '1', '0', $tid);
	updatemagicthreadlog($tid, $magicid, $magic['identifier'], $expiration);

	if($thread['authorid'] != $discuz_uid) {
		sendpm($thread['authorid'], 'magics_use_subject', 'magic_thread', 0);
	}

	showmessage('magics_operation_succeed', '', 1);

}

function showmagic() {
	global $tid, $lang;
	magicshowtype($lang['option'], 'top');
	magicshowsetting($lang['target_tid'], 'tid', $tid, 'hidden');
	magicshowtype('', 'bottom');
}

?>