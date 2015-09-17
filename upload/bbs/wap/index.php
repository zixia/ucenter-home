<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: index.php 16722 2008-11-17 04:38:57Z cnteacher $
*/

define('CURSCRIPT', 'wap');
require_once '../include/common.inc.php';

if(preg_match('/(mozilla|m3gate|winwap|openwave)/i', $_SERVER['HTTP_USER_AGENT'])) {
	dheader("Location: {$boardurl}index.php");
}

require_once './include/global.func.php';
require_once DISCUZ_ROOT.'./include/forum.func.php';
require_once DISCUZ_ROOT.'./include/chinese.class.php';
@include_once(DISCUZ_ROOT.'./forumdata/cache/cache_forums.php');

$discuz_action = 191;

$action = isset($action) ? $action : 'home';
if($action == 'goto' && !empty($url)) {
	header("Location: $url");
	exit();
} else {
	wapheader($bbname);
}

include language('wap');

if(!$wapstatus) {
	wapmsg('wap_disabled');
} elseif($bbclosed) {
	wapmsg('board_closed');
}

$sdb = loadmultiserver('wap');
$chs = '';
if(in_array($action, array('home', 'login', 'register', 'search', 'stats', 'my', 'myphone', 'goto', 'forum', 'thread', 'post'))) {
	require_once './include/'.$action.'.inc.php';
} else {
	wapmsg('undefined_action');
}

wapfooter();

?>
