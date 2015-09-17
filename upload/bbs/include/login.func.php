<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: login.func.php 20894 2009-10-29 02:06:08Z zhaoxiongfei $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function userlogin() {
	global $db, $tablepre, $_DCACHE, $ucresult, $username, $password, $questionid, $answer, $loginfield;
	require_once DISCUZ_ROOT.'./uc_client/client.php';

	if($loginfield == 'uid') {
		$isuid = 1;
	} elseif($loginfield == 'email') {
		$isuid = 2;
	} else {
		$isuid = 0;
	}

	$ucresult = uc_user_login($username, $password, $isuid, 1, $questionid, $answer);
	list($tmp['uid'], $tmp['username'], $tmp['password'], $tmp['email'], $duplicate) = daddslashes($ucresult, 1);
	$ucresult = $tmp;

	if($duplicate && $ucresult['uid'] > 0) {
		if($olduid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='".addslashes($ucresult['username'])."'")) {
			require_once DISCUZ_ROOT.'./include/membermerge.func.php';
			membermerge($olduid, $ucresult['uid']);
			uc_user_merge_remove($ucresult['username']);
		} else {
			return 0;
		}
	}

	if($ucresult['uid'] <= 0) {
		return 0;
	}

	$member = $db->fetch_first("SELECT m.uid AS discuz_uid, m.username AS discuz_user, m.password AS discuz_pw, m.secques AS discuz_secques,
		m.email, m.adminid, m.groupid, m.styleid, m.lastvisit, m.lastpost, u.allowinvisible
		FROM {$tablepre}members m LEFT JOIN {$tablepre}usergroups u USING (groupid)
		WHERE m.uid='$ucresult[uid]'");

	if(!$member) {
		return -1;
	}

	$member['discuz_userss'] = $member['discuz_user'];
	$member['discuz_user'] = addslashes($member['discuz_user']);
	foreach($member as $var => $value) {
		$GLOBALS[$var] = $value;
	}

	if(addslashes($member['email']) != $ucresult['email']) {
		$db->query("UPDATE {$tablepre}members SET email='$ucresult[email]' WHERE uid='$ucresult[uid]'");
	}

	if($questionid > 0 && empty($member['discuz_secques'])) {
		$GLOBALS['discuz_secques'] = random(8);
		$db->query("UPDATE {$tablepre}members SET secques='$GLOBALS[discuz_secques]' WHERE uid='$ucresult[uid]'");
	}

	$GLOBALS['styleid'] = $member['styleid'] ? $member['styleid'] : $_DCACHE['settings']['styleid'];

	$cookietime = intval(isset($_POST['cookietime']) ? $_POST['cookietime'] : 0);

	dsetcookie('cookietime', $cookietime, 31536000);
	dsetcookie('auth', authcode("$member[discuz_pw]\t$member[discuz_secques]\t$member[discuz_uid]", 'ENCODE'), $cookietime, 1, true);
	dsetcookie('loginuser');
	dsetcookie('activationauth');
	dsetcookie('pmnum');

	$GLOBALS['sessionexists'] = 0;

	if($_DCACHE['settings']['frameon'] && $_DCOOKIE['frameon'] == 'yes') {
		$GLOBALS['extrahead'] .= '<script>if(top != self) {parent.leftmenu.location.reload();}</script>';
	}

	return 1;
}

?>