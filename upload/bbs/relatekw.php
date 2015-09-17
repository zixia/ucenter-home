<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: relatekw.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

error_reporting(0);
set_magic_quotes_runtime(0);

define('DISCUZ_ROOT', './');
define('IN_DISCUZ', TRUE);
define('NOROBOT', TRUE);

require_once './forumdata/cache/cache_settings.php';
if(!$_DCACHE['settings']['tagstatus']) {
	exit;
}

require_once './config.inc.php';
require_once './include/global.func.php';

if($tid = @intval($_GET['tid'])) {
	require_once './include/db_'.$database.'.class.php';
	$db = new dbstuff;
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
	$db->select_db($dbname);
	unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

	if($db->result_first("SELECT count(*) FROM {$tablepre}threadtags WHERE tid='$tid'")) {
		exit;
	}
	$query = $db->query("SELECT subject, message FROM {$tablepre}posts WHERE tid='$tid' AND first='1'");
	$data = $db->fetch_array($query);
	$subject = $data['subject'];
	$message = cutstr($data['message'], 500, '');
} else {
	$subject = $_GET['subjectenc'];
	$message = $_GET['messageenc'];
}

$subjectenc = rawurlencode(strip_tags($subject));
$messageenc = rawurlencode(strip_tags(preg_replace("/\[.+?\]/U", '', $message)));
$data = @implode('', file("http://keyword.discuz.com/related_kw.html?ics=$charset&ocs=$charset&title=$subjectenc&content=$messageenc"));

if($data) {

	if(PHP_VERSION > '5' && $charset != 'utf-8') {
		require_once DISCUZ_ROOT.'./include/chinese.class.php';
		$chs = new Chinese('utf-8', $charset);
	}

	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $values, $index);
	xml_parser_free($parser);

	$kws = array();

	foreach($values as $valuearray) {
		if($valuearray['tag'] == 'kw' || $valuearray['tag'] == 'ekw') {
			$kws[] = !empty($chs) ? $chs->convert(trim($valuearray['value'])) : trim($valuearray['value']);
		}
	}

	$return = '';
	if($kws) {
		foreach($kws as $kw) {
			$kw = htmlspecialchars($kw);
			$return .= $kw.' ';
		}
		$return = htmlspecialchars($return);
	}

	if(!$tid) {
		$inajax = 1;
		include template('relatekw');
	} else {
		if($_DCACHE['settings']['tagstatus'] && $kws) {
			require_once DISCUZ_ROOT.'/forumdata/cache/cache_censor.php';
			$tagcount = 0;
			foreach($kws as $tagname) {
				$tagname = trim(empty($_DCACHE['censor']['filter']) ? $tagname : preg_replace($_DCACHE['censor']['filter']['find'], $_DCACHE['censor']['filter']['replace'], $tagname));
				if(preg_match('/^([\x7f-\xff_-]|\w|\s){3,20}$/', $tagname)) {
					$query = $db->query("SELECT closed FROM {$tablepre}tags WHERE tagname='$tagname'");
					if($db->num_rows($query)) {
						if(!$tagstatus = $db->result($query, 0)) {
							$db->query("UPDATE {$tablepre}tags SET total=total+1 WHERE tagname='$tagname'", 'UNBUFFERED');
						}
					} else {
						$db->query("INSERT INTO {$tablepre}tags (tagname, closed, total)
							VALUES ('$tagname', 0, 1)", 'UNBUFFERED');
						$tagstatus = 0;
					}
					if(!$tagstatus) {
						$db->query("INSERT {$tablepre}threadtags (tagname, tid) VALUES ('$tagname', $tid)", 'UNBUFFERED');
					}
					$tagcount++;
					if($tagcount > 4) {
						break;
					}
				}
			}
		}
	}
}

?>