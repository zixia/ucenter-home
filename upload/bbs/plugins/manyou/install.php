<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: install.php 20822 2009-10-26 10:20:57Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

CREATE TABLE IF NOT EXISTS cdb_myapp (
  `appid` mediumint(8) unsigned NOT NULL default '0',
  `appname` varchar(60) NOT NULL default '',
  `narrow` tinyint(1) NOT NULL default '0',
  `flag` tinyint(1) NOT NULL default '0',
  `version` mediumint(8) unsigned NOT NULL default '0',
  `displaymethod` tinyint(1) NOT NULL default '0',
  `displayorder` smallint(6) unsigned NOT NULL default '0',
  PRIMARY KEY  (`appid`),
  KEY `flag` (`flag`,`displayorder`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS cdb_userapp (
  `appid` mediumint(8) unsigned NOT NULL default '0',
  `appname` varchar(60) NOT NULL default '',
  `uid` mediumint(8) unsigned NOT NULL default '0',
  `privacy` tinyint(1) NOT NULL default '0',
  `allowsidenav` tinyint(1) NOT NULL default '0',
  `allowfeed` tinyint(1) NOT NULL default '0',
  `allowprofilelink` tinyint(1) NOT NULL default '0',
  `narrow` tinyint(1) NOT NULL default '0',
  `profilelink` text NOT NULL,
  `myml` text NOT NULL,
  `displayorder` smallint(6) unsigned NOT NULL default '0',
  KEY `uid` (`uid`,`displayorder`),
  KEY `appid` (`appid`),
  KEY `allowfeed` (`allowfeed`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS cdb_myinvite (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `typename` varchar(100) NOT NULL default '',
  `appid` mediumint(8) NOT NULL default '0',
  `type` tinyint(1) NOT NULL default '0',
  `fromuid` mediumint(8) unsigned NOT NULL default '0',
  `touid` mediumint(8) unsigned NOT NULL default '0',
  `myml` text NOT NULL,
  `dateline` int(10) unsigned NOT NULL default '0',
  `hash` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `hash` (`hash`),
  KEY `uid` (`touid`,`dateline`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS cdb_mynotice (
  id mediumint(8) unsigned NOT NULL auto_increment,
  uid mediumint(8) unsigned NOT NULL default '0',
  `type` varchar(20) NOT NULL default '',
  `new` tinyint(1) NOT NULL default '0',
  authorid mediumint(8) unsigned NOT NULL default '0',
  author varchar(15) NOT NULL default '',
  note text NOT NULL,
  dateline int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY uid (uid,new,dateline)
) TYPE=MyISAM;

REPLACE INTO cdb_settings (variable, value) VALUES ('my_extcredit', '1');
REPLACE INTO cdb_settings (variable, value) VALUES ('my_feedpp', '50');
INSERT INTO cdb_prompttype (`key`, `name`, `script`) VALUES ('mynotice','{$installlang[manyou][prompttypenotice]}','userapp.php?script=notice');
INSERT INTO cdb_prompttype (`key`, `name`, `script`) VALUES ('myinvite','{$installlang[manyou][prompttypeinvite]}','userapp.php?script=notice&action=invite');

EOF;

runquery($sql);

$sql1 = <<<EOF

REPLACE INTO cdb_settings (variable, value) VALUES ('my_status', '0');
REPLACE INTO cdb_settings (variable, value) VALUES ('my_siteid', '');
REPLACE INTO cdb_settings (variable, value) VALUES ('my_sitekey', '');

EOF;

if(empty($_DCACHE['settings']['my_siteid'])) {
	runquery($sql1);
}
if(empty($_DCACHE['settings']['uchomeurl'])) {
	getstatinfo('manyou2dz', '20090727', $_DCACHE['settings']['funcsiteid'], $_DCACHE['settings']['funckey']);
}

function getstatinfo($mark, $version, $siteid, $key) {
	global $db, $tablepre, $dbcharset, $_DCACHE;
	$onlineip = $GLOBALS['onlineip'];
	$funcurl = 'http://stat.discuz.com/func/funcstat.php';
	$members = $_DCACHE['settings']['totalmembers'];
	$bbname = $_DCACHE['settings']['bbname'];
	$PHP_SELF = htmlspecialchars($_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
	$url = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].preg_replace("/\/+(api|archiver|wap)?\/*$/i", '', substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'))).'/');
	$posts = $db->result($db->query("SELECT count(*) FROM {$tablepre}posts"), 0);
	$hash = $bbname.$url.$mark.$version.$posts;
	$threads = $db->result($db->query("SELECT count(*) FROM {$tablepre}threads"), 0);
	$hash = md5($hash.$members.$threads.$email.$siteid.md5($key).'install');
	$q = "bbname=$bbname&url=$url&mark=$mark&version=$version&dz_ver=".DISCUZ_RELEASE."&posts=$posts&members=$members&threads=$threads&email=$email&siteid=$siteid&key=".md5($key)."&ip=$onlineip&time=".time()."&hash=$hash";
	$q=rawurlencode(base64_encode($q));
	$siteinfo = dfopen($funcurl."?action=install&q=$q");
	if(empty($siteinfo)) {
		$siteinfo = dfopen($funcurl."?action=install&q=$q");
	}
	if($siteinfo && preg_match("/^[a-zA-Z0-9_]+,[A-Z]+$/i", $siteinfo)) {
		include_once DISCUZ_ROOT.'./include/cache.func.php';
		$siteinfo = explode(',', $siteinfo);
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('funcsiteid', '$siteinfo[0]')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('funckey', '$siteinfo[1]')");
		updatecache('settings');
	}
}
$finish = TRUE;