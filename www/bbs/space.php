<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: space.php 21055 2009-11-10 00:33:11Z monkey $
*/

if(!defined('CURSCRIPT')) {
	define('CURSCRIPT', 'profile');
}

require_once './include/common.inc.php';
$allowviewpro = $discuz_uid && ($uid == $discuz_uid || $username == $discuz_user) ? 1 : $allowviewpro;

if($my_status && $from) {
	$funcstatinfo[] = 'manyou2dz,20090727';
}
if(!$allowviewpro) {
	showmessage('group_nopermission', NULL, 'NOPERM');
}

require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
require_once DISCUZ_ROOT.'./include/forum.func.php';
@include_once DISCUZ_ROOT.'./forumdata/cache/cache_viewpro.php';
@extract($_DCACHE['custominfo']);

$discuz_action = 61;

if($oltimespan) {
	$oltimeadd1 = ', o.thismonth AS thismonthol, o.total AS totalol';
	$oltimeadd2 = "LEFT JOIN {$tablepre}onlinetime o ON o.uid=m.uid";
} else {
	$oltimeadd1 = $oltimeadd2 = '';
}

$member = $db->fetch_first("SELECT m.*, mf.*, u.grouptitle, u.type, u.creditshigher, u.creditslower, u.readaccess,
		u.color AS groupcolor, u.stars AS groupstars, u.allownickname, r.ranktitle,
		r.color AS rankcolor, r.stars AS rankstars $oltimeadd1
		FROM {$tablepre}members m
		LEFT JOIN {$tablepre}memberfields mf ON mf.uid=m.uid
		LEFT JOIN {$tablepre}usergroups u ON u.groupid=m.groupid
		LEFT JOIN {$tablepre}ranks r ON m.posts>=r.postshigher
		$oltimeadd2
		WHERE ".($uid ? "m.uid='$uid'" : "m.username='$username'")."ORDER BY r.postshigher DESC LIMIT 1");

if(!$member) {
	header("HTTP/1.0 404 Not Found");
	showmessage('member_nonexistence');
}

$usemagic = array();
if($magicstatus) {
	require_once DISCUZ_ROOT.'./forumdata/cache/cache_magics.php';
	foreach($_DCACHE['magics'] as $id => $magic) {
		if($magic['available'] == 1 && $magic['type'] == 2) {
			$usemagic['user'][$id]['name'] = $magic['name'];
			$usemagic['user'][$id]['pic'] = strtolower($magic['identifier']).'_s.gif';
		}
	}
}

$profileuid = $member['uid'];
$member['online'] = $db->result_first("SELECT lastactivity FROM {$tablepre}sessions WHERE uid='$member[uid]' AND invisible='0'");

if($member['groupid'] != ($member['groupidnew'] = getgroupid($member['uid'], $member, $member))) {
	$member = array_merge($member, $db->fetch_first("SELECT groupid, grouptitle, type, creditshigher, creditslower, color AS groupcolor,
		stars AS groupstars, allownickname
		FROM {$tablepre}usergroups WHERE groupid='$member[groupidnew]'"));
}

$modforums = $comma = '';
if($member['adminid'] > 0) {
	$query = $db->query("SELECT m.fid, f.name, f.type FROM {$tablepre}moderators m, {$tablepre}forums f WHERE m.uid='$member[uid]' AND m.inherited='0' AND f.fid=m.fid");
	while($forum = $db->fetch_array($query)) {
		$modforums .= "$comma<a href=\"".($forum['type'] == 'group' ? "$indexname?gid=" : "forumdisplay.php?fid=")."$forum[fid]\">$forum[name]</a>";
		$comma = ', ';
	}
}

$member['groupterms'] = $member['groupterms'] ? unserialize($member['groupterms']) : array();

$extgrouplist = array();
if($member['extgroupids']) {
	$temp = array_map('intval', explode("\t", $member['extgroupids']));
	if($temp = implodeids($temp)) {
		$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups WHERE groupid IN ($temp)");
		while($group = $db->fetch_array($query)) {
			$extgrouplist[] = array('title' => $group['grouptitle'], 'expiry' => (isset($member['groupterms']['ext'][$group['groupid']]) ? gmdate($dateformat, $member['groupterms']['ext'][$group['groupid']] + $timeoffset * 3600) : ''));
		}
	}
}

@$percent = round($member['posts'] * 100 / $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts"), 2);
$postperday = $timestamp - $member['regdate'] > 86400 ? round(86400 * $member['posts'] / ($timestamp - $member['regdate']), 2) : $member['posts'];

$member['grouptitle'] = $member['groupcolor'] ? '<font color="'.$member['groupcolor'].'">'.$member['grouptitle'].'</font>' : $member['grouptitle'];
$member['ranktitle'] = $member['rankcolor'] ? '<font color="'.$member['rankcolor'].'">'.$member['ranktitle'].'</font>' : $member['ranktitle'];

if($oltimespan) {
	$member['totalol'] = round($member['totalol'] / 60, 2);
	$member['thismonthol'] = gmdate('Yn', $member['lastactivity']) == gmdate('Yn', $timestamp) ? round($member['thismonthol'] / 60, 2) : 0;
}

$member['usernameenc'] = rawurlencode($member['username']);
$member['regdate'] = gmdate($dateformat, $member['regdate'] + $timeoffset * 3600);
$member['email'] = emailconv($member['email']);
$member['msn']= explode("\t", $member['msn']);

$member['replies'] = $member['posts'] - $member['threads'];

$member['lastvisit'] = dgmdate("$dateformat $timeformat", $member['lastvisit'] + ($timeoffset * 3600));
$member['lastpost'] = $member['lastpost'] ? dgmdate("$dateformat $timeformat", $member['lastpost'] + ($timeoffset * 3600)) : 'x';
$member['lastdate'] = gmdate($dateformat, $member['lastactivity'] + ($timeoffset * 3600));

$member['taobaoas'] = str_replace("'", '', addslashes($member['taobao']));

$member['olupgrade'] = $member['totalol'] ? 20 - $member['totalol'] % 20 : 20;

list($year, $month, $day) = explode('-', $member['bday']);
$member['bday'] = intval($year) ? $dateformat : preg_replace("/[^nj]*[Yy][^nj]*/", '', $dateformat);
$member['bday'] = str_replace('n', $month, $member['bday']);
$member['bday'] = str_replace('j', $day, $member['bday']);
$member['bday'] = str_replace('Y', $year, $member['bday']);
$member['bday'] = str_replace('y', substr($year, 2, 4), $member['bday']);

if($member['groupexpiry'] && isset($member['groupterms']['main']['time'])) {
	$member['maingroupexpiry'] = gmdate($dateformat, $member['groupterms']['main']['time'] + $timeoffset * 3600);
}

if($allowviewip && !($adminid == 2 && $member['adminid'] == 1) && !($adminid == 3 && ($member['adminid'] == 1 || $member['adminid'] == 2))) {
	require_once DISCUZ_ROOT.'./include/misc.func.php';
	$member['regiplocation'] = convertip($member['regip']);
	$member['lastiplocation'] = convertip($member['lastip']);
} else {
	$allowviewip = 0;
}

$_DCACHE['fields_required'] = is_array($_DCACHE['fields_required']) ? $_DCACHE['fields_required'] : array();
$_DCACHE['fields_optional'] = is_array($_DCACHE['fields_optional']) ? $_DCACHE['fields_optional'] : array();
foreach(array_merge($_DCACHE['fields_required'], $_DCACHE['fields_optional']) as $field) {
	if(!$field['invisible'] || $adminid == 1 || $member['uid'] == $discuz_uid) {
		$_DCACHE['fields'][] = $field;
	}
}

unset($_DCACHE['fields_required'], $_DCACHE['fields_optional']);

if($member['medals']) {
        require_once DISCUZ_ROOT.'./forumdata/cache/cache_medals.php';
        foreach($member['medals'] = explode("\t", $member['medals']) as $key => $medalid) {
                list($medalid, $medalexpiration) = explode("|", $medalid);
                if(isset($_DCACHE['medals'][$medalid]) && (!$medalexpiration || $medalexpiration > $timestamp)) {
                        $member['medals'][$key] = $_DCACHE['medals'][$medalid];
                } else {
                        unset($member['medals'][$key]);
                }
        }
}
$member['buyerrank'] = 0;
if($member['buyercredit']){
	foreach($ec_credit['rank'] AS $level => $credit) {
		if($member['buyercredit'] <= $credit) {
			$member['buyerrank'] = $level;
			break;
		}
	}
}
$member['sellerrank'] = 0;
if($member['sellercredit']){
	foreach($ec_credit['rank'] AS $level => $credit) {
		if($member['sellercredit'] <= $credit) {
			$member['sellerrank'] = $level;
			break;
		}
	}
}

if($my_status) {
	require_once DISCUZ_ROOT.'./manyou/sources/profile.func.php';
	$my_list = getmyml($member['uid']);
}

include template('viewpro_classic');

function emailconv($email, $tolink = 1) {
	$email = str_replace(array('@', '.'), array('&#64;', '&#46;'), $email);
	return $tolink ? '<a href="mailto:'.$email.'">'.$email.'</a>': $email;
}

?>