<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: assistant.inc.php 19591 2009-09-07 04:49:41Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$nocache = 1;
	$avatar = discuz_uc_avatar($GLOBALS['discuz_uid'], 'small');
	$fidadd = isset($_GET['fid']) ? '&srchfid='.$_GET['fid'] : '';
	@include DISCUZ_ROOT.'./forumdata/cache/cache_usergroups.php';
	if($_DCACHE['usergroups'][$GLOBALS['groupid']]['type'] == 'member' && $_DCACHE['usergroups'][$GLOBALS['groupid']]['creditslower'] != 999999999) {
		$creditupgrade = $_DCACHE['usergroups'][$GLOBALS['groupid']]['creditslower'] - $GLOBALS['credits'];
	} else {
		$creditupgrade = '';
	}
	include template('request_assistant');

} else {

	$request_version = '1.0';
	$request_name = $requestlang['assistant_name'];
	$request_description = $requestlang['assistant_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';
	$request_settings = array();

}

?>