<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: onlinetime_monthly.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$db->query("UPDATE {$tablepre}onlinetime SET thismonth='0'");
$db->query("UPDATE {$tablepre}statvars SET value='0' WHERE type='onlines' AND variable='lastupdate'");

?>