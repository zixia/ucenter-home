<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: frame.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

define('NOROBOT', TRUE);
include './include/common.inc.php';

$_GET['referer'] = urldecode($_GET['referer']);
$_SERVER['HTTP_REFERER'] = $_GET['referer'] ? $_GET['referer'] : $_SERVER['HTTP_REFERER'];
$_SERVER['HTTP_REFERER'] = !empty($_SERVER['HTTP_REFERER']) && substr($_SERVER['HTTP_REFERER'], 0, strlen($boardurl)) == $boardurl ? $_SERVER['HTTP_REFERER'] : (!empty($_GET['referer']) && substr($_GET['referer'], 0, strlen($boardurl)) == $boardurl ? $_GET['referer'] : $indexname);

if(empty($_SERVER['HTTP_REFERER'])) {
	dheader("Location:$indexname");
}

if(!$_DCACHE['settings']['frameon']) {
	showmessage('frame_off');
}

$_SERVER['HTTP_REFERER'] = preg_replace("/[&?]frameon=(yes|no)/i", '', $_SERVER['HTTP_REFERER']);
$newurl = $_SERVER['HTTP_REFERER'].(strpos($_SERVER['HTTP_REFERER'], '?') !== FALSE ? '&' : '?').'frameon=no';

if($_GET['frameon'] == 'no') {

	dsetcookie('frameon', 'no', 31536000);
	dheader("Location:$newurl");

} else {

	dsetcookie('frameon', 'yes', 31536000);
	$_DCOOKIE['frameon'] = 'yes';

	include template('frame');

}

?>