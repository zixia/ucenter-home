<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc. & (C)2009 DPS LuciferSheng
	This is NOT a freeware, use is subject to license terms

	$Id: admin.inc.php 21306 2009-11-26 00:56:50Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$palang = $GLOBALS['scriptlang']['dps_postawards'];

@include_once DISCUZ_ROOT.'./forumdata/cache/cache_usergroups.php';

if(submitcheck('submit')){
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	writetocache('postawards_setting', '', getcachevars(array('PACACHE' => array('userright' => $userright))));
	$cache = serialize($userright);
	$cachedata = "\$PACACHE['userright'] = ".arrayeval($userright).";\n\n";
	$db->query("REPLACE INTO {$tablepre}caches (cachename, type, dateline, data) VALUES ('postawards', '1', '$timestamp', '".addslashes($cachedata)."')");
}

@include_once DISCUZ_ROOT.'./forumdata/cache/cache_postawards_setting.php';

if(is_array($PACACHE)) {
	foreach($PACACHE['userright'] as $key => $item) {
		$postawards_checked[$key] = $item['postawards'] ? ' checked' : '';
		$systemcredit_checked[$key] = $item['systemcredit'] ? ' checked' : '';
		$ratemode_checked[$key] = $item['ratemode'] ? ' checked' : '';
		$ratelowlimit[$key] = $item['ratelowlimit'];
		$ratehighlimit[$key] = $item['ratehighlimit'];
		$ratealllimit[$key] = $item['ratealllimit'];
	}
}

showtips($palang['tips']);

showformheader("plugins&operation=config&identifier=dps_postawards&mod=admin&ac=submit");
showtableheader($palang['group_setting']);
showsubtitle(array($palang['groupname'], '', $palang['allow_postawards'], $palang['allow_systemcredit'], $palang['allow_systemcredit_low'], $palang['allow_systemcredit_high'], $palang['allow_systemcredit_all'], $palang['allow_ratemode']));

$query = $db->query("SELECT groupid, radminid, grouptitle FROM {$tablepre}usergroups WHERE radminid>0 ORDER BY groupid");

while($group = $db->fetch_array($query)){
	$list = showtablerow('', array('class="td35"', 'class="td35"', 'class="td35"', 'class="td35"', 'class="td35"', 'class="td35"', 'class="td35"', 'class="td35"'), array(
		$group['grouptitle'],
		'&raquo;',
		'<input type="checkbox" class="checkbox" name="userright['.$group['groupid'].'][postawards]" id="postawards" value="1"'.$postawards_checked[$group['groupid']].'>',
		'<input type="checkbox" class="checkbox" name="userright['.$group['groupid'].'][systemcredit]" id="systemcredit" value="1"'.$systemcredit_checked[$group['groupid']].'>',
		'<input type="text" name="userright['.$group['groupid'].'][ratelowlimit]" id="ratelowlimit" value="'.$ratelowlimit[$group['groupid']].'">',
		'<input type="text" name="userright['.$group['groupid'].'][ratehighlimit]" id="ratehighlimit" value="'.$ratehighlimit[$group['groupid']].'">',
		'<input type="text" name="userright['.$group['groupid'].'][ratealllimit]" id="ratealllimit" value="'.$ratealllimit[$group['groupid']].'">',
		'<input type="checkbox" class="checkbox" name="userright['.$group['groupid'].'][ratemode]" id="ratemode" value="1"'.$ratemode_checked[$group['groupid']].'>'), TRUE);
	echo $list;
}

showsubmit('submit', 'submit', '', '');
showtablefooter();
showformfooter();

?>
