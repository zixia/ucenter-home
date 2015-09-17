<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: userapp.php 20822 2009-10-26 10:20:57Z monkey $
*/

define('CURSCRIPT', 'userapp');

require_once './include/common.inc.php';
@include_once DISCUZ_ROOT.'./forumdata/cache/cache_scriptlang.php';

if($uchomeurl) {
	showmessage('manyou:uchome_exists');
}

$mnid = 'plink_userapp';
$funcstatinfo[] = 'manyou2dz,20090727';

$script = !empty($script) && in_array($script, array('feed', 'user', 'notice', 'invite', 'cp', 'admincp')) ? $script : 'feed';

if(!$my_status && $script != 'admincp') {
	showmessage('undefined_action', NULL, 'HALTED');
}

if(!empty($id)) {
	$script = 'user';
}

if(!$discuz_uid && $script != 'invite' && $script != 'feed' && $view != 'all') {
	showmessage('not_loggedin', $regname, 'NOPERM');
}

$isfounder = isfounder();
require_once DISCUZ_ROOT.'./manyou/sources/'.$script.'.php';

function isfounder($user = '') {
	$user = empty($user) ? array('uid' => $GLOBALS['discuz_uid'], 'adminid' => $GLOBALS['adminid'], 'username' => $GLOBALS['discuz_userss']) : $user;
	$founders = str_replace(' ', '', $GLOBALS['forumfounders']);
	if($user['adminid'] <> 1) {
		return FALSE;
	} elseif(empty($founders)) {
		return TRUE;
	} elseif(strexists(",$founders,", ",$user[uid],")) {
		return TRUE;
	} elseif(!is_numeric($user['username']) && strexists(",$founders,", ",$user[username],")) {
		return TRUE;
	} else {
		return FALSE;
	}
}

?>