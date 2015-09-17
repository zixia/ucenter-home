<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Application.php 18764 2009-07-20 09:33:12Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Application extends MyBase {

	function update($appId, $appName, $version, $displayMethod, $displayOrder = null) {
		$fields = array('appname' => $appName);
		$where = array('appid'	=> $appId);
		$result = updatetable('userapp', $fields, $where);

		$displayMethod = ($displayMethod == 'iframe') ? 1 : 0;
		$this->refreshApplication($appId, $appName, $version, $displayMethod, null, null, $displayOrder);
		return new APIResponse($result);
	}

	function remove($appIds) {
		$sql = sprintf('DELETE FROM %s WHERE appid IN (%s)', $GLOBALS['tablepre'].'userapp', implodeids($appIds));
		$result = $GLOBALS['db']->query($sql);

		$sql = sprintf('DELETE FROM %s WHERE appid IN (%s)', $GLOBALS['tablepre'].'myapp', implodeids($appIds));
		$GLOBALS['db']->query($sql);

		return new APIResponse($result);
	}

	function setFlag($applications, $flag) {
		$flag = ($flag == 'disabled') ? -1 : ($flag == 'default' ? 1 : 0);
		$appIds = array();
		if ($applications && is_array($applications)) {
			foreach($applications as $application) {
				$this->refreshApplication($application['appId'], $application['appName'], null, null, null, $flag, null);
				$appIds[] = $application['appId'];
			}
		}

		if ($flag == -1) {
			$sql = sprintf('DELETE FROM %s WHERE appid IN (%s)', $GLOBALS['tablepre'].'myfeed', implodeids($appIds));
			$GLOBALS['db']->query($sql);

			$sql = sprintf('DELETE FROM %s WHERE appid IN (%s)', $GLOBALS['tablepre'].'userapp', implodeids($appIds));
			$GLOBALS['db']->query($sql);

			$sql = sprintf('DELETE FROM %s WHERE appid IN (%s)', $GLOBALS['tablepre'].'myinvite', implodeids($appIds));
			$GLOBALS['db']->query($sql);
		}

		$result = true;
		return new APIResponse($result);
	}

}

?>