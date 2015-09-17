<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: upgrade.php 21275 2009-11-24 08:21:28Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

ALTER TABLE cdb_myrepeats ADD COLUMN `comment` varchar(255) NOT NULL;

EOF;

runquery($sql);

$finish = TRUE;

?>