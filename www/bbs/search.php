<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: search.php 20900 2009-10-29 02:49:38Z tiger $
*/

define('NOROBOT', TRUE);
define('CURSCRIPT', 'search');

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/forum.func.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_icons.php';

$discuz_action = 111;

$cachelife_time = 300;		// Life span for cache of searching in specified range of time
$cachelife_text = 3600;		// Life span for cache of text searching

$sdb = loadmultiserver('search');

$srchtype = empty($srchtype) ? '' : trim($srchtype);
$checkarray = array('posts' => '', 'trade' => '', 'qihoo' => '', 'threadsort' => '');

$searchid = isset($searchid) ? intval($searchid) : 0;

if($srchtype == 'trade' || $srchtype == 'threadsort' || $srchtype == 'qihoo') {
	$checkarray[$srchtype] = 'checked';
} elseif($srchtype == 'title' || $srchtype == 'fulltext') {
	$checkarray['posts'] = 'checked';
} else {
	$srchtype = '';
	$checkarray['posts'] = 'checked';
}

$keyword = isset($srchtxt) ? htmlspecialchars(trim($srchtxt)) : '';

$threadsorts = '';
if($srchtype == 'threadsort') {
	$query = $db->query("SELECT * FROM {$tablepre}threadtypes WHERE special='1' ORDER BY displayorder");
	while($type = $db->fetch_array($query)) {
		$threadsorts .= '<option value="'.$type['typeid'].'" '.($type['typeid'] == intval($sortid) ? 'selected=selected' : '').'>'.$type['name'].'</option>';
	}
}

$forumselect = forumselect('', '', '', TRUE);
if(!empty($srchfid) && !is_numeric($srchfid)) {
	$forumselect = str_replace('<option value="'.$srchfid.'">', '<option value="'.$srchfid.'" selected="selected">', $forumselect);
}

$disabled = array();
$disabled['title'] = !$allowsearch ? 'disabled' : '';
$disabled['fulltext'] = $allowsearch != 2 ? 'disabled' : '';

