<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: viewthread_reward.inc.php 16854 2008-11-24 14:15:05Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$bapid = 0;
$rewardprice = abs($thread['price']);
$bestpost = array();
if($thread['price'] < 0 && $page == 1) {
	foreach($postlist as $key => $post) {
		if(!$post['first']) {
			$bapid = $key;			
			break;
		}
	}
}

?>