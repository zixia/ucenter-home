<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: category.inc.php 21018 2009-11-06 06:57:53Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$gquery = $db->query("SELECT f.fid, f.fup, f.type, f.name, ff.moderators, ff.extra FROM {$tablepre}forums f LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid WHERE f.fid='$gid'");

$sql = $accessmasks	? "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, f.inheritedmod, ff.description, ff.moderators, ff.icon, ff.viewperm, ff.extra, a.allowview FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid
				LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid
				WHERE f.fup='$gid' AND f.status='1' AND f.type='forum' ORDER BY f.displayorder"
			: "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, f.inheritedmod, ff.description, ff.moderators, ff.icon, ff.viewperm, ff.extra FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff USING(fid)
				WHERE f.fup='$gid' AND f.status='1' AND f.type='forum' ORDER BY f.displayorder";

$query = $db->query($sql);
if(!$db->num_rows($gquery) || !$db->num_rows($query)) {
	showmessage('forum_nonexistence', NULL, 'HALTED');
}

while(($forum = $db->fetch_array($gquery)) || ($forum = $db->fetch_array($query))) {
	$forum['extra'] = unserialize($forum['extra']);
	if(!is_array($forum['extra'])) {
		$forum['extra'] = array();
	}
	if($forum['type'] != 'group') {
		$threads += $forum['threads'];
		$posts += $forum['posts'];
		$todayposts += $forum['todayposts'];
		if(forum($forum)) {
			$forum['orderid'] = $catlist[$forum['fup']]['forumscount'] ++;
			$forum['subforums'] = '';
			$forumlist[$forum['fid']] = $forum;
			$catlist[$forum['fup']]['forums'][] = $forum['fid'];
			$fids .= ','.$forum['fid'];
		}
	} else {
		$forum['collapseimg'] = 'collapsed_no.gif';
		$collapse['category_'.$forum['fid']] = '';

		if($forum['moderators']) {
			$forum['moderators'] = moddisplay($forum['moderators'], 'flat');
		}
		$forum['forumscount'] = 0;
		$forum['forumcolumns'] = 0;
		$catlist[$forum['fid']] = $forum;

		$navigation = '&raquo; '.$forum['name'];
		$navtitle = strip_tags($forum['name']).' - ';
	}

}

$query = $db->query("SELECT fid, fup, name, threads, posts, todayposts FROM {$tablepre}forums WHERE status='1' AND fup IN ($fids) AND type='sub' ORDER BY displayorder");
while($forum = $db->fetch_array($query)) {

	if($subforumsindex && $forumlist[$forum['fup']]['permission'] == 2) {
		$forumlist[$forum['fup']]['subforums'] .= '<a href="forumdisplay.php?fid='.$forum['fid'].'"><u>'.$forum['name'].'</u></a>&nbsp;&nbsp;';
	}
	$forumlist[$forum['fup']]['threads'] 	+= $forum['threads'];
	$forumlist[$forum['fup']]['posts'] 	+= $forum['posts'];
	$forumlist[$forum['fup']]['todayposts'] += $forum['todayposts'];

}

?>