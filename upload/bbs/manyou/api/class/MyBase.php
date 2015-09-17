<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: MyBase.php 21053 2009-11-09 10:29:02Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class MyBase {

	function getUserSpace($uId) {
		return getUserSpace($uId);
	}

	function _isfounder($user) {
		$founders = str_replace(' ', '', $GLOBALS['forumfounders']);
		if($user['adminid'] <> 1) {
			return FALSE;
		} elseif(empty($founders)) {
			return TRUE;
		} elseif(strexists(",$founders,", ",$user[uid],")) {
			return TRUE;
		} elseif(!is_numeric($user['username']) && strexists(",$founders,", ",$user[username],")) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function _space2user($member) {
		$adminLevel = 'none';
		if($this->_isfounder($member)) {
			$adminLevel = 'founder';
		} elseif($member['adminid'] == 1) {
			$adminLevel = 'manager';
		}

		$user = array(
			'uId'		=> $member['uid'],
			'handle'	=> $member['username'],
			'action'	=> $member['action'],
			'realName'	=> $member['name'],
			'realNameChecked' => $member['namestatus'] ? true : false,
			'spaceName'	=> $member['spacename'],
			'gender'	=> $member['sex'] == 1 ? 'male' : ($member['sex'] == 2 ? 'female' : 'unknown'),
			'email'		=> $member['email'],
			'qq'		=> $member['qq'],
			'msn'		=> $member['msn'],
			'birthday'	=> $member['bday'],
			'bloodType'	=> empty($member['blood']) ? 'unknown' : $member['blood'],
			'relationshipStatus' => $member['marry'] == 1 ? 'single' : ($member['marry'] == 2 ? 'notSingle' : 'unknown'),
			'birthProvince' => $member['birthprovince'],
			'birthCity'	=> $member['birthcity'],
			'resideProvince' => $member['resideprovince'],
			'resideCity'	=> $member['residecity'],
			'viewNum'	=> 0,
			'friendNum'	=> uc_friend_totalnum($member['uid'], 3),
			'myStatus'	=> $member['bio'],
			'lastActivity' => $member['lastactivity'],
			'created'	=> $member['regdate'],
			'credit'	=> $member['credits'],
			'isUploadAvatar'	=> $member['avatar'] ? true : false,
			'adminLevel'		=> $adminLevel,
			'homepagePrivacy'	=> 'public',
			'profilePrivacy'	=> 'public',
			'friendListPrivacy'	=> 'public',
		);
		return $user;
	}

	function _getFriends($uId, $num = DEFAULT_FRIENDNUM) {
		$friends = array();
		$ucresult = uc_friend_ls($uId, 1, $num, $num, 3);
		foreach($ucresult as $friend) {
			$friends[] = $friend['friendid'];
		}
		return $friends;
	}

	function refreshApplication($appId, $appName, $version, $displayMethod, $narrow, $flag, $displayOrder) {
		$fields = array();
		if($appName !== null && strlen($appName)>1) {
			$fields['appname'] = $appName;
		}
		if($version !== null) {
			$fields['version'] = $version;
		}
		if($displayMethod !== null) {
			// todo: remove
			$fields['displaymethod'] = $displayMethod;
		}
		if($narrow !== null) {
			$fields['narrow'] = $narrow;
		}
		if($flag !== null) {
			$fields['flag'] = $flag;
		}
		if($displayOrder !== null) {
			$fields['displayorder'] = $displayOrder;
		}
		$sql = sprintf('SELECT * FROM %s WHERE appid=\'%d\'', $GLOBALS['tablepre'].'myapp', $appId);
		$query = $GLOBALS['db']->query($sql);
		if($application = $GLOBALS['db']->fetch_array($query)) {
			$where = sprintf('appid = %d', $appId);
			updatetable('myapp', $fields, $where);
		} else {
			$fields['appid'] = $appId;
			$result = inserttable('myapp', $fields, 1);
		}
		$myapps = array();
		$sql = sprintf('SELECT * FROM %s WHERE flag=\'1\' ORDER BY displayorder', $GLOBALS['tablepre'].'myapp');
		$query = $GLOBALS['db']->query($sql);
		while($application = $GLOBALS['db']->fetch_array($query)) {
			$myapps[$application['appid']] = $application;
		}
		require_once DISCUZ_ROOT.'./include/cache.func.php';
		writetocache('manyou', '', getcachevars(array('myapps' => $myapps)));
	}
}

class my{

	function parseRequest() {
		global $_DCACHE;

		$request = $_POST;
		$module = $request['module'];
		$method = $request['method'];

		$errCode = 0;
		$errMessage = '';
		if($_DCACHE['settings']['bbclosed']) {
			$errCode = 2;
			$errMessage = 'Site Closed';
		} elseif(!$_DCACHE['settings']['my_status']) {
			$errCode = 2;
			$errMessage = 'Manyou Service Disabled';
		} elseif(!$_DCACHE['settings']['my_sitekey']) {
			$errCode = 12;
			$errMessage = 'My SiteKey NOT Exists';
		} elseif(empty($module) || empty($method)) {
			$errCode = '3';
			$errMessage = 'Invalid Method: ' . $moudle . '.' . $method;
		}

		if(get_magic_quotes_gpc()) {
			$request['params'] = sstripslashes($request['params']);
		}
		$mySign = $module.'|'.$method.'|'.$request['params'].'|'.$_DCACHE['settings']['my_sitekey'];
		$mySign = md5($mySign);
		if($mySign != $request['sign']) {
			$errCode = '10';
			$errMessage = 'Error Sign';
		}
		if($errCode) {
			return new APIErrorResponse($errCode, $errMessage);
		}

		$params = unserialize($request['params']);

		$params = $this->myAddslashes($params);
		if($module == 'Batch' && $method == 'run') {
			$response = array();
			foreach($params as $param) {
				$response[] = $this->callback($param['module'], $param['method'], $param['params']);
			}
			return new APIResponse($response, 'Batch');
		}
		return $this->callback($module, $method, $params);
	}

	function callback($module, $method, $params) {
		if(isset($params['uId'])) {
			$member = getUserSpace($params['uId']);
			if($this->_needCheckUserId($module, $method)) {
				if(!$member['uid']) {
					$errCode = 1;
					$errMessage = "User($params[uId]) Not Exists";
					return new APIErrorResponse($errCode, $errMessage);
				}
			}
		}
		$GLOBALS['discuz_uid'] = $member['uid'];
		$GLOBALS['discuz_user'] = $member['username'];

		@include_once DISCUZ_ROOT.'./manyou/api/class/'.$module.'.php';
		if(!class_exists($module)) {
			$errCode = 3;
			$errMessage = "Class($module) Not Exists";
			return new APIErrorResponse($errCode, $errMessage);
		}

		$class = new $module();
		$response = @call_user_func_array(array(&$class, $method), $params);

		return $response;
	}

	function formatResponse($data) {
		global $_DCACHE;
		$res = array(
			'timezone'	=> intval($_DCACHE['settings']['timeoffset']),
			'version'   	=> X_VER,
			'charset'	=> $GLOBALS['charset'],
			'language'	=> X_LANGUAGE,
			'my_version'	=> X_MYVER,
		);
		if (strtolower(get_class($data)) == 'apiresponse' ) {
			if (is_array($data->result) && $data->getMode() == 'Batch') {
				foreach($data->result as $result) {
					if (strtolower(get_class($result)) == 'apiresponse') {
						$res['result'][]  = $result->getResult();
					} else {
						$res['result'][] = array('errCode' => $result->getErrCode(),
							'errMessage' =>  $result->getErrMessage()
						);
					}
				}
			} else {
				$res['result']  = $data->getResult();
			}
		} else {
			$res['errCode'] = $data->getErrCode();
			$res['errMessage'] = $data->getErrMessage();
		}
		return serialize($res);
	}

	function _needCheckUserId($module, $method) {
		$myMethod = $module.'.'.$method;
		switch($myMethod) {
			case 'Notifications.send':
			case 'Request.send':
				$res = false;
				break;
			default:
				$res = true;
		}
		return $res;
	}

	function myAddslashes($string) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = $this->myAddslashes($val);
			}
		} else {
			$string = ($string === null) ? null : addslashes($string);
		}
		return $string;
	}

}

