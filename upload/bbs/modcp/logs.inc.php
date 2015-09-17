<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: logs.inc.php 16698 2008-11-14 07:58:56Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

if(!isset($_DCACHE['forums'])) {
	include(DISCUZ_ROOT.'./forumdata/cache/cache_forums.php');
}

include language('misc');

$lpp = empty($lpp) ? 20 : intval($lpp);
$lpp = min(200, max(5, $lpp));

$logdir = DISCUZ_ROOT.'./forumdata/logs/';
$logfiles = get_log_files($logdir, 'modcp');

$logs = array();
foreach($logfiles as $logfile) {
	$logs = array_merge($logs, file($logdir.$logfile));
}

$page = max(1, intval($page));
$start = ($page - 1) * $lpp;
$logs = array_reverse($logs);

if(!empty($keyword)) {
	foreach($logs as $key => $value) {
		if(strpos($value, $keyword) === FALSE) {
			unset($logs[$key]);
		}
	}
}

$num = count($logs);
$multipage = multi($num, $lpp, $page, "$cpscript?action=logs&lpp=$lpp&keyword=".rawurlencode($keyword));
$logs = array_slice($logs, $start, $lpp);
$keyword = isset($keyword) ? dhtmlspecialchars($keyword) : '';

$usergroup = array();

$filters = '';

$loglist = array();

foreach($logs as $logrow) {
	$log = explode("\t", $logrow);
	if(empty($log[1])) {
		continue;
	}
	$log[1] = gmdate('y-n-j H:i', $log[1] + $timeoffset * 3600);
	if(strtolower($log[2]) == strtolower($discuz_userss)) {
		$log[2] = '<a href="space.php?username='.rawurlencode($log[2]).'" target="_blank"><b>'.$log[2].'</b></a>';
	}

	$log[5] = trim($log[5]);
	$check = 'modcp_logs_action_'.$log[5];
	$log[5] = isset($language[$check]) ? $language[$check] : $log[5];

	$log[7] = intval($log[7]);
	//$fname = !empty($log[7]) ? strip_tags("{$_DCACHE['forums'][$log[7]]['name']}") : '';
	$log[7] = !empty($log[7]) ? '<a href="forumdisplay.php?fid='.$log[7].'" target="_blank">'.strip_tags("{$_DCACHE['forums'][$log[7]]['name']}").'</a>' : '';

	$log[8] = str_replace(array('GET={};', 'POST={};'), '', $log[8]);
	$log[8] = cutstr($log['8'], 60);

	$loglist[] = $log;
}

function get_log_files($logdir='', $action='action') {
	$dir = opendir($logdir);
	$files = array();
	while($entry = readdir($dir)) {
		$files[] = $entry;
	}
	closedir($dir);

	sort($files);
	$logfile = $action;
	$logfiles = array();
	foreach($files as $file) {
		if(strpos($file, $logfile) !== FALSE) {
			$logfiles[] = $file;
		}
	}
	$logfiles = array_slice($logfiles, -2, 2);
	return $logfiles;
}

?>