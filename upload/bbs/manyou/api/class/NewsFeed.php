<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: NewsFeed.php 18778 2009-07-22 01:42:14Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class NewsFeed extends MyBase {
	
	function get($uId, $num) {
		$result = array();
		$query = $GLOBALS['db']->query("SELECT * FROM ".$GLOBALS['tablepre']."feeds WHERE type='manyou' AND uid='$uId' ORDER BY dateline DESC LIMIT 0,$num");
		while($value = $GLOBALS['db']->fetch_array($query)) {
			$value['data'] = unserialize($value['data']);
			$value['template'] = empty($value['template']) ? array() : unserialize($value['template']);
			$title_template = $value['template']['title'];
			$body_template = $value['template']['body'];
			list($body_data, $body_general) = explode(chr(0).chr(0).chr(0), $value['data']['body']);
			$result[] = array(
				'appId' => 0,
				'created' => $value['dateline'],
				'type' => $value['appid'],
				'titleTemplate' => $title_template,
				'titleData' => $value['data']['title'],
				'bodyTemplate' => $body_template,
				'bodyData' => $body_data,
				'bodyGeneral' => $body_general,
				'image1' => $value['data']['image'][0]['src'],
				'image1Link' => $value['data']['image'][0]['link'],
				'image2' => $value['data']['image'][1]['src'],
				'image2Link' => $value['data']['image'][1]['link'],
				'image3' => $value['data']['image'][2]['src'],
				'image3Link' => $value['data']['image'][2]['link'],
				'image4' => $value['data']['image'][3]['src'],
				'image4Link' => $value['data']['image'][3]['link'],
				'targetIds' => '',
				'privacy' => 'public'
			);
		}
		return new APIResponse($result);
	}

}

?>