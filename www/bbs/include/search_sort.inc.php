<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: search_sort.inc.php 21043 2009-11-09 03:08:08Z tiger $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!empty($searchid)) {
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $tpp;

	$index = $db->fetch_first("SELECT searchstring, keywords, threads, threadsortid, tids FROM {$tablepre}searchindex WHERE searchid='$searchid' AND threadsortid='$sortid'");

	if(!$index) {
		showmessage('search_id_invalid');
	}

	$threadlist = $typelist = $resultlist = $optionlist = array();
	$query = $db->query("SELECT tid, subject, dateline, iconid FROM {$tablepre}threads WHERE tid IN ($index[tids]) AND displayorder>=0 ORDER BY dateline LIMIT $start_limit, $tpp");
	while($info = $db->fetch_array($query)) {
		$threadlist[$info['tid']]['icon'] = isset($GLOBALS['_DCACHE']['icons'][$info['iconid']]) ? '<img src="images/icons/'.$GLOBALS['_DCACHE']['icons'][$info['iconid']].'" alt="Icon'.$info['iconid'].'" class="icon" />' : '&nbsp;';
		$threadlist[$info['tid']]['dateline'] = dgmdate("$dateformat $timeformat", $info['dateline'] + $timeoffset * 3600);
		$threadlist[$info['tid']]['subject'] = $info['subject'];
	}

	@include_once DISCUZ_ROOT.'./forumdata/cache/threadsort_'.$index['threadsortid'].'.php';

	$query = $db->query("SELECT tid, optionid, value FROM {$tablepre}typeoptionvars WHERE tid IN ($index[tids])");
	while($info = $db->fetch_array($query)) {
		if($_DTYPE[$info['optionid']]['search']) {
			$optionid = $info['optionid'];
			$identifier = $_DTYPE[$optionid]['identifier'];
			$unit = $_DTYPE[$optionid]['unit'];
			$typelist[$info['tid']][$optionid]['value'] = $info['value'];
			$optionlist[$identifier] = $_DTYPE[$optionid]['title'].($unit ? "($unit)" : '');
		}
	}

	$optionlist = $optionlist ? array_unique($optionlist) : '';

	$choiceshow = array();
	foreach($threadlist as $tid => $thread) {
		$resultlist[$tid]['icon'] = $thread['icon'];
		$resultlist[$tid]['subject'] = $thread['subject'];
		$resultlist[$tid]['dateline'] = $thread['dateline'];
		if(is_array($typelist[$tid])) {
			foreach($typelist[$tid] as $optionid => $value) {
				$identifier = $_DTYPE[$optionid]['identifier'];
				if(in_array($_DTYPE[$optionid]['type'], array('select', 'radio'))) {
					$resultlist[$tid]['option'][$identifier] = $_DTYPE[$optionid]['choices'][$value['value']];
				} elseif($_DTYPE[$optionid]['type'] == 'checkbox') {
					foreach(explode("\t", $value['value']) as $choiceid) {
						$choiceshow[$tid] .= $_DTYPE[$optionid]['choices'][$choiceid].'&nbsp;';
					}
					$resultlist[$tid]['option'][$identifier] = $choiceshow[$tid];
				} elseif($_DTYPE[$optionid]['type'] == 'image') {
					$maxwidth = $_DTYPE[$optionid]['maxwidth'] ? 'width="'.$_DTYPE[$optionid]['maxwidth'].'"' : '';
					$maxheight = $_DTYPE[$optionid]['maxheight'] ? 'height="'.$_DTYPE[$optionid]['maxheight'].'"' : '';
					$resultlist[$tid]['option'][$identifier] = $optiondata[$optionid] ? "<a href=\"$optiondata[$optionid]\" target=\"_blank\"><img src=\"$value[value]\"  $maxwidth $maxheight border=\"0\"></a>" : '';
				} elseif($_DTYPE[$optionid]['type'] == 'url') {
					$resultlist[$tid]['option'][$identifier] = $optiondata[$optionid] ? "<a href=\"$value[value]\" target=\"_blank\">$value[value]</a>" : '';
				} else {
					$resultlist[$tid]['option'][$identifier] = $value['value'];
				}
			}
		}
	}

	$colspan = count($optionlist) + 2;
	$multipage = multi($index['threads'], $tpp, $page, "search.php?searchid=$searchid&srchtype=threadsort&sortid=$index[threadsortid]&searchsubmit=yes");
	$url_forward = 'search.php?'.$_SERVER['QUERY_STRING'];
	include template('search_sort');

} else {

	!($exempt & 2) && checklowerlimit($creditspolicy['search'], -1);

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

	$srchoption = $tab = '';
	if($searchoption && is_array($searchoption)) {
		foreach($searchoption as $optionid => $option) {
			$srchoption .= $tab.$optionid;
			$tab = "\t";
		}
	}

	$searchstring = 'type|'.addslashes($srchoption);
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
			showmessage('search_ctrl', "search.php?srchtype=threadsort&sortid=$selectsortid&srchfid=$fid");
		}
	}

	if($searchindex['id']) {

		$searchid = $searchindex['id'];

	} else {

		if((!$searchoption || !is_array($searchoption)) && !$selectsortid) {
			showmessage('search_threadtype_invalid', "search.php?srchtype=threadsort&sortid=$selectsortid&srchfid=$fid");
		} elseif(isset($srchfid) && $srchfid != 'all' && !(is_array($srchfid) && in_array('all', $srchfid)) && empty($forumsarray)) {
			showmessage('search_forum_invalid', "search.php?srchtype=threadsort&sortid=$selectsortid&srchfid=$fid");
		} elseif(!$fids) {
			showmessage('group_nopermission', NULL, 'NOPERM');
		}

		if($maxspm) {
			if($db->result_first("SELECT COUNT(*) FROM {$tablepre}searchindex WHERE dateline>'$timestamp'-60") >= $maxspm) {
				showmessage('search_toomany', 'search.php');
			}
		}

		@include_once DISCUZ_ROOT.'./forumdata/cache/threadsort_'.$selectsortid.'.php';

		$sqlsrch = $or = '';
		if(!empty($searchoption) && is_array($searchoption)) {
			foreach($searchoption as $optionid => $option) {
				$fieldname = $_DTYPE[$optionid]['identifier'] ? $_DTYPE[$optionid]['identifier'] : 1;
				if($option['value']) {
					if(in_array($option['type'], array('number', 'radio', 'select'))) {
						$option['value'] = intval($option['value']);
						$exp = '=';
						if($option['condition']) {
							$exp = $option['condition'] == 1 ? '>' : '<';
						}
						$sql = "$fieldname$exp'$option[value]'";
					} elseif($option['type'] == 'checkbox') {
						$sql = "$fieldname LIKE '%\t".(implode("\t", $option['value']))."\t%'";
					} else {
						$sql = "$fieldname LIKE '%$option[value]%'";
					}
					$sqlsrch .= $and."$sql ";
					$and = 'AND ';
				}
			}
		}

		$threads = $tids = 0;
		$query = $db->query("SELECT tid FROM {$tablepre}optionvalue$selectsortid ".($sqlsrch ? 'WHERE '.$sqlsrch : '')."");
		while($post = $db->fetch_array($query)) {
			$tids .= ','.$post['tid'];
		}
		$db->free_result($query);

		if($fids) {
			$query = $db->query("SELECT tid, closed FROM {$tablepre}threads WHERE tid IN ($tids) AND fid IN ($fids) LIMIT $maxsearchresults");
			while($post = $db->fetch_array($query)) {
				if($thread['closed'] <= 1) {
					$tids .= ','.$post['tid'];
					$threads++;
				}
			}
		}

		$db->query("INSERT INTO {$tablepre}searchindex (keywords, searchstring, useip, uid, dateline, expiration, threads, threadsortid, tids)
				VALUES ('$keywords', '$searchstring', '$onlineip', '$discuz_uid', '$timestamp', '$expiration', '$threads', '$selectsortid', '$tids')");
		$searchid = $db->insert_id();

		!($exempt & 2) && updatecredits($discuz_uid, $creditspolicy['search'], -1);

	}

	showmessage('search_redirect', "search.php?searchid=$searchid&srchtype=threadsort&sortid=$selectsortid&searchsubmit=yes");

}

?>