<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: medal.php 20267 2009-09-23 02:42:03Z monkey $
*/

define('CURSCRIPT', 'medal');

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_medals.php';

if(!$discuz_uid && $action) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

$medallist = $medallogs = array();
$tpp = 10;
$page = max(1, intval($page));
$start_limit = ($page - 1) * $tpp;

if(empty($action)) {

	$query = $db->query("SELECT * FROM {$tablepre}medals WHERE available='1' ORDER BY displayorder LIMIT 0,100");
	while($medal = $db->fetch_array($query)) {
		$medal['permission'] = formulaperm($medal['permission'], 2);
		$medallist[$medal['medalid']] = $medal;
	}

	$medaldata = $db->result_first("SELECT medals FROM {$tablepre}memberfields WHERE uid='$discuz_uid'");
	$membermedal = $medaldata ? explode("\t", $medaldata) : array();
	$medalcount = count($membermedal);
	if(!empty($membermedal)) {
		$medallog = array();
		foreach($membermedal as $medalid) {
			if($medalpos = strpos($medalid, '|')) {
				$medalid = substr($medalid, 0, $medalpos);
			}
			$medallog['name'] = $_DCACHE['medals'][$medalid]['name'];
			$medallog['image'] = $medallist[$medalid]['image'];
			$medallogs[] = $medallog;
		}
		
		foreach($medallist as $medalid => $medal) {
			if(in_array($medalid, $membermedal)) {
				unset($medallist[$medalid]);
			}
		}
	}

} elseif($action == 'apply' && submitcheck('medalsubmit')) {

	$medalid = intval($medalid);
	$formulamessage = $usermsg = $medalnew = '';
	$medal = $db->fetch_first("SELECT * FROM {$tablepre}medals WHERE medalid='$medalid'");
	if(!$medal['type']) {
		showmessage('medal_apply_invalid');
	}

	$medaldetail = $db->fetch_first("SELECT medalid FROM {$tablepre}medallog WHERE uid='$discuz_uid' AND medalid='$medalid' AND type NOT IN('3', '4')");
	if($medaldetail['medalid']) {
		showmessage('medal_apply_existence', 'medal.php');
	}

	$medalpermission = $medal['permission'] ? unserialize($medal['permission']) : '';
	if($medalpermission[0]) {
		formulaperm($medal['permission'], 1);

		if($formulamessage) {
			showmessage('medal_permforum_nopermission', 'medal.php');
		}

		$usermedal = $db->fetch_first("SELECT medals FROM {$tablepre}memberfields WHERE uid='$discuz_uid'");
		$medalnew = $usermedal['medals'] ? $usermedal['medals']."\t".$medal['medalid'] : $medal['medalid'];
		$db->query("UPDATE {$tablepre}memberfields SET medals='$medalnew' WHERE uid='$discuz_uid'");

		$db->query("INSERT INTO {$tablepre}medallog (uid, medalid, type, dateline, expiration, status) VALUES ('$discuz_uid', '$medalid', '1', '$timestamp', '$expiration', '0')");
		showmessage('medal_get_succeed', 'medal.php');
	} else {
		$expiration = empty($medal['expiration'])? 0 : $timestamp + $medal['expiration'] * 86400;
		$db->query("INSERT INTO {$tablepre}medallog (uid, medalid, type, dateline, expiration, status) VALUES ('$discuz_uid', '$medalid', '2', '$timestamp', '$expiration', '0')");
		showmessage('medal_apply_succeed', 'medal.php');
	}




} elseif($action == 'log') {

	$medallognum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}medallog WHERE uid='$discuz_uid' AND type IN ('0', '1')");
	$multipage = multi($medallognum, $tpp, $page, "medal.php?action=log");

	$query = $db->query("SELECT me.*, m.image FROM {$tablepre}medallog me
			LEFT JOIN {$tablepre}medals m USING (medalid)
			WHERE me.uid='$discuz_uid' AND me.type IN ('0', '1') ORDER BY me.dateline DESC LIMIT $start_limit,$tpp");
	while($medallog = $db->fetch_array($query)) {
		$medallog['name'] = $_DCACHE['medals'][$medallog['medalid']]['name'];
		$medallog['dateline'] = gmdate("$dateformat $timeformat", $medallog['dateline'] + $timeoffset * 3600);
		$medallog['expiration'] = !empty($medallog['expiration']) ? gmdate("$dateformat $timeformat", $medallog['expiration'] + $timeoffset * 3600) : '';
		$medallogs[] = $medallog;
	}

} else {
	showmessage('undefined_action', NULL, 'HALTED');
}

include template('medal');

?>