<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: post.inc.php 17326 2008-12-15 03:09:58Z liuqiang $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function task_condition() {
}

function task_preprocess() {
}

function task_csc($task = array()) {
	global $db, $tablepre, $discuz_uid, $timestamp;

	$taskvars = array('num' => 0);
	$query = $db->query("SELECT variable, value FROM {$tablepre}taskvars WHERE taskid='$task[taskid]'");
	while($taskvar = $db->fetch_array($query)) {
		if($taskvar['value']) {
			$taskvars[$taskvar['variable']] = $taskvar['value'];
		}
	}

	$tbladd = $sqladd = '';
	if($taskvars['threadid']) {
		$sqladd .= " AND p.tid='$taskvars[threadid]'";
	} else {
		if($taskvars['forumid']) {
			$sqladd .= " AND p.fid='$taskvars[forumid]'";
		}
		if($taskvars['authorid']) {
			$tbladd .= ", {$tablepre}threads t";
			$sqladd .= " AND p.tid=t.tid AND t.authorid='$taskvars[authorid]'";
		}
	}
	if($taskvars['act']) {
		if($taskvars['act'] == 'newthread') {
			$sqladd .= " AND p.first='1'";
		} elseif($taskvars['act'] == 'newreply') {
			$sqladd .= " AND p.first='0'";
		}
	}

	$sqladd .= ($taskvars['time'] = floatval($taskvars['time'])) ? " AND p.dateline BETWEEN $task[applytime] AND $task[applytime]+3600*$taskvars[time]" : " AND p.dateline>$task[applytime]";

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts p $tbladd WHERE p.authorid='$discuz_uid' $sqladd");

	if($num && $num >= $taskvars['num']) {
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