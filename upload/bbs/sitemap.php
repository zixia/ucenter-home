<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: sitemap.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

error_reporting(0);
define('IN_DISCUZ', TRUE);
define('DISCUZ_ROOT', './');

if(PHP_VERSION < '4.1.0') {
	$_GET = &$HTTP_GET_VARS;
	$_SERVER = &$HTTP_SERVER_VARS;
}

require_once DISCUZ_ROOT.'./config.inc.php';
require_once DISCUZ_ROOT.'./include/global.func.php';
require_once DISCUZ_ROOT.'./include/db_'.$database.'.class.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_settings.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';

$maxitemnum = 500;
$timestamp = time();
$PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
$boardurl = 'http://'.$_SERVER['HTTP_HOST'].substr($PHP_SELF, 0, strrpos($PHP_SELF, '/') + 1);

$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

if(!$_DCACHE['settings']['baidusitemap']) {
	exit('Baidu Sitemaps is closed!');
}
$sitemapfile = DISCUZ_ROOT.'./forumdata/sitemap.xml';
$xmlfiletime = @filemtime($sitemapfile);

header("Content-type: application/xml");

$xmlcontent = "<?xml version=\"1.0\" encoding=\"$charset\"?>\n".
	"<document xmlns:bbs=\"http://www.baidu.com/search/bbs_sitemap.xsd\">\n";

if($timestamp - $xmlfiletime >= $_DCACHE['settings']['baidusitemap_life'] * 3600) {
	$groupid = 7;
	$extgroupids = '';
	$xmlfiletime = $timestamp - $_DCACHE['settings']['baidusitemap_life'] * 3600;
	$fidarray = array();

	foreach($_DCACHE['forums'] as $fid => $forum) {
		if(sitemapforumperm($forum)) {
			$fidarray[] = $fid;
		}
	}

	$query = $db->query("SELECT tid, fid, subject, dateline, lastpost, replies, views, digest 
		FROM {$tablepre}threads 
		WHERE dateline > $xmlfiletime AND fid IN (".implode(',', $fidarray).") AND displayorder >= 0
		LIMIT $maxitemnum");

	$xmlcontent .= "	<webSite>$boardurl</webSite>\n".
		"	<webMaster>$adminemail</webMaster>\n".
		"	<updatePeri>".$_DCACHE['settings']['baidusitemap_life']."</updatePeri>\n".
		"	<updatetime>".gmdate('Y-m-d H:i:s', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600)."</updatetime>\n".
		"	<version>Discuz! {$_DCACHE['settings']['version']}</version>\n";

	while($thread = $db->fetch_array($query)) {
		$xmlcontent .= "	<item>\n".
			"		<link>".(!$_DCACHE['settings']['rewritestatus'] ? "{$boardurl}viewthread.php?tid=$thread[tid]" : "{$boardurl}thread-$thread[tid]-1-1.html")."</link>\n".
			"		<title>".dhtmlspecialchars($thread['subject'])."</title>\n".
			"		<pubDate>".gmdate('Y-m-d H:i:s', $thread['dateline'] + $_DCACHE['settings']['timeoffset'] * 3600)."</pubDate>\n".
			"		<bbs:lastDate>".gmdate('Y-m-d H:i:s', $thread['lastpost'] + $_DCACHE['settings']['timeoffset'] * 3600)."</bbs:lastDate>\n".
			"		<bbs:reply>$thread[replies]</bbs:reply>\n".
			"		<bbs:hit>$thread[views]</bbs:hit>\n".
			"		<bbs:boardid>$thread[fid]</bbs:boardid>\n".
			"		<bbs:pick>".(empty($thread['digest']) ? 0 : 1)."</bbs:pick>\n".
			"	</item>\n";
	}
	
	$xmlcontent .= "</document>";
	if($fp = @fopen($sitemapfile, 'w')) {
		fwrite($fp, $xmlcontent);
		flock($fp, 2);
		fclose($fp);
	}
	
	echo $xmlcontent;
	
} else {
	
	@readfile($sitemapfile);
	
}

function sitemapforumperm($forum) {
	return $forum['type'] != 'group' && (!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])));
}
?>