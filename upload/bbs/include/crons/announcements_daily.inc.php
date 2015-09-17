<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: announcements_daily.inc.php 17476 2008-12-25 02:58:18Z liuqiang $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$db->query("UPDATE {$tablepre}tasks SET available='2' WHERE available='1' AND starttime>'0' AND starttime<='$timestamp' AND (endtime IS NULL OR endtime>'$timestamp')", 'UNBUFFERED');

$db->query("DELETE FROM {$tablepre}announcements WHERE endtime<'$timestamp' AND endtime<>'0'");

if($db->affected_rows()) {
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache(array('announcements', 'announcements_forum', 'pmlist'));
}

?>