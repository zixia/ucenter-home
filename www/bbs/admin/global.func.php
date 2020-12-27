<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: global.func.php 21350 2010-01-06 12:23:28Z zhaoxiongfei $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

@set_time_limit(0);

function istpldir($dir) {
	return is_dir(DISCUZ_ROOT.'./'.$dir) && !in_array(substr($dir, -1, 1), array('/', '\\')) &&
		 strpos(realpath(DISCUZ_ROOT.'./'.$dir), realpath(DISCUZ_ROOT.'./templates')) === 0;
}

function isplugindir($dir) {
	return !$dir || (!preg_match("/(\.\.|[\\\\]+$)/", $dir) && substr($dir, -1) =='/');
}

function ispluginkey($key) {
	return preg_match("/^[a-z]+[a-z0-9_]*$/i", $key);
}

function dir_writeable($dir) {
	if(!is_dir($dir)) {
		@mkdir($dir, 0777);
	}
	if(is_dir($dir)) {
		if($fp = @fopen("$dir/test.txt", 'w')) {
			@fclose($fp);
			@unlink("$dir/test.txt");
			$writeable = 1;
		} else {
			$writeable = 0;
		}
	}
	return $writeable;
}

function filemtimesort($a, $b) {
	if($a['filemtime'] == $b['filemtime']) {
		return 0;
	}
	return ($a['filemtime'] > $b['filemtime']) ? 1 : -1;
}

function checkpermission($action, $break = 1) {
	if(!isset($GLOBALS['admincp'])) {
		cpmsg('action_access_noexists', '', 'error');
	} elseif($break && !$GLOBALS['admincp'][$action]) {
		cpmsg('action_noaccess_config', '', 'error');
	} else {
		return $GLOBALS['admincp'][$action];
	}
}

function bbsinformation() {

	global $db, $timestamp, $tablepre, $charset, $bbname, $_SERVER, $siteuniqueid, $save_mastermobile, $msn;
	$update = array('uniqueid' => $siteuniqueid, 'version' => DISCUZ_VERSION, 'release' => DISCUZ_RELEASE, 'php' => PHP_VERSION, 'mysql' => $db->version(), 'charset' => $charset, 'bbname' => $bbname, 'mastermobile' => $save_mastermobile);

	$updatetime = @filemtime(DISCUZ_ROOT.'./forumdata/updatetime.lock');
	if(empty($updatetime) || ($timestamp - $updatetime > 3600 * 4)) {
		@touch(DISCUZ_ROOT.'./forumdata/updatetime.lock');
		$update['members'] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members");
		$update['threads'] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads");
		$update['posts'] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts");
		$query = $db->query("SELECT special, count(*) AS spcount FROM {$tablepre}threads GROUP BY special");
		while($thread = $db->fetch_array($query)) {
			$thread['special'] = intval($thread['special']);
			$update['spt_'.$thread['special']] = $thread['spcount'];
		}
		if($msn['on'] && $msn['domain']) {
			$update['msn_domain'] = $msn['domain'];
		}
	}

	$data = '';
	foreach($update as $key => $value) {
		$data .= $key.'='.rawurlencode($value).'&';
	}

	return 'update='.rawurlencode(base64_encode($data)).'&md5hash='.substr(md5($_SERVER['HTTP_USER_AGENT'].implode('', $update).$timestamp), 8, 8).'&timestamp='.$timestamp;
}

