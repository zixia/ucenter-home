<?

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: plugin.php 20440 2009-09-28 01:13:19Z monkey $
*/

define('CURSCRIPT', 'plugin');

require_once './include/common.inc.php';

if(!empty($id)) {
	list($identifier, $module) = explode(':', $id);
	$module = $module !== NULL ? $module : $identifier;
}
$mnid = 'plugin_'.$identifier.'_'.$module;

$pluginmodule = isset($pluginlinks[$identifier][$module]) ? $pluginlinks[$identifier][$module] : (isset($plugins['script'][$identifier][$module]) ? $plugins['script'][$identifier][$module] : array('adminid' => 0, 'directory' => preg_match("/^[a-z]+[a-z0-9_]*$/i", $identifier) ? $identifier.'/' : ''));

if(empty($identifier) || !preg_match("/^[a-z0-9_\-]+$/i", $module) || !in_array($identifier, $plugins['available'])) {
	showmessage('undefined_action');
} elseif($pluginmodule['adminid'] && ($adminid < 1 || ($adminid > 0 && $pluginmodule['adminid'] < $adminid))) {
	showmessage('plugin_nopermission');
} elseif(@!file_exists(DISCUZ_ROOT.($modfile = './plugins/'.$pluginmodule['directory'].$module.'.inc.php'))) {
	showmessage('plugin_module_nonexistence');
}

if(@in_array($identifier, $pluginlangs)) {
	@include_once DISCUZ_ROOT.'./forumdata/cache/cache_scriptlang.php';
}

include DISCUZ_ROOT.$modfile;

function plugintemplate($file) {
	global $identifier, $pluginmodule;
	return template($file, $identifier, './plugins/'.$pluginmodule['directory'].'templates');
}

?>