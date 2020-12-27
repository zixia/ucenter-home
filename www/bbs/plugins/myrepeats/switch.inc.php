<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: switch.inc.php 21275 2009-11-24 08:21:28Z monkey $
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
if(!in_array($groupid, $_DPLUGIN['myrepeats']['vars']['usergroups'])) {
	$query = $db->query("SELECT * FROM {$tablepre}myrepeats WHERE username='$discuz_user'");
	if(!$db->num_rows($query)) {
		showmessage('myrepeats:usergroup_disabled');
	} else {
		$permusers = array();
		while($user = $db->fetch_array($query)) {
			$permusers[] = $user['uid'];
		}
		if(!$db->result_first("SELECT COUNT(*) FROM {$tablepre}members WHERE uid IN (".implodeids($permusers).") AND username='$username'")) {
			showmessage('myrepeats:usergroup_disabled');
		}
	}
}

require_once DISCUZ_ROOT.'./include/login.func.php';
require_once DISCUZ_ROOT.'./include/misc.func.php';

$user = $db->fetch_first("SELECT * FROM {$tablepre}myrepeats WHERE uid='$discuz_uid' AND username='$username'");
$olddiscuz_uid = $discuz_uid;
$olddiscuz_user = $discuz_user;
$olddiscuz_userss = $discuz_userss;
if(!$user) {
	showmessage('myrepeats:user_nonexistence');
} elseif($user['locked']) {
	$usernamess = stripslashes($username);
	showmessage('myrepeats:user_locked');
}

list($password, $questionid, $answer) = explode("\t", authcode($user['logindata'], 'DECODE', $_DCACHE['settings']['authkey']));
$referer = dreferer();

if(!($loginperm = logincheck())) {
	showmessage('myrepeats:login_strike');
}

$result = userlogin();
if($result > 0) {
	$db->query("UPDATE {$tablepre}myrepeats SET lastswitch='$timestamp' WHERE uid='$olddiscuz_uid' AND username='$username'", '');
	$ucsynlogin = $allowsynlogin ? uc_user_synlogin($discuz_uid) : '';
	dsetcookie('mrn', '', -1);
	dsetcookie('mrd', '', -1);
	$comment = $user['comment'] ? '('.$user['comment'].') ' : '';
	if(!$db->result_first("SELECT COUNT(*) FROM {$tablepre}myrepeats WHERE uid='$discuz_uid' AND username='$olddiscuz_user'")) {
		$olddiscuz_userssenc = rawurlencode($olddiscuz_userss);
		showmessage('myrepeats:login_succeed_rsnonexistence');
	} else {
		showmessage('myrepeats:login_succeed', $referer);
	}
} elseif($result == -1) {
	$ucresult['username'] = addslashes($ucresult['username']);
	$auth = authcode("$ucresult[username]\t".FORMHASH, 'ENCODE');
	showmessage('myrepeats:login_activation', $regname.'?action=activation&auth='.rawurlencode($auth).'&referer='.rawurlencode($referer));
} else {
	$password = preg_replace("/^(.{".round(strlen($password) / 4)."})(.+?)(.{".round(strlen($password) / 6)."})$/s", "\\1***\\3", $password);
	$errorlog = dhtmlspecialchars(
		$timestamp."\t".
		($ucresult['username'] ? $ucresult['username'] : stripslashes($username))."\t".
		$password."\t".
		($secques ? "Ques #".intval($questionid) : '')."\t".
		$onlineip);
	writelog('illegallog', $errorlog);
	loginfailed($loginperm);
	$fmsg = $ucresult['uid'] == '-3' ? (empty($questionid) || $answer == '' ? 'login_question_empty' : 'login_question_invalid') : 'login_invalid';
	showmessage('myrepeats:'.$fmsg, $referer);
}

?>