function isfounder($user = '') {
	$user = empty($user) ? array('uid' => $GLOBALS['discuz_uid'], 'adminid' => $GLOBALS['adminid'], 'username' => $GLOBALS['discuz_userss']) : $user;
	$founders = str_replace(' ', '', $GLOBALS['forumfounders']);
	if($user['adminid'] <> 1) {
		return FALSE;
	} elseif(empty($founders)) {
		return TRUE;
	} elseif(strexists(",$founders,", ",$user[uid],")) {
		return TRUE;
	} elseif(!is_numeric($user['username']) && strexists(",$founders,", ",$user[username],")) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function lang($name, $force = true) {
	global $lang;
	return isset($lang[$name]) ? $lang[$name] : ($force ? $name : '');
}

function admincustom($title, $url, $sort = 0) {
	global $db, $tablepre, $discuz_uid, $timestamp, $BASESCRIPT;
	$url = $BASESCRIPT.'?'.$url;
	$id = $db->result_first("SELECT id FROM {$tablepre}admincustom WHERE uid='$discuz_uid' AND sort='$sort' AND url='$url'");
	if($id) {
		$db->query("UPDATE {$tablepre}admincustom SET title='$title', clicks=clicks+1, dateline='$timestamp' WHERE id='$id'");
	} else {
		$db->query("INSERT INTO {$tablepre}admincustom (title, url, sort, uid, dateline) VALUES ('$title', '$url', '$sort', '$discuz_uid', '$timestamp')");
		$id = $db->insert_id();
	}
	return $id;
}

function cpurl($type = 'parameter', $filters = array('sid', 'frames')) {
	parse_str($_SERVER['QUERY_STRING'], $getarray);
	$extra = $and = '';
	foreach($getarray as $key => $value) {
		if(!in_array($key, $filters)) {
			@$extra .= $and.$key.($type == 'parameter' ? '%3D' : '=').rawurlencode($value);
			$and = $type == 'parameter' ? '%26' : '&';
		}
	}
	return $extra;
}


function showheader($key, $url) {
	echo '<li><em><a href="javascript:;" id="header_'.$key.'" hidefocus="true" onclick="toggleMenu(\''.$key.'\', \''.$url.'\');">'.lang('header_'.$key).'</a></em></li>';
}

function shownav($header = '', $menu = '', $nav = '') {
	global $action, $operation, $BASESCRIPT;

	$title = 'cplog_'.$action.($operation ? '_'.$operation : '');
	if(in_array($action, array('home', 'custommenu'))) {
		$customtitle = '';
	} elseif(lang($title, false)) {
		$customtitle = $title;
	} elseif(lang('nav_'.($header ? $header : 'index'), false)) {
		$customtitle = 'nav_'.$header;
	} else {
		$customtitle = rawurlencode($nav ? $nav : ($menu ? $menu : ''));
	}

	echo '<script type="text/JavaScript">if(parent.$(\'admincpnav\')) parent.$(\'admincpnav\').innerHTML=\''.lang('nav_'.($header ? $header : 'index')).
		($menu ? '&nbsp;&raquo;&nbsp;'.lang($menu) : '').
		($nav ? '&nbsp;&raquo;&nbsp;'.lang($nav) : '').'\';'.
		'if(parent.$(\'add2custom\')) parent.$(\'add2custom\').innerHTML='.($customtitle ? '\'<a href="'.$BASESCRIPT.'?action=misc&operation=custommenu&do=add&title='.$customtitle.'&url='.cpurl().'" target="main"><img src="images/admincp/btn_add2menu.gif" title="'.lang('custommenu_add').'" width="19" height="18" /></a>\';' : '\'\';').
		($customtitle ? 'if(parent.$(\'custombar_add\')) parent.$(\'custombar_add\').innerHTML='.($customtitle ? '\'<span onclick="ajaxget(\\\''.$BASESCRIPT.'?action=misc&operation=custombar&title='.$customtitle.'&url='.cpurl().'\\\', \\\'custombar\\\', \\\'\\\', \\\'\\\', \\\'\\\', function () { top.custombar_resize();});" title="'.lang('custombar_add_tips').'" />&nbsp;&nbsp;&nbsp;&nbsp;'.lang('custombar_add').'</span>\';' : '\'\';') : '').
		'top.custombar_resize();'.
	'</script>';
}

function showmenu($key, $menus) {
	global $BASESCRIPT;
	echo '<ul id="menu_'.$key.'" style="display: none">';
	if(is_array($menus)) {
		foreach($menus as $menu) {
			if($menu[0] && $menu[1]) {
				echo '<li><a href="'.(substr($menu[1], 0, 4) == 'http' ? $menu[1] : $BASESCRIPT.'?action='.$menu[1]).'" hidefocus="true" target="'.($menu[2] ? $menu[2] : 'main').'"'.($menu[3] ? $menu[3] : '').'>'.lang($menu[0]).'</a></li>';
			}
		}
	}
	echo '</ul>';
}

function cpmsg($message, $url = '', $type = '', $extra = '', $halt = TRUE) {
	extract($GLOBALS, EXTR_SKIP);
	include language('admincp.msg');
	$vars = explode(':', $message);
	if(count($vars) == 2 && isset($scriptlang[$vars[0]][$vars[1]])) {
		@eval("\$message = \"".str_replace('"', '\"', $scriptlang[$vars[0]][$vars[1]])."\";");
	} else {
		@eval("\$message = \"".(isset($msglang[$message]) ? $msglang[$message] : $message)."\";");
	}

	switch($type) {
		case 'succeed': $classname = 'infotitle2';break;
		case 'error': $classname = 'infotitle3';break;
		case 'loading': $classname = 'infotitle1';break;
		default: $classname = 'marginbot normal';break;

	}
	$message = "<h4 class=\"$classname\">$message</h4>";
	$url .= !empty($scrolltop) ? '&scrolltop='.intval($scrolltop) : '';

	if($type == 'form') {
		$message = "<form method=\"post\" action=\"$url\"><input type=\"hidden\" name=\"formhash\" value=\"".FORMHASH."\">".
			"<br />$message$extra<br />".
			"<p class=\"margintop\"><input type=\"submit\" class=\"btn\" name=\"confirmed\" value=\"$lang[ok]\"> &nbsp; \n".
			"<input type=\"button\" class=\"btn\" value=\"$lang[cancel]\" onClick=\"history.go(-1);\"></p></form><br />";
	} elseif($type == 'loadingform') {
		$message = "<form method=\"post\" action=\"$url\" id=\"loadingform\"><input type=\"hidden\" name=\"formhash\" value=\"".FORMHASH."\"><br />$message$extra<img src=\"images/admincp/ajax_loader.gif\" class=\"marginbot\" /><br />".
			'<p class="marginbot"><a href="###" onclick="$(\'loadingform\').submit();return false;" class="lightlink">'.lang('message_redirect').'</a></p></form><br /><script type="text/JavaScript">setTimeout("$(\'loadingform\').submit();", 2000);</script>';
	} else {
		$message .= $extra.($type == 'loading' ? '<img src="images/admincp/ajax_loader.gif" class="marginbot" />' : '');
		if($url) {
			if($type == 'button') {
				$message = "<br />$message<br /><p class=\"margintop\"><input type=\"submit\" class=\"btn\" name=\"submit\" value=\"$lang[start]\" onclick=\"location.href='$url'\" />";
			} else {
				$message .= '<p class="marginbot"><a href="'.$url.'" class="lightlink">'.lang('message_redirect').'</a></p>';
				$url = transsid($url);
				$message .= "<script type=\"text/JavaScript\">setTimeout(\"redirect('$url');\", 2000);</script>";
			}
		} elseif(strpos($message, $lang['return'])) {
			$message .= '<p class="marginbot"><a href="javascript:history.go(-1);" class="lightlink">'.lang('message_return').'</a></p>';
		}
	}

	if($halt) {
		echo '<h3>'.lang('discuz_message').'</h3><div class="infobox">'.$message.'</div>';
		cpfooter();
		dexit();
	} else {
		echo '<div class="infobox">'.$message.'</div>';
	}
}

function cpheader() {
	global  $charset, $frame, $BASESCRIPT;

	if(!defined('DISCUZ_CP_HEADER_OUTPUT')) {
		define('DISCUZ_CP_HEADER_OUTPUT', true);
	} else {
		return true;
	}

	$IMGDIR = IMGDIR;
	$STYLEID = STYLEID;
	$VERHASH = VERHASH;
	$frame = $frame != 'no' ? 1 : 0;
	echo <<< EOT

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=$charset">
<meta http-equiv="x-ua-compatible" content="ie=7" />
<link href="./images/admincp/admincp.css" rel="stylesheet" type="text/css" />
</head>
<body>
<script type="text/JavaScript">
var admincpfilename = '$BASESCRIPT', IMGDIR = '$IMGDIR', STYLEID = '$STYLEID', VERHASH = '$VERHASH', IN_ADMINCP = true, ISFRAME = $frame;
</script>
<script src="include/js/common.js" type="text/javascript"></script>
<script src="images/admincp/admincp.js" type="text/javascript"></script>
<script type="text/javascript">
if(ISFRAME && !parent.document.getElementById('leftmenu')) {
	redirect(admincpfilename + '?frames=yes&' + document.URL.substr(document.URL.indexOf(admincpfilename) + 12));
}
</script>
<div id="append_parent"></div>
<div class="container" id="cpcontainer">
EOT;

}

function showsubmenu($title, $menus = array(), $right = '') {
	global $BASESCRIPT;
	if(empty($menus)) {
		$s = '<div class="itemtitle">'.$right.'<h3>'.lang($title).'</h3></div>';
	} elseif(is_array($menus)) {
		$s = '<div class="itemtitle">'.$right.'<h3>'.lang($title).'</h3>';
		if(is_array($menus)) {
			$s .= '<ul class="tab1">';
			foreach($menus as $k => $menu) {
				if(is_array($menu[0])) {
					$s .= '<li id="addjs'.$k.'" class="'.($menu[2] ? ' current' : 'hasdropmenu').'" onmouseover="dropmenu(this);"><a href="#"><span>'.lang($menu[0]['menu']).'<em>&nbsp;&nbsp;</em></span></a><div id="addjs'.$k.'child" class="dropmenu" style="display:none;">';
					if(is_array($menu[0]['submenu'])) {
						foreach($menu[0]['submenu'] as $submenu) {
							$s .= '<a href="'.$BASESCRIPT.'?action='.$submenu[1].'">'.lang($submenu[0]).'</a>';
						}
					}
					$s .= '</div></li>';
				} else {
					$s .= '<li'.($menu[2] ? ' class="current"' : '').'><a href="'.$BASESCRIPT.'?action='.$menu[1].'"'.($menu[3] ? ' target="_blank"' : '').'><span>'.lang($menu[0]).'</span></a></li>';
				}
			}
			$s .= '</ul>';
		}
		$s .= '</div>';
	}
	echo !empty($menus) ? '<div class="floattop">'.$s.'</div><div class="floattopempty"></div>' : $s;
}

function showsubmenusteps($title, $menus = array()) {
	$s = '<div class="itemtitle">'.($title ? '<h3>'.lang($title).'</h3>' : '');
	if(is_array($menus)) {
		$s .= '<ul class="stepstat">';
			$i = 0;
		foreach($menus as $menu) {
			$i++;
			$s .= '<li'.($menu[1] ? ' class="current"' : '').' id="step'.$i.'">'.$i.'.'.lang($menu[0]).'</li>';
		}
		$s .= '</ul>';
	}
	$s .= '</div>';
	echo $s;
}

function showsubmenuanchors($title, $menus = array(), $right = '') {
	global $BASESCRIPT;
	if(!$title || !$menus || !is_array($menus)) {
		return;
	}
	echo <<<EOT
<script type="text/JavaScript">var currentAnchor = '$GLOBALS[anchor]';</script>
EOT;
	$s = '<div class="itemtitle">'.$right.'<h3>'.lang($title).'</h3>';
	$s .= '<ul class="tab1" id="submenu">';
	foreach($menus as $menu) {
		if($menu && is_array($menu)) {
			$s .= '<li'.(!$menu[3] ? ' id="nav_'.$menu[1].'" onclick="showanchor(this)"' : '').($menu[2] ? ' class="current"' : '').'><a href="'.($menu[3] ? $BASESCRIPT.'?action='.$menu[1] : '#').'"><span>'.lang($menu[0]).'</span></a></li>';
		}
	}
	$s .= '</ul>';
	$s .= '</div>';
	echo !empty($menus) ? '<div class="floattop">'.$s.'</div><div class="floattopempty"></div>' : $s;
	echo '<script type="text/JavaScript">'.
		'if(parent.$(\'custombar_add\')) parent.$(\'custombar_add\').innerHTML=\'<span onclick="ajaxget(\\\''.$BASESCRIPT.'?action=misc&operation=custombar&title='.rawurlencode($title).'&url='.cpurl().'\\\', \\\'custombar\\\', \\\'\\\', \\\'\\\', \\\'\\\', function () { top.custombar_resize();});doane(event);" title="'.lang('custombar_add_tips').'" />&nbsp;&nbsp;&nbsp;&nbsp;'.lang('custombar_add').'</span>\';'.
		'top.custombar_resize();'.
	'</script>';
}

function showtips($tips, $id = 'tips', $display = TRUE) {
	extract($GLOBALS, EXTR_SKIP);
	if(lang($tips, false)) {
		eval('$tips = "'.str_replace('"', '\\"', $lang[$tips]).'";');
	}
	$tmp = explode('</li><li>', substr($tips, 4, -5));
	if(count($tmp) > 4) {
		$tips = '<li>'.$tmp[0].'</li><li>'.$tmp[1].'</li><li id="'.$id.'_more" style="border: none; background: none; margin-bottom: 6px;"><a href="###" onclick="var tiplis = $(\''.$id.'lis\').getElementsByTagName(\'li\');for(var i = 0; i < tiplis.length; i++){tiplis[i].style.display=\'\'}$(\''.$id.'_more\').style.display=\'none\';">'.lang('tips_all').'...</a></li>';
		foreach($tmp AS $k => $v) {
			if($k > 1) {
				$tips .= '<li style="display: none">'.$v.'</li>';
			}
		}
	}
	unset($tmp);
	showtableheader('tips', '', 'id="'.$id.'"'.(!$display ? ' style="display: none;"' : ''), 0);
	showtablerow('', 'class="tipsblock"', '<ul id="'.$id.'lis">'.$tips.'</ul>');
	showtablefooter();
}

function showformheader($action, $extra = '', $name = 'cpform') {
	global $BASESCRIPT;
	echo '<form name="'.$name.'" method="post" action="'.$BASESCRIPT.'?action='.$action.'" id="'.$name.'"'.($extra == 'enctype' ? ' enctype="multipart/form-data"' : " $extra").'>'.
		'<input type="hidden" name="formhash" value="'.FORMHASH.'" />'.
		'<input type="hidden" id="formscrolltop" name="scrolltop" value="" />'.
		'<input type="hidden" name="anchor" value="'.htmlspecialchars($GLOBALS['anchor']).'" />';
}

function showhiddenfields($hiddenfields = array()) {
	if(is_array($hiddenfields)) {
		foreach($hiddenfields as $key => $val) {
			$val = is_string($val) ? htmlspecialchars($val) : $val;
			echo "\n<input type=\"hidden\" name=\"$key\" value=\"$val\">";
		}
	}
}

function showtableheader($title = '', $classname = '', $extra = '', $titlespan = 15) {
	$classname = str_replace(array('nobottom', 'notop'), array('nobdb', 'nobdt'), $classname);
	echo "\n".'<table class="tb tb2 '.$classname.'"'.($extra ? " $extra" : '').'>';
	if($title) {
		$span = $titlespan ? 'colspan="'.$titlespan.'"' : '';
		echo "\n".'<tr><th '.$span.' class="partition">'.lang($title).'</th></tr>';
	}
}

function showtagheader($tagname, $id, $display = FALSE, $classname = '') {
	echo '<'.$tagname.($classname ? " class=\"$classname\"" : '').' id="'.$id.'"'.($display ? '' : ' style="display: none"').'>';
}

function showtitle($title, $extra = '') {
	echo "\n".'<tr'.($extra ? " $extra" : '').'><th colspan="15" class="partition">'.lang($title).'</th></tr>';
}

function showsubtitle($title = array(), $rowclass='header') {
	if(is_array($title)) {
		$subtitle = "\n<tr class=\"$rowclass\">";
		foreach($title as $v) {
			if($v !== NULL) {
				$subtitle .= '<th>'.lang($v).'</th>';
			}
		}
		$subtitle .= '</tr>';
		echo $subtitle;
	}
}

function showtablerow($trstyle = '', $tdstyle = array(), $tdtext = array(), $return = FALSE) {
	if(!preg_match('/class\s*=\s*[\'"]([^\'"<>]+)[\'"]/i', $trstyle, $matches)) {
		$rowswapclass = is_array($tdtext) && count($tdtext) > 2 ? ' class="hover"' : '';
	} else {
		if(is_array($tdtext) && count($tdtext) > 2) {
			$rowswapclass = " class=\"{$matches[1]} hover\"";
			$trstyle = preg_replace('/class\s*=\s*[\'"]([^\'"<>]+)[\'"]/i', '', $trstyle);
		}
	}
	$cells = "\n".'<tr'.($trstyle ? ' '.$trstyle : '').$rowswapclass.'>';
	if(isset($tdtext)) {
		if(is_array($tdtext)) {
			foreach($tdtext as $key => $td) {
					$cells .= '<td'.(is_array($tdstyle) && !empty($tdstyle[$key]) ? ' '.$tdstyle[$key] : '').'>'.$td.'</td>';
			}
		} else {
			$cells .= '<td'.(!empty($tdstyle) && is_string($tdstyle) ? ' '.$tdstyle : '').'>'.$tdtext.'</td>';
		}
	}
	$cells .= '</tr>';
	if($return) {
		return $cells;
	}
	echo $cells;
}

function showsetting($setname, $varname, $value, $type = 'radio', $disabled = '', $hidden = 0, $comment = '', $extra = '') {

	$s = "\n";
	$check = array();
	$check['disabled'] = $disabled ? ' disabled' : '';

	if($type == 'radio') {
		$value ? $check['true'] = "checked" : $check['false'] = "checked";
		$value ? $check['false'] = '' : $check['true'] = '';
		$check['hidden1'] = $hidden ? ' onclick="$(\'hidden_'.$setname.'\').style.display = \'\';"' : '';
		$check['hidden0'] = $hidden ? ' onclick="$(\'hidden_'.$setname.'\').style.display = \'none\';"' : '';
		$s .= '<ul onmouseover="altStyle(this);">'.
			'<li'.($check['true'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="'.$varname.'" value="1" '.$check['true'].$check['hidden1'].$check['disabled'].'>&nbsp;'.lang('yes').'</li>'.
			'<li'.($check['false'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="'.$varname.'" value="0" '.$check['false'].$check['hidden0'].$check['disabled'].'>&nbsp;'.lang('no').'</li>'.
			'</ul>';
	} elseif($type == 'text' || $type == 'password' || $type == 'number') {
		$s .= '<input name="'.$varname.'" value="'.dhtmlspecialchars($value).'" type="'.$type.'" class="txt" '.$check['disabled'].' '.$extra.' />';
	} elseif($type == 'file') {
		$s .= '<input name="'.$varname.'" value="" type="file" class="txt uploadbtn marginbot" '.$check['disabled'].' '.$extra.' />';
	} elseif($type == 'textarea') {
		$readonly = $disabled ? 'readonly' : '';
		$s .= "<textarea $readonly rows=\"6\" ondblclick=\"textareasize(this, 1)\" onkeyup=\"textareasize(this, 0)\" name=\"$varname\" id=\"$varname\" cols=\"50\" class=\"tarea\" '.$extra.'>".dhtmlspecialchars($value)."</textarea>";
	} elseif($type == 'select') {
		$s .= '<select name="'.$varname[0].'" '.$extra.'>';
		foreach($varname[1] as $option) {
			$selected = $option[0] == $value ? 'selected="selected"' : '';
			$s .= "<option value=\"$option[0]\" $selected>".$option[1]."</option>\n";
		}
		$s .= '</select>';
	} elseif($type == 'mradio') {
		if(is_array($varname)) {
			$radiocheck = array($value => ' checked');
			$s .= '<ul'.(empty($varname[2]) ?  ' class="nofloat"' : '').' onmouseover="altStyle(this);">';
			foreach($varname[1] as $varary) {
				if(is_array($varary) && !empty($varary)) {
					$onclick = '';
					if(!empty($varary[2])) {
						foreach($varary[2] as $ctrlid => $display) {
							$onclick .= '$(\''.$ctrlid.'\').style.display = \''.$display.'\';';
						}
					}
					$onclick && $onclick = ' onclick="'.$onclick.'"';
					$s .= '<li'.($radiocheck[$varary[0]] ? ' class="checked"' : '').'><input class="radio" type="radio" name="'.$varname[0].'" value="'.$varary[0].'"'.$radiocheck[$varary[0]].$check['disabled'].$onclick.'>&nbsp;'.$varary[1].'</li>';
				}
			}
			$s .= '</ul>';
		}
	} elseif($type == 'mcheckbox') {
		$s .= '<ul class="nofloat" onmouseover="altStyle(this);">';
		foreach($varname[1] as $varary) {
			if(is_array($varary) && !empty($varary)) {
				$onclick = !empty($varary[2]) ? ' onclick="$(\''.$varary[2].'\').style.display = $(\''.$varary[2].'\').style.display == \'none\' ? \'\' : \'none\';"' : '';
				$checked = is_array($value) && in_array($varary[0], $value) ? ' checked' : '';
				$s .= '<li'.($checked ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="'.$varname[0].'[]" value="'.$varary[0].'"'.$checked.$check['disabled'].$onclick.'>&nbsp;'.$varary[1].'</li>';
			}
		}
		$s .= '</ul>';
	} elseif($type == 'binmcheckbox') {
		$checkboxs = count($varname[1]);
		$value = sprintf('%0'.$checkboxs.'b', $value);$i = 1;
		$s .= '<ul class="nofloat" onmouseover="altStyle(this);">';
		foreach($varname[1] as $key => $var) {
			$s .= '<li'.($value{$checkboxs - $i} ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="'.$varname[0].'['.$i.']" value="1"'.($value{$checkboxs - $i} ? ' checked' : '').' '.(!empty($varname[2][$key]) ? $varname[2][$key] : '').'>&nbsp;'.$var.'</li>';
			$i++;
		}
		$s .= '</ul>';
	} elseif($type == 'mselect') {
		$s .= '<select name="'.$varname[0].'" multiple="multiple" size="10" '.$extra.'>';
		foreach($varname[1] as $option) {
			$selected = is_array($value) && in_array($option[0], $value) ? 'selected="selected"' : '';
			$s .= "<option value=\"$option[0]\" $selected>".$option[1]."</option>\n";
		}
		$s .= '</select>';
	} elseif($type == 'color') {
		global $stylestuff;
		$preview_varname = str_replace('[', '_', str_replace(']', '', $varname));
		$code = explode(' ', $value);
		$css = '';
		for($i = 0; $i <= 1; $i++) {
			if($code[$i] != '') {
				if($code[$i]{0} == '#') {
					$css .= strtoupper($code[$i]).' ';
				} elseif(preg_match('/^http:\/\//i', $code[$i])) {
					$css .= 'url(\''.$code[$i].'\') ';
				} else {
					$css .= 'url(\''.$stylestuff['imgdir']['subst'].'/'.$code[$i].'\') ';
				}
			}
		}
		$background = trim($css);
		$colorid = ++$GLOBALS['coloridcount'];
		$s .= "<input id=\"c{$colorid}_v\" type=\"text\" class=\"txt\" style=\"float:left; width:200px;\" value=\"$value\" name=\"$varname\" onchange=\"updatecolorpreview('c{$colorid}')\">\n".
			"<input id=\"c$colorid\" onclick=\"c{$colorid}_frame.location='images/admincp/getcolor.htm?c{$colorid}';showMenu({'ctrlid':'c$colorid'})\" type=\"button\" class=\"colorwd\" value=\"\" style=\"background: $background\"><span id=\"c{$colorid}_menu\" style=\"display: none\"><iframe name=\"c{$colorid}_frame\" src=\"\" frameborder=\"0\" width=\"166\" height=\"186\" scrolling=\"no\"></iframe></span>\n$extra";
	} elseif($type == 'calendar') {
		$s .= "<input type=\"text\" class=\"txt\" name=\"$varname\" value=\"".dhtmlspecialchars($value)."\" onclick=\"showcalendar(event, this".($extra ? ', 1' : '').")\">\n";
	} elseif(in_array($type, array('multiply', 'range', 'daterange'))) {
		$onclick = $type == 'daterange' ? ' onclick="showcalendar(event, this)"' : '';
		$s .= "<input type=\"text\" class=\"txt\" name=\"$varname[0]\" value=\"".dhtmlspecialchars($value[0])."\" style=\"width: 108px; margin-right: 5px;\"$onclick>".($type == 'multiply' ? ' X ' : ' -- ')."<input type=\"text\" class=\"txt\" name=\"$varname[1]\" value=\"".dhtmlspecialchars($value[1])."\"class=\"txt\" style=\"width: 108px; margin-left: 5px;\"$onclick>";
	} else {
		$s .= $type;
	}
	showtablerow('', 'colspan="2" class="td27"', lang($setname));
	showtablerow('class="noborder"', array('class="vtop rowform"', 'class="vtop tips2"'), array(
		$s,
		($comment ? $comment : lang($setname.'_comment', 0)).($type == 'textarea' ? '<br />'.lang('tips_textarea') : '').
		($disabled ? '<br /><span class="smalltxt" style="color:#FF0000">'.lang($setname.'_disabled', 0).'</span>' : NULL)
	));
	if($hidden) {
		showtagheader('tbody', 'hidden_'.$setname, $value, 'sub');
	}

}

function mradio($name, $items = array(), $checked = '', $float = TRUE) {
	$list = '<ul'.($float ?  '' : ' class="nofloat"').' onmouseover="altStyle(this);">';
	if(is_array($items)) {
		foreach($items as $value => $item) {
			$list .= '<li'.($checked == $value ? ' class="checked"' : '').'><input type="radio" name="'.$name.'" value="'.$value.'" class="radio"'.($checked == $value ? ' checked="checked"' : '').' /> '.$item.'</li>';
		}
	}
	$list .= '</ul>';
	return $list;
}

function mcheckbox($name, $items = array(), $checked = array()) {
	$list = '<ul class="dblist" onmouseover="altStyle(this);">';
	if(is_array($items)) {
		foreach($items as $value => $item) {
			$list .= '<li'.(empty($checked) || in_array($value, $checked) ? ' class="checked"' : '').'><input type="checkbox" name="'.$name.'[]" value="'.$value.'" class="checkbox"'.(empty($checked) || in_array($value, $checked) ? ' checked="checked"' : '').' /> '.$item.'</li>';
		}
	}
	$list .= '</ul>';
	return $list;
}

function showsubmit($name = '', $value = 'submit', $before = '', $after = '', $floatright = '') {
	$str = '<tr>';
	$str .= $name && in_array($before, array('del', 'select_all', 'td')) ? '<td class="td25">'.($before != 'td' ? '<input type="checkbox" name="chkall" id="chkall" class="checkbox" onclick="checkAll(\'prefix\', this.form, \'delete\')" /><label for="chkall">'.lang($before) : '').'</label></td>' : '';
	$str .= '<td colspan="15">';
	$str .= $floatright ? '<div class="cuspages right">'.$floatright.'</div>' : '';
	$str .= '<div class="fixsel">';
	$str .= $before && !in_array($before, array('del', 'select_all', 'td')) ? $before.' &nbsp;' : '';
	$str .= $name ? '<input type="submit" class="btn" id="submit_'.$name.'" name="'.$name.'" title="'.lang('submit_tips').'" value="'.lang($value).'" />' : '';
	$after = $after == 'more_options' ? '<input class="checkbox" type="checkbox" value="1" onclick="$(\'advanceoption\').style.display = $(\'advanceoption\').style.display == \'none\' ? \'\' : \'none\'; this.value = this.value == 1 ? 0 : 1; this.checked = this.value == 1 ? false : true" id="btn_more" /><label for="btn_more">'.lang('more_options').'</label>' : $after;
	$str = $after ? $str.(($before && $before != 'del') || $name ? ' &nbsp;' : '').$after : $str;
	$str .= '</div></td>';
	$str .= '</tr>';
	echo $str.($name ? '<script type="text/JavaScript">_attachEvent(document.documentElement, \'keydown\', function (e) { entersubmit(e, \''.$name.'\'); });</script>' : '');
}

function showtagfooter($tagname) {
	echo '</'.$tagname.'>';
}

function showtablefooter() {
	echo '</table>'."\n";
}

function showformfooter() {
	global $scrolltop;
	echo '</form>'."\n";
	if($scrolltop) {
		echo '<script type="text/JavaScript">_attachEvent(window, \'load\', function () { scroll(0,'.intval($scrolltop).') }, document);</script>';
	}
}

function cpfooter() {
	global $version, $adminid, $db, $tablepre, $action, $bbname, $charset, $timestamp, $isfounder, $dbcharset;

?>
</div>
</body>
<?php

	if($_GET['highlight']) {
		$kws = explode(' ', $_GET['highlight']);
		echo '<script type="text/JavaScript">';
		foreach($kws as $kw) {
			echo 'parsetag(\''.$kw.'\');';
		}
		echo '</script>';
	}
?>
</html>

<?

	if($adminid == 1 && $action == 'home') {
		echo '<img src="admincp.php?action=misc&operation=checkstat" width="0" height="0">';
		$newsurl =  'ht'.'tp:/'.'/cus'.'tome'.'r.disc'.'uz.n'.'et/n'.'ews'.'.p'.'hp?'.bbsinformation();

		//$newsurl = 'http://localhost/com/n'.'ews'.'.p'.'hp?'.bbsinformation();
?>

<script type="text/javascript">
var newhtml = '';
newhtml += '<table class="tb tb2"><tr><th class="partition edited">&#x60A8;&#x5F53;&#x524D;&#x4F7F;&#x7528;&#x7684; Discuz! &#x7A0B;&#x5E8F;&#x7248;&#x672C;&#x6709;&#x91CD;&#x8981;&#x66F4;&#x65B0;&#xFF0C;&#x8BF7;&#x53C2;&#x7167;&#x4EE5;&#x4E0B;&#x63D0;&#x793A;&#x8FDB;&#x884C;&#x53CA;&#x65F6;&#x5347;&#x7EA7;</th></tr>';
newhtml += '<tr><td class="tipsblock"><a href="http://faq.comsenz.com/checkversion.php?product=Discuz&version=<?=DISCUZ_VERSION?>&release=<?=DISCUZ_RELEASE?>&charset=<?=$charset?>&dbcharset=<?=$dbcharset?>" target="_blank"><img src="<?=$newsurl?>" onload="shownews()" /></a></td></tr></table>';
$('boardnews').style.display = 'none';
$('boardnews').innerHTML = newhtml;
function shownews() {
	$('boardnews').style.display = '';
}
</script>
<?php

	}
	updatesession();
}

if(!function_exists('ajaxshowheader')) {
	function ajaxshowheader() {
		global $charset, $inajax;
		ob_end_clean();
		@header("Expires: -1");
		@header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
		@header("Pragma: no-cache");
		header("Content-type: application/xml");
		echo "<?xml version=\"1.0\" encoding=\"$charset\"?>\n<root><![CDATA[";
	}
}

if(!function_exists('ajaxshowfooter')) {
	function ajaxshowfooter() {
		echo ']]></root>';
	}
}

function checkacpaction($action, $operation = '', $halt = true) {

	global $radminid, $groupid, $dactionarray;

	$ret = ($dactionarray && ($radminid != $groupid) && (in_array($action, $dactionarray) || ($operation && in_array($action.'_'.$operation, $dactionarray)))) ? false : true;

	if($halt && !$ret || in_array('_readonly', $dactionarray) && !empty($_POST)) {
		cpheader();
		cpmsg('action_noaccess');
	}

	return $ret;

}

function showimportdata() {
	showsetting('import_type', array('importtype', array(
		array('file', lang('import_type_file'), array('importfile' => '', 'importtxt' => 'none')),
		array('txt', lang('import_type_txt'), array('importfile' => 'none', 'importtxt' => ''))
	)), 'file', 'mradio');
	showtagheader('tbody', 'importfile', TRUE);
	showsetting('import_file', 'importfile', '', 'file');
	showtagfooter('tbody');
	showtagheader('tbody', 'importtxt');
	showsetting('import_txt', 'importtxt', '', 'textarea');
	showtagfooter('tbody');
}

function getimportdata($name = '', $addslashes = 1, $ignoreerror = 0) {
	if($GLOBALS['importtype'] == 'file') {
		$data = @implode('', file($_FILES['importfile']['tmp_name']));
		@unlink($_FILES['importfile']['tmp_name']);
	} else {
		$data = $_POST['importtxt'] && MAGIC_QUOTES_GPC ? stripslashes($_POST['importtxt']) : $GLOBALS['importtxt'];
	}
	include_once DISCUZ_ROOT.'./include/xml.class.php';
	$xmldata = xml2array($data);
	if(!is_array($xmldata) || !$xmldata) {
		if($name && !strexists($data, '# '.$name)) {
			if(!$ignoreerror) {
				cpmsg('import_data_typeinvalid', '', 'error');
			} else {
				return array();
			}
		}
		$data = preg_replace("/(#.*\s+)*/", '', $data);
		$data = unserialize(base64_decode($data));
		if(!is_array($data) || !$data) {
			if(!$ignoreerror) {
				cpmsg('import_data_invalid', '', 'error');
			} else {
				return array();
			}
		}
	} else {
		if($name && $name != $xmldata['Title']) {
			if(!$ignoreerror) {
				cpmsg('import_data_typeinvalid', '', 'error');
			} else {
				return array();
			}
		}
		$data = exportarray($xmldata['Data'], 0);
	}
	if($addslashes) {
		$data = daddslashes($data, 1);
	}
	return $data;
}

function exportdata($name, $filename, $data) {
	include_once DISCUZ_ROOT.'./include/xml.class.php';
	$root = array(
		'Title' => $name,
		'Version' => $GLOBALS['version'],
		'Time' => gmdate("Y-m-d H:i", $GLOBALS['timestamp'] + $GLOBALS['timeoffset'] * 3600),
		'From' => $GLOBALS['bbname'].' ('.$GLOBALS['boardurl'].')',
		'Data' => exportarray($data, 1)
	);
	$filename = strtolower(str_replace(array('!', ' '), array('', '_'), $name)).'_'.$filename.'.xml';
	$plugin_export = array2xml($root, 1);
	ob_end_clean();
	dheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	dheader('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	dheader('Cache-Control: no-cache, must-revalidate');
	dheader('Pragma: no-cache');
	dheader('Content-Encoding: none');
	dheader('Content-Length: '.strlen($plugin_export));
	dheader('Content-Disposition: attachment; filename='.$filename);
	dheader('Content-Type: text/xml');
	echo $plugin_export;
	exit();
}

function exportarray($array, $method) {
	$tmp = $array;
	if($method) {
		foreach($array as $k => $v) {
			if(is_array($v)) {
				$tmp[$k] = exportarray($v, 1);
			} else {
				$uv = unserialize($v);
				if($uv && is_array($uv)) {
					$tmp['__'.$k] = exportarray($uv, 1);
					unset($tmp[$k]);
				} else {
					$tmp[$k] = $v;
				}
			}
		}
	} else {
		foreach($array as $k => $v) {
			if(is_array($v)) {
				if(substr($k, 0, 2) == '__') {
					$tmp[substr($k, 2)] = serialize(exportarray($v, 0));
					unset($tmp[$k]);
				} else {
					$tmp[$k] = exportarray($v, 0);
				}
			} else {
				$tmp[$k] = $v;
			}
		}
	}
	return $tmp;
}

?>