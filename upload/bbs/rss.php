<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: rss.php 20150 2009-09-21 01:46:30Z monkey $
*/


//error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(0);

define('IN_DISCUZ', TRUE);
define('DISCUZ_ROOT', '');

$timestamp = time();
$fidarray = array();

if(PHP_VERSION < '4.1.0') {
	$_GET = &$HTTP_GET_VARS;
	$_SERVER = &$HTTP_SERVER_VARS;
}

require_once DISCUZ_ROOT.'./config.inc.php';
require_once DISCUZ_ROOT.'./include/global.func.php';
require_once DISCUZ_ROOT.'./include/db_'.$database.'.class.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_settings.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
require_once DISCUZ_ROOT.'./forumdata/cache/style_'.intval($_DCACHE['settings']['styleid']).'.php';

if(!$_DCACHE['settings']['rssstatus']) {
	exit('RSS Disabled');
}

$ttl = $_DCACHE['settings']['rssttl'] ? $_DCACHE['settings']['rssttl']: 30;
$num = 20;

$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

$groupid = 7;
$discuz_uid = 0;
$discuz_user = $discuz_pw = $discuz_secques = '';

if(!empty($_GET['auth'])) {
	list($uid, $fid, $auth) = explode("\t", authcode($_GET['auth'], 'DECODE', md5($_DCACHE['settings']['authkey'])));
	$member = $db->fetch_first("SELECT uid AS discuz_uid, username AS discuz_user, password AS discuz_pw, secques AS discuz_secques, groupid
		FROM {$tablepre}members WHERE uid='".intval($uid)."'");
	if($member) {
		if($auth == substr(md5($member['discuz_pw'].$member['discuz_secques']), 0, 8)) {
			extract($member);
		}
	}
}

$PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
$boardurl = 'http://'.$_SERVER['HTTP_HOST'].substr($PHP_SELF, 0, strrpos($PHP_SELF, '/') + 1);

$bbname = dhtmlspecialchars(strip_tags($_DCACHE['settings']['bbname']));
$rssfid = empty($_GET['fid']) ? 0 : intval($_GET['fid']);
$forumname = '';

if(empty($rssfid)) {
	foreach($_DCACHE['forums'] as $fid => $forum) {
		if(rssforumperm($forum)) {
			$fidarray[] = $fid;
		}
	}
} else {
	$forum = isset($_DCACHE['forums'][$rssfid]) && $_DCACHE['forums'][$rssfid]['type'] != 'group' ? $_DCACHE['forums'][$rssfid] : array();

	if($forum && rssforumperm($forum)) {
		$fidarray = array($rssfid);
		$forumname = dhtmlspecialchars($_DCACHE['forums'][$rssfid]['name']);
	} else {
		exit('Specified forum not found');
	}
}

dheader("Content-type: application/xml");
echo 	"<?xml version=\"1.0\" encoding=\"".$charset."\"?>\n".
	"<rss version=\"2.0\">\n".
	"  <channel>\n".
	(count($fidarray) > 1 ?
		"    <title>$bbname</title>\n".
		"    <link>{$boardurl}".$_DCACHE[settings][indexname]."</link>\n".
		"    <description>Latest $num threads of all forums</description>\n"
		:
		"    <title>$bbname - $forumname</title>\n".
		"    <link>{$boardurl}forumdisplay.php?fid=$rssfid</link>\n".
		"    <description>Latest $num threads of $forumname</description>\n"
	).
	"    <copyright>Copyright(C) $bbname</copyright>\n".
	"    <generator>Discuz! Board by Comsenz Inc.</generator>\n".
	"    <lastBuildDate>".gmdate('r', $timestamp)."</lastBuildDate>\n".
	"    <ttl>$ttl</ttl>\n".
	"    <image>\n".
	"      <url>{$boardurl}images/logo.gif</url>\n".
	"      <title>$bbname</title>\n".
	"      <link>{$boardurl}</link>\n".
	"    </image>\n";

if($fidarray) {
	$query = $db->query("SELECT * FROM {$tablepre}rsscaches WHERE fid IN (".implode(',', $fidarray).") ORDER BY dateline DESC LIMIT $num");
	if($db->num_rows($query)) {
		while($thread = $db->fetch_array($query)) {
			if($timestamp - $thread['lastupdate'] > $ttl * 60) {
				updatersscache();
				break;
			} else {
				echo 	"    <item>\n".
					"      <title>".dhtmlspecialchars($thread['subject'])."</title>\n".
					"      <link>{$boardurl}viewthread.php?tid=$thread[tid]</link>\n".
					"      <description><![CDATA[$thread[description]]]></description>\n".
					"      <category>".dhtmlspecialchars($thread['forum'])."</category>\n".
					"      <author>".dhtmlspecialchars($thread['author'])."</author>\n".
					"      <pubDate>".gmdate('r', $thread['dateline'])."</pubDate>\n".
					"    </item>\n";
			}
		}
	} else {
		updatersscache();
	}
}

echo 	"  </channel>\n".
	"</rss>";

function rssforumperm($forum) {
	global $groupid, $discuz_uid;
	return $forum['type'] != 'group' && (!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || $accessmasks);
}

function updatersscache() {
	global $_DCACHE, $timestamp, $num, $tablepre, $db;
	$db->query("DELETE FROM {$tablepre}rsscaches");
	require_once DISCUZ_ROOT.'./include/post.func.php';
	foreach($_DCACHE['forums'] as $fid => $forum) {
		if($forum['type'] != 'group') {
			$query = $db->query("SELECT t.tid, t.readperm, t.price, t.author, t.dateline, t.subject, p.message, p.status
				FROM {$tablepre}threads t
				LEFT JOIN {$tablepre}posts p ON p.tid=t.tid AND p.first='1'
				WHERE t.fid='$fid' AND t.displayorder>='0'
				ORDER BY t.dateline DESC LIMIT $num");
			while($thread = $db->fetch_array($query)) {
				$forum['name'] = addslashes($forum['name']);
				$thread['author'] = $thread['author'] != '' ? addslashes($thread['author']) : 'Anonymous';
				$thread['subject'] = addslashes($thread['subject']);
				$thread['description'] = $thread['readperm'] > 0 || $thread['price'] > 0 || $thread['status'] & 1 ? '' : addslashes(nl2br(messagecutstr($thread['message'], 250)));
				$db->query("REPLACE INTO {$tablepre}rsscaches (lastupdate, fid, tid, dateline, forum, author, subject, description)
					VALUES ('$timestamp', '$fid', '$thread[tid]', '$thread[dateline]', '$forum[name]', '$thread[author]', '$thread[subject]', '$thread[description]')");
			}
		}
	}
}

?>