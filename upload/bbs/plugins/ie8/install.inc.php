<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc. & (C)2005-2009 mfboy
	This is NOT a freeware, use is subject to license terms

	$Id: install.inc.php 20544 2009-10-09 01:04:35Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_'.$identifier.'.php';
unset($name, $directory, $vars);

extract($_DPLUGIN['ie8'], EXTR_SKIP);
extract($vars);

if(!$ie8_acc_name || !$ie8_acc_category) {
	showmessage($adminid == 1 ? 'ie8:invalid_1' : 'ie8:invalid_2');
}

if(!empty($xml)) {
	$xml = "<?xml version=\"1.0\" encoding=\"$charset\"?>\n".
		"<os:openServiceDescription xmlns:os=\"http://www.microsoft.com/schemas/openservicedescription/1.0\">\n".
		"<os:homepageUrl>$boardurl</os:homepageUrl>\n".
		"<os:display>\n".
		"<os:name>$ie8_acc_name</os:name>\n".
		($ie8_acc_icon ? "<os:icon>$boardurl$ie8_acc_icon</os:icon>\n" : '').
		($ie8_acc_description ? "<os:description>$ie8_acc_description</os:description>\n" : '').
		"</os:display>\n".
		"<os:activity category=\"$ie8_acc_category\">\n".
		"<os:activityAction context=\"selection\">\n".
		"<os:execute method=\"post\" action=\"{$boardurl}plugin.php?id=$identifier:submit\">\n".
		"<os:parameter name=\"selection\" value=\"{selection}\" type=\"text\" />\n".
		"</os:execute>\n".
		"</os:activityAction>\n".
		"</os:activity>\n".
		"</os:openServiceDescription>";
	ob_end_clean();
	ob_start();

	header("Expires: -1");
	header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", false);
	header("Pragma: no-cache");
	header('Content-Encoding: none');
	header("Content-type: application/xml; charset=".$charset);
	echo $xml;
	exit;
} else {
	$navtitle = $name.' - ';
	include plugintemplate('ie8_install');
}

?>