function getUserSpace($uId) {
	$uId = intval($uId);
	$query = $GLOBALS['db']->query("SELECT mf.*, m.* FROM ".$GLOBALS['tablepre']."members m
		LEFT JOIN ".$GLOBALS['tablepre']."memberfields mf ON mf.uid=m.uid
		WHERE m.uid='$uId'");
	$member = $GLOBALS['db']->fetch_array($query);
	return $member;
}

function inserttable($tablename, $insertsqlarr, $returnid=0, $replace = false, $silent=0) {
	$insertkeysql = $insertvaluesql = $comma = '';
	foreach ($insertsqlarr as $insert_key => $insert_value) {
		$insertkeysql .= $comma.'`'.$insert_key.'`';
		$insertvaluesql .= $comma.'\''.$insert_value.'\'';
		$comma = ', ';
	}
	$method = $replace?'REPLACE':'INSERT';
	$GLOBALS['db']->query($method.' INTO '.$GLOBALS['tablepre'].$tablename.' ('.$insertkeysql.') VALUES ('.$insertvaluesql.')', $silent?'SILENT':'');
	if($returnid && !$replace) {
		return $GLOBALS['db']->insert_id();
	}
}

function updatetable($tablename, $setsqlarr, $wheresqlarr, $silent=0) {
	$setsql = $comma = '';
	foreach ($setsqlarr as $set_key => $set_value) {
		$setsql .= $comma.'`'.$set_key.'`'.'=\''.$set_value.'\'';
		$comma = ', ';
	}
	$where = $comma = '';
	if(empty($wheresqlarr)) {
		$where = '1';
	} elseif(is_array($wheresqlarr)) {
		foreach ($wheresqlarr as $key => $value) {
			$where .= $comma.'`'.$key.'`'.'=\''.$value.'\'';
			$comma = ' AND ';
		}
	} else {
		$where = $wheresqlarr;
	}
	$GLOBALS['db']->query('UPDATE '.$GLOBALS['tablepre'].$tablename.' SET '.$setsql.' WHERE '.$where, $silent?'SILENT':'');
}

function sstripslashes($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = sstripslashes($val);
		}
	} else {
		$string = stripslashes($string);
	}
	return $string;
}

