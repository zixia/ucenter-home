<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: my.php 21156 2009-11-18 00:44:35Z monkey $
*/

error_reporting(0);
define('IN_DISCUZ', TRUE);
define('X_VER', '7.2');
define('X_MYVER', '0.3');
define('X_LANGUAGE', 'zh_CN');
define('DISCUZ_ROOT', substr(dirname(__FILE__), 0, -10));
define('DEFAULT_FRIENDNUM', 2000);

$timestamp = time();
$member = array();
include_once DISCUZ_ROOT.'./config.inc.php';
require_once DISCUZ_ROOT.'./include/db_'.$database.'.class.php';
include_once DISCUZ_ROOT.'./forumdata/cache/cache_settings.php';
require_once DISCUZ_ROOT.'./include/global.func.php';
include_once DISCUZ_ROOT.'./manyou/api/class/MyBase.php';
include_once DISCUZ_ROOT.'./manyou/api/class/APIErrorResponse.php';
include_once DISCUZ_ROOT.'./manyou/api/class/APIResponse.php';
include_once DISCUZ_ROOT.'./uc_client/client.php';

$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

$server = new my();
$response = $server->parseRequest();
echo $server->formatResponse($response);

?>