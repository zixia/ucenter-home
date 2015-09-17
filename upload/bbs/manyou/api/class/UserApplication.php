<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: UserApplication.php 18764 2009-07-20 09:33:12Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class UserApplication extends MyBase {

	function add($uId, $appId, $appName, $privacy, $allowSideNav, $allowFeed, $allowProfileLink,  $defaultBoxType, $defaultMYML, $defaultProfileLink, $version, $displayMethod, $displayOrder = null) {
		$sql = sprintf('SELECT appid FROM %s WHERE uid = %d AND appid = %d', $GLOBALS['tablepre'].'userapp', $uId, $appId);
		$query = $GLOBALS['db']->query($sql);
		$row = $GLOBALS['db']->fetch_array($query);
		if($row['appid']) {
			$errCode = '170';
			$errMessage = 'Application has been already added';
			return new APIErrorResponse($errCode, $errMessage);
		}

		switch($privacy) {
			case 'public':
				$privacy = 0;
				break;
			case 'friends':
				$privacy = 1;
				break;
			case 'me':
				$privacy = 3;
				break;
			case 'none':
				$privacy = 5;
				break;
			default:
				$privacy = 0;
		}

		$narrow = ($defaultBoxType == 'narrow') ? 1 : 0;
		$fields = array(
			'appid' => $appId,
			'appname' => $appName,
			'uid' => $uId,
			'privacy' => $privacy,
			'allowsidenav' => $allowSideNav,
			'allowfeed' => $allowFeed,
			'allowprofilelink' => $allowProfileLink,
			'narrow' => $narrow,
			'profilelink' => $defaultProfileLink,
			'myml' => $defaultMYML
		);
		if($displayOrder !== null) {
			$fields['displayOrder'] = $displayOrder;
		}
		$result = inserttable('userapp', $fields, 1);

		$displayMethod = ($displayMethod == 'iframe') ? 1 : 0;
		$this->refreshApplication($appId, $appName, $version, $displayMethod, $narrow, null, null);
		return new APIResponse($result);
	}

	function update($uId, $appIds, $appName, $privacy, $allowSideNav, $allowFeed, $allowProfileLink, $version, $displayMethod, $displayOrder = null) {
		switch($privacy) {
			case 'public':
				$privacy = 0;
				break;
			case 'friends':
				$privacy = 1;
				break;
			case 'me':
				$privacy = 3;
				break;
			case 'none':
				$privacy = 5;
				break;
			default:
				$privacy = 0;
		}

		$fields = array(
			'appname' => $appName,
			'privacy' => $privacy,
			'allowsidenav' => $allowSideNav,
			'allowfeed' => $allowFeed,
			'allowprofilelink' => $allowProfileLink
		);
		if($displayOrder !== null) {
			$fields['displayOrder'] = $displayOrder;
		}
		$where = sprintf('uid=\'%d\' AND appid IN (%s)', $uId, implodeids($appIds));
		updatetable('userapp', $fields, $where);
		$result = $GLOBALS['db']->affected_rows();

		$displayMethod = ($displayMethod == 'iframe') ? 1 : 0;
		if(is_array($appIds)) {
			foreach($appIds as $appId) {
				$this->refreshApplication($appId, $appName, $version, $displayMethod, null, null, null);
			}
		}

		return new APIResponse($result);
	}

	function remove($uId, $appIds) {
		$sql = sprintf('DELETE FROM %s WHERE uid=\'%d\' AND appid IN (%s)', $GLOBALS['tablepre'].'userapp', $uId, implodeids($appIds));
		$res = $GLOBALS['db']->query($sql);

		$result = $GLOBALS['db']->affected_rows();
		return new APIResponse($result);
	}

	function getInstalled($uId) {
		$sql = sprintf('SELECT appid FROM %s WHERE uid=\'%d\'', $GLOBALS['tablepre'].'userapp', $uId);
		$query = $GLOBALS['db']->query($sql);
		$result = array();
		while($userApp = $GLOBALS['db']->fetch_array($query)) {
			$result[] = $userApp['appid'];
		}
		return new APIResponse($result);
	}

	function get($uId, $appIds) {
		$sql = sprintf('SELECT * FROM %s WHERE uid=\'%d\' AND appid IN (%s)', $GLOBALS['tablepre'].'userapp', $uId, implodeids($appIds));
		$query = $GLOBALS['db']->query($sql);

		$result = array();
		while($userApp = $GLOBALS['db']->fetch_array($query)) {
			switch($userApp['privacy']) {
				case 0:
					$privacy = 'public';
					break;
				case 1:
					$privacy = 'friends';
					break;
				case 3:
					$privacy = 'me';
					break;
				case 5:
					$privacy = 'none';
					break;
				default:
					$privacy = 'public';
			}
			$result[] = array(
				'appId' => $userApp['appid'],
				'privacy' => $privacy,
				'allowSideNav' => $userApp['allowsidenav'],
				'allowFeed' => $userApp['allowfeed'],
				'allowProfileLink' => $userApp['allowprofilelink'],
				'displayOrder' => $userApp['displayorder']
			);
		}
		return new APIResponse($result);
	}

}

?>