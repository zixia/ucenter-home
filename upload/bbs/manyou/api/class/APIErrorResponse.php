<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: APIErrorResponse.php 21053 2009-11-09 10:29:02Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class APIErrorResponse {
	var $errCode = 0;
	
	var $errMessage = '';

	function APIErrorResponse($errCode, $errMessage) {
		$this->errCode = $errCode;
		$this->errMessage = $errMessage;
	}

	function getErrCode() {
		return $this->errCode;
	}

	function getErrMessage() {
		return $this->errMessage;
	}

	function getResult() {
		return null;
	}

}

?>