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
	global $db, $tablepre, $buddyid;

	return intval($buddyid[0]) == $db->result_first("SELECT value FROM {$tablepre}taskvars WHERE taskid='$task[taskid]' AND variable='authorid'") ? TRUE : array('csc' => 0, 'remaintime' => 0);
}

function task_sufprocess() {
}

?>