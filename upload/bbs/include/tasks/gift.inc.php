<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: gift.inc.php 17385 2008-12-17 05:05:02Z liuqiang $
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

function task_preprocess($task = array()) {
	if(!isset($task['newbie'])) {
		dheader("Location: task.php?action=draw&id=$task[taskid]");
	}
}

function task_csc($task = array()) {
	return true;
}

function task_sufprocess() {
}

?>