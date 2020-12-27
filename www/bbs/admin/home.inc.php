<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: home.inc.php 20858 2009-10-28 00:59:31Z zhaoxiongfei $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(@file_exists(DISCUZ_ROOT.'./install/index.php')) {
	@unlink(DISCUZ_ROOT.'./install/index.php');
	if(@file_exists(DISCUZ_ROOT.'./install/index.php')) {
		dexit('Please delete install/index.php via FTP!');
	}
}

@include_once DISCUZ_ROOT.'./discuz_version.php';
require_once DISCUZ_ROOT.'./include/attachment.func.php';

$siteuniqueid = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='siteuniqueid'");
if(empty($siteuniqueid) || strlen($siteuniqueid) < 16) {
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
	$siteuniqueid = $chars[date('y')%60].$chars[date('n')].$chars[date('j')].$chars[date('G')].$chars[date('i')].$chars[date('s')].substr(md5($onlineip.$discuz_user.$timestamp), 0, 4).random(6);
	$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('siteuniqueid', '$siteuniqueid')");
}

if(empty($_DCACHE['settings']['authkey']) || strlen($_DCACHE['settings']['authkey']) < 16) {
	$authkey = $_DCACHE['settings']['authkey'] = substr(md5($siteuniqueid.$bbname.$timestamp), 8, 8).random(8);
	$db->query("REPLACE INTO {$tablepre}settings SET variable='authkey', value='$authkey'");
	updatesettings();
}


