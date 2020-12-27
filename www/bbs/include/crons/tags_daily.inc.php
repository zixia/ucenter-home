<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if($tagstatus) {
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache(array('tags_index', 'tags_viewthread'));
	$db->query("DELETE FROM {$tablepre}tags WHERE total=0", 'UNBUFFERED');
}

?>