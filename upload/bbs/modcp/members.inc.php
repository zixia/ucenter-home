<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: members.inc.php 20872 2009-10-28 03:12:08Z liulanbo $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

if($op == 'edit') {

	$member = loadmember($uid, $username, $error);
	$usernameenc = rawurlencode($member['username']);

	if($member && submitcheck('editsubmit') && !$error) {

		$sql = 'uid=uid';
		if($allowedituser) {
			
			if(!empty($clearavatar)) {
				require_once DISCUZ_ROOT.'./uc_client/client.php';
				uc_user_deleteavatar($member['uid']);
			}

			require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

			if($bionew) {
				$bionew = censor($bionew);
				$biohtmlnew = addslashes(discuzcode(stripslashes($bionew), 1, 0, 0, 0, $member['allowbiobbcode'], $member['allowbioimgcode'], 0, 0, 1));
			} else {
				$biohtmlnew = '';
			}

			if($biotradenew) {
				$biotradenew = censor($biotradenew);
				$biohtmlnew .= "\t\t\t".addslashes(discuzcode(stripslashes($biotradenew), 1, 0, 0, 0, 1, 1, 0, 0, 1));
			}

			if($signaturenew) {
				$signaturenew = censor($signaturenew);
				$sightmlnew = addslashes(discuzcode(stripslashes($signaturenew), 1, 0, 0, 0, $member['allowsigbbcode'], $member['allowsigimgcode'], 0, 0, 1));
			} else {
				$sightmlnew = '';
			}

			$locationnew && $locationnew = dhtmlspecialchars($locationnew);

			$sql .= ', sigstatus=\''.($signaturenew ? 1 : 0).'\'';
			$db->query("UPDATE {$tablepre}memberfields SET location='$locationnew', bio='$biohtmlnew', sightml='$sightmlnew' WHERE uid='$member[uid]'");
		}

		$db->query("UPDATE {$tablepre}members SET $sql WHERE uid='$member[uid]'");
		acpmsg('members_edit_succeed', "$cpscript?action=$action&op=$op");

	} elseif($member) {

		require_once DISCUZ_ROOT.'./include/editor.func.php';
		$bio = explode("\t\t\t", $member['bio']);
		$member['bio'] = html2bbcode($bio[0]);
		$member['biotrade'] = html2bbcode($bio[1]);
		$member['signature'] = html2bbcode($member['sightml']);
		$username = !empty($username) ? $member['username'] : '';

	}

} elseif($op == 'ban' && $allowbanuser) {

	$member = loadmember($uid, $username, $error);
	$usernameenc = rawurlencode($member['username']);
	
	if($member && submitcheck('bansubmit') && !$error) {
		$sql = 'uid=uid';
		$reason = trim($reason);
		if(!$reason && ($reasonpm == 1 || $reasonpm == 3)) {
			acpmsg('admin_reason_invalid');
		}

		if($bannew == 4 || $bannew == 5) {
			$groupidnew = $bannew;
			$banexpirynew = !empty($banexpirynew) ? $timestamp + $banexpirynew * 86400 : 0;
			$banexpirynew = $banexpirynew > $timestamp ? $banexpirynew : 0;
			if($banexpirynew) {
				$member['groupterms'] = $member['groupterms'] && is_array($member['groupterms']) ? $member['groupterms'] : array();
				$member['groupterms']['main'] = array('time' => $banexpirynew, 'adminid' => $member['adminid'], 'groupid' => $member['groupid']);
				$member['groupterms']['ext'][$groupidnew] = $banexpirynew;
				$sql .= ', groupexpiry=\''.groupexpiry($member['groupterms']).'\'';
			} else {
				$sql .= ', groupexpiry=0';
			}
			$adminidnew = -1;
		} elseif($member['groupid'] == 4 || $member['groupid'] == 5) {
			if(!empty($member['groupterms']['main']['groupid'])) {
				$groupidnew = $member['groupterms']['main']['groupid'];
				$adminidnew = $member['groupterms']['main']['adminid'];
				unset($member['groupterms']['main']);
				unset($member['groupterms']['ext'][$member['groupid']]);
				$sql .= ', groupexpiry=\''.groupexpiry($member['groupterms']).'\'';
			} else {
				$query = $db->query("SELECT groupid FROM {$tablepre}usergroups WHERE type='member' AND creditshigher<='$member[credits]' AND creditslower>'$member[credits]'");
				$groupidnew = $db->result($query, 0);
				$adminidnew = 0;
			}
		} else {
			$groupidnew = $member['groupid'];
			$adminidnew = $member['adminid'];
		}

		$sql .= ", adminid='$adminidnew', groupid='$groupidnew'";
		$db->query("UPDATE {$tablepre}members SET $sql WHERE uid='$member[uid]'");

		if($db->affected_rows($query)) {
			savebanlog($member['username'], $member['groupid'], $groupidnew, $banexpirynew, $reason);
		}

		$db->query("UPDATE {$tablepre}memberfields SET groupterms='".($member['groupterms'] ? addslashes(serialize($member['groupterms'])) : '')."' WHERE uid='$member[uid]'");

		acpmsg('modcp_member_ban_succeed', "$cpscript?action=$action&op=$op");

	}

} elseif($op == "ipban" && $allowbanip) {

	require_once DISCUZ_ROOT.'./include/misc.func.php';

	$iptoban = isset($ip) ? dhtmlspecialchars(explode('.', $ip)) : array('','','','');
	$updatecheck = $addcheck = $deletecheck = $adderror = 0;

	if(submitcheck('ipbansubmit')) {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM {$tablepre}banned WHERE id IN ($ids) AND ('$adminid'='1' OR admin='$discuz_user')");
			$deletecheck = $db->affected_rows();
		}

		if($ip1new != '' && $ip2new != '' && $ip3new != '' && $ip4new != '') {
			$addcheck = ipbanadd($ip1new, $ip2new, $ip3new, $ip4new, $validitynew, $adderror);
			if(!$addcheck) {
				$iptoban = array($ip1new, $ip2new, $ip3new, $ip4new);
			}
		}

		if(!empty($expirationnew) && is_array($expirationnew)) {
			foreach($expirationnew as $id => $expiration) {
				$db->query("UPDATE {$tablepre}banned SET expiration='".strtotime($expiration)."' WHERE id='$id' AND ('$adminid'='1' OR admin='$discuz_user')");
				empty($updatecheck) && $updatecheck = $db->affected_rows();
			}
		}

		if($deletecheck || $addcheck || $updatecheck) {
			require_once(DISCUZ_ROOT.'./include/cache.func.php');
			updatecache('ipbanned');
		}

	}

	$iplist = array();
	$query = $db->query("SELECT * FROM {$tablepre}banned ORDER BY dateline");
	while($banned = $db->fetch_array($query)) {
		for($i = 1; $i <= 4; $i++) {
			if($banned["ip$i"] == -1) {
				$banned["ip$i"] = '*';
			}
		}
		$banned['disabled'] = $adminid != 1 && $banned['admin'] != $discuz_userss ? 'disabled' : '';
		$banned['dateline'] = gmdate($dateformat, $banned['dateline'] + $timeoffset * 3600);
		$banned['expiration'] = gmdate($dateformat, $banned['expiration'] + $timeoffset * 3600);
		$banned['theip'] = "$banned[ip1].$banned[ip2].$banned[ip3].$banned[ip4]";
		$banned['location'] = convertip($banned['theip']);
		$iplist[$banned['id']] = $banned;
	}

} else {
	showmessage('undefined_action');
}

