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
	$parameter[] = 'ac=app';

	if(!empty($settings['uid'])) {
		$parameter[] = 'uid='.trim($settings['uid']);
	} elseif($discuz_uid) {
		$parameter[] = 'uid='.$GLOBALS['discuz_uid'];
	}

	if(!empty($settings['type'])) {
		$parameter[] = 'type='.intval($settings['type']);
	}

	$start = !empty($settings['start']) ? intval($settings['start']) : 0;
	$limit = !empty($settings['limit']) ? intval($settings['limit']) : 10;

	$parameter[] = 'start='.$start;
	$parameter[] = 'limit='.$limit;

	$plus = implode('&', $parameter);

	$url = $GLOBALS['uchomeurl']."/api/discuz.php?$plus";
	$applist = unserialize(dfopen($url));
	$writedata = '';
	if($applist && is_array($applist)) {
		$writedata = '<div class="sidebox"><h4>'.$settings['title'].'</h4><table>';
		foreach($applist as $app) {
			$searchs = $replaces = array();
			foreach(array_keys($app) as $key) {
				$searchs[] = '{'.$key.'}';
				$replaces[] = $app[$key];
			}
			$writedata .= '<tr><td>'.str_replace($searchs, $replaces, stripslashes($settings['template'])).'</td></tr>';
		}
		$writedata .= '</table></div>';
	}

} else {
	$request_version = '1.0';
	$request_name = $requestlang['app_name'];
	$request_description = $requestlang['app_desc'];
	$request_copyright = '<a href="http://u.discuz.net/home/" target="_blank">Comsenz Inc.</a>';

	$request_settings = array(
		'title' => array($requestlang['app_title'], $requestlang['app_title_comment'], 'text', '', $requestlang['app_title_value']),
		'uid' => array($requestlang['app_uids'], $requestlang['app_uids_comment'], 'text'),
		'type' => array($requestlang['app_type'], '', 'mradio', array(array('0', $requestlang['app_type_nolimit']), array('1', $requestlang['app_type_default']), array('2', $requestlang['app_type_userapp'])), '0'),
		'start' => array($requestlang['app_start'], $requestlang['app_start_comment'], 'text', '', '0'),
		'limit' => array($requestlang['app_limit'], $requestlang['app_limit_comment'], 'text', '', '10'),
		'template' => array($requestlang['app_template'], $requestlang['app_template_comment'], 'textarea', '','<img src="{icon}"><a href="{link}">{appname}</a>')
	);
}

?>