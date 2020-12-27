<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: stats.inc.php 18819 2009-07-23 10:38:43Z liuqiang $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$discuz_action = 194;

$members = $totalmembers;
@extract($sdb->fetch_first("SELECT SUM(threads) AS threads, SUM(posts) AS posts FROM {$tablepre}forums WHERE status='1'"));

echo "<p>$lang[stats]<br /><br />\n".
	"$lang[stats_members]: $members<br />\n".
	"$lang[stats_threads]: $threads<br />\n".
	"$lang[stats_posts]: $posts</p>\n";

?>