<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: ajax.php 21155 2009-11-18 00:36:45Z monkey $
*/

define('CURSCRIPT', 'ajax');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';
if($action == 'updatesecqaa') {

	$message = '';
	if($secqaa) {
		require_once DISCUZ_ROOT.'./forumdata/cache/cache_secqaa.php';
		$secqaa = max(1, random(1, 1));
		$message = $_DCACHE['secqaa'][$secqaa]['question'];
		if($seclevel) {
			$seccode = $secqaa * 1000000 + substr($seccode, -6);
			updatesession();
		} else {
			dsetcookie('secq', authcode($secqaa."\t".$timestamp."\t".$discuz_uid, 'ENCODE'), 3600);
		}
	}
	showmessage($message);

} elseif($action == 'updateseccode') {

	$message = '';
	if($seccodestatus) {
		$secqaa = substr($seccode, 0, 1);
		$seccode = random(6, 1);
		$rand = random(5, 1);
		if($seclevel) {
			$seccode += $secqaa * 1000000;
			updatesession();
		} else {
			$key = $seccodedata['type'] != 3 ? '' : $_DCACHE['settings']['authkey'].date('Ymd');
			dsetcookie('secc', authcode($seccode."\t".$timestamp."\t".$discuz_uid, 'ENCODE', $key), 3600);
		}
		if($seccodedata['type'] == 2) {
			$message = '<div style="width:'.$seccodedata['width'].'px; height:'.$seccodedata['height'].'px;" id="seccodeswf_'.$secchecktype.'"></div>'.(extension_loaded('ming') ? "<script type=\"text/javascript\" reload=\"1\">\n$('seccodeswf_$secchecktype').innerHTML=AC_FL_RunContent(
				'width', '$seccodedata[width]', 'height', '$seccodedata[height]', 'src', 'seccode.php?update=$rand',
				'quality', 'high', 'wmode', 'transparent', 'bgcolor', '#ffffff',
				'align', 'middle', 'menu', 'false', 'allowScriptAccess', 'sameDomain');\n</script>" :
				"<script type=\"text/javascript\" reload=\"1\">\n$('seccodeswf_$secchecktype').innerHTML=AC_FL_RunContent(
				'width', '$seccodedata[width]', 'height', '$seccodedata[height]', 'src', '{$boardurl}images/seccode/flash/flash2.swf',
				'FlashVars', 'sFile={$boardurl}seccode.php?update=$rand', 'menu', 'false', 'allowScriptAccess', 'sameDomain', 'swLiveConnect', 'true');\n</script>");
		} elseif($seccodedata['type'] == 3) {
			$flashcode = "<span id=\"seccodeswf_$secchecktype\"></span><script type=\"text/javascript\" reload=\"1\">\n$('seccodeswf_$secchecktype').innerHTML=AC_FL_RunContent(
				'id', 'seccodeplayer', 'name', 'seccodeplayer', 'width', '0', 'height', '0', 'src', '{$boardurl}images/seccode/flash/flash1.swf',
				'FlashVars', 'sFile={$boardurl}seccode.php?update=$rand', 'menu', 'false', 'allowScriptAccess', 'sameDomain', 'swLiveConnect', 'true');\n</script>";
			$message = 'seccode_player';
		} else {
			$message = '<img onclick="updateseccode'.$secchecktype.'()" width="'.$seccodedata['width'].'" height="'.$seccodedata['height'].'" src="seccode.php?update='.$rand.'" class="absmiddle" alt="" />';
		}
	}
	showmessage($message);

} elseif($action == 'checkseccode') {

	if($seclevel) {
		$tmp = $seccode;
	} else {
		$key = $seccodedata['type'] != 3 ? '' : $_DCACHE['settings']['authkey'].date('Ymd');
		list($tmp, $expiration, $seccodeuid) = explode("\t", authcode($_DCOOKIE['secc'], 'DECODE', $key));
		if($seccodeuid != $discuz_uid || $timestamp - $expiration > 600) {
			showmessage('submit_seccode_invalid');
		}
	}
	seccodeconvert($tmp);
	strtoupper($seccodeverify) != $tmp && showmessage('submit_seccode_invalid');
	showmessage('succeed');

} elseif($action == 'checksecanswer') {

	if($seclevel) {
		$tmp = $seccode;
	} else {
		list($tmp, $expiration, $seccodeuid) = explode("\t", authcode($_DCOOKIE['secq'], 'DECODE'));
		if($seccodeuid != $discuz_uid || $timestamp - $expiration > 600) {
			showmessage('submit_secqaa_invalid');
		}
	}

	require_once DISCUZ_ROOT.'./forumdata/cache/cache_secqaa.php';
	!$headercharset && @dheader('Content-Type: text/html; charset='.$charset);

	if(md5($secanswer) != $_DCACHE['secqaa'][substr($tmp, 0, 1)]['answer']) {
		showmessage('submit_secqaa_invalid');
	}
	showmessage('succeed');

} elseif($action == 'checkusername') {

	$username = trim($username);

	require_once DISCUZ_ROOT.'./uc_client/client.php';

	$ucresult = uc_user_checkname($username);

	if($ucresult == -1) {
		showmessage('profile_username_illegal', '', 1);
	} elseif($ucresult == -2) {
		showmessage('profile_username_protect', '', 1);
	} elseif($ucresult == -3) {
		if($db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$username'")) {
			showmessage('register_check_found', '', 1);
		} else {
			showmessage('register_activation', '', 1);
		}
	}

} elseif($action == 'checkemail') {

	$email = trim($email);

	require_once DISCUZ_ROOT.'./uc_client/client.php';

	$ucresult = uc_user_checkemail($email);
	if($ucresult == -4) {
		showmessage('profile_email_illegal', '', 1);
	} elseif($ucresult == -5) {
		showmessage('profile_email_domain_illegal', '', 1);
	} elseif($ucresult == -6) {
		showmessage('profile_email_duplicate', '', 1);
	}

} elseif($action == 'checkuserexists') {

	$check = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='".trim($username)."'");
	$check ? showmessage('<img src="'.IMGDIR.'/check_right.gif" width="13" height="13">')
		: showmessage('username_nonexistence');

} elseif($action == 'checkinvitecode') {

	$invitecode = trim($invitecode);
	$check = $db->result_first("SELECT invitecode FROM {$tablepre}invites WHERE invitecode='".trim($invitecode)."' AND status IN ('1', '3')");
	if(!$check) {
		showmessage('invite_invalid', '', 1);
	} else {
		$query = $db->query("SELECT m.username FROM {$tablepre}invites i, {$tablepre}members m WHERE invitecode='".trim($invitecode)."' AND i.uid=m.uid");
		$inviteuser = $db->fetch_array($query);
		$inviteuser = $inviteuser['username'];
		showmessage('invite_send');
	}

} elseif($action == 'attachlist') {

	require_once DISCUZ_ROOT.'./include/post.func.php';
	$attachlist = getattach(intval($posttime));
	$attachlist = $attachlist['attachs']['unused'];

	include template('header_ajax');
	include template('ajax_attachlist');
	include template('footer_ajax');
	dexit();

} elseif($action == 'imagelist') {

	require_once DISCUZ_ROOT.'./include/post.func.php';
	$attachlist = getattach();
	$imagelist = $attachlist['imgattachs']['unused'];

	include template('header_ajax');
	include template('ajax_imagelist');
	include template('footer_ajax');
	dexit();

} elseif($action == 'displaysearch_adv') {
	$display = $display == 1 ? 1 : '';
	dsetcookie('displaysearch_adv', $display);
}

showmessage($reglinkname, '', 2);

?>