<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: profile.func.php 18688 2009-07-10 05:31:27Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function _my_env_get($var) {
	if($var == 'owner') {
		return $GLOBALS['member']['uid'];
	} elseif($var == 'viewer') {
		return $GLOBALS['discuz_uid'];
	} elseif($var == 'prefix_url') {
		return $GLOBALS['boardurl'];
	} else {
		return '';
	}
}

function _my_get_friends($uid) {
	$var = "my_get_friends_$uid";
	if(!isset($GLOBALS[$var])) {
		$GLOBALS[$var] = array();
		require_once DISCUZ_ROOT.'./uc_client/client.php';
		$friends = uc_friend_ls($uid, 1, 2000, 2000, 3);
		foreach($friends as $friend) {
			$GLOBALS[$var][] = $friend['friendid'];
		}
	}
	return $GLOBALS[$var];
}

function _my_get_name($uid) {
	$var = "my_get_name_$uid";
	if(!isset($GLOBALS[$var])) {
		if($uid == $GLOBALS['member']['uid']) {
			$GLOBALS[$var] = $GLOBALS['member']['username'];
		} else {
			global $db, $tablepre;
			$GLOBALS[$var] = $db->result_first("SELECT username FROM {$tablepre}members WHERE uid='$uid'");
		}
	}
	return $GLOBALS[$var];
}

function _my_get_profilepic($uid, $size = 'small') {
	return UC_API.'/avatar.php?uid='.$uid.'&size='.$size;
}

function _my_are_friends($uid1, $uid2) {
	$var = "my_are_friends_{$uid1}_{$uid2}";
	if(!isset($GLOBALS[$var])) {
		require_once DISCUZ_ROOT.'./uc_client/client.php';
		$friends = uc_friend_ls($uid1, 1, 2000, 2000, 3);
		$result = FALSE;
		foreach($friends as $friend) {
			if($friend['friendid'] == $uid2) {
				$result = TRUE;
				break;
			}
		}
		$GLOBALS[$var] = $result;
	}
	return $GLOBALS[$var];
}

function _my_user_is_added_app($uid, $appid) {
	$var = "my_user_is_added_app_{$uid}_{$appid}";
	if(!isset($GLOBALS[$var])) {
		global $db, $tablepre;
		$GLOBALS[$var] = FALSE;
		if($db->result_first("SELECT count(*) FROM {$tablepre}userapp WHERE uid='$uid' AND appid='$appid' LIMIT 1")) {
			$GLOBALS[$var] = TRUE;
		}
	}
	return $GLOBALS[$var];
}

function _my_get_app_url($appid, $suffix) {
	return $GLOBALS['boardurl']."userapp.php?id=$appid".($suffix ? '&'.$suffix : '');
}

function _my_get_app_position($appid) {
	$var = "my_get_app_position_{$appid}";
	if(!isset($GLOBALS[$var])) {
		global $db, $tablepre;
		if($db->result_first("SELECT narrow FROM {$tablepre}userapp WHERE appid='$appid' LIMIT 1")) {
			$GLOBALS[$var] = 'narrow';
		} else {
			$GLOBALS[$var] = 'wide';
		}
	}
	return $GLOBALS[$var];
}

function getmyml($uid) {
	global $db, $tablepre, $myapps;
	include_once DISCUZ_ROOT.'./forumdata/cache/cache_manyou.php';
	$appids = array_keys($myapps);
	$my_list = array();
	$query = $db->query("SELECT * FROM {$tablepre}userapp WHERE uid='$uid' AND allowprofilelink='1' ORDER BY displayorder DESC");
	while($value = $db->fetch_array($query)) {
		if(in_array($value['appid'], $appids)) {
			if($value['myml']) {
				$value['appurl'] = 'userapp.php?id='.$value['appid'];
				if($value['narrow']) {
					$my_list['narrow'][] = $value;
				} else {
					$my_list['wide'][] = $value;
				}
			}
			if($value['profilelink']) {
				$my_list['guide'][] = $value;
			}
		}
	}
	return $my_list;
}

?>