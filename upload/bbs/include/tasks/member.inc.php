<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: member.inc.php 17090 2008-12-05 05:15:08Z liuqiang $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function task_condition() {
}

function task_preprocess() {
	global $db, $tablepre, $task, $discuz_uid, $timestamp;

	$act = $db->result_first("SELECT value FROM {$tablepre}taskvars WHERE taskid='$task[taskid]' AND variable='act'");
	if($act == 'buddy') {
		include_once DISCUZ_ROOT.'./uc_client/client.php';
		$db->query("REPLACE INTO {$tablepre}spacecaches (uid, variable, value, expiration) VALUES ('$discuz_uid', 'buddy$task[taskid]', '".(uc_friend_totalnum($discuz_uid, 1) + uc_friend_totalnum($discuz_uid, 3))."', '$timestamp')");
	} elseif($act == 'favorite') {
		$db->query("REPLACE INTO {$tablepre}spacecaches (uid, variable, value, expiration) VALUES ('$discuz_uid', 'favorite$task[taskid]', '".$db->result_first("SELECT COUNT(*) FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND tid>'0'")."', '$timestamp')");
	}
}

function task_csc($task = array()) {
	global $db, $tablepre, $discuz_uid, $timestamp;

	$taskvars = array('num' => 0);
	$num = 0;
	$query = $db->query("SELECT variable, value FROM {$tablepre}taskvars WHERE taskid='$task[taskid]'");
	while($taskvar = $db->fetch_array($query)) {
		if($taskvar['value']) {
			$taskvars[$taskvar['variable']] = $taskvar['value'];
		}
	}

	$taskvars['time'] = floatval($taskvars['time']);
	if($taskvars['act'] == 'buddy') {
		include_once DISCUZ_ROOT.'./uc_client/client.php';
		$num = uc_friend_totalnum($discuz_uid, 1) + uc_friend_totalnum($discuz_uid, 3) - $db->result_first("SELECT value FROM {$tablepre}spacecaches WHERE uid='$discuz_uid' AND variable='buddy$task[taskid]'");
	} elseif($taskvars['act'] == 'favorite') {
		$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND tid>'0'") - $db->result_first("SELECT value FROM {$tablepre}spacecaches WHERE uid='$discuz_uid' AND variable='favorite$task[taskid]'");
	} elseif($taskvars['act'] == 'magic') {
		$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}magiclog WHERE action='2' AND uid='$discuz_uid'".($taskvars['time'] ? " AND dateline BETWEEN $task[applytime] AND $task[applytime]+3600*$taskvars[time]" : " AND dateline>$task[applytime]"));
	}

	if($num && $num >= $taskvars['num']) {
		if(in_array($taskvars['act'], array('buddy', 'favorite'))) {
			$db->query("DELETE FROM {$tablepre}spacecaches WHERE uid='$discuz_uid' AND variable='$taskvars[act]$task[taskid]'");
		}
		return TRUE;
	} elseif($taskvars['time'] && $timestamp >= $task['applytime'] + 3600 * $taskvars['time'] && (!$num || $num < $taskvars['num'])) {
		return FALSE;
	} else {
		return array('csc' => $num > 0 && $taskvars['num'] ? sprintf("%01.2f", $num / $taskvars['num'] * 100) : 0, 'remaintime' => $taskvars['time'] ? $task['applytime'] + $taskvars['time'] * 3600 - $timestamp : 0);
	}

}

function task_sufprocess() {
}

?>