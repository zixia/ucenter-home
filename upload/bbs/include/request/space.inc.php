<?php
/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: space.inc.php 16697 2008-12-06 07:36:51Z andy $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if($requestrun) {

	$parameter = array();
	$parameter[] = 'ac=space';

	if(!empty($settings['uid'])) {
		$parameter[] = 'uid='.trim($settings['uid']);
	}

	if(!empty($settings['dateline'])) {
		$parameter[] = 'dateline='.intval($settings['dateline']);
	}
	if(!empty($settings['updatetime'])) {
		$parameter[] = 'updatetime='.intval($settings['updatetime']);
	}

	if(!empty($settings['startfriendnum'])) {
		$parameter[] = 'startfriendnum='.intval($settings['startfriendnum']);
	}
	if(!empty($settings['endfriendnum'])) {
		$parameter[] = 'endfriendnum='.intval($settings['endfriendnum']);
	}

	if(!empty($settings['startviewnum'])) {
		$parameter[] = 'startviewnum='.intval($settings['startviewnum']);
	}
	if(!empty($settings['endviewnum'])) {
		$parameter[] = 'endviewnum='.intval($settings['endviewnum']);
	}
	if(!empty($settings['startcredit'])) {
		$parameter[] = 'startcredit='.intval($settings['startcredit']);
	}
	if(!empty($settings['endcredit'])) {
		$parameter[] = 'endcredit='.intval($settings['endcredit']);
	}

	if($settings['avatar'] != -1) {
		$parameter[] = 'avatar='.intval($settings['avatar']);
	}
	if($settings['namestatus'] != -1) {
		$parameter[] = 'namestatus='.intval($settings['namestatus']);
	}

	if(!empty($settings['order'])) {
		$parameter[] = 'order='.trim($settings['order']);
	}
	if(!empty($settings['sc'])) {
		$parameter[] = 'sc='.trim($settings['sc']);
	}
	$start = !empty($settings['start']) ? intval($settings['start']) : 0;
	$limit = !empty($settings['limit']) ? intval($settings['limit']) : 10;

	$parameter[] = 'start='.$start;
	$parameter[] = 'limit='.$limit;

	$plus = implode('&', $parameter);

	$url = $GLOBALS['uchomeurl']."/api/discuz.php?$plus";
	$spacelist = unserialize(dfopen($url));
	$writedata = '';
	if($spacelist && is_array($spacelist)) {
		$writedata = '<div class="sidebox"><h4>'.$settings['title'].'</h4><table>';
		foreach($spacelist as $space) {
			$searchs = $replaces = array();
			foreach(array_keys($space) as $key) {
				$searchs[] = '{'.$key.'}';
				$replaces[] = $space[$key];
			}
			$writedata .= '<tr><td>'.str_replace($searchs, $replaces, stripslashes($settings['template'])).'</td></tr>';
		}
		$writedata .= '</table></div>';
	}

} else {
	$request_version = '1.0';
	$request_name = $requestlang['space_name'];
	$request_description = $requestlang['space_desc'];
	$request_copyright = '<a href="http://u.discuz.net/home/" target="_blank">Comsenz Inc.</a>';

	$request_settings = array(
		'title' => array($requestlang['space_title'], $requestlang['space_title_comment'], 'text', '', $requestlang['space_title_value']),
		'uid' => array($requestlang['space_uids'], $requestlang['space_uids_comment'], 'text'),
		'startfriendnum' => array($requestlang['space_startfriendnum'], '', 'text'),
		'endfriendnum' => array($requestlang['space_endfriendnum'], '', 'text'),
		'startviewnum' => array($requestlang['space_startviewnum'], '', 'text'),
		'endviewnum' => array($requestlang['space_endviewnum'], '', 'text'),
		'startcredit' => array($requestlang['space_startcredit'], '', 'text'),
		'endcredit' => array($requestlang['space_endcredit'], '', 'text'),
		'avatar' => array($requestlang['space_avatar'], $requestlang['space_avatar_comment'], 'mradio', array(array('-1', $requestlang['space_avatar_nolimit']), array('0', $requestlang['space_avatar_noexists']) , array('1', $requestlang['space_avatar_exists'])), '-1'),
		'namestatus'=> array($requestlang['space_namestatus'], $requestlang['space_namestatus_comment'], 'mradio', array(array('-1', $requestlang['space_namestatus_nolimit']), array('0', $requestlang['space_namestatus_nopass']) , array('1', $requestlang['space_namestatus_pass'])), '-1'),
		'dateline' => array($requestlang['space_dateline'], $requestlang['space_dateline_comment'], 'select', $requestlang['space_dateselect']),
		'updatetime' => array($requestlang['space_updatetime'], $requestlang['space_updatetime_comment'], 'select', $requestlang['space_dateselect']),
		'order' => array($requestlang['space_order'], $requestlang['space_order_comment'], 'select', $requestlang['space_orderselect']),
		'sc' => array($requestlang['space_sc'], $requestlang['space_sc_comment'], 'mradio', array(array('ASC', $requestlang['space_sc_asc']), array('DESC', $requestlang['space_sc_desc'])), 'DESC'),
		'start' => array($requestlang['space_start'], $requestlang['space_start_comment'], 'text', '', '0'),
		'limit' => array($requestlang['space_limit'], $requestlang['space_limit_comment'], 'text', '', '10'),
		'template' => array($requestlang['space_template'], $requestlang['space_template_comment'], 'textarea', '','<a href="{userlink}">{username}</a>')
	);
}
?>