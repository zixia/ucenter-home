<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: search.inc.php 16718 2008-11-17 03:48:41Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$cachelife_time = 300;		// Life span for cache of searching in specified range of time
$cachelife_text = 3600;		// Life span for cache of text searching

if(!$allowsearch) {
	wapmsg('search_group_nopermission');
}

$do = !empty($do) ? $do : '';

if($do != 'submit') {

	echo 	"<p>$lang[search]<br />".
		"$lang[keywords]:<input type=\"text\" name=\"srchtxt\" value=\"\" maxlength=\"15\" format=\"M*m\" /><br />\n".
		"$lang[username]:<input type=\"text\" name=\"srchuname\" value=\"\" format=\"M*m\" /><br />\n".
		"<anchor title=\"$lang[submit]\">$lang[submit]".
		"<go method=\"post\" href=\"index.php?action=search&amp;do=submit\">\n".
		"<postfield name=\"sid\" value=\"$sid\" />\n".
		"<postfield name=\"srchtxt\" value=\"$(srchtxt)\" />\n".
		"<postfield name=\"srchuname\" value=\"$(srchuname)\" />\n".
		"</go></anchor></p>";

} else {

	if(isset($searchid)) {

		$page = max(1, intval($page));
		$start_limit = $number = ($page - 1) * $waptpp;

		$index = $db->fetch_first("SELECT searchstring, keywords, threads, tids FROM {$tablepre}searchindex WHERE searchid='$searchid'");
		if(!$index) {
			wapmsg('search_id_invalid');
		}
		$index['keywords'] = rawurlencode($index['keywords']);
		$index['searchtype'] = preg_replace("/^([a-z]+)\|.*/", "\\1", $index['searchstring']);

		$searchnum = $db->result_first("SELECT COUNT(*) FROM  {$tablepre}threads WHERE tid IN ($index[tids]) AND displayorder>='0'");
		if($searchnum) {
			echo "<p>$lang[search_result]<br />";
			$query = $db->query("SELECT * FROM {$tablepre}threads WHERE tid IN ($index[tids]) AND displayorder>='0' ORDER BY dateline DESC LIMIT $start_limit, $waptpp");
			while($thread = $db->fetch_array($query)) {
				echo "<a href=\"index.php?action=thread&amp;tid=$thread[tid]\">#".++$number." ".cutstr($thread['subject'], 24)."</a>($thread[views]/$thread[replies])<br />\n";
			}
			echo wapmulti($searchnum, $waptpp, $page, "index.php?action=search&amp;searchid=$searchid&amp;do=submit&amp;sid=$sid");
			echo '</p>';
		} else {
			wapmsg('search_invalid');
		}

	} else {

		$srchtxt = trim(wapconvert($srchtxt));
		$srchuname = trim(wapconvert($srchuname));
		$srchuid = intval($srchuid);

		$searchstring = 'title|'.addslashes($srchtxt).'|'.$srchuid.'|'.$srchuname;
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
				wapmsg('search_ctrl');
			}
		}

		if($searchindex['id']) {

			$searchid = $searchindex['id'];

		} else {

			if(empty($srchfid)) {
				$srchfid = 'all';
			}

			if(!$srchtxt && !empty($srchuid) && !$srchuname && !$srchfrom) {
				wapmsg('search_invalid');
			}

			if(!empty($srchfrom) && empty($srchtxt) && empty($srchuid) && empty($srchuname)) {

				$searchfrom = !empty($before) ? '<=' : '>=';
				$searchfrom .= $timestamp - $srchfrom;
				$sqlsrch = "FROM {$tablepre}threads t WHERE t.displayorder>='0' AND t.lastpost$searchfrom";
				$expiration = $timestamp + $cachelife_time;
				$keywords = '';

			} else {

				if(!empty($mytopics) && $srchuid) {
					$srchfrom = 2592000;
					$srchuname = $srchtxt = $before = '';
				}

				$sqlsrch = "FROM {$tablepre}threads t WHERE t.displayorder>='0'";

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

				$sqltxtsrch = '';
				if($srchtxt) {
					$srchtxt = str_replace('*', '%', addcslashes($srchtxt, '%_'));
					$sqltxtsrch .= "t.subject LIKE '%$srchtxt%'";
					$sqlsrch .= " AND ($sqltxtsrch)";
				}

				if($srchuid) {
					$sqlsrch .= " AND authorid IN ($srchuid)";
				}

				if($srchfid != 'all' && $srchfid) {
					$sqlsrch .= " AND fid='$srchfid'";
				}

				$keywords = str_replace('%', '+', $srchtxt).(trim($srchuname) ? '+'.str_replace('%', '+', $srchuname) : '');
				$expiration = $timestamp + $cachelife_text;

			}

			$threads = $tids = 0;
			$query = $sdb->query("SELECT DISTINCT t.tid, t.closed $sqlsrch ORDER BY tid DESC LIMIT $maxsearchresults");
			while($thread = $sdb->fetch_array($query)) {
				if($thread['closed'] <= 1) {
					$tids .= ','.$thread['tid'];
					$threads++;
				}
			}
			$db->free_result($query);

			$db->query("INSERT INTO {$tablepre}searchindex (keywords, searchstring, useip, uid, dateline, expiration, threads, tids)
					VALUES ('$keywords', '$searchstring', '$onlineip', '$discuz_uid', '$timestamp', '$expiration', '$threads', '$tids')");
			$searchid = $db->insert_id();

		}

		header("Location: index.php?action=search&searchid=$searchid&do=submit&sid=$sid");

	}

}

?>