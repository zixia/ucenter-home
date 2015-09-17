<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: index.php 21048 2009-11-09 05:59:18Z monkey $
*/

define('BINDDOMAIN', 'index');

require_once './include/common.inc.php';

if(!$loadforum) {
	if($indextype) {
		$op = empty($op) ? $indextype : $op;
		$indexfile = in_array($op, array('classics', 'feeds')) ? $op : 'classics';
	} else {
		$indexfile = 'classics';
	}

	if($indexfile == 'classics' || !empty($gid)) {
		require_once DISCUZ_ROOT.'./include/index_classics.inc.php';
	} elseif($indexfile == 'feeds') {
		require_once DISCUZ_ROOT.'./include/index_feeds.inc.php';
	} else {
		showmessage('undefined_action');
	}
} else {
	require_once './forumdisplay.php';
}

?>