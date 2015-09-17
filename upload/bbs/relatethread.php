<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: relatethread.php 18819 2009-07-23 10:38:43Z liuqiang $
*/

error_reporting(0);
set_magic_quotes_runtime(0);

define('DISCUZ_ROOT', './');
define('IN_DISCUZ', TRUE);
define('NOROBOT', TRUE);

require_once './forumdata/cache/cache_settings.php';

$qihoo = $_DCACHE['settings']['qihoo'];
if(!$qihoo['relate']['bbsnum']) {
	exit;
}

$_SERVER = empty($_SERVER) ? $HTTP_SERVER_VARS : $_SERVER;
$_GET = empty($_GET) ? $HTTP_GET_VARS : $_GET;

$site = $_SERVER['HTTP_HOST'];
$subjectenc = rawurlencode($_GET['subjectenc']);
$tags = explode(' ',trim($_GET['tagsenc']));
$tid = intval($_GET['tid']);

require_once './config.inc.php';
if($_GET['verifykey'] <> md5($_DCACHE['settings']['authkey'].$tid.$subjectenc.$charset.$site)) {
	exit();
}

$tshow = !$qihoo['relate']['position'] ? 'mid' : 'bot';
$intnum = intval($qihoo['relate']['bbsnum']);
$extnum = intval($qihoo['relate']['webnum']);
$exttype = $qihoo['relate']['type'];
$up = intval($_GET['qihoo_up']);
$data = @implode('', file("http://related.code.qihoo.com/related.html?title=$subjectenc&ics=$charset&ocs=$charset&site=$site&sort=pdate&tshow=$tshow&intnum=$intnum&extnum=$extnum&exttype=$exttype&up=$up"));

if($data) {
	$timestamp = time();
	$chs = '';

	if(PHP_VERSION > '5' && $charset != 'utf-8') {
		require_once DISCUZ_ROOT.'./include/chinese.class.php';
		$chs = new Chinese('utf-8', $charset);
	}

	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $values, $index);
	xml_parser_free($parser);

	$xmldata = array('chanl', 'fid', 'title', 'tid', 'author', 'pdate', 'rdate', 'rnum', 'vnum', 'insite');
	$relatedthreadlist = $keywords = array();
	$nextuptime = 0;
	foreach($index as $tag => $valuearray) {
		if(in_array($tag, $xmldata)) {
			foreach($valuearray as $key => $value) {
				if($values[$index['title'][$key]]['value']) {
					$relatedthreadlist[$key][$tag] = !empty($chs) ? $chs->convert(trim($values[$value]['value'])) : trim($values[$value]['value']);
					$relatedthreadlist[$key]['fid'] = !$values[$index['fid'][$key]]['value'] ? preg_replace("/(.+?)\/forum\-(\d+)\-(\d+)\.html/", "\\2", trim($values[$index['curl'][$key]]['value'])) : trim($values[$index['fid'][$key]]['value']);
					$relatedthreadlist[$key]['tid'] = !$values[$index['tid'][$key]]['value'] ? preg_replace("/(.+?)\/thread\-(\d+)\-(\d+)-(\d+)\.html/", "\\2", trim($values[$index['surl'][$key]]['value'])) : trim($values[$index['tid'][$key]]['value']);
				}
			}
		} elseif(in_array($tag, array('kw', 'ekw'))) {
			$type = $tag == 'kw' ? 'general' : 'trade';
			foreach($valuearray as $value) {
				$keywords[$type][] = !empty($chs) ? $chs->convert(trim($values[$value]['value'])) : trim($values[$value]['value']);
			}
		} elseif($tag == 'nextuptime') {
			$nextuptime = $values[$index['nextuptime'][0]]['value'];
		} elseif($tag == 'keep' && intval($values[$index['keep'][0]]['value'])) {
			exit;
		}
	}

	$generalnew = array();
	if($keywords['general']) {
		$searchkeywords = rawurlencode(implode(' ', $keywords['general']));
		foreach($keywords['general'] as $keyword) {
			$generalnew[] = $keyword;
			if(!in_array($keyword, $tags)) {
				$relatedkeywords .= '<a href="search.php?srchtype=qihoo&amp;srchtxt='.rawurlencode($keyword).'&amp;searchsubmit=yes" target="_blank"><strong><font color="red">'.$keyword.'</font></strong></a>&nbsp;';
			}
		}
	}
	$keywords['general'] = $generalnew;

	$threadlist = array();
	if($relatedthreadlist) {
		foreach($relatedthreadlist as $key => $relatedthread) {
			if($relatedthread['insite'] == 1) {
				$threadlist['bbsthread'][] = $relatedthread;
			} elseif($qihoo['relate']['webnum']) {
				if(empty($qihoo['relate']['banurl']) || !preg_match($qihoo['relate']['banurl'], $relatedthread['tid'])) {
					$threadlist['webthread'][] = $relatedthread;
				}
			}
		}
		$threadlist['bbsthread'] = $threadlist['bbsthread'] ? array_slice($threadlist['bbsthread'], 0, $qihoo['relate']['bbsnum']) : array();
		$threadlist['webthread'] = $threadlist['webthread'] ? array_slice($threadlist['webthread'], 0, $qihoo['relate']['bbsnum'] - count($threadlist['bbsthread'])) : array();
		$relatedthreadlist = array_merge($threadlist['bbsthread'], $threadlist['webthread']);
	}

	$keywords['general'] = $keywords['general'][0] ? implode("\t", $keywords['general']) : '';
	$keywords['trade'] = $keywords['trade'][0] ? implode("\t", $keywords['trade']) : '';
	$relatedthreads = $relatedthreadlist ? addslashes(serialize($relatedthreadlist)) : '';
	$expiration = $nextuptime ? $nextuptime : $timestamp + 86400;

	require_once './include/db_'.$database.'.class.php';
	$db = new dbstuff;
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
	$db->select_db($dbname);
	unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

	$db->query("REPLACE INTO {$tablepre}relatedthreads (tid, type, expiration, keywords, relatedthreads)
		VALUES ('$tid', 'general', '$expiration', '$keywords[general]', '$relatedthreads')", 'UNBUFFERED');
	if($keywords['trade']) {
		$db->query("REPLACE INTO {$tablepre}relatedthreads (tid, type, expiration, keywords, relatedthreads)
			VALUES ('$tid', 'trade', '$expiration', '$keywords[trade]', '$relatedthreads')", 'UNBUFFERED');
	}
}

?>