<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: cron.func.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function runcron($cronid = 0) {
	global $timestamp, $db, $tablepre, $_DCACHE;

	if($cron = $db->fetch_first("SELECT * FROM {$tablepre}crons WHERE ".($cronid ? "cronid='$cronid'" : "available>'0' AND nextrun<='$timestamp'")." ORDER BY nextrun LIMIT 1")) {

		$lockfile = DISCUZ_ROOT.'./forumdata/runcron_'.$cron['cronid'].'.lock';

		$cron['filename'] = str_replace(array('..', '/', '\\'), '', $cron['filename']);
		$cronfile = DISCUZ_ROOT.'./include/crons/'.$cron['filename'];

		if(is_writable($lockfile) && filemtime($lockfile) > $timestamp - 600) {
			return NULL;
		} else {
			@touch($lockfile);
		}

		@set_time_limit(1000);
		@ignore_user_abort(TRUE);

		$cron['minute'] = explode("\t", $cron['minute']);
		cronnextrun($cron);

		extract($GLOBALS, EXTR_SKIP);
		if(!@include $cronfile) {
			errorlog('CRON', $cron['name'].' : Cron script('.$cron['filename'].') not found or syntax error', 0);
		}

		@unlink($lockfile);
	}

	$nextrun = $db->result_first("SELECT nextrun FROM {$tablepre}crons WHERE available>'0' ORDER BY nextrun LIMIT 1");
	if(!$nextrun === FALSE) {
		require_once DISCUZ_ROOT.'./include/cache.func.php';
		$_DCACHE['settings']['cronnextrun'] = $nextrun;
		updatesettings();
	}
}

function cronnextrun($cron) {
	global $db, $tablepre, $_DCACHE, $timestamp;

	if(empty($cron)) return FALSE;

	list($yearnow, $monthnow, $daynow, $weekdaynow, $hournow, $minutenow) = explode('-', gmdate('Y-m-d-w-H-i', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600));

	if($cron['weekday'] == -1) {
		if($cron['day'] == -1) {
			$firstday = $daynow;
			$secondday = $daynow + 1;
		} else {
			$firstday = $cron['day'];
			$secondday = $cron['day'] + gmdate('t', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);
		}
	} else {
		$firstday = $daynow + ($cron['weekday'] - $weekdaynow);
		$secondday = $firstday + 7;
	}

	if($firstday < $daynow) {
		$firstday = $secondday;
	}

	if($firstday == $daynow) {
		$todaytime = crontodaynextrun($cron);
		if($todaytime['hour'] == -1 && $todaytime['minute'] == -1) {
			$cron['day'] = $secondday;
			$nexttime = crontodaynextrun($cron, 0, -1);
			$cron['hour'] = $nexttime['hour'];
			$cron['minute'] = $nexttime['minute'];
		} else {
			$cron['day'] = $firstday;
			$cron['hour'] = $todaytime['hour'];
			$cron['minute'] = $todaytime['minute'];
		}
	} else {
		$cron['day'] = $firstday;
		$nexttime = crontodaynextrun($cron, 0, -1);
		$cron['hour'] = $nexttime['hour'];
		$cron['minute'] = $nexttime['minute'];
	}

	$nextrun = @gmmktime($cron['hour'], $cron['minute'] > 0 ? $cron['minute'] : 0, 0, $monthnow, $cron['day'], $yearnow) - $_DCACHE['settings']['timeoffset'] * 3600;

	$availableadd = $nextrun > $timestamp ? '' : ', available=\'0\'';
	$db->query("UPDATE {$tablepre}crons SET lastrun='$timestamp', nextrun='$nextrun' $availableadd WHERE cronid='$cron[cronid]'");
	return TRUE;
}

function crontodaynextrun($cron, $hour = -2, $minute = -2) {
	global $timestamp, $_DCACHE;

	$hour = $hour == -2 ? gmdate('H', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600) : $hour;
	$minute = $minute == -2 ? gmdate('i', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600) : $minute;

	$nexttime = array();
	if($cron['hour'] == -1 && !$cron['minute']) {
		$nexttime['hour'] = $hour;
		$nexttime['minute'] = $minute + 1;
	} elseif($cron['hour'] == -1 && $cron['minute'] != '') {
		$nexttime['hour'] = $hour;
		if(($nextminute = cronnextminute($cron['minute'], $minute)) === false) {
			++$nexttime['hour'];
			$nextminute = $cron['minute'][0];
		}
		$nexttime['minute'] = $nextminute;
	} elseif($cron['hour'] != -1 && $cron['minute'] == '') {
		if($cron['hour'] < $hour) {
			$nexttime['hour'] = $nexttime['minute'] = -1;
		} elseif($cron['hour'] == $hour) {
			$nexttime['hour'] = $cron['hour'];
			$nexttime['minute'] = $minute + 1;
		} else {
			$nexttime['hour'] = $cron['hour'];
			$nexttime['minute'] = 0;
		}
	} elseif($cron['hour'] != -1 && $cron['minute'] != '') {
		$nextminute = cronnextminute($cron['minute'], $minute);
		if($cron['hour'] < $hour || ($cron['hour'] == $hour && $nextminute === false)) {
			$nexttime['hour'] = -1;
			$nexttime['minute'] = -1;
		} else {
			$nexttime['hour'] = $cron['hour'];
			$nexttime['minute'] = $nextminute;
		}
	}

	return $nexttime;
}

function cronnextminute($nextminutes, $minutenow) {
	foreach($nextminutes as $nextminute) {
		if($nextminute > $minutenow) {
			return $nextminute;
		}
	}
	return false;
}

?>