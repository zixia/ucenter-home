<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: stats.php 18819 2009-07-23 10:38:43Z liuqiang $
*/

define('CURSCRIPT', 'stats');
define('NOROBOT', TRUE);
require_once './include/common.inc.php';

$discuz_action = 131;

$statscachelife = $statscachelife * 60;

if(!$allowviewstats) {
	showmessage('group_nopermission', NULL, 'NOPERM');
}

$navstyle = array();
if(empty($type)) {
	$navstyle = array('home' => 'class="current"');
	$stattype = $statstatus ? "'total', 'month', 'hour'" : '';
} else {
	$navstyle = array($type => 'class="current"');
	$stattype = $type == 'agent' ?  "'os', 'browser'" : ($type == 'views' ? "'week', 'hour'" : '');
}

$stats_total = array();

if($stattype) {
	$query = $db->query("SELECT * FROM {$tablepre}stats WHERE type IN ($stattype) ORDER BY type");
	while($stats = $db->fetch_array($query)) {
		switch($stats['type']) {
			case 'total':
				$stats_total[$stats['variable']] = $stats['count'];
				break;
			case 'os':
				$stats_os[$stats['variable']] = $stats['count'];
				if($stats['count'] > $maxos) {
					$maxos = $stats['count'];
				}
				break;
			case 'browser':
				$stats_browser[$stats['variable']] = $stats['count'];
				if($stats['count'] > $maxbrowser) {
					$maxbrowser = $stats['count'];
				}
				break;
			case 'month':
				$stats_month[$stats['variable']] = $stats['count'];
				if($stats['count'] > $maxmonth) {
					$maxmonth = $stats['count'];
					$maxmonth_year = intval($stats['variable'] / 100);
					$maxmonth_month = $stats['variable'] - $maxmonth_year * 100;
				}
				ksort($stats_month);
				break;
			case 'week':
				$stats_week[$stats['variable']] = $stats['count'];
				if($stats['count'] > $maxweek) {
					$maxweek = $stats['count'];
					$maxweek_day = $stats['variable'];
				}
				ksort($stats_week);
				break;
			case 'hour':
				$stats_hour[$stats['variable']] = $stats['count'];
				if($stats['count'] > $maxhour) {
					$maxhour = $stats['count'];
					$maxhourfrom = $stats['variable'];
					$maxhourto = $maxhourfrom + 1;
				}
				ksort($stats_hour);
				break;
		}
	}
}

$newstatvars = array();

if((empty($type) && empty($statstatus)) || (isset($type) && $type == 'posts')) {

	$maxmonthposts = $maxdayposts = 0;
	$stats_monthposts = $stats_dayposts = array();

	$stats_dayposts['starttime'] = gmdate('Ymd', $timestamp - 86400 * 30);
	$db->query("DELETE FROM {$tablepre}statvars WHERE type='dayposts' AND variable<'$stats_dayposts[starttime]'");

	$query = $db->query("SELECT * FROM {$tablepre}statvars WHERE type='monthposts' OR type='dayposts' ORDER BY variable");
	while($variable = $db->fetch_array($query)) {
		$variable['type'] == 'monthposts' ?	$stats_monthposts[$variable['variable']] = $variable['value'] :
							$stats_dayposts[$variable['variable']] = $variable['value'];
	}

	if(!isset($stats_monthposts['starttime'])) {
		$starttime = $db->result_first("SELECT MIN(dateline) FROM {$tablepre}posts");
		$stats_monthposts['starttime'] = gmdate('Y-m-01', ($starttime ? $starttime : $timestamp));
		$newstatvars[] = "'monthposts', 'starttime', '$stats_monthposts[starttime]'";
	}

	for($dateline = strtotime($stats_monthposts['starttime']); $dateline < strtotime(gmdate('Y-m-01', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600)); $dateline += gmdate('t', $dateline + 86400 * 15) * 86400) {
		$month = gmdate('Ym', $dateline + $_DCACHE['settings']['timeoffset'] * 3600);
		if(!isset($stats_monthposts[$month])) {
			$stats_monthposts[$month] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE dateline BETWEEN $dateline AND $dateline+2592000 AND invisible='0'");
			$newstatvars[] = "'monthposts', '$month', '$stats_monthposts[$month]'";
		}
		if($stats_monthposts[$month] > $maxmonthposts) {
			$maxmonthposts = $stats_monthposts[$month];
		}
	}

	for($dateline = strtotime($stats_dayposts['starttime']); $dateline < strtotime(gmdate('Y-m-d', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600)); $dateline += 86400) {
		$day = gmdate('Ymd', $dateline + $_DCACHE['settings']['timeoffset'] * 3600);
		if(!isset($stats_dayposts[$day])) {
			$stats_dayposts[$day] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE dateline BETWEEN $dateline AND $dateline+86400 AND invisible='0'");
			$newstatvars[] = "'dayposts', '$day', '$stats_dayposts[$day]'";
		}
		if($stats_dayposts[$day] > $maxdayposts) {
			$maxdayposts = $stats_dayposts[$day];
		}
	}

	unset($stats_monthposts['starttime'], $stats_dayposts['starttime']);

	ksort($stats_dayposts);
	ksort($stats_monthposts);

}

