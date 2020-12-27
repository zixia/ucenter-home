<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: counter.inc.php 17570 2009-02-11 07:41:54Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$visitor = array();

$visitor['agent'] = $_SERVER['HTTP_USER_AGENT'];
list($visitor['month'], $visitor['week'], $visitor['hour']) = explode("\t", gmdate("Ym\tw\tH", $timestamp + $_DCACHE['settings']['timeoffset'] * 3600));

if(!$sessionexists) {
	if(strexists($visitor['agent'], 'Netscape')) {
		$visitor['browser'] = 'Netscape';
	} elseif(strexists($visitor['agent'], 'Lynx')) {
		$visitor['browser'] = 'Lynx';
	} elseif(strexists($visitor['agent'], 'Opera')) {
		$visitor['browser'] = 'Opera';
	} elseif(strexists($visitor['agent'], 'Konqueror')) {
		$visitor['browser'] = 'Konqueror';
	} elseif(strexists($visitor['agent'], 'MSIE')) {
		$visitor['browser'] = 'MSIE';
	} elseif(strexists($visitor['agent'], 'Firefox')) {
		$visitor['browser'] = 'Firefox';
	} elseif(strexists($visitor['agent'], 'Safari')) {
		$visitor['browser'] = 'Safari';
	} elseif(substr($visitor['agent'], 0, 7) == 'Mozilla') {
		$visitor['browser'] = 'Mozilla';
	} else {
		$visitor['browser'] = 'Other';
	}

	if(strexists($visitor['agent'], 'Win')) {
		$visitor['os'] = 'Windows';
	} elseif(strexists($visitor['agent'], 'Mac')) {
		$visitor['os'] = 'Mac';
	} elseif(strexists($visitor['agent'], 'Linux')) {
		$visitor['os'] = 'Linux';
	} elseif(strexists($visitor['agent'], 'FreeBSD')) {
		$visitor['os'] = 'FreeBSD';
	} elseif(strexists($visitor['agent'], 'SunOS')) {
		$visitor['os'] = 'SunOS';
	} elseif(strexists($visitor['agent'], 'OS/2')) {
		$visitor['os'] = 'OS/2';
	} elseif(strexists($visitor['agent'], 'AIX')) {
		$visitor['os'] = 'AIX';
	} elseif(preg_match("/(Bot|Crawl|Spider)/i", $visitor['agent'])) {
		$visitor['os'] = 'Spiders';
	} else {
		$visitor['os'] = 'Other';
	}
	$visitorsadd = "OR (type='browser' AND variable='$visitor[browser]') OR (type='os' AND variable='$visitor[os]')".
		($discuz_user ? " OR (type='total' AND variable='members')" : " OR (type='total' AND variable='guests')");
	$updatedrows = 7;
} else {
	$visitorsadd = '';
	$updatedrows = 4;
}

$db->query("UPDATE {$tablepre}stats SET count=count+1 WHERE (type='total' AND variable='hits') $visitorsadd OR (type='month' AND variable='$visitor[month]') OR (type='week' AND variable='$visitor[week]') OR (type='hour' AND variable='$visitor[hour]')");

if($updatedrows > $db->affected_rows()) {
	$db->query("INSERT INTO {$tablepre}stats (type, variable, count)
			VALUES ('month', '$visitor[month]', '1')", 'SILENT');
}

?>