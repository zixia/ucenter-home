<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forumdisplay.php 21337 2010-01-06 08:09:58Z tiger $
*/

define('BINDDOMAIN', 'forumdisplay');

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/forum.func.php';

$discuz_action = 2;

if($forum['redirect']) {
	dheader("Location: $forum[redirect]");
} elseif($forum['type'] == 'group') {
	dheader("Location: {$indexname}?gid=$fid");
} elseif(empty($forum['fid'])) {
	header("HTTP/1.0 404 Not Found");
	showmessage('forum_nonexistence', NULL, 'HALTED');
}

$showoldetails = isset($showoldetails) ? $showoldetails : '';
switch($showoldetails) {
	case 'no': dsetcookie('onlineforum', 0, 86400 * 365); break;
	case 'yes': dsetcookie('onlineforum', 1, 86400 * 365); break;
}

$forum['name'] = strip_tags($forum['name']) ? strip_tags($forum['name']) : $forum['name'];
$forum['extra'] = unserialize($forum['extra']);
if(!is_array($forum['extra'])) {
	$forum['extra'] = array();
}

if($forum['type'] == 'forum') {
	$navigation = '&raquo; '.$forum['name'];
	$navtitle = $forum['name'];
} else {
	$forumup = $_DCACHE['forums'][$forum['fup']]['name'];
	$navigation = '&raquo; <a href="forumdisplay.php?fid='.$forum['fup'].'">'.$forumup.'</a> &raquo; '.$forum['name'];
	$navtitle = $forum['name'].' - '.strip_tags($forumup);
}

$rsshead = $rssstatus ? ('<link rel="alternate" type="application/rss+xml" title="'.$bbname.' - '.$navtitle.'" href="'.$boardurl.'rss.php?fid='.$fid.'&amp;auth='.$rssauth."\" />\n") : '';
$navtitle .= ' - ';
$metakeywords = !$forum['keywords'] ? $forum['name'] : $forum['keywords'];
$metadescription = !$forum['description'] ? $forum['name'] : strip_tags($forum['description']);

if($forum['viewperm'] && !forumperm($forum['viewperm']) && !$forum['allowview']) {
	showmessagenoperm('viewperm', $fid);
} elseif ($forum['formulaperm'] && $adminid != 1) {
	formulaperm($forum['formulaperm']);
}

if($forum['password']) {
	if($action == 'pwverify') {
		if($pw != $forum['password']) {
			showmessage('forum_passwd_incorrect', NULL, 'HALTED');
		} else {
			dsetcookie('fidpw'.$fid, $pw);
			showmessage('forum_passwd_correct', "forumdisplay.php?fid=$fid");
		}
	} elseif($forum['password'] != $_DCOOKIE['fidpw'.$fid]) {
		include template('forumdisplay_passwd');
		exit();
	}
}

$sdb = loadmultiserver();

foreach(array('modarea_c', 'sidebar') as $key) {
	if(!isset($_COOKIE['discuz_collapse']) || strpos($_COOKIE['discuz_collapse'], $key) === FALSE) {
		$collapseimg[$key] = 'collapsed_no';
		$collapse[$key] = '';
	} else {
		$collapseimg[$key] = 'collapsed_yes';
		$collapse[$key] = 'display: none';
	}
}

if($forum['modrecommend'] && $forum['modrecommend']['open']) {
	$forum['recommendlist'] = recommendupdate($fid, $forum['modrecommend'], '', 1);
}

