<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: cleanup_daily.inc.php 20902 2009-10-29 02:54:19Z liulanbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$db->query("UPDATE {$tablepre}advertisements SET available='0' WHERE endtime>'0' AND endtime<='$timestamp'", 'UNBUFFERED');
if($db->affected_rows()) {
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache(array('settings', 'advs_archiver', 'advs_register', 'advs_index', 'advs_forumdisplay', 'advs_viewthread'));
}
$db->query("TRUNCATE {$tablepre}searchindex");
$db->query("DELETE FROM {$tablepre}threadsmod WHERE dateline<'$timestamp'-31536000", 'UNBUFFERED');
$db->query("DELETE FROM {$tablepre}forumrecommend WHERE expiration<'$timestamp'", 'UNBUFFERED');
$db->query("DELETE FROM {$tablepre}feeds WHERE dateline<'$timestamp'-864000", 'UNBUFFERED');
$db->query("DELETE FROM {$tablepre}promptmsgs WHERE new='1' AND dateline<'$timestamp'-259200", 'UNBUFFERED');
$db->query("DELETE FROM {$tablepre}promptmsgs WHERE new='0' AND dateline<'$timestamp'-2592000", 'UNBUFFERED');

if($qihoo['status'] && $qihoo['relatedthreads']) {
	$db->query("DELETE FROM {$tablepre}relatedthreads WHERE expiration<'$timestamp'", 'UNBUFFERED');
}

$db->query("UPDATE {$tablepre}trades SET closed='1' WHERE expiration<>0 AND expiration<'$timestamp'", 'UNBUFFERED');
$db->query("DELETE FROM {$tablepre}tradelog WHERE status=0 AND lastupdate<'".($timestamp - 5 * 86400)."'", 'UNBUFFERED');

if($cachethreadon) {
	removedir($cachethreaddir, TRUE);
}

if($regstatus > 1) {
	$db->query("UPDATE {$tablepre}invites SET status='4' WHERE expiration<'$timestamp' AND status IN ('1', '3')");
}

$delaids = array();
$query = $db->query("SELECT aid, attachment, thumb FROM {$tablepre}attachments WHERE tid='0' AND dateline<$timestamp-86400");
while($attach = $db->fetch_array($query)) {
	dunlink($attach['attachment'], $attach['thumb']);
	$delaids[] = $attach['aid'];
}
if($delaids) {
	$db->query("DELETE FROM {$tablepre}attachments WHERE aid IN (".implodeids($delaids).")", 'UNBUFFERED');
	$db->query("DELETE FROM {$tablepre}attachmentfields WHERE aid IN (".implodeids($delaids).")", 'UNBUFFERED');
}

$uids = $members = array();
$query = $db->query("SELECT uid, groupid, credits FROM {$tablepre}members WHERE groupid IN ('4', '5') AND groupexpiry>'0' AND groupexpiry<'$timestamp'");
while($row = $db->fetch_array($query)) {
	$uids[] = $row['uid'];
	$members[$row[uid]] = $row;
}
if($uids) {
	$query = $db->query("SELECT uid, groupterms FROM {$tablepre}memberfields WHERE uid IN (".implodeids($uids).")");
	while($member = $db->fetch_array($query)) {
		$sql = 'uid=uid';
		$member['groupterms'] = unserialize($member['groupterms']);
		$member['groupid'] = $members[$member[uid]]['groupid'];
		$member['credits'] = $members[$member[uid]]['credits'];
		
		if(!empty($member['groupterms']['main']['groupid'])) {
			$groupidnew = $member['groupterms']['main']['groupid'];
			$adminidnew = $member['groupterms']['main']['adminid'];
			unset($member['groupterms']['main']);
			unset($member['groupterms']['ext'][$member['groupid']]);
			$sql .= ', groupexpiry=\''.groupexpiry($member['groupterms']).'\'';
		} else {
			$query = $db->query("SELECT groupid FROM {$tablepre}usergroups WHERE type='member' AND creditshigher<='$member[credits]' AND creditslower>'$member[credits]'");
			$groupidnew = $db->result($query, 0);
			$adminidnew = 0;
		}
		$sql .= ", adminid='$adminidnew', groupid='$groupidnew'";
		$db->query("UPDATE {$tablepre}members SET $sql WHERE uid='$member[uid]'");
		$db->query("UPDATE {$tablepre}memberfields SET groupterms='".($member['groupterms'] ? addslashes(serialize($member['groupterms'])) : '')."' WHERE uid='$member[uid]'");
	}
}

function removedir($dirname, $keepdir = FALSE) {

	$dirname = wipespecial($dirname);

	if(!is_dir($dirname)) {
		return FALSE;
	}
	$handle = opendir($dirname);
	while(($file = readdir($handle)) !== FALSE) {
		if($file != '.' && $file != '..') {
			$dir = $dirname . DIRECTORY_SEPARATOR . $file;
			is_dir($dir) ? removedir($dir) : unlink($dir);
		}
	}
	closedir($handle);
	return !$keepdir ? (@rmdir($dirname) ? TRUE : FALSE) : TRUE;
}
?>