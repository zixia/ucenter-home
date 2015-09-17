<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: javascript.php 18385 2009-04-30 02:25:43Z monkey $
*/

//error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(0);

define('IN_DISCUZ', TRUE);
define('DISCUZ_ROOT', '../');

if(PHP_VERSION < '4.1.0') {
	$_GET		=	&$HTTP_GET_VARS;
	$_SERVER	=	&$HTTP_SERVER_VARS;
}

require_once DISCUZ_ROOT.'./forumdata/cache/cache_settings.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_request.php';

if($_DCACHE['settings']['gzipcompress']) {
	ob_start('ob_gzhandler');
}

$jsstatus	=	isset($_DCACHE['settings']['jsstatus']) ? $_DCACHE['settings']['jsstatus'] : 1;

if(!$jsstatus && !empty($_GET['key'])) {
	exit("document.write(\"<font color=red>The webmaster did not enable this feature.</font>\");");
}

$jsrefdomains	=	isset($_DCACHE['settings']['jsrefdomains']) ? $_DCACHE['settings']['jsrefdomains'] : preg_replace("/([^\:]+).*/", "\\1", (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL));
$REFERER	= 	parse_url($_SERVER['HTTP_REFERER']);
if($jsrefdomains && (empty($REFERER) | !in_array($REFERER['host'], explode("\r\n", trim($jsrefdomains))))) {
	exit("document.write(\"<font color=red>Referer restriction is taking effect.</font>\");");
}

if(!empty($_GET['key']) && !empty($_DCACHE['request'][$_GET['key']]['url'])) {
	$cachefile	=	DISCUZ_ROOT.'./forumdata/cache/javascript_'.$_GET['key'].'.php';
	parse_str($_DCACHE['request'][$_GET['key']]['url'], $requestdata);
} else {
	exit;
}

$expiration	=	0;
$timestamp	=	time();
$rewritestatus 	=	$_DCACHE['settings']['rewritestatus'];
$uc		=	$_DCACHE['settings']['uc'];

if(((@!include($cachefile)) || $expiration < $timestamp) && !file_exists($cachefile.'.lock')) {

	require_once DISCUZ_ROOT.'./config.inc.php';
	require_once DISCUZ_ROOT.'./include/db_'.$database.'.class.php';
	require_once DISCUZ_ROOT.'./include/global.func.php';
	require_once DISCUZ_ROOT.'./include/request.func.php';

	$db = new dbstuff;
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
	unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

	$dateformat	=	!empty($_DCACHE['settings']['jsdateformat']) ? $_DCACHE['settings']['jsdateformat'] : (!empty($_DCACHE['settings']['dateformat']) ? $_DCACHE['settings']['dateformat'] : 'm/d');
	$timeformat	=	isset($_DCACHE['settings']['timeformat']) ? $_DCACHE['settings']['timeformat'] : 'H:i';
	$PHP_SELF	=	$_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$boardurl	=	'http://'.$_SERVER['HTTP_HOST'].preg_replace("/\/+(api)?\/*$/i", '', substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'))).'/';
	$datalist 	= 	parse_request($requestdata, $cachefile, 1);

}

echo $datalist;

function jsprocdata($data, $requestcharset) {
	global $boardurl, $_DCACHE, $charset;
	if($requestcharset) {
		include DISCUZ_ROOT.'include/chinese.class.php';
		if(strtoupper($charset) != 'UTF-8') {
			$c = new Chinese($charset, 'utf8');
		} else {
			$c = new Chinese('utf8', $requestcharset == 1 ? 'gbk' : 'big5');
		}
		$data = $c->Convert($data);
	}
	return 'document.write(\''.preg_replace("/\r\n|\n|\r/", '\n', addcslashes($data, "'\\")).'\');';
}


?>