if(!submitcheck('searchsubmit', 1)) {

	include template('search');

} else {

	if($srchtype == 'qihoo') {

		require DISCUZ_ROOT.'./include/search_qihoo.inc.php';
		exit();

	} elseif(!$allowsearch) {

		showmessage('group_nopermission', NULL, 'NOPERM');

	} elseif($srchtype == 'trade') {

		require DISCUZ_ROOT.'./include/search_trade.inc.php';
		exit;

	} elseif($srchtype == 'threadsort' && $sortid) {

		require DISCUZ_ROOT.'./include/search_sort.inc.php';
		exit;

	}

	$orderby = in_array($orderby, array('dateline', 'replies', 'views')) ? $orderby : 'lastpost';
	$ascdesc = isset($ascdesc) && $ascdesc == 'asc' ? 'asc' : 'desc';

	if(!empty($searchid)) {

		require_once DISCUZ_ROOT.'./include/misc.func.php';

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$index = $sdb->fetch_first("SELECT searchstring, keywords, threads, tids FROM {$tablepre}searchindex WHERE searchid='$searchid'");
		if(!$index) {
			showmessage('search_id_invalid');
		}

		$keyword = htmlspecialchars($index['keywords']);
		$keyword = $keyword != '' ? str_replace('+', ' ', $keyword) : '';

		$index['keywords'] = rawurlencode($index['keywords']);
		$index['searchtype'] = preg_replace("/^([a-z]+)\|.*/", "\\1", $index['searchstring']);

		$threadlist = array();
		$query = $sdb->query("SELECT * FROM {$tablepre}threads WHERE tid IN ($index[tids]) AND displayorder>='0' ORDER BY $orderby $ascdesc LIMIT $start_limit, $tpp");
		while($thread = $sdb->fetch_array($query)) {
			$threadlist[] = procthread($thread);
		}

		$multipage = multi($index['threads'], $tpp, $page, "search.php?searchid=$searchid&orderby=$orderby&ascdesc=$ascdesc&searchsubmit=yes");

		$url_forward = 'search.php?'.$_SERVER['QUERY_STRING'];

		if($prompts['newbietask'] && $newbietaskid && $newbietasks[$newbietaskid]['scriptname'] == 'search'){
			require_once DISCUZ_ROOT.'./include/task.func.php';
			task_newbie_complete();
		}

		include template('search');

	} else {

		!($exempt & 2) && checklowerlimit($creditspolicy['search'], -1);

		$srchuname = isset($srchuname) ? trim($srchuname) : '';

		if($allowsearch == 2 && $srchtype == 'fulltext') {
			periodscheck('searchbanperiods');
		} elseif($srchtype != 'title') {
			$srchtype = 'title';
		}

		$forumsarray = array();
		if(!empty($srchfid)) {
			foreach((is_array($srchfid) ? $srchfid : explode('_', $srchfid)) as $forum) {
				if($forum = intval(trim($forum))) {
					$forumsarray[] = $forum;
				}
			}
		}

		$fids = $comma = '';
		foreach($_DCACHE['forums'] as $fid => $forum) {
			if($forum['type'] != 'group' && (!$forum['viewperm'] && $readaccess) || ($forum['viewperm'] && forumperm($forum['viewperm']))) {
				if(!$forumsarray || in_array($fid, $forumsarray)) {
					$fids .= "$comma'$fid'";
					$comma = ',';
				}
			}
		}

		if($threadplugins && $specialplugin) {
			$specialpluginstr = implode("','", $specialplugin);
			$special[] = 127;
		} else {
			$specialpluginstr = '';
		}
		$specials = $special ? implode(',', $special) : '';
		$srchfilter = in_array($srchfilter, array('all', 'digest', 'top')) ? $srchfilter : 'all';

		$searchstring = $srchtype.'|'.addslashes($srchtxt).'|'.intval($srchuid).'|'.$srchuname.'|'.addslashes($fids).'|'.intval($srchfrom).'|'.intval($before).'|'.$srchfilter.'|'.$specials.'|'.$specialpluginstr;
		$searchindex = array('id' => 0, 'dateline' => '0');

		$query = $sdb->query("SELECT searchid, dateline,
			('$searchctrl'<>'0' AND ".(empty($discuz_uid) ? "useip='$onlineip'" : "uid='$discuz_uid'")." AND $timestamp-dateline<$searchctrl) AS flood,
			(searchstring='$searchstring' AND expiration>'$timestamp') AS indexvalid
			FROM {$tablepre}searchindex
			WHERE ('$searchctrl'<>'0' AND ".(empty($discuz_uid) ? "useip='$onlineip'" : "uid='$discuz_uid'")." AND $timestamp-dateline<$searchctrl) OR (searchstring='$searchstring' AND expiration>'$timestamp')
			ORDER BY flood");

		while($index = $sdb->fetch_array($query)) {
			if($index['indexvalid'] && $index['dateline'] > $searchindex['dateline']) {
				$searchindex = array('id' => $index['searchid'], 'dateline' => $index['dateline']);
				break;
			} elseif($adminid != '1' && $index['flood']) {
				showmessage('search_ctrl', 'search.php');
			}
		}

		if($searchindex['id']) {

			$searchid = $searchindex['id'];

		} else {

			if(!$srchtxt && !$srchuid && !$srchuname && !$srchfrom && !in_array($srchfilter, array('digest', 'top')) && !is_array($special)) {
				showmessage('search_invalid', 'search.php');
			} elseif(isset($srchfid) && $srchfid != 'all' && !(is_array($srchfid) && in_array('all', $srchfid)) && empty($forumsarray)) {
				showmessage('search_forum_invalid', 'search.php');
			} elseif(!$fids) {
				showmessage('group_nopermission', NULL, 'NOPERM');
			}

			if($adminid != '1' && $maxspm) {
				if(($sdb->result_first("SELECT COUNT(*) FROM {$tablepre}searchindex WHERE dateline>'$timestamp'-60")) >= $maxspm) {
					showmessage('search_toomany', 'search.php');
				}
			}

			$digestltd = $srchfilter == 'digest' ? "t.digest>'0' AND" : '';
			$topltd = $srchfilter == 'top' ? "AND t.displayorder>'0'" : "AND t.displayorder>='0'";

			if(!empty($srchfrom) && empty($srchtxt) && empty($srchuid) && empty($srchuname)) {

				$searchfrom = $before ? '<=' : '>=';
				$searchfrom .= $timestamp - $srchfrom;
				$sqlsrch = "FROM {$tablepre}threads t WHERE $digestltd t.fid IN ($fids) $topltd AND t.lastpost$searchfrom";
				$expiration = $timestamp + $cachelife_time;
				$keywords = '';

			} else {

				$sqlsrch = $srchtype == 'fulltext' ?
				"FROM {$tablepre}posts p, {$tablepre}threads t WHERE $digestltd t.fid IN ($fids) $topltd AND p.tid=t.tid AND p.invisible='0'" :
				"FROM {$tablepre}threads t WHERE $digestltd t.fid IN ($fids) $topltd";

				if($srchuname) {
					$srchuid = $comma = '';
					$srchuname = str_replace('*', '%', addcslashes($srchuname, '%_'));
					$query = $db->query("SELECT uid FROM {$tablepre}members WHERE username LIKE '".str_replace('_', '\_', $srchuname)."' LIMIT 50");
					while($member = $db->fetch_array($query)) {
						$srchuid .= "$comma'$member[uid]'";
						$comma = ', ';
					}
					if(!$srchuid) {
						$sqlsrch .= ' AND 0';
					}
				} elseif($srchuid) {
					$srchuid = "'$srchuid'";
				}

				if($srchtxt) {
					if(preg_match("(AND|\+|&|\s)", $srchtxt) && !preg_match("(OR|\|)", $srchtxt)) {
						$andor = ' AND ';
						$sqltxtsrch = '1';
						$srchtxt = preg_replace("/( AND |&| )/is", "+", $srchtxt);
					} else {
						$andor = ' OR ';
						$sqltxtsrch = '0';
						$srchtxt = preg_replace("/( OR |\|)/is", "+", $srchtxt);
					}
					$srchtxt = str_replace('*', '%', addcslashes($srchtxt, '%_'));
					foreach(explode('+', $srchtxt) as $text) {
						$text = trim($text);
						if($text) {
							$sqltxtsrch .= $andor;
							$sqltxtsrch .= $srchtype == 'fulltext' ? "(p.message LIKE '%".str_replace('_', '\_', $text)."%' OR p.subject LIKE '%$text%')" : "t.subject LIKE '%$text%'";
						}
					}
					$sqlsrch .= " AND ($sqltxtsrch)";
				}

				if($srchuid) {
					$sqlsrch .= ' AND '.($srchtype == 'fulltext' ? 'p' : 't').".authorid IN ($srchuid)";
				}

				if(!empty($srchfrom)) {
					$searchfrom = ($before ? '<=' : '>=').($timestamp - $srchfrom);
					$sqlsrch .= " AND t.lastpost$searchfrom";
				}

				if(!empty($specials)) {
					$sqlsrch .=  " AND special IN (".implodeids($special).")";
				}

				if(!empty($specialpluginstr)) {
					$sqlsrch .=  " AND iconid IN (".implodeids($specialplugin).")";
				}

				$keywords = str_replace('%', '+', $srchtxt).(trim($srchuname) ? '+'.str_replace('%', '+', $srchuname) : '');
				$expiration = $timestamp + $cachelife_text;

			}

			$threads = $tids = 0;
			$maxsearchresults = $maxsearchresults ? intval($maxsearchresults) : 500;
			$query = $sdb->query("SELECT ".($srchtype == 'fulltext' ? 'DISTINCT' : '')." t.tid, t.closed, t.author $sqlsrch ORDER BY tid DESC LIMIT $maxsearchresults");
			while($thread = $sdb->fetch_array($query)) {
				if($thread['closed'] <= 1 && $thread['author']) {
					$tids .= ','.$thread['tid'];
					$threads++;
				}
			}
			$db->free_result($query);

			$db->query("INSERT INTO {$tablepre}searchindex (keywords, searchstring, useip, uid, dateline, expiration, threads, tids)
					VALUES ('$keywords', '$searchstring', '$onlineip', '$discuz_uid', '$timestamp', '$expiration', '$threads', '$tids')");
			$searchid = $db->insert_id();

			!($exempt & 2) && updatecredits($discuz_uid, $creditspolicy['search'], -1);

		}

		showmessage('search_redirect', "search.php?searchid=$searchid&orderby=$orderby&ascdesc=$ascdesc&searchsubmit=yes");

	}

}

?>