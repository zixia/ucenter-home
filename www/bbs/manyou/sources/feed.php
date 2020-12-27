<?php
/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: feed.php 20442 2009-09-28 01:17:13Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./forumdata/cache/cache_manyou.php';
if(!isset($myapps)) {
	$query = $db->query("SELECT * FROM {$tablepre}myapp WHERE flag='1' ORDER BY displayorder");
	while($application = $db->fetch_array($query)) {
		$myapps[$application['appid']] = $application;
	}
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	writetocache('manyou', '', getcachevars(array('myapps' => $myapps)));
}
require_once DISCUZ_ROOT.'./uc_client/client.php';

$invitenum = $db->result_first("SELECT count(*) FROM {$tablepre}myinvite WHERE touid='$discuz_uid'");
$noticenum = $db->result_first("SELECT count(*) FROM {$tablepre}mynotice WHERE uid='$discuz_uid' AND new='1'");

$feeds = array();
$my_feedpp = $my_feedpp > 50 ? $my_feedpp : 50;
$view = !empty($view) && in_array($view, array('me', 'all', 'friend')) ? $view : 'friend';
if(!$discuz_uid) {
	$view = 'all';
}
$conf = array(
	'type' => 'manyou',
	'num' => $my_feedpp,
	'cachelife' => 0,
	'multipage' => 1,
	'page_url' => 'userapp.php?view='.$view
);

$apps = $myapps;
$query = $db->query("SELECT appid FROM {$tablepre}userapp WHERE uid='$discuz_uid' AND allowfeed='0'");
if($db->num_rows($query)) {
	while($userapp = $db->fetch_array($query)) {
		unset($apps[$userapp['appid']]);
	}
	$conf['appid'] = array_keys($apps);
}

if($view == 'me') {
	$conf['uid'] = $discuz_uid;
} elseif($view == 'friend') {
	$friendnum = uc_friend_totalnum($discuz_uid, 3);
	$friends = uc_friend_ls($discuz_uid, 1, $friendnum, $friendnum, 3);
	foreach($friends as $friend) {
		$conf['uid'][] = $friend['friendid'];
	}
}

$now = $timestamp + $timeoffset * 3600;
$day1 = gmdate($dateformat, $now);
$day2 = gmdate($dateformat, $now - 86400);
$day3 = gmdate($dateformat, $now - 172800);

$feeds = get_feed($conf);
$feeddate = '';$bi = 1;
foreach($feeds['data'] as $k => $feed) {
	$trans['{addbuddy}'] = $view == 'all' && $feed['uid'] != $discuz_uid ? '<a href="my.php?item=buddylist&newbuddyid='.$feed['uid'].'&buddysubmit=yes" id="ajax_buddy_'.($bi++).'" title="添加为好友" onclick="ajaxmenu(this, 3000);doane(event);"><img style="vertical-align:middle" src="manyou/images/myadd.gif" /></a>' : '';
	$feeds['data'][$k]['title'] = strtr($feed['title'], $trans);
	$feeds['data'][$k]['body'] = strtr($feed['body'], $trans);
	if($discuz_uid) {
		$feeds['data'][$k]['title'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="\\3&from='.$from.'" \\4>', $feeds['data'][$k]['title']);
		$feeds['data'][$k]['body'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="\\3&from='.$from.'" \\4>', $feeds['data'][$k]['body']);
	} else {
		$defurl = $regname.'" onclick="showWindow(\'register\', this.href);';
		$feeds['data'][$k]['title'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="'.$defurl.'" \\4>', $feeds['data'][$k]['title']);
		$feeds['data'][$k]['body'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="'.$defurl.'" \\4>', $feeds['data'][$k]['body']);
	}

	list($feeds['data'][$k]['body'], $feeds['data'][$k]['general']) = explode(chr(0).chr(0).chr(0), $feeds['data'][$k]['body']);
	$feeds['data'][$k]['icon_image'] = 'http://appicon.manyou.com/icons/'.$feed['appid'];
	$dateline = $feed['dbdateline'] + $timeoffset * 3600;
	$feeds['data'][$k]['date'] = gmdate($dateformat, $dateline);
	if($feeddate != $feeds['data'][$k]['date']) {
		$feeds['data'][$k]['daterange'] = $feeds['data'][$k]['date'];
	} else {
		$feeds['data'][$k]['daterange'] = '';
	}
	$feeddate = $feeds['data'][$k]['date'];
}

$multi = $feeds['multipage'];
$feeds = $feeds['data'];

include template('manyou_feed');

?>