if(empty($type)) {

	$newdatasql = '';
	$statvars = array();
	$query = $db->query("SELECT * FROM {$tablepre}statvars WHERE type='main'");
	while($variable = $db->fetch_array($query)) {
		$statvars[$variable['variable']] = $variable['value'];
	}

	if($timestamp - $statvars['lastupdate'] > $statscachelife) {
		$statvars = array('lastupdate' => $timestamp);
		$newstatvars[] = "'main', 'lastupdate', '$timestamp'";
	}

	if(isset($statvars['forums'])) {
		$forums = $statvars['forums'];
	} else {
		$forums = $db->result_first("SELECT COUNT(*) FROM {$tablepre}forums WHERE type IN ('forum', 'sub') AND status='1'");
		$newstatvars[] = "'main', 'forums', '$forums'";
	}

	if(isset($statvars['threads'])) {
		$threads = $statvars['threads'];
	} else {
		$threads = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE displayorder>='0'");
		$newstatvars[] = "'main', 'threads', '$threads'";
	}

	if(isset($statvars['posts']) && isset($statvars['runtime'])) {
		$posts = $statvars['posts'];
		$runtime = $statvars['runtime'];
	} else {
		$query = $db->query("SELECT COUNT(*), (MAX(dateline)-MIN(dateline))/86400 FROM {$tablepre}posts");
		list($posts, $runtime) = $db->fetch_row($query);
		$newstatvars[] = "'main', 'posts', '$posts'";
		$newstatvars[] = "'main', 'runtime', '$runtime'";
	}

	if(isset($statvars['members'])) {
		$members = $statvars['members'];
	} else {
		$members = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members");
		$newstatvars[] = "'main', 'members', '$members'";
	}

	if(isset($statvars['postsaddtoday'])) {
		$postsaddtoday = $statvars['postsaddtoday'];
	} else {
		$postsaddtoday = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE dateline>='".($timestamp - 86400)."' AND invisible='0'");
		$newstatvars[] = "'main', 'postsaddtoday', '$postsaddtoday'";
	}

	if(isset($statvars['membersaddtoday'])) {
		$membersaddtoday = $statvars['membersaddtoday'];
	} else {
		$membersaddtoday = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members WHERE regdate>='".($timestamp - 86400)."'");
		$newstatvars[] = "'main', 'membersaddtoday', '$membersaddtoday'";
	}

	if(isset($statvars['admins'])) {
		$admins = $statvars['admins'];
	} else {
		$admins = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members WHERE adminid>'0'");
		$newstatvars[] = "'main', 'admins', '$admins'";
	}

	if(isset($statvars['memnonpost'])) {
		$memnonpost = $statvars['memnonpost'];
	} else {
		$memnonpost = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members WHERE posts='0'");
		$newstatvars[] = "'main', 'memnonpost', '$memnonpost'";
	}

	if(isset($statvars['hotforum'])) {
		$hotforum = unserialize($statvars['hotforum']);
	} else {
		$hotforum = $db->fetch_first("SELECT posts, threads, fid, name FROM {$tablepre}forums WHERE status='1' ORDER BY posts DESC LIMIT 1");
		$newstatvars[] = "'main', 'hotforum', '".addslashes(serialize($hotforum))."'";
	}

	if(isset($statvars['bestmem']) && isset($statvars['bestmemposts'])) {
		$bestmem = $statvars['bestmem'];
		$bestmemposts = $statvars['bestmemposts'];
	} else {
		$query = $db->query("SELECT author, COUNT(*) AS posts FROM {$tablepre}posts WHERE dateline>='$timestamp'-86400 AND invisible='0' AND authorid>'0' GROUP BY author ORDER BY posts DESC LIMIT 1");
		list($bestmem, $bestmemposts) = $db->fetch_row($query);
		if($bestmem) {
			$bestmem = '<a href="space.php?username='.rawurlencode($bestmem).'">'.addslashes($bestmem).'</a>';
		} else {
			$bestmem = 'None';
			$bestmemposts = 0;
		}
		$newstatvars[] = "'main', 'bestmem', '$bestmem'";
		$newstatvars[] = "'main', 'bestmemposts', '$bestmemposts'";
	}

	$mempost = $members - $memnonpost;
	@$mempostavg = sprintf ("%01.2f", $posts / $members);
	@$threadreplyavg = sprintf ("%01.2f", ($posts - $threads) / $threads);
	@$mempostpercent = sprintf ("%01.2f", 100 * $mempost / $members);
	@$postsaddavg = round($posts / $runtime);
	@$membersaddavg = round($members / $runtime);

	@$stats_total['visitors'] = $stats_total['members'] + $stats_total['guests'];
	@$pageviewavg = sprintf ("%01.2f", $stats_total['hits'] / $stats_total['visitors']);
	@$activeindex = round(($membersaddavg / $members + $postsaddavg / $posts) * 1500 + $threadreplyavg * 10 + $mempostavg * 1 + $mempostpercent / 10 + $pageviewavg);

	if($statstatus) {
		$statsbar_month = statsdata('month', $maxmonth, 'ksort');
	} else {
		$statsbar_monthposts = statsdata('monthposts', $maxmonthposts);
		$statsbar_dayposts = statsdata('dayposts', $maxdayposts);
	}

	$lastupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $timeoffset * 3600);
	$nextupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $statscachelife + $timeoffset * 3600);

	updatenewstatvars();
	include template('stats_main');

} elseif($type == 'posts' || ($statstatus && in_array($type, array('views', 'agent')))) {

	switch($type) {
		case 'views':	$statsbar_week = statsdata('week', $maxweek);
				$statsbar_hour = statsdata('hour', $maxhour);
				break;
		case 'agent':	$statsbar_browser = statsdata('browser', $maxbrowser, 'arsort');
				$statsbar_os = statsdata('os', $maxos, 'arsort');
				break;
		case 'posts':	$statsbar_monthposts = statsdata('monthposts', $maxmonthposts);
				$statsbar_dayposts = statsdata('dayposts', $maxdayposts);
				break;
	}

	updatenewstatvars();
	include template('stats_misc');

} elseif($type == 'threadsrank') {

	$threadsrank = '';

	$threadview = $threadreply = array();
	$query = $db->query("SELECT views, tid, subject FROM {$tablepre}threads WHERE displayorder>='0' ORDER BY views DESC LIMIT 0, 20");
	while($thread = $db->fetch_array($query)) {
		$thread['subject'] = cutstr($thread['subject'], 45);
		$threadview[] = $thread;
	}
	$query = $db->query("SELECT replies, tid, subject FROM {$tablepre}threads WHERE displayorder>='0' ORDER BY replies DESC LIMIT 0, 20");
	while($thread = $db->fetch_array($query)) {
		$thread['subject'] = cutstr($thread['subject'], 50);
		$threadreply[] = $thread;
	}

	for($i = 0; $i < 20; $i++) {
		$bgclass = $i % 2 ? ' class="colplural"' : '';
		$threadsrank .= "<tr".$bgclass."><td class=\"stat_subject\"><a href=\"viewthread.php?tid={$threadview[$i]['tid']}\">{$threadview[$i]['subject']}</a>&nbsp;</td><td class=\"stat_num\">{$threadview[$i]['views']}</td>\n".
			"<td class=\"stat_subject\"><a href=\"viewthread.php?tid={$threadreply[$i]['tid']}\">{$threadreply[$i]['subject']}</a><td class=\"stat_num\">{$threadreply[$i]['replies']}</td></tr>\n";
	}

	updatenewstatvars();
	include template('stats_misc');

} elseif($type == 'forumsrank') {

	$forumsrank = array();

	$statvars = array();
	$query = $db->query("SELECT * FROM {$tablepre}statvars WHERE type='forumsrank'");
	while($variable = $db->fetch_array($query)) {
		$statvars[$variable['variable']] = $variable['value'];
	}

	if($timestamp - $statvars['lastupdate'] > $statscachelife) {
		$statvars = array('lastupdate' => $timestamp);
		$newstatvars[] = "'forumsrank', 'lastupdate', '$timestamp'";
	}

	$threads = $posts = $thismonth = $today = array();

	if(isset($statvars['threads'])) {
		$threads = unserialize($statvars['threads']);
	} else {
		$statvars['forums'] = 0;
		$query = $db->query("SELECT fid, name, threads FROM {$tablepre}forums WHERE status='1' AND type<>'group' ORDER BY threads DESC LIMIT 0, 20");
		while($forum = $db->fetch_array($query)) {
			$statvars['forums']++;
			$threads[] = $forum;
		}
		$newstatvars[] = "'forumsrank', 'threads', '".addslashes(serialize($threads))."'";
		$newstatvars[] = "'forumsrank', 'forums', '$statvars[forums]'";
	}
	$statvars['forums'] = $statvars['forums'] ? $statvars['forums'] : 20;

	if(isset($statvars['posts'])) {
		$posts = unserialize($statvars['posts']);
	} else {
		$query = $db->query("SELECT fid, name, posts FROM {$tablepre}forums WHERE status='1' AND type<>'group' ORDER BY posts DESC LIMIT 0, $statvars[forums]");
		while($forum = $db->fetch_array($query)) {
			$posts[] = $forum;
		}
		$newstatvars[] = "'forumsrank', 'posts', '".addslashes(serialize($posts))."'";
	}

	if(isset($statvars['thismonth'])) {
		$thismonth = unserialize($statvars['thismonth']);
	} else {
		$query = $db->query("SELECT DISTINCT(p.fid) AS fid, f.name, COUNT(pid) AS posts FROM {$tablepre}posts p
			LEFT JOIN {$tablepre}forums f USING (fid)
			WHERE dateline>='$timestamp'-86400*30 AND invisible='0' AND authorid>'0'
			GROUP BY p.fid ORDER BY posts DESC LIMIT 0, $statvars[forums]");

		while($forum = $db->fetch_array($query)) {
			$thismonth[] = $forum;
		}
		$newstatvars[] = "'forumsrank', 'thismonth', '".addslashes(serialize($thismonth))."'";
	}

	if(isset($statvars['today'])) {
		$today = unserialize($statvars['today']);
	} else {
		$query = $db->query("SELECT DISTINCT(p.fid) AS fid, f.name, COUNT(pid) AS posts FROM {$tablepre}posts p
			LEFT JOIN {$tablepre}forums f USING (fid)
			WHERE dateline>='$timestamp'-86400 AND invisible='0' AND authorid>'0'
			GROUP BY p.fid ORDER BY posts DESC LIMIT 0, $statvars[forums]");

		while($forum = $db->fetch_array($query)) {
			$today[] = $forum;
		}
		$newstatvars[] = "'forumsrank', 'today', '".addslashes(serialize($today))."'";
	}

	for($i = 0; $i < $statvars['forums']; $i++) {
		$bgclass = $i % 2 ? ' class="colplural"' : '';
		@$forumsrank[0] .= $threads[$i]['name'] || $posts[$i]['name'] ? "<tr".$bgclass."><td class=\"stat_subject\"><a href=\"forumdisplay.php?fid={$threads[$i]['fid']}\" target=\"_blank\">{$threads[$i]['name']}</a></td><td class=\"stat_num\">{$threads[$i]['threads']}</td>\n".
			"<td class=\"stat_subject\"><a href=\"forumdisplay.php?fid={$posts[$i]['fid']}\" target=\"_blank\">{$posts[$i]['name']}</a></td><td class=\"stat_num\">{$posts[$i]['posts']}</td>\n" : '';
		@$forumsrank[1] .= $thismonth[$i]['name'] || $today[$i]['name'] ? "<tr".$bgclass."><td class=\"stat_subject\"><a href=\"forumdisplay.php?fid={$thismonth[$i]['fid']}\" target=\"_blank\">{$thismonth[$i]['name']}</a></td><td class=\"stat_num\">{$thismonth[$i]['posts']}</td>\n".
			"<td class=\"stat_subject\"><a href=\"forumdisplay.php?fid={$today[$i]['fid']}\" target=\"_blank\">{$today[$i]['name']}</a></td><td class=\"stat_num\">{$today[$i]['posts']}</td></tr>\n" : '';
	}

	$lastupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $timeoffset * 3600);
	$nextupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $statscachelife + $timeoffset * 3600);

	updatenewstatvars();
	include template('stats_misc');

} elseif($type == 'postsrank') {

	$postsrank = '';

	$statvars = array();
	$query = $db->query("SELECT * FROM {$tablepre}statvars WHERE type='postsrank'");
	while($variable = $db->fetch_array($query)) {
		$statvars[$variable['variable']] = $variable['value'];
	}

	if($timestamp - $statvars['lastupdate'] > $statscachelife) {
		$statvars = array('lastupdate' => $timestamp);
		$newstatvars[] = "'postsrank', 'lastupdate', '$timestamp'";
	}

	$posts = $digestposts = $thismonth = $today = array();

	if(isset($statvars['posts'])) {
		$posts = unserialize($statvars['posts']);
	} else {
		$query = $db->query("SELECT username, uid, posts FROM {$tablepre}members ORDER BY posts DESC LIMIT 0, 20");
		while($member = $db->fetch_array($query)) {
			$posts[] = $member;
		}
		$newstatvars[] = "'postsrank', 'posts', '".addslashes(serialize($posts))."'";
	}

	if(isset($statvars['digestposts'])) {
		$digestposts = unserialize($statvars['digestposts']);
	} else {
		$query = $db->query("SELECT username, uid, digestposts FROM {$tablepre}members ORDER BY digestposts DESC LIMIT 0, 20");
		while($member = $db->fetch_array($query)) {
			$digestposts[] = $member;
		}
		$newstatvars[] = "'postsrank', 'digestposts', '".addslashes(serialize($digestposts))."'";
	}

	if(isset($statvars['thismonth'])) {
		$thismonth = unserialize($statvars['thismonth']);
	} else {
		$query = $db->query("SELECT DISTINCT(author) AS username, COUNT(pid) AS posts
			FROM {$tablepre}posts WHERE dateline>='$timestamp'-86400*30 AND invisible='0' AND authorid>'0'
			GROUP BY author ORDER BY posts DESC LIMIT 0, 20");

		while($member = $db->fetch_array($query)) {
			$thismonth[] = $member;
		}
		$newstatvars[] = "'postsrank', 'thismonth', '".addslashes(serialize($thismonth))."'";
	}

	if(isset($statvars['today'])) {
		$today = unserialize($statvars['today']);
	} else {
		$query = $db->query("SELECT DISTINCT(author) AS username, COUNT(pid) AS posts
			FROM {$tablepre}posts WHERE dateline >='$timestamp'-86400 AND invisible='0' AND authorid>'0'
			GROUP BY author ORDER BY posts DESC LIMIT 0, 20");

		while($member = $db->fetch_array($query)) {
			$today[] = $member;
		}
		$newstatvars[] = "'postsrank', 'today', '".addslashes(serialize($today))."'";
	}

	for($i = 0; $i < 20; $i++) {
		$bgclass = $i % 2 ? ' class="colplural"' : '';
		@$postsrank .= "<tr".$bgclass."><td class=\"stat_subject\"><a href=\"space.php?username=".rawurlencode($posts[$i]['username'])."\" target=\"_blank\">{$posts[$i]['username']}</a>&nbsp;</td><td class=\"stat_num\">{$posts[$i]['posts']}</td>\n".
			"<td class=\"stat_subject\"><a href=\"space.php?username=".rawurlencode($digestposts[$i]['username'])."\" target=\"_blank\">{$digestposts[$i]['username']}</a></td><td class=\"stat_num\">{$digestposts[$i]['digestposts']}</td>\n".
			"<td class=\"stat_subject\"><a href=\"space.php?username=".rawurlencode($thismonth[$i]['username'])."\" target=\"_blank\">{$thismonth[$i]['username']}</a></td><td class=\"stat_num\">{$thismonth[$i]['posts']}</td>\n".
			"<td class=\"stat_subject\"><a href=\"space.php?username=".rawurlencode($today[$i]['username'])."\" target=\"_blank\">{$today[$i]['username']}</a></td><td class=\"stat_num\">{$today[$i]['posts']}</td></tr>\n";
	}

	$lastupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $timeoffset * 3600);
	$nextupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $statscachelife + $timeoffset * 3600);

	updatenewstatvars();
	include template('stats_misc');

} elseif($type == 'creditsrank') {

	$creditsrank = '';

	$statvars = array();
	$query = $db->query("SELECT * FROM {$tablepre}statvars WHERE type='creditsrank'");
	while($variable = $db->fetch_array($query)) {
		$statvars[$variable['variable']] = $variable['value'];
	}

	if($timestamp - $statvars['lastupdate'] > $statscachelife) {
		$statvars = array('lastupdate' => $timestamp);
		$newstatvars[] = "'creditsrank', 'lastupdate', '$timestamp'";
	}

	//ATTENTION: initialize the arrays first!
	$credits = $extendedcredits = array();

	if(isset($statvars['credits'])) {
		$credits = unserialize($statvars['credits']);
	} else {
		$query = $db->query("SELECT username, uid, credits FROM {$tablepre}members ORDER BY credits DESC LIMIT 0, 20");
		while($member = $db->fetch_array($query)) {
			$credits[] = $member;
		}
		$newstatvars[] = "'creditsrank', 'credits', '".addslashes(serialize($credits))."'";
	}

	if(isset($statvars['extendedcredits'])) {
		$extendedcredits = unserialize($statvars['extendedcredits']);
	} else {
		foreach($extcredits as $id => $credit) {
			$query = $db->query("SELECT username, uid, extcredits$id AS credits FROM {$tablepre}members ORDER BY extcredits$id DESC LIMIT 0, 20");
			while($member = $db->fetch_array($query)) {
				$extendedcredits[$id][] = $member;
			}
		}
		$newstatvars[] = "'creditsrank', 'extendedcredits', '".addslashes(serialize($extendedcredits))."'";
	}

	if(is_array($extendedcredits)) {
		$extcreditfirst = 0;$extcreditkeys = $creditsrank = array();
		foreach($extendedcredits as $key => $extendedcredit) {
			$max = $extendedcredit[0]['credits'];
			!$extcreditfirst && $extcreditfirst = $key;
			$extcreditkeys[] = $key;
			foreach($extendedcredit as $i => $members) {
				@$width = intval(370 * $members['credits'] / $max);
				$width += 2;
				$creditsrank[$key] .= "<tr><td width=\"100\"><a href=\"space.php?uid=$members[uid]\" target=\"_blank\">$members[username]</a></strong></td>\n".
					"<td><div class=\"optionbar\"><div style=\"width: {$width}px\">&nbsp;</div></div>&nbsp; <strong>$members[credits]</strong></td></tr>\n";
			}
		}
		$extcredit = empty($extcredit) || !in_array($extcredit, $extcreditkeys) ? $extcreditfirst : intval($extcredit);
	}

	$lastupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $timeoffset * 3600);
	$nextupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $statscachelife + $timeoffset * 3600);

	updatenewstatvars();
	include template('stats_misc');

} elseif($type == 'onlinetime' && $oltimespan) {

	$onlines = '';

	$statvars = array();
	$query = $db->query("SELECT * FROM {$tablepre}statvars WHERE type='onlines'");
	while($variable = $db->fetch_array($query)) {
		$statvars[$variable['variable']] = $variable['value'];
	}

	if($timestamp - $statvars['lastupdate'] > $statscachelife) {
		$statvars = array('lastupdate' => $timestamp);
		$newstatvars[] = "'onlines', 'lastupdate', '$timestamp'";
	}

	$total = $thismonth = array();

	if(isset($statvars['total'])) {
		$total = unserialize($statvars['total']);
	} else {
		$query = $db->query("SELECT o.uid, m.username, o.total AS time
			FROM {$tablepre}onlinetime o
			LEFT JOIN {$tablepre}members m USING (uid)
			ORDER BY o.total DESC LIMIT 20");
		while($online = $db->fetch_array($query)) {
			$online['time'] = round($online['time'] / 60, 2);
			$total[] = $online;
		}
		$newstatvars[] = "'onlines', 'total', '".addslashes(serialize($total))."'";
	}

	if(isset($statvars['thismonth'])) {
		$thismonth = unserialize($statvars['thismonth']);
	} else {
		$dateline = strtotime(gmdate('Y-n-01', $timestamp));
		$query = $db->query("SELECT o.uid, m.username, o.thismonth AS time
			FROM {$tablepre}onlinetime o, {$tablepre}members m
			WHERE o.uid=m.uid AND m.lastactivity>='$dateline'
			ORDER BY o.thismonth DESC LIMIT 20");
		while($online = $db->fetch_array($query)) {
			$online['time'] = round($online['time'] / 60, 2);
			$thismonth[] = $online;
		}
		$newstatvars[] = "'onlines', 'thismonth', '".addslashes(serialize($thismonth))."'";
	}

	for($i = 0; $i < 20; $i++) {
		$bgclass = $i % 2 ? ' class="colplural"' : '';
		@$onlines .= "<tr".$bgclass."><td class=\"stat_subject\"><a href=\"space.php?uid={$total[$i]['uid']}\" target=\"_blank\">{$total[$i]['username']}</a>&nbsp;</td><td class=\"stat_num\">{$total[$i]['time']}</td>\n".
			"<td class=\"stat_subject\"><a href=\"space.php?uid={$thismonth[$i]['uid']}\" target=\"_blank\">{$thismonth[$i]['username']}</a></td><td class=\"stat_num\">{$thismonth[$i]['time']}</td></tr>\n";
	}

	$lastupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $timeoffset * 3600);
	$nextupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $statscachelife + $timeoffset * 3600);

	updatenewstatvars();
	include template('stats_onlinetime');

} elseif($type == 'team') {

	$statvars = array();
	$query = $db->query("SELECT * FROM {$tablepre}statvars WHERE type='team'");
	while($variable = $db->fetch_array($query)) {
		$statvars[$variable['variable']] = $variable['value'];
	}

	if($timestamp - $statvars['lastupdate'] > $statscachelife) {
		$statvars = array('lastupdate' => $timestamp);
		$newstatvars[] = "'team', 'lastupdate', '$timestamp'";
	}

	$team = array();

	if(isset($statvars['team'])) {
		$team = unserialize($statvars['team']);
	} else {
		$forums = $moderators = $members = $fuptemp = array();
		$categories = array(0 => array('fid' => 0, 'fup' => 0, 'type' => 'group', 'name' => $bbname));

		$uids = 0;
		$query = $db->query("SELECT fid, uid FROM {$tablepre}moderators WHERE inherited='0' ORDER BY displayorder");
		while($moderator = $db->fetch_array($query)) {
			$moderators[$moderator['fid']][] = $moderator['uid'];
			$uids .= ','.$moderator['uid'];
		}

		if($oltimespan) {
			$oltimeadd1 = ', o.thismonth AS thismonthol, o.total AS totalol';
			$oltimeadd2 = "LEFT JOIN {$tablepre}onlinetime o ON o.uid=m.uid";
		} else {
			$oltimeadd1 = $oltimeadd2 = '';
		}

		$totaloffdays = $totalol = $totalthismonthol = 0;
		$query = $db->query("SELECT m.uid, m.username, m.adminid, m.lastactivity, m.credits, m.posts $oltimeadd1
			FROM {$tablepre}members m $oltimeadd2
			WHERE m.uid IN ($uids) OR m.adminid IN (1, 2) ORDER BY m.adminid");

		$admins = array();
		while($member = $db->fetch_array($query)) {
			if($member['adminid'] == 1 || $member['adminid'] == 2) {
				$admins[] = $member['uid'];
			}

			$member['offdays'] = intval(($timestamp - $member['lastactivity']) / 86400);
			$totaloffdays += $member['offdays'];

			if($oltimespan) {
				$member['totalol'] = round($member['totalol'] / 60, 2);
				$member['thismonthol'] = gmdate('Yn', $member['lastactivity']) == gmdate('Yn', $timestamp) ? round($member['thismonthol'] / 60, 2) : 0;
				$totalol += $member['totalol'];
				$totalthismonthol += $member['thismonthol'];
			}

			$members[$member['uid']] = $member;
			$uids .= ','.$member['uid'];
		}

		$totalthismonthposts = 0;
		$query = $db->query("SELECT authorid, COUNT(*) AS posts FROM {$tablepre}posts
			WHERE dateline>=$timestamp-86400*30 AND authorid IN ($uids) AND invisible='0' GROUP BY authorid");
		while($post = $db->fetch_array($query)) {
			$members[$post['authorid']]['thismonthposts'] = $post['posts'];
			$totalthismonthposts += $post['posts'];
		}

		$totalmodposts = $totalmodactions = 0;
		if($modworkstatus) {
			$starttime = gmdate("Y-m-1", $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);
			$query = $db->query("SELECT uid, SUM(count) AS actioncount FROM {$tablepre}modworks
				WHERE dateline>='$starttime' GROUP BY uid");
			while($member = $db->fetch_array($query)) {
				$members[$member['uid']]['modactions'] = $member['actioncount'];
				$totalmodactions += $member['actioncount'];
			}
		}

		$query = $db->query("SELECT fid, fup, type, name, inheritedmod FROM {$tablepre}forums WHERE status='1' ORDER BY type, displayorder");
		while($forum = $db->fetch_array($query)) {
			$forum['moderators'] = count($moderators[$forum['fid']]);
			switch($forum['type']) {
				case 'group':
					$categories[$forum['fid']] = $forum;
					$forums[$forum['fid']][$forum['fid']] = $forum;
					$catfid = $forum['fid'];
					break;
				case 'forum':
					$forums[$forum['fup']][$forum['fid']] = $forum;
					$fuptemp[$forum['fid']] = $forum['fup'];
					$catfid = $forum['fup'];
					break;
				case 'sub':
					$forums[$fuptemp[$forum['fup']]][$forum['fid']] = $forum;
					$catfid = $fuptemp[$forum['fup']];
					break;
			}
			if(!empty($moderators[$forum['fid']])) {
				$categories[$catfid]['moderating'] = 1;
			}
		}

		foreach($categories as $fid => $category) {
			if(empty($category['moderating'])) {
				unset($categories[$fid]);
			}
		}

		$team = array	(
				'categories' => $categories,
				'forums' => $forums,
				'admins' => $admins,
				'moderators' => $moderators,
				'members' => $members,
				'avgoffdays' => @($totaloffdays / count($members)),
				'avgthismonthposts' => @($totalthismonthposts / count($members)),
				'avgtotalol' => @($totalol / count($members)),
				'avgthismonthol' => @($totalthismonthol / count($members)),
				'avgmodactions' => @($totalmodactions / count($members)),
				);

		$newstatvars[] = "'team', 'team', '".addslashes(serialize($team))."'";
	}

	if(is_array($team)) {
		foreach($team['members'] as $uid => $member) {
			@$member['thismonthposts'] = intval($member['thismonthposts']);
			@$team['members'][$uid]['offdays'] = $member['offdays'] > $team['avgoffdays'] ? '<b><i>'.$member['offdays'].'</i></b>' : $member['offdays'];
			@$team['members'][$uid]['thismonthposts'] = $member['thismonthposts'] < $team['avgthismonthposts'] / 2 ? '<b><i>'.$member['thismonthposts'].'</i></b>' : $member['thismonthposts'];
			@$team['members'][$uid]['lastactivity'] = gmdate("$dateformat $timeformat", $member['lastactivity'] + $timeoffset * 3600);
			@$team['members'][$uid]['thismonthol'] = $member['thismonthol'] < $team['avgthismonthol'] / 2 ? '<b><i>'.$member['thismonthol'].'</i></b>' : $member['thismonthol'];
			@$team['members'][$uid]['totalol'] = $member['totalol'] < $team['avgtotalol'] / 2 ? '<b><i>'.$member['totalol'].'</i></b>' : $member['totalol'];
			@$team['members'][$uid]['modposts'] = $member['modposts'] < $team['avgmodposts'] / 2 ? '<b><i>'.intval($member['modposts']).'</i></b>' : intval($member['modposts']);
			@$team['members'][$uid]['modactions'] = $member['modactions'] < $team['avgmodactions'] / 2 ? '<b><i>'.intval($member['modactions']).'</i></b>' : intval($member['modactions']);
		}
	}

	$lastupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $timeoffset * 3600);
	$nextupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $statscachelife + $timeoffset * 3600);

	updatenewstatvars();
	include template('stats_team');

} elseif($type == 'modworks' && $modworkstatus) {

	$before = (isset($before) && $before > 0 && $before <=  $maxmodworksmonths) ? intval($before) : 0 ;

	list($now['year'], $now['month'], $now['day']) = explode("-", gmdate("Y-n-j", $timestamp + $_DCACHE['settings']['timeoffset'] * 3600));

	for($i = 0; $i <= $maxmodworksmonths; $i++) {
		$month = date("Y-m", mktime(0, 0, 0, $now['month'] - $i, 1, $now['year']));
		if($i != $before) {
			$monthlinks[$i] = "<a href=\"stats.php?type=$type&amp;before=$i&amp;uid=$uid\">$month</a>";
		} else {
			$thismonth = $month;
			$starttime = $month.'-01';
			$endtime = date("Y-m-01", mktime(0, 0, 0, $now['month'] - $i + 1 , 1, $now['year']));
			$daysofmonth = date("t", mktime(0, 0, 0, $now['month'] - $i , 1, $now['year']));
			$monthlinks[$i] = "<b>$month</b>";
		}
	}

	$expiretime = date('Y-m', mktime(0, 0, 0, $now['month'] - $maxmodworksmonths - 1, 1, $now['year']));
	$daysofmonth = empty($before) ? $now['day'] : $daysofmonth;

	$mergeactions = array('OPN' => 'CLS', 'ECL' => 'CLS', 'UEC' => 'CLS', 'EOP' => 'CLS', 'UEO' => 'CLS',
		'UDG' => 'DIG', 'EDI' =>'DIG', 'UED' => 'DIG', 'UST' => 'STK', 'EST' => 'STK',	'UES' => 'STK',
		'DLP' => 'DEL',	'PRN' => 'DEL',	'UDL' => 'DEL',	'UHL' => 'HLT',	'EHL' => 'HLT',	'UEH' => 'HLT',
		'SPL' => 'MRG', 'ABL' => 'EDT', 'RBL' => 'EDT');

	if(!empty($uid)) {

		$member = $db->fetch_first("SELECT username FROM {$tablepre}members WHERE uid='$uid' AND adminid>'0'");
		if(!$member) {
			showmessage('undefined_action');
		}

		$modactions = $totalactions = array();
		for($i = 1; $i <= $daysofmonth; $i++) {
			$modactions[sprintf("$thismonth-%02d", $i)] = array();
		}

		$query = $db->query("SELECT * FROM {$tablepre}modworks WHERE uid='$uid' AND dateline>='{$starttime}' AND dateline<'$endtime'");
		while($data = $db->fetch_array($query)) {
			if(isset($mergeactions[$data['modaction']])) {
				$data['modaction'] = $mergeactions[$data['modaction']];
			}
			$modactions[$data['dateline']][$data['modaction']]['count'] += $data['count'];
			$modactions[$data['dateline']][$data['modaction']]['posts'] += $data['posts'];
			$totalactions[$data['modaction']]['count'] += $data['count'];
			$totalactions[$data['modaction']]['posts'] += $data['posts'];
		}

	} else {

		$modworksupdated = false;

		$variable = empty($before) ? 'thismonth' : $starttime;

		$members = $db->fetch_first("SELECT * FROM {$tablepre}statvars WHERE type='modworks' AND variable='$variable'");
		if($members) {
			$members = unserialize($members['value']);
			if( !empty($before) || (($timestamp - $members['lastupdate'] < $statscachelife) && $members['thismonth'] == $starttime)) {
				$modworksupdated = true;
			}
		}

		if($modworksupdated) {

			if(empty($before)) {
				unset($members['lastupdate'], $members['thismonth']);
			}

		} else {

			$members = array();
			$uids = $totalmodactions = 0;

			$query = $db->query("SELECT uid, username, adminid FROM {$tablepre}members WHERE adminid IN (1, 2, 3) ORDER BY adminid, uid");
			while($member = $db->fetch_array($query)) {
				$members[$member['uid']] = $member;
				$uids .= ', '.$member['uid'];
			}

			$query = $db->query("SELECT uid, modaction, SUM(count) AS count, SUM(posts) AS posts
					FROM {$tablepre}modworks
					WHERE uid IN ($uids) AND dateline>='{$starttime}' AND dateline<'$endtime' GROUP BY uid, modaction");

			while($data = $db->fetch_array($query)) {
				if(isset($mergeactions[$data['modaction']])) {
					$data['modaction'] = $mergeactions[$data['modaction']];
				}
				$members[$data['uid']]['total'] += $data['count'];
				$totalmodactioncount += $data['count'];

				$members[$data['uid']][$data['modaction']]['count'] += $data['count'];
				$members[$data['uid']][$data['modaction']]['posts'] += $data['posts'];

			}

			$avgmodactioncount = @($totalmodactioncount / count($members));
			foreach($members as $id => $member) {
				$members[$id]['totalactions'] = intval($members[$id]['totalactions']);
				$members[$id]['username'] = ($members[$id]['total'] < $avgmodactioncount / 2) ? ('<b><i>'.$members[$id]['username'].'</i></b>') : ($members[$id]['username']);
			}

			if(!empty($before)) {
				$db->query("DELETE FROM {$tablepre}statvars WHERE type='modworks' AND variable<'$expiretime'", 'UNBUFFERED');
				$db->query("DELETE FROM {$tablepre}modworks WHERE dateline<'{$expiretime}-01'", 'UNBUFFERED');
				$newstatvars[] = "'modworks', '$starttime', '".addslashes(serialize($members))."'";
			} else {
				$members['thismonth'] = $starttime;
				$members['lastupdate'] = $timestamp;
				$newstatvars[] = "'modworks', 'thismonth', '".addslashes(serialize($members))."'";
				unset($members['lastupdate'], $members['thismonth']);
			}
		}
	}

	include language('modactions');

	$bgarray = array();
	foreach($modactioncode as $key => $val) {
		if(isset($mergeactions[$key])) {
			unset($modactioncode[$key]);
		}
	}

	$tdcols = count($modactioncode) + 1;
	$tdwidth = floor(90 / ($tdcols - 1)).'%';
	updatenewstatvars();
	include template('stats_misc');

} elseif($type == 'trade') {

	$statvars = array();
	$query = $db->query("SELECT * FROM {$tablepre}statvars WHERE type='trade'");
	while($variable = $db->fetch_array($query)) {
		$statvars[$variable['variable']] = $variable['value'];
	}

	if($timestamp - $statvars['lastupdate'] > $statscachelife) {
		$statvars = array('lastupdate' => $timestamp);
		$newstatvars[] = "'trade', 'lastupdate', '$timestamp'";
	}

	if($statvars['tradesum']) {
		$tradesums = unserialize($statvars['tradesum']);
	} else {
		$query = $db->query("SELECT subject, tid, pid, seller, sellerid, sum(tradesum) as tradesum FROM {$tablepre}trades WHERE tradesum>0 GROUP BY sellerid ORDER BY tradesum DESC LIMIT 10");
		while($data = $db->fetch_array($query)) {
			$tradesums[] = $data;
		}
		$newstatvars[] = "'trade', 'tradesums', '".addslashes(serialize($tradesums))."'";
	}
	
	if($statvars['credittradesum']) {
		$credittradesums = unserialize($statvars['credittradesum']);
	} else {
		$query = $db->query("SELECT subject, tid, pid, seller, sellerid, sum(credittradesum) as credittradesum FROM {$tablepre}trades WHERE credittradesum>0 GROUP BY sellerid ORDER BY credittradesum DESC LIMIT 10");
		while($data = $db->fetch_array($query)) {
			$credittradesums[] = $data;
		}
		$newstatvars[] = "'trade', 'credittradesum', '".addslashes(serialize($credittradesums))."'";
	}

	if($statvars['totalitems']) {
		$totalitems = unserialize($statvars['totalitems']);
	} else {
		$query = $db->query("SELECT subject, tid, pid, seller, sellerid, sum(totalitems) as totalitems FROM {$tablepre}trades WHERE totalitems>0 GROUP BY sellerid ORDER BY totalitems DESC LIMIT 10");
		while($data = $db->fetch_array($query)) {
			$totalitems[] = $data;
		}
		$newstatvars[] = "'trade', 'totalitems', '".addslashes(serialize($totalitems))."'";
	}

	$lastupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $timeoffset * 3600);
	$nextupdate = gmdate("$dateformat $timeformat", $statvars['lastupdate'] + $statscachelife + $timeoffset * 3600);

	updatenewstatvars();
	include template('stats_trade');

} else {

	showmessage('undefined_action', NULL, 'HALTED');

}

function statsdata($type, $max, $sortfunc = '') {
	global $barno;

	$statsbar = '';
	$sum = 0;

	$datarray = $GLOBALS["stats_$type"];
	if(is_array($datarray)) {
		if($sortfunc) {
			eval("$sortfunc(\$datarray);");
		}
		foreach($datarray as $count) {
			$sum += $count;
		}
	} else {
		$datarray = array();
	}

	foreach($datarray as $variable => $count) {
		$barno ++;
		switch($type) {
			case $type == 'month' || $type == 'monthposts':
				$variable = substr($variable, 0, 4).'-'.substr($variable, -2);
				break;
			case 'dayposts':
				$variable = substr($variable, 0, 4).'-'.substr($variable, -4, 2).'-'.substr($variable, -2);
				break;
			case 'week':
				include language('misc');
				$variable = $language['week_'.intval($variable)];
				break;
			case 'hour':
				$variable = intval($variable);
				break;
			default:
				$variable = '<img src="images/stats/'.strtolower(str_replace('/', '', $variable)).'.gif" border="0" alt="" /> '.$variable;
				break;
		}
		@$width = intval(370 * $count / $max);
		@$percent = sprintf ("%01.1f", 100 * $count / $sum);
		$width = $width ? $width : '2';
		$variable = $count == $max ? '<strong>'.$variable.'</strong>' : $variable;
		$count = '<div class="optionbar"><div style="width: '.$width.'px">&nbsp;</div></div>&nbsp; <strong>'.$percent.'%</strong> ('.$count.')';
		$statsbar .= "<tr><th width=\"100\">$variable</th><td>$count</td></tr>\n";
	}

	return $statsbar;
}

function updatenewstatvars() {
	global $newstatvars, $db, $tablepre;
	if($newstatvars && $newdata = @implode('),(', $newstatvars)) {
		$db->query("REPLACE INTO {$tablepre}statvars (type, variable, value) VALUES ($newdata)");
	}
	$newstatvars = array();
}
?>