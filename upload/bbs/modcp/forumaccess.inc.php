<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: forumaccess.inc.php 16698 2008-11-14 07:58:56Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

$list = $logids = array();

include_once(DISCUZ_ROOT.'./include/forum.func.php');
$forumlistall = forumselect(false, false, $fid);

$adderror = $successed = 0;
$new_user = isset($new_user) ? trim($new_user) : '';

if($fid && $forum['ismoderator'] && $new_user != '' && submitcheck('addsubmit')) {
	$deleteaccess = isset($deleteaccess) ? 1 : 0;
	if($new_user != '') {

		$user = $db->fetch_first("SELECT uid, adminid FROM {$tablepre}members WHERE username='$new_user'");
		$uid = $user['uid'];

		if(empty($user)) {
			$adderror = 1;
		} elseif($user['adminid'] && $adminid != 1) {
			$adderror = 2;
		} else {

			$access = $db->fetch_first("SELECT * FROM {$tablepre}access WHERE fid='$fid' AND uid='$uid'");

			if($deleteaccess) {

				if($access && $adminid != 1 && inwhitelist($access)) {
					$adderror = 3;
				} else {
					$successed = true;
					$access && delete_access($user['uid'], $fid);
				}

			} elseif(!(empty($new_view) && empty($new_post) && empty($new_reply) && empty($new_getattach) && empty($new_postattach))) {

				if($new_view == -1) {
					$new_view = $new_post = $new_reply = $new_getattach = $new_postattach = -1;
				} else {
					$new_view = 0;
					$new_post = $new_post ? -1 : 0;
					$new_reply = $new_reply ? -1 : 0;
					$new_getattach = $new_getattach ? -1 : 0;
					$new_postattach = $new_postattach ? -1 : 0;
				}

				if(empty($access)) {
					$successed = true;
					$db->query("INSERT INTO {$tablepre}access SET
						uid='$uid', fid='$fid', allowview='$new_view', allowpost='$new_post', allowreply='$new_reply',
						allowpostattach='$new_postattach', allowgetattach='$new_getattach', adminuser='$discuz_uid', dateline='$timestamp'");
					$db->query("UPDATE {$tablepre}members SET accessmasks='1' WHERE uid='$uid'", 'UNBUFFERED');
						
				} elseif($new_view == -1 && $access['allowview'] == 1 && $adminid != 1) {
					$adderror = 3;
				} else {
					if($adminid > 1) {
						$new_view = $access['allowview'] == 1 ? 1 : $new_view;
						$new_post = $access['allowpost'] == 1 ? 1 : $new_post;
						$new_reply = $access['allowreply'] == 1 ? 1 : $new_reply;
						$new_getattach = $access['allowgetattach'] == 1 ? 1 : $new_getattach;
						$new_postattach = $access['postattach'] == 1 ? 1 : $new_postattach;
					}
					$successed = true;
					$db->query("UPDATE {$tablepre}access SET
						allowview='$new_view', allowpost='$new_post', allowreply='$new_reply',
						allowpostattach='$new_postattach', allowgetattach='$new_getattach',
						adminuser='$discuz_uid', dateline='$timestamp' WHERE uid='$uid' AND fid='$fid'");
					$db->query("UPDATE {$tablepre}members SET accessmasks='1' WHERE uid='$uid'", 'UNBUFFERED');

				}
			}
		}
	}
	$new_user = $adderror ? $new_user : '';
}
$new_user = dhtmlspecialchars($new_user);

$fidadd = $useradd = '';
$suser = isset($suser) ? trim($suser) : '';
if(submitcheck('searchsubmit')) {
	$fidadd = $fid ? "AND fid='$fid'" : '';
	if($suser != '') {
		$suid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$suser'");
		$useradd = "AND uid='$suid'";
	}
}
$suser = dhtmlspecialchars($suser);

$page = max(1, intval($page));
$ppp = 10;
$list = array('pagelink' => '', 'data' => array());

if($num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}access WHERE 1=1 $fidadd $useradd")) {

	$page = $page > ceil($num / $ppp) ? ceil($num / $ppp) : $page;
	$start_limit = ($page - 1) * $ppp;
	$list['pagelink'] = multi($num, $ppp, $page, "modcp.php?fid=$fid&action=$action");

	$query = $db->query("SELECT * FROM {$tablepre}access WHERE 1=1 $fidadd $useradd ORDER BY dateline DESC LIMIT $start_limit, $ppp");
	$uidarray = array();
	while($access = $db->fetch_array($query)) {
		$uidarray[$access['uid']] = $access['uid'];
		$uidarray[$access['adminuser']] = $access['adminuser'];
		$access['allowview'] = accessimg($access['allowview']);
		$access['allowpost'] = accessimg($access['allowpost']);
		$access['allowreply'] = accessimg($access['allowreply']);
		$access['allowpostattach'] = accessimg($access['allowpostattach']);
		$access['allowgetattach'] = accessimg($access['allowgetattach']);
		$access['dateline'] = gmdate("$dateformat", $access['dateline'] + $timeoffset * 3600);
		$access['forum'] = '<a href="forumdisplay.php?fid='.$access['fid'].'" target="_blank">'.strip_tags($_DCACHE['forums'][$access['fid']]['name']).'</a>';
		$list['data'][] = $access;
	}

	$users = array();
	if($uids = implodeids($uidarray)) {
		$query = $db->query("SELECT uid, username FROM {$tablepre}members WHERE uid IN ($uids)");
		while ($user = $db->fetch_array($query)) {
			$users[$user['uid']] = $user['username'];
		}
	}
}

function delete_access($uid, $fid) {
	global $db, $tablepre;
	$db->query("DELETE FROM {$tablepre}access WHERE uid='$uid' AND fid='$fid'");
	$mask = $db->result_first("SELECT count(*) FROM {$tablepre}access WHERE uid='$uid'");
	if(!$mask) {
		$db->query("UPDATE {$tablepre}members SET accessmasks='' WHERE uid='$uid'", 'UNBUFFERED');
	}
}

function accessimg($access) {
	return $access == -1 ? '<img src="images/common/access_disallow.gif" />' :
		($access == 1 ? '<img src="images/common/access_allow.gif" />' : '<img src="images/common/access_normal.gif" />');
}

function inwhitelist($access) {
	$return = false;
	foreach (array('allowview', 'allowpost', 'allowreply', 'allowpostattach', 'allowgetattach') as $key) {
		if($access[$key] == 1) {
			$return = true;
			break;
		}
	}
	return $return;
}

?>