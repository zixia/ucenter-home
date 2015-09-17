<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: html.inc.php 16697 2008-11-14 07:36:51Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$settings['code'] = stripslashes($settings['code']);
	if($settings['type']) {
		include_once DISCUZ_ROOT.'./include/discuzcode.func.php';
		$writedata = discuzcode($settings['code'], 0, 0);		
	} else {
		$writedata = $settings['code'];
	}
	if($settings['side']) {
		$writedata = '<div class="sidebox">'.$writedata.'</div>';
	}

} else {

	$request_version = '1.0';
	$request_name = $requestlang['html_name'];
	$request_description = $requestlang['html_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';
	$request_settings = array(
		'type' => array($requestlang['html_type'], $requestlang['html_type_comment'], 'select', array(
			array('0', $requestlang['html_type_html']),
			array('1', $requestlang['html_type_code'])
			)
		),
		'code' => array($requestlang['html_code'], $requestlang['html_code_comment'], 'textarea'),
		'side' => array($requestlang['html_side'], $requestlang['html_side_comment'], 'radio'),
	);

}

?>