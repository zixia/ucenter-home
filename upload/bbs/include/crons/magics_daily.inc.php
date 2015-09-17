<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magics_daily.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!empty($magicstatus)) {
	$magicarray = array();
	$query = $db->query("SELECT magicid, supplytype, supplynum, num FROM {$tablepre}magics WHERE available='1'");
	while($magic = $db->fetch_array($query)) {
		if($magic['supplytype'] && $magic['supplynum']) {
			$magicarray[$magic['magicid']]['supplytype'] = $magic['supplytype'];
			$magicarray[$magic['magicid']]['supplynum'] = $magic['supplynum'];
		}
	}

	list($daynow, $weekdaynow) = explode('-', gmdate('d-w', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600));

	foreach($magicarray as $id => $magic) {
		$autosupply = 0;
		if($magic['supplytype'] == 1) {
			$autosupply = 1;
		} elseif($magic['supplytype'] == 2 && $weekdaynow == 1) {
			$autosupply = 1;
		} elseif($magic['supplytype'] == 3 && $daynow == 1) {
			$autosupply = 1;
		}

		if(!empty($autosupply)) {
			$db->query("UPDATE {$tablepre}magics SET num=num+'$magic[supplynum]' WHERE magicid='$id'");
		}
	}
}

?>