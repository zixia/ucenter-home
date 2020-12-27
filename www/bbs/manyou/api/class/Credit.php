<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Credit.php 18764 2009-07-20 09:33:12Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Credit extends MyBase {

	function get($uId) {
		global $_DCACHE;
		$query = $GLOBALS['db']->query('SELECT extcredits'.$_DCACHE['settings']['my_extcredit'].' AS credit FROM '.$GLOBALS['tablepre'].'members WHERE uid=\''.$uId.'\'');
		$row = $GLOBALS['db']->fetch_array($query);
		return new APIResponse($row['credit']);
	}

	function update($uId, $credits) {
		global $_DCACHE;
		$sql = sprintf('UPDATE %s SET extcredits%s=extcredits%s+(%d) WHERE uid=\'%d\'', $GLOBALS['tablepre'].'members', $_DCACHE['settings']['my_extcredit'], $_DCACHE['settings']['my_extcredit'], $credits, $uId);
		$GLOBALS['db']->query($sql);

		$query = $GLOBALS['db']->query('SELECT extcredits'.$_DCACHE['settings']['my_extcredit'].' AS credit FROM '.$GLOBALS['tablepre'].'members WHERE uid=\''.$uId.'\'');
		$row = $GLOBALS['db']->fetch_array($query);
		return new APIResponse($row['credit']);
	}

}

?>