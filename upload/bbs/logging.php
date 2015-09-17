<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: logging.php 20592 2009-10-10 06:37:56Z monkey $
*/

define('NOROBOT', TRUE);
define('CURSCRIPT', 'logging');

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/misc.func.php';
require_once DISCUZ_ROOT.'./include/login.func.php';
require_once DISCUZ_ROOT.'./uc_client/client.php';


if($action == 'logout' && !empty($formhash)) {

	if($_DCACHE['settings']['frameon'] && $_DCOOKIE['frameon'] == 'yes') {
		$extrahead .= '<script>if(top != self) {parent.leftmenu.location.reload();}</script>';
	}

	if($formhash != FORMHASH) {
		showmessage('logout_succeed', dreferer());
	}

	$ucsynlogout = $allowsynlogin ? uc_user_synlogout() : '';

	clearcookies();
	$groupid = 7;
	$discuz_uid = 0;
	$discuz_user = $discuz_pw = '';
	$styleid = $_DCACHE['settings']['styleid'];

	showmessage('logout_succeed', dreferer());

} elseif($action == 'seccode') {

	$seccodecheck = 1;
	include template('header_ajax');
	include template('seccheck');
	include template('footer_ajax');

} elseif($action == 'login') {

	if($discuz_uid) {
		$ucsynlogin = '';
		showmessage('login_succeed', $indexname);
	}

	$field = $loginfield == 'uid' ? 'uid' : 'username';

	if(!($loginperm = logincheck())) {
		showmessage('login_strike');
	}

	$seccodecheck = $seccodestatus & 2;
	$seccodescript = '';

	if($seccodecheck && $seccodedata['loginfailedcount']) {
		$seccodecheck = $db->result_first("SELECT count(*) FROM {$tablepre}failedlogins WHERE ip='$onlineip' AND count>='$seccodedata[loginfailedcount]' AND $timestamp-lastupdate<=900");
		$seccodescript = '<script type="text/javascript" reload="1">if($(\'seccodelayer\').innerHTML == \'\') ajaxget(\'logging.php?action=seccode\', \'seccodelayer\');</script>';
	}

	if(!submitcheck('loginsubmit', 1, $seccodecheck)) {

		$discuz_action = 6;

		$referer = dreferer();

		$thetimenow = '(GMT '.($timeoffset > 0 ? '+' : '').$timeoffset.') '.
			dgmdate("$dateformat $timeformat", $timestamp + $timeoffset * 3600).

		$styleselect = '';
		$query = $db->query("SELECT styleid, name FROM {$tablepre}styles WHERE available='1'");
		while($styleinfo = $db->fetch_array($query)) {
			$styleselect .= "<option value=\"$styleinfo[styleid]\">$styleinfo[name]</option>\n";
		}

		$cookietimecheck = !empty($_DCOOKIE['cookietime']) ? 'checked="checked"' : '';

		if($seccodecheck) {
			$seccode = random(6, 1) + $seccode{0} * 1000000;
		}

		$username = !empty($_DCOOKIE['loginuser']) ? htmlspecialchars($_DCOOKIE['loginuser']) : '';
		include template('login');

	} else {

		$discuz_uid = 0;
		$discuz_user = $discuz_pw = $discuz_secques = '';
		$result = userlogin();

		if($result > 0) {
			$ucsynlogin = $allowsynlogin ? uc_user_synlogin($discuz_uid) : '';
			if(!empty($inajax)) {
				$msgforward = unserialize($msgforward);
				$mrefreshtime = intval($msgforward['refreshtime']) * 1000;
				include_once DISCUZ_ROOT.'./forumdata/cache/cache_usergroups.php';
				$usergroups = $_DCACHE['usergroups'][$groupid]['grouptitle'];
				$message = 1;
				include template('login');
			} else {
				if($groupid == 8) {
					showmessage('login_succeed_inactive_member', 'memcp.php');
				} else {
					showmessage('login_succeed', dreferer());
				}
			}
		} elseif($result == -1) {
			$ucresult['username'] = addslashes($ucresult['username']);
			$auth = authcode("$ucresult[username]\t".FORMHASH, 'ENCODE');
			if($inajax) {
				$message = 2;
				$location = $regname.'?action=activation&auth='.rawurlencode($auth);
				include template('login');
			} else {
				showmessage('login_activation', $regname.'?action=activation&auth='.rawurlencode($auth));
			}
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
			showmessage($fmsg, 'logging.php?action=login');
		}

	}

} else {
	showmessage('undefined_action');
}

?>