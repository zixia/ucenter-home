<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: promotion.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!empty($fromuid)) {
	$fromuid = intval($fromuid);
	$fromuser = '';
}

if(!$discuz_uid || !($fromuid == $discuz_uid || $fromuser == $discuz_user)) {

	if($creditspolicy['promotion_visit']) {
		$db->query("REPLACE INTO {$tablepre}promotions (ip, uid, username)
			VALUES ('$onlineip', '$fromuid', '$fromuser')");
	}

	if($creditspolicy['promotion_register']) {
		if(!empty($fromuser) && empty($fromuid)) {
			if(empty($_DCOOKIE['promotion'])) {
				$fromuid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$fromuser'");
			} else {
				$fromuid = intval($_DCOOKIE['promotion']);
			}
		}
		if($fromuid) {
			dsetcookie('promotion', ($_DCOOKIE['promotion'] = $fromuid), 1800);
		}
	}

}

?>