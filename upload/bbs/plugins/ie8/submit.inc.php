<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc. & (C)2005-2009 mfboy
	This is NOT a freeware, use is subject to license terms

	$Id: submit.inc.php 20544 2009-10-09 01:04:35Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_'.$identifier.'.php';
unset($name, $directory, $vars);

extract($_DPLUGIN[$identifier], EXTR_SKIP);
extract($vars);

if($charset != 'UTF-8') {
	require_once DISCUZ_ROOT.'./include/chinese.class.php';
	$chs = new Chinese('UTF-8', $charset);
	$selection = $chs->Convert($selection);
	unset($chs);
}

$selection = stripslashes($selection);
$selection_subject = cutstr(str_replace(array("\n", "\r", "\t"), ' ', $selection), 75);
$selection_message = htmlspecialchars($selection);

if(!$selection_subject || !$selection_message) {
	showmessage('ie8:message_invalid');
}

@include_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';

$forums = $myfavorites = $myfavfids = array();

foreach($_DCACHE['forums'] as $fid => $forum) {
	$forumfup = $_DCACHE['forums'][$forum['fup']];
	if($forum['type'] == 'forum') {
		$forums[$fid] = $_DCACHE['forums'][$forum['fup']]['name'].' &raquo; '.$forum['name'];
	} elseif($forum['type'] == 'sub') {
		$forums[$fid] = $_DCACHE['forums'][$forumfup['fup']]['name'].' &raquo; '.$_DCACHE['forums'][$forum['fup']]['name'].' &raquo; '.$forum['name'];
	}
}

if($discuz_uid) {
	$query = $db->query("SELECT fid FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND fid>0");
	while($fav = $db->fetch_array($query)) {
		$myfavfids[] = $fav['fid'];
	}
}

foreach($myfavfids as $fid) {
	$forum = $_DCACHE['forums'][$fid];
	$forumfup = $_DCACHE['forums'][$forum['fup']];

	if($forum['type'] == 'forum') {
		$myfavorites[$fid] = $_DCACHE['forums'][$forum['fup']]['name'].' &raquo; '.$forum['name'];
	} elseif ($forum['type'] == 'sub') {
		$myfavorites[$fid] = $_DCACHE['forums'][$forumfup['fup']]['name'].' &raquo; '.$_DCACHE['forums'][$forum['fup']]['name'].' &raquo; '.$forum['name'];
	}
}

include plugintemplate('ie8_submit');

?>