<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magic_money.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(submitcheck('usesubmit')) {

	$getmoney = rand(1, intval($magic['price'] * 1.5));
	$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[3]=extcredits$creditstransextra[3]+'$getmoney' WHERE uid='$discuz_uid'");

	usemagic($magicid, $magic['num']);
	updatemagiclog($magicid, '2', '1', '0', '', '', $discuz_uid);
	showmessage('magics_MOK_message', '', 1);

}

function showmagic() {
	global $lang;
	magicshowtips($lang['MOK_info'], $lang['option']);
}

?>