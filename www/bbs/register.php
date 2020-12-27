<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: register.php 21057 2009-11-10 01:05:36Z monkey $
*/

define('CURSCRIPT', 'register');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_profilefields.php';
require_once DISCUZ_ROOT.'./uc_client/client.php';

$discuz_action = 5;

if($discuz_uid) {
	showmessage('login_succeed', $indexname);
} elseif (!$regstatus || !$ucactivation) {
	if($action == 'activation' || $activationauth) {
		if(!$ucactivation) {
			showmessage('register_disable_activation');
		}
	} elseif(!$regstatus) {
		showmessage('register_disable');
	}
}

$inviteconfig = array();
$query = $db->query("SELECT * FROM {$tablepre}settings WHERE variable IN ('bbrules', 'bbrulestxt', 'welcomemsg', 'welcomemsgtitle', 'welcomemsgtxt', 'inviteconfig')");
while($setting = $db->fetch_array($query)) {
	$$setting['variable'] = $setting['value'];
}

$invitecode = $regstatus > 1 && $invitecode ? dhtmlspecialchars($invitecode) : '';
if($regstatus > 1) {
	$inviterewardcredit = $inviteaddcredit = $invitedaddcredit = '';
	@extract(unserialize($inviteconfig));
}


$groupinfo = $db->fetch_first("SELECT groupid, allownickname, allowcstatus, allowcusbbcode, allowsigbbcode, allowsigimgcode, maxsigsize FROM {$tablepre}usergroups WHERE ".($regverify ? "groupid='8'" : "creditshigher<=".intval($initcredits)." AND ".intval($initcredits)."<creditslower LIMIT 1"));

$seccodecheck = $seccodestatus & 1;
$secqaacheck = $secqaa['status'][1];

$fromuid = !empty($_DCOOKIE['promotion']) && $creditspolicy['promotion_register'] ? intval($_DCOOKIE['promotion']) : 0;

$action = isset($action) ? $action : '';
$username = isset($username) ? $username : '';

$bbrulehash = $bbrules ? substr(md5(FORMHASH), 0, 8) : '';

