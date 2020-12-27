<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: plugin.inc.php 20199 2009-09-22 00:08:56Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

list($identifier, $module) = explode(':', $id);
if(!is_array($plugins['modcp_'.$op]) || !array_key_exists($id, $plugins['modcp_'.$op])) {
	showmessage('undefined_action');
}
$directory = $plugins['modcp_'.$op][$id]['directory'];
if(empty($identifier) || !preg_match("/^[a-z]+[a-z0-9_]*\/$/i", $directory) || !preg_match("/^[a-z0-9_\-]+$/i", $module)) {
	showmessage('undefined_action');
}
if(@!file_exists(DISCUZ_ROOT.($modfile = './plugins/'.$directory.$module.'.inc.php'))) {
	showmessage('plugin_module_nonexistence');
}

if(@in_array($identifier, $pluginlangs)) {
	@include_once DISCUZ_ROOT.'./forumdata/cache/cache_scriptlang.php';
}

$modtpl = $identifier.':'.$module;

include DISCUZ_ROOT.$modfile;

?>