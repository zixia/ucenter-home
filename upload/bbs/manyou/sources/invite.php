<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: invite.php 20442 2009-09-28 01:17:13Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$_GET['u'] = intval($_GET['u']);

$sitekey = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='siteuniqueid'");
if($_GET['c'] != substr(md5($sitekey.'|'.$_GET['u'].(empty($_GET['app']) ? '' : '|'.$_GET['app'])), 8, 16)) {
	showmessage('manyou:invite_error');
}

$friendname = $db->result_first("SELECT username FROM {$tablepre}members WHERE uid='$_GET[u]'");
if(!$friendname) {
	showmessage('manyou:invite_error');
}

if(!$discuz_uid) {
	$regname .= (strpos($regname, '?') ? '&' : '?').'referer='.rawurlencode($boardurl.'manyou/invite.php?'.$_SERVER['QUERY_STRING']);
	showmessage('manyou:invite_message', $regname);
}

if($_GET['u'] == $discuz_uid) {
	showmessage('manyou:invite_noself');
}

require_once './uc_client/client.php';

uc_friend_add($_GET['u'], $discuz_uid);
uc_friend_add($discuz_uid, $_GET['u']);
manyoulog('friend', $discuz_uid, 'add', $_GET['u']);
manyoulog('friend', $_GET['u'], 'add', $discuz_uid);


showmessage('manyou:invite_friend', 'userapp.php?script=user&id='.$_GET['app'].'&my_extra=invitedby_bi_'.$_GET['u'].'_'.$_GET['c'].'&my_suffix=Lw%3D%3D');

?>