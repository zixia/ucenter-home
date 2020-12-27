<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: viewthread_activity.inc.php 17393 2008-12-17 07:30:46Z liuqiang $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$sdb = loadmultiserver();
$applylist = array();
$activity = $sdb->fetch_first("SELECT * FROM {$tablepre}activities WHERE tid='$tid'");
$activityclose = $activity['expiration'] ? ($activity['expiration'] > $timestamp - date('Z') ? 0 : 1) : 0;
$activity['starttimefrom'] = dgmdate("$dateformat $timeformat", $activity['starttimefrom'] + $timeoffset * 3600);
$activity['starttimeto'] = $activity['starttimeto'] ? gmdate("$dateformat $timeformat", $activity['starttimeto'] + $timeoffset * 3600) : 0;
$activity['expiration'] = $activity['expiration'] ? gmdate("$dateformat $timeformat", $activity['expiration'] + $timeoffset * 3600) : 0;

$isverified = $applied = 0;
if($discuz_uid) {
	$query = $db->query("SELECT verified FROM {$tablepre}activityapplies WHERE tid='$tid' AND uid='$discuz_uid'");
	if($db->num_rows($query)) {
		$isverified = $db->result($query, 0);
		$applied = 1;
	}
}

$query = $db->query("SELECT aa.username, aa.uid, aa.dateline, aa.message, aa.payment, aa.contact, m.groupid FROM {$tablepre}activityapplies aa
	LEFT JOIN {$tablepre}members m USING(uid)
	LEFT JOIN {$tablepre}memberfields mf USING(uid)
	WHERE aa.tid='$tid' AND aa.verified=1 ORDER BY aa.dateline DESC LIMIT 9");
while($activityapplies = $db->fetch_array($query)) {
	$activityapplies['dateline'] = dgmdate("$dateformat $timeformat", $activityapplies['dateline'] + $timeoffset * 3600);
	$applylist[] = $activityapplies;
}

if($thread['authorid'] == $discuz_uid) {
	$applylistverified = array();
	$query = $db->query("SELECT aa.username, aa.uid, aa.dateline, aa.message, aa.payment, aa.contact, m.groupid FROM {$tablepre}activityapplies aa
		LEFT JOIN {$tablepre}members m USING(uid)
		LEFT JOIN {$tablepre}memberfields mf USING(uid)
		WHERE aa.tid='$tid' AND aa.verified=0 ORDER BY aa.dateline DESC LIMIT 9");
	while($activityapplies = $db->fetch_array($query)) {
		$activityapplies['dateline'] = dgmdate("$dateformat $timeformat", $activityapplies['dateline'] + $timeoffset * 3600);
		$applylistverified[] = $activityapplies;
	}
}

$applynumbers = $db->result_first("SELECT COUNT(*) FROM {$tablepre}activityapplies WHERE tid='$tid' AND verified=1");
$aboutmembers = $activity['number'] >= $applynumbers ? $activity['number'] - $applynumbers : 0;

?>