function feed_add($icon, $title_template='', $title_data=array(), $body_template='', $body_data=array(), $body_general='', $images=array(), $image_links=array(), $target_ids='', $friend='', $appid=UC_APPID, $returnid=0) {
	$arg = array(
		'type' => 'manyou',
		'appid' => $icon,
		'uid' => $GLOBALS['discuz_uid'],
		'username' => addslashes($GLOBALS['discuz_user'])
	);
	$image = array();
	foreach($images as $k => $v) {
		if($v) {
			$image[] = array('src' => $v, 'link' => $image_links[$k]);
		}
	}
	$title_data = is_array($title_data) ? $title_data : array();
	$body_data = is_array($body_data) ? $body_data : array();
	$title_data['actor'] = '<a href="space.php?uid='.$GLOBALS['discuz_uid'].'">'.$GLOBALS['discuz_user'].'</a>{addbuddy}';
	$body_data['actor'] = '<a href="space.php?uid='.$GLOBALS['discuz_uid'].'">'.$GLOBALS['discuz_user'].'</a>{addbuddy}';
	$data = array(
		'title' => sstripslashes($title_data),
		'body' => sstripslashes($body_data),
		'image' => $image
	);
	$template = array(
		'title' => sstripslashes($title_template),
		'body' => sstripslashes($body_template.chr(0).chr(0).chr(0).$body_general)
	);
	add_feed($arg, $data, $template);
	return 1;
}

function notification_add($uid, $type, $note, $returnid=0) {
	$setarr = array(
		'uid' => $uid,
		'type' => $type,
		'new' => 1,
		'authorid' => $GLOBALS['discuz_uid'],
		'author' => addslashes($GLOBALS['discuz_user']),
		'note' => addslashes(sstripslashes($note)),
		'dateline' => $GLOBALS['timestamp']
	);
	//PromptUpdate

	if($returnid) {
		return inserttable('mynotice', $setarr, $returnid);
	} else {
		inserttable('mynotice', $setarr);
	}
}

?>