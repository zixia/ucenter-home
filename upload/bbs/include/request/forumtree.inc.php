<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forumtree.inc.php 20763 2009-10-19 02:35:45Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	require_once DISCUZ_ROOT.'./include/forum.func.php';
	if(!$_DCACHE['forums']) {
		include_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
	}
	foreach($_DCACHE['forums'] as $forum) {
		if(!$forum['status']) {
			continue;
		}
		if(!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || strstr($forum['users'], "\t$GLOBALS[discuz_uid]\t")) {
			$forum['name'] = addslashes($forum['name']);
			$forum['type'] != 'group' && $haschild[$forum['fup']] = true;
			$forumlist[] = $forum;
		}
	}
	$nocache = 1;

	include template('request_forumtree');

} else {

	$request_version = '1.0';
	$request_name = $requestlang['forumtree_name'];
	$request_description = $requestlang['forumtree_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';

}

?>