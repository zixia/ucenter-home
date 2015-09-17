<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: cp.php 20442 2009-09-28 01:17:13Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(submitcheck('settingsubmit', 1)) {
	$db->query("UPDATE {$tablepre}userapp SET allowfeed='$allowfeednew',allowprofilelink='$allowprofilelinknew' WHERE appid='$appid'");
	include template('header_ajax');
	echo $scriptlang['manyou']['myop_setting_update'];
	echo '<script type="text/javascript" reload="1">';
	echo 'setTimeout("$(\'appsetting_msg\').innerHTML = \'\'", 3000);';
	echo '</script>';
	include template('footer_ajax');
}

include_once DISCUZ_ROOT.'./forumdata/cache/cache_manyou.php';

$invitenum = $db->result_first("SELECT count(*) FROM {$tablepre}myinvite WHERE touid='$discuz_uid'");
$noticenum = $db->result_first("SELECT count(*) FROM {$tablepre}mynotice WHERE uid='$discuz_uid' AND new='1'");

$uchUrl = $boardurl.'manyou/cp.php?ac=userapp';

//manyou
$my_prefix = 'http://uchome.manyou.com';
if(empty($_GET['my_suffix'])) {
	$appId = intval($_GET['appid']);
	if($appId) {
		$mode = $_GET['mode'];
		if ($mode == 'about') {
			$my_suffix = '/userapp/about?appId='.$appId;
		} else {
			$my_suffix = '/userapp/privacy?appId='.$appId;
		}
	} else {
		$my_suffix = '/userapp/list';
	}
} else {
	$my_suffix = $_GET['my_suffix'];
}
$my_extra = isset($_GET['my_extra']) ? $_GET['my_extra'] : '';

$delimiter = strrpos($my_suffix, '?') ? '&' : '?';
$myUrl = $my_prefix.urldecode($my_suffix.$delimiter.'my_extra='.$my_extra);

$hash = $my_siteid.'|'.$discuz_uid.'|'.$my_sitekey.'|'.$timestamp;
$hash = md5($hash);
$delimiter = strrpos($myUrl, '?') ? '&' : '?';

$url = $myUrl.$delimiter.'s_id='.$my_siteid.'&uch_id='.$discuz_uid.'&uch_url='.rawurlencode($uchUrl).'&my_suffix='.rawurlencode($my_suffix).'&timestamp='.$timestamp.'&my_sign='.$hash;

include_once template("manyou_userapp");

?>