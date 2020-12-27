<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: viewthread_debate.inc.php 21214 2009-11-20 07:17:05Z liulanbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$debate = $thread;
$debate = $sdb->fetch_first("SELECT * FROM {$tablepre}debates WHERE tid='$tid'");
$debate['dbendtime'] = $debate['endtime'];
if($debate['dbendtime']) {
	$debate['endtime'] = gmdate("$dateformat $timeformat", $debate['dbendtime'] + $timeoffset * 3600);
}
if($debate['dbendtime'] > $timestamp) {
	$debate['remaintime'] = remaintime($debate['dbendtime'] - $timestamp);
}
$debate['starttime'] = dgmdate("$dateformat $timeformat", $debate['starttime'] + $timeoffset * 3600);
$debate['affirmpoint'] = discuzcode($debate['affirmpoint'], 0, 0, 0, 1, 1, 0, 0, 0, 0, 0);
$debate['negapoint'] = discuzcode($debate['negapoint'], 0, 0, 0, 1, 1, 0, 0, 0, 0, 0);
if($debate['affirmvotes'] || $debate['negavotes']) {
	if($debate['affirmvotes'] && $debate['affirmvotes'] > $debate['negavotes']) {
		$debate['affirmvoteswidth'] = 100;
		$debate['negavoteswidth'] = intval($debate['negavotes'] / $debate['affirmvotes'] * 100);
	} elseif($debate['negavotes'] && $debate['negavotes'] > $debate['affirmvotes']) {
		$debate['negavoteswidth'] = 100;
		$debate['affirmvoteswidth'] = intval($debate['affirmvotes'] / $debate['negavotes'] * 100);
	} else {
		$debate['affirmvoteswidth'] = $debate['negavoteswidth'] = 100;
	}
} else {
	$debate['negavoteswidth'] = $debate['affirmvoteswidth'] = 0;
}
if($debate['umpirepoint']) {
	$debate['umpirepoint'] = discuzcode($debate['umpirepoint'], 0, 0, 0, 1, 1, 1, 0, 0, 0, 0);
}
$debate['umpireurl'] = rawurlencode($debate['umpire']);
list($debate['bestdebater'], $debate['bestdebateruid'], $debate['bestdebaterstand'], $debate['bestdebatervoters'], $debate['bestdebaterreplies']) = explode("\t", $debate['bestdebater']);
$debate['bestdebaterurl'] = rawurlencode($debate['bestdebater']);

$query = $sdb->query("SELECT author, authorid FROM {$tablepre}posts p LEFT JOIN {$tablepre}debateposts dp ON p.pid=dp.pid WHERE p.tid='$tid' AND p.invisible='0' AND dp.stand='1' GROUP BY dp.uid ORDER BY p.dateline DESC LIMIT 5");
while($affirmavatar = $sdb->fetch_array($query)) {
	$debate['affirmavatars'] .= '<a title="'.$affirmavatar['author'].'" target="_blank" href="space.php?uid='.$affirmavatar['authorid'].'">'.discuz_uc_avatar($affirmavatar['authorid'], 'small').'</a>';
}

$query = $sdb->query("SELECT author, authorid FROM {$tablepre}posts p LEFT JOIN {$tablepre}debateposts dp ON p.pid=dp.pid WHERE p.tid='$tid' AND p.invisible='0' AND dp.stand='2' GROUP BY dp.uid ORDER BY p.dateline DESC LIMIT 5");
while($negaavatar = $sdb->fetch_array($query)) {
	$debate['negaavatars'] .= '<a title="'.$negaavatar['author'].'" target="_blank" href="space.php?uid='.$negaavatar['authorid'].'">'.discuz_uc_avatar($negaavatar['authorid'], 'small').'</a>';
}

if($fastpost && $allowpostreply && $thread['closed'] == 0) {
	$firststand = $sdb->result_first("SELECT stand FROM {$tablepre}debateposts WHERE tid='$tid' AND uid='$discuz_uid' AND stand<>'0' ORDER BY dateline LIMIT 1");
}

?>