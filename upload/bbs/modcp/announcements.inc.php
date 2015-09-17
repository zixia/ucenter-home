<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: announcements.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

$annlist = null;
$add_successed = false;
$op = empty($op) ? 'add' : $op;

$announce = array();
$announce['checked'] = array('selected="selected"', '');

switch ($op) {

	case 'add':

		if(!submitcheck('submit')) {
			$announce['starttime'] = gmdate("$dateformat", $timestamp + 3600 * $timeoffset);
			$announce['endtime'] = gmdate("$dateformat", $timestamp + 3600 * $timeoffset + 86400 * 30);
		} else {
			$message = is_array($message) ? $message[$type] : '';
			save_announce(0, $starttime, $endtime, $subject, $type, $message, 0);
			$add_successed = true;
		}
		break;

	case 'manage':

		$annlist = get_annlist();

		if(submitcheck('submit')) {
			$delids = array();
			if(!empty($delete) && is_array($delete)) {
				foreach ($delete as $id) {
					$id = intval($id);
					if(isset($annlist[$id])) {
						unset($annlist[$id]);
						$delids[] = $id;
					}
				}
				if($delids) {
					$db->query("DELETE FROM {$tablepre}announcements WHERE id IN(".implodeids($delids).") AND author='$discuz_user'", 'UNBUFFERED');
				}
			}

			$updateorder = false;
			if(!empty($order) && is_array($order)) {
				foreach ($order as $id => $val) {
					$val = intval($val);
					if(isset($annlist[$id]) && $annlist[$id]['displayorder'] != $val) {
						$annlist[$id]['displayorder'] = $val;
						$db->query("UPDATE {$tablepre}announcements SET displayorder='$val' WHERE id='$id'", "UNBUFFERED");
						$updateorder = true;
					}
				}
			}

			if($delids || $updateorder) {
				update_announcecache();
			}
		}

		break;

	case 'edit':

		$id = intval($id);
		$query = $db->query("SELECT * FROM {$tablepre}announcements WHERE id='$id' AND author='$discuz_user'");
		if(!$announce = $db->fetch_array($query)) {
			showmessage('modcp_ann_nofound');
		}

		if(!submitcheck('submit')) {
			$announce['starttime'] = $announce['starttime'] ? gmdate($dateformat, $announce['starttime'] + $_DCACHE['settings']['timeoffset'] * 3600) : '';
			$announce['endtime'] = $announce['endtime'] ? gmdate($dateformat, $announce['endtime'] + $_DCACHE['settings']['timeoffset'] * 3600) : '';
			$announce['message'] = $announce['type'] != 1 ? dhtmlspecialchars($announce['message']) : $announce['message'];
			$announce['checked'] = $announce['type'] != 1 ? array('selected="selected"', '') : array('', 'selected="selected"');
		} else {
			$announce['starttime'] = $starttime;
			$announce['endtime'] = $endtime;
			$announce['checked'] = $type != 1 ? array('selected="selected"', '') : array('', 'selected="selected"');
			$message = $message[$type];
			save_announce($id, $starttime, $endtime, $subject, $type, $message, $displayorder);
			$edit_successed = true;
		}

		break;

}

$annlist = get_annlist();

function get_annlist() {
	global $db, $tablepre, $adminid, $discuz_userss, $dateformat, $_DCACHE;
	$annlist =  array();
	$query = $db->query("SELECT * FROM {$tablepre}announcements ORDER BY displayorder, starttime DESC, id DESC");
	while($announce = $db->fetch_array($query)) {
		$announce['disabled'] = $announce['author'] != $discuz_userss ? 'disabled' : '';
		$announce['starttime'] = $announce['starttime'] ? gmdate($dateformat, $announce['starttime'] + $_DCACHE['settings']['timeoffset'] * 3600) : '-';
		$announce['endtime'] = $announce['endtime'] ? gmdate($dateformat, $announce['endtime'] + $_DCACHE['settings']['timeoffset'] * 3600) : '-';
		$annlist[$announce['id']] = $announce;
	}
	return $annlist;
}

function update_announcecache() {
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache(array('announcements', 'announcements_forum'));
}

function save_announce($id = 0, $starttime, $endtime, $subject, $type, $message, $displayorder = 0) {

	global $db, $tablepre, $discuz_user, $timestamp;

	$displayorder = intval($displayorder);
	$type = intval($type);

	$starttime = empty($starttime) || strtotime($starttime) < $timestamp ? $timestamp : strtotime($starttime);
	$endtime = empty($endtime) ? 0 : (strtotime($endtime) < $starttime ? ($starttime + 86400 * 30) : strtotime($endtime));

	$subject = htmlspecialchars(trim($subject));

	if($type == 1) {
		list($message) = explode("\n", trim($message));
		$message = dhtmlspecialchars($message);
	} else {
		$type = 0;
		$message = trim($message);
	}

	if(empty($subject) || empty($message)) {
		acpmsg('modcp_ann_empty');
	} elseif($type == 1 && substr(strtolower($message), 0, 7) != 'http://') {
		acpmsg('modcp_ann_urlerror');
	} else {
		$sql = "author='$discuz_user', subject='$subject', type='$type', starttime='$starttime', endtime='$endtime',
			message='$message', displayorder='$displayorder'";

		if(empty($id)) {
			$db->query("INSERT INTO {$tablepre}announcements SET $sql");
		} else {
			$db->query("UPDATE {$tablepre}announcements SET $sql WHERE id='$id'", 'UNBUFFERED');
		}
		update_announcecache();
		return true;
	}
}

?>