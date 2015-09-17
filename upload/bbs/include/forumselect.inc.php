<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forumselect.inc.php 20900 2009-10-29 02:49:38Z tiger $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!isset($_DCACHE['forums'])) {
	require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
}

$grouplist = $commonlist = '';
$forumlist = $subforumlist = array();
$i = array();

$commonfids = explode('D', $_DCOOKIE['visitedfid']);

if($discuz_uid) {
	$query = $db->query("SELECT fid FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND fid>0");
	while($fav = $db->fetch_array($query)) {
		$commonfids[] = $fav['fid'];
	}
}

foreach($commonfids as $k => $fid) {
	if($_DCACHE['forums'][$fid]['type'] == 'sub') {
		$commonfids[] = $_DCACHE['forums'][$fid]['fup'];
		unset($commonfids[$k]);
	}
}

$commonfids = array_unique($commonfids);

foreach($commonfids as $fid) {
	$commonlist .= '<li fid="'.$fid.'">'.$_DCACHE['forums'][$fid]['name'].'</li>';
}

foreach($_DCACHE['forums'] as $forum) {
	if(!$forum['status'] || $forum['status'] == 2) {
		continue;
	}
	if($forum['type'] == 'group') {
		$grouplist .= '<li fid="'.$forum['fid'].'">'.$forum['name'].'</li>';
		$visible[$forum['fid']] = true;
	} elseif($forum['type'] == 'forum' && isset($visible[$forum['fup']]) && (!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || strstr($forum['users'], "\t$discuz_uid\t"))) {
		$forumlist[$forum['fup']] .= '<li fid="'.$forum['fid'].'">'.$forum['name'].'</li>';
		$visible[$forum['fid']] = true;
	} elseif($forum['type'] == 'sub' && isset($visible[$forum['fup']]) && (!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || strstr($forum['users'], "\t$discuz_uid\t"))) {
		$subforumlist[$forum['fup']] .= '<li fid="'.$forum['fid'].'">'.$forum['name'].'</li>';
	}
}

include template('post_forumselect');
exit;

?>