if(!submitcheck('regsubmit', 0, $seccodecheck, $secqaacheck)) {

	if($action == 'activation') {
		$auth = explode("\t", authcode($auth, 'DECODE'));
		if(FORMHASH != $auth[1]) {
			showmessage('register_activation_invalid', 'logging.php?action=login');
		}
		$username = $auth[0];
		$activationauth = authcode("$auth[0]\t".FORMHASH, 'ENCODE');
	}

	$referer = isset($referer) ? dhtmlspecialchars($referer) : dreferer();

	$fromuser = !empty($fromuser) ? dhtmlspecialchars($fromuser) : '';
	if($fromuid) {
		$query = $db->query("SELECT username FROM {$tablepre}members WHERE uid='$fromuid'");
		if($db->num_rows($query)) {
			$fromuser = dhtmlspecialchars($db->result($query, 0));
		} else {
			dsetcookie('promotion');
		}
	}

	$bbrulestxt = nl2br("\n$bbrulestxt\n\n");
	if($action == 'activation') {
		$auth = dhtmlspecialchars($auth);
	}

	if($seccodecheck) {
		$seccode = random(6, 1);
	}
	if($secqaa['status'][1]) {
		$seccode = random(1, 1) * 1000000 + substr($seccode, -6);
	}

	$username = dhtmlspecialchars($username);

	include template('register');

} else {

	if($bbrules && $bbrulehash != $_POST['agreebbrule']) {
		showmessage('register_rules_agree');
	}

	$activation = array();
	if(isset($activationauth)) {
		$activationauth = explode("\t", authcode($activationauth, 'DECODE'));
		if($activationauth[1] == FORMHASH && !($activation = daddslashes(uc_get_user($activationauth[0]), 1))) {
			showmessage('register_activation_invalid', 'logging.php?action=login');
		}
	}

	if(!$activation) {
		$username = addslashes(trim(stripslashes($username)));
		if(uc_get_user($username) && !$db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$username'")) {
			if($inajax) {
				showmessage('profile_username_duplicate');
			} else {
				showmessage('register_activation_message', 'logging.php?action=login');
			}
		}

		if($password !== $password2) {
			showmessage('profile_passwd_notmatch');
		}

		if(!$password || $password != addslashes($password)) {
			showmessage('profile_passwd_illegal');
		}

		$email = trim($email);

	}

	$censorexp = '/^('.str_replace(array('\\*', "\r\n", ' '), array('.*', '|', ''), preg_quote(($censoruser = trim($censoruser)), '/')).')$/i';

	if($censoruser && @preg_match($censorexp, $username)) {
		showmessage('profile_username_protect');
	}

	$fieldadd1 = $fieldadd2 = '';
	foreach($_DCACHE['fields_required'] as $field) {
		$field_key = 'field_'.$field['fieldid'];
		$field_val = ${'field_'.$field['fieldid'].'new'};
		if($field['required'] && trim($field_val) == '') {
			showmessage('profile_required_info_invalid');
		} elseif($field['selective'] && $field_val != '' && !isset($field['choices'][$field_val])) {
			showmessage('undefined_action', NULL, 'HALTED');
		} else {
			$fieldadd1 .= ", $field_key";
			$fieldadd2 .= ', \''.dhtmlspecialchars($field_val).'\'';
		}
	}

	if($regverify == 2 && !trim($regmessage)) {
		showmessage('profile_required_info_invalid');
	}

	if($_DCACHE['ipctrl']['ipregctrl']) {
		foreach(explode("\n", $_DCACHE['ipctrl']['ipregctrl']) as $ctrlip) {
			if(preg_match("/^(".preg_quote(($ctrlip = trim($ctrlip)), '/').")/", $onlineip)) {
				$ctrlip = $ctrlip.'%';
				$regctrl = 72;
				break;
			} else {
				$ctrlip = $onlineip;
			}
		}
	} else {
		$ctrlip = $onlineip;
	}

	if($_DCACHE['ipctrl']['ipverifywhite']) {
		foreach(explode("\n", $_DCACHE['ipctrl']['ipverifywhite']) as $ctrlip) {
			if(preg_match("/^(".preg_quote(($ctrlip = trim($ctrlip)), '/').")/", $onlineip)) {
				$regverify = 0;
				break;
			}
		}
	}

	if($regstatus > 1) {
		if($regstatus == 2 && !$invitecode) {
			showmessage('register_invite_notfound');
		} elseif($invitecode) {
			$groupinfo['groupid'] = $invitegroupid ? intval($invitegroupid) : $groupinfo['groupid'];
			$invite = $db->fetch_first("SELECT uid, invitecode, inviteip, expiration FROM {$tablepre}invites WHERE invitecode='$invitecode' AND status IN ('1', '3')");
			if(!$invite) {
				showmessage('register_invite_error');
			} else {
				if($invite['inviteip'] == $onlineip) {
					showmessage('register_invite_iperror');
				} elseif($invite['expiration'] < $timestamp) {
					showmessage('register_invite_expiration');
				}
			}
		}
	}

	if($regctrl) {
		$query = $db->query("SELECT ip FROM {$tablepre}regips WHERE ip LIKE '$ctrlip' AND count='-1' AND dateline>$timestamp-'$regctrl'*3600 LIMIT 1");
		if($db->num_rows($query)) {
			showmessage('register_ctrl', NULL, 'HALTED');
		}
	}

	$secques = $questionid > 0 ? random(8) : '';

	if(!$activation) {
		$uid = uc_user_register($username, $password, $email, $questionid, $answer, $onlineip);

		if($uid <= 0) {
			if($uid == -1) {
				showmessage('profile_username_illegal');
			} elseif($uid == -2) {
				showmessage('profile_username_protect');
			} elseif($uid == -3) {
				showmessage('profile_username_duplicate');
			} elseif($uid == -4) {
				showmessage('profile_email_illegal');
			} elseif($uid == -5) {
				showmessage('profile_email_domain_illegal');
			} elseif($uid == -6) {
				showmessage('profile_email_duplicate');
			} else {
				showmessage('undefined_action', NULL, 'HALTED');
			}
		}
	} else {
		list($uid, $username, $email) = $activation;
	}

	if($db->result_first("SELECT uid FROM {$tablepre}members WHERE uid='$uid'")) {
		if(!$activation) {
			uc_user_delete($uid);
		}
		showmessage('profile_uid_duplicate');
	}

	if($regfloodctrl) {
		if($regattempts = $db->result_first("SELECT count FROM {$tablepre}regips WHERE ip='$onlineip' AND count>'0' AND dateline>'$timestamp'-86400")) {
			if($regattempts >= $regfloodctrl) {
				showmessage('register_flood_ctrl', NULL, 'HALTED');
			} else {
				$db->query("UPDATE {$tablepre}regips SET count=count+1 WHERE ip='$onlineip' AND count>'0'");
			}
		} else {
			$db->query("INSERT INTO {$tablepre}regips (ip, count, dateline)
				VALUES ('$onlineip', '1', '$timestamp')");
		}
	}

	$idstring = random(6);
	$authstr = $regverify == 1 ? "$timestamp\t2\t$idstring" : '';

	$password = md5(random(10));

	$db->query("INSERT INTO {$tablepre}members (uid, username, password, secques, adminid, groupid, regip, regdate, lastvisit, lastactivity, posts, credits, extcredits1, extcredits2, extcredits3, extcredits4, extcredits5, extcredits6, extcredits7, extcredits8, email, showemail, timeoffset, pmsound, invisible, newsletter)
		VALUES ('$uid', '$username', '$password', '$secques', '0', '$groupinfo[groupid]', '$onlineip', '$timestamp', '$timestamp', '$timestamp', '0', $initcredits, '$email', '0', '9999', '1', '0', '1')");

	$db->query("REPLACE INTO {$tablepre}memberfields (uid, authstr $fieldadd1) VALUES ('$uid', '$authstr' $fieldadd2)");

	if($regctrl || $regfloodctrl) {
		$db->query("DELETE FROM {$tablepre}regips WHERE dateline<='$timestamp'-".($regctrl > 72 ? $regctrl : 72)."*3600", 'UNBUFFERED');
		if($regctrl) {
			$db->query("INSERT INTO {$tablepre}regips (ip, count, dateline)
				VALUES ('$onlineip', '-1', '$timestamp')");
		}
	}

	$regmessage = dhtmlspecialchars($regmessage);
	if($regverify == 2) {
		$db->query("REPLACE INTO {$tablepre}validating (uid, submitdate, moddate, admin, submittimes, status, message, remark)
			VALUES ('$uid', '$timestamp', '0', '', '1', '0', '$regmessage', '')");
	}

	if($invitecode && $regstatus > 1) {
		$db->query("UPDATE {$tablepre}invites SET reguid='$uid', regdateline='$timestamp', status='2' WHERE invitecode='$invitecode'");
		if($inviteaddbuddy) {
			include_once DISCUZ_ROOT.'./uc_client/client.php';
			uc_friend_add($invite['uid'], $uid, '');
			uc_friend_add($uid, $invite['uid'], '');
			if($my_status) {
				manyoulog('friend', $invite['uid'], 'add', $uid);
			}
		}

		if($inviterewardcredit) {
			if($inviteaddcredit) {
				$db->query("UPDATE {$tablepre}members SET extcredits$inviterewardcredit=extcredits$inviterewardcredit+'$inviteaddcredit' WHERE uid='$uid'");
			}
			if($invitedaddcredit) {
				$db->query("UPDATE {$tablepre}members SET extcredits$inviterewardcredit=extcredits$inviterewardcredit+'$invitedaddcredit' WHERE uid='$invite[uid]'");
			}
		}
	}

	$discuz_uid = $uid;
	$discuz_user = $username;
	$discuz_userss = stripslashes($discuz_user);
	$discuz_pw = $password;
	$discuz_secques = $secques;
	$groupid = $groupinfo['groupid'];
	$styleid = $styleid ? $styleid : $_DCACHE['settings']['styleid'];

	if($welcomemsg && !empty($welcomemsgtxt)) {
		$welcomtitle = !empty($welcomemsgtitle) ? $welcomemsgtitle : "Welcome to $bbname!";
		$welcomtitle = addslashes(replacesitevar($welcomtitle));
		$welcomemsgtxt = addslashes(replacesitevar($welcomemsgtxt));
		if($welcomemsg == 1) {
			sendpm($uid, $welcomtitle, $welcomemsgtxt, 0);
		} elseif($welcomemsg == 2) {
			sendmail("$username <$email>", $welcomtitle, $welcomemsgtxt);
		}
	}

	if($fromuid) {
		updatecredits($fromuid, $creditspolicy['promotion_register']);
		dsetcookie('promotion', '');
	}

	if($taskon && $newbietasks) {
		$newbietaskids = array_keys($newbietasks);
		if($task = $db->fetch_first("SELECT * FROM {$tablepre}tasks WHERE taskid='$newbietaskids[0]' AND available='2'")) {
			require_once DISCUZ_ROOT.'./include/task.func.php';
			$task['newbie'] = 1;
			task_apply($task);
			$db->query("UPDATE {$tablepre}members SET prompt=prompt|8, newbietaskid='$newbietaskids[0]' WHERE uid='$discuz_uid'", 'UNBUFFERED');
		}
	}

	require_once DISCUZ_ROOT.'./include/cache.func.php';
	$_DCACHE['settings']['totalmembers']++;
	updatesettings();

	dsetcookie('loginuser', '');
	dsetcookie('activationauth', '', -86400 * 365);

	manyoulog('user', $discuz_uid, 'add');

	if(!empty($inajax)) {
		$msgforward = unserialize($msgforward);
		$mrefreshtime = intval($msgforward['refreshtime']) * 1000;
		$message = 1;
		if($regverify != 1) {
			include template('register');
		}
	}

	switch($regverify) {
		case 1:
			sendmail("$username <$email>", 'email_verify_subject', 'email_verify_message');
			if(!empty($inajax)) {
				include template('register');
			} else {
				showmessage('profile_email_verify');
			}
			break;
		case 2:
			showmessage('register_manual_verify', 'memcp.php');
			break;
		default:
			if($_DCACHE['settings']['frameon'] && $_DCOOKIE['frameon'] == 'yes') {
				$extrahead .= '<script>if(top != self) {parent.leftmenu.location.reload();}</script>';
			}
			showmessage('register_succeed', dreferer());
			break;
	}

}

function replacesitevar($string, $replaces = array()) {
	global $sitename, $bbname, $timestamp, $timeoffset, $adminemail, $adminemail, $discuz_user;
	$sitevars = array(
		'{sitename}' => $sitename,
		'{bbname}' => $bbname,
		'{time}' => gmdate('Y-n-j H:i', $timestamp + $timeoffset * 3600),
		'{adminemail}' => $adminemail,
		'{username}' => $discuz_user,
		'{myname}' => $discuz_user
	);
	$replaces = array_merge($sitevars, $replaces);
	return str_replace(array_keys($replaces), array_values($replaces), $string);
}

?>