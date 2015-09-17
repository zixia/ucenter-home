<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: cp.php 18639 2009-07-08 01:07:40Z monkey $
*/

$s = '';
foreach($_GET as $k => $v) {
	$s .= '&'.$k.'='.rawurlencode($v);
}

header('location: ../userapp.php?script=cp'.$s);

?>