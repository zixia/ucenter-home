<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: birthday.inc.php 16697 2008-11-14 07:36:51Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$limit = !empty($settings['limit']) ? intval($settings['limit']) : 12;

	$cachefile = DISCUZ_ROOT.'./forumdata/cache/requestscript_birthday.php';
	$today = gmdate('m-d', $timestamp + $timeoffset * 3600);
	if((@!include($cachefile)) || $today != $todaycache || $limit != $limitcache) {
		$query = $db->query("SELECT username, uid FROM {$tablepre}members WHERE RIGHT(bday, 5)='".$today."' ORDER BY bday LIMIT $limit");

		$birthdaymembers = array();
		while($member = $db->fetch_array($query)) {
			$member['username'] = htmlspecialchars($member['username']);
			$birthdaymembers[] = $member;
		}
		$cachefile = DISCUZ_ROOT.'./forumdata/cache/requestscript_birthday.php';
		writetorequestcache($cachefile, 0, "\$limitcache = $limit;\n\$todaycache = '".$today."';\n\$birthdaymembers = ".var_export($birthdaymembers, 1).';');
	}

	include template('request_birthday');

} else {

	$request_version = '1.0';
	$request_name = $requestlang['birthday_name'];
	$request_description = $requestlang['birthday_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';
	$request_settings = array(
		'limit' => array($requestlang['birthday_limit'], $requestlang['birthday_limit_comment'], 'text'),
	);

}

?>