<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: invite.php 16708 2008-11-17 01:34:55Z tiger $
*/

define('CURSCRIPT', 'invite');

require_once './include/common.inc.php';

if($regstatus <= 1) {
	showmessage('invite_close');
} elseif(!$discuz_uid) {
	showmessage('group_nopermission', NULL, 'NOPERM');
}

$action = !empty($action) ? $action : 'index';
$tpp = 10;
$page = max(1, intval($page));
$start_limit = ($page - 1) * $tpp;

if($action == 'index') {
	$myinvitenum = intval($db->result_first("SELECT COUNT(*) FROM {$tablepre}invites WHERE uid='$discuz_uid' AND dateline>'$timestamp'-86400 AND dateline<'$timestamp'"));
		
	if(!submitcheck('buysubmit')) {

		$invitenum = intval($db->result_first("SELECT COUNT(*) FROM {$tablepre}invites WHERE uid='$discuz_uid' AND status IN ('1', '3')"));
		$multipage = multi($invitenum, $tpp, $page, "invite.php");

		$invitelist = $inviteuserlist = array();
		$query = $db->query("SELECT dateline, expiration, invitecode, status
				FROM {$tablepre}invites
				WHERE uid='$discuz_uid' AND status IN ('1', '3') ORDER BY dateline DESC LIMIT $start_limit,$tpp");
		while($invite = $db->fetch_array($query)) {
			$invite['expiration'] = round(($invite['expiration'] - $timestamp) / 86400);
			$invitelist[] = $invite;
		}

		$query = $db->query("SELECT i.dateline, i.expiration, i.invitecode, m.username, m.uid
				FROM {$tablepre}invites i, {$tablepre}members m
				WHERE i.uid='$discuz_uid' AND i.status='2' AND i.reguid=m.uid ORDER BY i.regdateline DESC LIMIT 0, 10");
		while($inviteuser = $db->fetch_array($query)) {
			$inviteuser['avatar'] = discuz_uc_avatar($inviteuser['uid'], 'small');
			$inviteuserlist[] = $inviteuser;
		}

		include template('invite_index');

	} else {

		if(!$allowinvite) {
			showmessage('group_nopermission', NULL, 'NOPERM');
		}

		if($maxinvitenum && $myinvitenum == $maxinvitenum) {
			showmessage('invite_num_range_invalid');
		}

		$amount = intval($amount);
		$buyinvitecredit = $amount ? $amount * $inviteprice : 0;

		if(!$amount || $amount < 0) {
			showmessage('invite_num_invalid');
		} elseif(${'extcredits'.$creditstransextra[4]} < $buyinvitecredit && $buyinvitecredit) {
			showmessage('invite_credits_no_enough');
		} elseif(($maxinvitenum && $myinvitenum + $amount > $maxinvitenum) || $amount > 50) {
			showmessage('invite_num_buy_range_invalid');
		} elseif($buyinvitecredit && !$creditstransextra[4]) {
			showmessage('credits_transaction_disabled');
		} else {
			for($i=1; $i<=$amount; $i++) {
				$invitecode = substr(md5($discuz_uid.$timestamp.random(6)), 0, 10).random(6);
				$expiration = $timestamp + $maxinviteday * 86400;
				$db->query("INSERT INTO {$tablepre}invites (uid, dateline, expiration, inviteip, invitecode) VALUES ('$discuz_uid', '$timestamp', '$expiration', '$onlineip', '$invitecode')", 'UNBUFFERED');
			}
			if($buyinvitecredit && $creditstransextra[4]) {
				$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[4]=extcredits$creditstransextra[4]-'$buyinvitecredit' WHERE uid='$discuz_uid'");
			}
			showmessage('invite_buy_succeed');
		}
	}


} elseif($action == 'sendinvite') {

	if(!$allowmailinvite) {
		showmessage('group_nopermission', 'invite.php');
	}

	if(!submitcheck('sendsubmit')) {

		$fromuid = $creditspolicy['promotion_register'] ? '&amp;fromuid='.$discuz_uid : '';
		$threadurl = "{$boardurl}$regname?invitecode=$invitecode$fromuid";

		$email = $db->result_first("SELECT email FROM {$tablepre}members WHERE uid='$discuz_uid'");

		include template('invite_send');
	} else {
		if(empty($fromname) || empty($fromemail) || empty($sendtoname) || empty($sendtoemail)) {
			showmessage('email_friend_invalid');
		}

		if(!$invitenum = $db->result_first("SELECT invitecode FROM {$tablepre}invites WHERE uid='$discuz_uid' AND status='1' AND invitecode='$invitecode'")) {
			showmessage('invite_invalid');
		} else {
			$db->query("UPDATE {$tablepre}invites SET status='3' WHERE uid='$discuz_uid' AND invitecode='$invitecode'");
			sendmail("$sendtoname <$sendtoemail>", 'email_to_invite_subject', 'email_to_invite_message', "$fromname <$fromemail>");
			showmessage('email_invite_succeed', 'invite.php');
		}
	}

} elseif($action == 'markinvite') {

	$changestatus = $do == 'undo' ? 1 : 3;

	if(!empty($invitecode)) {
		$db->query("UPDATE {$tablepre}invites SET status='$changestatus' WHERE uid='$discuz_uid' AND invitecode='$invitecode'");
	}

	$invite = $db->fetch_first("SELECT invitecode, dateline, expiration FROM {$tablepre}invites WHERE uid='$discuz_uid' AND invitecode='$invitecode'");
	$invite['expiration'] = round(($invite['expiration'] - $timestamp) / 86400);

	include template('invite_index');

} else {

	showmessage('undefined_action');
}

?>