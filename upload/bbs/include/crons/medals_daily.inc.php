<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: medals_daily.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$medalnewarray = array();

$query = $db->query("SELECT me.id, me.uid, me.medalid, me.expiration, mf.medals 
					FROM {$tablepre}medallog me
					LEFT JOIN {$tablepre}memberfields mf USING (uid)
					WHERE me.status=1 AND me.expiration<$timestamp");

while($medalnew = $db->fetch_array($query)) {
	$medalsnew = array();
	$medalnew['medals'] = empty($medalnewarray[$medalnew['uid']]) ? explode("\t", $medalnew['medals']) : explode("\t", $medalnewarray[$medalnew['uid']]);

	foreach($medalnew['medals'] as $key => $medalnewid) {
		list($medalid, $medalexpiration) = explode("|", $medalnewid);
		if($medalnew['medalid'] == $medalid) {
			unset($medalnew['medals'][$key]);
		}
	}

	$medalnewarray[$medalnew['uid']] = implode("\t", $medalnew['medals']);
	$db->query("UPDATE {$tablepre}medallog SET status='0' WHERE id='".$medalnew['id']."'");
	$db->query("UPDATE {$tablepre}memberfields SET medals='".$medalnewarray[$medalnew['uid']]."' WHERE uid='".$medalnew['uid']."'");
}
?>