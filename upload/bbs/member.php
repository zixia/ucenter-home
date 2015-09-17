<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: member.php 19359 2009-08-26 09:50:42Z monkey $
*/

define('CURSCRIPT', 'member');
define('NOROBOT', TRUE);
require_once './include/common.inc.php';

if($action == 'clearcookies') {

	if(is_array($_COOKIE) && (empty($discuz_uid) || ($discuz_uid && $formhash == formhash()))) {
		foreach($_COOKIE as $key => $val) {
			dsetcookie($key, '', -86400 * 365, 0);
		}
	}
	showmessage('login_clearcookie', $indexname);

} elseif($action == 'online') {

	$discuz_action = 31;

	@include language('actions');

	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $memberperpage;

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}sessions");
	$multipage = multi($num, $memberperpage, $page, 'member.php?action=online');

	$onlinelist = array();
	$query = $db->query("SELECT s.*, f.name, t.subject FROM {$tablepre}sessions s
		LEFT JOIN {$tablepre}forums f ON s.fid=f.fid
		LEFT JOIN {$tablepre}threads t ON s.tid=t.tid
		WHERE s.invisible='0'
		ORDER BY s.lastactivity DESC LIMIT $start_limit, $memberperpage");

	while($online = $db->fetch_array($query)) {
		$online['lastactivity'] = gmdate($timeformat, $online['lastactivity'] + $timeoffset * 3600);
		$online['action'] = $actioncode[$online['action']];
		$online['subject'] = $online['subject'] ? cutstr($online['subject'], 35) : NULL;
		$online['ip'] = $online['ip1'].'.'.$online['ip2'].'.'.$online['ip3'].'.'.$online['ip4'];

		$onlinelist[] = $online;
	}

	include template('whosonline');

} elseif($action == 'list') {

	$discuz_action = 41;

	if(($adminid != 1) && !$memliststatus && $type != 'birthdays') {
		showmessage('member_list_disable', NULL, 'HALTED');
	} elseif($type == 'birthdays' && !$maxbdays) {
		showmessage('todays_birthdays_banned');
	}

	$type = isset($type) && in_array($type, array('admins','birthdays','grouplist')) ? $type : '';
	$order = isset($order) && in_array($order, array('credits','gender','username')) ? $order : '';
	$orderadd = $sql = $num = $birthdayadd = '';
	
	if(!empty($listgid) && ($listgid = intval($_GET['listgid']))) {
		$type = $adminid == 1 ? 'grouplist' : $type;
	} else {
		$listgid = '';
	}
	switch($type) {
		case 'admins':
			$sql = 'WHERE groupid IN (1, 2, 3)';
			$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members $sql");
			break;
		case 'birthdays':
			@include DISCUZ_ROOT.'./forumdata/cache/cache_birthdays.php';
			$num = $_DCACHE['birthdays']['num'];
			$sql = 'WHERE m.uid IN ('.($_DCACHE['birthdays']['uids'] ? $_DCACHE['birthdays']['uids'] : '0').')';
			$birthdayadd = ',m.bday ';
			break;
		case 'grouplist':
			$sql = "WHERE groupid='$listgid'";
			$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members $sql");
			break;
		default:
			$type = '';
			$order = empty($order) ? '' : $order;
			switch($order) {
				case 'credits': $orderadd = "ORDER BY credits DESC"; break;
				case 'gender': 	$orderadd = "ORDER BY gender DESC"; break;
				case 'username': $orderadd = "ORDER BY username DESC"; break;
				default: $orderadd = 'ORDER BY uid'; $order = 'uid'; break;
			}
			$sql = !empty($srchmem) ? " WHERE username LIKE '".str_replace(array('_', '%'), array('\_', '\%'), $srchmem)."%'" : '';
			$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members $sql");
	}

	$page = $membermaxpages && $page > $membermaxpages ? 1 : $page;
	$start_limit = ($page - 1) * $memberperpage;

	$multipage = multi($num, $memberperpage, $page, "member.php?action=list&listgid=$listgid&srchmem=".rawurlencode($srchmem)."&amp;order=$order&amp;type=$type", $membermaxpages);

	$memberlist = array();

	$query = $db->query("SELECT m.uid, m.username, m.gender, m.email, m.regdate, m.lastvisit, m.posts, m.credits,
		m.showemail$birthdayadd FROM {$tablepre}members m
		$sql $orderadd LIMIT $start_limit, $memberperpage");

	while($member = $db->fetch_array($query)) {
		$member['usernameenc'] = rawurlencode($member['username']);
		$member['regdate'] = gmdate($dateformat, $member['regdate'] + $timeoffset * 3600 );
		$member['lastvisit'] = dgmdate("$dateformat $timeformat", $member['lastvisit'] + ($timeoffset * 3600));
		$memberlist[] = $member;
	}

	include template('memberlist');

} elseif($action == 'markread') {

	if($discuz_user) {
		$db->query("UPDATE {$tablepre}members SET lastvisit='$timestamp' WHERE uid='$discuz_uid'");
	}

	showmessage('mark_read_succeed', $indexname);

} elseif($action == 'regverify' && $regverify == 2 && $groupid == 8 && submitcheck('verifysubmit')) {

	$query = $db->query("SELECT uid FROM {$tablepre}validating WHERE uid='$discuz_uid' AND status='1'");
	if($db->num_rows($query)) {
		$db->query("UPDATE {$tablepre}validating SET submittimes=submittimes+1, submitdate='$timestamp', status='0', message='".dhtmlspecialchars($regmessagenew)."'
			WHERE uid='$discuz_uid'");
		showmessage('submit_verify_succeed', 'memcp.php');
	} else {
		showmessage('undefined_action', NULL, 'HALTED');
	}

} elseif($action == 'emailverify') {

	$member = $db->fetch_first("SELECT mf.authstr FROM {$tablepre}members m, {$tablepre}memberfields mf
		WHERE m.uid='$discuz_uid' AND mf.uid=m.uid AND m.groupid='8'");

	if(!$member) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	if($regverify == 2) {
		showmessage('register_verify_invalid');
	}

	list($dateline, $type, $idstring) = explode("\t", $member['authstr']);
	if($type == 2 && $timestamp - $dateline < 86400) {
		showmessage('email_verify_invalid');
	}

	$idstring = $type == 2 && $idstring ? $idstring : random(6);
	$db->query("UPDATE {$tablepre}memberfields SET authstr='$timestamp\t2\t$idstring' WHERE uid='$discuz_uid'");

	sendmail("$discuz_userss <$email>", 'email_verify_subject', 'email_verify_message');
	showmessage('email_verify_succeed');

} elseif($action == 'activate' && $uid && $id) {

	$query = $db->query("SELECT m.uid, m.username, m.credits, mf.authstr FROM {$tablepre}members m, {$tablepre}memberfields mf
		WHERE m.uid='$uid' AND mf.uid=m.uid AND m.groupid='8'");

	$member = $db->fetch_array($query);

	list($dateline, $operation, $idstring) = explode("\t", $member['authstr']);

	if($operation == 2 && $idstring == $id) {
		$query = $db->query("SELECT groupid FROM {$tablepre}usergroups WHERE type='member' AND $member[credits]>=creditshigher AND $member[credits]<creditslower LIMIT 1");
		$db->query("UPDATE {$tablepre}members SET groupid='".$db->result($query, 0)."' WHERE uid='$member[uid]'");
		$db->query("UPDATE {$tablepre}memberfields SET authstr='' WHERE uid='$member[uid]'");
		showmessage('activate_succeed', $indexname);
	} else {
		showmessage('activate_illegal', NULL, 'HALTED');
	}

} elseif($action == 'lostpasswd') {

	$discuz_action = 141;

	if(submitcheck('lostpwsubmit')) {
		require_once DISCUZ_ROOT.'./uc_client/client.php';
		list($tmp['uid'], , $tmp['email']) = uc_get_user($username);
		if($email != $tmp['email']) {
			showmessage('getpasswd_account_notmatch');
		}
		$member = $db->fetch_first("SELECT uid, username, adminid, email FROM {$tablepre}members WHERE uid='$tmp[uid]'");
		if(!$member) {
			showmessage('getpasswd_account_notmatch');
		} elseif($member['adminid'] == 1 || $member['adminid'] == 2) {
			showmessage('getpasswd_account_invalid');
		}

		if($member['email'] != $tmp['email']) {
			$db->query("UPDATE {$tablepre}members SET email='".addslashes($tmp['email'])."' WHERE uid='".addslashes($tmp['uid'])."'");
		}

		$idstring = random(6);
		$db->query("UPDATE {$tablepre}memberfields SET authstr='$timestamp\t1\t$idstring' WHERE uid='$member[uid]'");

		sendmail("$username <$tmp[email]>", 'get_passwd_subject', 'get_passwd_message');
		showmessage('getpasswd_send_succeed', '', 141);
	}

} elseif($action == 'getpasswd' && $uid && $id) {

	$discuz_action = 141;

	$member = $db->fetch_first("SELECT m.username, m.email, mf.authstr FROM {$tablepre}members m, {$tablepre}memberfields mf
		WHERE m.uid='$uid' AND mf.uid=m.uid");

	list($dateline, $operation, $idstring) = explode("\t", $member['authstr']);

	if($dateline < $timestamp - 86400 * 3 || $operation != 1 || $idstring != $id) {
		showmessage('getpasswd_illegal', NULL, 'HALTED');
	}

	if(!submitcheck('getpwsubmit') || $newpasswd1 != $newpasswd2) {
		$hashid = $id;
		include template('getpasswd');
	} else {
		if($newpasswd1 != addslashes($newpasswd1)) {
			showmessage('profile_passwd_illegal');
		}

		require_once DISCUZ_ROOT.'./uc_client/client.php';
		uc_user_edit($member['username'], $newpasswd1, $newpasswd1, $member['email'], 1);
		$password = md5(random(10));

		$db->query("UPDATE {$tablepre}members SET password='$password' WHERE uid='$uid'");
		$db->query("UPDATE {$tablepre}memberfields SET authstr='' WHERE uid='$uid'");

		showmessage('getpasswd_succeed');
	}

} elseif($action == 'groupexpiry' && $discuz_uid) {

	if(!$groupexpiry) {
		showmessage('group_expiry_disabled');
	}

	$groupterms = unserialize($db->result_first("SELECT groupterms FROM {$tablepre}memberfields WHERE uid='$discuz_uid'"));

	$expgrouparray = $expirylist = $termsarray = array();

	if(!empty($groupterms['ext']) && is_array($groupterms['ext'])) {
		$termsarray = $groupterms['ext'];
	}
	if(!empty($groupterms['main']['time']) && (empty($termsarray[$groupid]) || $termsarray[$groupid] > $groupterm['main']['time'])) {
		$termsarray[$groupid] = $groupterms['main']['time'];
	}

	foreach($termsarray as $expgroupid => $expiry) {
		if($expiry <= $timestamp) {
			$expgrouparray[] = $expgroupid;
		}
	}

	if(!empty($groupterms['ext'])) {
		foreach($groupterms['ext'] as $extgroupid => $time) {
			$expirylist[$extgroupid] = array('time' => gmdate($dateformat, $time + $timeoffset * 3600), 'type' => 'ext');
		}
	}

	if(!empty($groupterms['main'])) {
		$expirylist[$groupid] = array('time' => gmdate($dateformat, $groupterms['main']['time'] + $timeoffset * 3600), 'type' => 'main');
	}

	if($expirylist) {
		$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups WHERE groupid IN (".implode(',', array_keys($expirylist)).")");
		while($group = $db->fetch_array($query)) {
			$expirylist[$group['groupid']]['grouptitle'] = in_array($group['groupid'], $expgrouparray) ? '<s>'.$group['grouptitle'].'</s>' : $group['grouptitle'];
		}
	} else {
		$db->query("UPDATE {$tablepre}members SET groupexpiry='0' WHERE uid='$discuz_uid'");
	}

	if($expgrouparray) {

		$extgroupidarray = array();
		foreach(explode("\t", $extgroupids) as $extgroupid) {
			if(($extgroupid = intval($extgroupid)) && !in_array($extgroupid, $expgrouparray)) {
				$extgroupidarray[] = $extgroupid;
			}
		}

		$groupidnew = $groupid;
		$adminidnew = $adminid;
		foreach($expgrouparray as $expgroupid) {
			if($expgroupid == $groupid) {
				if(!empty($groupterms['main']['groupid'])) {
					$groupidnew = $groupterms['main']['groupid'];
					$adminidnew = $groupterms['main']['adminid'];
				} else {
					$groupidnew = $db->result_first("SELECT groupid FROM {$tablepre}usergroups WHERE type='member' AND '$credits'>=creditshigher AND '$credits'<creditslower LIMIT 1");
					if(in_array($adminid, array(1, 2, 3))) {
						$query = $db->query("SELECT groupid FROM {$tablepre}usergroups WHERE groupid IN ('".implode('\',\'', $extgroupidarray)."') AND radminid='$adminid' LIMIT 1");
						$adminidnew = ($db->num_rows($query)) ? $adminid : 0;
					} else {
						$adminidnew = 0;
					}
				}
				unset($groupterms['main']);
			}
			unset($groupterms['ext'][$expgroupid]);
		}

		$groupexpirynew = groupexpiry($groupterms);
		$extgroupidsnew = implode("\t", $extgroupidarray);
		$grouptermsnew = addslashes(serialize($groupterms));

		$db->query("UPDATE {$tablepre}members SET adminid='$adminidnew', groupid='$groupidnew', extgroupids='$extgroupidsnew', groupexpiry='$groupexpirynew' WHERE uid='$discuz_uid'");
		$db->query("UPDATE {$tablepre}memberfields SET groupterms='$grouptermsnew' WHERE uid='$discuz_uid'");

	}

	include template('groupexpiry');

} elseif($action == 'switchstatus' && $discuz_uid) {

	if(!$allowinvisible) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	}

	$db->query("UPDATE {$tablepre}members SET invisible = !invisible WHERE uid='$discuz_uid'", 'UNBUFFERED');
	include language('misc');
	showmessage($invisible
		? '<a href="member.php?action=switchstatus" title="'.$language['login_switch_invisible_mode'].'" ajaxtarget="loginstatus">'.$language['login_normal_mode'].'</a>'
		: '<a href="member.php?action=switchstatus" title="'.$language['login_switch_normal_mode'].'" ajaxtarget="loginstatus">'.$language['login_invisible_mode'].'</a>',
		dreferer());

} else {

	showmessage('undefined_action', NULL, 'HALTED');

}

?>