<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: admincp.php 20442 2009-09-28 01:17:13Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!isfounder()) {
	showmessage('manyou:nofounder');
}

define('MY_URL', 'http://api.manyou.com/uchome.php');

if(submitcheck('mysubmit')) {
	$sitekey = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='siteuniqueid'");

	if(empty($my_status) && !empty($my_siteid) && !empty($my_sitekey)) {
		$my_status = 1;
	}
	$register = 0;
	if(empty($my_status)) {
		$register = 1;
		$res = my_site_register($sitekey, $bbname, $boardurl.'manyou/', UC_API, $charset, $timeoffset, 0, 0);
	} else {
		$res = my_site_refresh($my_siteid, $bbname, $boardurl.'manyou/', UC_API, $charset, $timeoffset, 0, 0, $my_sitekey, $sitekey);
	}
	if($res['errCode']) {
		showmessage('manyou:myop_error');
	} else {
		require_once DISCUZ_ROOT.'./include/cache.func.php';
		if($register) {
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('my_siteid', '{$res[result][mySiteId]}'), ('my_sitekey', '{$res[result][mySiteKey]}'), ('my_status', '1')");
			updatecache('settings');
			showmessage('manyou:myop_open', 'userapp.php?script=admincp');
		} else {
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('my_status', '1')");
			updatecache('settings');
			showmessage('manyou:myop_sync', 'userapp.php?script=admincp');
		}
	}
} elseif(submitcheck('closemysubmit')) {
	$res = my_site_close($my_siteid, $my_sitekey);
	$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('my_status', '0')");
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache('settings');
	if($res['errCode']) {
		showmessage('manyou:myop_error');
	} else {
		showmessage('manyou:myop_close', 'userapp.php?script=admincp');
	}
} elseif(submitcheck('settingmysubmit')) {
	$myextcreditnew = intval($myextcreditnew);
	$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('my_extcredit', '$myextcreditnew')");
	$myfeedppnew = intval($myfeedppnew);
	$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('my_feedpp', '$myfeedppnew')");
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache('settings');
	showmessage('manyou:setting_update', 'userapp.php?script=admincp');
}

if($my_status) {
	$selfUrl = $boardurl.'manyou/admincp.php?ac=userapp';

	$my_suffix = !empty($my_suffix) ? $my_suffix : '/appadmin/list';

	$my_prefix = 'http://uchome.manyou.com';
	$tmp_suffix = $my_suffix;
	$myUrl = $my_prefix.$tmp_suffix;
	$hash = md5($my_siteid.'|'.$discuz_uid.'|'.$my_sitekey.'|'.$timestamp);
	$delimiter = strrpos($myUrl, '?') ? '&' : '?';
	$url = $myUrl.$delimiter.'s_id='.$my_siteid.'&uch_id='.$discuz_uid.'&uch_url='.rawurlencode($selfUrl).'&my_suffix='.rawurlencode($my_suffix).'&timestamp='.$timestamp.'&my_sign='.$hash;
	$my_noticejs = my_noticejs();
}

include template('manyou_admincp');

function my_site_register($siteKey, $siteName, $siteUrl, $ucUrl, $siteCharset, $siteTimeZone, $siteRealNameEnable, $siteRealAvatarEnable) {
	$siteName = urlencode($siteName);
	$postString = sprintf('action=%s&productType=DISCUZ&siteVersion=7.0.0&myVersion=0.3&siteKey=%s&siteName=%s&siteUrl=%s&ucUrl=%s&siteCharset=%s&siteTimeZone=%s&siteRealNameEnable=%s&siteRealAvatarEnable=%s', 'siteRegister', $siteKey, $siteName, rawurlencode($siteUrl), rawurlencode($ucUrl), $siteCharset, $siteTimeZone, $siteRealNameEnable, $siteRealAvatarEnable);
	$response = dfopen(MY_URL, 0, $postString, '', false);
	$res = unserialize($response);
	if (!$response) {
		$res['errCode'] = 111;
		$res['errMessage'] = 'Empty Response';
		$res['result'] = $response;
	} elseif(!$res) {
		$res['errCode'] = 110;
		$res['errMessage'] = 'Error Response';
		$res['result'] = $response;
	}
	return $res;
}

function my_site_refresh($mySiteId, $siteName, $siteUrl, $ucUrl, $siteCharset, $siteTimeZone, $siteEnableRealName, $siteEnableRealAvatar, $mySiteKey, $siteKey) {
	$key = $mySiteId.$siteName.$siteUrl.$ucUrl.$siteCharset.$siteTimeZone.$siteEnableRealName.$mySiteKey.$siteKey;
	$key = md5($key);
	$siteName = urlencode($siteName);
	$postString = sprintf('action=%s&productType=DISCUZ&siteVersion=7.0.0&myVersion=0.3&key=%s&mySiteId=%d&siteName=%s&siteUrl=%s&ucUrl=%s&siteCharset=%s&siteTimeZone=%s&siteEnableRealName=%s&siteEnableRealAvatar=%s&siteKey=%s', 'siteRefresh', $key, $mySiteId, $siteName, rawurlencode($siteUrl), rawurlencode($ucUrl), $siteCharset, $siteTimeZone, $siteEnableRealName, $siteEnableRealAvatar, $siteKey);
	$response = dfopen(MY_URL, 0, $postString, '', false);
	$res = unserialize($response);
	if (!$response) {
		$res['errCode'] = 111;
		$res['errMessage'] = 'Empty Response';
		$res['result'] = $response;
	} elseif(!$res) {
		$res['errCode'] = 110;
		$res['errMessage'] = 'Error Response';
		$res['result'] = $response;
	}
	return $res;
}

function my_site_close($mySiteId, $mySiteKey) {
	$key = $mySiteId.$mySiteKey;
	$key = md5($key);
	$postString = sprintf('action=%s&key=%s&mySiteId=%d', 'siteClose', $key, $mySiteId);
	$response = dfopen(MY_URL, 0, $postString, '', false);
	$res = unserialize($response);
	if (!$response) {
		$res['errCode'] = 111;
		$res['errMessage'] = 'Empty Response';
		$res['result'] = $response;
	} elseif(!$res) {
		$res['errCode'] = 110;
		$res['errMessage'] = 'Error Response';
		$res['result'] = $response;
	}
	return $res['result'];
}

function my_noticejs() {
	$key = md5($GLOBALS['my_siteid'].$GLOBALS['timestamp'].$GLOBALS['my_sitekey']);
	return '<script type="text/javascript" src="http://notice.uchome.manyou.com/notice?sId='.$GLOBALS['my_siteid'].'&ts='.$GLOBALS['timestamp'].'&key='.$key.'" charset="UTF-8"></script>';
}

?>