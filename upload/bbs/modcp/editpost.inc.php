<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: editpost.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

if(!$discuz_uid || !($forum['ismoderator'])) {
	//	showmessage('admin_nopermission', NULL, 'HALTED');
	exit('Admin Nopermission');
}

if($action == 'editsubject') {

	$orig = $db->fetch_first("SELECT m.adminid, p.first, p.authorid, p.author, p.dateline, p.anonymous, p.invisible FROM {$tablepre}posts p
		LEFT JOIN {$tablepre}members m ON m.uid=p.authorid
		WHERE p.tid='$tid' AND p.first='1' AND fid='$fid'");

	if(empty($orig)) {
		showmessage('thread_nonexistence', NULL, 'AJAXERROR');
	}

	periodscheck('postbanperiods');

	if(empty($forum['allowview'])) {
		if(!$forum['viewperm'] && !$readaccess) {
			showmessage('group_nopermission', NULL, 'NOPERM');
		} elseif($forum['viewperm'] && !forumperm($forum['viewperm'])) {
			showmessage('forum_nopermission', NULL, 'NOPERM');
		}
	}

	if(!$forum['ismoderator'] || !$alloweditpost || (in_array($orig['adminid'], array(1, 2, 3)) && $adminid > $orig['adminid'])) {
		showmessage('post_edit_nopermission', NULL, 'HALTED');
	}

	if(!submitcheck('editsubjectsubmit', 1)) {
		include template('modcp_editpost');
		exit;
	} else {
		require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
		$subjectnew = dhtmlspecialchars(censor(trim($subjectnew)));
		if(strlen($subjectnew) > 80) {
			showmessage('post_subject_toolong');
		}
		$query = $db->query("UPDATE {$tablepre}threads SET subject='$subjectnew' WHERE tid='$tid'");
		$query = $db->query("UPDATE {$tablepre}posts SET subject='$subjectnew' WHERE tid='$tid' AND first='1'");
		showmessage('<a href="viewthread.php?tid='.$tid.'">'.stripslashes($subjectnew).'</a>');
	}

} elseif($action == 'editmessage') {

	$orig = $db->fetch_first("SELECT m.adminid, p.first, p.authorid, p.author, p.dateline, p.anonymous, p.invisible, p.message FROM {$tablepre}posts p
		LEFT JOIN {$tablepre}members m ON m.uid=p.authorid
		WHERE p.pid='$pid' AND p.invisible > -1");

	if(empty($orig)) {
		showmessage('post_check', NULL, 'AJAXERROR');
	}

	periodscheck('postbanperiods');

	if(empty($forum['allowview'])) {
		if(!$forum['viewperm'] && !$readaccess) {
			showmessage('group_nopermission', NULL, 'NOPERM');
		} elseif($forum['viewperm'] && !forumperm($forum['viewperm'])) {
			showmessage('forum_nopermission', NULL, 'NOPERM');
		}
	}

	if(!$forum['ismoderator'] || !$alloweditpost || (in_array($orig['adminid'], array(1, 2, 3)) && $adminid > $orig['adminid'])) {
		showmessage('post_edit_nopermission', NULL, 'HALTED');
	}

	if(!submitcheck('editmessagesubmit', 1)) {
		include template('modcp_editpost');
		exit;
	} else {
		require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
		if($do == 'notupdate') {
			$message = $orig['message'];
			$message = discuzcode($message, 0, 0, 0, $forum['allowsmilies'], $forum['allowbbcode'], ($forum['allowimgcode'] && $showimages ? 1 : 0), $forum['allowhtml'], 0, 0, $orig['authorid']);
			showmessage(stripslashes($message));
		} else {
			$message = censor(trim($message));
			if(!$disablepostctrl) {
				if($maxpostsize && strlen($message) > $maxpostsize) {
					showmessage('post_message_toolong');
				} elseif($minpostsize && strlen(preg_replace("/\[quote\].+?\[\/quote\]/is", '', $message)) < $minpostsize) {
					showmessage('post_message_tooshort');
				}
			}
			$query = $db->query("UPDATE {$tablepre}posts SET message='$message' WHERE pid='$pid'");
			$message = discuzcode($message, 0, 0, 0, $forum['allowsmilies'], $forum['allowbbcode'], ($forum['allowimgcode'] && $showimages ? 1 : 0), $forum['allowhtml'], 0, 0, $orig['authorid']);
			showmessage(stripslashes($message));
		}
	}

}

include template('modcp_editpost');

?>