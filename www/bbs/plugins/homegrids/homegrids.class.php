<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: homegrids.class.php 20541 2009-10-09 00:34:37Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_homegrids {
	function index_hot() {
		@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_homegrids.php';
		$modules = explode("\n", $_DPLUGIN['homegrids']['vars']['modules']);
		$boardurl = $GLOBALS['boardurl'];
		$moduleA = $titleA = $widthA = $heightA = $moduleB = $moduleC = $navB = '';
		$navi = 1;
		foreach($modules as $m) {
			list($type, $m, $title, $width, $height) = explode(',', $m);
			if($type == 'A') {
				$titleA = $title;
				$moduleA .= request($m, 0, 0, 1);
				$widthA = intval($width);
				$heightA = intval($height);
			} elseif($type == 'B') {
				$navB .= '<span '.($navi == 1 ? 'class="current" ' : '').'onmouseover="switchTab(\'homegrids\', '.$navi.', {navi})" id="homegrids_'.$navi.'">'.$title.'</span>';
				$moduleB .= '<h4 id="homegrids_t_'.$navi.'">'.$title.'</h4><div class="homegridslist" id="homegrids_c_'.$navi.'">'.request($m, 0, 0, 1).'</div>';
				$navi++;
			} elseif($type == 'C') {
				$moduleC .= '<td valign="top" width="'.$width.'"><h4>'.$title.'</h4>'.request($m, 0, 0, 1).'</td>';
			}
		}
		$navcount = $navi - 1;
		$navB = str_replace('{navi}', $navcount, $navB);
		include template('homegrids:homegrids');
		return $return;
	}
}

?>