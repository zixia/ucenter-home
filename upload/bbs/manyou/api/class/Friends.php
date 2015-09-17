<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Friends.php 18735 2009-07-14 08:43:18Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Friends extends MyBase {
	
	function areFriends($uId1, $uId2) {
		$num = uc_friend_totalnum($uId1, 3);
		$friends = uc_friend_ls($uId1, 1, $num, $num, 3);
		$result = false;
		foreach($friends as $friend) {
			if($friend['friendid'] == $uId2) {
				$result = true;
				break;
			}
		}
		return new APIResponse($result);
	}

	function get($uIds, $friendNum = DEFAULT_FRIENDNUM) {
		$result = array();
		foreach($uIds as $uId) {
			$friends = uc_friend_ls($uId, 1, $friendNum, $friendNum, 3);
			foreach($friends as $friend) {
				$result[$friend['uid']][] = $friend['friendid'];
			}
		}
		return new APIResponse($result);
	}

}

?>