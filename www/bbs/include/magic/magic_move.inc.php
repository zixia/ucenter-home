<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magic_move.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(submitcheck('usesubmit')) {

	if(empty($tid) && empty($moveto)) {
		showmessage('magics_info_nonexistence');
	}

	$thread = getpostinfo($tid, 'tid', array('fid', 'tid', 'authorid', 'special'));
	checkmagicperm($magicperm['forum'], $thread['fid']);

	if($thread['authorid'] != $discuz_uid) {
		showmessage('magics_operation_nopermission');
	}

	if($thread['special']) {
		$query = $db->query("SELECT allowpostspecial FROM {$tablepre}forums WHERE fid='$moveto'");
		if(!substr(sprintf('%04b', $forum['allowpostspecial']), -$thread['special'], 1)) {
			showmessage('admin_move_nopermission');
		}
	}

	$query = $db->query("SELECT postperm FROM {$tablepre}forumfields WHERE fid='$moveto'");
	if($forum = $db->fetch_array($query)) {
		if(!$forum['postperm'] && !$allowpost) {
			showmessage('group_nopermission');
		} elseif($forum['postperm'] && !forumperm($forum['postperm'])) {
			showmessage('post_forum_newthread_nopermission');
		}
	}

	$db->query("UPDATE {$tablepre}threads SET fid='$moveto', moderated='1' WHERE tid='$tid'");
	$db->query("UPDATE {$tablepre}posts SET fid='$moveto' WHERE tid='$tid'");

	require_once DISCUZ_ROOT.'./include/post.func.php';
	updateforumcount($moveto);
	updateforumcount($thread['fid']);

	usemagic($magicid, $magic['num']);
	updatemagiclog($magicid, '2', '1', '0', $tid);
	updatemagicthreadlog($tid, $magicid, $magic['identifier']);
	showmessage('magics_operation_succeed', '', 1);

}

function showmagic() {
	global $tid, $lang;
	require_once DISCUZ_ROOT.'./include/forum.func.php';
	magicshowtype($lang['option'], 'top');
	magicshowsetting($lang['target_tid'], 'tid', $tid, 'text');
	magicshowsetting($lang['MVK_target'], '', '', '<select name="moveto">'.forumselect().'</select>');
	magicshowtype('', 'bottom');
}

?>