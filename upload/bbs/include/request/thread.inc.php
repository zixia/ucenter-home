<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: thread.inc.php 20942 2009-11-02 04:12:16Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	if(!defined('TPLDIR')) {
		@include_once DISCUZ_ROOT.'./forumdata/cache/style_'.$_DCACHE['settings']['styleid'].'.php';
	}
	require_once DISCUZ_ROOT.'./include/post.func.php';

	if($tid = intval($settings['tid'])) {
		$thread = $db->fetch_first("SELECT subject, fid, special, price FROM {$tablepre}threads WHERE tid='$tid'");
		$fid = $thread['fid'];
		if($thread['special'] == 1) {
			$multiple = $db->result_first("SELECT multiple FROM {$tablepre}polls WHERE tid='$tid'");
			$optiontype = $multiple ? 'checkbox' : 'radio';
			$query = $db->query("SELECT polloptionid, polloption FROM {$tablepre}polloptions WHERE tid='$tid' ORDER BY displayorder");
			while($polloption = $db->fetch_array($query)) {
				$polloption['polloption'] = preg_replace("/\[url=(https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
					"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $polloption['polloption']);
				$polloptions[] = $polloption;
			}
		} elseif($thread['special'] == 2) {
			$query = $db->query("SELECT subject, price, aid, pid FROM {$tablepre}trades WHERE tid='$tid' ORDER BY displayorder DESC LIMIT 2");
			while($trade = $db->fetch_array($query)) {
				$trades[] = $trade;
			}
		} elseif($thread['special'] == 3) {
			$extcredits = $_DCACHE['settings']['extcredits'];
			$creditstransextra = $_DCACHE['settings']['creditstransextra'];
			$rewardend = $thread['price'] < 0;
			$rewardprice = abs($thread['price']);
			$message = messagecutstr($db->result_first("SELECT message FROM {$tablepre}posts WHERE tid='$tid' AND first=1"), 100);
		} elseif($thread['special'] == 4) {
			$message = messagecutstr($db->result_first("SELECT message FROM {$tablepre}posts WHERE tid='$tid' AND first=1"), 100);
			$number = $db->result_first("SELECT number FROM {$tablepre}activities WHERE tid='$tid'");
			$applynumbers = $db->result_first("SELECT COUNT(*) FROM {$tablepre}activityapplies WHERE tid='$tid' AND verified=1");
			$aboutmembers = $number - $applynumbers;
		} elseif($thread['special'] == 5) {
			$message = messagecutstr($db->result_first("SELECT message FROM {$tablepre}posts WHERE tid='$tid' AND first=1"), 100);
			$debate = $db->fetch_first("SELECT affirmdebaters, negadebaters, affirmvotes, negavotes FROM {$tablepre}debates WHERE tid='$tid'");
			$debate['affirmvoteswidth'] = $debate['affirmvotes']  ? intval(80 * (($debate['affirmvotes'] + 1) / ($debate['affirmvotes'] + $debate['negavotes'] + 1))) : 1;
			$debate['negavoteswidth'] = $debate['negavotes']  ? intval(80 * (($debate['negavotes'] + 1) / ($debate['affirmvotes'] + $debate['negavotes'] + 1))) : 1;
		} else {
			$message = messagecutstr($db->result_first("SELECT message FROM {$tablepre}posts WHERE tid='$tid' AND first=1"), 100);
		}

	}

	include template('request_thread');

} else {

	$request_version = '1.0';
	$request_name = $requestlang['thread_name'];
	$request_description = $requestlang['thread_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';
	$request_settings = array(
		'tid' => array($requestlang['thread_id'], '', 'text'),
	);

}

?>