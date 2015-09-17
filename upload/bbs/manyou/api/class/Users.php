<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Users.php 18764 2009-07-20 09:33:12Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Users extends MyBase {

	function getInfo($uIds, $fields = array()) {
		$result = array();
		$query = $GLOBALS['db']->query("SELECT mf.*, m.* FROM ".$GLOBALS['tablepre']."members m
			LEFT JOIN ".$GLOBALS['tablepre']."memberfields mf ON mf.uid=m.uid
			WHERE m.uid IN (".implodeids($uIds).")");
		while($space = $GLOBALS['db']->fetch_array($query)) {
			$user = $this->_space2user($space);
			$tmp = array();
			if($fields) {
				foreach($fields as $field) {
					$tmp[$field] = $user[$field];
				}
			} else {
				$tmp = $user;
			}
			$result[] = $tmp;
		}
		return new APIResponse($result);
	}

	function getFriendInfo($uId, $num = DEFAULT_FRIENDNUM) {
		$allFriends = $this->_getFriends($uId);
		$totalNum = count($allFriends);
		$result = array(
			'totalNum' => $totalNum,
			'friends' => array(),
			'allFriends' => $allFriends
		);
		$num = $num > $totalNum ? $totalNum : $num;
		if(is_array($allFriends)) {
			for($i = 0; $i < $num; $i++) {
				$friendId = $allFriends[$i];
				$space = $this->getUserSpace($friendId);
				$user = $this->_space2user($space);
				$result['friends'][] = $user;
			}
		}
		return new APIResponse($result);
	}

	function getExtraInfo($uIds) {
		return new APIResponse(0);
	}

}

?>