<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: google.inc.php 16697 2008-11-14 07:36:51Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$writedata = !empty($GLOBALS['google']) ? '<div id="headsearch" class="sidebox"><script type="text/javascript">
		var google_host="'.$_SERVER['HTTP_HOST'].'",
		google_charset="'.$GLOBALS['charset'].'",
		google_hl="'.$settings['lang'].'",
		google_lr="'.($settings['lang'] ? 'lang_'.$settings['lang'] : '').'";
		google_default_0="'.($settings['default'] == 0 ? ' selected' : '').'";
		google_default_1="'.($settings['default'] == 1 ? ' selected' : '').'";
		</script>
		<script type="text/javascript" src="include/js/google.js"></script></div>
	' : '';

} else {

	$request_version = '1.0';
	$request_name = $requestlang['google_name'];
	$request_description = $requestlang['google_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';
	$request_settings = array(
		'lang' => array($requestlang['google_lang'], $requestlang['google_lang_comment'], 'mradio', array(
			array('', $requestlang['google_lang_any']),
			array('en', $requestlang['google_lang_en']),
			array('zh-CN', $requestlang['google_lang_zh-CN']),
			array('zh-TW', $requestlang['google_lang_zh-TW']))
		),
		'default' => array($requestlang['google_default'], $requestlang['google_default_comment'], 'mradio', array(
			array(0, $requestlang['google_default_0']),
			array(1, $requestlang['google_default_1']))
		),
	);

}

?>