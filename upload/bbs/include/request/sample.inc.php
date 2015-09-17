<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: sample.inc.php 21053 2009-11-09 10:29:02Z wangjinbo $
*/


if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {


	$settings['fid'] = !empty($settings['sidestatus']) && $specialfid ? $specialfid : $settings['fid'];
	$limit = !empty($settings['limit']) ? intval($settings['limit']) : 10;
	$fid = !empty($settings['fid']) ? 'fid='.intval($settings['fid']) : 'fid>0';

	$query = $db->query("SELECT tid, subject FROM {$tablepre}threads WHERE $fid AND displayorder>=0 ORDER BY dateline DESC LIMIT $limit");

	$writedata = '<ul>';
	while($thread = $db->fetch_array($query)) {
		$writedata .= "
			<li>
			<a href=\"{$boardurl}viewthread.php?tid=$thread[tid]\" target=\"_blank\">$thread[subject]</a>
			</li>
		";
	}
	$writedata .= '</ul>';

} else {


	$request_version = '1.0';
	$request_name = '模块调用脚本范例';
	$request_description = '最新主题调用范例，您可以参照本脚本 ./include/request/sample.inc.php 中的说明编写模块脚本';
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';
	$request_settings = array(
		'limit' 	=> array('返回条目数', '设置返回的条目数', 'text'),
		'fid' 		=> array('选择版块', '选择显示哪个版块的帖子', 'select', array()),
		'sidestatus' 	=> array('主题列表页面(forumdisplay.php)专用', '设置此数据调用模块为主题列表页面(forumdisplay.php)的专用模块，只调用当前版块的内容', 'radio'),
	);

	include DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
	$settings['fid'][3][] = array(0, '');
	foreach($_DCACHE['forums'] as $fid => $forum) {
		$settings['fid'][3][] = array($fid, $forum['name']);
	}

}

?>