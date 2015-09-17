<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: notice.php 20442 2009-09-28 01:17:13Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include_once DISCUZ_ROOT.'./forumdata/cache/cache_manyou.php';

$invitenum = $db->result_first("SELECT count(*) FROM {$tablepre}myinvite WHERE touid='$discuz_uid'");
$noticenum = $db->result_first("SELECT count(*) FROM {$tablepre}mynotice WHERE uid='$discuz_uid' AND new='1'");

if($option == 'del') {
	$appid = intval($_GET['appid']);
	$db->query("DELETE FROM {$tablepre}myinvite WHERE appid='$appid' AND touid='$discuz_uid'");
	showmessage('manyou:done', 'userapp.php?script=notice&action=invite');
} elseif($option == 'deluserapp') {
	$hash = trim($_GET['hash']);
	if($action == 'invite') {		
		$query = $db->query("SELECT * FROM {$tablepre}myinvite WHERE hash='$hash' AND touid='$discuz_uid'");
		if($value = $db->fetch_array($query)) {
			$db->query("DELETE FROM {$tablepre}myinvite WHERE hash='$hash' AND touid='$discuz_uid'");
			showmessage('manyou:done', 'userapp.php?script=notice&action=invite');
		} else {
			showmessage('manyou:noperm');
		}
	} else {
		$db->query("DELETE FROM {$tablepre}mynotice WHERE id='$hash' AND uid='$discuz_uid'");
		showmessage('manyou:done', 'userapp.php?script=notice');
	}
}

if($action == 'invite') {
	
	$type = intval($_GET['type']);
	$typesql = $type ? "AND appid='$type'" : '';
	
	$page = isset($page) ? max(1, intval($page)) : 1;
	$start_limit = ($page - 1) * $tpp;
	
	$count = $db->result_first("SELECT count(*) FROM {$tablepre}myinvite WHERE touid='$discuz_uid' $typesql");
	$query = $db->query("SELECT * FROM {$tablepre}myinvite WHERE touid='$discuz_uid' $typesql ORDER BY dateline DESC LIMIT $start_limit,$tpp");
	while($value = $db->fetch_array($query)) {
		$key = md5($value['typename'].$value['type']);
		$list[$key][] = $value;		
		$appidarr[] = $value['appid'];
	}
	
	updateprompt('myinvite', $discuz_uid, 0);
	$multi = multi($count, $tpp, $page, "userapp.php?script=notice&action=invite");

} else {

	$page = isset($page) ? max(1, intval($page)) : 1;
	$start_limit = ($page - 1) * $tpp;
	
	$noticeids = array();
	$count = $db->result_first("SELECT count(*) FROM {$tablepre}mynotice WHERE uid='$discuz_uid'");
	$query = $db->query("SELECT * FROM {$tablepre}mynotice WHERE uid='$discuz_uid' ORDER BY dateline DESC LIMIT $start_limit,$tpp");
	while($value = $db->fetch_array($query)) {
		$value['dateline'] = dgmdate("$dateformat $timeformat", $value['dateline'] + $timeoffset * 3600);
		$list[] = $value;
		$noticeids[] = $value['id'];
	}
	
	if($noticeids) {
		$db->query("UPDATE {$tablepre}mynotice SET new='0' WHERE id IN (".implodeids($noticeids).")");
		updateprompt('mynotice', $discuz_uid, 0);
	}
	
	$multi = multi($count, $tpp, $page, "userapp.php?script=notice");
	
}

include template('manyou_notice');

?>