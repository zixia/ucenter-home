<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: styles.inc.php 21213 2009-11-20 04:55:21Z zhaoxiongfei $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

$operation = empty($operation) ? 'admin' : $operation;

if($operation == 'export' && $id) {
	$stylearray = $db->fetch_first("SELECT s.name, s.templateid, t.name AS tplname, t.directory, t.copyright FROM {$tablepre}styles s LEFT JOIN {$tablepre}templates t ON t.templateid=s.templateid WHERE styleid='$id'");
	if(!$stylearray) {
		cpheader();
		cpmsg('styles_export_invalid', '', 'error');
	}

	$query = $db->query("SELECT * FROM {$tablepre}stylevars WHERE styleid='$id'");
	while($style = $db->fetch_array($query)) {
		$stylearray['style'][$style['variable']] = $style['substitute'];
	}

	$stylearray['version'] = strip_tags($version);
	exportdata('Discuz! Style', $stylearray['name'], $stylearray);
}

cpheader();

$predefinedvars = array('available' => array(), 'boardimg' => array(), 'imgdir' => array(), 'styleimgdir' => array(), 'stypeid' => array(),
	'headerbgcolor' => array(0, $lang['styles_edit_type_bg']),
	'bgcolor' => array(0),
	'sidebgcolor' => array(0, '', '#FFF sidebg.gif repeat-y 100% 0'),

	'headerborder' => array(1, $lang['styles_edit_type_header'], '1px'),
	'headerbordercolor' => array(0),
	'headertext' => array(0),
	'footertext' => array(0),

	'font' => array(1, $lang['styles_edit_type_font']),
	'fontsize' => array(1),
	'smfont' => array(1),
	'smfontsize' => array(1),
	'tabletext' => array(0),
	'midtext' => array(0),
	'lighttext' => array(0),

	'link' => array(0, $lang['styles_edit_type_url']),
	'highlightlink' => array(0),

	'wrapwidth' => array(1, $lang['styles_edit_type_wrap'], '98%'),
	'wrapbg' => array(0),
	'wrapborder' => array(1, '', '0'),
	'wrapbordercolor' => array(0),

	'msgfontsize' => array(1, $lang['styles_edit_type_post'], '14px'),
	'msgbigsize' => array(1, '', '16px'),
	'contentwidth' => array(1),
	'contentseparate' => array(0),

	'menuborder' => array(0, $lang['styles_edit_type_menu']),
	'menubgcolor' => array(0),
	'menutext' => array(0),
	'menuhover' => array(0),
	'menuhovertext' => array(0),

	'inputborder' => array(0, $lang['styles_edit_type_input']),
	'inputborderdarkcolor' => array(0),
	'inputbg' => array(0, '', '#FFF'),

	'dropmenuborder' => array(0, $lang['styles_edit_type_dropmenu']),
	'dropmenubgcolor' => array(0),

	'floatbgcolor' => array(0, $lang['styles_edit_type_float']),
	'floatmaskbgcolor' => array(0),

	'commonborder' => array(0, $lang['styles_edit_type_other']),
	'commonbg' => array(0),
	'specialborder' => array(0),
	'specialbg' => array(0),
	'interleavecolor' => array(0),
	'noticetext' => array(0),
);

