<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: myphone.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$discuz_action = 194;

echo "<p>$lang[my_phone]<br />".dhtmlspecialchars($_SERVER[HTTP_USER_AGENT])."<br /><br />";
if(function_exists('getallheaders')) {
	foreach(getallheaders() as $key => $value) {
		echo strtoupper($key).": $value<br/>\n";
	}
} else {
	foreach(array('REMOTE_ADDR', 'REMOTE_PORT', 'REMOTE_USER', 'GATEWAY_INTERFACE', 'SERVER_PROTOCOL', 'HTTP_CONNECTION', 'HTTP_VIA') as $key) {
		if(!empty($_SERVER[$key])) {
			echo "<br />$key: ".dhtmlspecialchars($_SERVER[$key])."\n";
		}
	}
}
echo '</p>';

?>