<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Site.php 18764 2009-07-20 09:33:12Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Site extends MyBase {

	function getUpdatedUsers($num) {
		$logfile = DISCUZ_ROOT.'./forumdata/logs/manyou_user.log';
		$totalNum = 0;
		$result = array();
		if(file_exists($logfile) && @rename($logfile, $logfile.'.bak')) {
			$data = file($logfile.'.bak');
			$totalNum = count($data);
			if($num < $totalNum) {
				$ldata = array_slice($data, $num);
				$data = array_slice($data, 0, $num);
				$newdata = @file($logfile);
				$writedata = is_array($newdata) ? array_merge($ldata, $newdata) : $ldata;
				if($fp = @fopen($logfile, 'w')) {
					@flock($fp, 2);
					foreach($writedata as $row) {
						fwrite($fp, trim($row)."\n");
					}
					fclose($fp);
				}
			}
			@unlink($logfile.'.bak');
			if($data) {
				$dataary = $uIds = array();
				foreach($data as $row) {
					list(,, $uid, $action) = explode("\t", $row);
					$uIds[] = $uid;
					$dataary[] = array($uid, $action);
				}
				$sql = 'SELECT m.*, mf.* FROM %s m LEFT JOIN %s mf ON m.uid = mf.uid WHERE m.uid IN (%s)';
				$sql = sprintf($sql, $GLOBALS['tablepre'].'members', $GLOBALS['tablepre'].'memberfields', implodeids(array_unique($uIds)));
				$query = $GLOBALS['db']->query($sql);
				$users = array();
				while($member = $GLOBALS['db']->fetch_array($query)) {
					$user = $this->_space2user($member);
					$users[$user['uId']] = $user;
				}

				foreach($dataary as $row) {
					$users[$row[0]]['action'] = trim($row[1]);
					$result[] = $users[$row[0]];
				}
			}
		}

		$result = array(
			'totalNum' => count($data),
			'users' => $result
		);
		return new APIResponse($result);
	}

	function getUpdatedFriends($num) {
		$logfile = DISCUZ_ROOT.'./forumdata/logs/manyou_friend.log';
		$totalNum = 0;
		$result = array();
		if(file_exists($logfile) && @rename($logfile, $logfile.'.bak')) {
			$data = file($logfile.'.bak');
			$totalNum = count($data);
			if($num < $totalNum) {
				$ldata = array_slice($data, $num);
				$data = array_slice($data, 0, $num);
				$newdata = @file($logfile);
				$writedata = is_array($newdata) ? array_merge($ldata, $newdata) : $ldata;
				if($fp = @fopen($logfile, 'w')) {
					@flock($fp, 2);
					foreach($writedata as $row) {
						fwrite($fp, trim($row)."\n");
					}
					fclose($fp);
				}
			}
			@unlink($logfile.'.bak');
			if($data) {
				foreach($data as $row) {
					list(,, $uid, $action, $fuid) = explode("\t", $row);
					$result[] = array('uId' => $uid,  'uId2' => $fuid, 'action' => $action);
				}
			}
		}

		$result = array(
			'totalNum' => $totalNum,
			'friends' => $result
		);
		return new APIResponse($result);
	}

	function getAllUsers($from, $num, $friendNum = DEFAULT_FRIENDNUM) {
		$totalNum = $GLOBALS['db']->result_first("SELECT COUNT(*) FROM ".$GLOBALS['tablepre']."members");

		$sql = 'SELECT m.*, mf.* FROM %s m LEFT JOIN %s mf ON m.uid = mf.uid ORDER BY m.uid LIMIT %d, %d';
		$sql = sprintf($sql, $GLOBALS['tablepre'].'members', $GLOBALS['tablepre'].'memberfields', $from, $num);
		$query = $GLOBALS['db']->query($sql);

		$users = array();
		while($member = $GLOBALS['db']->fetch_array($query)) {
			$user = $this->_space2user($member);
			$user['friends'] = $this->_getFriends($member['uid'], $friendNum);
			$user['action'] = 'add';
			$users[] = $user;
		}
		$result = array(
			'totalNum' => $totalNum,
			'users' => $users
		);
		return new APIResponse($result);
	}

}

?>