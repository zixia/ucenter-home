<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: uninstall.php 20527 2009-10-04 07:06:47Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

DROP TABLE cdb_myapp;
DROP TABLE cdb_userapp;
DROP TABLE cdb_myinvite;
DROP TABLE cdb_mynotice;
UPDATE cdb_settings SET value='0' WHERE variable='my_status';
DELETE FROM cdb_prompttype WHERE `key`='mynotice';
DELETE FROM cdb_prompttype WHERE `key`='myinvite';

EOF;

runquery($sql);

$finish = TRUE;