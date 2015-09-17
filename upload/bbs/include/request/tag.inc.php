<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: tag.inc.php 16697 2008-11-14 07:36:51Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$max = $db->result_first("SELECT total FROM {$tablepre}tags WHERE closed=0 ORDER BY total DESC LIMIT 1");
	$viewthreadtags = !empty($settings['limit']) ? intval($settings['limit']) : 10;

	if(!$settings['type']) {
		$count = $db->result_first("SELECT count(*) FROM {$tablepre}tags WHERE closed=0");
		$randlimit = mt_rand(0, $count <= $viewthreadtags ? 0 : $count - $viewthreadtags);
		$query = $db->query("SELECT tagname,total FROM {$tablepre}tags WHERE closed=0 LIMIT $randlimit, $viewthreadtags");
	} else {
		$query = $db->query("SELECT tagname,total FROM {$tablepre}tags WHERE closed=0 ORDER BY total DESC LIMIT $viewthreadtags");
	}
	$taglist = array();
	while($tagrow = $db->fetch_array($query)) {
		$tagrow['level'] = ceil($tagrow['total'] * 5 / $max);
		$tagrow['tagnameenc'] = rawurlencode($tagrow['tagname']);
		$taglist[] = $tagrow;
	}
	!$settings['type'] && shuffle($taglist);

	include template('request_tag');

} else {

	$request_version = '1.0';
	$request_name = $requestlang['tag_name'];
	$request_description = $requestlang['tag_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';
	$request_settings = array(
		'type' => array($requestlang['tag_type'], $requestlang['tag_type_comment'], 'mradio', array(
			array(0, $requestlang['tag_type_0']),
			array(1, $requestlang['tag_type_1'])
			)
		),
		'limit' => array($requestlang['tag_type_limit'], $requestlang['tag_type_limit_comment'], 'text'),
	);

}

?>