<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: search_trade.inc.php 17492 2008-12-31 01:39:40Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$orderby = in_array($orderby, array('dateline', 'price', 'expiration')) ? $orderby : 'dateline';
$ascdesc = isset($ascdesc) && $ascdesc == 'asc' ? 'asc' : 'desc';

if(!empty($searchid)) {

	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $tpp;

	$index = $db->fetch_first("SELECT searchstring, keywords, threads, tids FROM {$tablepre}searchindex WHERE searchid='$searchid'");
	if(!$index) {
		showmessage('search_id_invalid');
	}
	$index['keywords'] = rawurlencode($index['keywords']);
	$index['searchtype'] = preg_replace("/^([a-z]+)\|.*/", "\\1", $index['searchstring']);

	$threadlist = $tradelist = array();

	$query = $db->query("SELECT * FROM {$tablepre}trades WHERE pid IN ($index[tids]) ORDER BY $orderby $ascdesc LIMIT $start_limit, $tpp");
	while($tradethread = $db->fetch_array($query)) {
		$tradethread['lastupdate'] = dgmdate("$dateformat $timeformat", $tradethread['lastupdate'] + $timeoffset * 3600);
		$tradethread['lastbuyer'] = rawurlencode($tradethread['lastbuyer']);
		if($tradethread['expiration']) {
			$tradethread['expiration'] = ($tradethread['expiration'] - $timestamp) / 86400;
			if($tradethread['expiration'] > 0) {
				$tradethread['expirationhour'] = floor(($tradethread['expiration'] - floor($tradethread['expiration'])) * 24);
				$tradethread['expiration'] = floor($tradethread['expiration']);
			} else {
				$tradethread['expiration'] = -1;
			}
		}
		$tradelist[] = $tradethread;
	}

	$multipage = multi($index['threads'], $tpp, $page, "search.php?searchid=$searchid".($orderby ? "&orderby=$orderby" : '')."&srchtype=trade&searchsubmit=yes");

	$url_forward = 'search.php?'.$_SERVER['QUERY_STRING'];

	include template('search_trade');

} else {

	!($exempt & 2) && checklowerlimit($creditspolicy['search'], -1);

	$srchtxt = isset($srchtxt) ? trim($srchtxt) : '';
	$srchuname = isset($srchuname) ? trim($srchuname) : '';

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

	$srchfilter = in_array($srchfilter, array('all', 'digest', 'top')) ? $srchfilter : 'all';

	$searchstring = 'trade|'.addslashes($srchtxt).'|'.intval($srchtypeid).'|'.intval($srchuid).'|'.$srchuname.'|'.addslashes($fids).'|'.intval($srchfrom).'|'.intval($before).'|'.$srchfilter;
	$searchindex = array('id' => 0, 'dateline' => '0');

	$query = $db->query("SELECT searchid, dateline,
		('$searchctrl'<>'0' AND ".(empty($discuz_uid) ? "useip='$onlineip'" : "uid='$discuz_uid'")." AND $timestamp-dateline<$searchctrl) AS flood,
		(searchstring='$searchstring' AND expiration>'$timestamp') AS indexvalid
		FROM {$tablepre}searchindex
		WHERE ('$searchctrl'<>'0' AND ".(empty($discuz_uid) ? "useip='$onlineip'" : "uid='$discuz_uid'")." AND $timestamp-dateline<$searchctrl) OR (searchstring='$searchstring' AND expiration>'$timestamp')
		ORDER BY flood");

	while($index = $db->fetch_array($query)) {
		if($index['indexvalid'] && $index['dateline'] > $searchindex['dateline']) {
			$searchindex = array('id' => $index['searchid'], 'dateline' => $index['dateline']);
			break;
		} elseif($index['flood']) {
			showmessage('search_ctrl', 'search.php');
		}
	}

	if($searchindex['id']) {

		$searchid = $searchindex['id'];

	} else {

		if(!$srchtxt && !$srchtypeid && !$srchuid && !$srchuname && !$srchfrom && !in_array($srchfilter, array('digest', 'top'))) {
			showmessage('search_invalid', 'search.php');
		} elseif(isset($srchfid) && $srchfid != 'all' && !(is_array($srchfid) && in_array('all', $srchfid)) && empty($forumsarray)) {
			showmessage('search_forum_invalid', 'search.php');
		} elseif(!$fids) {
			showmessage('group_nopermission', NULL, 'NOPERM');
		}

		if($maxspm) {
			if($db->result_first("SELECT COUNT(*) FROM {$tablepre}searchindex WHERE dateline>'$timestamp'-60") >= $maxspm) {
				showmessage('search_toomany', 'search.php');
			}
		}

		$digestltd = $srchfilter == 'digest' ? "t.digest>'0' AND" : '';
		$topltd = $srchfilter == 'top' ? "AND t.displayorder>'0'" : "AND t.displayorder>='0'";

		if(!empty($srchfrom) && empty($srchtxt) && empty($srchtypeid) && empty($srchuid) && empty($srchuname)) {

			$searchfrom = $before ? '<=' : '>=';
			$searchfrom .= $timestamp - $srchfrom;
			$sqlsrch = "FROM {$tablepre}trades tr INNER JOIN {$tablepre}threads t ON tr.tid=t.tid AND $digestltd t.fid IN ($fids) $topltd WHERE tr.dateline$searchfrom";
			$expiration = $timestamp + $cachelife_time;
			$keywords = '';

		} else {

			$sqlsrch = "FROM {$tablepre}trades tr INNER JOIN {$tablepre}threads t ON tr.tid=t.tid AND $digestltd t.fid IN ($fids) $topltd WHERE 1";

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

			if($srchtypeid) {
				$srchtypeid = intval($srchtypeid);
				$sqlsrch .= " AND tr.typeid='$srchtypeid'";
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
						$sqltxtsrch .= "tr.subject LIKE '%$text%'";
					}
				}
				$sqlsrch .= " AND ($sqltxtsrch)";
			}

			if($srchuid) {
				$sqlsrch .= " AND tr.sellerid IN ($srchuid)";
			}

			if(!empty($srchfrom)) {
				$searchfrom = ($before ? '<=' : '>=').($timestamp - $srchfrom);
				$sqlsrch .= " AND tr.dateline$searchfrom";
			}


			$keywords = str_replace('%', '+', $srchtxt).(trim($srchuname) ? '+'.str_replace('%', '+', $srchuname) : '');
			$expiration = $timestamp + $cachelife_text;

		}

		$threads = $tids = 0;
		$query = $db->query("SELECT tr.tid, tr.pid, t.closed $sqlsrch ORDER BY tr.pid DESC LIMIT $maxsearchresults");
		while($post = $db->fetch_array($query)) {
			if($thread['closed'] <= 1) {
				$tids .= ','.$post['pid'];
				$threads++;
			}
		}
		$db->free_result($query);

		$db->query("INSERT INTO {$tablepre}searchindex (keywords, searchstring, useip, uid, dateline, expiration, threads, tids)
				VALUES ('$keywords', '$searchstring', '$onlineip', '$discuz_uid', '$timestamp', '$expiration', '$threads', '$tids')");
		$searchid = $db->insert_id();

		!($exempt & 2) && updatecredits($discuz_uid, $creditspolicy['search'], -1);

	}

	showmessage('search_redirect', "search.php?searchid=$searchid&srchtype=trade&orderby=$orderby&ascdesc=$ascdesc&searchsubmit=yes");

}

?>