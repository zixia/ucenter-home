<?php
/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: feed.inc.php 16697 2008-12-05 07:36:51Z andy $
*/
if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$parameter = array();
	$parameter[] = 'ac=feed';

	if(!empty($settings['uids'])) {
		$parameter[] = 'uids='.trim($settings['uids']);
	}

	if(!empty($settings['friend'])) {
		$parameter[] = 'friend='.intval($GLOBALS['discuz_uid']);
		$nocache = 1;
	}

	$start = !empty($settings['start']) ? intval($settings['start']) : 0;
	$limit = !empty($settings['limit']) ? intval($settings['limit']) : 10;

	$parameter[] = 'start='.$start;
	$parameter[] = 'limit='.$limit;

	$plus = implode('&', $parameter);

	$url = $GLOBALS['uchomeurl']."/api/discuz.php?$plus";
	$feedlist = unserialize(dfopen($url));
	$writedata = '';
	if($feedlist && is_array($feedlist)) {
		$writedata = '<div class="sidebox"><h4>'.$settings['title'].'</h4><table>';
		foreach($feedlist as $feed) {
			$searchs = $replaces = array();
			foreach(array_keys($feed) as $key) {
				$searchs[] = '{'.$key.'}';
				$replaces[] = $feed[$key];
			}
			$writedata .= '<tr><td>'.str_replace($searchs, $replaces, stripslashes($settings['template'])).'</td></tr>';
		}
		$writedata .= '</table></div>';
	}

} else {
	$request_version = '1.0';
	$request_name = $requestlang['feed_name'];
	$request_description = $requestlang['feed_desc'];
	$request_copyright = '<a href="http://u.discuz.net/home/" target="_blank">Comsenz Inc.</a>';

	$request_settings = array(
		'title' => array($requestlang['feed_title'], $requestlang['feed_title_comment'], 'text', '', $requestlang['feed_title_value']),
		'uids' 	=> array($requestlang['feed_uids'], $requestlang['feed_uids_comment'], 'text'),
		'friend' => array($requestlang['feed_friend'], '', 'mradio', array(array('0', $requestlang['feed_friend_nolimit']), array('1', $requestlang['feed_friend_friendonly'])), '0'),
		'start' => array($requestlang['feed_start'], $requestlang['feed_start_comment'], 'text', '', '0'),
		'limit' => array($requestlang['feed_limit'], $requestlang['feed_limit_comment'], 'text', '', '10'),
		'template' => array($requestlang['feed_template'], $requestlang['feed_template_comment'], 'textarea', '','<a href="{userlink}">{title_template}</a>')
	);
}

?>