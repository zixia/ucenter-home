<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: viewthread_poll.inc.php 18454 2009-05-22 01:52:27Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$polloptions = array();
$votersuid = '';

if($count = $sdb->fetch_first("SELECT MAX(votes) AS max, SUM(votes) AS total FROM {$tablepre}polloptions WHERE tid = '$tid'")) {


	$options = $sdb->fetch_first("SELECT multiple, visible, maxchoices, expiration, overt FROM {$tablepre}polls WHERE tid='$tid'");
	$multiple = $options['multiple'];
	$visible = $options['visible'];
	$maxchoices = $options['maxchoices'];
	$expiration = $options['expiration'];
	$overt = $options['overt'];

	$query = $sdb->query("SELECT polloptionid, votes, polloption, voterids FROM {$tablepre}polloptions WHERE tid='$tid' ORDER BY displayorder");
	$voterids = '';
	$bgcolor = rand(0, 9);
	while($options = $sdb->fetch_array($query)) {
		if($bgcolor > 9) {
			$bgcolor = 0;
		}
		$viewvoteruid[] = $options['voterids'];
		$voterids .= "\t".$options['voterids'];
		$polloptions[] = array
		(
			'polloptionid'	=> $options['polloptionid'],
			'polloption'	=> preg_replace("/\[url=(https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
				"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $options['polloption']),
			'votes'		=> $options['votes'],
			'width'		=> @round($options['votes'] * 300 / $count['max']) + 2,
			'percent'	=> @sprintf("%01.2f", $options['votes'] * 100 / $count['total']),
			'color'		=> $bgcolor
		);
		$bgcolor++;
	}

	$voterids = explode("\t", $voterids);
	$voters = array_unique($voterids);
	$voterscount = count($voters) - 1;
	array_shift($voters);

	if(!$expiration) {
		$expirations = $timestamp + 86400;
	} else {
		$expirations = $expiration;
		if($expirations > $timestamp) {
			$thread['remaintime'] = remaintime($expirations - $timestamp);
		}
	}

	$allwvoteusergroup = $allowvote;
	$allowvotepolled = !in_array(($discuz_uid ? $discuz_uid : $onlineip), $voters);
	$allowvotethread = (!$thread['closed'] && !checkautoclose() || $alloweditpoll) && $timestamp < $expirations && $expirations > 0;

	$allowvote = $allwvoteusergroup && $allowvotepolled && $allowvotethread;

	$optiontype = $multiple ? 'checkbox' : 'radio';
	$visiblepoll = $visible || $forum['ismoderator'] || ($discuz_uid && $discuz_uid == $thread['authorid']) || ($expirations >= $timestamp && in_array(($discuz_uid ? $discuz_uid : $onlineip), $voters)) || $expirations < $timestamp ? 0 : 1;
}

?>