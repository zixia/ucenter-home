<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: todayposts_daily.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$yesterdayposts = intval($db->result_first("SELECT sum(todayposts) FROM {$tablepre}forums"));

$historypost = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='historyposts'");

$hpostarray = explode("\t", $historypost);
$historyposts = $hpostarray[1] < $yesterdayposts ? "$yesterdayposts\t$yesterdayposts" : "$yesterdayposts\t$hpostarray[1]";

$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('historyposts', '$historyposts')");
$db->query("UPDATE {$tablepre}forums SET todayposts='0'");

require_once DISCUZ_ROOT.'./include/cache.func.php';
$_DCACHE['settings']['historyposts'] = $historyposts;
updatesettings();

?>