if(submitcheck('notesubmit', 1)) {
	if($noteid) {
		$db->query("DELETE FROM {$tablepre}adminnotes WHERE id='$noteid' AND (admin='$discuz_user' OR adminid>='$adminid')");
	}
	if($newmessage) {
		$newaccess[$adminid] = 1;
		$newaccess = bindec(intval($newaccess[1]).intval($newaccess[2]).intval($newaccess[3]));
		$newexpiration = strtotime($newexpiration);
		$newmessage = nl2br(dhtmlspecialchars($newmessage));
		$db->query("INSERT INTO {$tablepre}adminnotes (admin, access, adminid, dateline, expiration, message)
			VALUES ('$discuz_user', '$newaccess', '$adminid', '$timestamp', '$newexpiration', '$newmessage')");
	}
}

$serverinfo = PHP_OS.' / PHP v'.PHP_VERSION;
$serverinfo .= @ini_get('safe_mode') ? ' Safe Mode' : NULL;
$serversoft = $_SERVER['SERVER_SOFTWARE'];
$dbversion = $db->result_first("SELECT VERSION()");

if(@ini_get('file_uploads')) {
	$fileupload = ini_get('upload_max_filesize');
} else {
	$fileupload = '<font color="red">'.$lang['no'].'</font>';
}

//$groupselect = '';
//$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups ORDER BY creditslower, groupid");
//while($group = $db->fetch_array($query)) {
//	$groupselect .= '<option value="'.$group['groupid'].'">'.$group['grouptitle'].'</option>';
//}

$dbsize = 0;
$query = $db->query("SHOW TABLE STATUS LIKE '$tablepre%'", 'SILENT');
while($table = $db->fetch_array($query)) {
	$dbsize += $table['Data_length'] + $table['Index_length'];
}
$dbsize = $dbsize ? sizecount($dbsize) : $lang['unknown'];

if(isset($attachsize)) {
	$attachsize = $db->result($db->query("SELECT SUM(filesize) FROM {$tablepre}attachments"), 0);
	$attachsize = is_numeric($attachsize) ? sizecount($attachsize) : $lang['unknown'];
} else {
	$attachsize = '<a href="admincp.php?action=home&attachsize">[ '.$lang['detail'].' ]</a>';
}

$membersmod = $db->result_first("SELECT COUNT(*) FROM {$tablepre}validating WHERE status='0'");
$postsmod = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE first='0' AND invisible='-2'");
$threadsdel = $threadsmod = 0;
$query = $db->query("SELECT displayorder FROM {$tablepre}threads WHERE displayorder<'0'");
while($thread = $db->fetch_array($query)) {
	if($thread['displayorder'] == -1) {
		$threadsdel++;
	} elseif($thread['displayorder'] == -2) {
		$threadsmod++;
	}
}

cpheader();
shownav();

showsubmenu('home_welcome');

$save_mastermobile = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='mastermobile'");
$save_mastermobile = !empty($save_mastermobile) ? authcode($save_mastermobile, 'DECODE', $authkey) : '';

$securityadvise = '';
$securityadvise .= !$discuz_secques ? $lang['home_secques_invalid'] : '';
$securityadvise .= empty($forumfounders) ? $lang['home_security_nofounder'] : '';
$securityadvise .= $admincp['tpledit'] ? $lang['home_security_tpledit'] : '';
$securityadvise .= $admincp['runquery'] ? $lang['home_security_runquery'] : '';

if(isfounder()) {
	if($securyservice) {
		$new_mastermobile = trim($new_mastermobile);
		if(empty($new_mastermobile)) {
			$save_mastermobile = $new_mastermobile;
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('mastermobile', '$new_mastermobile')");
		} elseif($save_mastermobile != $new_mastermobile && strlen($new_mastermobile) == 11 && is_numeric($new_mastermobile) && (substr($new_mastermobile, 0, 2) == '13' || substr($new_mastermobile, 0, 2) == '15')) {
			$save_mastermobile = $new_mastermobile;
			$new_mastermobile = authcode($new_mastermobile, 'ENCODE', $authkey);
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('mastermobile', '$new_mastermobile')");
		}
	}

	$view_mastermobile = !empty($save_mastermobile) ? substr($save_mastermobile, 0 , 3).'*****'.substr($save_mastermobile, -3) : '';

	$securityadvise = '<li><p>'.lang('home_security_service_info').'</p><form method="post" action="'.$BASESCRIPT.'?action=home&securyservice=yes">'.lang('home_security_service_mobile').': <input type="text" class="txt" name="new_mastermobile" value="'.$view_mastermobile.'" size="30" /> <input type="submit" class="btn" name="securyservice" value="'.lang($view_mastermobile ? 'submit' : 'home_security_service_open').'"  /> <span class="lightfont">'.lang($view_mastermobile ? 'home_security_service_mobile_save' : 'home_security_service_mobile_none').'</span></form></li>'.$securityadvise;
}

showtableheader('home_security_tips', '', '', 0);
showtablerow('', 'class="tipsblock"', '<ul>'.$securityadvise.'</ul>');
showtablefooter();
//showsubmenu('home_security_tips');
//echo '<ul class="safelist">'.$securityadvise.'</ul>';

echo '<div id="boardnews"></div>';

showtableheader('', 'nobottom fixpadding');
if($membersmod || $threadsmod || $postsmod || $threadsdel) {
	showtablerow('', '', '<h3 class="left margintop">'.lang('home_mods').': </h3><p class="left difflink">'.
		($membersmod ? '<a href="'.$BASESCRIPT.'?action=moderate&operation=members">'.lang('home_mod_members').'</a>(<em class="lightnum">'.$membersmod.'</em>)' : '').
		($threadsmod ? '<a href="'.$BASESCRIPT.'?action=moderate&operation=threads">'.lang('home_mod_threads').'</a>(<em class="lightnum">'.$threadsmod.'</em>)' : '').
		($postsmod ? '<a href="'.$BASESCRIPT.'?action=moderate&operation=replies">'.lang('home_mod_posts').'</a>(<em class="lightnum">'.$postsmod.'</em>)' : '').
		($threadsdel ? '<a href="'.$BASESCRIPT.'?action=recyclebin">'.lang('home_del_threads').'</a>(<em class="lightnum">'.$threadsdel.'</em>)' : '').
		'</p><div class="clear"></div>'
	);
}
showtablefooter();

showformheader('home');
showtableheader('home_notes', 'fixpadding"', '', '3');

$query = $db->query("SELECT * FROM {$tablepre}adminnotes ORDER BY dateline DESC");
while($note = $db->fetch_array($query)) {
	if($note['expiration'] < $timestamp) {
		$db->query("DELETE FROM {$tablepre}adminnotes WHERE id='$note[id]'");
	} else {
		$note['adminenc'] = rawurlencode($note['admin']);
		$note['dateline'] = gmdate($dateformat, $note['dateline'] + $timeoffset * 3600);
		$note['expiration'] = gmdate($dateformat, $note['expiration'] + $timeoffset * 3600);
		showtablerow('', array('', '', ''), array(
			'<a href="'.$BASESCRIPT.'?action=home&notesubmit=yes&noteid='.$note['id'].'"><img src="images/admincp/close.gif" width="7" height="8" title="'.lang('delete').'" /></a>',
			"<span class=\"bold\"><a href=\"space.php?username=$note[adminenc]\" target=\"_blank\">$note[admin]</a>: </span>$note[message]",
			"$note[dateline] ~ $note[expiration]"
		));
	}
}

showtablerow('', array(), array(
	lang('home_notes_add'),
	'<input type="text" class="txt" name="newmessage" value="" style="width:300px;" />',
	lang('validity').': <input type="text" class="txt" name="newexpiration" value="'.gmdate('Y-n-j', $timestamp + $timeoffset * 3600 + 86400 * 30).'" size="8" /><input name="notesubmit" value="'.lang('submit').'" type="submit" class="btn" />'
));
showtablefooter();
showformfooter();

include_once DISCUZ_ROOT.'./uc_client/client.php';

showtableheader('home_sys_info', 'fixpadding');
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_discuz_version'),
	'Discuz! '.DISCUZ_VERSION.' Release '.DISCUZ_RELEASE.' <a href="http://faq.comsenz.com/checkversion.php?product=Discuz&version='.DISCUZ_VERSION.'&release='.DISCUZ_RELEASE.'&charset='.$charset.'&dbcharset='.$dbcharset.'" class="lightlink smallfont" target="_blank">'.lang('home_check_newversion').'</a> <a href="http://www.comsenz.com/purchase/discuz/" class="lightlink smallfont" target="_blank">&#19987;&#19994;&#25903;&#25345;&#19982;&#26381;&#21153;</a> <a href="http://idc.comsenz.com" class="lightlink smallfont" target="_blank">&#68;&#105;&#115;&#99;&#117;&#122;&#33;&#19987;&#29992;&#20027;&#26426;</a>'
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_ucclient_version'),
	'UCenter '.UC_CLIENT_VERSION.' Release '.UC_CLIENT_RELEASE
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_environment'),
	$serverinfo
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_serversoftware'),
	$serversoft
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_database'),
	$dbversion
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_upload_perm'),
	$fileupload
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_database_size'),
	$dbsize
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_attach_size'),
	$attachsize
));
showtablefooter();

