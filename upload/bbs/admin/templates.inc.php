<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: templates.inc.php 17432 2008-12-20 13:28:29Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();
if(!isfounder()) cpmsg('noaccess_isfounder', '', 'error');

$operation = empty($operation) ? 'admin' : $operation;

if($operation == 'admin') {

	if(!submitcheck('tplsubmit')) {

		$templates = '';
		$query = $db->query("SELECT * FROM {$tablepre}templates");
		while($tpl = $db->fetch_array($query)) {
			$templates .= showtablerow('', array('class="td25"', '', 'class="td29"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" ".($tpl[templateid] == 1 ? 'disabled ' : '')."value=\"$tpl[templateid]\">",
				"<input type=\"text\" class=\"txt\" size=\"8\" name=\"namenew[$tpl[templateid]]\" value=\"$tpl[name]\">",
				"<input type=\"text\" class=\"txt\" size=\"20\" name=\"directorynew[$tpl[templateid]]\" value=\"$tpl[directory]\">",
				!empty($tpl['copyright']) ? $tpl['copyright'] : "<input type=\"text\" class=\"txt\" size=\"8\" name=\"copyrightnew[$tpl[templateid]]\" value=>",
				"<a href=\"$BASESCRIPT?action=templates&operation=maint&id=$tpl[templateid]\" class=\"act\">$lang[detail]</a>"
			), TRUE);
		}

		shownav('style', 'templates_admin');
		showsubmenu('templates_admin');
		showformheader('templates');
		showtableheader();
		showsubtitle(array('', 'templates_admin_name', 'dir', 'copyright', ''));
		echo $templates;
		echo '<tr><td>'.$lang['add_new'].'</td><td><input type="text" class="txt" size="8" name="newname"></td><td class="td29"><input type="text" class="txt" size="20" name="newdirectory"></td><td><input type="text" class="txt" size="25" name="newcopyright"></td><td>&nbsp;</td></tr>';
		showsubmit('tplsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if($newname) {
			if(!$newdirectory) {
				cpmsg('tpl_new_directory_invalid', '', 'error');
			} elseif(!istpldir($newdirectory)) {
				$directory = $newdirectory;
				cpmsg('tpl_directory_invalid', '', 'error');
			}
			$db->query("INSERT INTO {$tablepre}templates (name, directory, copyright)
				VALUES ('$newname', '$newdirectory', '$newcopyright')", 'UNBUFFERED');
		}

		foreach($directorynew as $id => $directory) {
			if(!$delete || ($delete && !in_array($id, $delete))) {
				if(!istpldir($directory)) {
					cpmsg('tpl_directory_invalid', '', 'error');
				} elseif($id == 1 && $directory != './templates/default') {
					cpmsg('tpl_default_directory_invalid', '', 'error');
				}
				$db->query("UPDATE {$tablepre}templates SET name='$namenew[$id]', directory='$directorynew[$id]' WHERE templateid='$id'", 'UNBUFFERED');
				if(!empty($copyrightnew[$id])) {
					$db->query("UPDATE {$tablepre}templates SET copyright='$copyrightnew[$id]' WHERE templateid='$id' AND copyright=''", 'UNBUFFERED');
				}
			}
		}

		if(is_array($delete)) {
			if(in_array('1', $delete)) {
				cpmsg('tpl_delete_invalid', '', 'error');
			}
			if($ids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}templates WHERE templateid IN ($ids) AND templateid<>'1'", 'UNBUFFERED');
				$db->query("UPDATE {$tablepre}styles SET templateid='1' WHERE templateid IN ($ids)", 'UNBUFFERED');
			}
		}

		updatecache('styles');
		cpmsg('tpl_update_succeed', $BASESCRIPT.'?action=templates', 'succeed');

	}

} elseif($operation == 'maint') {

	if(empty($id)) {
		$tplselect = "<select name=\"id\" style=\"width: 150px\">\n";
		$query = $db->query("SELECT templateid, name FROM {$tablepre}templates");
		while($tpl = $db->fetch_array($query)) {
			$tplselect .= "<option value=\"$tpl[templateid]\">$tpl[name]</option>\n";
		}
		$tplselect .= '</select>';
		cpmsg('templates_edit_select', $BASESCRIPT.'?action=templates&operation=maint'.(!empty($highlight) ? "&highlight=$highlight" : ''), 'form', $tplselect);
	}

	$template = $db->fetch_first("SELECT * FROM {$tablepre}templates WHERE templateid='$id'");
	if(!$template) {
		cpmsg('undefined_action', '', 'error');
	} elseif(!istpldir($template['directory'])) {
		$directory = $template['directory'];
		cpmsg('tpl_directory_invalid', '', 'error');
	}

	$warning = $template['templateid'] == 1 ? 'templates_maint_default_comment' : 'templates_maint_nondefault_comment';
	if($keyword) {
		$keywordadd = " - $lang[templates_maint_keyword] <i>".dhtmlspecialchars(stripslashes($keyword))."</i> - <a href=\"$BASESCRIPT?action=templates&operation=maint&id=$id\">[$lang[templates_maint_view_all]]</a>";
		$keywordenc = rawurlencode($keyword);
	}

	$tpldir = dir(DISCUZ_ROOT.'./'.$template['directory']);
	$tplarray = $langarray = $differ = $new = array();
	while($entry = $tpldir->read()) {
		$extension = strtolower(fileext($entry));
		if($extension == 'htm' || $extension == 'php') {
			if($extension == 'htm') {
				$tplname = substr($entry, 0, -4);
				$pos = strpos($tplname, '_');
				if($keyword) {
					if(!stristr(implode("\n", file(DISCUZ_ROOT."./$template[directory]/$entry")), $keyword)) {
						continue;
					}
				}
				if(!$pos) {
					$tplarray[$tplname][] = $tplname;
				} else {
					$tplarray[substr($tplname, 0, $pos)][] = $tplname;
				}
			} else {
				$langarray[] = substr($entry, 0, -9);
			}
			if($template['templateid'] != 1) {
				if(file_exists(DISCUZ_ROOT."./templates/default/$entry")) {
					if(md5_file(DISCUZ_ROOT."./templates/default/$entry") != md5_file(DISCUZ_ROOT."./$template[directory]/$entry")) {
						$differ[] = $entry;
					}
				} else {
					$new[] = $entry;
				}
			}
		}
	}
	$tpldir->close();

	ksort($tplarray);
	ksort($langarray);
	$templates = $languages = '';

	$allowedittpls = checkpermission('tpledit', 0);
	foreach($tplarray as $tpl => $subtpls) {
		$templates .= "<li><h5>$tpl</h5><ul class=\"tpllist3\">";
		foreach($subtpls as $subtpl) {
			$filename = "$subtpl.htm";
			$resetlink = '';
			if(in_array($filename, $differ)) {
				$subtpl = '<font color=\'#FF0000\'>'.$subtpl.'</font>';
				$resetlink = " <a href=\"$BASESCRIPT?action=templates&operation=edit&templateid=$template[templateid]&fn=$filename&reset=yes\" class=\"act\">$lang[templates_maint_reset]</a>";
			}
			if(in_array($filename, $new)) {
				$subtpl = '<font color=\'#00FF00\'>'.$subtpl.'</font>';
			}
			$templates .= '<li>';
			if($allowedittpls) {
				$templates .= "$subtpl &nbsp; <a href=\"$BASESCRIPT?action=templates&operation=edit&templateid=$template[templateid]&fn=$filename&keyword=$keywordenc\" class=\"act\">$lang[edit]</a> ".
					"<a href=\"$BASESCRIPT?action=templates&operation=edit&templateid=$template[templateid]&fn=$filename&delete=yes\" class=\"act\">$lang[delete]</a>$resetlink";
			} else {
				$templates .= "$subtpl &nbsp; <a href=\"$BASESCRIPT?action=templates&operation=edit&templateid=$template[templateid]&fn=$filename&keyword=$keywordenc\" class=\"act\">$lang[view]</a> ";
			}
			$templates .= '</li>';
		}
		$templates .= "</ul></li>";
	}
	$languages .= '<ul class="tpllist3">';
	foreach($langarray as $langpack) {
		$resetlink = '';
		$langpackname = $langpack;
		if(is_array($differ) && in_array($langpack.'.lang.php', $differ)) {
			$langpackname = '<font color=\'#FF0000\'>'.$langpackname.'</font>';
			$resetlink = " <a href=\"$BASESCRIPT?action=templates&operation=edit&templateid=$template[templateid]&fn=$langpack.lang.php&reset=yes\" class=\"act\">$lang[templates_maint_reset]</a>";
		}
		$languages .= '<li>';
		if($allowedittpls) {
			$languages .= "$langpackname &nbsp; <a href=\"$BASESCRIPT?action=templates&operation=edit&templateid=$template[templateid]&fn=$langpack.lang.php\" class=\"act\">$lang[edit]</a>";
			$languages .= $template['templateid'] != 1 ? " <a href=\"$BASESCRIPT?action=templates&operation=edit&templateid=$template[templateid]&fn=$langpack.lang.php&delete=yes\" class=\"act\">$lang[delete]</a>" : '';
			$languages .= "$resetlink";
		} else {
			$languages .= "$langpackname &nbsp; <a href=\"$BASESCRIPT?action=templates&operation=edit&templateid=$template[templateid]&fn=$langpack.lang.php\" class=\"act\">$lang[view]</a>";
		}
		$languages .= '</li>';
	}
	$languages .= '</ul>';

	shownav('style', 'templates_maint');
	showsubmenu(lang('templates_maint').' - '.$template['name']);
	showtips($warning);
	showtableheader('', 'nobottom fixpadding');

	showformheader("templates&operation=add&id=$id");
	showtablerow('', array('class="td21"', 'class="td22"'), array(
		lang('templates_maint_new'),
		'<input type="text" class="txt" name="name" size="40" maxlength="40">',
		'<input type="submit" class="btn" value="'.lang('submit').'">'
	));
	showformfooter();

	showformheader("templates&operation=maint&id=$id");
	showtablerow('', array('class="td21"', 'class="td22"'), array(
		lang('templates_maint_search'),
		'<input type="text" class="txt" name="keyword" size="40">',
		'<input type="submit" class="btn" value="'.lang('submit').'">'
	));
	showformfooter();

	showtablefooter();

	showtableheader(lang('templates_maint_select').$keywordadd, 'notop fixpadding');

?>

<tr><td style="background:none;">
<ul class="tpllist">
	<li>
		<h4>Discuz! <?=$lang['templates_maint_language_pack']?></h4>
		<ul class="tpllist2">
			<?=$languages?>
		</ul>
	</li>
	<li>
		<h4>Discuz! <?=$lang['templates_maint_html']?></h4>
		<ul class="tpllist2">
			<?=$templates?>
		</ul>
	</li>
</ul>
</td></tr>

<?

	showtablefooter();

} elseif($operation == 'copy') {

	checkpermission('tpledit');
	$srctemplate = $db->fetch_first("SELECT directory FROM {$tablepre}templates WHERE templateid='$templateid'");
	if(!$srctemplate) {
		cpmsg('tpl_edit_nonexistence', '', 'error');
	}

	$desctemplate = $db->fetch_first("SELECT directory FROM {$tablepre}templates WHERE templateid='$copyto'");
	if(!$desctemplate) {
		cpmsg('tpl_edit_nonexistence', '', 'error');
	}

	if(!file_exists(DISCUZ_ROOT.$desctemplate['directory'])) {
		$directory = $desctemplate['directory'];
		cpmsg('tpl_directory_invalid', '', 'error');
	}

	$newfilename = DISCUZ_ROOT.$desctemplate['directory']."/$fn";
	if(file_exists($newfilename) && !$confirmed) {
		cpmsg('tpl_desctpl_exists', "$BASESCRIPT?action=templates&operation=copy&templateid=$templateid&fn=$fn&copyto=$copyto", 'form');
	}

	if(!copy(DISCUZ_ROOT."./$srctemplate[directory]/$fn", $newfilename)) {
		cpmsg('tpl_tplcopy_invalid', '', 'error');
	}

	cpmsg('tpl_tplcopy_succeed', "$BASESCRIPT?action=templates&operation=edit&templateid=$templateid&fn=$fn", 'succeed');

} elseif($operation == 'edit') {

	$allowedittpls = checkpermission('tpledit', 0);
	$template = $db->fetch_first("SELECT * FROM {$tablepre}templates WHERE templateid='$templateid'");
	if(!$template) {
		cpmsg('tpl_edit_nonexistence', '', 'error');
	}

	$directorys = '';
	$query = $db->query("SELECT templateid, directory FROM {$tablepre}templates WHERE templateid!='$templateid' GROUP BY directory");
	while($directory = $db->fetch_array($query)) {
		$directorys .='<option value="'.$directory['templateid'].'">'.$directory['directory'].'</option>';
	}

	$fn = str_replace(array('..', '/', '\\'), array('', '', ''), $fn);
	$filename = DISCUZ_ROOT."./$template[directory]/$fn";
	if(!is_writeable($filename)) {
		cpmsg('tpl_edit_invalid', '', 'error');
	}

	$keywordenc = rawurlencode($keyword);

	if(!submitcheck('editsubmit') && $delete != 'yes' && $reset != 'yes') {

		$islang = FALSE;
		if(preg_match('/\.lang\.php$/i', $filename)) {
			$currentlang = $lang;
			$currentmsglang = $msglang;
			unset($lang, $msglang);
			include $filename;
			$islang = TRUE;
			$langinputs = '';
			isset($actioncode) && $langinputs .= langedit('actioncode');
			isset($language) && $langinputs .= langedit('language');
			isset($lang) && $langinputs .= langedit('lang');
			isset($msglang) && $langinputs .= langedit('msglang');
			isset($spacelanguage) && $langinputs .= langedit('spacelanguage');
			$lang = $currentlang;
			$msglang = $currentmsglang;
		} else {
			$fp = @fopen($filename, 'rb');
			$content = @fread($fp, filesize($filename));
			fclose($fp);
		}

		$resetbutton = $onclickevent = $checkresult = '';
		if($template['templateid'] != 1) {
			$defaulttpl = DISCUZ_ROOT."./templates/default/$fn";
			if(file_exists($defaulttpl) && md5_file($defaulttpl) != md5_file($filename)) {
				$resetbutton = ' <input  style="vertical-align: middle" type="button" class="btn" value="'.$lang['templates_edit_reset'].'" accesskey="r" onclick="location.href=\''.$BASESCRIPT.'?action=templates&operation=edit&templateid='.$template['templateid'].'&fn='.$fn.'&keyword='.$keywordenc.'&reset=yes\'"> '.
					 (strtolower(fileext($fn)) == 'htm' ? '<input  style="vertical-align: middle" type="button" class="btn" value="'.$lang['templates_edit_diff'].'" onclick="location.href=\''.$BASESCRIPT.'?action=templates&operation=edit&templateid='.$template['templateid'].'&fn='.$fn.'&keyword='.$keywordenc.'&checktpl=yes\'"> ' : '');
			}

			$dellist = $addlist = array();
			if($checktpl && strtolower(fileext($fn)) == 'htm') {
				$fp = @fopen($defaulttpl, 'rb');
				$defaultcontent = @fread($fp, filesize($defaulttpl));
				fclose($fp);

				require_once DISCUZ_ROOT.'./include/diff.class.php';

				$a = new Diff($content, $defaultcontent);
				$entries = $a->fetch_diff();

				$result = '<br /><table class="tb tb2 nobdb" width="100%" border="0" cellpadding="0" cellspacing="0"><tr class="partition"><td>'.$lang['templates_edit_diff_current'].'</td><td>&nbsp;</td><td>'.$lang['templates_edit_diff_default'].'</td></tr>';
				foreach ($entries as $diff_entry) {
					$result .= '<tr><th width="49.5%" valign="top" class="diff-'.$diff_entry->left_class().'">'.
						$diff_entry->diff_text($diff_entry->left).'</th><th width="1%">&nbsp;</th>'.
						'<th width="49.5%" valign="top" class="diff-'.$diff_entry->right_class().'">'.
						$diff_entry->diff_text($diff_entry->right)."</th></tr>";
				}
				$result .= '</table><br /><table class="tb tb2 nobdb" width="100%" border="0" cellpadding="4" cellspacing="0">'.
					'<tr><th class="diff-deleted" style="text-align: center">'.$lang['templates_edit_diff_deleted'].'</th><th class="diff-notext">&nbsp;</th></tr>'.
					'<tr><th class="diff-changed" style="text-align: center" colspan="2">'.$lang['templates_edit_diff_changed'].'&nbsp;</th></tr>'.
					'<tr><th class="diff-notext">&nbsp;</th><th class="diff-added" style="text-align: center">'.$lang['templates_edit_diff_added'].'</th></tr></table>';
				$checkresult = $result;
			}
		} else {
			$onclickevent = 'onclick="return confirm(\''.$lang['templates_edit_default_overwriteconfirm'].'\')"';
		}

		$content = dhtmlspecialchars($content);
		$filemtime = date("$dateformat $timeformat", filemtime($filename));

?>
<script language="JavaScript">
var n = 0;
function displayHTML(obj) {
	win = window.open(" ", 'popup', 'toolbar = no, status = no, scrollbars=yes');
	win.document.write("" + obj.value + "");
}
function HighlightAll(obj) {
	obj.focus();
	obj.select();
	if(document.all) {
		obj.createTextRange().execCommand("Copy");
		window.status = "<?=$lang['templates_edit_clickboard']?>";
		setTimeout("window.status=''", 1800);
	}
}
function findInPage(obj, str, noalert) {
	var txt, i, found;
	if(str == "") {
		return false;
	}
	if(document.layers) {
		if(!obj.find(str)) {
			while(obj.find(str, false, true)) {
				n++;
			}
		} else {
			n++;
		}
		if(n == 0 && !noalert) {
			alert("<?=$lang['templates_edit_keyword_not_found']?>");
		}
	}
	if(document.all) {
		txt = obj.createTextRange();
		for(i = 0; i <= n && (found = txt.findText(str)) != false; i++) {
			txt.moveStart('character', 1);
			txt.moveEnd('textedit');
		}
		if(found) {
			txt.moveStart('character', -1);
			txt.findText(str);
			txt.select();
			txt.scrollIntoView();
			n++;
			return true;
		} else {
			if(n > 0) {
				n = 0;
				findInPage(obj, str, noalert);
			} else if(!noalert) {
				alert("<?=$lang['templates_edit_keyword_not_found']?>");
			}
		}
	}
	return false;
}

<?

if($islang) {

?>
	var ni = 0;
	var niprev = 0;
	function MultifindInPage(obj, str) {
		for(var i = ni; i < obj.elements.length; i++) {
			if(obj.elements[i].type == 'textarea') {
				if(findInPage(obj.elements[i], str, 1)) {
					ni = i;
					break;
				}
			}
			if(i == obj.elements.length - 1) ni = 0;
		}
	}
<?

}

?>
</script>

<?
		shownav('style', 'templates_edit');
		showsubmenu("$lang[templates_edit] - $template[name] $fn - $lang[lastmodified]: $filemtime");
		showformheader("templates&operation=edit&templateid=$templateid&fn=$fn");
		showhiddenfields(array('keyword' => $keywordenc));
?>

<div class="colorbox">
<?

if($islang) {

?>
<div style="margin-bottom:10px;width:99%;height:390px;overflow-y:scroll;overflow-x:hidden;">
<table class="tb tb2" style="border:none">
<tr><td><b><?=$lang['templates_edit_variable']?></b></td><td><b><?=$lang['templates_edit_value']?></b></td></tr>
<?=$langinputs?>
</table>
</div>
<?

} else {

?>
<textarea cols="100" rows="25" name="templatenew" style="margin-bottom:10px;width:99%;height:390px;"><?=$content?></textarea>
<?

}

?>
<input name="search" type="text" class="txt" style="width:150px;" accesskey="t" size="20" onChange="n=0;">
<?

if($islang) {

?>
<input type="button" class="btn" value="<?=$lang['search']?>" accesskey="f" onClick="MultifindInPage(this.form, this.form.search.value)">&nbsp;&nbsp;&nbsp;
<?

} else {

?>
<input type="button" class="btn" value="<?=$lang['search']?>" accesskey="f" onClick="findInPage(this.form.templatenew, this.form.search.value)">&nbsp;&nbsp;&nbsp;
<?

}

?>
<input type="button" class="btn" value="<?=$lang['return']?>" accesskey="e" onClick="location.href='<?=$BASESCRIPT?>?action=templates&operation=maint&id=<?=$templateid?>&keyword=<?=$keywordenc?>'">
<input type="button" class="btn" value="<?=$lang['preview']?>" accesskey="p" onClick="displayHTML(this.form.templatenew)">
<input type="button" class="btn" value="<?=$lang['copy']?>" accesskey="c" onClick="HighlightAll(this.form.templatenew)">

<?
		if($allowedittpls) {
			echo "<input type=\"submit\" class=\"btn\" name=\"editsubmit\" value=\"$lang[submit]\" $onclickevent><br />";
			if($directorys) {
				echo $lang['templates_edit_copyto_otherdirs']."<select id=\"copyto\">".
					"$directorys</select> <input style=\"vertical-align: middle\" type=\"button\" class=\"btn\" value=\"$lang[templates_edit_start_copy]\" ".
					"accesskey=\"r\" onclick=\"if(\$('copyto').value == 1 && confirm('$lang[templates_edit_default_overwriteconfirm]') || \$('copyto').value != 1) location.href='$BASESCRIPT?action=templates&operation=copy&templateid={$template['templateid']}&fn={$fn}&copyto='+\$('copyto').value\">";
			}
			echo $resetbutton;
		}
		echo '</div></form>'.$checkresult;

	} elseif($delete == 'yes') {
		checkpermission('tpledit');
		if(!$confirmed) {
			cpmsg('tpl_delete_confirm', "$BASESCRIPT?action=templates&operation=edit&templateid=$templateid&fn=$fn&delete=yes", 'form');
		} else {
			if(@unlink($filename)) {
				cpmsg('tpl_delete_succeed', "$BASESCRIPT?action=templates&operation=maint&id=$templateid", 'succeed');
			} else {
				cpmsg('tpl_delete_fail', '', 'error');
			}
		}

	} elseif($reset == 'yes') {
		checkpermission('tpledit');
		if(!$confirmed) {
			cpmsg('tpl_reset_confirm', "$BASESCRIPT?action=templates&operation=edit&templateid=$templateid&fn=$fn&keyword=$keywordenc&reset=yes", 'form');
		} else {
			$defaultfilename = DISCUZ_ROOT.'./templates/default/'.$fn;
			$filename = DISCUZ_ROOT."./$template[directory]/$fn";

			if(!copy($defaultfilename, $filename)) {
				cpmsg('tpl_edit_invalid', '', 'error');
			}

			cpmsg('tpl_reset_succeed', "$BASESCRIPT?action=templates&operation=maint&id=$templateid&keyword=$keywordenc", 'succeed');
		}

	} else {
		checkpermission('tpledit');
		if(preg_match('/\.lang\.php$/i', $filename)) {
			$templatenew = '';
			foreach($langnew as $key => $value) {
				$templatenew .= '$'.$key." = array\n(\n";
				foreach($value as $key1 => $value1) {
					if(substr($value1, strlen($value1) -1 , 1) == '\\') {
						$value1 .= '\\\\';
					}
					$templatenew .= "\t'$key1' => '".str_replace('\\\\\'', '\\\'', addcslashes(stripslashes(str_replace("\x0d\x0a", "\x0a", $value1)), "'"))."',\n";
				}
				$templatenew .= ");\n";
			}
			$templatenew = "<?php\n\n// Language Pack for Discuz! Version 1.0.0\n\n$templatenew\n?>";
		} else {
			$templatenew = stripslashes(str_replace("\x0d\x0a", "\x0a", $templatenew));
		}

		$fp = fopen($filename, 'wb');
		flock($fp, 2);
		fwrite($fp, $templatenew);
		fclose($fp);

		if(substr(basename($filename), 0, 3) == 'css') {
			updatecache('styles');
		}

		cpmsg('tpl_edit_succeed', "$BASESCRIPT?action=templates&operation=maint&id=$templateid&keyword=$keywordenc", 'succeed');

	}

} elseif($operation == 'add') {

	checkpermission('tpledit');
	$template = $db->fetch_first("SELECT * FROM {$tablepre}templates WHERE templateid='$id'");
	if(!$template) {
		cpmsg('tpl_add_invalid', '', 'error');
	} elseif(!istpldir($template['directory'])) {
		$directory = $template['directory'];
		cpmsg('tpl_directory_invalid', '', 'error');
	} elseif(file_exists(DISCUZ_ROOT."./$template[directory]/$name.htm")) {
		cpmsg('tpl_add_duplicate', '', 'error');
	} elseif(!@$fp = fopen(DISCUZ_ROOT."./$template[directory]/$name.htm", 'wb')) {
		cpmsg('tpl_add_file_invalid', '', 'error');
	}

	@fclose($fp);
	cpmsg('tpl_add_succeed', "$BASESCRIPT?action=templates&operation=edit&templateid=1&fn=$name.htm", 'succeed');

}

function langedit($var) {
	global $$var, $currentlang;
	$return = '';
	foreach($$var as $key => $value) {
		$return .= '<tr><td width="100" style="border:0">'.$key.'</td><td style="border:0"><textarea cols="100" rows="3" name="langnew['.$var.']['.$key.']" style="width: 95%;">'.dhtmlspecialchars($value).'</textarea></td></tr>';
	}
	return $return;
}

?>