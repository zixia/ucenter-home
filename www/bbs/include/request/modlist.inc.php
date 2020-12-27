<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: modlist.inc.php 16697 2008-11-14 07:36:51Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$modlist = array();
	if($GLOBALS['forum']['moderators']) {
		$moderators = daddslashes(explode("\t", $GLOBALS['forum']['moderators']), 1);
		if($GLOBALS['modworkstatus']) {
			$query = $db->query("SELECT m.uid, m.username, sum(mw.count) as actioncount FROM {$tablepre}members m LEFT JOIN {$tablepre}modworks mw on m.uid=mw.uid WHERE m.username in (".implodeids($moderators).") GROUP BY mw.uid ORDER BY actioncount DESC");
		} else {
			$query = $db->query("SELECT m.uid, m.username, m.posts FROM {$tablepre}members m WHERE m.username in (".implodeids($moderators).") ORDER BY posts DESC");
		}
		while($modrow = $db->fetch_array($query)) {		
			$modrow['avatar'] = discuz_uc_avatar($modrow['uid'], 'small');
			$modrow['actioncount'] = intval($modrow['actioncount']);
			$modlist[] = $modrow;
		}
	}

	include template('request_modlist');

} else {

	$request_version = '1.0';
	$request_name = $requestlang['modlist_name'];
	$request_description = $requestlang['modlist_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';

}

?>