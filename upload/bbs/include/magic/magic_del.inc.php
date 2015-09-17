<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magic_del.inc.php 19960 2009-09-15 23:18:37Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(submitcheck('usesubmit')) {

	if(empty($pid)) {
		showmessage('magics_info_nonexistence');
	}

	$post = getpostinfo($pid, 'pid', array('t.tid', 't.fid', 't.authorid', 'first'));
	checkmagicperm($magicperm['forum'], $post['fid']);

	if($post['authorid'] != $discuz_uid) {
		showmessage('magics_operation_nopermission');
	}

	require_once DISCUZ_ROOT.'./include/post.func.php';

	if($post['first']) {
		foreach(array('threads', 'threadsmod', 'relatedthreads', 'posts', 'polls', 'polloptions', 'trades', 'activities', 'activityapplies', 'attachments', 'favorites', 'debates', 'debateposts', 'typeoptionvars', 'forumrecommend') as $value) {
			$db->query("DELETE FROM {$tablepre}$value WHERE tid='$post[tid]'", 'UNBUFFERED');
		}

		$query = $db->query("SELECT uid, attachment, dateline, thumb, remote FROM {$tablepre}attachments WHERE tid='$post[tid]'");
		while($attach = $db->fetch_array($query)) {
			dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
		}
		updateforumcount($post['fid']);
	} else {
		$db->query("DELETE FROM {$tablepre}posts WHERE pid='$pid'", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}attachments WHERE pid='$pid'", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}attachmentfields WHERE pid='$pid'", 'UNBUFFERED');
		$query = $db->query("SELECT uid, attachment, dateline, thumb, remote FROM {$tablepre}attachments WHERE pid='$pid'");
		while($attach = $db->fetch_array($query)) {
			dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
		}
		updatethreadcount($post['tid']);
	}

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