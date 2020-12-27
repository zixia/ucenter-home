<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: cache.func.php 21311 2009-11-26 01:35:43Z liulanbo $
*/

define('DISCUZ_KERNEL_VERSION', '7.2');
define('DISCUZ_KERNEL_RELEASE', '20091126');


function updatecache($cachename = '') {
	global $db, $bbname, $tablepre, $maxbdays;


	static $cachescript = array
		(

		'settings'	=> array('settings'),
		'forums'	=> array('forums'),
		'icons'		=> array('icons'),
		'stamps'	=> array('stamps'),
		'ranks'		=> array('ranks'),
		'usergroups'	=> array('usergroups'),
		'request'	=> array('request'),
		'medals'	=> array('medals'),
		'magics'	=> array('magics'),
		'topicadmin'	=> array('modreasons', 'stamptypeid'),
		'archiver'	=> array('advs_archiver'),
		'register'	=> array('advs_register', 'ipctrl'),
		'faqs'		=> array('faqs'),
		'secqaa'	=> array('secqaa'),
		'censor'	=> array('censor'),
		'ipbanned'	=> array('ipbanned'),
		'smilies'	=> array('smilies_js'),
		'forumstick' => array('forumstick'),

		'index'		=> array('announcements', 'onlinelist', 'forumlinks', 'advs_index', 'heats'),
		'forumdisplay'	=> array('smilies', 'announcements_forum', 'globalstick', 'forums', 'icons', 'onlinelist', 'advs_forumdisplay', 'forumstick'),
		'viewthread'	=> array('smilies', 'smileytypes', 'forums', 'usergroups', 'ranks', 'stamps', 'bbcodes', 'smilies', 'advs_viewthread', 'tags_viewthread', 'custominfo', 'groupicon', 'focus', 'stamps'),
		'post'		=> array('bbcodes_display', 'bbcodes', 'smileycodes', 'smilies', 'smileytypes', 'icons', 'domainwhitelist'),
		'profilefields'	=> array('fields_required', 'fields_optional'),
		'viewpro'	=> array('fields_required', 'fields_optional', 'custominfo'),
		'bbcodes'	=> array('bbcodes', 'smilies', 'smileytypes'),
		);

	if($maxbdays) {
		$cachescript['birthdays'] = array('birthdays');
		$cachescript['index'][]   = 'birthdays_index';
	}

	$updatelist = empty($cachename) ? array_values($cachescript) : (is_array($cachename) ? array('0' => $cachename) : array(array('0' => $cachename)));
	$updated = array();
	foreach($updatelist as $value) {
		foreach($value as $cname) {
			if(empty($updated) || !in_array($cname, $updated)) {
				$updated[] = $cname;
				getcachearray($cname);
			}
		}
	}

	foreach($cachescript as $script => $cachenames) {
		if(empty($cachename) || (!is_array($cachename) && in_array($cachename, $cachenames)) || (is_array($cachename) && array_intersect($cachename, $cachenames))) {
			$cachedata = '';
			$query = $db->query("SELECT data FROM {$tablepre}caches WHERE cachename in(".implodeids($cachenames).")");
			while($data = $db->fetch_array($query)) {
				$cachedata .= $data['data'];
			}
			writetocache($script, $cachenames, $cachedata);
		}
	}

	if(!$cachename || $cachename == 'styles') {
		$stylevars = $styledata = $styleicons = array();
		$defaultstyleid = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable = 'styleid'");
		list(, $imagemaxwidth) = explode("\t", $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable = 'zoomstatus'"));
		$imagemaxwidth = $imagemaxwidth ? $imagemaxwidth : 600;
		$imagemaxwidthint = intval($imagemaxwidth);
		$query = $db->query("SELECT sv.* FROM {$tablepre}stylevars sv LEFT JOIN {$tablepre}styles s ON s.styleid = sv.styleid AND (s.available=1 OR s.styleid='$defaultstyleid')");
		while($var = $db->fetch_array($query)) {
			$stylevars[$var['styleid']][$var['variable']] = $var['substitute'];
		}
		$query = $db->query("SELECT s.*, t.directory AS tpldir FROM {$tablepre}styles s LEFT JOIN {$tablepre}templates t ON s.templateid=t.templateid WHERE s.available=1 OR s.styleid='$defaultstyleid'");
		while($data = $db->fetch_array($query)) {
			$data = array_merge($data, $stylevars[$data['styleid']]);
			$datanew = array();
			$data['imgdir'] = $data['imgdir'] ? $data['imgdir'] : 'images/default';
			$data['styleimgdir'] = $data['styleimgdir'] ? $data['styleimgdir'] : $data['imgdir'];
			foreach($data as $k => $v) {
				if(substr($k, -7, 7) == 'bgcolor') {
					$newkey = substr($k, 0, -7).'bgcode';
					$datanew[$newkey] = setcssbackground($data, $k);
				}
			}
			$data = array_merge($data, $datanew);
			$styleicons[$data['styleid']] = $data['menuhover'];
			if(strstr($data['boardimg'], ',')) {
				$flash = explode(",", $data['boardimg']);
				$flash[0] = trim($flash[0]);
				$flash[0] = preg_match('/^http:\/\//i', $flash[0]) ? $flash[0] : $data['styleimgdir'].'/'.$flash[0];
				$data['boardlogo'] = "<embed src=\"".$flash[0]."\" width=\"".trim($flash[1])."\" height=\"".trim($flash[2])."\" type=\"application/x-shockwave-flash\" wmode=\"transparent\"></embed>";
			} else {
				$data['boardimg'] = preg_match('/^http:\/\//i', $data['boardimg']) ? $data['boardimg'] : $data['styleimgdir'].'/'.$data['boardimg'];
				$data['boardlogo'] = "<img src=\"$data[boardimg]\" alt=\"$bbname\" border=\"0\" />";
			}
			$data['bold'] = $data['nobold'] ? 'normal' : 'bold';
			$contentwidthint = intval($data['contentwidth']);
			$contentwidthint = $contentwidthint ? $contentwidthint : 600;
			if(substr(trim($data['contentwidth']), -1, 1) != '%') {
				if(substr(trim($imagemaxwidth), -1, 1) != '%') {
					$data['imagemaxwidth'] = $imagemaxwidthint > $contentwidthint ? $contentwidthint : $imagemaxwidthint;
				} else {
					$data['imagemaxwidth'] = intval($contentwidthint * $imagemaxwidthint / 100);
				}
			} else {
				if(substr(trim($imagemaxwidth), -1, 1) != '%') {
					$data['imagemaxwidth'] = '%'.$imagemaxwidthint;
				} else {
					$data['imagemaxwidth'] = ($imagemaxwidthint > $contentwidthint ? $contentwidthint : $imagemaxwidthint).'%';
				}
			}
			$data['verhash'] = random(3);
			$styledata[] = $data;
		}
		foreach($styledata as $data) {
			$data['styleicons'] = $styleicons;
			writetocache($data['styleid'], '', getcachevars($data, 'CONST'), 'style_');
			writetocsscache($data);
		}
	}

	if(!$cachename || $cachename == 'usergroups') {
		@include_once DISCUZ_ROOT.'forumdata/cache/cache_settings.php';
		$threadplugins = !isset($_DCACHE['settings']) ? $GLOBALS['threadplugins'] : $_DCACHE['settings'];
		$allowthreadplugin = $threadplugins ? unserialize($db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='allowthreadplugin'")) : array();

		$query = $db->query("SELECT * FROM {$tablepre}usergroups u
					LEFT JOIN {$tablepre}admingroups a ON u.groupid=a.admingid");
		while($data = $db->fetch_array($query)) {
			$ratearray = array();
			if($data['raterange']) {
				foreach(explode("\n", $data['raterange']) as $rating) {
					$rating = explode("\t", $rating);
					$ratearray[$rating[0]] = array('min' => $rating[1], 'max' => $rating[2], 'mrpd' => $rating[3]);
				}
			}
			$data['raterange'] = $ratearray;
			$data['grouptitle'] = $data['color'] ? '<font color="'.$data['color'].'">'.$data['grouptitle'].'</font>' : $data['grouptitle'];
			$data['grouptype'] = $data['type'];
			$data['grouppublic'] = $data['system'] != 'private';
			$data['groupcreditshigher'] = $data['creditshigher'];
			$data['groupcreditslower'] = $data['creditslower'];
			$data['allowthreadplugin'] = $threadplugins ? $allowthreadplugin[$data['groupid']] : array();
			unset($data['type'], $data['system'], $data['creditshigher'], $data['creditslower'], $data['color'], $data['groupavatar'], $data['admingid']);
			writetocache($data['groupid'], '', getcachevars($data), 'usergroup_');
		}
	}


	if(!$cachename || $cachename == 'admingroups') {
		$query = $db->query("SELECT * FROM {$tablepre}admingroups");
		while($data = $db->fetch_array($query)) {
			writetocache($data['admingid'], '', getcachevars($data), 'admingroup_');
		}
	}

	if(!$cachename || $cachename == 'plugins') {
		$query = $db->query("SELECT pluginid, available, adminid, name, identifier, datatables, directory, copyright, modules FROM {$tablepre}plugins");
		while($plugin = $db->fetch_array($query)) {
			$data = array_merge($plugin, array('modules' => array()), array('vars' => array()));
			$plugin['modules'] = unserialize($plugin['modules']);
			if(is_array($plugin['modules'])) {
				foreach($plugin['modules'] as $module) {
					$data['modules'][$module['name']] = $module;
				}
			}
			$queryvars = $db->query("SELECT variable, value FROM {$tablepre}pluginvars WHERE pluginid='$plugin[pluginid]'");
			while($var = $db->fetch_array($queryvars)) {
				$data['vars'][$var['variable']] = $var['value'];
			}
			writetocache($plugin['identifier'], '', "\$_DPLUGIN['$plugin[identifier]'] = ".arrayeval($data), 'plugin_');
		}
	}

	if(!$cachename || $cachename == 'threadsorts') {
		$sortlist = $templatedata = array();
		$query = $db->query("SELECT t.typeid AS sortid, tt.optionid, tt.title, tt.type, tt.unit, tt.rules, tt.identifier, tt.description, tv.required, tv.unchangeable, tv.search, tv.subjectshow
			FROM {$tablepre}threadtypes t
			LEFT JOIN {$tablepre}typevars tv ON t.typeid=tv.sortid
			LEFT JOIN {$tablepre}typeoptions tt ON tv.optionid=tt.optionid
			WHERE t.special='1' AND tv.available='1'
			ORDER BY tv.displayorder");
		while($data = $db->fetch_array($query)) {
			$data['rules'] = unserialize($data['rules']);
			$sortid = $data['sortid'];
			$optionid = $data['optionid'];
			$sortlist[$sortid][$optionid] = array(
				'title' => dhtmlspecialchars($data['title']),
				'type' => dhtmlspecialchars($data['type']),
				'unit' => dhtmlspecialchars($data['unit']),
				'identifier' => dhtmlspecialchars($data['identifier']),
				'description' => dhtmlspecialchars($data['description']),
				'required' => intval($data['required']),
				'unchangeable' => intval($data['unchangeable']),
				'search' => intval($data['search']),
				'subjectshow' => intval($data['subjectshow']),
				);

			if(in_array($data['type'], array('select', 'checkbox', 'radio'))) {
				if($data['rules']['choices']) {
					$choices = array();
					foreach(explode("\n", $data['rules']['choices']) as $item) {
						list($index, $choice) = explode('=', $item);
						$choices[trim($index)] = trim($choice);
					}
					$sortlist[$sortid][$optionid]['choices'] = $choices;
				} else {
					$typelist[$sortid][$optionid]['choices'] = array();
				}
			} elseif(in_array($data['type'], array('text', 'textarea'))) {
				$sortlist[$sortid][$optionid]['maxlength'] = intval($data['rules']['maxlength']);
			} elseif($data['type'] == 'image') {
				$sortlist[$sortid][$optionid]['maxwidth'] = intval($data['rules']['maxwidth']);
				$sortlist[$sortid][$optionid]['maxheight'] = intval($data['rules']['maxheight']);
			} elseif($data['type'] == 'number') {
				$sortlist[$sortid][$optionid]['maxnum'] = intval($data['rules']['maxnum']);
				$sortlist[$sortid][$optionid]['minnum'] = intval($data['rules']['minnum']);
			}
		}
		$query = $db->query("SELECT typeid, description, template, stemplate FROM {$tablepre}threadtypes WHERE special='1'");
		while($data = $db->fetch_array($query)) {
			$templatedata[$data['typeid']] = $data['template'];
			$stemplatedata[$data['typeid']] = $data['stemplate'];
			$threaddesc[$data['typeid']] = dhtmlspecialchars($data['description']);
		}

		foreach($sortlist as $sortid => $option) {
			writetocache($sortid, '', "\$_DTYPE = ".arrayeval($option).";\n\n\$_DTYPETEMPLATE = \"".str_replace('"', '\"', $templatedata[$sortid])."\";\n\n\$_DSTYPETEMPLATE = \"".str_replace('"', '\"', $stemplatedata[$sortid])."\";\n", 'threadsort_');
		}
	}

}

function setcssbackground(&$data, $code) {
	$codes = explode(' ', $data[$code]);
	$css = $codevalue = '';
	for($i = 0; $i < count($codes); $i++) {
		if($i < 2) {
			if($codes[$i] != '') {
				if($codes[$i]{0} == '#') {
					$css .= strtoupper($codes[$i]).' ';
					$codevalue = strtoupper($codes[$i]);
				} elseif(preg_match('/^http:\/\//i', $codes[$i])) {
					$css .= 'url(\"'.$codes[$i].'\") ';
				} else {
					$css .= 'url("'.$data['styleimgdir'].'/'.$codes[$i].'") ';
				}
			}
		} else {
			$css .= $codes[$i].' ';
		}
	}
	$data[$code] = $codevalue;
	$css = trim($css);
	return $css ? 'background: '.$css : '';
}

function updatesettings() {
	global $_DCACHE;
	if(isset($_DCACHE['settings']) && is_array($_DCACHE['settings'])) {
		writetocache('settings', '', '$_DCACHE[\'settings\'] = '.arrayeval($_DCACHE['settings']).";\n\n");
	}
}

function writetocache($script, $cachenames, $cachedata = '', $prefix = 'cache_') {
	global $authkey;
	if(is_array($cachenames) && !$cachedata) {
		foreach($cachenames as $name) {
			$cachedata .= getcachearray($name, $script);
		}
	}

	$dir = DISCUZ_ROOT.'./forumdata/cache/';
	if(!is_dir($dir)) {
		@mkdir($dir, 0777);
	}
	if($fp = @fopen("$dir$prefix$script.php", 'wb')) {
		fwrite($fp, "<?php\n//Discuz! cache file, DO NOT modify me!".
			"\n//Created: ".date("M j, Y, G:i").
			"\n//Identify: ".md5($prefix.$script.'.php'.$cachedata.$authkey)."\n\n$cachedata?>");
		fclose($fp);
	} else {
		exit('Can not write to cache files, please check directory ./forumdata/ and ./forumdata/cache/ .');
	}
}

function writetocsscache($data) {
	$cssdata = '';
	foreach(array('_common' => array('css_common', 'css_append'),
			'_special' => array('css_special', 'css_special_append'),
			'_wysiwyg' => array('css_wysiwyg', '_wysiwyg_append'),
			'_seditor' => array('css_seditor', 'css_seditor_append'),
			'_calendar' => array('css_calendar', 'css_calendar_append'),
			'_moderator' => array('css_moderator', 'css_moderator_append'),
			'_script' => array('css_script', 'css_script_append'),
			'_task_newbie' => array('css_task_newbie', 'css_task_newbie_append')
		) as $extra => $cssfiles) {
		$cssdata = '';
		foreach($cssfiles as $css) {
			$cssfile = DISCUZ_ROOT.'./'.$data['tpldir'].'/'.$css.'.htm';
			!file_exists($cssfile) && $cssfile = DISCUZ_ROOT.'./templates/default/'.$css.'.htm';
			if(file_exists($cssfile)) {
				$fp = fopen($cssfile, 'r');
				$cssdata .= @fread($fp, filesize($cssfile))."\n\n";
				fclose($fp);
			}
		}
		$cssdata = preg_replace("/\{([A-Z0-9]+)\}/e", '\$data[strtolower(\'\1\')]', $cssdata);
		$cssdata = preg_replace("/<\?.+?\?>\s*/", '', $cssdata);
		$cssdata = !preg_match('/^http:\/\//i', $data['styleimgdir']) ? str_replace("url(\"$data[styleimgdir]", "url(\"../../$data[styleimgdir]", $cssdata) : $cssdata;
		$cssdata = !preg_match('/^http:\/\//i', $data['styleimgdir']) ? str_replace("url($data[styleimgdir]", "url(../../$data[styleimgdir]", $cssdata) : $cssdata;
		$cssdata = !preg_match('/^http:\/\//i', $data['imgdir']) ? str_replace("url(\"$data[imgdir]", "url(\"../../$data[imgdir]", $cssdata) : $cssdata;
		$cssdata = !preg_match('/^http:\/\//i', $data['imgdir']) ? str_replace("url($data[imgdir]", "url(../../$data[imgdir]", $cssdata) : $cssdata;
		if($extra != '_script') {
			$cssdata = preg_replace(array('/\s*([,;:\{\}])\s*/', '/[\t\n\r]/', '/\/\*.+?\*\//'), array('\\1', '',''), $cssdata);
		}
		if(@$fp = fopen(DISCUZ_ROOT.'./forumdata/cache/style_'.$data['styleid'].$extra.'.css', 'w')) {
			fwrite($fp, $cssdata);
			fclose($fp);
		} else {
			exit('Can not write to cache files, please check directory ./forumdata/ and ./forumdata/cache/ .');
		}
	}

}

function writetojscache() {
	$dir = DISCUZ_ROOT.'include/js/';
	$dh = opendir($dir);
	$remove = array(
		'/(^|\r|\n)\/\*.+?(\r|\n)\*\/(\r|\n)/is',
		'/\/\/note.+?(\r|\n)/i',
		'/\/\/debug.+?(\r|\n)/i',
		'/(^|\r|\n)(\s|\t)+/',
		'/(\r|\n)/',
	);
	while(($entry = readdir($dh)) !== false) {
		if(fileext($entry) == 'js') {
			$jsfile = $dir.$entry;
			$fp = fopen($jsfile, 'r');
			$jsdata = @fread($fp, filesize($jsfile));
			fclose($fp);
			$jsdata = preg_replace($remove, '', $jsdata);
			if(@$fp = fopen(DISCUZ_ROOT.'./forumdata/cache/'.$entry, 'w')) {
				fwrite($fp, $jsdata);
				fclose($fp);
			} else {
				exit('Can not write to cache files, please check directory ./forumdata/ and ./forumdata/cache/ .');
			}
		}
	}
}

function getcachearray($cachename, $script = '') {
	global $db, $timestamp, $tablepre, $timeoffset, $maxbdays, $smcols, $smrows, $charset, $scriptlang;

	$cols = '*';
	$conditions = '';
	switch($cachename) {
		case 'settings':
			$table = 'settings';
			$conditions = "WHERE variable NOT IN ('siteuniqueid', 'mastermobile', 'bbrules', 'bbrulestxt', 'closedreason', 'creditsnotify', 'backupdir', 'custombackup', 'jswizard', 'maxonlines', 'modreasons', 'newsletter', 'welcomemsg', 'welcomemsgtxt', 'postno', 'postnocustom', 'customauthorinfo', 'focus', 'domainwhitelist', 'ipregctrl', 'ipverifywhite')";
			break;
		case 'ipctrl':
			$table = 'settings';
			$conditions = "WHERE variable IN ('ipregctrl', 'ipverifywhite')";
			break;
		case 'custominfo':
			$table = 'settings';
			$conditions = "WHERE variable IN ('extcredits', 'customauthorinfo', 'postno', 'postnocustom')";
			break;
		case 'request':
			$table = 'request';
			$conditions = '';
			break;
		case 'usergroups':
			$table = 'usergroups';
			$cols = 'groupid, type, grouptitle, creditshigher, creditslower, stars, color, groupavatar, readaccess, allowcusbbcode, allowgetattach, edittimelimit';
			$conditions = "ORDER BY creditslower";
			break;
		case 'ranks':
			$table = 'ranks';
			$cols = 'ranktitle, postshigher, stars, color';
			$conditions = "ORDER BY postshigher DESC";
			break;
		case 'announcements':
			$table = 'announcements';
			$cols = 'id, subject, type, starttime, endtime, displayorder, groups, message';
			$conditions = "WHERE starttime<='$timestamp' AND (endtime>='$timestamp' OR endtime='0') ORDER BY displayorder, starttime DESC, id DESC";
			break;
		case 'announcements_forum':
			$table = 'announcements a';
			$cols = 'a.id, a.author, m.uid AS authorid, a.subject, a.message, a.type, a.starttime, a.displayorder';
			$conditions = "LEFT JOIN {$tablepre}members m ON m.username=a.author WHERE a.type!=2 AND a.groups = '' AND a.starttime<='$timestamp' ORDER BY a.displayorder, a.starttime DESC, a.id DESC LIMIT 1";
			break;
		case 'globalstick':
			$table = 'forums';
			$cols = 'fid, type, fup';
			$conditions = "WHERE status='1' AND type IN ('forum', 'sub') ORDER BY type";
			break;
		case 'forumstick':
			$table = 'settings';
			$cols = 'variable, value';
			$conditions = "WHERE variable='forumstickthreads'";
			break;
		case 'forums':
			$table = 'forums f';
			$cols = 'f.fid, f.type, f.name, f.fup, f.simple, f.status, ff.viewperm, ff.formulaperm, ff.viewperm, ff.postperm, ff.replyperm, ff.getattachperm, ff.postattachperm, ff.extra, a.uid';
			$conditions = "LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid LEFT JOIN {$tablepre}access a ON a.fid=f.fid AND a.allowview>'0' ORDER BY f.type, f.displayorder";
			break;
		case 'onlinelist':
			$table = 'onlinelist';
			$conditions = "ORDER BY displayorder";
			break;
		case 'groupicon':
			$table = 'onlinelist';
			$conditions = "ORDER BY displayorder";
			break;
		case 'forumlinks':
			$table = 'forumlinks';
			$conditions = "ORDER BY displayorder";
			break;
		case 'bbcodes':
			$table = 'bbcodes';
			$conditions = "WHERE available>'0'";
			break;
		case 'bbcodes_display':
			$table = 'bbcodes';
			$cols = 'tag, icon, explanation, params, prompt';
			$conditions = "WHERE available='2' AND icon!='' ORDER BY displayorder";
			break;
		case 'smilies':
			$table = 'smilies s';
			$cols = 's.id, s.code, s.url, t.typeid';
			$conditions = "LEFT JOIN {$tablepre}imagetypes t ON t.typeid=s.typeid WHERE s.type='smiley' AND s.code<>'' AND t.available='1' ORDER BY LENGTH(s.code) DESC";
			break;
		case 'smileycodes':
			$table = 'imagetypes';
			$cols = 'typeid, directory';
			$conditions = "WHERE type='smiley' AND available='1' ORDER BY displayorder";
			break;
		case 'smileytypes':
			$table = 'imagetypes';
			$cols = 'typeid, name, directory';
			$conditions = "WHERE type='smiley' AND available='1' ORDER BY displayorder";
			break;
		case 'smilies_js':
			$table = 'imagetypes';
			$cols = 'typeid, name, directory';
			$conditions = "WHERE type='smiley' AND available='1' ORDER BY displayorder";
			break;
		case 'icons':
			$table = 'smilies';
			$cols = 'id, url';
			$conditions = "WHERE type='icon' ORDER BY displayorder";
			break;
		case 'stamps':
			$table = 'smilies';
			$cols = 'id, url, displayorder';
			$conditions = "WHERE type='stamp' ORDER BY displayorder";
			break;
		case 'stamptypeid':
			$table = 'smilies';
			$cols = 'displayorder, typeid';
			$conditions = "WHERE type='stamp' AND typeid>'0'";
			break;
		case 'fields_required':
			$table = 'profilefields';
			$cols = 'fieldid, invisible, title, description, required, unchangeable, selective, choices';
			$conditions = "WHERE available='1' AND required='1' ORDER BY displayorder";
			break;
		case 'fields_optional':
			$table = 'profilefields';
			$cols = 'fieldid, invisible, title, description, required, unchangeable, selective, choices';
			$conditions = "WHERE available='1' AND required='0' ORDER BY displayorder";
			break;
		case 'ipbanned':
			$db->query("DELETE FROM {$tablepre}banned WHERE expiration<'$timestamp'");
			$table = 'banned';
			$cols = 'ip1, ip2, ip3, ip4, expiration';
			break;
		case 'censor':
			$table = 'words';
			$cols = 'find, replacement, extra';
			break;
		case 'medals':
			$table = 'medals';
			$cols = 'medalid, name, image';
			$conditions = "WHERE available='1'";
			break;
		case 'magics':
			$table = 'magics';
			$cols = 'magicid, type, available, identifier, name, description, weight, price';
			break;
		case 'birthdays_index':
			$table = 'members';
			$cols = 'uid, username, email, bday';
			$conditions = "WHERE RIGHT(bday, 5)='".gmdate('m-d', $timestamp + $timeoffset * 3600)."' ORDER BY bday LIMIT $maxbdays";
			break;
		case 'birthdays':
			$table = 'members';
			$cols = 'uid';
			$conditions = "WHERE RIGHT(bday, 5)='".gmdate('m-d', $timestamp + $timeoffset * 3600)."' ORDER BY bday";
			break;
		case 'modreasons':
			$table = 'settings';
			$cols = 'value';
			$conditions = "WHERE variable='modreasons'";
			break;
		case 'faqs':
			$table = 'faqs';
			$cols = 'fpid, id, identifier, keyword';
			$conditions = "WHERE identifier!='' AND keyword!=''";
			break;
		case 'tags_viewthread':
			global $viewthreadtags;
			$taglimit = intval($viewthreadtags);
			$table = 'tags';
			$cols = 'tagname, total';
			$conditions = "WHERE closed=0 ORDER BY total DESC LIMIT $taglimit";
			break;
		case 'domainwhitelist':
			$table = 'settings';
			$cols = 'value';
			$conditions = "WHERE variable='domainwhitelist'";
			break;
	}

	$data = array();
	if(!in_array($cachename, array('focus', 'secqaa', 'heats')) && substr($cachename, 0, 5) != 'advs_') {
		if(empty($table) || empty($cols)) return '';
		$query = $db->query("SELECT $cols FROM {$tablepre}$table $conditions");
	}
	switch($cachename) {
		case 'settings':
			while($setting = $db->fetch_array($query)) {
				if($setting['variable'] == 'extcredits') {
					if(is_array($setting['value'] = unserialize($setting['value']))) {
						foreach($setting['value'] as $key => $value) {
							if($value['available']) {
								unset($setting['value'][$key]['available']);
							} else {
								unset($setting['value'][$key]);
							}
						}
					}
				} elseif($setting['variable'] == 'creditsformula') {
					if(!preg_match("/^([\+\-\*\/\.\d\(\)]|((extcredits[1-8]|digestposts|posts|threads|pageviews|oltime)([\+\-\*\/\(\)]|$)+))+$/", $setting['value']) || !is_null(@eval(preg_replace("/(digestposts|posts|threads|pageviews|oltime|extcredits[1-8])/", "\$\\1", $setting['value']).';'))) {
						$setting['value'] = '$member[\'extcredits1\']';
					} else {
						$setting['value'] = preg_replace("/(digestposts|posts|threads|pageviews|oltime|extcredits[1-8])/", "\$member['\\1']", $setting['value']);
					}
				} elseif($setting['variable'] == 'maxsmilies') {
					$setting['value'] = $setting['value'] <= 0 ? -1 : $setting['value'];
				} elseif($setting['variable'] == 'threadsticky') {
					$setting['value'] = explode(',', $setting['value']);
				} elseif($setting['variable'] == 'attachdir') {
					$setting['value'] = preg_replace("/\.asp|\\0/i", '0', $setting['value']);
					$setting['value'] = str_replace('\\', '/', substr($setting['value'], 0, 2) == './' ? DISCUZ_ROOT.$setting['value'] : $setting['value']);
				} elseif($setting['variable'] == 'onlinehold') {
					$setting['value'] = $setting['value'] * 60;
				} elseif($setting['variable'] == 'userdateformat') {
					if(empty($setting['value'])) {
						$setting['value'] = array();
					} else {
						$setting['value'] = dhtmlspecialchars(explode("\n", $setting['value']));
						$setting['value'] = array_map('trim', $setting['value']);
					}
				} elseif(in_array($setting['variable'], array('creditspolicy', 'ftp', 'secqaa', 'ec_credit', 'qihoo', 'spacedata', 'infosidestatus', 'uc', 'outextcredits', 'relatedtag', 'sitemessage', 'msn', 'uchome', 'heatthread', 'recommendthread', 'disallowfloat', 'indexhot', 'dzfeed_limit', 'binddomains', 'forumdomains', 'allowviewuserthread'))) {
					$setting['value'] = @unserialize($setting['value']);
				}
				$GLOBALS[$setting['variable']] = $data[$setting['variable']] = $setting['value'];
			}

			if($data['heatthread']['iconlevels']) {
				$data['heatthread']['iconlevels'] = explode(',', $data['heatthread']['iconlevels']);
				arsort($data['heatthread']['iconlevels']);
			} else {
				$data['heatthread']['iconlevels'] = array();
			}
			if($data['recommendthread']['status']) {
				if($data['recommendthread']['iconlevels']) {
					$data['recommendthread']['iconlevels'] = explode(',', $data['recommendthread']['iconlevels']);
					arsort($data['recommendthread']['iconlevels']);
				} else {
					$data['recommendthread']['iconlevels'] = array();
				}
			} else {
				$data['recommendthread'] = array('allow' => 0);
			}

			$data['allowviewuserthread'] = $data['allowviewuserthread']['allow'] && is_array($data['allowviewuserthread']['fids']) && $data['allowviewuserthread']['fids'] ? implodeids($data['allowviewuserthread']['fids']) : '';
			$data['sitemessage']['time'] = !empty($data['sitemessage']['time']) ? $data['sitemessage']['time'] * 1000 : 0;
			$data['sitemessage']['register'] = !empty($data['sitemessage']['register']) ? explode("\n", $data['sitemessage']['register']) : '';
			$data['sitemessage']['login'] = !empty($data['sitemessage']['login']) ? explode("\n", $data['sitemessage']['login']) : '';
			$data['sitemessage']['newthread'] = !empty($data['sitemessage']['newthread']) ? explode("\n", $data['sitemessage']['newthread']) : '';
			$data['sitemessage']['reply'] = !empty($data['sitemessage']['reply']) ? explode("\n", $data['sitemessage']['reply']) : '';
			$GLOBALS['version'] = $data['version'] = DISCUZ_KERNEL_VERSION;
			$GLOBALS['totalmembers'] = $data['totalmembers'] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members");
			$GLOBALS['lastmember'] = $data['lastmember'] = $db->result_first("SELECT username FROM {$tablepre}members ORDER BY uid DESC LIMIT 1");
			$data['cachethreadon'] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}forums WHERE status='1' AND threadcaches>0") ? 1 : 0;
			$data['cronnextrun'] = $db->result_first("SELECT nextrun FROM {$tablepre}crons WHERE available>'0' AND nextrun>'0' ORDER BY nextrun LIMIT 1");
			$data['disallowfloat'] = is_array($data['disallowfloat']) ? implode('|', $data['disallowfloat']) : '';

			$data['ftp']['connid'] = 0;
			$data['indexname'] = empty($data['indexname']) ? 'index.php' : $data['indexname'];
			if(!$data['imagelib']) {
				unset($data['imageimpath']);
			}

			if(is_array($data['relatedtag']['order'])) {
				asort($data['relatedtag']['order']);
				$relatedtag = array();
				foreach($data['relatedtag']['order'] AS $k => $v) {
					$relatedtag['status'][$k] = $data['relatedtag']['status'][$k];
					$relatedtag['name'][$k] = $data['relatedtag']['name'][$k];
					$relatedtag['limit'][$k] = $data['relatedtag']['limit'][$k];
					$relatedtag['template'][$k] = $data['relatedtag']['template'][$k];
				}
				$data['relatedtag'] = $relatedtag;

				foreach((array)$data['relatedtag']['status'] AS $appid => $status) {
					if(!$status) {
						unset($data['relatedtag']['limit'][$appid]);
					}
				}
				unset($data['relatedtag']['status'], $data['relatedtag']['order'], $relatedtag);
			}

			$data['seccodedata'] = $data['seccodedata'] ? unserialize($data['seccodedata']) : array();
			if($data['seccodedata']['type'] == 2) {
				if(extension_loaded('ming')) {
					unset($data['seccodedata']['background'], $data['seccodedata']['adulterate'],
						$data['seccodedata']['ttf'], $data['seccodedata']['angle'],
						$data['seccodedata']['color'], $data['seccodedata']['size'],
						$data['seccodedata']['animator']);
				} else {
					$data['seccodedata']['animator'] = 0;
				}
			}

			$secqaacheck = sprintf('%03b', $data['secqaa']['status']);
			$data['secqaa']['status'] = array(
				1 => $secqaacheck{2},
				2 => $secqaacheck{1},
				3 => $secqaacheck{0}
			);
			if(!$data['secqaa']['status'][2] && !$data['secqaa']['status'][3]) {
				unset($data['secqaa']['minposts']);
			}

			if($data['watermarktype'] == 2 && $data['watermarktext']) {
				$data['watermarktext'] = unserialize($data['watermarktext']);
				if($data['watermarktext']['text'] && strtoupper($charset) != 'UTF-8') {
					require_once DISCUZ_ROOT.'include/chinese.class.php';
					$c = new Chinese($charset, 'utf8');
					$data['watermarktext']['text'] = $c->Convert($data['watermarktext']['text']);
				}
				$data['watermarktext']['text'] = bin2hex($data['watermarktext']['text']);
				$data['watermarktext']['fontpath'] = 'images/fonts/'.$data['watermarktext']['fontpath'];
				$data['watermarktext']['color'] = preg_replace('/#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/e', "hexdec('\\1').','.hexdec('\\2').','.hexdec('\\3')", $data['watermarktext']['color']);
				$data['watermarktext']['shadowcolor'] = preg_replace('/#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/e', "hexdec('\\1').','.hexdec('\\2').','.hexdec('\\3')", $data['watermarktext']['shadowcolor']);
			} else {
				$data['watermarktext'] = array();
			}

			$tradetypes = implodeids(unserialize($data['tradetypes']));
			$data['tradetypes'] = array();
			if($tradetypes) {
				$query = $db->query("SELECT typeid, name FROM {$tablepre}threadtypes WHERE typeid in ($tradetypes)");
				while($type = $db->fetch_array($query)) {
					$data['tradetypes'][$type['typeid']] = $type['name'];
				}
			}

			$data['styles'] = array();
			$query = $db->query("SELECT styleid, name FROM {$tablepre}styles WHERE available='1'");
			while($style = $db->fetch_array($query)) {
				$data['styles'][$style['styleid']] = dhtmlspecialchars($style['name']);
			}
			$data['stylejumpstatus'] = $data['stylejump'] && count($data['styles']) > 1;

			$globaladvs = advertisement('all');
			$data['globaladvs'] = $globaladvs['all'] ? $globaladvs['all'] : array();
			$data['redirectadvs'] = $globaladvs['redirect'] ? $globaladvs['redirect'] : array();

			$data['invitecredit'] = '';
			if($data['inviteconfig'] = unserialize($data['inviteconfig'])) {
				$data['invitecredit'] = $data['inviteconfig']['invitecredit'];
			}
			unset($data['inviteconfig']);

			$data['newbietasks'] = array();
			$query = $db->query("SELECT taskid, name, scriptname FROM {$tablepre}tasks WHERE available='2' AND newbietask='1' ORDER BY displayorder");
			while($task = $db->fetch_array($query)) {
				$taskid = $task['taskid'];
				unset($task['taskid']);
				$task['scriptname'] = substr($task['scriptname'], 7);
				$data['newbietasks'][$taskid] = $task;
			}

			$outextcreditsrcs = $outextcredits = array();
			foreach((array)$data['outextcredits'] as $value) {
				$outextcreditsrcs[$value['creditsrc']] = $value['creditsrc'];
				$key = $value['appiddesc'].'|'.$value['creditdesc'];
				if(!isset($outextcredits[$key])) {
					$outextcredits[$key] = array('title' => $value['title'], 'unit' => $value['unit']);
				}
				$outextcredits[$key]['ratiosrc'][$value['creditsrc']] = $value['ratiosrc'];
				$outextcredits[$key]['ratiodesc'][$value['creditsrc']] = $value['ratiodesc'];
				$outextcredits[$key]['creditsrc'][$value['creditsrc']] = $value['ratio'];
			}
			$data['outextcredits'] = $outextcredits;

			$exchcredits = array();
			$allowexchangein = $allowexchangeout = FALSE;
			foreach((array)$data['extcredits'] as $id => $credit) {
				$data['extcredits'][$id]['img'] = $credit['img'] ? '<img style="vertical-align:middle" src="'.$credit['img'].'" />' : '';
				if(!empty($credit['ratio'])) {
					$exchcredits[$id] = $credit;
					$credit['allowexchangein'] && $allowexchangein = TRUE;
					$credit['allowexchangeout'] && $allowexchangeout = TRUE;
				}
				$data['creditnotice'] && $data['creditnames'][] = str_replace("'", "\'", htmlspecialchars($id.'|'.$credit['title'].'|'.$credit['unit']));
			}
			$data['creditnames'] = $data['creditnotice'] ? implode(',', $data['creditnames']) : '';

			$creditstranssi = explode(',', $data['creditstrans']);
			$data['creditstrans'] = $creditstranssi[0];
			unset($creditstranssi[0]);
			$data['creditstransextra'] = $creditstranssi;
			for($i = 1;$i < 6;$i++) {
				$data['creditstransextra'][$i] = !$data['creditstransextra'][$i] ? $data['creditstrans'] : $data['creditstransextra'][$i];
			}
			$data['exchangestatus'] = $allowexchangein && $allowexchangeout;
			$data['transferstatus'] = isset($data['extcredits'][$data['creditstrans']]);

			list($data['zoomstatus'], $data['imagemaxwidth']) = explode("\t", $data['zoomstatus']);
			$data['imagemaxwidth'] = substr(trim($data['imagemaxwidth']), -1, 1) != '%' && $data['imagemaxwidth'] <= 1920 ? $data['imagemaxwidth'] : '';

			$data['msn']['on'] = $data['msn']['on'] && $data['msn']['domain'] ? 1 : 0;
			$data['msn']['domain'] = $data['msn']['on'] ? $data['msn']['domain'] : 'discuz.org';

			if($data['qihoo']['status']) {
				$qihoo = $data['qihoo'];
				$data['qihoo']['links'] = $data['qihoo']['relate'] = array();
				foreach(explode("\n", trim($qihoo['keywords'])) AS $keyword) {
					if($keyword = trim($keyword)) {
						$data['qihoo']['links']['keywords'][] = '<a href="search.php?srchtype=qihoo&amp;srchtxt='.rawurlencode($keyword).'&amp;searchsubmit=yes" target="_blank">'.dhtmlspecialchars(trim($keyword)).'</a>';
					}
				}
				foreach((array)$qihoo['topics'] AS $topic) {
					if($topic['topic'] = trim($topic['topic'])) {
						$data['qihoo']['links']['topics'][] = '<a href="topic.php?topic='.rawurlencode($topic['topic']).'&amp;keyword='.rawurlencode($topic['keyword']).'&amp;stype='.$topic['stype'].'&amp;length='.$topic['length'].'&amp;relate='.$topic['relate'].'" target="_blank">'.dhtmlspecialchars(trim($topic['topic'])).'</a>';
					}
				}
				if(is_array($qihoo['relatedthreads'])) {
					if($data['qihoo']['relate']['bbsnum'] = intval($qihoo['relatedthreads']['bbsnum'])) {
						$data['qihoo']['relate']['position'] = intval($qihoo['relatedthreads']['position']);
						$data['qihoo']['relate']['validity'] = intval($qihoo['relatedthreads']['validity']);
						if($data['qihoo']['relate']['webnum'] = intval($qihoo['relatedthreads']['webnum'])) {
							$data['qihoo']['relate']['banurl'] = $qihoo['relatedthreads']['banurl'] ? '/('.str_replace("\r\n", '|', $qihoo['relatedthreads']['banurl']).')/i' : '';
							$data['qihoo']['relate']['type'] = implode('|', (array)$qihoo['relatedthreads']['type']);
							$data['qihoo']['relate']['order'] = intval($qihoo['relatedthreads']['order']);
						}
					} else {
						$data['qihoo']['relate'] = array();
					}
				}
				unset($qihoo, $data['qihoo']['keywords'], $data['qihoo']['topics'], $data['qihoo']['relatedthreads']);
			} else {
				$data['qihoo'] = array();
			}

			$data['prompts'] = $data['promptkeys'] = $data['promptpmids'] = array();
			$query = $db->query("SELECT * FROM {$tablepre}prompttype ORDER BY id");
			while($prompttype = $db->fetch_array($query)) {
				if($prompttype['key'] == 'task' && !$data['taskon'] || $prompttype['key'] == 'magics' && !$data['magicstatus']) {
					continue;
				}
				$data['prompts'][$prompttype['key']] = array('name' => $prompttype['name'], 'script' => $prompttype['script'], 'id' => $prompttype['id'], 'new' => 0);
				$data['promptkeys'][$prompttype['id']] = $prompttype['key'];
				if(!$prompttype['script']) {
					$data['promptpmids'][] = $prompttype['id'];
				}
			}
			require_once DISCUZ_ROOT.'./uc_client/client.php';
			$ucnewpm = uc_pm_checknew($GLOBALS['discuz_uid'], 2);
			$data['announcepm'] = $ucnewpm['announcepm'];

			$data['plugins'] = $data['pluginlangs'] = $data['templatelangs'] = $data['pluginlinks'] = $data['hookscript'] = $data['threadplugins'] = $data['specialicon'] = $adminmenu = $scriptlang = array();
			$threadpluginicons = FALSE;
			$query = $db->query("SELECT pluginid, available, name, identifier, directory, datatables, modules FROM {$tablepre}plugins");
			$data['plugins']['available'] = array();
			while($plugin = $db->fetch_array($query)) {
				$templatelang = array();
				$addadminmenu = $plugin['available'] && $db->result_first("SELECT count(*) FROM {$tablepre}pluginvars WHERE pluginid='$plugin[pluginid]'") ? TRUE : FALSE;
				$plugin['modules'] = unserialize($plugin['modules']);
				if($plugin['available']) {
					$data['plugins']['available'][] = $plugin['identifier'];
					if(!empty($plugin['modules']['extra']['langexists'])) {
						@include DISCUZ_ROOT.'./forumdata/plugins/'.$plugin['identifier'].'.lang.php';
						if(!empty($scriptlang)) {
							$data['pluginlangs'][] = $plugin['identifier'];
						}
						if(!empty($templatelang)) {
							$data['templatelangs'][] = $plugin['identifier'];
						}
					}
				}
				$plugin['directory'] = $plugin['directory'].((!empty($plugin['directory']) && substr($plugin['directory'], -1) != '/') ? '/' : '');
				if(is_array($plugin['modules'])) {
					unset($plugin['modules']['extra']);
					foreach($plugin['modules'] as $module) {
						if($plugin['available'] && isset($module['name'])) {
							$k = '';
							switch($module['type']) {
								case 1:
									$k = 'links';
								case 5:
									$k = !$k ? 'jsmenu' : $k;
								case 7:
									$k = !$k ? 'plinks_my' : $k;
								case 9:
									$k = !$k ? 'plinks_tools' : $k;
									$data['plugins'][$k][] = array('displayorder' => $module['displayorder'], 'adminid' => $module['adminid'], 'url' => "<a id=\"mn_plink_$module[name]\" href=\"$module[url]\">$module[menu]</a>");
									break;
								case 2:
									$k = 'links';
								case 6:
									$k = !$k ? 'jsmenu' : $k;
								case 8:
									$k = !$k ? 'plinks_my' : $k;
								case 10:
									$k = !$k ? 'plinks_tools' : $k;
									$data['plugins'][$k][] = array('displayorder' => $module['displayorder'], 'id' => $plugin['identifier'].':'.$module['name'], 'adminid' => $module['adminid'], 'url' => "<a id=\"mn_plugin_$plugin[identifier]_$module[name]\" href=\"plugin.php?id=$plugin[identifier]:$module[name]\">$module[menu]</a>");
									$data['pluginlinks'][$plugin['identifier']][$module['name']] = array('adminid' => $module['adminid'], 'directory' => $plugin['directory']);
									break;
								case 13:
									$data['plugins']['script'][$plugin['identifier']][$module['name']] = array('directory' => $plugin['directory']);
									break;
								case 14:
									$k = 'faq';
								case 15:
									$k = !$k ? 'modcp_base' : $k;
								case 16:
									$k = !$k ? 'modcp_tools' : $k;
									$data['plugins'][$k][$plugin['identifier'].':'.$module['name']] = array('displayorder' => $module['displayorder'], 'name' => $module['menu'], 'directory' => $plugin['directory']);
									break;
								case 3:
									$addadminmenu = TRUE;
									break;
								case 4:
									$data['plugins']['include'][$plugin['identifier']] = array('displayorder' => $module['displayorder'], 'adminid' => $module['adminid'], 'script' => $plugin['directory'].$module['name']);
									break;
								case 11:
									$script = $plugin['directory'].$module['name'];
									@include_once './plugins/'.$script.'.class.php';
									if(class_exists('plugin_'.$plugin['identifier'])) {
										$classname = 'plugin_'.$plugin['identifier'];
										$hookmethods = get_class_methods($classname);
										foreach($hookmethods as $funcname) {
											$v = explode('_', $funcname);
											$curscript = $v[0];
											if(!$curscript || $classname == $funcname) {
												continue;
											}
											if(!@in_array($script, $data['hookscript'][$curscript]['module'])) {
												$data['hookscript'][$curscript]['module'][$plugin['identifier']] = $script;
												$data['hookscript'][$curscript]['adminid'][$plugin['identifier']] = $module['adminid'];
											}
											if(preg_match('/\_output$/', $funcname)) {
												$varname = preg_replace('/\_output$/', '', $funcname);
												$data['hookscript'][$curscript]['outputfuncs'][$varname][] = array('displayorder' => $module['displayorder'], 'func' => array($plugin['identifier'], $funcname));
											} else {
												$data['hookscript'][$curscript]['funcs'][$funcname][] = array('displayorder' => $module['displayorder'], 'func' => array($plugin['identifier'], $funcname));
											}
										}
									}
									break;
								case 12:
									$script = $plugin['directory'].$module['name'];
									@include_once './plugins/'.$script.'.class.php';
									if(class_exists('threadplugin_'.$plugin['identifier'])) {
										$classname = 'threadplugin_'.$plugin['identifier'];
										$hookclass = new $classname;
										if($hookclass->name) {
											$data['threadplugins'][$plugin['identifier']]['name'] = $hookclass->name;
											$data['threadplugins'][$plugin['identifier']]['module'] = $script;
											if($hookclass->iconfile) {
												$threadpluginicons = TRUE;
												$data['threadplugins'][$plugin['identifier']]['icon'] = $hookclass->iconfile;
											}
										}
									}
									break;
							}
						}
					}
				}
				if($addadminmenu) {
					$adminmenu[] = array('url' => "plugins&operation=config&pluginid=$plugin[pluginid]", 'name' => $plugin['name']);
				}
			}
			$data['my_status'] = $data['plugins']['available'] && in_array('manyou', $data['plugins']['available']) ? $data['my_status'] : 0;
			writetocache('scriptlang', '', getcachevars(array('scriptlang' => $scriptlang)));
			if($threadpluginicons) {
				$existicons = array();
				$query = $db->query("SELECT id, url FROM {$tablepre}smilies WHERE type='icon'");
				while($icon = $db->fetch_array($query)) {
					$icons[$icon['url']] = $icon['id'];
				}
				$iconupdate = FALSE;
				foreach($data['threadplugins'] as $identifier => $icon) {
					if(!array_key_exists($icon['icon'], $icons)) {
						$db->query("INSERT INTO {$tablepre}smilies (type, url) VALUES ('icon', '$icon[icon]')");
						$iconid = $db->insert_id();
						$iconupdate = TRUE;
					} else {
						$iconid = $icons[$icon['icon']];
					}
					$data['threadplugins'][$identifier]['iconid'] = $iconid;
					$data['specialicon'][$iconid] = $identifier;
					$data['threadplugins'][$identifier]['icon'] = 'images/icons/'.$icon['icon'];
				}
				if($iconupdate) {
					updatecache('icons');
				}
			}

			foreach($data['hookscript'] as $curscript => $scriptdata) {
				if(is_array($scriptdata['funcs'])) {
					foreach($scriptdata['funcs'] as $funcname => $funcs) {
						usort($funcs, 'pluginmodulecmp');
						$tmp = array();
						foreach($funcs as $k => $v) {
							$tmp[$k] = $v['func'];
						}
						$data['hookscript'][$curscript]['funcs'][$funcname] = $tmp;
					}
				}
				if(is_array($scriptdata['outputfuncs'])) {
					foreach($scriptdata['outputfuncs'] as $funcname => $funcs) {
						usort($funcs, 'pluginmodulecmp');
						$tmp = array();
						foreach($funcs as $k => $v) {
							$tmp[$k] = $v['func'];
						}
						$data['hookscript'][$curscript]['outputfuncs'][$funcname] = $tmp;
					}
				}
			}

			$data['tradeopen'] = $db->result_first("SELECT count(*) FROM {$tablepre}usergroups WHERE allowposttrade='1'") ? 1 : 0;

			foreach(array('links', 'plinks_my', 'plinks_tools', 'include', 'jsmenu', 'faq', 'modcp_base', 'modcp_member', 'modcp_forum') as $pluginkey) {
				if(is_array($data['plugins'][$pluginkey])) {
					if(in_array($pluginkey, array('faq', 'modcp_base', 'modcp_tools'))) {
						uasort($data['plugins'][$pluginkey], 'pluginmodulecmp');
					} else {
						usort($data['plugins'][$pluginkey], 'pluginmodulecmp');
					}
					foreach($data['plugins'][$pluginkey] as $key => $module) {
						unset($data['plugins'][$pluginkey][$key]['displayorder']);
					}
				}
			}
			writetocache('adminmenu', '', getcachevars(array('adminmenu' => $adminmenu)), '');

			$data['hooks'] = array();
			$query = $db->query("SELECT ph.title, ph.code, p.identifier FROM {$tablepre}plugins p
				LEFT JOIN {$tablepre}pluginhooks ph ON ph.pluginid=p.pluginid AND ph.available='1'
				WHERE p.available='1' ORDER BY p.identifier");
			while($hook = $db->fetch_array($query)) {
				if($hook['title'] && $hook['code']) {
					$data['hooks'][$hook['identifier'].'_'.$hook['title']] = $hook['code'];
				}
			}

			$data['navs'] = $data['subnavs'] = $data['navmns'] = array();
			list($mnid) = explode('.', basename($data['indexname']));
			$data['navmns'][] = $mnid;$mngsid = 1;
			$query = $db->query("SELECT * FROM {$tablepre}navs WHERE available='1' AND parentid='0' ORDER BY displayorder");
			while($nav = $db->fetch_array($query)) {
				if($nav['type'] == '0' && (($nav['url'] == 'member.php?action=list' && !$data['memliststatus']) || ($nav['url'] == 'tag.php' && !$data['tagstatus']))) {
					continue;
				}
				$nav['style'] = parsehighlight($nav['highlight']);
				if($db->result_first("SELECT COUNT(*) FROM {$tablepre}navs WHERE parentid='$nav[id]' AND available='1'")) {
					$id = random(6);
					$subquery = $db->query("SELECT * FROM {$tablepre}navs WHERE available='1' AND parentid='$nav[id]' ORDER BY displayorder");
					$subnavs = "<ul class=\"popupmenu_popup headermenu_popup\" id=\"".$id."_menu\" style=\"display: none\">";
					while($subnav = $db->fetch_array($subquery)) {
						$subnavs .= "<li><a href=\"$subnav[url]\" hidefocus=\"true\" ".($subnav['title'] ? "title=\"$subnav[title]\" " : '').($subnav['target'] == 1 ? "target=\"_blank\" " : '').parsehighlight($subnav['highlight']).">$subnav[name]</a></li>";
					}
					$subnavs .= '</ul>';
					$data['subnavs'][] = $subnavs;
					$data['navs'][$nav['id']]['nav'] = "<li class=\"menu_".$nav['id']."\" id=\"$id\" onmouseover=\"showMenu({'ctrlid':this.id})\"><a href=\"$nav[url]\" hidefocus=\"true\" ".($nav['title'] ? "title=\"$nav[title]\" " : '').($nav['target'] == 1 ? "target=\"_blank\" " : '')." class=\"dropmenu\"$nav[style]>$nav[name]</a></li>";
				} else {
					if($nav['id'] == '3') {
						$data['navs'][$nav['id']]['nav'] = !empty($data['plugins']['jsmenu']) ? "<li class=\"menu_3\" id=\"pluginnav\" onmouseover=\"showMenu({'ctrlid':this.id,'menuid':'plugin_menu'})\"><a href=\"javascript:;\" hidefocus=\"true\" ".($nav['title'] ? "title=\"$nav[title]\" " : '').($nav['target'] == 1 ? "target=\"_blank\" " : '')."class=\"dropmenu\"$nav[style]>$nav[name]</a></li>" : '';
					} elseif($nav['id'] == '5') {
						$data['navs'][$nav['id']]['nav'] = "<li class=\"menu_5\"><a href=\"misc.php?action=nav\" hidefocus=\"true\" ".($nav['title'] ? "title=\"$nav[title]\" " : '')."onclick=\"showWindow('nav', this.href);return false;\"$nav[style]>$nav[name]</a></li>";
					} else {
						if($nav['id'] == '1') {
							$nav['url'] = $GLOBALS['indexname'];
						}
						list($mnid) = explode('.', basename($nav['url']));
						$purl = parse_url($nav['url']);
						$getvars = array();
						if($purl['query']) {
							parse_str($purl['query'], $getvars);
							$mnidnew = $mnid.'_'.$mngsid;
							$data['navmngs'][$mnid][] = array($getvars, $mnidnew);
							$mnid = $mnidnew;
							$mngsid++;
						}
						$data['navmns'][] = $mnid;
						$data['navs'][$nav['id']]['nav'] = "<li class=\"menu_".$nav['id']."\"><a href=\"$nav[url]\" hidefocus=\"true\" ".($nav['title'] ? "title=\"$nav[title]\" " : '').($nav['target'] == 1 ? "target=\"_blank\" " : '')."id=\"mn_$mnid\"$nav[style]>$nav[name]</a></li>";
					}
				}
				$data['navs'][$nav['id']]['level'] = $nav['level'];
			}

			$ucapparray = uc_app_ls();
			$data['allowsynlogin'] = isset($ucapparray[UC_APPID]['synlogin']) ? $ucapparray[UC_APPID]['synlogin'] : 1;
			$appnamearray = array('UCHOME','XSPACE','DISCUZ','SUPESITE','SUPEV','ECSHOP','ECMALL');
			$data['ucapp'] = $data['ucappopen'] = array();
			$data['uchomeurl'] = '';
			$appsynlogins = 0;
			foreach($ucapparray as $apparray) {
				if($apparray['appid'] != UC_APPID) {
					if(!empty($apparray['synlogin'])) {
						$appsynlogins = 1;
					}
					if($data['uc']['navlist'][$apparray['appid']] && $data['uc']['navopen']) {
						$data['ucapp'][$apparray['appid']]['name'] = $apparray['name'];
						$data['ucapp'][$apparray['appid']]['url'] = $apparray['url'];
					}
				}
				if(!empty($apparray['viewprourl'])) {
					$data['ucapp'][$apparray['appid']]['viewprourl'] = $apparray['url'].$apparray['viewprourl'];
				}
				foreach($appnamearray as $name) {
					if($apparray['type'] == $name && $apparray['appid'] != UC_APPID) {
						$data['ucappopen'][$name] = 1;
						if($name == 'UCHOME') {
							$data['uchomeurl'] = $apparray['url'];
						} elseif($name == 'XSPACE') {
							$data['xspaceurl'] = $apparray['url'];
						}
					}
				}
			}
			$data['allowsynlogin'] = $data['allowsynlogin'] && $appsynlogins ? 1 : 0;
			$data['homeshow'] = $data['uchomeurl'] && $data['uchome']['homeshow'] ? $data['uchome']['homeshow'] : '0';
/*
			if($data['uchomeurl']) {
				$data['homeshow']['avatar'] = $data['uc']['homeshow'] & 1 ? 1 : 0;
				$data['homeshow']['viewpro'] = $data['uc']['homeshow'] & 2 ? 1 : 0;
				$data['homeshow']['ad'] = $data['uc']['homeshow'] & 4 ? 1 : 0;
				$data['homeshow']['side'] = $data['uc']['homeshow'] & 8 ? 1 : 0;
			}
*/
			$data['medalstatus'] = intval($db->result_first("SELECT count(*) FROM {$tablepre}medals WHERE available='1'"));

			include language('runtime');
			$dlang['date'] = explode(',', $dlang['date']);
			$data['dlang'] = $dlang;

			unset($data['allowthreadplugin']);
			if($data['jspath'] == 'forumdata/cache/') {
				writetojscache();
			} elseif(!$data['jspath']) {
				$data['jspath'] = 'include/js/';
			}

			list($ec_contract, $ec_securitycode, $ec_partner, $ec_creditdirectpay) = explode("\t", authcode($data['ec_contract'], 'DECODE', $data['authkey']));
			unset($data['ec_contract']);
			if($ec_contract) {
				$ec_partner = addslashes($ec_partner);
				$alipaycache = "define('DISCUZ_PARTNER', '$ec_partner');\n";
				$ec_securitycode = addslashes($ec_securitycode);
				$alipaycache .= "define('DISCUZ_SECURITYCODE', '$ec_securitycode');\n";
				$ec_creditdirectpay = !empty($ec_creditdirectpay) ? '1' : '0';
				$alipaycache .= "define('DISCUZ_DIRECTPAY', $ec_creditdirectpay);\n";
				writetocache('alipaycontract', '', $alipaycache);
			} else {
				@unlink(DISCUZ_ROOT.'./forumdata/cache/cache_alipaycontract.php');
			}

			break;
		case 'ipctrl':
			while($setting = $db->fetch_array($query)) {
				$data[$setting['variable']] = $setting['value'];
			}
			break;
		case 'custominfo':
			while($setting = $db->fetch_array($query)) {
				$data[$setting['variable']] = $setting['value'];
			}

			$data['customauthorinfo'] = unserialize($data['customauthorinfo']);
			$data['customauthorinfo'] = $data['customauthorinfo'][0];
			$data['extcredits'] = unserialize($data['extcredits']);

			include language('templates');
			$authorinfoitems = array(
				'uid' => '$post[uid]',
				'posts' => '$post[posts]',
				'threads' => '$post[threads]',
				'digest' => '$post[digestposts]',
				'credits' => '$post[credits]',
				'readperm' => '$post[readaccess]',
				'gender' => '$post[gender]',
				'location' => '$post[location]',
				'oltime' => '$post[oltime] '.$language['hours'],
				'regtime' => '$post[regdate]',
				'lastdate' => '$post[lastdate]',
			);

			if(!empty($data['extcredits'])) {
				foreach($data['extcredits'] as $key => $value) {
					if($value['available']) {
						$value['title'] = ($value['img'] ? '<img style="vertical-align:middle" src="'.$value['img'].'" /> ' : '').$value['title'];
						$authorinfoitems['extcredits'.$key] = array($value['title'], '$post[extcredits'.$key.'] {$extcredits['.$key.'][unit]}');
					}
				}
			}

			$data['fieldsadd'] = '';$data['profilefields'] = array();
			$query = $db->query("SELECT * FROM {$tablepre}profilefields WHERE available='1' AND invisible='0' ORDER BY displayorder");
			while($field = $db->fetch_array($query)) {
				$data['fieldsadd'] .= ', mf.field_'.$field['fieldid'];
				if($field['selective']) {
					foreach(explode("\n", $field['choices']) as $item) {
						list($index, $choice) = explode('=', $item);
						$data['profilefields'][$field['fieldid']][trim($index)] = trim($choice);
					}
					$authorinfoitems['field_'.$field['fieldid']] = array($field['title'], '{$profilefields['.$field['fieldid'].'][$post[field_'.$field['fieldid'].']]}');
				} else {
					$authorinfoitems['field_'.$field['fieldid']] = array($field['title'], '$post[field_'.$field['fieldid'].']');
				}
			}

			$customauthorinfo = array();
			if(is_array($data['customauthorinfo'])) {
				foreach($data['customauthorinfo'] as $key => $value) {
					if(array_key_exists($key, $authorinfoitems)) {
						if(substr($key, 0, 10) == 'extcredits') {
							$v = addcslashes('<dt>'.$authorinfoitems[$key][0].'</dt><dd>'.$authorinfoitems[$key][1].'&nbsp;</dd>', '"');
						} elseif(substr($key, 0, 6) == 'field_') {
							$v = addcslashes('<dt>'.$authorinfoitems[$key][0].'</dt><dd>'.$authorinfoitems[$key][1].'&nbsp;</dd>', '"');
						} elseif($key == 'gender') {
							$v = '".('.$authorinfoitems['gender'].' == 1 ? "'.addcslashes('<dt>'.$language['authorinfoitems_'.$key].'</dt><dd>'.$language['authorinfoitems_gender_male'].'&nbsp;</dd>', '"').'" : ('.$authorinfoitems['gender'].' == 2 ? "'.addcslashes('<dt>'.$language['authorinfoitems_'.$key].'</dt><dd>'.$language['authorinfoitems_gender_female'].'&nbsp;</dd>', '"').'" : ""))."';
						} elseif($key == 'location') {
							$v = '".('.$authorinfoitems[$key].' ? "'.addcslashes('<dt>'.$language['authorinfoitems_'.$key].'</dt><dd>'.$authorinfoitems[$key].'&nbsp;</dd>', '"').'" : "")."';
						} else {
							$v = addcslashes('<dt>'.$language['authorinfoitems_'.$key].'</dt><dd>'.$authorinfoitems[$key].'&nbsp;</dd>', '"');
						}
						if(isset($value['left'])) {
							$customauthorinfo[1][] = $v;
						}
						if(isset($value['menu'])) {
							$customauthorinfo[2][] = $v;
						}
						if(isset($value['special'])) {
							$customauthorinfo[3][] = $v;
						}
					}
				}
			}

			$customauthorinfo[1] = @implode('', $customauthorinfo[1]);
			$customauthorinfo[2] = @implode('', $customauthorinfo[2]);
			$data['customauthorinfo'] = $customauthorinfo;

			$postnocustomnew[0] = $data['postno'] != '' ? (preg_match("/^[\x01-\x7f]+$/", $data['postno']) ? '<sup>'.$data['postno'].'</sup>' : $data['postno']) : '<sup>#</sup>';
			$data['postnocustom'] = unserialize($data['postnocustom']);
			if(is_array($data['postnocustom'])) {
				foreach($data['postnocustom'] as $key => $value) {
					$value = trim($value);
					$postnocustomnew[$key + 1] = preg_match("/^[\x01-\x7f]+$/", $value) ? '<sup>'.$value.'</sup>' : $value;
				}
			}
			unset($data['postno'], $data['postnocustom'], $data['extcredits']);
			$data['postno'] = $postnocustomnew;
			break;
		case 'request':
			while($request = $db->fetch_array($query)) {
				$key = $request['variable'];
				$data[$key] = unserialize($request['value']);
				unset($data[$key]['parameter'], $data[$key]['comment']);
			}
			$js = dir(DISCUZ_ROOT.'./forumdata/cache');
			while($entry = $js->read()) {
				if(preg_match("/^(javascript_|request_)/", $entry)) {
					@unlink(DISCUZ_ROOT.'./forumdata/cache/'.$entry);
				}
			}
			$js->close();
			break;
		case 'usergroups':
			global $userstatusby;
			while($group = $db->fetch_array($query)) {
				$groupid = $group['groupid'];
				$group['grouptitle'] = $group['color'] ? '<font color="'.$group['color'].'">'.$group['grouptitle'].'</font>' : $group['grouptitle'];
				if($userstatusby == 1) {
					$group['userstatusby'] = 1;
				} elseif($userstatusby == 2) {
					if($group['type'] != 'member') {
						$group['userstatusby'] = 1;
					} else {
						$group['userstatusby'] = 2;
					}
				}
				if($group['type'] != 'member') {
					unset($group['creditshigher'], $group['creditslower']);
				}
				unset($group['groupid'], $group['color']);
				$data[$groupid] = $group;
			}
			break;
		case 'ranks':
			global $userstatusby;
			if($userstatusby == 2) {
				while($rank = $db->fetch_array($query)) {
					$rank['ranktitle'] = $rank['color'] ? '<font color="'.$rank['color'].'">'.$rank['ranktitle'].'</font>' : $rank['ranktitle'];
					unset($rank['color']);
					$data[] = $rank;
				}
			}
			break;
		case 'announcements':
			$data = array();
			while($datarow = $db->fetch_array($query)) {
				if($datarow['type'] == 2) {
					$datarow['pmid'] = $datarow['id'];
					unset($datarow['id']);
					unset($datarow['message']);
					$datarow['subject'] = cutstr($datarow['subject'], 60);
				}
				$datarow['groups'] = empty($datarow['groups']) ? array() : explode(',', $datarow['groups']);
				$data[] = $datarow;
			}
			break;
		case 'announcements_forum':
			if($data = $db->fetch_array($query)) {
				$data['authorid'] = intval($data['authorid']);
				if(empty($data['type'])) {
					unset($data['message']);
				}
			} else {
				$data = array();
			}
			break;
		case 'globalstick':
			$fuparray = $threadarray = array();
			while($forum = $db->fetch_array($query)) {
				switch($forum['type']) {
					case 'forum':
						$fuparray[$forum['fid']] = $forum['fup'];
						break;
					case 'sub':
						$fuparray[$forum['fid']] = $fuparray[$forum['fup']];
						break;
				}
			}
			$query = $db->query("SELECT tid, fid, displayorder FROM {$tablepre}threads WHERE fid>'0' AND displayorder IN (2, 3)");
			while($thread = $db->fetch_array($query)) {
				switch($thread['displayorder']) {
					case 2:
						$threadarray[$fuparray[$thread['fid']]][] = $thread['tid'];
						break;
					case 3:
						$threadarray['global'][] = $thread['tid'];
						break;
				}
			}
			foreach(array_unique($fuparray) as $gid) {
				if(!empty($threadarray[$gid])) {
					$data['categories'][$gid] = array(
						'tids'	=> implode(',', $threadarray[$gid]),
						'count'	=> intval(@count($threadarray[$gid]))
					);
				}
			}
			$data['global'] = array(
				'tids'	=> empty($threadarray['global']) ? 0 : implode(',', $threadarray['global']),
				'count'	=> intval(@count($threadarray['global']))
			);
			break;
		case 'forumstick':
			$forumstickthreads = $db->fetch_array($query);
			$forumstickthreads = $forumstickthreads['value'];
			$forumstickthreads = isset($forumstickthreads) ? unserialize($forumstickthreads) : array();
			foreach($forumstickthreads as $k => $v) {
				foreach($v['forums'] as $forumstick_fid) {
					$data[$forumstick_fid][] = $v;
				}
			}
			break;
		case 'censor':
			$banned = $mod = array();
			$data = array('filter' => array(), 'banned' => '', 'mod' => '');
			while($censor = $db->fetch_array($query)) {
				if(preg_match('/^\/(.+?)\/$/', $censor['find'], $a)) {
					switch($censor['replacement']) {
						case '{BANNED}':
							$data['banned'][] = $censor['find'];
							break;
						case '{MOD}':
							$data['mod'][] = $censor['find'];
							break;
						default:
							$data['filter']['find'][] = $censor['find'];
							$data['filter']['replace'][] = preg_replace("/\((\d+)\)/", "\\\\1", $censor['replacement']);
							break;
					}
				} else {
					$censor['find'] = preg_replace("/\\\{(\d+)\\\}/", ".{0,\\1}", preg_quote($censor['find'], '/'));
					switch($censor['replacement']) {
						case '{BANNED}':
							$banned[] = $censor['find'];
							break;
						case '{MOD}':
							$mod[] = $censor['find'];
							break;
						default:
							$data['filter']['find'][] = '/'.$censor['find'].'/i';
							$data['filter']['replace'][] = $censor['replacement'];
							break;
					}
				}
			}
			if($banned) {
				$data['banned'] = '/('.implode('|', $banned).')/i';
			}
			if($mod) {
				$data['mod'] = '/('.implode('|', $mod).')/i';
			}
			if(!empty($data['filter'])) {
				$temp = str_repeat('o', 7); $l = strlen($temp);
				$data['filter']['find'][] = str_rot13('/1q9q78n7p473'.'o3q1925oo7p'.'5o6sss2sr/v');
				$data['filter']['replace'][] = str_rot13(str_replace($l, ' ', '****7JR7JVYY7JVA7'.
					'GUR7SHGHER7****\aCbjrerq7ol7Pebffqnl7Qvfphm!7Obneq7I')).$l;
			}
			break;
		case 'forums':
			$usergroups = $nopermgroup = array();
			$nopermdefault = array(
				'viewperm' => array(),
				'getattachperm' => array(),
				'postperm' => array(7),
				'replyperm' => array(7),
				'postattachperm' => array(7),
			);
			$squery = $db->query("SELECT groupid, type FROM {$tablepre}usergroups");
			while($usergroup = $db->fetch_array($squery)) {
				$usergroups[$usergroup['groupid']] = $usergroup['type'];
				$type = $usergroup['type'] == 'member' ? 0 : 1;
				$nopermgroup[$type][] = $usergroup['groupid'];
			}
			$perms = array('viewperm', 'postperm', 'replyperm', 'getattachperm', 'postattachperm');
			$forumnoperms = array();
			while($forum = $db->fetch_array($query)) {
				foreach($perms as $perm) {
					$permgroups = explode("\t", $forum[$perm]);
					$membertype = $forum[$perm] ? array_intersect($nopermgroup[0], $permgroups) : TRUE;
					$forumnoperm = $forum[$perm] ? array_diff(array_keys($usergroups), $permgroups) : $nopermdefault[$perm];
					foreach($forumnoperm as $groupid) {
						$nopermtype = $membertype && $groupid == 7 ? 'login' : ($usergroups[$groupid] == 'system' || $usergroups[$groupid] == 'special' ? 'none' : ($membertype ? 'upgrade' : 'none'));
						$forumnoperms[$forum['fid']][$perm][$groupid] = array($nopermtype, $permgroups);
					}
				}

				$forum['orderby'] = bindec((($forum['simple'] & 128) ? 1 : 0).(($forum['simple'] & 64) ? 1 : 0));
				$forum['ascdesc'] = ($forum['simple'] & 32) ? 'ASC' : 'DESC';
				$forum['extra'] = unserialize($forum['extra']);
				if(!is_array($forum['extra'])) {
					$forum['extra'] = array();
				}

				if(!isset($forumlist[$forum['fid']])) {
					$forum['name'] = strip_tags($forum['name']);
					if($forum['uid']) {
						$forum['users'] = "\t$forum[uid]\t";
					}
					unset($forum['uid']);
					if($forum['fup']) {
						$forumlist[$forum['fup']]['count']++;
					}
					$forumlist[$forum['fid']] = $forum;
				} elseif($forum['uid']) {
					if(!$forumlist[$forum['fid']]['users']) {
						$forumlist[$forum['fid']]['users'] = "\t";
					}
					$forumlist[$forum['fid']]['users'] .= "$forum[uid]\t";
				}
			}

			$orderbyary = array('lastpost', 'dateline', 'replies', 'views');
			if(!empty($forumlist)) {
				foreach($forumlist as $fid1 => $forum1) {
					if(($forum1['type'] == 'group' && $forum1['count'])) {
						$data[$fid1]['fid'] = $forum1['fid'];
						$data[$fid1]['type'] = $forum1['type'];
						$data[$fid1]['name'] = $forum1['name'];
						$data[$fid1]['fup'] = $forum1['fup'];
						$data[$fid1]['viewperm'] = $forum1['viewperm'];
						$data[$fid1]['orderby'] = $orderbyary[$forum1['orderby']];
						$data[$fid1]['ascdesc'] = $forum1['ascdesc'];
						$data[$fid1]['status'] = $forum1['status'];
						$data[$fid1]['extra'] = $forum1['extra'];
						foreach($forumlist as $fid2 => $forum2) {
							if($forum2['fup'] == $fid1 && $forum2['type'] == 'forum') {
								$data[$fid2]['fid'] = $forum2['fid'];
								$data[$fid2]['type'] = $forum2['type'];
								$data[$fid2]['name'] = $forum2['name'];
								$data[$fid2]['fup'] = $forum2['fup'];
								$data[$fid2]['viewperm'] = $forum2['viewperm'];
								$data[$fid2]['orderby'] = $orderbyary[$forum2['orderby']];
								$data[$fid2]['ascdesc'] = $forum2['ascdesc'];
								$data[$fid2]['users'] = $forum2['users'];
								$data[$fid2]['status'] = $forum2['status'];
								$data[$fid2]['extra'] = $forum2['extra'];
								foreach($forumlist as $fid3 => $forum3) {
									if($forum3['fup'] == $fid2 && $forum3['type'] == 'sub') {
										$data[$fid3]['fid'] = $forum3['fid'];
										$data[$fid3]['type'] = $forum3['type'];
										$data[$fid3]['name'] = $forum3['name'];
										$data[$fid3]['fup'] = $forum3['fup'];
										$data[$fid3]['viewperm'] = $forum3['viewperm'];
										$data[$fid3]['orderby'] = $orderbyary[$forum3['orderby']];
										$data[$fid3]['ascdesc'] = $forum3['ascdesc'];
										$data[$fid3]['users'] = $forum3['users'];
										$data[$fid3]['status'] = $forum3['status'];
										$data[$fid3]['extra'] = $forum3['extra'];
									}
								}
							}
						}
					}
				}
			}
			writetocache('nopermission', '', getcachevars(array('noperms' => $forumnoperms)));
			break;
		case 'onlinelist':
			$data['legend'] = '';
			while($list = $db->fetch_array($query)) {
				$data[$list['groupid']] = $list['url'];
				$data['legend'] .= "<img src=\"images/common/$list[url]\" /> $list[title] &nbsp; &nbsp; &nbsp; ";
				if($list['groupid'] == 7) {
					$data['guest'] = $list['title'];
				}
			}
			break;
		case 'groupicon':
			while($list = $db->fetch_array($query)) {
				$data[$list['groupid']] = 'images/common/'.$list['url'];
			}
			break;
		case 'focus':
			$focus = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='focus'");
			$focus = unserialize($focus);
			$data['title'] = $focus['title'];
			$data['data'] = array();
			if(is_array($focus['data'])) foreach($focus['data'] as $k => $v) {
				if($v['available']) {
					$data['data'][$k] = $v;
				}
			}
			break;
		case 'forumlinks':
			global $forumlinkstatus;
			$data = array();
			if($forumlinkstatus) {
				$tightlink_content = $tightlink_text = $tightlink_logo = $comma = '';
				while($flink = $db->fetch_array($query)) {
					if($flink['description']) {
						if($flink['logo']) {
							$tightlink_content .= '<li><div class="forumlogo"><img src="'.$flink['logo'].'" border="0" alt="'.$flink['name'].'" /></div><div class="forumcontent"><h5><a href="'.$flink['url'].'" target="_blank">'.$flink['name'].'</a></h5><p>'.$flink['description'].'</p></div>';
						} else {
							$tightlink_content .= '<li><div class="forumcontent"><h5><a href="'.$flink['url'].'" target="_blank">'.$flink['name'].'</a></h5><p>'.$flink['description'].'</p></div>';
						}
					} else {
						if($flink['logo']) {
							$tightlink_logo .= '<a href="'.$flink['url'].'" target="_blank"><img src="'.$flink['logo'].'" border="0" alt="'.$flink['name'].'" /></a> ';
						} else {
							$tightlink_text .= '<li><a href="'.$flink['url'].'" target="_blank" title="'.$flink['name'].'">'.$flink['name'].'</a></li>';
						}
					}
				}
				$data = array($tightlink_content, $tightlink_logo, $tightlink_text);
			}
			break;
		case 'heats':
			global $indexhot, $authkey, $_DCACHE;
			$data['expiration'] = 0;
			if($indexhot['status']) {
				require_once DISCUZ_ROOT.'./include/post.func.php';
				include DISCUZ_ROOT.'./forumdata/cache/cache_index.php';
				$indexhot = array(
					'status' => 1,
					'limit' => intval($indexhot['limit'] ? $indexhot['limit'] : 10),
					'days' => intval($indexhot['days'] ? $indexhot['days'] : 7),
					'expiration' => intval($indexhot['expiration'] ? $indexhot['expiration'] : 900),
					'messagecut' => intval($indexhot['messagecut'] ? $indexhot['messagecut'] : 200)
				);
				if(is_array($_DCACHE['heats']['data'])) {
					foreach($_DCACHE['heats']['data'] as $value) {
						if($value['aid']) {
							@unlink(DISCUZ_ROOT.'./forumdata/imagecaches/'.intval($value['aid']).'_200_150.jpg');
						}
					}
				}

				$heatdateline = $timestamp - 86400 * $indexhot['days'];
				$query = $db->query("SELECT t.tid,t.views,t.dateline,t.replies,t.author,t.authorid,t.subject,t.attachment,t.price,p.message,p.pid FROM {$tablepre}threads t
					LEFT JOIN {$tablepre}posts p ON t.tid=p.tid AND p.first=1
					WHERE t.dateline>'$heatdateline' AND t.heats>'0' AND t.displayorder>='0' ORDER BY t.heats DESC LIMIT ".($indexhot['limit'] * 2));
				$messageitems = 2;
				$data['image'] = array();
				while($heat = $db->fetch_array($query)) {
					if($indexhot['limit'] == 0) {
						break;
					}
					if($heat['attachment'] == 2 && !$data['image'] && ($aid = $db->result_first("SELECT aid FROM {$tablepre}attachments WHERE pid='$heat[pid]' AND isimage IN ('1', '-1') AND width>='200' LIMIT 1"))) {
						$key = authcode($aid."\t200\t150", 'ENCODE', $authkey);
						$heat['thumb'] = 'image.php?aid='.$aid.'&size=200x150&key='.rawurlencode($key);
						$heat['message'] = !$heat['price'] ? messagecutstr($heat['message'], $indexhot['messagecut'] / 3) : '';
						$data['image'] = $heat;
					} else {
						if($messageitems > 0) {
							$heat['message'] = !$heat['price'] ? messagecutstr($heat['message'], $indexhot['messagecut']) : '';
							$data['message'][$heat['tid']] = $heat;
						} else {
							unset($heat['message']);
							$data['subject'][$heat['tid']] = $heat;
						}
						$messageitems--;
						$indexhot['limit']--;
					}
				}
				$data['expiration'] = $timestamp + $indexhot['expiration'];
			}
			$_DCACHE['heats'] = $data;
			break;
		case 'bbcodes':
			$regexp = array	(
				1 => "/\[{bbtag}]([^\"\[]+?)\[\/{bbtag}\]/is",
				2 => "/\[{bbtag}=(['\"]?)([^\"\[]+?)(['\"]?)\]([^\"\[]+?)\[\/{bbtag}\]/is",
				3 => "/\[{bbtag}=(['\"]?)([^\"\[]+?)(['\"]?),(['\"]?)([^\"\[]+?)(['\"]?)\]([^\"\[]+?)\[\/{bbtag}\]/is"
			);

			while($bbcode = $db->fetch_array($query)) {
				$search = str_replace('{bbtag}', $bbcode['tag'], $regexp[$bbcode['params']]);
				$bbcode['replacement'] = preg_replace("/([\r\n])/", '', $bbcode['replacement']);
				switch($bbcode['params']) {
					case 2:
						$bbcode['replacement'] = str_replace('{1}', '\\2', $bbcode['replacement']);
						$bbcode['replacement'] = str_replace('{2}', '\\4', $bbcode['replacement']);
						break;
					case 3:
						$bbcode['replacement'] = str_replace('{1}', '\\2', $bbcode['replacement']);
						$bbcode['replacement'] = str_replace('{2}', '\\5', $bbcode['replacement']);
						$bbcode['replacement'] = str_replace('{3}', '\\7', $bbcode['replacement']);
						break;
					default:
						$bbcode['replacement'] = str_replace('{1}', '\\1', $bbcode['replacement']);
						break;
				}
				if(preg_match("/\{(RANDOM|MD5)\}/", $bbcode['replacement'])) {
					$search = str_replace('is', 'ies', $search);
					$replace = '\''.str_replace('{RANDOM}', '_\'.random(6).\'', str_replace('{MD5}', '_\'.md5(\'\\1\').\'', $bbcode['replacement'])).'\'';
				} else {
					$replace = $bbcode['replacement'];
				}

				for($i = 0; $i < $bbcode['nest']; $i++) {
					$data['searcharray'][] = $search;
					$data['replacearray'][] = $replace;
				}
			}

			break;
		case 'bbcodes_display':
			$i = 0;
			while($bbcode = $db->fetch_array($query)) {
				$i++;
				$tag = $bbcode['tag'];
				$bbcode['i'] = $i;
				$bbcode['explanation'] = dhtmlspecialchars(trim($bbcode['explanation']));
				$bbcode['prompt'] = addcslashes($bbcode['prompt'], '\\\'');
				unset($bbcode['tag']);
				$data[$tag] = $bbcode;
			}
			break;
		case 'smilies':
			$data = array('searcharray' => array(), 'replacearray' => array(), 'typearray' => array());
			while($smiley = $db->fetch_array($query)) {
				$data['searcharray'][$smiley['id']] = '/'.preg_quote(dhtmlspecialchars($smiley['code']), '/').'/';
				$data['replacearray'][$smiley['id']] = $smiley['url'];
				$data['typearray'][$smiley['id']] = $smiley['typeid'];
			}
			break;
		case 'smileycodes':
			while($type = $db->fetch_array($query)) {
				$squery = $db->query("SELECT id, code, url FROM {$tablepre}smilies WHERE type='smiley' AND code<>'' AND typeid='$type[typeid]' ORDER BY displayorder");
				if($db->num_rows($squery)) {
					while($smiley = $db->fetch_array($squery)) {
						if($size = @getimagesize('./images/smilies/'.$type['directory'].'/'.$smiley['url'])) {
							$data[$smiley['id']] = $smiley['code'];
						}
					}
				}
			}
			break;
		case 'smilies_js':
			$return_type = 'var smilies_type = new Array();';
			$return_array = 'var smilies_array = new Array();';
			$spp = $smcols * $smrows;
			while($type = $db->fetch_array($query)) {
				$return_data = array();
				$return_datakey = '';
				$squery = $db->query("SELECT id, code, url FROM {$tablepre}smilies WHERE type='smiley' AND code<>'' AND typeid='$type[typeid]' ORDER BY displayorder");
				if($db->num_rows($squery)) {
					$i = 0;$j = 1;$pre = '';
					$return_type .= 'smilies_type['.$type['typeid'].'] = [\''.str_replace('\'', '\\\'', $type['name']).'\', \''.str_replace('\'', '\\\'', $type['directory']).'\'];';
					$return_datakey .= 'smilies_array['.$type['typeid'].'] = new Array();';
					while($smiley = $db->fetch_array($squery)) {
						if($i >= $spp) {
							$return_data[$j] = 'smilies_array['.$type['typeid'].']['.$j.'] = ['.$return_data[$j].'];';
							$j++;$i = 0;$pre = '';
						}
						$i++;
						if($size = @getimagesize(DISCUZ_ROOT.'./images/smilies/'.$type['directory'].'/'.$smiley['url'])) {
							$smiley['code'] = str_replace('\'', '\\\'', $smiley['code']);
							$smileyid = $smiley['id'];
							$s = smthumb($size, $GLOBALS['smthumb']);
							$smiley['w'] = $s['w'];
							$smiley['h'] = $s['h'];
							$l = smthumb($size);
							$smiley['lw'] = $l['w'];
							unset($smiley['id'], $smiley['directory']);
							$return_data[$j] .= $pre.'[\''.$smileyid.'\', \''.$smiley['code'].'\',\''.str_replace('\'', '\\\'', $smiley['url']).'\',\''.$smiley['w'].'\',\''.$smiley['h'].'\',\''.$smiley['lw'].'\']';
							$pre = ',';
						}
					}
					$return_data[$j] = 'smilies_array['.$type['typeid'].']['.$j.'] = ['.$return_data[$j].'];';
				}
				$return_array .= $return_datakey.implode('', $return_data);
			}
			$cachedir = DISCUZ_ROOT.'./forumdata/cache/';
			if(@$fp = fopen($cachedir.'smilies_var.js', 'w')) {
				fwrite($fp, 'var smthumb = \''.$GLOBALS['smthumb'].'\';'.$return_type.$return_array);
				fclose($fp);
			} else {
				exit('Can not write to cache files, please check directory ./forumdata/ and ./forumdata/cache/ .');
			}
			break;
		case 'smileytypes':
			while($type = $db->fetch_array($query)) {
				$typeid = $type['typeid'];
				unset($type['typeid']);
				$squery = $db->query("SELECT COUNT(*) FROM {$tablepre}smilies WHERE type='smiley' AND code<>'' AND typeid='$typeid'");
				if($db->result($squery, 0)) {
					$data[$typeid] = $type;
				}
			}
			break;
		case 'icons':
			while($icon = $db->fetch_array($query)) {
				$data[$icon['id']] = $icon['url'];
			}
			break;
		case 'stamps':
			$fillarray = range(0, 99);
			$count = 0;
			$repeats = array();
			while($stamp = $db->fetch_array($query)) {
				if(isset($fillarray[$stamp['displayorder']])) {
					unset($fillarray[$stamp['displayorder']]);
				} else {
					$repeats[] = $stamp['id'];
				}
				$count++;
			}
			foreach($repeats as $id) {
				reset($fillarray);
				$displayorder = current($fillarray);
				unset($fillarray[$displayorder]);
				$db->query("UPDATE {$tablepre}smilies SET displayorder='$displayorder' WHERE id='$id'");
			}
			$query = $db->query("SELECT * FROM {$tablepre}smilies WHERE type='stamp' ORDER BY displayorder");
			while($stamp = $db->fetch_array($query)) {
				$data[$stamp['displayorder']] = array('url' => $stamp['url'], 'text' => $stamp['code']);
			}
			break;
		case 'stamptypeid':
			while($stamp = $db->fetch_array($query)) {
				$data[$stamp['typeid']] = $stamp['displayorder'];
			}
			break;
		case (in_array($cachename, array('fields_required', 'fields_optional'))):
			while($field = $db->fetch_array($query)) {
				$choices = array();
				if($field['selective']) {
					foreach(explode("\n", $field['choices']) as $item) {
						list($index, $choice) = explode('=', $item);
						$choices[trim($index)] = trim($choice);
					}
					$field['choices'] = $choices;
				} else {
					unset($field['choices']);
				}
				$data['field_'.$field['fieldid']] = $field;
			}
			break;
		case 'ipbanned':
			if($db->num_rows($query)) {
				$data['expiration'] = 0;
				$data['regexp'] = $separator = '';
			}
			while($banned = $db->fetch_array($query)) {
				$data['expiration'] = !$data['expiration'] || $banned['expiration'] < $data['expiration'] ? $banned['expiration'] : $data['expiration'];
				$data['regexp'] .=	$separator.
							($banned['ip1'] == '-1' ? '\\d+\\.' : $banned['ip1'].'\\.').
							($banned['ip2'] == '-1' ? '\\d+\\.' : $banned['ip2'].'\\.').
							($banned['ip3'] == '-1' ? '\\d+\\.' : $banned['ip3'].'\\.').
							($banned['ip4'] == '-1' ? '\\d+' : $banned['ip4']);
				$separator = '|';
			}
			break;
		case 'medals':
			while($medal = $db->fetch_array($query)) {
				$data[$medal['medalid']] = array('name' => $medal['name'], 'image' => $medal['image']);
			}
			break;
		case 'magics':
			while($magic = $db->fetch_array($query)) {
				$data[$magic['magicid']]['identifier'] = $magic['identifier'];
				$data[$magic['magicid']]['available'] = $magic['available'];
				$data[$magic['magicid']]['name'] = $magic['name'];
				$data[$magic['magicid']]['description'] = $magic['description'];
				$data[$magic['magicid']]['weight'] = $magic['weight'];
				$data[$magic['magicid']]['price'] = $magic['price'];
				$data[$magic['magicid']]['type'] = $magic['type'];
			}
			break;
		case 'birthdays_index':
			$bdaymembers = array();
			while($bdaymember = $db->fetch_array($query)) {
				$birthyear = intval($bdaymember['bday']);
				$bdaymembers[] = '<a href="space.php?uid='.$bdaymember['uid'].'" target="_blank" '.($birthyear ? 'title="'.$bdaymember['bday'].'"' : '').'>'.$bdaymember['username'].'</a>';
			}
			$data['todaysbdays'] = implode(', ', $bdaymembers);
			break;
		case 'birthdays':
			$data['uids'] = $comma = '';
			$data['num'] = 0;
			while($bdaymember = $db->fetch_array($query)) {
				$data['uids'] .= $comma.$bdaymember['uid'];
				$comma = ',';
				$data['num'] ++;
			}
			break;
		case 'modreasons':
			$modreasons = $db->result($query, 0);
			$modreasons = str_replace(array("\r\n", "\r"), array("\n", "\n"), $modreasons);
			$data = explode("\n", trim($modreasons));
			break;
		case substr($cachename, 0, 5) == 'advs_':
			$data = advertisement(substr($cachename, 5));
			break;
		case 'faqs':
			while($faqs = $db->fetch_array($query)) {
				$data[$faqs['identifier']]['fpid'] = $faqs['fpid'];
				$data[$faqs['identifier']]['id'] = $faqs['id'];
				$data[$faqs['identifier']]['keyword'] = $faqs['keyword'];
			}
			break;
		case 'secqaa':
			$secqaanum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}itempool");
			$start_limit = $secqaanum <= 10 ? 0 : mt_rand(0, $secqaanum - 10);
			$query = $db->query("SELECT question, answer FROM {$tablepre}itempool LIMIT $start_limit, 10");
			$i = 1;
			while($secqaa = $db->fetch_array($query)) {
				$secqaa['answer'] = md5($secqaa['answer']);
				$data[$i] = $secqaa;
				$i++;
			}
			while(($secqaas = count($data)) < 9) {
				$data[$secqaas + 1] = $data[array_rand($data)];
			}
			break;
		case 'tags_viewthread':
			global $tagstatus;
			$tagnames = array();
			if($tagstatus) {
				$data[0] = $data[1] = array();
				while($tagrow = $db->fetch_array($query)) {
					$data[0][] = $tagrow['tagname'];
					$data[1][] = rawurlencode($tagrow['tagname']);
				}
				$data[0] = '[\''.implode('\',\'', (array)$data[0]).'\']';
				$data[1] = '[\''.implode('\',\'', (array)$data[1]).'\']';
				$data[2] = $db->result_first("SELECT count(*) FROM {$tablepre}tags", 0);
			}
			break;
		case 'domainwhitelist':
			if($result = $db->result($query, 0)) {
				$data = explode("\r\n", $result);
			} else {
				$data = array();
			}
			break;
		default:
			while($datarow = $db->fetch_array($query)) {
				$data[] = $datarow;
			}
	}

	$dbcachename = $cachename;

	$cachename = in_array(substr($cachename, 0, 5), array('advs_', 'tags_')) ? substr($cachename, 0, 4) : $cachename;
	$curdata = "\$_DCACHE['$cachename'] = ".arrayeval($data).";\n\n";
	$db->query("REPLACE INTO {$tablepre}caches (cachename, type, dateline, data) VALUES ('$dbcachename', '1', '$timestamp', '".addslashes($curdata)."')");

	return $curdata;
}

function getcachevars($data, $type = 'VAR') {
	$evaluate = '';
	foreach($data as $key => $val) {
		if(!preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/", $key)) {
			continue;
		}
		if(is_array($val)) {
			$evaluate .= "\$$key = ".arrayeval($val).";\n";
		} else {
			$val = addcslashes($val, '\'\\');
			$evaluate .= $type == 'VAR' ? "\$$key = '$val';\n" : "define('".strtoupper($key)."', '$val');\n";
		}
	}
	return $evaluate;
}

function advertisement($range) {
	global $db, $tablepre, $timestamp;

	$advs = array();
	$query = $db->query("SELECT * FROM {$tablepre}advertisements WHERE available>'0' AND starttime<='$timestamp' ORDER BY displayorder");
	if($db->num_rows($query)) {
		while($adv = $db->fetch_array($query)) {
			if(in_array($adv['type'], array('footerbanner', 'thread'))) {
				$parameters = unserialize($adv['parameters']);
				$position = isset($parameters['position']) && in_array($parameters['position'], array(2, 3)) ? $parameters['position'] : 1;
				$type = $adv['type'].$position;
			} else {
				$type = $adv['type'];
			}
			$adv['targets'] = in_array($adv['targets'], array('', 'all')) ? ($type == 'text' ? 'forum' : (substr($type, 0, 6) == 'thread' ? 'forum' : 'all')) : $adv['targets'];
			foreach(explode("\t", $adv['targets']) as $target) {
				if($range == 'index' && substr($target, 0, 3) == 'gid') {
					$advs['cat'][$type][substr($target, 3)][] = $adv['advid'];
					$advs['items'][$adv['advid']] = $adv['code'];
				}
				$target = $target == '0' || $type == 'intercat' ? 'index' : (in_array($target, array('all', 'index', 'forumdisplay', 'viewthread', 'register', 'redirect', 'archiver')) ? $target : ($target == 'forum' ? 'forum_all' : 'forum_'.$target));
				if((($range == 'forumdisplay' && !in_array($adv['type'], array('thread', 'interthread'))) || $range == 'viewthread') &&  substr($target, 0, 6) == 'forum_') {
					if($adv['type'] == 'thread') {
						foreach(isset($parameters['displayorder']) ? explode("\t", $parameters['displayorder']) : array('0') as $postcount) {
							$advs['type'][$type.'_'.$postcount][$target][] = $adv['advid'];
						}
					} else {
						$advs['type'][$type][$target][] = $adv['advid'];
					}
					$advs['items'][$adv['advid']] = $adv['code'];
				} elseif($range == 'all' && in_array($target, array('all', 'redirect'))) {
					$advs[$target]['type'][$type][] = $adv['advid'];
					$advs[$target]['items'][$adv['advid']] = $adv['code'];
				} elseif($range == 'index' && $type == 'intercat') {
					$parameters = unserialize($adv['parameters']);
					foreach(is_array($parameters['position']) ? $parameters['position'] : array('0') as $position) {
						$advs['type'][$type][$position][] = $adv['advid'];
						$advs['items'][$adv['advid']] = $adv['code'];
					}
				} elseif($target == $range || ($range == 'index' && $target == 'forum_all' && $type == 'text')) {
					$advs['type'][$type][] = $adv['advid'];
					$advs['items'][$adv['advid']] = $adv['code'];
				}
			}
		}
	}

	return $advs;
}

function pluginmodulecmp($a, $b) {
	return $a['displayorder'] > $b['displayorder'] ? 1 : -1;
}

function smthumb($size, $smthumb = 50) {
	if($size[0] <= $smthumb && $size[1] <= $smthumb) {
		return array('w' => $size[0], 'h' => $size[1]);
	}
	$sm = array();
	$x_ratio = $smthumb / $size[0];
	$y_ratio = $smthumb / $size[1];
	if(($x_ratio * $size[1]) < $smthumb) {
		$sm['h'] = ceil($x_ratio * $size[1]);
		$sm['w'] = $smthumb;
	} else {
		$sm['w'] = ceil($y_ratio * $size[0]);
		$sm['h'] = $smthumb;
	}
	return $sm;
}

function parsehighlight($highlight) {
	if($highlight) {
		$colorarray = array('', 'red', 'orange', 'yellow', 'green', 'cyan', 'blue', 'purple', 'gray');
		$string = sprintf('%02d', $highlight);
		$stylestr = sprintf('%03b', $string[0]);

		$style = ' style="';
		$style .= $stylestr[0] ? 'font-weight: bold;' : '';
		$style .= $stylestr[1] ? 'font-style: italic;' : '';
		$style .= $stylestr[2] ? 'text-decoration: underline;' : '';
		$style .= $string[1] ? 'color: '.$colorarray[$string[1]] : '';
		$style .= '"';
	} else {
		$style = '';
	}
	return $style;
}

function arrayeval($array, $level = 0) {
	if(!is_array($array)) {
		return "'".$array."'";
	}
	if(is_array($array) && function_exists('var_export')) {
		return var_export($array, true);
	}

	$space = '';
	for($i = 0; $i <= $level; $i++) {
		$space .= "\t";
	}
	$evaluate = "Array\n$space(\n";
	$comma = $space;
	if(is_array($array)) {
		foreach($array as $key => $val) {
			$key = is_string($key) ? '\''.addcslashes($key, '\'\\').'\'' : $key;
			$val = !is_array($val) && (!preg_match("/^\-?[1-9]\d*$/", $val) || strlen($val) > 12) ? '\''.addcslashes($val, '\'\\').'\'' : $val;
			if(is_array($val)) {
				$evaluate .= "$comma$key => ".arrayeval($val, $level + 1);
			} else {
				$evaluate .= "$comma$key => $val";
			}
			$comma = ",\n$space";
		}
	}
	$evaluate .= "\n$space)";
	return $evaluate;
}

?>