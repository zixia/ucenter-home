<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: announcement.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

define('CURSCRIPT', 'announcement');
require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

$discuz_action = 21;

$query = $db->query("SELECT id, subject, groups, author, starttime, endtime, message FROM {$tablepre}announcements WHERE type!=2 AND starttime<='$timestamp' AND (endtime='0' OR endtime>'$timestamp') ORDER BY displayorder, starttime DESC, id DESC");

if(!$db->num_rows($query)) {
	showmessage('announcement_nonexistence');
}

$announcelist = array();
while($announce = $db->fetch_array($query)) {
	$announce['authorenc'] = rawurlencode($announce['author']);
	$tmp = explode('.', gmdate('Y.m', $announce['starttime'] + $timeoffset * 3600));
	$months[$tmp[0].$tmp[1]] = $tmp;
	if(!empty($m) && $m != gmdate('Ym', $announce['starttime'] + $timeoffset * 3600)) {
		continue;
	}
	$announce['starttime'] = gmdate($dateformat, $announce['starttime'] + $timeoffset * 3600);
	$announce['endtime'] = $announce['endtime'] ? gmdate($dateformat, $announce['endtime'] + $timeoffset * 3600) : '';
	$announce['message'] = $announce['type'] == 1 ? "[url]{$announce[message]}[/url]" : $announce['message'];
	$announce['message'] = nl2br(discuzcode($announce['message'], 0, 0, 1, 1, 1, 1, 1));
	$announcelist[] = $announce;
}

$annid = intval($id);

include template('announcement');

?>