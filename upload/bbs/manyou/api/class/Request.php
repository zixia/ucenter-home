<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Request.php 19508 2009-09-03 05:54:22Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Request extends MyBase {

	function send($uId, $recipientIds, $appId, $requestName, $myml, $type) {
		$now = time();
		$result = array();
		$type = ($type == 'request') ? 1 : 0;

		$fields = array(
			'typename' => $requestName,
			'appid' => $appId,
			'type' => $type,
			'fromuid' => $uId,
			'dateline' => $now
		);
		foreach($recipientIds as $key => $val) {
			$hash = crc32($appId.$val.$now.rand(0, 1000));
			$hash = sprintf('%u', $hash);
			$fields['touid'] = intval($val);
			$fields['hash'] = $hash;
			$fields['myml'] = str_replace('{{MyReqHash}}', $hash, $myml);
			$result[] = inserttable('myinvite', $fields, 1);
			$number = $GLOBALS['db']->result_first('SELECT count(*) FROM '.$GLOBALS['tablepre'].'myinvite WHERE touid=\''.$fields['touid'].'\'');
			updateprompt('myinvite', $fields['touid'], $number);
		}
		return new APIResponse($result);
	}

}

?>