function loadmember(&$uid, &$username, &$error) {
	global $db, $tablepre, $timeoffset;

	$uid = !empty($uid) && is_numeric($uid) && $uid > 0 ? $uid : '';
	$username = isset($username) && $username != '' ? dhtmlspecialchars(trim($username)) : '';

	$member = array();

	if($uid || $username != '') {

		$query = $db->query("SELECT m.uid, m.username, m.groupid, m.adminid, mf.groupterms, mf.location, mf.bio, mf.sightml, u.type AS grouptype, u.allowsigbbcode, u.allowsigimgcode, u.allowcusbbcode, u.allowbiobbcode, u.allowbioimgcode, u.allowcusbbcode FROM {$tablepre}members m
			LEFT JOIN {$tablepre}memberfields mf ON mf.uid=m.uid
			LEFT JOIN {$tablepre}usergroups u ON u.groupid=m.groupid
			WHERE ".($uid ? "m.uid='$uid'" : "m.username='$username'"));

		if(!$member = $db->fetch_array($query)) {
			$error = 2;
		} elseif(($member['grouptype'] == 'system' && in_array($member['groupid'], array(1, 2, 3, 6, 7, 8))) || in_array($member['adminid'], array(1,2,3))) {
			$error = 3;
		} else {
			$member['groupterms'] = unserialize($member['groupterms']);
			$member['banexpiry'] = !empty($member['groupterms']['main']['time']) && ($member['groupid'] == 4 || $member['groupid'] == 5) ? gmdate('Y-n-j', $member['groupterms']['main']['time'] + $timeoffset * 3600) : '';
			$error = 0;
		}

	} else {
		$error = 1;
	}

	return $member;
}

function ipbanadd($ip1new, $ip2new, $ip3new, $ip4new, $validitynew, &$error) {

	global $db, $tablepre, $timestamp, $adminid, $onlineip, $discuz_user;

	if($ip1new != '' && $ip2new != '' && $ip3new != '' && $ip4new != '') {
		$own = 0;
		$ip = explode('.', $onlineip);
		for($i = 1; $i <= 4; $i++) {

			if(!is_numeric(${'ip'.$i.'new'}) || ${'ip'.$i.'new'} < 0) {
				if($adminid != 1) {
					$error = 1;
					return FALSE;
				}
				${'ip'.$i.'new'} = -1;
				$own++;
			} elseif(${'ip'.$i.'new'} == $ip[$i - 1]) {
				$own++;
			}
			${'ip'.$i.'new'} = intval(${'ip'.$i.'new'}) > 255 ? 255 : intval(${'ip'.$i.'new'});
		}

		if($own == 4) {
			$error = 2;
			return FALSE;
		}

		$query = $db->query("SELECT * FROM {$tablepre}banned WHERE (ip1='$ip1new' OR ip1='-1') AND (ip2='$ip2new' OR ip2='-1') AND (ip3='$ip3new' OR ip3='-1') AND (ip4='$ip4new' OR ip4='-1')");
		if($banned = $db->fetch_array($query)) {
			$error = 3;
			return FALSE;
		}

		$expiration = $validitynew > 1 ? ($timestamp + $validitynew * 86400) : $timestamp + 86400;

		$db->query("UPDATE {$tablepre}sessions SET groupid='6' WHERE ('$ip1new'='-1' OR ip1='$ip1new') AND ('$ip2new'='-1' OR ip2='$ip2new') AND ('$ip3new'='-1' OR ip3='$ip3new') AND ('$ip4new'='-1' OR ip4='$ip4new')");
		$db->query("INSERT INTO {$tablepre}banned (ip1, ip2, ip3, ip4, admin, dateline, expiration)
				VALUES ('$ip1new', '$ip2new', '$ip3new', '$ip4new', '$discuz_user', '$timestamp', '$expiration')");

		return TRUE;

	}

	return FALSE;

}

?>