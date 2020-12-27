<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: report.inc.php 20608 2009-10-12 02:59:23Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

$reportlist = $logids = array();

if(!empty($fid) && ($forum['type'] == 'group' || !$forum['ismoderator'])) {
	return false;
}

$fidadd = $fid ? "fid='$fid'" : "fid IN ($modforums[fids])";

if(submitcheck('deletesubmit') && $logids = implodeids($ids)) {

	if($op == 'delete') {
		$db->query("DELETE FROM {$tablepre}reportlog WHERE id IN ($logids) AND $fidadd", 'UNBUFFERED');
		if($forum['modworks'] && !$db->result_first("SELECT COUNT(*) FROM {$tablepre}reportlog WHERE $fidadd AND status=1")) {
			$db->query("UPDATE {$tablepre}forums SET modworks='0' WHERE $fidadd", 'UNBUFFERED');
		}
	}

}

$page = max(1, intval($page));
$ppp = 10;
$reportlist = array('pagelink' => '', 'data' => array());

$reportnums = array();
$query = $db->query("SELECT fid, count(*) AS num FROM {$tablepre}reportlog GROUP BY fid");
while ($row = $db->fetch_array($query)) {
	$reportnums[$row['fid']] = $row['num'];
}

if($num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}reportlog WHERE $fidadd")) {

	require_once DISCUZ_ROOT.'./include/post.func.php';
	$page = $page > ceil($num / $ppp) ? ceil($num / $ppp) : $page;
	$start_limit = ($page - 1) * $ppp;
	$reportlist['pagelink'] = multi($num, $ppp, $page, "modcp.php?fid=$fid&action=report");

	$query = $db->query("SELECT r.*, p.tid, p.message, p.author, p.authorid, t.subject, t.displayorder FROM {$tablepre}reportlog r
			LEFT JOIN {$tablepre}posts p ON p.pid=r.pid
			LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
			WHERE r.$fidadd ORDER BY r.dateline DESC LIMIT $start_limit, $ppp");
	$deleteids = $comma = '';
	while($report = $db->fetch_array($query)) {
		if($report['tid'] && $report['displayorder'] >= 0) {
			$report['dateline'] = gmdate("$dateformat $timeformat", $report['dateline'] + $timeoffset * 3600);
			$report['message'] = messagecutstr($report['message'], 200);
			$reportlist['data'][] = $report;
		} else {
			$deleteids .= $comma.$report['id'];
			$comma = ',';
		}
	}
	if($deleteids) {
		$db->query("DELETE FROM {$tablepre}reportlog WHERE id in ($deleteids)");
		if($forum['modworks'] && !$db->result_first("SELECT COUNT(*) FROM {$tablepre}reportlog WHERE fid='$fid' AND status=1")) {
			$db->query("UPDATE {$tablepre}forums SET modworks='0' WHERE fid='$fid'", 'UNBUFFERED');
		}
	}
}

?>