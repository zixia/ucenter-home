<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: index.php 20890 2009-10-29 01:12:33Z zhaoxiongfei $
*/

$kw_spiders		= 'Bot|Crawl|Spider';
			// keywords regular expression of search engine spiders

$kw_browsers		= 'MSIE|Netscape|Opera|Konqueror|Mozilla';
			// keywords regular expression of Internet browsers

$kw_searchengines	= 'google|yahoo|msn|baidu|yisou|sogou|iask|zhongsou|sohu|sina|163';
			// keywords regular expression of search engine names

error_reporting(0);
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

ob_start();

define('DISCUZ_ROOT', '../');
define('IN_DISCUZ', TRUE);
define('CURSCRIPT', 'archiver');

require_once '../forumdata/cache/cache_settings.php';

if(!$_DCACHE['settings']['archiverstatus']) {
	exit('Sorry, Discuz! Archiver is not available.');
} elseif($_DCACHE['settings']['bbclosed']) {
	exit('Sorry, the bulletin board has been closed temporarily.');
}

require_once '../config.inc.php';
require_once '../include/db_'.$database.'.class.php';
require_once '../templates/default/archiver.lang.php';
require_once '../forumdata/cache/cache_forums.php';
require_once '../forumdata/cache/cache_archiver.php';

$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
$db->select_db($dbname);

if(!function_exists('loadmultiserver')) {
	function loadmultiserver($type = '') {
		global $db, $dbcharset, $multiserver;
		$type = empty($type) && defined('CURSCRIPT') ? CURSCRIPT : $type;
		static $sdb = null;
		if($type && !empty($multiserver['enable'][$type])) {
			if(!is_a($sdb, 'dbstuff')) $sdb = new dbstuff();
			if($sdb->link > 0) {
				return $sdb;
			} elseif($sdb->link === null && (!empty($multiserver['slave']['dbhost']) || !empty($multiserver[$type]['dbhost']))) {
				$setting = !empty($multiserver[$type]['host']) ? $multiserver[$type] : $multiserver['slave'];
				$sdb->connect($setting['dbhost'], $setting['dbuser'], $setting['dbpw'], $setting['dbname'], $setting['pconnect'], false, $dbcharset);
				if($sdb->link) {
					return $sdb;
				} else {
					$sdb->link = -32767;
				}
			}
		}
		return $db;
	}
}
$sdb = loadmultiserver();

unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

$_SERVER = empty($_SERVER) ? $HTTP_SERVER_VARS : $_SERVER;
$PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
$boardurl = 'http://'.$_SERVER['HTTP_HOST'].substr($PHP_SELF, 0, strpos($PHP_SELF, 'archiver/'));

$groupid = 7;
$extgroupids = '';

$fid = $page = $tid = 0;
$qm = $_DCACHE['settings']['rewritestatus'] & 16 ? '' : '?';
$fullversion = array('title' => $_DCACHE['settings']['bbname'], 'link' => $_DCACHE['settings']['indexname']);
$querystring = preg_replace("/\.html$/i", '', trim($_SERVER['QUERY_STRING']));
if($querystring) {
	$queryparts = explode('-', $querystring);
	$lastpart = '';
	foreach($queryparts as $querypart) {
		if(empty($lastpart)) {
			$lastpart = in_array($querypart, array('fid', 'page', 'tid')) ? $querypart : '';
		} else {
			$$lastpart = intval($querypart);
			$lastpart = '';
		}
	}
}

$navtitle = $meta_contentadd = $advlist = null;

if($tid) {
	$action = 'thread';
	$forward = 'viewthread.php?tid='.$tid;
} elseif($fid) {
	$action = 'forum';
	$forward = 'forumdisplay.php?fid='.$fid;
} else {
	$action = 'index';
	$forward = 'index.php';
}

if($_DCACHE['settings']['archiverstatus'] != 1 && !preg_match("/($kw_spiders)/i", $_SERVER['HTTP_USER_AGENT']) &&
	(($_DCACHE['settings']['archiverstatus'] == 2 && preg_match("/($kw_searchengines)/", $_SERVER['HTTP_REFERER'])) ||
	($_DCACHE['settings']['archiverstatus'] == 3 && preg_match("/($kw_browsers)/", $_SERVER['HTTP_USER_AGENT'])))) {
	header("Location: $boardurl$forward");
	exit;
}

