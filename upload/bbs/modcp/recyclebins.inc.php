<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: recyclebins.inc.php 16698 2008-11-14 07:58:56Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}


$op = !in_array($op , array('list', 'delete', 'search', 'restore')) ? 'list' : $op;
$do = !empty($do) ? dhtmlspecialchars($do) : '';

$tidarray = array();

if($fid && $forum['ismoderator'] && $modforums['recyclebins'][$fid]) {

	$srchupdate = false;

	if($adminid == 1 && ($op == 'delete' || $op == 'restore') && submitcheck('dosubmit')) {

		if($ids = implodeids($moderate)) {
			$query = $db->query("SELECT tid FROM {$tablepre}threads WHERE tid IN($ids) AND fid='$fid' AND displayorder='-1'");
			while ($tid = $db->fetch_array($query)) {
				$tidarray[] = $tid['tid'];
			}
			if($tidarray) {
				require_once DISCUZ_ROOT.'./include/post.func.php';
				($op == 'delete') && deletethreads($tidarray);
				($op == 'restore') && undeletethreads($tidarray);

				if($oldop == 'search') {
					$srchupdate = true;
				}
			}
		}

		$op = dhtmlspecialchars($oldop);

	}

	$result = array();
	foreach (array('threadoption', 'viewsless', 'viewsmore', 'repliesless', 'repliesmore', 'noreplydays') as $key) {
		$$key = isset($$key) && is_numeric($$key) ? intval($$key) : '';
		$result[$key] = $$key;
	}

	foreach (array('starttime', 'endtime', 'keywords', 'users') as $key) {
		$result[$key] = isset($$key) ? dhtmlspecialchars($$key) : '';
	}

	$threadoptionselect = array($threadoption => 'selected');

	if($op == 'search' &&  submitcheck('searchsubmit')) {

		$sql = '';

		if($threadoption > 0 && $threadoption < 255) {
			$sql .= " AND special='$threadoption'";
		} elseif($threadoption == 999) {
			$sql .= " AND digest in(1,2,3)";
		} elseif($threadoption == 888) {
			$sql .= " AND displayorder IN(1,2,3)";
		}

		$sql .= $viewsless !== ''? " AND views<='$viewsless'" : '';
		$sql .= $viewsmore !== ''? " AND views>='$viewsmore'" : '';
		$sql .= $repliesless !== ''? " AND replies<='$repliesless'" : '';
		$sql .= $repliesmore !== ''? " AND replies>='$repliesmore'" : '';
		$sql .= $noreplydays !== ''? " AND lastpost<='$timestamp'-'$noreplydays'*86400" : '';
		$sql .= $starttime != '' ? " AND dateline>='".strtotime($starttime)."'" : '';
		$sql .= $endtime != '' ? " AND dateline<='".strtotime($endtime)."'" : '';

		if(trim($keywords)) {
			$sqlkeywords = '';
			$or = '';
			$keywords = explode(',', str_replace(' ', '', $keywords));
			for($i = 0; $i < count($keywords); $i++) {
				$sqlkeywords .= " $or subject LIKE '%".$keywords[$i]."%'";
				$or = 'OR';
			}
			$sql .= " AND ($sqlkeywords)";

			$keywords = implode(', ', $keywords);
		}

		if(trim($users)) {
			$sql .= " AND author IN ('".str_replace(',', '\',\'', str_replace(' ', '', trim($users)))."')";
		}

		if($sql) {

			$query = $db->query("SELECT tid FROM {$tablepre}threads WHERE fid='$fid' AND displayorder='-1' $sql ORDER BY displayorder DESC, lastpost DESC LIMIT 1000");
			$tids = $comma = '';
			$count = 0;
			while ($tid = $db->fetch_array($query)) {
				$tids .= $comma.$tid['tid'];
				$comma = ',';
				$count ++;
			}

			$result['tids'] = $tids;
			$result['count'] = $count;
			$result['fid'] = $fid;

			$modsession->set('srchresult_r', $result, true);

			$db->free_result($query);
			unset($result, $tids);
			$page = 1;

		} else {
			$op = 'list';
		}
	}

	$page = max(1, intval($page));
	$total = 0;
	$query = $multipage = '';

	if($op == 'list') {
		$total = $db->result_first("SELECT count(*) FROM {$tablepre}threads WHERE fid='$fid' AND displayorder='-1'");
		$tpage = ceil($total / $tpp);
		$page = min($tpage, $page);
		$multipage = multi($total, $tpp, $page, "$cpscript?action=$action&amp;op=$op&amp;fid=$fid&amp;do=$do");
		if($total) {
			$start = ($page - 1) * $tpp;
			$query = $db->query("SELECT * FROM {$tablepre}threads WHERE fid='$fid' AND displayorder='-1' ORDER BY displayorder DESC, lastpost DESC LIMIT $start, $tpp");
		}
	}

	if($op == 'search') {

		$result = $modsession->get('srchresult_r');

		if($result['fid'] == $fid) {

			if($srchupdate && $result['count'] && $tidarray) {
				$td = explode(',', $result['tids']);
				$newtids = $comma = $newcount = '';
				if(is_array($td)) {
					foreach ($td as $v) {
						$v = intval($v);
						if(!in_array($v, $tidarray)) {
							$newcount ++;
							$newtids .= $comma.$v; $comma = ',';
						}
					}
					$result['count'] = $newcount;
					$result['tids'] = $newtids;
					$modsession->set('srchresult_r'.$fid, $result, true);
				}
			}

			$threadoptionselect = array($result['threadoption'] => 'selected');

			$total = $result['count'];
			$tpage = ceil($total / $tpp);
			$page = min($tpage, $page);
			$multipage = multi($total, $tpp, $page, "$cpscript?action=$action&amp;op=$op&amp;fid=$fid&amp;do=$do");
			if($total) {
				$start = ($page - 1) * $tpp;
				$query = $db->query("SELECT * FROM {$tablepre}threads WHERE tid in($result[tids]) AND fid='$fid' AND displayorder='-1' ORDER BY lastpost DESC LIMIT $start, $tpp");
			}

		} else {
			$result = array();
			$modsession->set('srchresult_r', array());
		}

	}

	$postlist = array();
	if($query) {
		require_once DISCUZ_ROOT.'./include/misc.func.php';
		while ($thread = $db->fetch_array($query)) {
			$post = procthread($thread);
			$post['modthreadkey'] = modthreadkey($post['tid']);
			$postlist[] = $post;
		}
	}

}

?>