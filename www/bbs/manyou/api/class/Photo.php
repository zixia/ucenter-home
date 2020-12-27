<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Photo.php 21053 2009-11-09 10:29:02Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Photo extends MyBase {

	function createAlbum($uId, $name, $privacy, $passwd = null, $friendIds = null) {
		return new APIResponse(0);
	}

	function updateAlbum($uId, $aId, $name = null, $privacy = null, $passwd = null, $friendIds = null, $coverId = null) {
		return new APIResponse(0);
	}

	function removeAlbum($uId, $aId, $action = null , $targetAlbumId = null) {
		return new APIResponse(0);
	}

	function getAlbums($uId) {
		return new APIResponse(0);
	}

	function upload($uId, $aId, $fileName, $fileType, $fileSize, $data, $caption = null) {
		$result = array();
		return new APIResponse($result);
	}

	function get($uId, $aId, $pIds = null) {
		$result = array();
		return new APIResponse($result);
	}

	function update($uId, $pId, $aId, $fileName = null, $fileType = null, $fileSize = null, $caption = null, $data = null) {
		$result = array();
		return new APIResponse($result);
	}

	function remove($uId, $pIds) {
		$result = array();
		return new APIResponse($result);
	}

}

?>