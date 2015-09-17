<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: memcp.inc.php 21277 2009-11-24 08:49:22Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if(!$discuz_uid) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_myrepeats.php';
$_DPLUGIN['myrepeats']['vars']['usergroups'] = (array)unserialize($_DPLUGIN['myrepeats']['vars']['usergroups']);
if(in_array('', $_DPLUGIN['myrepeats']['vars']['usergroups'])) {
	$_DPLUGIN['myrepeats']['vars']['usergroups'] = array();
}
$singleprem = FALSE;
$permusers = array();
if(!in_array($groupid, $_DPLUGIN['myrepeats']['vars']['usergroups'])) {
	$query = $db->query("SELECT * FROM {$tablepre}myrepeats WHERE username='$discuz_user'");
	if(!$db->num_rows($query)) {
		showmessage('myrepeats:usergroup_disabled');
	} else {
		$singleprem = TRUE;
		while($user = $db->fetch_array($query)) {
			$permusers[] = $user['uid'];
		}
		$query = $db->query("SELECT username FROM {$tablepre}members WHERE uid IN (".implodeids($permusers).")");
		$permusers = array();
		while($user = $db->fetch_array($query)) {
			$permusers[] = $user['username'];
		}
	}
}

if($op == 'add' && submitcheck('adduser')) {
	if($singleprem && in_array(stripslashes($usernamenew), $permusers) || !$singleprem) {
		$usernamenew = strip_tags($usernamenew);
		$logindata = addslashes(authcode($passwordnew."\t".$questionidnew."\t".$answernew, 'ENCODE', $_DCACHE['settings']['authkey']));
		if($db->result_first("SELECT COUNT(*) FROM {$tablepre}myrepeats WHERE uid='$discuz_uid' AND username='$usernamenew'")) {
			$db->query("UPDATE {$tablepre}myrepeats SET logindata='$logindata' WHERE uid='$discuz_uid' AND username='$usernamenew'");
		} else {
			$db->query("INSERT INTO {$tablepre}myrepeats (uid, username, logindata, comment) VALUES ('$discuz_uid', '$usernamenew', '$logindata', '".strip_tags($commentnew)."')");
		}
		dsetcookie('mrn', '', -1);
		dsetcookie('mrd', '', -1);
		$usernamenew = stripslashes($usernamenew);
		showmessage('myrepeats:adduser_succeed', 'plugin.php?id=myrepeats:memcp');
	}
} elseif($op == 'update' && submitcheck('updateuser')) {
	if(!empty($delete)) {
		$db->query("DELETE FROM {$tablepre}myrepeats WHERE uid='$discuz_uid' AND username IN (".implodeids($delete).")");
	}
	foreach($comment as $user => $v) {
		$db->query("UPDATE {$tablepre}myrepeats SET comment='".strip_tags($v)."' WHERE uid='$discuz_uid' AND username='$user'");
	}
	dsetcookie('mrn', '', -1);
	dsetcookie('mrd', '', -1);
	showmessage('myrepeats:updateuser_succeed', 'plugin.php?id=myrepeats:memcp');
}

$username = empty($username) ? '' : htmlspecialchars(stripslashes($username));

$repeatusers = array();
$query = $db->query("SELECT * FROM {$tablepre}myrepeats WHERE uid='$discuz_uid'");
while($myrepeat = $db->fetch_array($query)) {
	$myrepeat['lastswitch'] = $myrepeat['lastswitch'] ? dgmdate("$dateformat $timeformat", $myrepeat['lastswitch'] + $timeoffset * 3600) : '';
	$myrepeat['usernameenc'] = rawurlencode($myrepeat['username']);
	$myrepeat['comment'] = htmlspecialchars($myrepeat['comment']);
	$repeatusers[] = $myrepeat;
}

include template('myrepeats:memcp');

?>