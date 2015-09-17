<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Feed.php 18764 2009-07-20 09:33:12Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Feed extends MyBase {

	function publishTemplatizedAction($uId, $appId, $titleTemplate, $titleData, $bodyTemplate, $bodyData, $bodyGeneral = '', $image1 = '', $image1Link = '', $image2 = '', $image2Link = '', $image3 = '', $image3Link = '', $image4 = '', $image4Link = '', $targetIds = '', $privacy = '', $hashTemplate = '', $hashData = '') {
		$friend = ($privacy == 'public') ? 0 : ($privacy == 'friends' ? 1 : 2);
		
		$images = array($image1, $image2, $image3, $image4);
		$image_links = array($image1Link, $image2Link, $image3Link, $image4Link);
		$result = feed_add($appId, $titleTemplate, $titleData, $bodyTemplate, $bodyData, $bodyGeneral, $images, $image_links, $targetIds, $friend, 0, 1);
		
		return new APIResponse($result);
	}

}

?>