if($operation == 'admin') {

	$query = $db->query("SELECT s.styleid, s.available, s.name, t.name AS tplname, t.directory, t.copyright FROM {$tablepre}styles s LEFT JOIN {$tablepre}templates t ON t.templateid=s.templateid ORDER BY s.available desc, s.styleid");
	$sarray = $tpldirs = array();
	while($row = $db->fetch_array($query)) {
		$sarray[$row['styleid']] = $row;
		$tpldirs[] = realpath($row['directory']);
	}

	$defaultid = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='styleid'");

	if(!submitcheck('stylesubmit')) {
		$narray = array();
		$dir = DISCUZ_ROOT.'./templates';
		$templatedir = dir($dir);$i = -1;
		while($entry = $templatedir->read()) {
			$tpldir = realpath($dir.'/'.$entry);
			if(!in_array($entry, array('.', '..')) && !in_array($tpldir, $tpldirs) && is_dir($tpldir)) {
				$styleexist = 0;
				$searchdir = dir($tpldir);
				while($searchentry = $searchdir->read()) {
					if(substr($searchentry, 0, 13) == 'discuz_style_' && fileext($searchentry) == 'xml') {
						$styleexist++;
					}
				}
				if($styleexist) {
					$narray[$i] = array(
						'styleid' => '',
						'available' => '',
						'name' => $entry,
						'directory' => './templates/'.$entry,
						'name' => $entry,
						'tplname' => $entry,
						'filemtime' => @filemtime($dir.'/'.$entry),
						'stylecount' => $styleexist
					);
					$i--;
				}
			}
		}

		uasort($narray, 'filemtimesort');
		$sarray += $narray;

		$stylelist = '';
		$i = 0;
		foreach($sarray as $id => $style) {
			$style['name'] = dhtmlspecialchars($style['name']);
			$isdefault = $id == $defaultid ? 'checked' : '';
			$available = $style['available'] ? 'checked' : NULL;
			$preview = file_exists($style['directory'].'/preview.jpg') ? $style['directory'].'/preview.jpg' : './images/admincp/stylepreview.gif';
			$stylelist .= ($i == 0 ? '<tr>' : '').
				'<td width="33%" '.($available ? 'style="background: #F2F9FD"' : '').'><table cellspacing="0" cellpadding="0" style="margin-left: 10px; width: 200px;"><tr><td style="width: 120px; text-align: center; border-top: none;">'.
				($id > 0 ? "<p style=\"margin-bottom: 2px;\">&nbsp;</p>".
				($available ? "<a href=\"$indexname?styleid=$id\" target=\"_blank\">" : '' )."<img src=\"$preview\" alt=\"$lang[preview]\"/></a>
				<p style=\"margin: 2px 0\"><span style=\"float: left; dispaly: inline; margin-left: 4px; width: 20px; height: 20px; background: ".($styleicons[$id] ? $styleicons[$id] : 'url(./images/admincp/transparent.gif)')."\">&nbsp;</span><input type=\"text\" class=\"txt\" name=\"namenew[$id]\" value=\"$style[name]\" size=\"30\" style=\"margin-right:0; width: 80px;\"></p>
				<p class=\"lightfont\">($style[tplname])</p></td><td style=\"padding-top: 17px; width: 80px; border-top: none; vertical-align: top;\">
				<p style=\"margin: 2px 0\">$lang[available] <input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$id]\" value=\"1\" $available></p>
				<p style=\"margin: 2px 0\">$lang[default] <input type=\"radio\" class=\"radio\" name=\"defaultnew\" value=\"$id\" $isdefault></p>
				<p style=\"margin: 2px 0\">$lang[styles_uninstall] ".($isdefault ? '<input class="checkbox" type="checkbox" disabled="disabled" />' : '<input class="checkbox" type="checkbox" name="delete[]" value="'.$id.'" />')."</p>
				<p style=\"margin: 8px 0 2px\"><a href=\"$BASESCRIPT?action=styles&operation=edit&id=$id\">$lang[edit]</a></p>
				<p style=\"margin: 2px 0\"><a href=\"$BASESCRIPT?action=styles&operation=export&id=$id\">$lang[export]</a></p>
				<p style=\"margin: 2px 0\"><a href=\"$BASESCRIPT?action=styles&operation=copy&id=$id\">$lang[copy]</a></p>" :
				"<p style=\"margin-bottom: 2px;\">&nbsp;</p>
				<img src=\"$preview\" />
				<p style=\"margin: 13px 0\" class=\"lightfont\">($style[tplname])</p></td><td style=\"padding-top: 17px; width: 80px; border-top: none; vertical-align: top;\">
				<p style=\"margin: 2px 0\"><a href=\"$BASESCRIPT?action=styles&operation=import&dir=$style[name]\">$lang[styles_install]</a></p>
				<p style=\"margin: 2px p\">$lang[styles_stylecount]$style[stylecount]</p>".
				($style['filemtime'] > $timestamp - 86400 ? '<p style=\"margin-bottom: 2px;\"><font color="red">New!</font></p>' : '')).
				"</td></tr></table></td>\n".($i == 3 ? '</tr>' : '');
			$i++;
			if($i == 3) {
				$i = 0;
			}
		}
		if($i > 0) {
			$stylelist .= str_repeat('<td></td>', 3 - $i);
		}

		shownav('style', 'styles_admin');
		showsubmenu('styles_admin', array(
			array('admin', 'styles', '1'),
			array('import', 'styles&operation=import', '0')
		));
		showtips('styles_admin_tips');
		showformheader('styles');
		showhiddenfields(array('updatecsscache' => 0));
		showtableheader('', 'tdhover');
		echo $stylelist;
		showtablefooter();
		showtableheader();
		echo '<tr><td>'.$lang['add_new'].'</td><td><input type="text" class="txt" name="newname" size="18"></td><td colspan="5">&nbsp;</td></tr>';
		showsubmit('stylesubmit', 'submit', 'del', '<input onclick="this.form.updatecsscache.value=1" type="submit" class="btn" name="stylesubmit" value="'.lang('styles_csscache_update').'">');
		showtablefooter();
		showformfooter();

	} else {

		if($updatecsscache) {
			updatecache('styles');
			cpmsg('csscache_update', $BASESCRIPT.'?action=styles', 'succeed');
		} else {

			if(is_numeric($defaultnew) && $defaultid != $defaultnew && isset($sarray[$defaultnew])) {
				$defaultid = $defaultnew;
				$db->query("UPDATE {$tablepre}settings SET value='$defaultid' WHERE variable='styleid'");
			}

			$availablenew[$defaultid] = 1;

			foreach($sarray as $id => $old) {
				$namenew[$id] = trim($namenew[$id]);
				$availablenew[$id] = $availablenew[$id] ? 1 : 0;
				if($namenew[$id] != $old['name'] || $availablenew[$id] != $old['available']) {
					$db->query("UPDATE {$tablepre}styles SET name='$namenew[$id]', available='$availablenew[$id]' WHERE styleid='$id'");
				}
			}

			if(!empty($delete) && is_array($delete)) {
				$did = array();
				foreach($delete as $id) {
					$id = intval($id);
					if($id == $defaultid) {
						cpmsg('styles_delete_invalid', '', 'error');
					} elseif($id != 1){
						$did[] = intval($id);
					}
				}
				if($did && ($ids = implodeids($did))) {
					$query = $db->query("SELECT templateid FROM {$tablepre}styles");
					$tplids = array();
					while($style = $db->fetch_array($query)) {
						$tplids[$style['templateid']] = $style['templateid'];
					}
					$db->query("DELETE FROM {$tablepre}styles WHERE styleid IN ($ids)");
					$db->query("DELETE FROM {$tablepre}stylevars WHERE styleid IN ($ids)");
					$db->query("UPDATE {$tablepre}members SET styleid='0' WHERE styleid IN ($ids)");
					$db->query("UPDATE {$tablepre}forums SET styleid='0' WHERE styleid IN ($ids)");
					$db->query("UPDATE {$tablepre}sessions SET styleid='$defaultid' WHERE styleid IN ($ids)");
					$query = $db->query("SELECT templateid FROM {$tablepre}styles");
					while($style = $db->fetch_array($query)) {
						unset($tplids[$style['templateid']]);
					}
					if($tplids) {
						$db->query("DELETE FROM {$tablepre}templates WHERE templateid IN (".implodeids($tplids).")");
					}
				}
			}

			if($newname) {
				$db->query("INSERT INTO {$tablepre}styles (name, templateid) VALUES ('$newname', '1')");
				$styleidnew = $db->insert_id();
				foreach(array_keys($predefinedvars) as $variable) {
					$substitute = isset($predefinedvars[$variable][2]) ? $predefinedvars[$variable][2] : '';;
					$db->query("INSERT INTO {$tablepre}stylevars (styleid, variable, substitute)
						VALUES ('$styleidnew', '$variable', '$substitute')");
				}
			}

			updatecache('settings');
			updatecache('styles');
			cpmsg('styles_edit_succeed', $BASESCRIPT.'?action=styles', 'succeed');
		}

	}

} elseif($operation == 'import') {

	if(!submitcheck('importsubmit') && !isset($dir)) {

		shownav('style', 'styles_admin');
		showsubmenu('styles_admin', array(
			array('admin', 'styles', '0'),
			array('import', 'styles&operation=import', '1')
		));
		showformheader('styles&operation=import', 'enctype');
		showtableheader('styles_import');
		showimportdata();
		showtablerow('', '', '<input class="checkbox" type="checkbox" name="ignoreversion" id="ignoreversion" value="1" /><label for="ignoreversion"> '.lang('styles_import_ignore_version').'</label>');
		showsubmit('importsubmit');
		showtablefooter();
		showformfooter();

	} else {

		require_once DISCUZ_ROOT.'./admin/importdata.func.php';
		$renamed = import_styles($ignoreversion, $dir);
		cpmsg(!empty($dir) ? 'styles_install_succeed' : ($renamed ? 'styles_import_succeed_renamed' : 'styles_import_succeed'), $BASESCRIPT.'?action=styles', 'succeed');
	}

} elseif($operation == 'copy') {

	$style = $db->fetch_first("SELECT * FROM {$tablepre}styles WHERE styleid='$id'");
	$style['name'] .= '_'.random(4);
	$db->query("INSERT INTO {$tablepre}styles (name, available, templateid)
			VALUES ('$style[name]', '$style[available]', '$style[templateid]')");
	$styleidnew = $db->insert_id();

	$query = $db->query("SELECT * FROM {$tablepre}stylevars WHERE styleid='$id'");
	while($stylevar = $db->fetch_array($query)) {
		$stylevar['substitute'] = addslashes($stylevar['substitute']);
		$db->query("INSERT INTO {$tablepre}stylevars (styleid, variable, substitute)
				VALUES ('$styleidnew', '$stylevar[variable]', '$stylevar[substitute]')");
	}

	updatecache('styles');
	updatecache('settings');
	cpmsg('styles_copy_succeed', $BASESCRIPT.'?action=styles', 'succeed');

} elseif($operation == 'edit') {

	if(!submitcheck('editsubmit')) {

		if(empty($id)) {
			$stylelist = "<select name=\"id\" style=\"width: 150px\">\n";
			$query = $db->query("SELECT styleid, name FROM {$tablepre}styles");
			while($style = $db->fetch_array($query)) {
				$stylelist .= "<option value=\"$style[styleid]\">$style[name]</option>\n";
			}
			$stylelist .= '</select>';
			cpmsg('styles_nonexistence', $BASESCRIPT.'?action=styles&operation=edit'.(!empty($highlight) ? "&highlight=$highlight" : ''), 'form', $stylelist);
		}

		$style = $db->fetch_first("SELECT name, templateid FROM {$tablepre}styles WHERE styleid='$id'");
		if(!$style) {
			cpmsg('undefined_action', '', 'error');
		}

		$stylecustom = '';
		$stylestuff = $existvars = array();
		$query = $db->query("SELECT * FROM {$tablepre}stylevars WHERE styleid='$id'");
		while($stylevar = $db->fetch_array($query)) {
			if(array_key_exists($stylevar['variable'], $predefinedvars)) {
				$stylestuff[$stylevar['variable']] = array('id' => $stylevar['stylevarid'], 'subst' => $stylevar['substitute']);
				$existvars[] = $stylevar['variable'];
			} else {
				$stylecustom .= showtablerow('', array('class="td25"', 'class="td24 bold"', 'class="td26"'), array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$stylevar[stylevarid]\">",
					'{'.strtoupper($stylevar['variable']).'}',
					"<textarea name=\"stylevar[$stylevar[stylevarid]]\" style=\"height: 45px\" cols=\"50\" rows=\"2\">$stylevar[substitute]</textarea>",
				), TRUE);
			}
		}
		if($diffvars = array_diff(array_keys($predefinedvars), $existvars)) {
			foreach($diffvars as $variable) {
				$db->query("INSERT INTO {$tablepre}stylevars (styleid, variable, substitute)
					VALUES ('$id', '$variable', '')");
				$stylestuff[$variable] = array('id' => $db->insert_id(), 'subst' => '');
			}
		}

		$tplselect = array();
		$query = $db->query("SELECT templateid, name FROM {$tablepre}templates");
		while($template = $db->fetch_array($query)) {
			$tplselect[] = array($template['templateid'], $template['name']);
		}

		$smileytypes = array();
		$query = $db->query("SELECT typeid, name FROM {$tablepre}imagetypes WHERE available='1'");
		while($type = $db->fetch_array($query)) {
			$smileytypes[] = array($type['typeid'], $type['name']);
		}

		$adv = !empty($adv) ? 1 : 0;

		shownav('style', 'styles_edit');
		showsubmenu('styles_admin', array(
			array('admin', 'styles', '0'),
			array('import', 'styles&operation=import', '0'),
			array(array('menu' => 'edit' , 'submenu' => array(
				array('styles_edit_simple', 'styles&operation=edit&id='.$id, !$adv),
				array('styles_edit_adv', 'styles&operation=edit&id='.$id.'&adv=1', $adv),
			)), '', 1)
		));

		if($adv) {

?>
<script type="text/JavaScript">
function imgpre_onload(obj) {
	if(!obj.complete) {
		setTimeout(function() {imgpre_resize(obj)}, 100);
	}
	imgpre_resize(obj);
}
function imgpre_resize(obj) {
	if(obj.width > 350) {
		obj.style.width = '350px';
	}
}
function imgpre_update(id, obj) {
	url = obj.value;
	if(url) {
		re = /^http:\/\//i;
		var matches = re.exec(url);
		if(matches == null) {
			url = ($('styleimgdir').value ? $('styleimgdir').value : ($('imgdir').value ? $('imgdir').value : 'images/default')) + '/' + url;
		}
		$('bgpre_' + id).style.backgroundImage = 'url(' + url + ')';
	} else {
		$('bgpre_' + id).style.backgroundImage = 'url(images/common/none.gif)';
	}
}
function imgpre_switch(id) {
	if($('bgpre_' + id).innerHTML == '') {
		url = $('bgpre_' + id).style.backgroundImage.substring(4, $('bgpre_' + id).style.backgroundImage.length - 1);
		$('bgpre_' + id).innerHTML = '<img onload="imgpre_onload(this)" src="' + url + '" />';
		$('bgpre_' + id).backgroundImage = $('bgpre_' + id).style.backgroundImage;
		$('bgpre_' + id).style.backgroundImage = '';
	} else {
		$('bgpre_' + id).style.backgroundImage = $('bgpre_' + id).backgroundImage;
		$('bgpre_' + id).innerHTML = '';
	}
}
</script>
<?

			showformheader("styles&operation=edit&id=$id&adv=1");
			showtableheader($lang['styles_edit'].' - '.$style['name'], 'nobottom');
			showsetting('styles_edit_name', 'namenew', $style['name'], 'text');
			showsetting('styles_edit_tpl', array('templateidnew', $tplselect), $style['templateid'], 'select');
			showsetting('styles_edit_smileytype', array("stylevar[{$stylestuff[stypeid][id]}]", $smileytypes), $stylestuff['stypeid']['subst'], 'select');
			showsetting('styles_edit_imgdir', '', '', '<input type="text" class="txt" name="stylevar['.$stylestuff['imgdir']['id'].']" id="imgdir" value="'.$stylestuff['imgdir']['subst'].'" />');
			showsetting('styles_edit_styleimgdir', '', '', '<input type="text" class="txt" name="stylevar['.$stylestuff['styleimgdir']['id'].']" id="styleimgdir" value="'.$stylestuff['styleimgdir']['subst'].'" />');
			showsetting('styles_edit_logo', "stylevar[{$stylestuff[boardimg][id]}]", $stylestuff['boardimg']['subst'], 'text');

			foreach($predefinedvars as $predefinedvar => $v) {
				if($v !== array()) {
					if(!empty($v[1])) {
						showtitle($v[1]);
					}
					$type = $v[0] == 1 ? 'text' : 'color';
					$extra = '';
					$comment = ($type == 'text' ? $lang['styles_edit_'.$predefinedvar.'_comment'] : $lang['styles_edit_hexcolor']).$lang['styles_edit_'.$predefinedvar.'_comment'];
					if(substr($predefinedvar, -7, 7) == 'bgcolor') {
						$stylestuff[$predefinedvar]['subst'] = explode(' ', $stylestuff[$predefinedvar]['subst']);
						$bgimg = $stylestuff[$predefinedvar]['subst'][1];
						$bgextra = implode(' ', array_slice($stylestuff[$predefinedvar]['subst'], 2));
						$stylestuff[$predefinedvar]['subst'] = $stylestuff[$predefinedvar]['subst'][0];
						$bgimgpre = $bgimg ? (preg_match('/^http:\/\//i', $bgimg) ? $bgimg : ($stylestuff['styleimgdir']['subst'] ? $stylestuff['styleimgdir']['subst'] : ($stylestuff['imgdir']['subst'] ? $stylestuff['imgdir']['subst'] : 'images/default')).'/'.$bgimg) : 'images/common/none.gif';
						$comment .= '<div id="bgpre_'.$stylestuff[$predefinedvar]['id'].'" onclick="imgpre_switch('.$stylestuff[$predefinedvar]['id'].')" style="background-image:url('.$bgimgpre.');cursor:pointer;float:right;width:350px;height:40px;overflow:hidden;border: 1px solid #ccc"></div>'.$lang['styles_edit_'.$predefinedvar.'_comment'].$lang['styles_edit_bg'];
						$extra = '<br /><input name="stylevarbgimg['.$stylestuff[$predefinedvar]['id'].']" value="'.$bgimg.'" onchange="imgpre_update('.$stylestuff[$predefinedvar]['id'].', this)" type="text" class="txt" style="margin:5px 0;" />'.
							'<br /><input name="stylevarbgextra['.$stylestuff[$predefinedvar]['id'].']" value="'.$bgextra.'" type="text" class="txt" />';
						$varcomment = ' {'.strtoupper($predefinedvar).'},{'.strtoupper(substr($predefinedvar, 0, -7)).'BGCODE}:';
					} else {
						$varcomment = ' {'.strtoupper($predefinedvar).'}:';
					}
					showsetting(lang('styles_edit_'.$predefinedvar).$varcomment, 'stylevar['.$stylestuff[$predefinedvar]['id'].']', $stylestuff[$predefinedvar]['subst'], $type, '', 0, $comment, $extra);
				}
			}
			showtablefooter();

			showtableheader('styles_edit_customvariable', 'notop');
			showsubtitle(array('', 'styles_edit_variable', 'styles_edit_subst'));
			echo $stylecustom;
			showtablerow('', array('class="td25"', 'class="td24 bold"', 'class="td26"'), array(
				lang('add_new'),
				'<input type="text" class="txt" name="newcvar">',
				'<textarea name="newcsubst" class="tarea" style="height: 45px" cols="50" rows="2"></textarea>'

			));

			showsubmit('editsubmit', 'submit', 'del', '<input type="button" class="btn" onclick="location.href=\''.$BASESCRIPT.'?action=styles&operation=edit&id='.$id.'\'" value="'.$lang['styles_edit_simple_switch'].'">');
			showtablefooter();
			showformfooter();

		} else {

			showformheader("styles&operation=edit&id=$id&adv=1");
			showtableheader($lang['styles_edit'].' - '.$style['name'], 'nobottom');
			showsetting('styles_edit_name', 'namenew', $style['name'], 'text');
			showsetting('styles_edit_tpl', array('templateidnew', $tplselect), $style['templateid'], 'select');
			showsetting('styles_edit_smileytype', array("stylevar[{$stylestuff[stypeid][id]}]", $smileytypes), $stylestuff['stypeid']['subst'], 'select');
			showsetting('styles_edit_imgdir', '', '', '<input type="text" class="txt" name="stylevar['.$stylestuff['imgdir']['id'].']" id="imgdir" value="'.$stylestuff['imgdir']['subst'].'" onchange="imgdirurl_update()" />');
			showsetting('styles_edit_styleimgdir', '', '', '<input type="text" class="txt" name="stylevar['.$stylestuff['styleimgdir']['id'].']" id="styleimgdir" value="'.$stylestuff['styleimgdir']['subst'].'" onchange="imgdirurl_update()" />');
			showtitle('styles_edit_visual');

?>
<tr><td colspan="2">

<table cellpadding="0" cellspacing="0" width="100%" id="previewbody"><tr><td valign="top" width="560" style="border: none;">

<style>
	#style_preview p, #style_preview ul, #style_preview li { margin: 0; padding: 0; list-style: none; }
	#sp_menu li { float: left; margin-left: 5px; padding: 0 10px; height: 25px; border-style: solid; border-width: 1px 1px 0; line-height: 25px; }
	#sp_wrap th, #sp_wrap td { border: 0; }
	#previewbody .colorwd { float: none; width: 21px; margin-left: 3px; }
		#previewbody div.color { float:left; width: 120px;}
</style>
<div id="style_preview" style="width: 550px; border: 1px solid #333; background: #0D2345 url(images/default/bodybg.gif) repeat-x scroll 0 90px; font-size: 12px; color: #444;">
	<div id="sp_header" style="position: relative; padding: 20px 10px 20px 10px; border-bottom: 1px solid #00B2E8; background: #00A2D2 url(images/default/header.gif) repeat-x scroll 0 100%;">
		<img id="sp_logo" src="images/default/logo.gif" />
		<div id="sp_umenu" style="position: absolute; right: 10px; top: 10px; color: #97F2FF;"><?=$discuz_userss?> | <?=$lang['styles_edit_visual_exit']?></div>
		<ul id="sp_menu" style="position: absolute; right: 10px; bottom: -1px; color: #666;">
			<li id="sp_menucurrent" style="border-color: #00B2E8; background: #1E4B7E; color: #FFF;"><?=$lang['styles_edit_visual_menu_current']?></li>
			<li id="sp_menuitem" style="border-color: #00B2E8; background: #EBF4FD url(images/default/mtabbg.gif) repeat-x scroll 0 100%;"><?=$lang['styles_edit_visual_menu']?></li>
		</ul>
	</div>
	<div id="sp_wrap" style="margin: 10px 10px 5px; border: 0px solid #000; background: #FFF url(images/default/sidebg.gif) repeat-y scroll 100% 0;">
		<div id="sp_content" style="padding: 10px; width: 350px; w\idth: 330px;">
			<div id="sp_backcolor" style="background: #F7F7F7;"><?=$lang['styles_edit_visual_text']?></div>
			<div id="sp_line" style="margin: 5px 0 0 0; height: 2px; background: #E6E7E1; line-height: 2px; overflow: hidden;"></div>
			<p id="sp_smalltext" style="padding: 5px 0 20px 0; text-align: right; color: #999; font-size: 0.83em;">---- Comsenz.Com</p>
			<table cellpadding="0" cellspacing="0">
<?

			function getcolor($colorid, $id) {
				return "<input id=\"c$colorid\" onclick=\"c{$colorid}_frame.location='images/admincp/getcolor.htm?c{$colorid}|{$id}';showMenu({'ctrlid':'c$colorid'})\" type=\"button\" class=\"colorwd\" value=\"\" style=\"background: $background\"><span id=\"c{$colorid}_menu\" style=\"display: none\"><iframe name=\"c{$colorid}_frame\" src=\"\" frameborder=\"0\" width=\"166\" height=\"186\" scrolling=\"no\"></iframe></span>";
			}
			echo '<tr><td width="100">'.$lang['styles_edit_visual_setting_commonborder'].'</td><td><input id="commonborder" name="stylevar['.$stylestuff['commonborder']['id'].']" value="'.$stylestuff['commonborder']['subst'].'" size="10" onchange="$(\'sp_line\').style.backgroundColor = this.value;updatecolorpreview(\'c18\', \'commonborder\')" onclick="setfocus(this)" />'.getcolor(18, 'commonborder').'</td></tr>';
			echo '<tr><td width="100">'.$lang['styles_edit_visual_setting_commonbg'].'</td><td><input id="commonbg" name="stylevar['.$stylestuff['commonbg']['id'].']" value="'.$stylestuff['commonbg']['subst'].'" size="10" onchange="$(\'sp_backcolor\').style.backgroundColor = this.value;updatecolorpreview(\'c19\', \'commonbg\')" onclick="setfocus(this)" />'.getcolor(19, 'commonbg').'</td></tr>';
			echo '<tr><td width="100">'.$lang['styles_edit_visual_setting_font'].'</td><td><input name="stylevar['.$stylestuff['font']['id'].']" value="'.$stylestuff['font']['subst'].'" size="15" onchange="$(\'sp_tabletext\').style.fontFamily = this.value" />'.
				' <input name="stylevar['.$stylestuff['fontsize']['id'].']" value="'.$stylestuff['fontsize']['subst'].'" size="5" onchange="$(\'sp_tabletext\').style.fontSize = this.value" /></td></tr>';
			echo '<tr><td width="100">'.$lang['styles_edit_visual_setting_smfont'].'</td><td><input name="stylevar['.$stylestuff['smfont']['id'].']" value="'.$stylestuff['smfont']['subst'].'" size="15" onchange="$(\'sp_smalltext\').style.fontFamily = this.value" />'.
				' <input name="stylevar['.$stylestuff['smfontsize']['id'].']" value="'.$stylestuff['smfontsize']['subst'].'" size="5" onchange="$(\'sp_smalltext\').style.fontSize = this.value" /></td></tr>';
			echo '<tr><td width="100" id="spt_tabletext">'.$lang['styles_edit_visual_setting_tabletext'].'</td><td><input id="tabletext" name="stylevar['.$stylestuff['tabletext']['id'].']" value="'.$stylestuff['tabletext']['subst'].'" size="10" onchange="$(\'spt_tabletext\').style.color = $(\'sp_tabletext\').style.color = this.value;updatecolorpreview(\'c20\', \'tabletext\')" onclick="setfocus(this)" />'.getcolor(20, 'tabletext').'</td></tr>';
			echo '<tr><td width="100" id="spt_midtext">'.$lang['styles_edit_visual_setting_midtext'].'</td><td><input id="midtext" name="stylevar['.$stylestuff['midtext']['id'].']" value="'.$stylestuff['midtext']['subst'].'" size="10" onchange="$(\'spt_midtext\').style.color = $(\'sp_midtext\').style.color = this.value;updatecolorpreview(\'c21\', \'midtext\')" onclick="setfocus(this)" />'.getcolor(21, 'midtext').'</td></tr>';
			echo '<tr><td width="100" id="spt_lighttext">'.$lang['styles_edit_visual_setting_lighttext'].'</td><td><input id="lighttext" name="stylevar['.$stylestuff['lighttext']['id'].']" value="'.$stylestuff['lighttext']['subst'].'" size="10" onchange="$(\'spt_lighttext\').style.color = $(\'sp_smalltext\').style.color = this.value;updatecolorpreview(\'c22\', \'lighttext\')" onclick="setfocus(this)" />'.getcolor(22, 'lighttext').'</td></tr>';
			echo '<tr><td width="100" id="spt_link">'.$lang['styles_edit_visual_setting_link'].'</td><td><input id="link" name="stylevar['.$stylestuff['link']['id'].']" value="'.$stylestuff['link']['subst'].'" size="10" onchange="$(\'spt_link\').style.color = $(\'sp_link\').style.color = this.value;updatecolorpreview(\'c23\', \'link\')" onclick="setfocus(this)" />'.getcolor(23, 'link').'</td></tr>';
			echo '<tr><td width="100" id="spt_highlightlink">'.$lang['styles_edit_visual_setting_highlightlink'].'</td><td><input id="highlightlink" name="stylevar['.$stylestuff['highlightlink']['id'].']" value="'.$stylestuff['highlightlink']['subst'].'" size="10" onchange="$(\'spt_highlightlink\').style.color = $(\'sp_link\').style.color = this.value;updatecolorpreview(\'c24\', \'highlightlink\')" onclick="setfocus(this)" />'.getcolor(24, 'highlightlink').'</td></tr>';
			echo '<tr><td width="100" id="spt_noticetext">'.$lang['styles_edit_visual_setting_noticetext'].'</td><td><input id="noticetext" name="stylevar['.$stylestuff['noticetext']['id'].']" value="'.$stylestuff['noticetext']['subst'].'" size="10" onchange="$(\'spt_noticetext\').style.color = $(\'sp_notice\').style.color = this.value;updatecolorpreview(\'c25\', \'noticetext\')" onclick="setfocus(this)" />'.getcolor(25, 'noticetext').'</td></tr>';

?>
			</table>
		</div>
	</div>
	<div id="sp_footer" style="margin: 0 auto 20px; color: #8691A2; text-align: center;">
		<?=$lang['styles_edit_visual_footer']?>
	</div>
</div>

</td><td valign="top" style="border: none;">

<script type="text/JavaScript">
var imgdirurl = '<? echo $imgdirurl = $stylestuff['styleimgdir']['subst'] ? $stylestuff['styleimgdir']['subst'] : ($stylestuff['imgdir']['subst'] ? $stylestuff['imgdir']['subst'] : 'images/default');?>/';
function imgdirurl_update() {
	imgdirurl = ($('styleimgdir').value ? $('styleimgdir').value : ($('imgdir').value ? $('imgdir').value : 'images/default')) + '/';
}
function updatecolorpreview(obj, objv) {
	$(obj).style.background = $(objv).value;
}
var colorfocus;
function setfocus(obj) {
	colorfocus = obj;
}

function setgcolor(color) {
	if(!colorfocus) {
		alert('<?=$lang['styles_edit_visual_selectcolorbox']?>');
		return;
	}
	colorfocus.value = color;
	var change = colorfocus.onchange.toString();
	if(change) {
		var start = change.indexOf('{');
		var end = change.lastIndexOf('}');
		s = change.substring(start + 1, end);
		s = s.replace(/this\.value/ig, "'" + colorfocus.value + "'");
		eval(s)
	}
}
</script>

<table cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed">
<?

			$copystyle = array(
				'wrapbg' => array('inputbg','dropmenubgcolor'),
				'commonborder' => array('specialborder', 'dropmenuborder', 'floatmaskbgcolor', 'inputborder', 'inputborderdarkcolor', 'contentseparate'),
				'commonbg' => array('specialbg', 'interleavecolor', 'floatbgcolor')
			);

			foreach($copystyle as $copysrc => $copydescs) {
				foreach($copydescs as $copydesc) {
					if($stylestuff[$copysrc]['subst'] == $stylestuff[$copydesc]['subst']) {
						echo '<input type="hidden" name="copyids['.$stylestuff[$copysrc]['id'].'][]" value="'.$stylestuff[$copydesc]['id'].'" />';
					}
				}
			}

			echo '<tr><td width="100">'.$lang['styles_edit_visual_setting_boardimg'].'</td><td><input name="stylevar['.$stylestuff['boardimg']['id'].']" value="'.$stylestuff['boardimg']['subst'].'" size="10" onchange="$(\'sp_logo\').src = this.value ? imgdirurl + this.value : \'images/common/none.gif\'" /></td></tr>';
			$stylestuff['headerbgcolor']['subst'] = explode(' ', $stylestuff['headerbgcolor']['subst']);
			$headerbgcolor = $stylestuff['headerbgcolor']['subst'][0];
			$headerbgimg = $stylestuff['headerbgcolor']['subst'][1];
			$headerbgextra = implode(' ', array_slice($stylestuff['headerbgcolor']['subst'], 2));

			echo '<tr><td>'.$lang['styles_edit_visual_setting_headerbgcolor'].'</td><td><div class="color"><input id="headerbgcolor" name="stylevar['.$stylestuff['headerbgcolor']['id'].']" value="'.$headerbgcolor.'" size="10" onchange="$(\'sp_header\').style.background = this.value + \' url(\' + imgdirurl + $(\'headerbgcolorimg\').value + \') \' + $(\'headerbgcolorextra\').value;updatecolorpreview(\'c1\', \'headerbgcolor\')" onclick="setfocus(this)" />'.getcolor(1, 'headerbgcolor').'</div>'.
				' <input id="headerbgcolorimg" name="stylevarbgimg['.$stylestuff['headerbgcolor']['id'].']" value="'.$headerbgimg.'" size="10" onchange="$(\'sp_header\').style.background = $(\'headerbgcolor\').value + \' url(\' + imgdirurl + this.value + \') \' + $(\'headerbgcolorextra\').value" />'.
				' <input id="headerbgcolorextra" name="stylevarbgextra['.$stylestuff['headerbgcolor']['id'].']" value="'.$headerbgextra.'" size="10" onchange="$(\'sp_header\').style.background = $(\'headerbgcolor\').value + \' url(\' + imgdirurl + $(\'headerbgcolorimg\').value + \') \' + this.value" /></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_setting_headerborder'].'</td><td><div class="color"><input id="headerbordercolor" name="stylevar['.$stylestuff['headerbordercolor']['id'].']" value="'.$stylestuff['headerbordercolor']['subst'].'" size="10" onchange="$(\'sp_menucurrent\').style.borderColor = this.value;$(\'sp_header\').style.borderBottom = $(\'headerborder\').value + \' solid \' + this.value;updatecolorpreview(\'c2\', \'headerbordercolor\')" onclick="setfocus(this)" />'.getcolor(2, 'headerbordercolor').'</div>'.
				' <input id="headerborder" name="stylevar['.$stylestuff['headerborder']['id'].']" value="'.$stylestuff['headerborder']['subst'].'" size="10" onchange="$(\'sp_header\').style.borderBottom = this.value + \' solid \' + $(\'headerbordercolor\').value" /></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_setting_headertext'].'</td><td><div class="color"><input id="headertext" name="stylevar['.$stylestuff['headertext']['id'].']" value="'.$stylestuff['headertext']['subst'].'" size="10" onchange="$(\'sp_umenu\').style.color = this.value;updatecolorpreview(\'c3\', \'headertext\')" onclick="setfocus(this)" />'.getcolor(3, 'headertext').'</div></td></tr>';

			echo '<tr><td>'.$lang['styles_edit_visual_setting_menuborder'].'</td><td><div class="color"><input id="menuborder" name="stylevar['.$stylestuff['menuborder']['id'].']" value="'.$stylestuff['menuborder']['subst'].'" size="10" onchange="$(\'sp_menuitem\').style.borderColor = this.value;updatecolorpreview(\'c4\', \'menuborder\')" onclick="setfocus(this)" />'.getcolor(4, 'menuborder').'</div></td></tr>';
			$stylestuff['menubgcolor']['subst'] = explode(' ', $stylestuff['menubgcolor']['subst']);
			$menubgcolor = $stylestuff['menubgcolor']['subst'][0];
			$menubgimg = $stylestuff['menubgcolor']['subst'][1];
			$menubgextra = implode(' ', array_slice($stylestuff['menubgcolor']['subst'], 2));
			echo '<tr><td>'.$lang['styles_edit_visual_setting_menubgcolor'].'</td><td><div class="color"><input id="menubgcolor" name="stylevar['.$stylestuff['menubgcolor']['id'].']" value="'.$menubgcolor.'" size="10" onchange="$(\'sp_menuitem\').style.background = this.value + \' url(\' + imgdirurl + $(\'menubgcolorimg\').value + \') \' + $(\'menubgcolorextra\').value;updatecolorpreview(\'c5\', \'menubgcolor\')" onclick="setfocus(this)" />'.getcolor(5, 'menubgcolor').'</div>'.
				' <input id="menubgcolorimg" name="stylevarbgimg['.$stylestuff['menubgcolor']['id'].']" value="'.$menubgimg.'" size="10" onchange="$(\'sp_menuitem\').style.background = $(\'menubgcolor\').value + \' url(\' + imgdirurl + this.value + \') \' + $(\'menubgcolorextra\').value;" />'.
				' <input id="menubgcolorextra" name="stylevarbgextra['.$stylestuff['menubgcolor']['id'].']" value="'.$menubgextra.'" size="10" onchange="$(\'sp_menuitem\').style.background = $(\'menubgcolor\').value + \' url(\' + imgdirurl + $(\'menubgcolorimg\').value + \') \' + this.value" /></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_setting_menutext'].'</td><td><div class="color"><input id="menutext" name="stylevar['.$stylestuff['menutext']['id'].']" value="'.$stylestuff['menutext']['subst'].'" size="10" onchange="$(\'sp_menu\').style.color = this.value;updatecolorpreview(\'c6\', \'menutext\')" onclick="setfocus(this)" />'.getcolor(6, 'menutext').'</div></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_setting_menuhover'].'</td><td><div class="color"><input id="menuhover" name="stylevar['.$stylestuff['menuhover']['id'].']" value="'.$stylestuff['menuhover']['subst'].'" size="10" onchange="$(\'sp_menucurrent\').style.backgroundColor = this.value;updatecolorpreview(\'c7\', \'menuhover\')" onclick="setfocus(this)" />'.getcolor(7, 'menuhover').'</div></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_setting_menuhovertext'].'</td><td><div class="color"><input id="menuhovertext" name="stylevar['.$stylestuff['menuhovertext']['id'].']" value="'.$stylestuff['menuhovertext']['subst'].'" size="10" onchange="$(\'sp_menucurrent\').style.color = this.value;updatecolorpreview(\'c8\', \'menuhovertext\')" onclick="setfocus(this)" />'.getcolor(8, 'menuhovertext').'</div></td></tr>';

			$stylestuff['bgcolor']['subst'] = explode(' ', $stylestuff['bgcolor']['subst']);
			$bgcolor = $stylestuff['bgcolor']['subst'][0];
			$bgcolorimg = $stylestuff['bgcolor']['subst'][1];
			$bgcolorextra = implode(' ', array_slice($stylestuff['bgcolor']['subst'], 2));
			echo '<tr><td>'.$lang['styles_edit_visual_setting_bgcolor'].'</td><td><div class="color"><input id="bgcolor" name="stylevar['.$stylestuff['bgcolor']['id'].']" value="'.$bgcolor.'" size="10" onchange="$(\'style_preview\').style.background = this.value + \' url(\' + imgdirurl + $(\'bgcolorimg\').value + \') \' + $(\'bgcolorextra\').value;updatecolorpreview(\'c9\', \'bgcolor\')" onclick="setfocus(this)" />'.getcolor(9, 'bgcolor').'</div>'.
				' <input id="bgcolorimg" name="stylevarbgimg['.$stylestuff['bgcolor']['id'].']" value="'.$bgcolorimg.'" size="10" onchange="$(\'style_preview\').style.background = $(\'bgcolor\').value + \' url(\' + imgdirurl + this.value + \') \' + $(\'bgcolorextra\').value" />'.
				' <input id="bgcolorextra" name="stylevarbgextra['.$stylestuff['bgcolor']['id'].']" value="'.$bgcolorextra.'" size="10" onchange="$(\'style_preview\').style.background = $(\'bgcolor\').value + \' url(\' + imgdirurl + $(\'bgcolorimg\').value + \') \' + this.value" /></td></tr>';

			$stylestuff['sidebgcolor']['subst'] = explode(' ', $stylestuff['sidebgcolor']['subst']);
			$sidebgcolor = $stylestuff['sidebgcolor']['subst'][0];
			$sidebgcolorimg = $stylestuff['sidebgcolor']['subst'][1];
			$sidebgcolorextra = implode(' ', array_slice($stylestuff['sidebgcolor']['subst'], 2));
			echo '<tr><td>'.$lang['styles_edit_visual_setting_sidebgcolor'].'</td><td><input id="sidebgcolorimg" name="stylevarbgimg['.$stylestuff['sidebgcolor']['id'].']" value="'.$sidebgcolorimg.'" size="10" onchange="$(\'sp_wrap\').style.backgroundImage = \'url(\' + imgdirurl + this.value + \')\'" /><input name="stylevar['.$stylestuff['sidebgcolor']['id'].']" type="hidden" value="'.$sidebgcolor.'"><input name="stylevarbgextra['.$stylestuff['sidebgcolor']['id'].']" type="hidden" value="'.$sidebgcolorextra.'"></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_setting_wrapwidth'].'</td><td><input name="stylevar['.$stylestuff['wrapwidth']['id'].']" value="'.$stylestuff['wrapwidth']['subst'].'" size="10" /></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_setting_wrapbg'].'</td><td><div class="color"><input id="wrapbg" name="stylevar['.$stylestuff['wrapbg']['id'].']" value="'.$stylestuff['wrapbg']['subst'].'" size="10" onchange="$(\'sp_wrap\').style.backgroundColor = this.value;updatecolorpreview(\'c10\', \'wrapbg\')" onclick="setfocus(this)" />'.getcolor(10, 'wrapbg').'</div></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_setting_wrapborder'].'</td><td><div class="color"><input id="wrapbordercolor" name="stylevar['.$stylestuff['wrapbordercolor']['id'].']" value="'.$stylestuff['wrapbordercolor']['subst'].'" size="10" onchange="$(\'sp_wrap\').style.border = $(\'wrapborder\').value + \' solid \' + this.value;updatecolorpreview(\'c11\', \'wrapbordercolor\')" onclick="setfocus(this)" />'.getcolor(11, 'wrapbordercolor').'</div>'.
				' <input id="wrapborder" name="stylevar['.$stylestuff['wrapborder']['id'].']" value="'.$stylestuff['wrapborder']['subst'].'" size="10" onchange="$(\'sp_wrap\').style.border = this.value + \' solid \' + $(\'wrapbordercolor\').value" /></td></tr>';

			echo '<tr><td>'.$lang['styles_edit_visual_setting_footertext'].'</td><td><div class="color"><input id="footertext" name="stylevar['.$stylestuff['footertext']['id'].']" value="'.$stylestuff['footertext']['subst'].'" size="10" onchange="$(\'sp_footer\').style.color = this.value;updatecolorpreview(\'c12\', \'footertext\')" onclick="setfocus(this)" />'.getcolor(12, 'footertext').'</div></td></tr>';
			echo '<tr><td>'.$lang['styles_edit_visual_getcolorfromimg'].'</td><td><input id="imgurl" size="10"> <a href="javascript:;" onclick="ajaxget(\''.$BASESCRIPT.'?action=styles&operation=getcolor&file=\' + $(\'imgurl\').value, \'colorlist\')">'.$lang['styles_edit_visual_getcolor'].'</a></td></tr>';
			echo '<tr><td></td><td><div id="colorlist"></div></td></tr>';

