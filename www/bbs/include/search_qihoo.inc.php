<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$RCSfile: search_qihoo.inc.php,v $
$Revision: 1.8 $
$Date: 2007/08/06 09:54:48 $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(empty($srchtxt) && empty($srchuname)) {
	showmessage('search_invalid', 'search.php');
}

$keywordlist = '';
foreach(explode("\n", trim($qihoo_keyword)) as $key => $keyword) {
	$keywordlist .= $comma.trim($keyword);
	$comma = '|';
	if(strlen($keywordlist) >= 100) {
		break;
	}
}

if($orderby == 'lastpost') {
	$orderby = 'rdate';
} elseif($orderby == 'dateline') {
	$orderby = 'pdate';
} else {
	$orderby = '';
}

$stype = empty($stype) ? '' : ($stype == 2 ? 'author' : 'title');

$url = 'http://search.qihoo.com/usearch.html?site='.rawurlencode(site()).
	'&kw='.rawurlencode($srchtxt).
	'&ics='.$charset.
	'&ocs='.$charset.
	($orderby ? '&sort='.$orderby : '').
	($srchfid ? '&chanl='.rawurlencode($_DCACHE['forums'][$srchfid]['name']) : '').
	'&bbskw='.rawurlencode($keywordlist).
	'&summary='.$qihoo['summary'].
	'&stype='.$stype.
	'&count='.$tpp.
	'&fw=dz&SITEREFER='.rawurlencode($boardurl);

dheader("Location: $url");

?>