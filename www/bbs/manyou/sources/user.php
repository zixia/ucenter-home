<?php
/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: user.php 20442 2009-09-28 01:17:13Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include_once DISCUZ_ROOT.'./forumdata/cache/cache_manyou.php';

$invitenum = $db->result_first("SELECT count(*) FROM {$tablepre}myinvite WHERE touid='$discuz_uid'");
$noticenum = $db->result_first("SELECT count(*) FROM {$tablepre}mynotice WHERE uid='$discuz_uid' AND new='1'");

$appid = empty($_GET['id'])?'':intval($_GET['id']);

if(!$appid) {
	showmessage('manyou:noappid');
}

$app = array();
$app = $db->fetch_first("SELECT * FROM {$tablepre}myapp WHERE appid='$appid' LIMIT 1");
if($app['flag'] < 0) {
	showmessage('manyou:noappid');
}

$userapp = $db->fetch_first("SELECT * FROM {$tablepre}userapp WHERE uid='{$discuz_uid}' AND appid='$appid'");

$my_appId = $appid;
$my_suffix = !empty($my_suffix) ? base64_decode(urldecode($my_suffix)) : '/';
$my_prefix = $boardurl.'manyou/';

if(preg_match('/^\//', $my_suffix)) {
	$url = 'http://apps.manyou.com/'.$my_appId.$my_suffix;
} else {
	$url = 'http://apps.manyou.com/'.$my_appId.'/'.$my_suffix;
}

$url = $url.(strpos($my_suffix, '?') ? '&' : '?').'my_uchId='.$discuz_uid.'&my_sId='.$my_siteid.'&my_prefix='.rawurlencode($my_prefix).'&my_suffix='.rawurlencode($my_suffix);
$current_url = $boardurl.'manyou/userapp.php';
if($_SERVER['QUERY_STRING']) {
	$current_url = $current_url.'?'.$_SERVER['QUERY_STRING'];
}

$extra = $my_extra;
$url .= '&my_current='.rawurlencode($current_url).'&my_extra='.rawurlencode($extra).'&my_ts='.$timestamp.'&my_appVersion='.$app['version'];
$hash = $my_siteid.'|'.$discuz_uid.'|'.$appid.'|'.$current_url.'|'.$extra.'|'.$timestamp.'|'.$my_sitekey;
$hash = md5($hash);
$url .= '&my_sig='.$hash;
$my_suffix = rawurlencode($my_suffix);

include template('manyou_app');

?>