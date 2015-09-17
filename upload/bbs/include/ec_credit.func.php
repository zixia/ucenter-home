<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Technology Ltd.
	This is NOT a freeware, use is subject to license terms

	$Id: ec_credit.func.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function updatecreditcache($uid, $type, $return = 0) {

	global $db, $tablepre;

	$all = countcredit($uid, $type);
	$halfyear = countcredit($uid, $type, 180);
	$thismonth = countcredit($uid, $type, 30);
	$thisweek = countcredit($uid, $type, 7);
	$before = array(
		'good' => $all['good'] - $halfyear['good'],
		'soso' => $all['soso'] - $halfyear['soso'],
		'bad' => $all['bad'] - $halfyear['bad'],
		'total' => $all['total'] - $halfyear['total']
	);

	$data = array('all' => $all, 'before' => $before, 'halfyear' => $halfyear, 'thismonth' => $thismonth, 'thisweek' => $thisweek);

	$db->query("REPLACE INTO {$tablepre}spacecaches (uid, variable, value, expiration) VALUES ('$uid', '$type', '".addslashes(serialize($data))."', '".getexpiration()."')");
	if($return) {
		return $data;
	}

}

function countcredit($uid, $type, $days = 0) {

	global $timestamp, $db, $tablepre;

	$type = $type == 'buyercredit' ? 1 : 0;
	$timeadd = $days ? ("AND dateline>='".($timestamp - $days * 86400)."'") : '';
	$query = $db->query("SELECT score FROM {$tablepre}tradecomments WHERE rateeid='$uid' AND type='$type' $timeadd");
	$good = $soso = $bad = 0;
	while($credit = $db->fetch_array($query)) {
		if($credit['score'] == 1) {
			$good++;
		} elseif($credit['score'] == 0) {
			$soso++;
		} else {
			$bad++;
		}
	}
	return array('good' => $good, 'soso' => $soso, 'bad' => $bad, 'total' => $good + $soso + $bad);
}

function updateusercredit($uid, $type, $level) {

	global $timestamp, $db, $tablepre;

	$uid = intval($uid);
	if(!$uid || !in_array($type, array('buyercredit', 'sellercredit')) || !in_array($level, array('good', 'soso', 'bad'))) {
		return;
	}

	if($cache = $db->fetch_first("SELECT value, expiration FROM {$tablepre}spacecaches WHERE uid='$uid' AND variable='$type'")) {
		$expiration = $cache['expiration'];
		$cache = unserialize($cache['value']);
	} else {
		$init = array('good' => 0, 'soso' => 0, 'bad' => 0, 'total' => 0);
		$cache = array('all' => $init, 'before' => $init, 'halfyear' => $init, 'thismonth' => $init, 'thisweek' => $init);
		$expiration = getexpiration();
	}

	foreach(array('all', 'before', 'halfyear', 'thismonth', 'thisweek') as $key) {
		$cache[$key][$level]++;
		$cache[$key]['total']++;
	}

	$db->query("REPLACE INTO {$tablepre}spacecaches (uid, variable, value, expiration) VALUES ('$uid', '$type', '".addslashes(serialize($cache))."', '$expiration')");

	$score = $level == 'good' ? 1 : ($level == 'soso' ? 0 : -1);
	$db->query("UPDATE {$tablepre}memberfields SET $type=$type+($score) WHERE uid='$uid'");

}

function getexpiration() {
	$date = getdate($GLOBALS['timestamp']);
	return mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']) + 86400;
}

?>