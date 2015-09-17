<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: register.inc.php 21057 2009-11-10 01:05:36Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($discuz_uid) {
	wapmsg('login_succeed');
}

if(!$wapregister) {
	wapmsg('register_disable');
}

$groupinfo = $db->fetch_first("SELECT groupid FROM {$tablepre}usergroups WHERE ".($regverify ? "groupid='8'" : "creditshigher<=".intval($initcredits)." AND ".intval($initcredits)."<creditslower LIMIT 1"));

if(empty($username)) {

	echo "<p>$lang[register_username]:<input type=\"text\" name=\"username\" value=\"\" maxlength=\"15\" /><br />\n".
		"$lang[password]: <input type=\"password\" name=\"password\" value=\"\" /><br />\n".
		"$lang[email]: <input type=\"text\" name=\"email\" value=\"\" /><br />\n".
		($regverify == 2 ? "$lang[register_reason]: <input type=\"text\" name=\"regmessage\" value=\"\" />\n" : '').
		"<anchor title=\"$lang[submit]\">$lang[submit]".
		"<go method=\"post\" href=\"index.php?action=register&amp;sid=$sid\">\n".
		"<postfield name=\"username\" value=\"$(username)\" />\n".
		"<postfield name=\"password\" value=\"$(password)\" />\n".
		"<postfield name=\"email\" value=\"$(email)\" />\n".
		"</go></anchor></p>\n";

} else {

	@include_once DISCUZ_ROOT.'./forumdata/cache/cache_register.php';
	require_once DISCUZ_ROOT.'./uc_client/client.php';
	$email = trim(wapconvert($email));
	$username = trim(wapconvert($username));
	$regmessage = dhtmlspecialchars(wapconvert($regmessage));


	if(uc_get_user($username) && !$db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$username'")) {
		wapmsg('register_activation_message');
	}

	if($regstatus == 2) {
		wapmsg('register_invite');
	}

	if($_DCACHE['ipctrl']['ipregctrl']) {
		foreach(explode("\n", $_DCACHE['ipctrl']['ipregctrl']) as $ctrlip) {
			if(preg_match("/^(".preg_quote(($ctrlip = trim($ctrlip)), '/').")/", $onlineip)) {
				$ctrlip = $ctrlip.'%';
				$regctrl = 72;
				break;
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

	if($regctrl) {
		$query = $db->query("SELECT ip FROM {$tablepre}regips WHERE ip LIKE '$ctrlip' AND count='-1' AND dateline>$timestamp-'$regctrl'*3600 LIMIT 1");
		if($db->num_rows($query)) {
			wapmsg('register_ctrl', NULL, 'HALTED');
		}
	}

	if($regfloodctrl) {
		if($regattempts = $db->result_first("SELECT count FROM {$tablepre}regips WHERE ip='$onlineip' AND count>'0' AND dateline>'$timestamp'-86400")) {
			if($regattempts >= $regfloodctrl) {
				wapmsg('register_flood_ctrl');
			} else {
				$db->query("UPDATE {$tablepre}regips SET count=count+1 WHERE ip='$onlineip' AND count>'0'");
			}
		} else {
			$db->query("INSERT INTO {$tablepre}regips (ip, count, dateline)
				VALUES ('$onlineip', '1', '$timestamp')");
		}
	}

	$uid = uc_user_register($username, $password, $email);
	if($uid <= 0) {
		if($uid == -1) {
			wapmsg('profile_username_illegal');
		} elseif($uid == -2) {
			wapmsg('profile_username_protect');
		} elseif($uid == -3) {
			wapmsg('profile_username_duplicate');
		} elseif($uid == -4) {
			wapmsg('profile_email_illegal');
		} elseif($uid == -5) {
			wapmsg('profile_email_domain_illegal');
		} elseif($uid == -6) {
			wapmsg('profile_email_duplicate');
		} else {
			wapmsg('undefined_action');
		}
	}

	$password = md5(random(10));

	$idstring = random(6);
	$authstr = $regverify == 1 ? "$timestamp\t2\t$idstring" : '';

	$db->query("REPLACE INTO {$tablepre}members (uid, username, password, secques, gender, adminid, groupid, regip, regdate, lastvisit, lastactivity, posts, credits, extcredits1, extcredits2, extcredits3, extcredits4, extcredits5, extcredits6, extcredits7, extcredits8, email, bday, sigstatus, tpp, ppp)
		VALUES ('$uid', '$username', '$password', '', '', '0', '$groupinfo[groupid]', '$onlineip', '$timestamp', '$timestamp', '$timestamp', '0', $initcredits, '$email', '', '', '20', '20')");

	$db->query("REPLACE INTO {$tablepre}memberfields (uid, authstr) VALUES ('$uid', '$authstr')");

	if($regverify == 2) {
		$db->query("REPLACE INTO {$tablepre}validating (uid, submitdate, moddate, admin, submittimes, status, message, remark)
			VALUES ('$uid', '$timestamp', '0', '', '1', '0', '$regmessage', '')");
	}

	$discuz_uid = $uid;
	$discuz_user = $username;
	$discuz_userss = stripslashes($discuz_user);
	$discuz_pw = $password;
	$groupid = $groupinfo['groupid'];
	$styleid = $styleid ? $styleid : $_DCACHE['settings']['styleid'];

	switch($regverify) {
		case 1:
			sendmail("$discuz_userss <$email>", 'email_verify_subject', 'email_verify_message');
			wapmsg('profile_email_verify');
			break;
		case 2:

			wapmsg('register_manual_verify');
			break;
		default:
			wapmsg('register_succeed');
			break;
	}
}

?>
