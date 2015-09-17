<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: var.inc.php 21311 2009-11-26 01:35:43Z liulanbo $
*/

if(!defined('IN_COMSENZ')) {
	exit('Access Denied');
}

define('SOFT_NAME', 'Discuz!');
define('SOFT_VERSION', '7.2');
define('SOFT_RELEASE', '20091126');
define('INSTALL_LANG', 'SC_UTF8');
define('DZUCFULL', false);

define('CONFIG', ROOT_PATH.'./config.inc.php');

$sqlfile = ROOT_PATH.'./install/discuz.sql';

$lockfile = ROOT_PATH.'./forumdata/install.lock';

include CONFIG;

//$charset = strtolower(str_replace('_', '', strstr(INSTALL_LANG, '_')));
//$charset = $charset == 'utf8' ? 'utf-8' : $charset;
define('CHARSET', $charset);
define('DBCHARSET', !$dbcharset && in_array(strtolower($charset), array('gbk', 'big5', 'utf-8')) ? str_replace('-', '', $charset) : $dbcharset);

define('ORIG_TABLEPRE', 'cdb_');

define('METHOD_UNDEFINED', 255);
define('ENV_CHECK_RIGHT', 0);
define('ERROR_CONFIG_VARS', 1);
define('SHORT_OPEN_TAG_INVALID', 2);
define('INSTALL_LOCKED', 3);
define('DATABASE_NONEXISTENCE', 4);
define('PHP_VERSION_TOO_LOW', 5);
define('MYSQL_VERSION_TOO_LOW', 6);
define('UC_URL_INVALID', 7);
define('UC_DNS_ERROR', 8);
define('UC_URL_UNREACHABLE', 9);
define('UC_VERSION_INCORRECT', 10);
define('UC_DBCHARSET_INCORRECT', 11);
define('UC_API_ADD_APP_ERROR', 12);
define('UC_ADMIN_INVALID', 13);
define('UC_DATA_INVALID', 14);
define('DBNAME_INVALID', 15);
define('DATABASE_ERRNO_2003', 16);
define('DATABASE_ERRNO_1044', 17);
define('DATABASE_ERRNO_1045', 18);
define('DATABASE_CONNECT_ERROR', 19);
define('TABLEPRE_INVALID', 20);
define('CONFIG_UNWRITEABLE', 21);
define('ADMIN_USERNAME_INVALID', 22);
define('ADMIN_EMAIL_INVALID', 25);
define('ADMIN_EXIST_PASSWORD_ERROR', 26);
define('ADMININFO_INVALID', 27);
define('LOCKFILE_NO_EXISTS', 28);
define('TABLEPRE_EXISTS', 29);
define('ERROR_UNKNOW_TYPE', 30);
define('ENV_CHECK_ERROR', 31);
define('UNDEFINE_FUNC', 32);
define('MISSING_PARAMETER', 33);
define('LOCK_FILE_NOT_TOUCH', 34);

$func_items = array('mysql_connect', 'fsockopen', 'gethostbyname', 'file_get_contents', 'xml_parser_create');

$env_items = array
(
	'os' => array('c' => 'PHP_OS', 'r' => 'notset', 'b' => 'unix'),
	'php' => array('c' => 'PHP_VERSION', 'r' => '4.0', 'b' => '5.0'),
	'attachmentupload' => array('r' => 'notset', 'b' => '2M'),
	'gdversion' => array('r' => '1.0', 'b' => '2.0'),
	'diskspace' => array('r' => '10M', 'b' => 'notset'),
);

$dirfile_items = array
(
	'config' => array('type' => 'file', 'path' => './config.inc.php'),
	'attach' => array('type' => 'dir', 'path' => './attachments'),
	'forumdata' => array('type' => 'dir', 'path' => './forumdata'),
	'cache' => array('type' => 'dir', 'path' => './forumdata/cache'),
	'ftemplates' => array('type' => 'dir', 'path' => './forumdata/templates'),
	'threadcache' => array('type' => 'dir', 'path' => './forumdata/threadcaches'),
	'logs' => array('type' => 'dir', 'path' => './forumdata/logs'),
	'uccache' => array('type' => 'dir', 'path' => './uc_client/data/cache')
);


$form_app_reg_items = array
(
	'ucenter' => array
	(
		'ucurl' => array('type' => 'text', 'required' => 1, 'reg' => '/^https?:\/\//', 'value' => array('type' => 'var', 'var' => 'ucapi')),
		'ucip' => array('type' => 'text', 'required' => 0, 'reg' => '/^\d+\.\d+\.\d+\.\d+$/'),
		'ucpw' => array('type' => 'password', 'required' => 1, 'reg' => '/^.*$/')
	),
	'siteinfo' => array
	(
		'sitename' => array('type' => 'text', 'required' => 1, 'reg' => '/^.*$/', 'value' => array('type' => 'constant', 'var' => 'SOFT_NAME')),
		'siteurl' => array('type' => 'text', 'required' => 1, 'reg' => '/^https?:\/\//', 'value' => array('type' => 'var', 'var' => 'default_appurl'))
	)
);

$form_db_init_items = array
(
	'dbinfo' => array
	(
		'dbhost' => array('type' => 'text', 'required' => 1, 'reg' => '/^.+$/', 'value' => array('type' => 'var', 'var' => 'dbhost')),
		'dbname' => array('type' => 'text', 'required' => 1, 'reg' => '/^.+$/', 'value' => array('type' => 'var', 'var' => 'dbname')),
		'dbuser' => array('type' => 'text', 'required' => 0, 'reg' => '/^.*$/', 'value' => array('type' => 'var', 'var' => 'dbuser')),
		'dbpw' => array('type' => 'password', 'required' => 0, 'reg' => '/^.*$/', 'value' => array('type' => 'var', 'var' => 'dbpw')),
		'tablepre' => array('type' => 'text', 'required' => 0, 'reg' => '/^.*+/', 'value' => array('type' => 'var', 'var' => 'tablepre')),
		'adminemail' => array('type' => 'text', 'required' => 1, 'reg' => '/@/', 'value' => array('type' => 'var', 'var' => 'adminemail')),
	),
	'admininfo' => array
	(
		'username' => array('type' => 'text', 'required' => 1, 'reg' => '/^.*$/', 'value' => array('type' => 'constant', 'var' => 'admin')),
		'password' => array('type' => 'password', 'required' => 1, 'reg' => '/^.*$/'),
		'password2' => array('type' => 'password', 'required' => 1, 'reg' => '/^.*$/'),
		'email' => array('type' => 'text', 'required' => 1, 'reg' => '/@/', 'value' => array('type' => 'var', 'var' => 'adminemail')),
		'testdata' => array('type' => 'checkbox', 'required' => 0, 'reg' => '/^1$/', 'value' => array('type' => 'constant', 'var' => '1')),
	)
);

?>