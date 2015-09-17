<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: avatar.inc.php 17065 2008-12-05 01:30:57Z liuqiang $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function task_install() {
	global $db, $tablepre;
}

function task_uninstall() {
	global $db, $tablepre;
}

function task_upgrade() {
	global $db, $tablepre;
}

function task_condition() {
}

function task_preprocess() {
}

function task_csc($task = array()) {
	global $db, $tablepre, $discuz_uid;

	$forumid = $db->result_first("SELECT value FROM {$tablepre}taskvars WHERE taskid='$task[taskid]' AND variable='forumid'");
	return $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE fid='$forumid' AND authorid='$discuz_uid'") ? TRUE : array('csc' => 0, 'remaintime' => 0);
}

function task_sufprocess() {
}

?>