showtableheader('home_dev', 'fixpadding');
showtablerow('', array('class="vtop td24 lineheight"'), array(
	lang('home_dev_copyright'),
	'<span class="bold"><a href="http://www.comsenz.com" class="lightlink2" target="_blank">&#x5eb7;&#x76db;&#x521b;&#x60f3;(&#x5317;&#x4eac;)&#x79d1;&#x6280;&#x6709;&#x9650;&#x516c;&#x53f8; (Comsenz Inc.)</a></span>'
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_dev_manager'),
	'<a href="http://www.discuz.net/space.php?uid=1" class="lightlink smallfont" target="_blank">&#x6234;&#x5FD7;&#x5EB7; (Kevin \'Crossday\' Day)</a>'
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight smallfont"'), array(
	lang('home_dev_team'),
	'<a href="http://www.discuz.net/space.php?uid=2691" class="lightlink smallfont" target="_blank">Liang \'Readme\' Chen</a>,
	 <a href="http://www.discuz.net/space.php?uid=1519" class="lightlink smallfont" target="_blank">Yang \'Summer\' Xia</a>,
	 <a href="http://www.discuz.net/space.php?uid=859" class="lightlink smallfont" target="_blank">Hypo \'cnteacher\' Wang</a>,
	 <a href="http://www.discuz.net/space.php?uid=16678" class="lightlink smallfont" target="_blank">Yang \'Dokho\' Song</a>,
	 <a href="http://www.discuz.net/space.php?uid=10407" class="lightlink smallfont" target="_blank">Qiang Liu</a>,
	 <a href="http://www.discuz.net/space.php?uid=80629" class="lightlink smallfont" target="_blank">Ning \'Monkey\' Hou</a>,
	 <a href="http://www.discuz.net/space.php?uid=15104" class="lightlink smallfont" target="_blank">Xiongfei \'Redstone\' Zhao</a>,
	 <a href="http://www.discuz.net/space.php?uid=632268" class="lightlink smallfont" target="_blank">Jinbo \'Ggggqqqqihc\' Wang</a>,
	 <a href="http://www.discuz.net/space.php?uid=246213" class="lightlink smallfont" target="_blank">Lanbo Liu</a>,
	 <a href="http://www.discuz.net/space.php?uid=598685" class="lightlink smallfont" target="_blank">Guoquan Zhao</a>,
	 <a href="http://www.discuz.net/space.php?uid=492114" class="lightlink smallfont" target="_blank">Liang \'Metthew\' Xu</a>'
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight"'), array(
	lang('home_dev_skins'),
	'<a href="http://www.discuz.net/space.php?uid=294092" class="lightlink smallfont" target="_blank">Fangming \'Lushnis\' Li</a>,
	<a href="http://www.discuz.net/space.php?uid=674006" class="lightlink smallfont" target="_blank">Jizhou \'Iavav\' Yuan</a>,
	<a href="http://www.discuz.net/space.php?uid=362790" class="lightlink smallfont" target="_blank">Defeng \'Dfox\' Xu</a>,
	<a href="http://www.discuz.net/space.php?uid=717854" class="lightlink smallfont" target="_blank">Ruitao \'Pony.M\' Ma</a>'
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight"'), array(
	lang('home_dev_thanks'),
	'<a href="http://www.discuz.net/space.php?uid=122246" class="lightlink smallfont" target="_blank">Heyond</a>,
	<a href="http://www.discuz.net/space.php?uid=210272" class="lightlink smallfont" target="_blank">XiaoDun \'Kenshine\' Fang</a>,
	<a href="http://www.discuz.net/space.php?uid=86282" class="lightlink smallfont" target="_blank">Jianxieshui</a>,
	<a href="http://www.discuz.net/space.php?uid=9600" class="lightlink smallfont" target="_blank">Theoldmemory</a>,
	<a href="http://www.discuz.net/space.php?uid=2629" class="lightlink smallfont" target="_blank">Rain5017</a>,
	<a href="http://www.discuz.net/space.php?uid=26926" class="lightlink smallfont" target="_blank">Snow Wolf</a>,
	<a href="http://www.discuz.net/space.php?uid=17149" class="lightlink smallfont" target="_blank">Hehechuan</a>,
	<a href="http://www.discuz.net/space.php?uid=9132" class="lightlink smallfont" target="_blank">Pk0909</a>,
	<a href="http://www.discuz.net/space.php?uid=248" class="lightlink smallfont" target="_blank">feixin</a>,
	<a href="http://www.discuz.net/space.php?uid=675" class="lightlink smallfont" target="_blank">Laobing Jiuba</a>,
	<a href="http://www.discuz.net/space.php?uid=13877" class="lightlink smallfont" target="_blank">Artery</a>,
	<a href="http://www.discuz.net/space.php?uid=233" class="lightlink smallfont" target="_blank">Huli Hutu</a>,
	<a href="http://www.discuz.net/space.php?uid=122" class="lightlink smallfont" target="_blank">Lao Gui</a>,
	<a href="http://www.discuz.net/space.php?uid=159" class="lightlink smallfont" target="_blank">Tyc</a>,
	<a href="http://www.discuz.net/space.php?uid=177" class="lightlink smallfont" target="_blank">Stoneage</a>,
	<a href="http://www.discuz.net/space.php?uid=7155" class="lightlink smallfont" target="_blank">Gregry</a>,
	<a href="http://www.7dps.com" class="lightlink smallfont" target="_blank">Discuz! Product Support</a>'
));
showtablerow('', array('class="vtop td24 lineheight"', 'class="lineheight"'), array(
	lang('home_dev_links'),
	'<a href="http://www.comsenz.com" class="lightlink" target="_blank">&#x516C;&#x53F8;&#x7F51;&#x7AD9;</a>, <a href="http://idc.comsenz.com" class="lightlink" target="_blank">&#x865A;&#x62DF;&#x4E3B;&#x673A;</a>, <a href="http://www.comsenz.com/category-51" class="lightlink" target="_blank">&#x8D2D;&#x4E70;&#x6388;&#x6743;</a>, <a href="http://www.discuz.com/" class="lightlink" target="_blank">&#x44;&#x69;&#x73;&#x63;&#x75;&#x7A;&#x21;&#x20;&#x4EA7;&#x54C1;</a>, <a href="http://www.comsenz.com/downloads/styles/discuz" class="lightlink" target="_blank">&#x6A21;&#x677F;</a>, <a href="http://www.comsenz.com/downloads/plugins/discuz" class="lightlink" target="_blank">&#x63D2;&#x4EF6;</a>, <a href="http://faq.comsenz.com" class="lightlink" target="_blank">&#x6587;&#x6863;</a>, <a href="http://www.discuz.net/" class="lightlink" target="_blank">&#x8BA8;&#x8BBA;&#x533A;</a>'
));
showtablefooter();

echo '</div>';

?>