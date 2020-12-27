<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: cleanup_monthly.inc.php 19081 2009-08-12 09:26:57Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$myrecordtimes = $timestamp - $_DCACHE['settings']['myrecorddays'] * 86400;

$db->query("DELETE FROM {$tablepre}invites WHERE dateline<'$timestamp'-2592000 AND status='4'", 'UNBUFFERED');
$db->query("TRUNCATE {$tablepre}relatedthreads");
$db->query("DELETE FROM {$tablepre}mytasks WHERE status='-1' AND dateline<'$timestamp'-2592000", 'UNBUFFERED');

?>