<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: plugins.inc.php 21070 2009-11-10 06:59:40Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!empty($identifier) && !empty($mod)) {
	$operation = 'config';
}

$pluginid = !empty($_GET['pluginid']) ? intval($_GET['pluginid']) : 0;

if(!$operation) {

	if(!submitcheck('submit')) {

		shownav('plugin', 'nav_plugins');
		showsubmenu('nav_plugins', array(
			array('plugins_list', 'plugins', 1),
			array('import', 'plugins&operation=import', 0)
		));
		showformheader('plugins');
		showtableheader();
		showsubtitle(array('enable', 'plugins_name', 'version', 'copyright', 'plugins_directory', 'display_order', ''));

		$query = $db->query("SELECT * FROM {$tablepre}plugins ORDER BY available DESC, pluginid DESC");
		$installsdir = array();
		while($plugin = $db->fetch_array($query)) {
			$hookexists = FALSE;
			$plugin['modules'] = unserialize($plugin['modules']);
			if(is_array($plugin['modules'])) {
				foreach($plugin['modules'] as $k => $module) {
					if($module['type'] == 11) {
						$hookorder = $module['displayorder'];
						$hookexists = $k;
						break;
					}
				}
			}
			showtablerow('style="height:20px"', array('class="td25"', ($plugin['available'] ? 'class="bold"' : ''), 'class="td25"', '', '', 'class="td28 td23"', 'class="td24"'), array(
				"<input class=\"checkbox\"".($hookexists !== FALSE ? " onclick=\"display('displayorder_$plugin[pluginid]')\"" : '')." type=\"checkbox\" name=\"availablenew[$plugin[pluginid]]\" value=\"1\" ".(!$plugin['name'] || !$plugin['identifier'] ? 'disabled' : ($plugin['available'] ? 'checked' : '')).">",
				"<a href=\"$BASESCRIPT?action=plugins&operation=config&pluginid=$plugin[pluginid]\">".dhtmlspecialchars($plugin['name'])."</a>",
				dhtmlspecialchars($plugin['version']),
				dhtmlspecialchars($plugin['copyright']),
				$plugin['directory'],
				($hookexists !== FALSE ? "<input class=\"txt\" type=\"text\" id=\"displayorder_$plugin[pluginid]\" name=\"displayordernew[$plugin[pluginid]][$hookexists]\" value=\"$hookorder\" style=\"".(!$plugin['available'] ? 'display:none' : '')."\" />" : ''),
				"<a href=\"$BASESCRIPT?action=plugins&operation=delete&pluginid=$plugin[pluginid]\" class=\"act\">$lang[plugins_config_uninstall]</a>&nbsp;".
				"<a href=\"$BASESCRIPT?action=plugins&operation=edit&pluginid=$plugin[pluginid]\" class=\"act\">$lang[plugins_editlink]</a>"
			));
			$installsdir[] = $plugin['directory'];
		}

		$plugindir = DISCUZ_ROOT.'./plugins';
		$pluginsdir = dir($plugindir);
		$newplugins = array();
		while($entry = $pluginsdir->read()) {
			if(!in_array($entry, array('.', '..')) && is_dir($plugindir.'/'.$entry) && !in_array($entry.'/', $installsdir)) {
				$entrydir = DISCUZ_ROOT.'./plugins/'.$entry;
				$d = dir($entrydir);
				$filemtime = filemtime($entrydir);
				while($f = $d->read()) {
					if(preg_match('/^discuz\_plugin\_'.$entry.'(\_\w+)?\.xml$/', $f)) {
						$entrytitle = $entry;
						$entryversion = $entrycopyright = '';
						if(file_exists($entrydir.'/discuz_plugin_'.$entry.'.xml')) {
							$importtxt = @implode('', file($entrydir.'/discuz_plugin_'.$entry.'.xml'));
							$pluginarray = getimportdata('Discuz! Plugin', 1, 1);
							if(!empty($pluginarray['plugin']['name'])) {
								$entrytitle = dhtmlspecialchars($pluginarray['plugin']['name']);
								$entryversion = dhtmlspecialchars($pluginarray['plugin']['version']);
								$entrycopyright = dhtmlspecialchars($pluginarray['plugin']['copyright']);
							}
						}
						$file = $entrydir.'/'.$f;
						echo '<tr><td></td><td>'.$entrytitle.($filemtime > $timestamp - 86400 ? ' <font color="red">New!</font>' : '').'</td><td>'.$entryversion.'</td><td>'.$entrycopyright.'</td><td colspan="2">'.$entry.'/</td><td><a href="'.$BASESCRIPT.'?action=plugins&operation=import&dir='.$entry.'" class="act">'.$lang['plugins_config_install'].'</a></td></tr>';
						break;
					}
				}
			}
		}

		showsubmit('submit', 'submit', '', '<a href="'.$BASESCRIPT.'?action=plugins&operation=add">'.$lang['plugins_add'].'</a> &nbsp; <a href="'.$BASESCRIPT.'?action=plugins&operation=hooklist">'.$lang['plugins_hooklist'].'</a>');
		showtablefooter();
		showformfooter();

	} else {

		$db->query("UPDATE {$tablepre}plugins SET available='0'");
		if(is_array($availablenew)) {
			$query = $db->query("SELECT pluginid, modules FROM {$tablepre}plugins WHERE pluginid IN (".implodeids(array_keys($availablenew)).")");
			while($plugin = $db->fetch_array($query)) {
				if(!empty($displayordernew[$plugin['pluginid']])) {
					$plugin['modules'] = unserialize($plugin['modules']);
					$k = array_keys($displayordernew[$plugin['pluginid']]);
					$v = array_values($displayordernew[$plugin['pluginid']]);
					$plugin['modules'][$k[0]]['displayorder'] = $v[0];
					$plugin['modules'] = addslashes(serialize($plugin['modules']));
					$moduleadd = "modules='".$plugin['modules']."', ";
				} else {
					$moduleadd = '';
				}
				$db->query("UPDATE {$tablepre}plugins SET {$moduleadd}available='".$availablenew[$plugin['pluginid']]."' WHERE pluginid='$plugin[pluginid]'");
			}
		}

		updatecache('plugins');
		updatecache('settings');
		updatemenu();
		cpmsg('plugins_edit_succeed', $BASESCRIPT.'?action=plugins', 'succeed');

	}

} elseif($operation == 'hooklist') {

		shownav('plugin', 'nav_plugins');
		showsubmenu('nav_plugins', array(
			array('plugins_list', 'plugins', 0),
			array('import', 'plugins&operation=import', 0),
			array('plugins_hooklist', 'plugins&operation=hooklist', 1)
		));

		$plugins = $funcs = array();
		$query = $db->query("SELECT pluginid, name, identifier FROM {$tablepre}plugins WHERE available='1'");
		while($plugin = $db->fetch_array($query)) {
			$plugins[$plugin['identifier']] = $plugin;
		}

		showtableheader();
		$rows = '';
		if($hookscript) {
			foreach($hookscript as $script => $value) {
				if($script == 'plugin') {
					continue;
				}
				$funcs = array();
				$scripttitle = in_array($script, array('index', 'profile', 'global')) ? $lang['plugins_hooklist_script_'.$script] : (file_exists(DISCUZ_ROOT.'./'.$script.'.php') ? $script.'.php' : $script);
				showsubtitle(array($scripttitle, ''));
				if(is_array($value['funcs'])) {
					foreach($value['funcs'] as $hookkey => $hookfuncs) {
						foreach($hookfuncs as $hookfunc) {
							$funcs[$hookfunc[1]][] = $hookfunc[0];
						}
					}
				}
				if(is_array($value['outputfuncs'])) {
					foreach($value['outputfuncs'] as $hookkey => $hookfuncs) {
						foreach($hookfuncs as $hookfunc) {
							$hookfunc[1] = preg_replace('/\_output$/', '', $hookfunc[1]);
							$funcs[$hookfunc[1]][] = $hookfunc[0];
						}
					}
				}
				foreach($funcs as $func => $v) {
					echo '<tr><td valign="top" width="30%">';
					$first = 0;
					$v = array_unique($v);
					foreach($v as $plugin) {
						if(!$first++) {
							echo (isset($lang['plugins_hooklist__'.$func]) ? $lang['plugins_hooklist__'.$func] : $lang['plugins_hooklist_userdefine'].':'.$func).'</td><td>';
						}
						echo '<a href="'.$BASESCRIPT.'?action=plugins&operation=config&pluginid='.$plugins[$plugin]['pluginid'].'">'.$plugins[$plugin]['name'].'</a><br>';
					}
					echo '</td></tr>';
				}
			}
		} else {
			echo '<div class="infobox"><h4 class="infotitle2">'.$lang['plugins_hooklist_empty'].'</h4></div>';
		}
		showtablefooter();

} elseif($operation == 'export' && $pluginid) {

	$plugin = $db->fetch_first("SELECT * FROM {$tablepre}plugins WHERE pluginid='$pluginid'");
	if(!$plugin) {
		cpheader();
		cpmsg('undefined_action', '', 'error');
	}

	unset($plugin['pluginid']);

	$pluginarray = array();
	$pluginarray['plugin'] = $plugin;
	$pluginarray['version'] = strip_tags($version);

	$query = $db->query("SELECT * FROM {$tablepre}pluginhooks WHERE pluginid='$pluginid'");
	while($hook = $db->fetch_array($query)) {
		unset($hook['pluginhookid'], $hook['pluginid']);
		$pluginarray['hooks'][] = $hook;
	}

	$query = $db->query("SELECT * FROM {$tablepre}pluginvars WHERE pluginid='$pluginid'");
	while($var = $db->fetch_array($query)) {
		unset($var['pluginvarid'], $var['pluginid']);
		$pluginarray['vars'][] = $var;
	}
	$modules = unserialize($pluginarray['plugin']['modules']);
	if($modules['extra']['langexists'] && file_exists($file = DISCUZ_ROOT.'./forumdata/plugins/'.$pluginarray['plugin']['identifier'].'.lang.php')) {
		include $file;
		if(!empty($scriptlang[$pluginarray['plugin']['identifier']])) {
			$pluginarray['language']['scriptlang'] = $scriptlang[$pluginarray['plugin']['identifier']];
		}
		if(!empty($templatelang[$pluginarray['plugin']['identifier']])) {
			$pluginarray['language']['templatelang'] = $templatelang[$pluginarray['plugin']['identifier']];
		}
		if(!empty($installlang[$pluginarray['plugin']['identifier']])) {
			$pluginarray['language']['installlang'] = $installlang[$pluginarray['plugin']['identifier']];
		}
	}
	unset($modules['extra']);
	$pluginarray['plugin']['modules'] = serialize($modules);

	exportdata('Discuz! Plugin', $plugin['identifier'], $pluginarray);

} elseif($operation == 'import') {

	if(!submitcheck('importsubmit') && !isset($dir)) {

		shownav('plugin', 'nav_plugins');
		showsubmenu('nav_plugins', array(
			array('plugins_list', 'plugins', 0),
			array('import', 'plugins&operation=import', 1)
		));
		showformheader('plugins&operation=import', 'enctype');
		showtableheader('plugins_import', 'fixpadding');
		showimportdata();
		showtablerow('', '', '<input type="checkbox" name="ignoreversion" value="1" class="checkbox" /> '.lang('plugins_import_ignore_version'));
		showsubmit('importsubmit');
		showtablefooter();
		showformfooter();

	} else {

		if(!isset($dir)) {
			$pluginarray = getimportdata('Discuz! Plugin');
		} elseif(!isset($installtype)) {
			$pdir = DISCUZ_ROOT.'./plugins/'.$dir;
			$d = dir($pdir);
			$xmls = '';$count = 0;
			while($f = $d->read()) {
				if(preg_match('/^discuz\_plugin_'.$dir.'(\_\w+)?\.xml$/', $f, $a)) {
					$extratxt = $extra = substr($a[1], 1);
					if(preg_match('/^SC\_GBK$/i', $extra)) {
						$extratxt = '&#31616;&#20307;&#20013;&#25991;&#29256;';
					} elseif(preg_match('/^SC\_UTF8$/i', $extra)) {
						$extratxt = '&#31616;&#20307;&#20013;&#25991;&#85;&#84;&#70;&#56;&#29256;';
					} elseif(preg_match('/^TC\_BIG5$/i', $extra)) {
						$extratxt = '&#32321;&#39636;&#20013;&#25991;&#29256;';
					} elseif(preg_match('/^TC\_UTF8$/i', $extra)) {
						$extratxt = '&#32321;&#39636;&#20013;&#25991;&#85;&#84;&#70;&#56;&#29256;';
					}
					$url = $BASESCRIPT.'?action=plugins&operation=import&dir='.$dir.'&installtype='.$extra.(!empty($referer) ? '&referer='.rawurlencode($referer) : '');
					$xmls .= '&nbsp;<input type="button" class="btn" onclick="location.href=\''.$url.'\'" value="'.($extra ? $extratxt : $lang['plugins_import_default']).'">&nbsp;';
					$count++;
				}
			}
			$xmls .= '<br /><br /><input class="btn" onclick="location.href=\''.$BASESCRIPT.'?action=plugins\'" type="button" value="'.$lang['cancel'].'"/>';
			if($count == 1) {
				dheader('location: '.$url);
			}
			echo '<div class="infobox"><h4 class="infotitle2">'.$lang['plugins_import_installtype_1'].' '.$dir.' '.$lang['plugins_import_installtype_2'].' '.$count.' '.$lang['plugins_import_installtype_3'].'</h4>'.$xmls.'</div>';
			exit;
		} else {
			$extra = $installtype ? '_'.$installtype : '';
			$importfile = DISCUZ_ROOT.'./plugins/'.$dir.'/discuz_plugin_'.$dir.$extra.'.xml';
			$importtxt = @implode('', file($importfile));
			$pluginarray = getimportdata('Discuz! Plugin');
			if(empty($license) && $pluginarray['license']) {
				require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
				$pluginarray['license'] = discuzcode(stripslashes(strip_tags($pluginarray['license'])), 1, 0);
				echo '<div class="infobox"><h4 class="infotitle2">'.$pluginarray['plugin']['name'].' '.$pluginarray['plugin']['version'].' '.$lang['plugins_import_license'].'</h4><div style="text-align:left;line-height:25px;">'.$pluginarray['license'].'</div><br /><br /><center>'.
					'<button onclick="location.href=\''.$BASESCRIPT.'?action=plugins&operation=import&dir='.$dir.'&installtype='.$installtype.'&license=yes\'">'.$lang['plugins_import_agree'].'</button>&nbsp;&nbsp;'.
					'<button onclick="location.href=\''.$BASESCRIPT.'?action=plugins\'">'.$lang['plugins_import_pass'].'</button></center></div>';
				exit;
			}
		}

		if(!ispluginkey($pluginarray['plugin']['identifier'])) {
			cpmsg('plugins_edit_identifier_invalid', '', 'error');
		}
		if(!ispluginkey($pluginarray['plugin']['identifier'])) {
			cpmsg('plugins_edit_identifier_invalid', '', 'error');
		}
		if(is_array($pluginarray['hooks'])) {
			foreach($pluginarray['hooks'] as $config) {
				if(!ispluginkey($config['title'])) {
					cpmsg('plugins_import_hooks_title_invalid', '', 'error');
				}
			}
		}
		if(is_array($pluginarray['vars'])) {
			foreach($pluginarray['vars'] as $config) {
				if(!ispluginkey($config['variable'])) {
					cpmsg('plugins_import_var_invalid', '', 'error');
				}
			}
		}

		$langexists = FALSE;
		if(!empty($pluginarray['language'])) {
			@mkdir('./forumdata/plugins/', 0777);
			$file = DISCUZ_ROOT.'./forumdata/plugins/'.$pluginarray['plugin']['identifier'].'.lang.php';
			if($fp = @fopen($file, 'wb')) {
				$scriptlangstr = !empty($pluginarray['language']['scriptlang']) ? "\$scriptlang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['scriptlang']) : '';
				$templatelangstr = !empty($pluginarray['language']['templatelang']) ? "\$templatelang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['templatelang']) : '';
				$installlangstr = !empty($pluginarray['language']['installlang']) ? "\$installlang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['installlang']) : '';
				fwrite($fp, "<?php\n".$scriptlangstr.$templatelangstr.$installlangstr.'?>');
				fclose($fp);
			}
			$langexists = TRUE;
		}

		if(empty($ignoreversion) && strip_tags($pluginarray['version']) != strip_tags($version)) {
			if(isset($dir)) {
				cpmsg('plugins_import_version_invalid_confirm', $BASESCRIPT.'?action=plugins&operation=import&ignoreversion=yes&dir='.$dir.'&installtype='.$installtype.'&license='.$license, 'form');
			} else {
				cpmsg('plugins_import_version_invalid', '', 'error');
			}
		}

		$plugin = $db->fetch_first("SELECT name, pluginid FROM {$tablepre}plugins WHERE identifier='{$pluginarray[plugin][identifier]}' LIMIT 1");
		if($plugin) {
			cpmsg('plugins_import_identifier_duplicated', '', 'error');
		}

		if(!empty($pluginarray['intro']) || $langexists) {
			$pluginarray['plugin']['modules'] = unserialize(stripslashes($pluginarray['plugin']['modules']));
			if(!empty($pluginarray['intro'])) {
				require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
				$pluginarray['plugin']['modules']['extra']['intro'] = discuzcode(stripslashes(strip_tags($pluginarray['intro'])), 1, 0);
			}
			$langexists && $pluginarray['plugin']['modules']['extra']['langexists'] = 1;
			$pluginarray['plugin']['modules'] = addslashes(serialize($pluginarray['plugin']['modules']));
		}

		$sql1 = $sql2 = $comma = '';
		foreach($pluginarray['plugin'] as $key => $val) {
			if($key == 'directory') {
				$val .= (!empty($val) && substr($val, -1) != '/') ? '/' : '';
			} elseif($key == 'available') {
				$val = 0;
			}
			$sql1 .= $comma.$key;
			$sql2 .= $comma.'\''.$val.'\'';
			$comma = ',';
		}
		$db->query("INSERT INTO {$tablepre}plugins ($sql1) VALUES ($sql2)");
		$pluginid = $db->insert_id();

		foreach(array('hooks', 'vars') as $pluginconfig) {
			if(is_array($pluginarray[$pluginconfig])) {
				foreach($pluginarray[$pluginconfig] as $config) {
					$sql1 = 'pluginid';
					$sql2 = '\''.$pluginid.'\'';
					foreach($config as $key => $val) {
						$sql1 .= ','.$key;
						$sql2 .= ',\''.$val.'\'';
					}
					$db->query("INSERT INTO {$tablepre}plugin$pluginconfig ($sql1) VALUES ($sql2)");
				}
			}
		}
		if(!empty($dir) && !empty($pluginarray['importfile'])) {
			require_once DISCUZ_ROOT.'./admin/importdata.func.php';
			foreach($pluginarray['importfile'] as $importtype => $file) {
				if(in_array($importtype, array('request', 'project', 'smilies', 'styles'))) {
					$files = explode(',', $file);
					foreach($files as $file) {
						if(file_exists($file = DISCUZ_ROOT.'./plugins/'.$dir.'/'.$file)) {
							$importtxt = @implode('', file($file));
							$imporfun = 'import_'.$importtype;
							$imporfun();
						}
					}
				}
			}
		}

		updatecache('plugins');
		updatecache('settings');
		updatemenu();

		if(!empty($dir) && !empty($pluginarray['installfile']) && preg_match('/^[\w\.]+$/', $pluginarray['installfile'])) {
			dheader('location: '.$BASESCRIPT.'?action=plugins&operation=plugininstall&dir='.$dir.'&installtype='.$installtype);
		}

		pluginstat('install', $pluginarray['plugin']);
		cpmsg(!empty($dir) ? 'plugins_install_succeed' : 'plugins_import_succeed', !empty($referer) ? $referer : $BASESCRIPT.'?action=plugins', 'succeed');

	}

} elseif($operation == 'plugininstall' || $operation == 'pluginuninstall' || $operation == 'pluginupgrade') {

	$finish = FALSE;
	$dir = str_replace('/', '', $dir);
	$installtype = str_replace('/', '', $installtype);
	$extra = $installtype ? '_'.$installtype : '';
	$xmlfile = !empty($xmlfile) && preg_match('/^[\w\.]+$/', $xmlfile) ? $xmlfile : 'discuz_plugin_'.$dir.$extra.'.xml';
	$importfile = DISCUZ_ROOT.'./plugins/'.$dir.'/'.$xmlfile;
	if(!file_exists($importfile)) {
		cpmsg('undefined_action', '', 'error');
	}
	$importtxt = @implode('', file($importfile));
	$pluginarray = getimportdata('Discuz! Plugin');
	if($operation == 'plugininstall') {
		$filename = $pluginarray['installfile'];
	} elseif($operation == 'pluginuninstall') {
		$filename = $pluginarray['uninstallfile'];
	} else {
		$filename = $pluginarray['upgradefile'];
		$toversion = $pluginarray['plugin']['version'];
	}
	if(file_exists($langfile = DISCUZ_ROOT.'./forumdata/plugins/'.$dir.'.lang.php')) {
		@include $langfile;
	}
	if(!empty($filename) && preg_match('/^[\w\.]+$/', $filename)) {
		$filename = DISCUZ_ROOT.'./plugins/'.$dir.'/'.$filename;
		if(file_exists($filename)) {
			@include_once $filename;
		} else {
			$finish = TRUE;
		}
	} else {
		$finish = TRUE;
	}

	if($finish) {
		updatecache('settings');
		updatemenu();
		if($operation == 'plugininstall') {
			pluginstat('install', $pluginarray['plugin']);
			cpmsg('plugins_install_succeed', "$BASESCRIPT?action=plugins", 'succeed');
		} if($operation == 'pluginuninstall') {
			@unlink($langfile);
			pluginstat('uninstall', $pluginarray['plugin']);
			cpmsg('plugins_delete_succeed', "$BASESCRIPT?action=plugins", 'succeed');
		} else {
			pluginstat('upgrade', $pluginarray['plugin']);
			cpmsg('plugins_upgrade_succeed', "$BASESCRIPT?action=plugins", 'succeed');
		}
	}

} elseif($operation == 'upgrade' && preg_match('/^[\w\.]+$/', $xmlfile)) {

	$plugin = $db->fetch_first("SELECT directory, modules, version FROM {$tablepre}plugins WHERE pluginid='$pluginid'");
	$importfile = DISCUZ_ROOT.'./plugins/'.$plugin['directory'].$xmlfile;
	if(!file_exists($importfile)) {
		cpmsg('undefined_action', '', 'error');
	}
	$importtxt = @implode('', file($importfile));
	$pluginarray = getimportdata('Discuz! Plugin');

	if(!ispluginkey($pluginarray['plugin']['identifier'])) {
		cpmsg('plugins_edit_identifier_invalid', '', 'error');
	}
	if(is_array($pluginarray['hooks'])) {
		foreach($pluginarray['hooks'] as $config) {
			if(!ispluginkey($config['title'])) {
				cpmsg('plugins_upgrade_hooks_title_invalid', '', 'error');
			}
		}
	}
	if(is_array($pluginarray['vars'])) {
		foreach($pluginarray['vars'] as $config) {
			if(!ispluginkey($config['variable'])) {
				cpmsg('plugins_upgrade_var_invalid', '', 'error');
			}
		}
	}

	if(is_array($pluginarray['hooks'])) {
		$db->query("DELETE FROM {$tablepre}pluginhooks WHERE pluginid='$pluginid'");
		foreach($pluginarray['hooks'] as $config) {
			$sql1 = 'pluginid';
			$sql2 = '\''.$pluginid.'\'';
			foreach($config as $key => $val) {
				$sql1 .= ','.$key;
				$sql2 .= ',\''.$val.'\'';
			}
			$db->query("INSERT INTO {$tablepre}pluginhooks ($sql1) VALUES ($sql2)");
		}
	}
	if(is_array($pluginarray['vars'])) {
		$query = $db->query("SELECT variable FROM {$tablepre}pluginvars WHERE pluginid='$pluginid'");
		$pluginvars = $pluginvarsnew = array();
		while($pluginvar = $db->fetch_array($query)) {
			$pluginvars[] = $pluginvar['variable'];
		}
		foreach($pluginarray['vars'] as $config) {
			if(!in_array($config['variable'], $pluginvars)) {
				$sql1 = 'pluginid';
				$sql2 = '\''.$pluginid.'\'';
				foreach($config as $key => $val) {
					$sql1 .= ','.$key;
					$sql2 .= ',\''.$val.'\'';
				}
				$db->query("INSERT INTO {$tablepre}pluginvars ($sql1) VALUES ($sql2)");
			} else {
				$sql = $comma = '';
				foreach($config as $key => $val) {
					if($key != 'value') {
						$sql .= $comma.$key.'=\''.$val.'\'';
						$comma = ',';
					}
				}
				if($sql) {
					$db->query("UPDATE {$tablepre}pluginvars SET $sql WHERE pluginid='$pluginid' AND variable='$config[variable]'");
				}
			}
			$pluginvarsnew[] = $config['variable'];
		}
		$pluginvardiff = array_diff($pluginvars, $pluginvarsnew);
		if($pluginvardiff) {
			$db->query("DELETE FROM {$tablepre}pluginvars WHERE pluginid='$pluginid' AND variable IN (".implodeids($pluginvardiff).")");
		}
	}
	$langexists = FALSE;
	if(!empty($pluginarray['language'])) {
		@mkdir('./forumdata/plugins/', 0777);
		$file = DISCUZ_ROOT.'./forumdata/plugins/'.$pluginarray['plugin']['identifier'].'.lang.php';
		if($fp = @fopen($file, 'wb')) {
			$scriptlangstr = !empty($pluginarray['language']['scriptlang']) ? "\$scriptlang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['scriptlang']) : '';
			$templatelangstr = !empty($pluginarray['language']['templatelang']) ? "\$templatelang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['templatelang']) : '';
			$installlangstr = !empty($pluginarray['language']['installlang']) ? "\$installlang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['installlang']) : '';
			fwrite($fp, "<?php\n".$scriptlangstr.$templatelangstr.$installlangstr.'?>');
			fclose($fp);
		}
		$langexists = TRUE;
	}

	if(!empty($pluginarray['intro']) || $langexists) {
		$pluginarray['plugin']['modules'] = unserialize(stripslashes($pluginarray['plugin']['modules']));
		if(!empty($pluginarray['intro'])) {
			require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
			$pluginarray['plugin']['modules']['extra']['intro'] = discuzcode(stripslashes(strip_tags($pluginarray['intro'])), 1, 0);
		}
		$langexists && $pluginarray['plugin']['modules']['extra']['langexists'] = 1;
		$pluginarray['plugin']['modules'] = addslashes(serialize($pluginarray['plugin']['modules']));
	}
	$modulenew = $pluginarray['modules'];

	$db->query("UPDATE {$tablepre}plugins SET version='{$pluginarray[plugin][version]}', modules='{$pluginarray[plugin][modules]}' WHERE pluginid='$pluginid'");

	updatecache('plugins');
	updatecache('settings');

	if(!empty($plugin['directory']) && !empty($pluginarray['upgradefile']) && preg_match('/^[\w\.]+$/', $pluginarray['upgradefile'])) {
		dheader('location: '.$BASESCRIPT.'?action=plugins&operation=pluginupgrade&dir='.$plugin['directory'].'&xmlfile='.rawurlencode($xmlfile).'&fromversion='.$plugin['version']);
	}
	$toversion = $pluginarray['plugin']['version'];

	pluginstat('upgrade', $pluginarray['plugin']);
	cpmsg('plugins_upgrade_succeed', "$BASESCRIPT?action=plugins", 'succeed');

} elseif($operation == 'config') {

	$plugin = $db->fetch_first("SELECT * FROM {$tablepre}plugins WHERE ".($identifier ? "identifier='$identifier'" : "pluginid='$pluginid'"));
	if(!$plugin) {
		cpmsg('undefined_action', '', 'error');
	} else {
		$pluginid = $plugin['pluginid'];
	}
	$plugin['modules'] = unserialize($plugin['modules']);

	$pluginvars = array();
	$query = $db->query("SELECT * FROM {$tablepre}pluginvars WHERE pluginid='$pluginid' ORDER BY displayorder");
	while($var = $db->fetch_array($query)) {
		$pluginvars[$var['variable']] = $var;
	}

	$anchor = in_array($anchor, array('home', 'vars')) ? $anchor : 'home';
	if(!$mod) {
		$submenuitem = array(array('plugins_home', 'home', $anchor == 'home'));
		if($pluginvars) {
			$submenuitem[] = array('config', 'vars', $anchor == 'vars');
		}
	} else {
		$submenuitem = array(array('plugins_home', "plugins&operation=config&pluginid=$pluginid&anchor=home", 0));
		if($pluginvars) {
			$submenuitem[] = array('config', "plugins&operation=config&pluginid=$pluginid&anchor=vars", 0);
		}
	}
	if(is_array($plugin['modules'])) {
		foreach($plugin['modules'] as $module) {
			if($module['type'] == 3) {
				$submenuitem[] = array($module['menu'], "plugins&operation=config&identifier=$plugin[identifier]&mod=$module[name]", $mod == $module['name'], !$mod ? 1 : 0);
			}
		}
	}

	if(empty($mod)) {

		if(!submitcheck('editsubmit')) {
			$operation = '';
			shownav('plugin', 'nav_plugins', $plugin['name']);
			showsubmenuanchors($plugin['name'].(!$plugin['available'] ? ' ('.$lang['plugins_unavailable'].')' : ''), $submenuitem);

			showtagheader('div', 'home', $anchor == 'home');

			if($plugin['description'] || $plugin['copyright'] || $plugin['modules']['extra']['intro']) {
				echo '<div class="colorbox" style="line-height:25px">'.(!empty($plugin['modules']['extra']['intro']) ? $plugin['modules']['extra']['intro'].'<br />' : '').nl2br($plugin['description']).'<br /><div style="width:95%;height:30px !important;" style="clear:both"><div style="float:right">'.$plugin['copyright'].'</div></div></div><br /><br />';
			}

			showtagfooter('div');

			showtagheader('div', 'vars', $anchor == 'vars');

			if($pluginvars) {
				showformheader("plugins&operation=config&pluginid=$pluginid");
				showtableheader();
				showtitle($lang['plugins_config']);

				$extra = array();
				foreach($pluginvars as $var) {
					$var['variable'] = 'varsnew['.$var['variable'].']';
					if($var['type'] == 'number') {
						$var['type'] = 'text';
					} elseif($var['type'] == 'select') {
						$var['type'] = "<select name=\"$var[variable]\">\n";
						foreach(explode("\n", $var['extra']) as $key => $option) {
							$option = trim($option);
							if(strpos($option, '=') === FALSE) {
								$key = $option;
							} else {
								$item = explode('=', $option);
								$key = trim($item[0]);
								$option = trim($item[1]);
							}
							$var['type'] .= "<option value=\"".dhtmlspecialchars($key)."\" ".($var['value'] == $key ? 'selected' : '').">$option</option>\n";
						}
						$var['type'] .= "</select>\n";
						$var['variable'] = $var['value'] = '';
					} elseif($var['type'] == 'date') {
						$var['type'] = 'calendar';
						$extra['date'] = '<script type="text/javascript" src="include/js/calendar.js"></script>';
					} elseif($var['type'] == 'datetime') {
						$var['type'] = 'calendar';
						$var['extra'] = 1;
						$extra['date'] = '<script type="text/javascript" src="include/js/calendar.js"></script>';
					} elseif($var['type'] == 'forum') {
						require_once DISCUZ_ROOT.'./include/forum.func.php';
						$var['type'] = '<select name="'.$var['variable'].'"><option value="">'.lang('plugins_empty').'</option>'.forumselect(FALSE, 0, $var['value'], TRUE).'</select>';
						$var['variable'] = $var['value'] = '';
					} elseif($var['type'] == 'forums') {
						$var['description'] = ($var['description'] ? (isset($lang[$var['description']]) ? $lang[$var['description']] : $var['description']).'<br />' : '').$lang['plugins_edit_vars_multiselect_comment'].'<br />'.$var['comment'];
						$var['value'] = unserialize($var['value']);
						$var['value'] = is_array($var['value']) ? $var['value'] : array();
						require_once DISCUZ_ROOT.'./include/forum.func.php';
						$var['type'] = '<select name="'.$var['variable'].'[]" size="10" multiple="multiple"><option value="">'.lang('plugins_empty').'</option>'.forumselect(FALSE, 0, 0, TRUE).'</select>';
						foreach($var['value'] as $v) {
							$var['type'] = str_replace('<option value="'.$v.'">', '<option value="'.$v.'" selected>', $var['type']);
						}
						$var['variable'] = $var['value'] = '';
					} elseif(substr($var['type'], 0, 5) == 'group') {
						if($var['type'] == 'groups') {
							$var['description'] = ($var['description'] ? (isset($lang[$var['description']]) ? $lang[$var['description']] : $var['description']).'<br />' : '').$lang['plugins_edit_vars_multiselect_comment'].'<br />'.$var['comment'];
							$var['value'] = unserialize($var['value']);
							$var['type'] = '<select name="'.$var['variable'].'[]" size="10" multiple="multiple"><option value=""'.(@in_array('', $var['value']) ? ' selected' : '').'>'.lang('plugins_empty').'</option>';
						} else {
							$var['type'] = '<select name="'.$var['variable'].'"><option value="">'.lang('plugins_empty').'</option>';
						}
						$var['value'] = is_array($var['value']) ? $var['value'] : array($var['value']);

						$query = $db->query("SELECT type, groupid, grouptitle, radminid FROM {$tablepre}usergroups ORDER BY (creditshigher<>'0' || creditslower<>'0'), creditslower, groupid");
						$groupselect = array();
						while($group = $db->fetch_array($query)) {
							$group['type'] = $group['type'] == 'special' && $group['radminid'] ? 'specialadmin' : $group['type'];
							$groupselect[$group['type']] .= '<option value="'.$group['groupid'].'"'.(@in_array($group['groupid'], $var['value']) ? ' selected' : '').'>'.$group['grouptitle'].'</option>';
						}
						$var['type'] .= '<optgroup label="'.$lang['usergroups_member'].'">'.$groupselect['member'].'</optgroup>'.
							($groupselect['special'] ? '<optgroup label="'.$lang['usergroups_special'].'">'.$groupselect['special'].'</optgroup>' : '').
							($groupselect['specialadmin'] ? '<optgroup label="'.$lang['usergroups_specialadmin'].'">'.$groupselect['specialadmin'].'</optgroup>' : '').
							'<optgroup label="'.$lang['usergroups_system'].'">'.$groupselect['system'].'</optgroup></select>';
						$var['variable'] = $var['value'] = '';
					} elseif($var['type'] == 'extcredit') {
						$var['type'] = '<select name="'.$var['variable'].'"><option value="">'.lang('plugins_empty').'</option>';
						foreach($extcredits as $id => $credit) {
							$var['type'] .= '<option value="'.$id.'"'.($var['value'] == $id ? ' selected' : '').'>'.$credit['title'].'</option>';
						}
						$var['type'] .= '</select>';
						$var['variable'] = $var['value'] = '';
					}

					showsetting(isset($lang[$var['title']]) ? $lang[$var['title']] : $var['title'], $var['variable'], $var['value'], $var['type'], '', 0, isset($lang[$var['description']]) ? $lang[$var['description']] : nl2br($var['description']), $var['extra']);
				}
				showsubmit('editsubmit');
				showtablefooter();
				showformfooter();
				echo implode('', $extra);
			}

		} else {

			if(is_array($varsnew)) {
				foreach($varsnew as $variable => $value) {
					if(isset($pluginvars[$variable])) {
						if($pluginvars[$variable]['type'] == 'number') {
							$value = (float)$value;
						} elseif(in_array($pluginvars[$variable]['type'], array('forums', 'groups'))) {
							$value = addslashes(serialize($value));
						}
						$db->query("UPDATE {$tablepre}pluginvars SET value='$value' WHERE pluginid='$pluginid' AND variable='$variable'");
					}
				}
			}

			updatecache('plugins');
			cpmsg('plugins_settings_succeed', $BASESCRIPT.'?action=plugins&operation=config&pluginid='.$pluginid.'&anchor='.$anchor, 'succeed');

		}

	} else {

		$modfile = '';
		if(is_array($plugin['modules'])) {
			foreach($plugin['modules'] as $module) {
				if($module['type'] == 3 && $module['name'] == $mod) {
					$plugin['directory'] .= (!empty($plugin['directory']) && substr($plugin['directory'], -1) != '/') ? '/' : '';
					$modfile = './plugins/'.$plugin['directory'].$module['name'].'.inc.php';
					break;
				}
			}
		}

		if($modfile) {
			if(!empty($plugin['modules']['extra']['langexists'])) {
				@include_once DISCUZ_ROOT.'./forumdata/plugins/'.$plugin['identifier'].'.lang.php';
			}
			shownav('plugin', 'nav_plugins', $plugin['name']);
			showsubmenu($plugin['name'].(!$plugin['available'] ? ' ('.$lang['plugins_unavailable'] : ''), $submenuitem);
			if(!@include DISCUZ_ROOT.$modfile) {
				cpmsg('plugins_settings_module_nonexistence', '', 'error');
			} else {
				dexit();
			}
		} else {
			cpmsg('undefined_action', '', 'error');
		}

	}

} elseif($operation == 'add') {

	if(!submitcheck('addsubmit')) {
		shownav('plugin', 'nav_plugins');
		showsubmenu('nav_plugins', array(
			array('plugins_list', 'plugins', 0),
			array('import', 'plugins&operation=import', 0),
			array('plugins_add', 'plugins&operation=add', 1)
		));
		showtips('plugins_add_tips');

		showformheader("plugins&operation=add", '', 'configform');
		showtableheader();
		showsetting('plugins_edit_name', 'namenew', '', 'text');
		showsetting('plugins_edit_copyright', 'copyrightnew', '', 'text');
		showsetting('plugins_edit_identifier', 'identifiernew', '', 'text');
		showsubmit('addsubmit');
		showtablefooter();
		showformfooter();
	} else {
		$namenew	= dhtmlspecialchars(trim($namenew));
		$identifiernew	= trim($identifiernew);
		$copyrightnew	= dhtmlspecialchars($copyrightnew);

		if(!$namenew) {
			cpmsg('plugins_edit_name_invalid', '', 'error');
		} else {
			$query = $db->query("SELECT pluginid FROM {$tablepre}plugins WHERE identifier='$identifiernew' LIMIT 1");
			if($db->num_rows($query) || !ispluginkey($identifiernew)) {
				cpmsg('plugins_edit_identifier_invalid', '', 'error');
			}
		}

		$db->query("INSERT INTO {$tablepre}plugins (name, identifier, directory, available, copyright) VALUES ('$namenew', '$identifiernew', '$identifiernew/', '0', '$copyrightnew')");
		$pluginid = $db->insert_id();
		updatecache('plugins');
		cpmsg('plugins_add_succeed', "$BASESCRIPT?action=plugins&operation=edit&pluginid=$pluginid", 'succeed');
	}

} elseif($operation == 'edit') {

	if(empty($pluginid) ) {
		$pluginlist = '<select name="pluginid">';
		$query = $db->query("SELECT pluginid, name FROM {$tablepre}plugins");
		while($plugin = $db->fetch_array($query)) {
			$pluginlist .= '<option value="'.$plugin['pluginid'].'">'.$plugin['name'].'</option>';
		}
		$pluginlist .= '</select>';
		cpmsg('plugins_nonexistence', $BASESCRIPT.'?action=plugins&operation=edit'.(!empty($highlight) ? "&highlight=$highlight" : ''), 'form', $pluginlist);
	} else {
		$condition = !empty($uid) ? "uid='$uid'" : "username='$username'";
	}

	$plugin = $db->fetch_first("SELECT * FROM {$tablepre}plugins WHERE pluginid='$pluginid'");
	if(!$plugin) {
		cpmsg('undefined_action', '', 'error');
	}

	$plugin['modules'] = unserialize($plugin['modules']);

	if(!submitcheck('editsubmit')) {

		$adminidselect = array($plugin['adminid'] => 'selected');

		shownav('plugin', 'nav_plugins');
		$anchor = in_array($anchor, array('config', 'modules', 'hooks', 'vars')) ? $anchor : 'config';
		showsubmenuanchors($lang['plugins_edit'].' - '.$plugin['name'], array(
			array('plugins_list', 'plugins', 0, 1),
			array('config', 'config', $anchor == 'config'),
			array('plugins_config_module', 'modules', $anchor == 'modules'),
			array('plugins_config_hooks', 'hooks', $anchor == 'hooks'),
			array('plugins_config_vars', 'vars', $anchor == 'vars'),
			array('export', 'plugins&operation=export&pluginid='.$plugin['pluginid'], 0, 1),
		));
		showtips('plugins_edit_tips');

		showtagheader('div', 'config', $anchor == 'config');
		showformheader("plugins&operation=edit&type=common&pluginid=$pluginid", '', 'configform');
		showtableheader();
		showsetting('plugins_edit_name', 'namenew', $plugin['name'], 'text');
		if(!$plugin['copyright']) {
			showsetting('plugins_edit_copyright', 'copyrightnew', $plugin['copyright'], 'text');
		}
		showsetting('plugins_edit_identifier', 'identifiernew', $plugin['identifier'], 'text');
		showsetting('plugins_edit_adminid', '', '', '<select name="adminidnew"><option value="1" '.$adminidselect[1].'>'.$lang['usergroups_system_1'].'</option><option value="2" '.$adminidselect[2].'>'.$lang['usergroups_system_2'].'</option><option value="3" '.$adminidselect[3].'>'.$lang['usergroups_system_3'].'</option></select>');
		showsetting('plugins_edit_directory', 'directorynew', $plugin['directory'], 'text');
		showsetting('plugins_edit_datatables', 'datatablesnew', $plugin['datatables'], 'text');
		showsetting('plugins_edit_description', 'descriptionnew', $plugin['description'], 'textarea');
		showsetting('plugins_edit_langexists', 'langexists', $plugin['modules']['extra']['langexists'], 'radio');
		showsubmit('editsubmit');
		showtablefooter();
		showformfooter();
		showtagfooter('div');

		showtagheader('div', 'modules', $anchor == 'modules');
		showformheader("plugins&operation=edit&type=modules&pluginid=$pluginid", '', 'modulesform');
		showtableheader('plugins_edit_modules');
		showsubtitle(array('', 'plugins_edit_modules_type', 'plugins_edit_modules_name', 'plugins_edit_modules_menu', 'plugins_edit_modules_menu_url', 'plugins_edit_modules_adminid', 'display_order'));

		$moduleids = array();
		if(is_array($plugin['modules'])) {
			foreach($plugin['modules'] as $moduleid => $module) {
				if($moduleid === 'extra') {
					continue;
				}
				$adminidselect = array($module['adminid'] => 'selected');
				$includecheck = empty($val['include']) ? $lang['no'] : $lang['yes'];

				$typeselect = '<optgroup label="'.lang('plugins_edit_modules_type_g1').'">'.
					'<option h="1111" e="inc" value="1"'.($module['type'] == 1 || $module['type'] == 2 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_1').'</option>'.
					'<option h="1111" e="inc" value="5"'.($module['type'] == 5 || $module['type'] == 6 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_5').'</option>'.
					'<option h="1111" e="inc" value="7"'.($module['type'] == 7 || $module['type'] == 8 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_7').'</option>'.
					'<option h="1111" e="inc" value="9"'.($module['type'] == 9 || $module['type'] == 10 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_9').'</option>'.
					'<option h="1001" e="inc" value="14"'.($module['type'] == 14 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_14').'</option>'.
					'<option h="1001" e="inc" value="15"'.($module['type'] == 15 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_15').'</option>'.
					'<option h="1001" e="inc" value="16"'.($module['type'] == 16 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_16').'</option>'.
					'<option h="1001" e="inc" value="3"'.($module['type'] == 3 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_3').'</option>'.
					'</optgroup>'.
					'<optgroup label="'.lang('plugins_edit_modules_type_g2').'">'.
					'<option h="0000" e="inc" value="13"'.($module['type'] == 13 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_13').'</option>'.
					'<option h="0011" e="inc" value="4"'.($module['type'] == 4 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_4').'</option>'.
					'<option h="0011" e="class" value="11"'.($module['type'] == 11 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_11').'</option>'.
					'<option h="0001" e="class" value="12"'.($module['type'] == 12 ? ' selected="selected"' : '').'>'.lang('plugins_edit_modules_type_12').'</option>'.
					'</optgroup>';
				showtablerow('', array('class="td25"', 'class="td28"'), array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[$moduleid]\">",
					"<select id=\"s_$moduleid\" onchange=\"shide(this, '$moduleid')\" name=\"typenew[$moduleid]\">$typeselect</select>",
					"<input type=\"text\" class=\"txt\" size=\"15\" name=\"namenew[$moduleid]\" value=\"$module[name]\"><span id=\"e_$moduleid\"></span>",
					"<span id=\"m_$moduleid\"><input type=\"text\" class=\"txt\" size=\"15\" name=\"menunew[$moduleid]\" value=\"$module[menu]\"></span>",
					"<span id=\"u_$moduleid\"><input type=\"text\" class=\"txt\" size=\"15\" id=\"url_$moduleid\" onchange=\"shide($('s_$moduleid'), '$moduleid')\" name=\"urlnew[$moduleid]\" value=\"".dhtmlspecialchars($module['url'])."\"></span>",
					"<span id=\"a_$moduleid\"><select name=\"adminidnew[$moduleid]\">\n".
					"<option value=\"0\" $adminidselect[0]>$lang[usergroups_system_0]</option>\n".
					"<option value=\"1\" $adminidselect[1]>$lang[usergroups_system_1]</option>\n".
					"<option value=\"2\" $adminidselect[2]>$lang[usergroups_system_2]</option>\n".
					"<option value=\"3\" $adminidselect[3]>$lang[usergroups_system_3]</option>\n".
					"</select></span>",
					"<span id=\"o_$moduleid\"><input type=\"text\" class=\"txt\" style=\"width:50px\" name=\"ordernew[$moduleid]\" value=\"$module[displayorder]\"></span>"
				));
				$moduleids[] = $moduleid;
			}
		}
		showtablerow('', array('class="td25"', 'class="td28"'), array(
			lang('add_new'),
			'<select id="s_n" onchange="shide(this, \'n\')" name="newtype">
				<optgroup label="'.lang('plugins_edit_modules_type_g1').'">
				<option h="1111" e="inc" value="1">'.lang('plugins_edit_modules_type_1').'</option>
				<option h="1111" e="inc" value="5">'.lang('plugins_edit_modules_type_5').'</option>
				<option h="1111" e="inc" value="7">'.lang('plugins_edit_modules_type_7').'</option>
				<option h="1111" e="inc" value="9">'.lang('plugins_edit_modules_type_9').'</option>
				<option h="1001" e="inc" value="14">'.lang('plugins_edit_modules_type_14').'</option>
				<option h="1001" e="inc" value="15">'.lang('plugins_edit_modules_type_15').'</option>
				<option h="1001" e="inc" value="16">'.lang('plugins_edit_modules_type_16').'</option>
				<option h="1001" e="inc" value="3">'.lang('plugins_edit_modules_type_3').'</option>
				</optgroup>
				<optgroup label="'.lang('plugins_edit_modules_type_g2').'">
				<option h="0000" e="inc" value="13">'.lang('plugins_edit_modules_type_13').'</option>
				<option h="0011" e="inc" value="4">'.lang('plugins_edit_modules_type_4').'</option>
				<option h="0011" e="class" value="11">'.lang('plugins_edit_modules_type_11').'</option>
				<option h="0001" e="class" value="12">'.lang('plugins_edit_modules_type_12').'</option>
				</optgroup>
			</select>',
			'<input type="text" class="txt" size="15" name="newname"><span id="e_n"></span>',
			'<span id="m_n"><input type="text" class="txt" size="15" name="newmenu"></span>',
			'<span id="u_n"><input type="text" class="txt" size="15" id="url_n" onchange="shide($(\'s_n\'), \'n\')" name="newurl"></span>',
			'<span id="a_n"><select name="newadminid">'.
			'<option value="0">'.lang('usergroups_system_0').'</option>'.
			'<option value="1" selected>'.lang('usergroups_system_1').'</option>'.
			'<option value="2">'.lang('usergroups_system_2').'</option>'.
			'<option value="3">'.lang('usergroups_system_3').'</option>'.
			'</select></span>',
			'<span id="o_n"><input type="text" class="txt" style="width:50px"  name="neworder"></span>',
		));
		showsubmit('editsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();
		showtagfooter('div');

		showtagheader('div', 'hooks', $anchor == 'hooks');
		showformheader("plugins&operation=edit&type=hooks&pluginid=$pluginid", '', 'hooksform');
		showtableheader('plugins_edit_hooks');
		showsubtitle(array('', 'available', 'plugins_hooks_title', 'plugins_hooks_callback', 'plugins_hooks_description', ''));
		$query = $db->query("SELECT pluginhookid, title, description, available FROM {$tablepre}pluginhooks WHERE pluginid='$plugin[pluginid]'");
		while($hook = $db->fetch_array($query)) {
			$hook['description'] = nl2br(cutstr($hook['description'], 50));
			$hook['evalcode'] = 'eval($hooks[\''.$plugin['identifier'].'_'.$hook['title'].'\']);';
			showtablerow('', '', array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[$hook[pluginhookid]]\">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$hook[pluginhookid]]\" value=\"1\" ".($hook['available'] ? 'checked' : '')." onclick=\"if(this.checked) {\$('hookevalcode{$hook[pluginhookid]}').value='".addslashes($hook[evalcode])."';}else{\$('hookevalcode{$hook[pluginhookid]}').value='N/A';}\">",
				"<input type=\"text\" class=\"txt\" name=\"titlenew[$hook[pluginhookid]]\" size=\"15\" value=\"$hook[title]\"></td>\n".
				"<td><input type=\"text\" class=\"txt\" name=\"hookevalcode{$hook[pluginhookid]}\" id=\"hookevalcode{$hook[pluginhookid]}\"size=\"30\" value=\"".($hook['available'] ? $hook[evalcode] : 'N/A')."\" readonly>",
				$hook['description'],
				"<a href=\"$BASESCRIPT?action=plugins&operation=hooks&pluginid=$plugin[pluginid]&pluginhookid=$hook[pluginhookid]\" class=\"act\">$lang[edit]</a>"
			));
		}
		showtablerow('', array('', '', '', 'colspan="3"'), array(
			lang('add_new'),
			'',
			'<input type="text" class="txt" name="newtitle" size="15">',
			''
		));
		showsubmit('editsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();
		showtagfooter('div');
		$shideinit = '';
		foreach($moduleids as $moduleid) {
			$shideinit .= 'shide($("s_'.$moduleid.'"), \''.$moduleid.'\');';
		}
		echo '<script type="text/JavaScript">
			function shide(obj, id) {
				v = obj.options[obj.selectedIndex].getAttribute("h");
				$("m_" + id).style.display = v.substr(0,1) == "1" ? "" : "none";
				$("u_" + id).style.display = v.substr(1,1) == "1" ? "" : "none";
				$("a_" + id).style.display = v.substr(2,1) == "1" ? "" : "none";
				$("o_" + id).style.display = v.substr(3,1) == "1" ? "" : "none";
				e = obj.options[obj.selectedIndex].getAttribute("e");
				$("e_" + id).innerHTML = e && ($("url_" + id).value == \'\' || $("u_" + id).style.display == "none") ? "." + e + ".php" : "";
			}
			shide($("s_n"), "n");'.$shideinit.'
		</script>';

		showtagheader('div', 'vars', $anchor == 'vars');
		showformheader("plugins&operation=edit&type=vars&pluginid=$pluginid", '', 'varsform');
		showtableheader('plugins_edit_vars');
		showsubtitle(array('', 'display_order', 'plugins_vars_title', 'plugins_vars_variable', 'plugins_vars_type', ''));
		$query = $db->query("SELECT * FROM {$tablepre}pluginvars WHERE pluginid='$plugin[pluginid]' ORDER BY displayorder");
		while($var = $db->fetch_array($query)) {
			$var['type'] = $lang['plugins_edit_vars_type_'. $var['type']];
			$var['title'] .= isset($lang[$var['title']]) ? '<br />'.$lang[$var['title']] : '';
			showtablerow('', array('class="td25"', 'class="td28"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$var[pluginvarid]\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[$var[pluginvarid]]\" value=\"$var[displayorder]\">",
				$var['title'],
				$var['variable'],
				$var['type'],
				"<a href=\"$BASESCRIPT?action=plugins&operation=vars&pluginid=$plugin[pluginid]&pluginvarid=$var[pluginvarid]\" class=\"act\">$lang[detail]</a>"
			));
		}
		showtablerow('', array('class="td25"', 'class="td28"'), array(
			lang('add_new'),
			'<input type="text" class="txt" size="2" name="newdisplayorder" value="0">',
			'<input type="text" class="txt" size="15" name="newtitle">',
			'<input type="text" class="txt" size="15" name="newvariable">',
			'<select name="newtype">
				<option value="number">'.lang('plugins_edit_vars_type_number').'</option>
				<option value="text" selected>'.lang('plugins_edit_vars_type_text').'</option>
				<option value="textarea">'.lang('plugins_edit_vars_type_textarea').'</option>
				<option value="radio">'.lang('plugins_edit_vars_type_radio').'</option>
				<option value="select">'.lang('plugins_edit_vars_type_select').'</option>
				<option value="color">'.lang('plugins_edit_vars_type_color').'</option>
				<option value="date">'.lang('plugins_edit_vars_type_date').'</option>
				<option value="datetime">'.lang('plugins_edit_vars_type_datetime').'</option>
				<option value="forum">'.lang('plugins_edit_vars_type_forum').'</option>
				<option value="forums">'.lang('plugins_edit_vars_type_forums').'</option>
				<option value="group">'.lang('plugins_edit_vars_type_group').'</option>
				<option value="groups">'.lang('plugins_edit_vars_type_groups').'</option>
				<option value="extcredit">'.lang('plugins_edit_vars_type_extcredit').'</option>
			</seletc>',
			''
		));
		showsubmit('editsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();
		showtagfooter('div');

	} else {

		if($type == 'common') {

			$namenew	= dhtmlspecialchars(trim($namenew));
			$directorynew	= dhtmlspecialchars($directorynew);
			$identifiernew	= trim($identifiernew);
			$datatablesnew	= dhtmlspecialchars(trim($datatablesnew));
			$descriptionnew	= dhtmlspecialchars($descriptionnew);
			$copyrightnew	= $plugin['copyright'] ? addslashes($plugin['copyright']) : dhtmlspecialchars($copyrightnew);
			$adminidnew	= ($adminidnew > 0 && $adminidnew <= 3) ? $adminidnew : 1;

			if(!$namenew) {
				cpmsg('plugins_edit_name_invalid', '', 'error');
			} elseif(!isplugindir($directorynew)) {
				cpmsg('plugins_edit_directory_invalid', '', 'error');
			} elseif($identifiernew != $plugin['identifier']) {
				$query = $db->query("SELECT pluginid FROM {$tablepre}plugins WHERE identifier='$identifiernew' LIMIT 1");
				if($db->num_rows($query) || !ispluginkey($identifiernew)) {
					cpmsg('plugins_edit_identifier_invalid', '', 'error');
				}
			}
			if($langexists && !file_exists($langfile = DISCUZ_ROOT.'./forumdata/plugins/'.$identifiernew.'.lang.php')) {
				cpmsg('plugins_edit_language_invalid', '', 'error');
			}
			$plugin['modules']['extra']['langexists'] = $langexists;
			$db->query("UPDATE {$tablepre}plugins SET adminid='$adminidnew', name='$namenew', modules='".addslashes(serialize($plugin['modules']))."', identifier='$identifiernew', description='$descriptionnew', datatables='$datatablesnew', directory='$directorynew', copyright='$copyrightnew' WHERE pluginid='$pluginid'");

		} elseif($type == 'modules') {

			$modulesnew = array();
			$newname = trim($newname);
			if(is_array($plugin['modules'])) {
				foreach($plugin['modules'] as $moduleid => $module) {
					if(!isset($delete[$moduleid])) {
						if($moduleid === 'extra') {
							continue;
						}
						$modulesnew[] = array(
							'name'		=> $namenew[$moduleid],
							'menu'		=> $menunew[$moduleid],
							'url'		=> $urlnew[$moduleid],
							'type'		=> $typenew[$moduleid],
							'adminid'	=> ($adminidnew[$moduleid] >= 0 && $adminidnew[$moduleid] <= 3) ? $adminidnew[$moduleid] : $module['adminid'],
							'displayorder'	=> intval($ordernew[$moduleid]),
						);
					}
				}
			}

			$newmodule = array();
			if(!empty($newname)) {
				$modulesnew[] = array(
					'name'		=> $newname,
					'menu'		=> $newmenu,
					'url'		=> $newurl,
					'type'		=> $newtype,
					'adminid'	=> $newadminid,
					'displayorder'	=> intval($neworder),
				);
			}

			usort($modulesnew, 'modulecmp');

			$namesarray = array();
			foreach($modulesnew as $key => $module) {
				$namekey = in_array($module['type'], array(11, 12)) ? 1 : 0;
				if(!ispluginkey($module['name'])) {
					cpmsg('plugins_edit_modules_name_invalid', '', 'error');
				} elseif(@in_array($module['name'], $namesarray[$namekey])) {
					cpmsg('plugins_edit_modules_duplicated', '', 'error');
				}
				$namesarray[$namekey][] = $module['name'];

				$module['menu'] = trim($module['menu']);
				$module['url'] = trim($module['url']);
				$module['adminid'] = $module['adminid'] >= 0 && $module['adminid'] <= 3 ? $module['adminid'] : 1 ;

				$unseturl = TRUE;
				switch($module['type']) {
					case 1:
					case 5:
					case 7:
					case 9:
						if(empty($module['url'])) {
							$module['type']++;
						} else {
							$unseturl = FALSE;
						}
					case 3:
					case 14:
					case 15:
					case 16:
					case 17:
						if(empty($module['menu'])) {
							cpmsg('plugins_edit_modules_menu_invalid', '', 'error');
						}
						if($unseturl) {
							unset($module['url']);
						}
						break;
					case 4:
					case 11:
					case 12:
					case 13:
						unset($module['menu'], $module['url']);
						break;
					default:
						cpmsg('undefined_action', '', 'error');
				}

				$modulesnew[$key] = $module;
			}
			if(!empty($plugin['modules']['extra'])) {
				$modulesnew['extra'] = $plugin['modules']['extra'];
			}

			$db->query("UPDATE {$tablepre}plugins SET modules='".addslashes(serialize($modulesnew))."' WHERE pluginid='$pluginid'");

		} elseif($type == 'hooks') {

			if(is_array($delete)) {
				$ids = $comma = '';
				foreach($delete as $id => $val) {
					$ids .= "$comma'$id'";
					$comma = ',';
				}
				$db->query("DELETE FROM {$tablepre}pluginhooks WHERE pluginid='$pluginid' AND pluginhookid IN ($ids)");
			}

			if(is_array($titlenew)) {
				$titlearray = array();
				foreach($titlenew as $id => $val) {
					if(!ispluginkey($val) || in_array($val, $titlearray)) {
						cpmsg('plugins_edit_hooks_title_invalid', '', 'error');
					}
					$titlearray[] = $val;
					$db->query("UPDATE {$tablepre}pluginhooks SET title='".dhtmlspecialchars($titlenew[$id])."', available='".intval($availablenew[$id])."' WHERE pluginid='$pluginid' AND pluginhookid='$id'");
				}
			}

			if($newtitle) {
				if(!ispluginkey($newtitle) || (is_array($titlenew) && in_array($newtitle, $titlenew))) {
					cpmsg('plugins_edit_hooks_title_invalid', '', 'error');
				}
				$db->query("INSERT INTO {$tablepre}pluginhooks (pluginid, title, description, code, available)
					VALUES ('$pluginid', '".dhtmlspecialchars($newtitle)."', '', '', 0)");
			}

		} elseif($type == 'vars') {

			if($ids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}pluginvars WHERE pluginid='$pluginid' AND pluginvarid IN ($ids)");
			}

			if(is_array($displayordernew)) {
				foreach($displayordernew as $id => $displayorder) {
					$db->query("UPDATE {$tablepre}pluginvars SET displayorder='$displayorder' WHERE pluginid='$pluginid' AND pluginvarid='$id'");
				}
			}

			$newtitle = dhtmlspecialchars(trim($newtitle));
			$newvariable = trim($newvariable);
			if($newtitle && $newvariable) {
				$query = $db->query("SELECT pluginvarid FROM {$tablepre}pluginvars WHERE pluginid='$pluginid' AND variable='$newvariable' LIMIT 1");
				if($db->num_rows($query) || strlen($newvariable) > 40 || !ispluginkey($newvariable)) {
					cpmsg('plugins_edit_var_invalid', '', 'error');
				}

				$db->query("INSERT INTO {$tablepre}pluginvars (pluginid, displayorder, title, variable, type)
					VALUES ('$pluginid', '$newdisplayorder', '$newtitle', '$newvariable', '$newtype')");
			}

		}

		updatecache('plugins');
		updatecache('settings');
		updatemenu();
		cpmsg('plugins_edit_succeed', "$BASESCRIPT?action=plugins&operation=edit&pluginid=$pluginid&anchor=$anchor", 'succeed');

	}

} elseif($operation == 'delete') {

	$plugin = $db->fetch_first("SELECT name, identifier, directory, modules, version FROM {$tablepre}plugins WHERE pluginid='$pluginid'");
	$dir = $plugin['directory'];
	$modules = unserialize($plugin['modules']);

	if(!$confirmed) {

		$entrydir = DISCUZ_ROOT.'./plugins/'.$dir;
		$newver = $upgradestr = '';
		if(file_exists($entrydir)) {
			$d = dir($entrydir);
			while($f = $d->read()) {
				if(preg_match('/^discuz\_plugin\_'.$plugin['identifier'].'(\_\w+)?\.xml$/', $f, $a)) {
					$extratxt = $extra = substr($a[1], 1);
					if(preg_match('/^SC\_GBK$/i', $extra)) {
						$extratxt = '&#31616;&#20307;&#20013;&#25991;&#29256;';
					} elseif(preg_match('/^SC\_UTF8$/i', $extra)) {
						$extratxt = '&#31616;&#20307;&#20013;&#25991;&#85;&#84;&#70;&#56;&#29256;';
					} elseif(preg_match('/^TC\_BIG5$/i', $extra)) {
						$extratxt = '&#32321;&#39636;&#20013;&#25991;&#29256;';
					} elseif(preg_match('/^TC\_UTF8$/i', $extra)) {
						$extratxt = '&#32321;&#39636;&#20013;&#25991;&#85;&#84;&#70;&#56;&#29256;';
					}
					$importtxt = @implode('', file($entrydir.'/'.$f));
					$pluginarray = getimportdata('Discuz! Plugin');
					$newver = !empty($pluginarray['plugin']['version']) ? $pluginarray['plugin']['version'] : 0;
					$upgradestr .= $newver > $plugin['version'] ? '&nbsp;<input class="btn" onclick="location.href=\''.$BASESCRIPT.'?action=plugins&operation=upgrade&pluginid='.$pluginid.'&xmlfile='.rawurlencode($a[0]).'\'" type="button" value="'.lang('plugins_update_to').($extra ? $extratxt : $lang['plugins_import_default']).' '.$newver.'" />&nbsp;' : '';
				}
			}
		}
		showsubmenu($lang['plugins_config_uninstall'].' - '.$plugin['name']);
		echo '<div class="infobox">'.($upgradestr ? '<h4 class="infotitle2">'.$lang['plugins_config_upgrade'].'</h4>'.$upgradestr.'<br /><br />' : '').'
			<h4 class="infotitle2">'.$lang['plugins_config_delete'].'</h4>
			<input class="btn" onclick="location.href=\''.$BASESCRIPT.'?action=plugins&operation=delete&pluginid='.$pluginid.'&confirmed=yes\'" type="button" value="'.$lang['plugins_config_uninstallplugin'].'" /><br /><br />
			<input class="btn" onclick="location.href=\''.$BASESCRIPT.'?action=plugins\'" type="button" value="'.$lang['cancel'].'"/>
			</div>';

	} else {

		$identifier = $plugin['identifier'];
		$db->query("DELETE FROM {$tablepre}plugins WHERE pluginid=$pluginid");
		$db->query("DELETE FROM {$tablepre}pluginvars WHERE pluginid=$pluginid");

		updatecache('plugins');
		updatecache('settings');
		updatemenu();

		if($dir) {
			$dir = substr($dir, 0, -1);
			$pdir = DISCUZ_ROOT.'./plugins/'.$dir;
			if(file_exists($pdir)) {
				$d = dir($pdir);
				while($f = $d->read()) {
					if(preg_match('/^discuz\_plugin_'.$dir.'(\_\w+)?\.xml$/', $f, $a)) {
						$installtype = substr($a[1], 1);
						$file = $pdir.'/'.$f;
						$importtxt = @implode('', file($file));
						$pluginarray = getimportdata('Discuz! Plugin');
						if(!empty($pluginarray['uninstallfile']) && preg_match('/^[\w\.]+$/', $pluginarray['uninstallfile'])) {
							dheader('location: '.$BASESCRIPT.'?action=plugins&operation=pluginuninstall&dir='.$dir.'&installtype='.$installtype);
						}
						break;
					}
				}
			}
		}
		if(!empty($modules['extra']['langexists'])) {
			@unlink(DISCUZ_ROOT.'./forumdata/plugins/'.$identifier.'.lang.php');
		}

		pluginstat('uninstall', $pluginarray['plugin']);
		cpmsg('plugins_delete_succeed', "$BASESCRIPT?action=plugins", 'succeed');
	}

} elseif($operation == 'hooks') {

	$pluginhook = $db->fetch_first("SELECT * FROM {$tablepre}plugins p, {$tablepre}pluginhooks ph WHERE p.pluginid='$pluginid' AND ph.pluginid=p.pluginid AND ph.pluginhookid='$pluginhookid'");
	if(!$pluginhook) {
		cpmsg('undefined_action', '', 'error');
	}

	if(!submitcheck('hooksubmit')) {
		shownav('plugin', 'nav_plugins');
		showsubmenu($lang['plugins_edit'].' - '.$pluginhook['name'], array(
			array('plugins_list', 'plugins', 0),
			array('config', 'plugins&operation=edit&pluginid='.$pluginid.'&anchor=config', 0),
			array('plugins_config_module', 'plugins&operation=edit&pluginid='.$pluginid.'&anchor=modules', 0),
			array('plugins_config_hooks', 'plugins&operation=edit&pluginid='.$pluginid.'&anchor=hooks', 1),
			array('plugins_config_vars', 'plugins&operation=edit&pluginid='.$pluginid.'&anchor=vars', 0),
			array('export', 'plugins&operation=export&pluginid='.$pluginid, 0),
		));
		showtips('plugins_edit_hooks_tips');
		showformheader("plugins&operation=hooks&pluginid=$pluginid&pluginhookid=$pluginhookid");
		showtableheader();
		showtitle($lang['plugins_edit_hooks'].' - '.$pluginhook['title']);
		showsetting('plugins_edit_hooks_description', 'descriptionnew', $pluginhook['description'], 'textarea');
		showsetting('plugins_edit_hooks_code', 'codenew', $pluginhook['code'], 'textarea');
		showsubmit('hooksubmit');
		showtablefooter();
		showformfooter();

	} else {

		$descriptionnew	= dhtmlspecialchars(trim($descriptionnew));
		$codenew	= trim($codenew);

		$db->query("UPDATE {$tablepre}pluginhooks SET description='$descriptionnew', code='$codenew' WHERE pluginid='$pluginid' AND pluginhookid='$pluginhookid'");

		updatecache('settings');
		cpmsg('plugins_edit_hooks_succeed', "$BASESCRIPT?action=plugins&operation=edit&pluginid=$pluginid&anchor=hooks", 'succeed');
	}

} elseif($operation == 'vars') {

	$pluginvar = $db->fetch_first("SELECT * FROM {$tablepre}plugins p, {$tablepre}pluginvars pv WHERE p.pluginid='$pluginid' AND pv.pluginid=p.pluginid AND pv.pluginvarid='$pluginvarid'");
	if(!$pluginvar) {
		cpmsg('undefined_action', '', 'error');
	}

	if(!submitcheck('varsubmit')) {
		shownav('plugin', 'nav_plugins');
		showsubmenu($lang['plugins_edit'].' - '.$pluginvar['name'], array(
			array('plugins_list', 'plugins', 0),
			array('config', 'plugins&operation=edit&pluginid='.$pluginid.'&anchor=config', 0),
			array('plugins_config_module', 'plugins&operation=edit&pluginid='.$pluginid.'&anchor=modules', 0),
			array('plugins_config_hooks', 'plugins&operation=edit&pluginid='.$pluginid.'&anchor=hooks', 0),
			array('plugins_config_vars', 'plugins&operation=edit&pluginid='.$pluginid.'&anchor=vars', 1),
			array('export', 'plugins&operation=export&pluginid='.$pluginid, 0),
		));

		$typeselect = '<select name="typenew" onchange="if(this.value == \'select\') $(\'extra\').style.display=\'\'; else $(\'extra\').style.display=\'none\';">';
		foreach(array('number', 'text', 'radio', 'textarea', 'select', 'color', 'date', 'datetime', 'forum', 'forums', 'group', 'groups', 'extcredit') as $type) {
			$typeselect .= '<option value="'.$type.'" '.($pluginvar['type'] == $type ? 'selected' : '').'>'.$lang['plugins_edit_vars_type_'.$type].'</option>';
		}
		$typeselect .= '</select>';

		showformheader("plugins&operation=vars&pluginid=$pluginid&pluginvarid=$pluginvarid");
		showtableheader();
		showtitle($lang['plugins_edit_vars'].' - '.$pluginvar['title']);
		showsetting('plugins_edit_vars_title', 'titlenew', $pluginvar['title'], 'text');
		showsetting('plugins_edit_vars_description', 'descriptionnew', $pluginvar['description'], 'textarea');
		showsetting('plugins_edit_vars_type', '', '', $typeselect);
		showsetting('plugins_edit_vars_variable', 'variablenew', $pluginvar['variable'], 'text');
		showtagheader('tbody', 'extra', $pluginvar['type'] == 'select');
		showsetting('plugins_edit_vars_extra', 'extranew',  $pluginvar['extra'], 'textarea');
		showtagfooter('tbody');
		showsubmit('varsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$titlenew	= cutstr(dhtmlspecialchars(trim($titlenew)), 25);
		$descriptionnew	= cutstr(dhtmlspecialchars(trim($descriptionnew)), 255);
		$variablenew	= trim($variablenew);
		$extranew	= dhtmlspecialchars(trim($extranew));

		if(!$titlenew) {
			cpmsg('plugins_edit_var_title_invalid', '', 'error');
		} elseif($variablenew != $pluginvar['variable']) {
			$query = $db->query("SELECT pluginvarid FROM {$tablepre}pluginvars WHERE variable='$variablenew'");
			if($db->num_rows($query) || !$variablenew || strlen($variablenew) > 40 || !ispluginkey($variablenew)) {
				cpmsg('plugins_edit_vars_invalid', '', 'error');
			}
		}

		$db->query("UPDATE {$tablepre}pluginvars SET title='$titlenew', description='$descriptionnew', type='$typenew', variable='$variablenew', extra='$extranew' WHERE pluginid='$pluginid' AND pluginvarid='$pluginvarid'");

		updatecache('plugins');
		cpmsg('plugins_edit_vars_succeed', "$BASESCRIPT?action=plugins&operation=edit&pluginid=$pluginid&anchor=vars", 'succeed');
	}

}

function modulecmp($a, $b) {
	return $a['displayorder'] > $b['displayorder'] ? 1 : -1;
}

function updatemenu() {
	global $BASESCRIPT;
	$pluginmenus = array(array('addons', 'addons'), array('plugins_menu', 'plugins'));
	@include DISCUZ_ROOT.'./forumdata/cache/adminmenu.php';
	if(is_array($adminmenu)) {
		foreach($adminmenu as $row) {
			$pluginmenus[] = array($row['name'], $row['url']);
		}
	}
	$s = '';
	foreach($pluginmenus as $menu) {
		if($menu[0] && $menu[1]) {
			$s .= '<li><a href="'.(substr($menu[1], 0, 4) == 'http' ? $menu[1] : $BASESCRIPT.'?action='.$menu[1]).'" hidefocus="true" target="'.($menu[2] ? $menu[2] : 'main').'"'.($menu[3] ? $menu[3] : '').'>'.lang($menu[0]).'</a></li>';
		}
	}
	echo '<script type="text/JavaScript">parent.$(\'menu_plugin\').innerHTML = \''.str_replace("'", "\'", $s).'\';parent.initCpMenus(\'leftmenu\');parent.$(\'cmain\').innerHTML = parent.initCpMap();</script>';
}

function runquery($sql) {
	global $dbcharset, $tablepre, $db;

	$sql = str_replace("\r", "\n", str_replace(array(' cdb_', ' {tablepre}', ' `cdb_'), array(' '.$tablepre, ' '.$tablepre, ' `'.$tablepre), $sql));
	$ret = array();
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
		}
		$num++;
	}
	unset($sql);

	foreach($ret as $query) {
		$query = trim($query);
		if($query) {

			if(substr($query, 0, 12) == 'CREATE TABLE') {
				$name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
				$db->query(createtable($query, $dbcharset));

			} else {
				$db->query($query);
			}

		}
	}
}

function createtable($sql, $dbcharset) {
	$type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
	$type = in_array($type, array('MYISAM', 'HEAP')) ? $type : 'MYISAM';
	return preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql).
	(mysql_get_server_info() > '4.1' ? " ENGINE=$type DEFAULT CHARSET=$dbcharset" : " TYPE=$type");
}

function langeval($array) {
	$return = '';
	foreach($array as $k => $v) {
		$k = str_replace("'", '', $k);
		$return .= "\t'$k' => '".str_replace(array("\\'", "'"), array("\\\'", "\'"), stripslashes($v))."',\n";
	}
	return "array(\n$return);\n\n";
}

function pluginstat($type, $data) {
	$url = 'http://stat.discuz.com/plugins.php?action='.$type.'&id='.rawurlencode($data['identifier']).'&version='.rawurlencode($data['version']).'&url='.rawurlencode($GLOBALS['boardurl']).'&ip='.$GLOBALS['onlineip'];
	echo '<script src="'.$url.'" type="text/JavaScript"></script>';
}

?>