if(($globaladvs = $_DCACHE['settings']['globaladvs']) || !empty($_DCACHE['advs'])) {
        $redirectadvs = $_DCACHE['settings']['redirectadvs'];
	require_once '../include/advertisements.inc.php';
}

$headernav = '<a href="archiver/"><strong>'.$_DCACHE['settings']['bbname'].'</strong></a> ';
$headerbanner = !empty($advlist['headerbanner']) ? $advlist[headerbanner] : '';

require_once "./include/$action.inc.php";

showfooter();

function multi($total, $page, $perpage, $link) {
	$pages = @ceil($total / $perpage) + 1;
	$pagelink = '';
	if($pages > 1) {
		$pagelink .= "{$GLOBALS[lang][page]}: \n";
		$pagestart = $page - 10 < 1 ? 1 : $page - 10;
		$pageend = $page + 10 >= $pages ? $pages : $page + 10;
		for($i = $pagestart; $i < $pageend; $i++) {
			$pagelink .= ($i == $page ? "<strong>[$i]</strong>" : "<a href=archiver/$link-page-$i.html>$i</a>")." \n";
		}
	}
	return $pagelink;
}

function forumperm($viewperm) {
	return (empty($viewperm) || ($viewperm && strstr($viewperm, "\t7\t")));
}

function forumformulaperm($formula) {
	$formula = unserialize($formula);$formula = $formula[1];
	if(!$formula) {
		return TRUE;
	}
	$_DSESSION = array();
	@eval("\$formulaperm = ($formula) ? TRUE : FALSE;");
	return $formulaperm;
}

function showheader() {
	header('Content-Type: text/html; charset='.$charset);
	global $boardurl, $_DCACHE, $charset, $navtitle, $headerbanner, $headernav;
	echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<base href="{$boardurl}" />
<title>{$navtitle} {$_DCACHE['settings']['bbname']} {$_DCACHE['settings']['seotitle']} - Powered by Discuz! Archiver</title>
{$_DCACHE['settings']['seohead']}
<meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
<meta name="keywords" content="Discuz!,Board,Comsenz,forums,bulletin board,{$_DCACHE['settings']['seokeywords']}" />
<meta name="description" content="{$meta_contentadd} {$_DCACHE['settings']['bbname']} {$_DCACHE['settings']['seodescription']} - Discuz! Archiver" />
<meta name="generator" content="Discuz! Archiver {$_DCACHE['settings']['version']}" />
<meta name="author" content="Discuz! Team & Comsenz UI Team" />
<meta name="copyright" content="2001-2009 Comsenz Inc." />
<style type="text/css">
	body {font-family: Verdana;FONT-SIZE: 12px;MARGIN: 0;color: #000000;background: #ffffff;}
	img {border:0;}
	li {margin-top: 8px;}
	.page {padding: 4px; border-top: 1px #EEEEEE solid}
	.author {background-color:#EEEEFF; padding: 6px; border-top: 1px #ddddee solid}
	#nav, #content, #footer {padding: 8px; border: 1px solid #EEEEEE; clear: both; width: 95%; margin: auto; margin-top: 10px;}
</style>
</head>
<body vlink="#333333" link="#333333">
<h2 style="text-align: center; margin-top: 20px">{$_DCACHE[settings][bbname]}'s Archiver </h2>
<center>{$headerbanner}</center>
<div id="nav">$headernav</div>
<div id="content">

EOT;

}

function showfooter() {

	global $lang, $fullversion, $advlist, $_DCACHE;

	echo "</div><div id=\"footer\">{$lang['full_version']}: <strong><a href=\"{$fullversion['link']}\" target=\"_blank\">{$fullversion['title']}</a></strong></div>";

	empty($advlist['footerbanner1']) or print '<div class="archiver_banner">'.$advlist[footerbanner1].'</div>';
	empty($advlist['footerbanner2']) or print '<div class="archiver_banner">'.$advlist[footerbanner2].'</div>';
	empty($advlist['footerbanner3']) or print '<div class="archiver_banner">'.$advlist[footerbanner3].'</div>';

	echo <<<EOT
<br /><center>
<div style="text-algin: center; font-size: 11px">Powered by <strong><a href="http://www.discuz.net" target="_blank">Discuz! Archiver</a></strong> {$_DCACHE['settings']['version']}&nbsp;
&copy; 2001-2009 <a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a><br /><br /></div>
</center>
</body>
</html>
EOT;
}

function getstatus($status, $position) {
	$t = $status & pow(2, $position - 1) ? 1 : 0;
	return $t;
}
?>