<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: rowcombine.inc.php 20152 2009-09-21 02:22:58Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$combinetitles = $combinedata = $datalist = '';
	$combinemodules = explode("\n", $settings['data']);
	$idbase = 'cm_'.random(4);
	foreach($combinemodules as $id => $combinemodule) {
		$i = $id + 1;
		$id = $idbase.'_c_'.$i;
		list($combinekey, $combinetitle) = explode(',', $combinemodule);
		$cachekey = $combinekey;
		if(CURSCRIPT == 'forumdisplay' && $specialfid) {
			$cachekey .= '_fid'.$specialfid;
		}
		$combinecachefile = DISCUZ_ROOT.'./forumdata/cache/request_'.md5($cachekey).'.php';
		if((@!include($combinecachefile)) || $expiration < $timestamp) {
			parse_str($_DCACHE['request'][$combinekey]['url'], $requestdata);
			$datalist = parse_request($requestdata, $combinecachefile, 0, $specialfid, $combinekey);
		}
		$combinedata .= '<div id="'.$id.'" class="combine" style="display:none">'.$datalist.'</div>';
		$combinetitles[$i] = $combinetitle;
	}
	$combinecount = count($combinemodules);

	include template('request_rowcombine');

} else {

	$request_version = '1.0';
	$request_name = $requestlang['rowcombine_name'];
	$request_description = $requestlang['rowcombine_desc'];
	$request_copyright = '<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>';
	$request_settings = array(
		'title' => array($requestlang['rowcombine_title'], $requestlang['rowcombine_title_comment'], 'text'),
		'data' => array($requestlang['rowcombine_name'], $requestlang['rowcombine_data_comment'], 'textarea'),
	);

}

?>