if($forum['modworks']) {
	$reportnum = $allowviewreport ? $db->result_first("SELECT COUNT(*) FROM {$tablepre}reportlog WHERE fid='$fid' AND status='1'") : 0;
	$modnum = $allowmodpost ? ($db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE invisible='-2' AND first='0' and fid='$fid'") +
		$db->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE fid='$fid' AND displayorder='-2'")) : 0;
	$modusernum = $allowmoduser ? $db->result_first("SELECT COUNT(*) FROM {$tablepre}validating WHERE status='0'") : 0;
}

$toptablewidth = $forum['rules'] && $forum['recommendlist'] ? '50%' : '100%';
$infosidestatus[0] = !empty($infosidestatus['f'.$fid][0]) ? $infosidestatus['f'.$fid][0] : $infosidestatus[0];
$infosidestatus['allow'] = $infosidestatus['allow'] && $infosidestatus[0] && $infosidestatus[0] != -1 ? (!$collapse['sidebar'] ? 2 : 1) : 0;

$forum['typemodels'] = $forum['typemodels'] ? unserialize($forum['typemodels']) : array();

$optionadd = $filterurladd = '';
$threadids = array();
if($forum['threadsorts']['defaultshow'] && $forum['threadsorts']['types'] && empty($sortid)) {
	$sortid = $forum['threadsorts']['defaultshow'];
	$filterurladd = '&amp;filter=sort';
}

if($sortid && $forum['threadsorts']['types'][$sortid]) {
	$sortid = intval($sortid);
	include_once DISCUZ_ROOT.'./forumdata/cache/threadsort_'.$sortid.'.php';
	require_once DISCUZ_ROOT.'./include/forumsort.func.php';

	$quicksearchlist = quicksearch();
}

$moderatedby = moddisplay($forum['moderators'], 'forumdisplay');
$highlight = empty($highlight) ? '' : htmlspecialchars($highlight);
if($forum['autoclose']) {
	$closedby = $forum['autoclose'] > 0 ? 'dateline' : 'lastpost';
	$forum['autoclose'] = abs($forum['autoclose']) * 86400;
}

$subexists = 0;
foreach($_DCACHE['forums'] as $sub) {
	if($sub['type'] == 'sub' && $sub['fup'] == $fid && (!$hideprivate || !$sub['viewperm'] || forumperm($sub['viewperm']) || strstr($sub['users'], "\t$discuz_uid\t"))) {
		if(!$sub['status']) {
			continue;
		}
		$subexists = 1;
		$sublist = array();
		$sql = $accessmasks ? "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, ff.description, ff.moderators, ff.icon, ff.viewperm, ff.extra, a.allowview FROM {$tablepre}forums f
						LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid
						LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid
						WHERE fup='$fid' AND status>'0' AND type='sub' ORDER BY f.displayorder"
					: "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, ff.description, ff.moderators, ff.icon, ff.viewperm, ff.extra FROM {$tablepre}forums f
						LEFT JOIN {$tablepre}forumfields ff USING(fid)
						WHERE f.fup='$fid' AND f.status>'0' AND f.type='sub' ORDER BY f.displayorder";
		$query = $sdb->query($sql);
		while($sub = $sdb->fetch_array($query)) {
			$sub['extra'] = unserialize($sub['extra']);
			if(!is_array($sub['extra'])) {
				$sub['extra'] = array();
			}
			if(forum($sub)) {
				$sub['orderid'] = count($sublist);
				$sublist[] = $sub;
			}
		}
		break;
	}
}

if($subexists) {
	if($forum['forumcolumns']) {
		$forum['forumcolwidth'] = floor(100 / $forum['forumcolumns']).'%';
		$forum['subscount'] = count($sublist);
		$forum['endrows'] = '';
		if($colspan = $forum['subscount'] % $forum['forumcolumns']) {
			while(($forum['forumcolumns'] - $colspan) > 0) {
				$forum['endrows'] .= '<td>&nbsp;</td>';
				$colspan ++;
			}
			$forum['endrows'] .= '</tr>';
		}
	}
	if(empty($_COOKIE['discuz_collapse']) || strpos($_COOKIE['discuz_collapse'], 'subforum_'.$fid) === FALSE) {
		$collapse['subforum'] = '';
		$collapseimg['subforum'] = 'collapsed_no.gif';
	} else {
		$collapse['subforum'] = 'display: none';
		$collapseimg['subforum'] = 'collapsed_yes.gif';
	}
}

if($forum['simple'] & 1) {
	$forummenu = '';
	if($forumjump) {
		$forummenu = forumselect(FALSE, 1);
	}
	include template('forumdisplay_simple');
	exit();
}

$page = isset($page) ? max(1, intval($page)) : 1;
$page = $threadmaxpages && $page > $threadmaxpages ? 1 : $page;
$start_limit = ($page - 1) * $tpp;

if($page == 1) {
	if($_DCACHE['announcements_forum']) {
		$announcement = $_DCACHE['announcements_forum'];
		$announcement['starttime'] = gmdate($dateformat, $announcement['starttime'] + ($timeoffset * 3600));
	} else {
		$announcement = NULL;
	}
}

$forumdisplayadd = $filteradd = $sortadd = $typeadd = $sorturladd = '';
$sorturladd = $selectadd = array();
$specialtype = array('poll' => 1, 'trade' => 2, 'reward' => 3, 'activity' => 4, 'debate' => 5);
isset($orderby) && in_array($orderby, array('lastpost', 'dateline', 'replies', 'views', 'recommends', 'heats')) ? $forumdisplayadd .= "&amp;orderby=$orderby" : $orderby = $_DCACHE['forums'][$fid]['orderby'] ? $_DCACHE['forums'][$fid]['orderby'] : 'lastpost';
isset($ascdesc) && in_array($ascdesc, array('ASC', 'DESC')) ? $forumdisplayadd .= "&amp;ascdesc=$ascdesc" : $ascdesc = $_DCACHE['forums'][$fid]['ascdesc'] ? $_DCACHE['forums'][$fid]['ascdesc'] : 'DESC';

if(isset($filter)) {
	if($filter == 'digest') {
		$forumdisplayadd .= '&amp;filter=digest';
		$filteradd = "AND digest>'0'";
	} elseif($recommendthread['status'] && $filter == 'recommend') {
		$forumdisplayadd .= '&amp;filter=recommend';
		$filteradd = "AND recommends>'".intval($recommendthread['iconlevels'][0])."'";
	} elseif($filter == 'type' && $forum['threadtypes']['listable'] && $typeid && isset($forum['threadtypes']['types'][$typeid])) {
		$forumdisplayadd .= "&amp;filter=type&amp;typeid=$typeid";
		$typeadd = "&amp;typeid=$typeid";
		$filteradd = "AND typeid='$typeid'";
		if($sortid) {
			$filteradd .= "AND sortid='$sortid'";
			$forumdisplayadd .= $sortadd = "&amp;sortid=$sortid";
		}
	} elseif($filter == 'sort' && $sortid && isset($forum['threadsorts']['types'][$sortid])) {
		$forumdisplayadd .= "&amp;filter=sort&amp;sortid=$sortid";
		$sortadd = "&amp;sortid=$sortid";
		$filteradd = "AND sortid='$sortid'";
		if($typeid) {
			$filteradd .= "AND typeid='$typeid'";
			$forumdisplayadd .= $typeadd = "&amp;typeid=$typeid";
		}
		$query_string = daddslashes($_SERVER['QUERY_STRING'], 1);
		if($query_string && $quicksearchlist['option']) {
			$query_string = substr($query_string, (strpos($query_string, "&") + 1));
			parse_str($query_string, $selectadd);
			if($selectadd && is_array($selectadd)) {
				$and = '';
				foreach($quicksearchlist['option'] as $option) {
					$identifier = $option['identifier'];
					foreach($selectadd as $option => $value) {
						$sorturladd[$identifier] .= !in_array($option, array('filter', 'sortid', $identifier)) ? "$and$option=$value" : '';
						$and = '&';
					}
				}
			}
		}
	} elseif($filter == 'special' && array_key_exists($extraid, $threadplugins)) {
		$forumdisplayadd .= "&amp;filter=special&amp;extraid=$extraid";
		$filteradd = "AND iconid='{$threadplugins[$extraid][iconid]}'";
	} elseif(preg_match("/^\d+$/", $filter)) {
		$forumdisplayadd .= "&amp;filter=$filter";
		$filteradd = $filter ? "AND lastpost>='".($timestamp - $filter)."'" : '';
		$orderby = $orderby != 'recommends' ? $orderby : 'heats';
	} elseif(isset($specialtype[$filter])) {
		$forumdisplayadd .= "&amp;filter=$filter";
		$filteradd = "AND special='$specialtype[$filter]'";
	} elseif($orderby == 'lastpost') {
		$filter = '';
	} else {
		$filter = 2592000;
		$forumdisplayadd .= "&amp;filter=2592000";
		$filteradd = "AND lastpost>='".($timestamp - 2592000)."'";
	}
} else {
	$filter = '';
}

$check = array();
$check[$filter] = $check[$orderby] = $check[$ascdesc] = 'selected="selected"';

if($whosonlinestatus == 2 || $whosonlinestatus == 3) {
	$whosonlinestatus = 1;
	$onlineinfo = explode("\t", $onlinerecord);
	$detailstatus = $showoldetails == 'yes' || (((!isset($_DCOOKIE['onlineforum']) && !$whosonline_contract) || $_DCOOKIE['onlineforum']) && $onlineinfo[0] < 500 && !$showoldetails);

	if($detailstatus) {
		updatesession();
		@include language('actions');
		$whosonline = array();
		$forumname = strip_tags($forum['name']);
		$guestwhere = isset($_DCACHE['onlinelist'][7]) ? '' : "uid>'0' AND";

		$query = $db->query("SELECT uid, groupid, username, invisible, lastactivity, action FROM {$tablepre}sessions WHERE $guestwhere fid='$fid' AND invisible='0'");
		if($db->num_rows($query)) {
			$whosonlinestatus = 1;
			while($online = $db->fetch_array($query)) {
				if($online['uid']) {
					$online['icon'] = isset($_DCACHE['onlinelist'][$online['groupid']]) ? $_DCACHE['onlinelist'][$online['groupid']] : $_DCACHE['onlinelist'][0];
				} else {
					$online['icon'] = $_DCACHE['onlinelist'][7];
					$online['username'] = $_DCACHE['onlinelist']['guest'];
				}
				$online['action'] = $actioncode[$online['action']];
				$online['lastactivity'] = gmdate($timeformat, $online['lastactivity'] + ($timeoffset * 3600));
				$whosonline[] = $online;
			}
		}
		unset($online);
	}
} else {
	$whosonlinestatus = 0;
}

if(empty($filter) && empty($sortid)) {
	$threadcount = $forum['threads'];
} else {
	$threadcount = $sdb->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE fid='$fid' $filteradd AND displayorder>='0'");
}
$thisgid = $forum['type'] == 'forum' ? $forum['fup'] : $_DCACHE['forums'][$forum['fup']]['fup'];
if($globalstick && $forum['allowglobalstick']) {
	$stickytids = $_DCACHE['globalstick']['global']['tids'].(empty($_DCACHE['globalstick']['categories'][$thisgid]['count']) ? '' : ','.$_DCACHE['globalstick']['categories'][$thisgid]['tids']);
	$forumstickytids = array();
	$_DCACHE['forumstick'][$fid] = is_array($_DCACHE['forumstick'][$fid]) ? $_DCACHE['forumstick'][$fid] : array();
	$forumstickycount = count($_DCACHE['forumstick'][$fid]);
	foreach($_DCACHE['forumstick'][$fid] as $forumstickthread) {
		$forumstickytids[] = $forumstickthread['tid'];
	}
	if(!empty($forumstickytids)) {
		$forumstickytids = implodeids($forumstickytids);
		$stickytids .= ", $forumstickytids";
	}
	
	$stickytids = trim($stickytids, ', ');
	if ($stickytids === ''){
		$stickytids = '0';
	}

	$stickycount = $_DCACHE['globalstick']['global']['count'] + $_DCACHE['globalstick']['categories'][$thisgid]['count'] + $forumstickycount;
} else {
	$forumstickycount = $stickycount = $stickytids = 0;
}

$filterbool = !empty($filter) && in_array($filter, array('digest', 'recommend', 'type', 'activity', 'poll', 'trade', 'reward', 'debate'));
$threadcount += $filterbool ? 0 : $stickycount;
$multipage = multi($threadcount, $tpp, $page, "forumdisplay.php?fid=$fid$forumdisplayadd", $threadmaxpages);
$extra = rawurlencode("page=$page$forumdisplayadd");

$separatepos = 0;
$threadlist = $threadids = array();
$colorarray = array('', '#EE1B2E', '#EE5023', '#996600', '#3C9D40', '#2897C5', '#2B65B7', '#8F2A90', '#EC1282');

$displayorderadd = !$filterbool && $stickycount ? 't.displayorder IN (0, 1)' : 't.displayorder IN (0, 1, 2, 3, 4)';

if(($start_limit && $start_limit > $stickycount) || !$stickycount || $filterbool) {

	$querysticky = '';
	$query = $sdb->query("SELECT t.* FROM {$tablepre}threads t
		WHERE t.fid='$fid' $filteradd AND $displayorderadd
		ORDER BY t.displayorder DESC, t.$orderby $ascdesc
		LIMIT ".($filterbool ? $start_limit : $start_limit - $stickycount).", $tpp");

} else {

	$querysticky = $sdb->query("SELECT t.* FROM {$tablepre}threads t
		WHERE t.tid IN ($stickytids) AND t.displayorder IN (2, 3, 4)
		ORDER BY displayorder DESC, $orderby $ascdesc
		LIMIT $start_limit, ".($stickycount - $start_limit < $tpp ? $stickycount - $start_limit : $tpp));

	if($tpp - $stickycount + $start_limit > 0) {
		$query = $sdb->query("SELECT t.* FROM {$tablepre}threads t
			WHERE t.fid='$fid' $filteradd AND $displayorderadd
			ORDER BY displayorder DESC, $orderby $ascdesc
			LIMIT ".($tpp - $stickycount + $start_limit));
	} else {
		$query = '';
	}

}

$ppp = $forum['threadcaches'] && !$discuz_uid ? $_DCACHE['settings']['postperpage'] : $ppp;

while(($querysticky && $thread = $sdb->fetch_array($querysticky)) || ($query && $thread = $sdb->fetch_array($query))) {
	if($thread['special'] != 127) {
		$thread['icon'] = isset($_DCACHE['icons'][$thread['iconid']]) ? '<img src="images/icons/'.$_DCACHE['icons'][$thread['iconid']].'" class="icon" />' : '&nbsp;';
	} else {
		$thread['icon'] = '<a href="forumdisplay.php?fid='.$fid.'&amp;filter=special&extraid='.$specialicon[$thread['iconid']].'"><img src="'.$threadplugins[$specialicon[$thread['iconid']]]['icon'].'" alt="'.$threadplugins[$specialicon[$thread['iconid']]]['name'].'" class="icon" /></a>';
	}
	$thread['lastposterenc'] = rawurlencode($thread['lastposter']);

	$thread['typeid'] = $thread['typeid'] && !empty($forum['threadtypes']['prefix']) && isset($forum['threadtypes']['types'][$thread['typeid']]) ?
		'<em>[<a href="forumdisplay.php?fid='.$fid.'&amp;filter=type&amp;typeid='.$thread['typeid'].'">'.$forum['threadtypes']['types'][$thread['typeid']].'</a>]</em>' : '';

	$thread['sortid'] = $thread['sortid'] && !empty($forum['threadsorts']['prefix']) && isset($forum['threadsorts']['types'][$thread['sortid']]) ?
		'<em>[<a href="forumdisplay.php?fid='.$fid.'&amp;filter=sort&amp;sortid='.$thread['sortid'].'">'.$forum['threadsorts']['types'][$thread['sortid']].'</a>]</em>' : '';
	$thread['multipage'] = '';
	$topicposts = $thread['special'] ? $thread['replies'] : $thread['replies'] + 1;
	$thread['special'] == 3 && $thread['price'] < 0 && $thread['replies']--;
	if($topicposts > $ppp) {
		$pagelinks = '';
		$thread['pages'] = ceil($topicposts / $ppp);
		for($i = 2; $i <= 6 && $i <= $thread['pages']; $i++) {
			$pagelinks .= "<a href=\"viewthread.php?tid=$thread[tid]&amp;extra=$extra&amp;page=$i\">$i</a>";
		}
		if($thread['pages'] > 6) {
			$pagelinks .= "..<a href=\"viewthread.php?tid=$thread[tid]&amp;extra=$extra&amp;page=$thread[pages]\">$thread[pages]</a>";
		}
		$thread['multipage'] = '&nbsp;...'.$pagelinks;
	}

	if($thread['highlight']) {
		$string = sprintf('%02d', $thread['highlight']);
		$stylestr = sprintf('%03b', $string[0]);

		$thread['highlight'] = ' style="';
		$thread['highlight'] .= $stylestr[0] ? 'font-weight: bold;' : '';
		$thread['highlight'] .= $stylestr[1] ? 'font-style: italic;' : '';
		$thread['highlight'] .= $stylestr[2] ? 'text-decoration: underline;' : '';
		$thread['highlight'] .= $string[1] ? 'color: '.$colorarray[$string[1]] : '';
		$thread['highlight'] .= '"';
	} else {
		$thread['highlight'] = '';
	}

	if($recommendthread['status'] && $thread['recommends']) {
		foreach($recommendthread['iconlevels'] as $k => $i) {
			if($thread['recommends'] > $i) {
				$thread['recommendicon'] = $k+1;
				break;
			}
		}
	} else {
		$thread['recommendicon'] = '';
	}

	$thread['moved'] = $thread['heatlevel'] = 0;
	if($thread['closed'] || ($forum['autoclose'] && $timestamp - $thread[$closedby] > $forum['autoclose'])) {
		$thread['new'] = 0;
		if($thread['closed'] > 1) {
			$thread['moved'] = $thread['tid'];
			$thread['tid'] = $thread['closed'];
			$thread['replies'] = '-';
			$thread['views'] = '-';
		}
		$thread['folder'] = 'lock';
	} else {
		$thread['folder'] = 'common';
		if($lastvisit < $thread['lastpost'] && (empty($_DCOOKIE['oldtopics']) || strpos($_DCOOKIE['oldtopics'], 'D'.$thread['tid'].'D') === FALSE)) {
			$thread['new'] = 1;
			$thread['folder'] = 'new';
		} else {
			$thread['new'] = 0;
		}
		if($thread['replies'] > $thread['views']) {
			$thread['views'] = $thread['replies'];
		}
		if($heatthread['iconlevels']) {
			foreach($heatthread['iconlevels'] as $k => $i) {
				if($thread['heats'] > $i) {
					$thread['heatlevel'] = $k + 1;
					break;
				}
			}
		}
	}

	$thread['dateline'] = gmdate($dateformat, $thread['dateline'] + $timeoffset * 3600);
	$thread['lastpost'] = dgmdate("$dateformat $timeformat", $thread['lastpost'] + $timeoffset * 3600);

	if(in_array($thread['displayorder'], array(1, 2, 3, 4))) {
		$thread['id'] = 'stickthread_'.$thread['tid'];
		$separatepos++;
	} else {
		$thread['id'] = 'normalthread_'.$thread['tid'];
	}

	$threadids[] = $thread['tid'];
	$threadlist[] = $thread;

}

if($sortid && $forum['threadsorts']['types'][$sortid]) {
	$sortlistarray = sortshowlist($searchoid, $searchvid, $threadids, $searchoption, $selectadd);
	$stemplate = $sortlistarray['stemplate'] ? $sortlistarray['stemplate'] : '';
	$threadlist = $sortlistarray['thread']['list'] ? $sortlistarray['thread']['list'] : $threadlist;
	$threadcount = !empty($sortlistarray['thread']['count']) ? $sortlistarray['thread']['count'] : $threadcount;
	$multipage = $sortlistarray['thread']['multipage'] ? $sortlistarray['thread']['multipage'] : $multipage;
	$sortthreadlist = $sortlistarray['sortthreadlist'] ? $sortlistarray['sortthreadlist'] : array();
}

$separatepos = $separatepos ? $separatepos + 1 : ($announcement ? 1 : 0);

$visitedforums = $visitedforums ? visitedforums() : '';
$forummenu = '';

$usesigcheck = $discuz_uid && $sigstatus ? 'checked="checked"' : '';
$allowpost = (!$forum['postperm'] && $allowpost) || ($forum['postperm'] && forumperm($forum['postperm'])) || ($forum['allowpost'] == 1 && $allowpost);
$fastpost = $fastpost && !$forum['allowspecialonly'];
$allowpost = $forum['allowpost'] != -1 ? $allowpost : false;
$addfeedcheck = $customaddfeed & 1 ? 'checked="checked"': '';

$showpoll = $showtrade = $showreward = $showactivity = $showdebate = 0;
if($forum['allowpostspecial']) {
	$showpoll = $forum['allowpostspecial'] & 1;
	$showtrade = $forum['allowpostspecial'] & 2;
	$showreward = isset($extcredits[$creditstransextra[2]]) && ($forum['allowpostspecial'] & 4);
	$showactivity = $forum['allowpostspecial'] & 8;
	$showdebate = $forum['allowpostspecial'] & 16;
}

if($allowpost) {
	$allowpostpoll = $allowpostpoll && $showpoll;
	$allowposttrade = $allowposttrade && $showtrade;
	$allowpostreward = $allowpostreward && $showreward;
	$allowpostactivity = $allowpostactivity && $showactivity;
	$allowpostdebate = $allowpostdebate && $showdebate;
}

$forum['threadplugin'] = $allowpost && $threadplugins ? unserialize($forum['threadplugin']) : array();

if($forumjump) {
	$forummenu = forumselect(FALSE, 1);
}

$template = $sortid ? 'forumdisplay_sort' : 'forumdisplay';

include template($template);

?>