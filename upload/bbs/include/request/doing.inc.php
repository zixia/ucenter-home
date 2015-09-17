<?php
/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: doing.inc.php 16697 2008-12-12 07:36:51Z andy $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$parameter = array();
	$parameter[] = 'ac=doing';

	if(!empty($settings['uid'])) {
		$parameter[] = 'uid='.trim($settings['uid']);
	}

	if(!empty($settings['mood'])) {
		$parameter[] = 'mood='.intval($settings['mood']);
	}

	$start = !empty($settings['start']) ? intval($settings['start']) : 0;
	$limit = !empty($settings['limit']) ? intval($settings['limit']) : 10;

	$parameter[] = 'start='.$start;
	$parameter[] = 'limit='.$limit;

	$plus = implode('&', $parameter);

	$url = $GLOBALS['uchomeurl']."/api/discuz.php?$plus";
	$doinglist = unserialize(dfopen($url));
	$writedata = '';
	if($doinglist && is_array($doinglist)) {
		$writedata = '<div class="sidebox"><h4>'.$settings['title'].'</h4><table>';
		foreach($doinglist as $doing) {
			$searchs = $replaces = array();
			foreach(array_keys($doing) as $key) {
				$searchs[] = '{'.$key.'}';
				$replaces[] = $doing[$key];
			}
			$writedata .= '<tr><td>'.str_replace($searchs, $replaces, stripslashes($settings['template'])).'</td></tr>';
		}
		$writedata .= '</table></div>';
	}

} else {
	$request_version = '1.0';
	$request_name = $requestlang['doing_name'];
	$request_description = $requestlang['doing_desc'];
	$request_copyright = '<a href="http://u.discuz.net/home/" target="_blank">Comsenz Inc.</a>';

	$request_settings = array(
		'title' => array($requestlang['doing_title'], $requestlang['doing_title_comment'], 'text', '', $requestlang['doing_title_value']),
		'uid' => array($requestlang['doing_uids'], $requestlang['doing_uids_comment'], 'text'),
		'mood' => array($requestlang['doing_mood'], '', 'mradio', array(array('0', $requestlang['doing_mood_nolimit']), array('1', $requestlang['doing_mood_moodonly'])), '0'),
		'start' => array($requestlang['doing_start'], $requestlang['doing_start_comment'], 'text', '', '0'),
		'limit' => array($requestlang['doing_limit'], $requestlang['doing_limit_comment'], 'text', '', '10'),
		'template' => array($requestlang['doing_template'], $requestlang['doing_template_comment'], 'textarea', '','<a href="{link}">{message}</a>')
	);
}
?>