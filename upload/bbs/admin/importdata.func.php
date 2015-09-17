<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: importdata.func.php 20026 2009-09-17 02:39:58Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

function import_request($importrewrite = 2) {
	global $db, $tablepre;
	$importarray = getimportdata('Discuz! Request', 0);
	$keys = implode("','", array_keys($importarray));

	if($importrewrite != 2) {
		$query = $db->query("SELECT variable FROM {$tablepre}request WHERE variable IN ('$keys')");
		$existkeyarray = array();
		while($existkey = $db->fetch_array($query)) {
			if($importrewrite == 1) {
				unset($importarray[$existkey['variable']]);
			} else {
				$existkeyarray[] = $existkey['variable'];
			}
		}

		if($importrewrite == 0 && $existkeyarray) {
			$existkeys = implode(", ", $existkeyarray);
			cpmsg('jswizard_import_exist', '', 'error');
		}
	}

	foreach($importarray as $key => $value) {
		$value = unserialize($value);
		$type = $value['type'];
		unset($value['type']);
		$value = addslashes(serialize($value));
		$db->query("REPLACE INTO {$tablepre}request (variable, value, `type`) VALUES ('$key', '$value', '$type')");
	}

	updatecache('request');
}

function import_project() {
	global $db, $tablepre, $version;
	$projectarray = getimportdata('Discuz! Project');

	$db->query("INSERT INTO {$tablepre}projects (name, type, description, value) VALUES ('$projectarray[name]', '$projectarray[type]', '$projectarray[description]', '$projectarray[value]')");
}

function import_smilies() {
	global $db, $tablepre;
	$smileyarray = getimportdata('Discuz! Smilies');

	$renamed = 0;
	if($db->result_first("SELECT COUNT(*) FROM {$tablepre}imagetypes WHERE type='smiley' AND name='$smileyarray[name]'")) {
		$smileyarray['name'] .= '_'.random(4);
		$renamed = 1;
	}
	$db->query("INSERT INTO {$tablepre}imagetypes (name, type, directory)
		VALUES ('$smileyarray[name]', 'smiley', '$smileyarray[directory]')");
	$typeid = $db->insert_id();

	foreach($smileyarray['smilies'] as $key => $smiley) {
		$db->query("INSERT INTO {$tablepre}smilies (type, typeid, displayorder, code, url)
			VALUES ('smiley', '$typeid', '$smiley[displayorder]', '', '$smiley[url]')");
	}
	$db->query("UPDATE {$tablepre}smilies SET code=CONCAT('{:', typeid, '_', id, ':}') WHERE typeid='$typeid'");

	updatecache(array('smileytypes', 'smilies', 'smileycodes', 'smilies_js'));
	return $renamed;
}

function import_styles($ignoreversion = 1, $dir = '') {
	global $db, $tablepre, $version, $importtxt, $stylearray;
	if(!isset($dir)) {
		$stylearrays = array(getimportdata('Discuz! Style'));
	} else {
		$dir = str_replace(array('/', '\\'), '', $dir);
		$templatedir = DISCUZ_ROOT.'./templates/'.$dir;
		$searchdir = dir($templatedir);
		$stylearrays = array();
		while($searchentry = $searchdir->read()) {
			if(substr($searchentry, 0, 13) == 'discuz_style_' && fileext($searchentry) == 'xml') {
				$importfile = $templatedir.'/'.$searchentry;
				$importtxt = implode('', file($importfile));
				$stylearrays[] = getimportdata('Discuz! Style');
			}
		}
	}

	foreach($stylearrays as $stylearray) {
		if(empty($ignoreversion) && strip_tags($stylearray['version']) != strip_tags($version)) {
			cpmsg('styles_import_version_invalid', '', 'error');
		}

		$renamed = 0;
		if($stylearray['templateid'] != 1) {
			$templatedir = DISCUZ_ROOT.'./'.$stylearray['directory'];
			if(!is_dir($templatedir)) {
				if(!@mkdir($templatedir, 0777)) {
					$basedir = dirname($stylearray['directory']);
					cpmsg('styles_import_directory_invalid', '', 'error');
				}
			}

			if(!($templateid = $db->result_first("SELECT templateid FROM {$tablepre}templates WHERE name='$stylearray[tplname]'"))) {
				$db->query("INSERT INTO {$tablepre}templates (name, directory, copyright)
					VALUES ('$stylearray[tplname]', '$stylearray[directory]', '$stylearray[copyright]')");
				$templateid = $db->insert_id();
			}
		} else {
			$templateid = 1;
		}

		if($db->result_first("SELECT COUNT(*) FROM {$tablepre}styles WHERE name='$stylearray[name]'")) {
			$stylearray['name'] .= '_'.random(4);
			$renamed = 1;
		}
		$db->query("INSERT INTO {$tablepre}styles (name, templateid)
			VALUES ('$stylearray[name]', '$templateid')");
		$styleidnew = $db->insert_id();

		foreach($stylearray['style'] as $variable => $substitute) {
			$substitute = @htmlspecialchars($substitute);
			$db->query("INSERT INTO {$tablepre}stylevars (styleid, variable, substitute)
				VALUES ('$styleidnew', '$variable', '$substitute')");
		}
	}

	updatecache('styles');
	updatecache('settings');
	return $renamed;
}

?>