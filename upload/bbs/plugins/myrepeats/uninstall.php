<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: uninstall.php 21227 2009-11-22 05:53:46Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

DROP TABLE cdb_myrepeats;

EOF;

runquery($sql);

$finish = TRUE;