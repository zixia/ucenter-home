<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Notifications.php 19508 2009-09-03 05:54:22Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Notifications extends MyBase {

	function get($uId) {
		$notify = $result = array();
		$result = array(
			'message' => array(
				'unread' => 0,
				'mostRecent' => 0
			),
			'notification'   => array(
				'unread' => 0 ,
				'mostRecent' => 0
			),
			'friendRequest' => array(
				'uIds' => array()
			)
		);

		$query = $GLOBALS['db']->query("SELECT * FROM ".$GLOBALS['tablepre']."notification WHERE uid='$uId' AND new='1' ORDER BY id DESC");
		$i = 0;
		while($value = $GLOBALS['db']->fetch_array($query)) {
			$i++;
			if(!$result['notification']['mostRecent']) $result['notification']['mostRecent'] = $value['dateline'];
		}
		$result['notification']['unread'] = $i;

		$pmarr = uc_pm_list($uId, 1, 1, 'newbox', 'newpm');
		if($pmarr['count']) {
			$result['message']['unread'] = $pmarr['count'];
			$result['message']['mostRecent'] = $pmarr['data'][0]['dateline'];
		}


		return new APIResponse($result);
	}

	function send($uId, $recipientIds, $appId, $notification) {
		$result = array();

		foreach($recipientIds as $recipientId) {
			$val = intval($recipientId);
			$result[$val] = notification_add($val, $appId, $notification, 1);
			$number = $GLOBALS['db']->result_first('SELECT count(*) FROM '.$GLOBALS['tablepre'].'mynotice WHERE uid=\''.$val.'\' AND new=\'1\'');
			updateprompt('mynotice', $val, $number);
		}
		return new APIResponse($result);
	}

}

?>