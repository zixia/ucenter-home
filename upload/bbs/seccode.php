<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: seccode.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

define('CURSCRIPT', 'seccode');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';

$refererhost = parse_url($_SERVER['HTTP_REFERER']);
$refererhost['host'] .= !empty($refererhost['port']) ? (':'.$refererhost['port']) : '';

if($seccodedata['type'] < 2 && ($refererhost['host'] != $_SERVER['HTTP_HOST'] || !$seccodestatus) || $seccodedata['type'] == 2 && !extension_loaded('ming') && $_POST['fromFlash'] != 1 || $seccodedata['type'] == 3 && $_GET['fromFlash'] != 1) {
	exit('Access Denied');
}

if($seclevel) {
	if($update && $seccodedata['type'] != 3) {
		$seccode = random(6, 1) + $seccode{0} * 1000000;
		updatesession();
	}
} else {
	$key = $seccodedata['type'] != 3 ? '' : $_DCACHE['settings']['authkey'].date('Ymd');
	list($seccode, $expiration, $seccodeuid) = explode("\t", authcode($_DCOOKIE['secc'], 'DECODE', $key));
	if($seccodeuid != $discuz_uid || $timestamp - $expiration > 600) {
		exit('Access Denied');
	}
}

if(!$nocacheheaders) {
	@dheader("Expires: -1");
	@dheader("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
	@dheader("Pragma: no-cache");
}

include_once './include/seccode.class.php';
$code = new seccode();
$code->code = $seccode;
$code->type = $seccodedata['type'];
$code->width = $seccodedata['width'];
$code->height = $seccodedata['height'];
$code->background = $seccodedata['background'];
$code->adulterate = $seccodedata['adulterate'];
$code->ttf = $seccodedata['ttf'];
$code->angle = $seccodedata['angle'];
$code->color = $seccodedata['color'];
$code->size = $seccodedata['size'];
$code->shadow = $seccodedata['shadow'];
$code->animator = $seccodedata['animator'];
$code->fontpath = DISCUZ_ROOT.'./images/fonts/';
$code->datapath = DISCUZ_ROOT.'./images/seccode/';
$code->includepath = DISCUZ_ROOT.'./include/';
$code->display();

?>