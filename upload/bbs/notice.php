<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: notice.php 20815 2009-10-24 09:05:29Z monkey $
*/

define('CURSCRIPT', 'notice');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';

$discuz_action = 102;
if(!$discuz_uid) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

$filter = !empty($filter) && in_array($filter, $promptkeys) ? $filter : '';
$newexists = 0;
$typeadd = $filter ? "AND typeid='".$prompts[$filter]['id']."'" : "AND typeid IN (".implodeids($promptpmids).")";
$page = max(1, intval($page));
$start_limit = ($page - 1) * $ppp;
$pmlist = $ids = array();
$today = $timestamp - ($timestamp + $timeoffset * 3600) % 86400;

$count = $db->result_first("SELECT count(*) FROM {$tablepre}promptmsgs WHERE uid='$discuz_uid' $typeadd");
$query = $db->query("SELECT id AS pmid, new, message, dateline, typeid, actor FROM {$tablepre}promptmsgs WHERE uid='$discuz_uid' $typeadd ORDER BY dateline DESC LIMIT $start_limit, $ppp");
while($row = $db->fetch_array($query)) {
	if($row['new']) {
		$newexists = 1;
	}
	$find = array('{boardurl}', '{time}');
	$replace = array($boardurl, '<em>'.dgmdate("$dateformat $timeformat", $row['dateline'] + $timeoffset * 3600).'</em>'.($row['new'] ? '<img src="'.IMGDIR.'/notice_newpm.gif" alt="NEW" />' : ''));
	if(strpos($row['message'], '{actor}') !== FALSE) {
		list($actorcount, $actors) = explode("\t", $row['actor']);
		$actorarray = explode(',', $actors);
		$actor = $comma = '';
		foreach($actorarray as $au) {
			$actor .= $comma.($au != '<i>Anonymous</i>' ? '<a href="space.php?username='.rawurlencode($au).'" target="_blank">'.$au.'</a>' : $au);
			$comma = ',';
		}
		if($actorcount > 5) {
			include_once language('misc');
			$actor .= eval('return " '.$language['notice_actor'].'";');
		} else {
			$actor .= ' ';
		}
		$find[] = '{actor}';
		$replace[] = $actor;

	}
	$row['message'] = str_replace($find, $replace, $row['message']);
	$pmlist[] = $row;
}
if($newexists) {
	$db->query("UPDATE {$tablepre}promptmsgs SET new='0' WHERE uid='$discuz_uid' AND new='1'", 'UNBUFFERED');
}
$multipage = multi($count, $ppp, $page, 'notice.php'.($filter ? '?filter='.$filter : ''));
if(!$filter) {
	foreach($prompts as $promptkey => $promptdata) {
		if($promptdata['new']) {
			updateprompt($promptkey, $discuz_uid, 0);
		}
	}
} elseif($prompts[$filter]['new']) {
	updateprompt($filter, $discuz_uid, 0);
}


include template('notice');

?>