?>
</td><tr></table>

</td><tr></table>

<script>
var inps = $('previewbody').getElementsByTagName('INPUT');
for(i = 0;i < inps.length;i++) {
	if(inps[i].onchange) {
		var change = inps[i].onchange.toString();
		if(change) {
			var start = change.indexOf('{');
			var end = change.lastIndexOf('}');
			s = change.substring(start + 1, end);
			s = s.replace(/this\.value/ig, "'" + inps[i].value + "'");
			eval(s)
		}
	}
}
</script>
</td></tr>
<?

			showsubmit('editsubmit', 'submit', '', '<input type="button" class="btn" onclick="location.href=\''.$BASESCRIPT.'?action=styles&operation=edit&id='.$id.'&adv=1\'" value="'.$lang['styles_edit_adv_switch'].'">');
			showtablefooter();
			showformfooter();

		}

	} else {

		if($newcvar && $newcsubst) {
			if($db->result_first("SELECT COUNT(*) FROM {$tablepre}stylevars WHERE variable='$newcvar' AND styleid='$id'")) {
				cpmsg('styles_edit_variable_duplicate', '', 'error');
			} elseif(!preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/", $newcvar)) {
				cpmsg('styles_edit_variable_illegal', '', 'error');
			}
			$newcvar = strtolower($newcvar);
			$db->query("INSERT INTO {$tablepre}stylevars (styleid, variable, substitute)
				VALUES ('$id', '$newcvar', '$newcsubst')");
		}

		$db->query("UPDATE {$tablepre}styles SET name='$namenew', templateid='$templateidnew' WHERE styleid='$id'");
		foreach($stylevar as $varid => $substitute) {
			if(!empty($stylevarbgimg[$varid])) {
				$substitute .= ' '.$stylevarbgimg[$varid];
				if(!empty($stylevarbgextra[$varid])) {
					$substitute .= ' '.$stylevarbgextra[$varid];
				}
			}
			$substitute = @htmlspecialchars($substitute);
			$stylevarids = "'$varid'";
			if(!empty($copyids[$varid])) {
				$stylevarids .= ','.implodeids($copyids[$varid]);
			}
			$db->query("UPDATE {$tablepre}stylevars SET substitute='$substitute' WHERE stylevarid IN ($stylevarids) AND styleid='$id'");
		}

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM {$tablepre}stylevars WHERE stylevarid IN ($ids) AND styleid='$id'");
		}

		updatecache('styles');
		cpmsg('styles_edit_succeed', $BASESCRIPT.'?action=styles'.($newcvar && $newcsubst ? '&operation=edit&id='.$id : ''), 'succeed');

	}

} elseif($operation == 'getcolor') {

	$imginfo = @getimagesize($file);

	if(!$imginfo) {
		$inajax = 1;showmessage($lang['styles_edit_visual_getcolor_fileerror']);
	}

	$im = '';
	switch($imginfo['mime']) {
		case 'image/jpeg':
			$im = function_exists('imagecreatefromjpeg') ? imagecreatefromjpeg($file) : '';
			break;
		case 'image/gif':
			$im = function_exists('imagecreatefromgif') ? imagecreatefromgif($file) : '';
			break;
		case 'image/png':
			$im = function_exists('imagecreatefrompng') ? imagecreatefrompng($file) : '';
			break;
	}
	if(!$im || !function_exists('imageistruecolor') ||
		!function_exists('imagetruecolortopalette') ||
		!function_exists('imagecreatetruecolor') ||
		!function_exists('imagecopy') ||
		!function_exists('imagecolorstotal') ||
		!function_exists('imagecolorsforindex')) {
		$inajax = 1;showmessage($lang['styles_edit_visual_getcolor_nosupport']);
	}

	if(!imageistruecolor($im)) {
		$imt = imagecreatetruecolor($imginfo[0], $imginfo[1]);
		imagecopy($imt, $im, 0, 0, 0, 0, $imginfo[0], $imginfo[1]);
		$im = $imt;
	}
	imagetruecolortopalette($im, 1, 64);

	$colorn = imagecolorstotal($im);

	$colors = array();
	for($i = 0;$i < $colorn;$i++) {
		$rgb = imagecolorsforindex($im, $i);
		$color = sprintf('%02s', dechex($rgb['red'])).sprintf('%02s', dechex($rgb['green'])).sprintf('%02s', dechex($rgb['blue']));
		if($color != 'ffffff') {
			$colors[] = $color;
		}
	}

	$colors = array_unique($colors);
	sort($colors);

	include template('header_ajax');

	for($i = 0;$i < count($colors);$i++) {
		echo '<p onclick="setgcolor(\'#'.$colors[$i].'\')" style="float:left;width:20px;height:20px;cursor:pointer;background-color: #'.$colors[$i].'"> </p>';
	}

	include template('footer_ajax');
	exit;

}

?>