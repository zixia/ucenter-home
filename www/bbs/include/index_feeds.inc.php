<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: index_feeds.inc.php 20822 2009-10-26 10:20:57Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'./include/forum.func.php';

$discuz_action = 1;

$lastvisittime = dgmdate("$dateformat $timeformat", $lastvisit + $timeoffset * 3600);
$newthreads = round(($timestamp - $lastvisit + 600) / 1000) * 1000;
$lastvisit = $lastvisit ? dgmdate("$dateformat $timeformat", $lastvisit + 3600 * $timeoffset) : 0;

$announcements = '';
if($_DCACHE['announcements']) {
	$readapmids = !empty($_DCOOKIE['readapmid']) ? explode('D', $_DCOOKIE['readapmid']) : array();
	foreach($_DCACHE['announcements'] as $announcement) {
		if(empty($announcement['groups']) || in_array($groupid, $announcement['groups'])) {
			if(empty($announcement['type'])) {
				$announcements .= '<li><a href="announcement.php?id='.$announcement['id'].'">'.$announcement['subject'].
					'<em>('.gmdate($dateformat, $announcement['starttime'] + $timeoffset * 3600).')</em></a></li>';
			} elseif($announcement['type'] == 1) {
				$announcements .= '<li><a href="'.$announcement['message'].'" target="_blank">'.$announcement['subject'].
					'<em>('.gmdate($dateformat, $announcement['starttime'] + $timeoffset * 3600).')</em></a></li>';
			}
		}
	}
}
unset($_DCACHE['announcements']);

$postdata = $historyposts ? explode("\t", $historyposts) : array();
$heats = array();
$threads = $posts = $todayposts = 0;
$now = $timestamp + $timeoffset * 3600;
$day1 = gmdate($dateformat, $now);
$day2 = gmdate($dateformat, $now - 86400);
$day3 = gmdate($dateformat, $now - 172800);
$type = empty($type) ? '' : $type;

if(!isset($_COOKIE['discuz_collapse']) || strpos($_COOKIE['discuz_collapse'], 'sidebar') === FALSE) {
	$collapseimg['sidebar'] = 'collapsed_no';
	$collapse['sidebar'] = '';
} else {
	$collapseimg['sidebar'] = 'collapsed_yes';
	$collapse['sidebar'] = 'display: none';
}
$infosidestatus['allow'] = $infosidestatus['allow'] && $infosidestatus[2] && $infosidestatus[2] != -1 ? (!$collapse['sidebar'] ? 2 : 1) : 0;

if($indexhot['status'] && $_DCACHE['heats']['expiration'] < $timestamp) {
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache('heats');
}

if(!$type) {
	$view = empty($view) ? 0 : 1;
	$conf = array(
		'num' => 50,
		'cachelife' => 300,
		'multipage' => $view,
		'page_url' => $indexname.(!empty($view) ? '?view=all&op=feeds' : '')
	);
} elseif($type == 'manyou') {
	$conf = array(
		'type' => 'manyou',
		'num' => '50',
		'cachelife' => 300,
		'multipage' => 0,
		'page_url' => $indexname
	);
}

$feeds = get_feed($conf);
$feeddate = '';
if(empty($type)) {
	foreach($feeds['data'] as $k => $feed) {
		$feeds['data'][$k]['date'] = gmdate($dateformat, $feed['dbdateline'] + $timeoffset * 3600);
		$feeds['data'][$k]['daterange'] = $feeddate != $feeds['data'][$k]['date'] ? $feeds['data'][$k]['date'] : '';
		$feeds['data'][$k]['title'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="\\3&from=indexfeeds" \\4>', $feeds['data'][$k]['title']);
		$feeds['data'][$k]['body'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="\\3&from=indexfeeds" \\4>', $feeds['data'][$k]['body']);
		$feeddate = $feeds['data'][$k]['date'];
	}
} else {
	$bi = 1;
	foreach($feeds['data'] as $k => $feed) {
		$trans['{addbuddy}'] = $feed['uid'] != $discuz_uid ? '<a href="my.php?item=buddylist&newbuddyid='.$feed['uid'].'&buddysubmit=yes" id="ajax_buddy_'.($bi++).'" onclick="ajaxmenu(this, 3000);doane(event);"><img style="vertical-align:middle" src="manyou/images/myadd.gif" /></a>' : '';
		$feeds['data'][$k]['title'] = strtr($feed['title'], $trans);
		$feeds['data'][$k]['body'] = strtr($feed['body'], $trans);
		$feeds['data'][$k]['title'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="\\3&from=indexfeeds" \\4>', $feeds['data'][$k]['title']);
		$feeds['data'][$k]['body'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="\\3&from=indexfeeds" \\4>', $feeds['data'][$k]['body']);
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
}

$multi = $feeds['multipage'];
$feeds = $feeds['data'];

$sql = !empty($accessmasks) ?
	"SELECT f.threads, f.posts, f.todayposts, ff.viewperm, a.allowview FROM {$tablepre}forums f
		LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid
		LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid
		WHERE f.status='1' ORDER BY f.type, f.displayorder"
	: "SELECT f.threads, f.posts, f.todayposts, ff.viewperm FROM {$tablepre}forums f
		LEFT JOIN {$tablepre}forumfields ff USING(fid)
		WHERE f.status='1' ORDER BY f.type, f.displayorder";
$query = $db->query($sql);
while($forumdata = $db->fetch_array($query)) {
	if(!$forumdata['viewperm'] || ($forumdata['viewperm'] && forumperm($forumdata['viewperm'])) || !empty($forumdata['allowview'])) {
		$threads += $forumdata['threads'];
		$posts += $forumdata['posts'];
		$todayposts += $forumdata['todayposts'];
	}
}

include template('discuz_feeds');

?>