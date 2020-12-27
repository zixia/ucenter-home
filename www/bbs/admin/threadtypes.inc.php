<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: threadtypes.inc.php 21237 2009-11-23 03:02:28Z liulanbo $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!$operation) {

	if($special) {
		$special = 1;
		$navlang = 'threadtype_infotypes';
		$operation = 'type';
		$changetype = 'threadsorts';
	} else {
		$special = 0;
		$navlang = 'forums_edit_threadtypes';
		$changetype = 'threadtypes';
	}

	if(!submitcheck('typesubmit')) {

		$forumsarray = $fidsarray = array();
		$query = $db->query("SELECT f.fid, f.name, ff.$changetype FROM {$tablepre}forums f , {$tablepre}forumfields ff WHERE ff.$changetype<>'' AND f.fid=ff.fid");
		while($forum = $db->fetch_array($query)) {
			$forum[$changetype] = unserialize($forum[$changetype]);
			if(is_array($forum[$changetype]['types'])) {
				foreach($forum[$changetype]['types'] as $typeid => $name) {
					$forumsarray[$typeid][] = '<a href="'.$BASESCRIPT.'?action=forums&operation=edit&fid='.$forum['fid'].'&anchor=threadtypes">'.$forum['name'].'</a>';
					$fidsarray[$typeid][] = $forum['fid'];
				}
			}
		}

		if($special) {
			$typemodelopt = '';
			$query = $db->query("SELECT id, name FROM {$tablepre}typemodels ORDER BY displayorder");
			while($typemodel = $db->fetch_array($query)) {
				$typemodelopt .= "<option value=\"$typemodel[id]\" ".($typemodel['id'] == $threadtype['special'] ? 'selected="selected"' : '').">$typemodel[name]</option>";
			}
		}

		$threadtypes = '';
		$query = $db->query("SELECT * FROM {$tablepre}threadtypes WHERE ".($special ? "special!='0'" : "special='0'")." ORDER BY displayorder");
		while($type = $db->fetch_array($query)) {
			$threadtypes .= showtablerow('', array('class="td25"', 'class="td28"', '', 'class="td29"', 'title="'.lang('forums_threadtypes_forums_comment').'"', 'class="td25"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$type[typeid]\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[$type[typeid]]\" value=\"$type[displayorder]\">",
				"<input type=\"text\" class=\"txt\" size=\"15\" name=\"namenew[$type[typeid]]\" value=\"".dhtmlspecialchars($type['name'])."\">",
				"<input type=\"text\" class=\"txt\" size=\"30\" name=\"descriptionnew[$type[typeid]]\" value=\"$type[description]\">",
				is_array($forumsarray[$type['typeid']]) ? '<ul class="nowrap lineheight"><li>'.implode(',</li><li> ', $forumsarray[$type['typeid']])."</li></ul><input type=\"hidden\" name=\"fids[$type[typeid]]\" value=\"".implode(', ', $fidsarray[$type['typeid']])."\">" : '',
				$special ? "<a href=\"$BASESCRIPT?action=threadtypes&operation=sortdetail&sortid=$type[typeid]\" class=\"act nowrap\">$lang[detail]</a>" : ''
			), TRUE);
		}

?>
<script type="text/JavaScript">
var rowtypedata = [
	[
		[1, '', 'td25'],
		[1, '<input type="text" class="txt" name="newdisplayorder[]" size="2" value="">', 'td28'],
		[1, '<input type="text" class="txt" name="newname[]" size="15">'],
		[1, '<input type="text" class="txt" name="newdescription[]" size="30" value="">', 'td29'],
		[2, '']
	],
];
</script>
<?

		shownav('forum', $navlang);
		showsubmenu($navlang);
		!$special ? showtips('forums_edit_threadtypes_tips') : '';

		showformheader("threadtypes&");
		showhiddenfields(array('special' => $special));
		showtableheader('');
		showsubtitle(array('', 'display_order', 'name', 'description', 'forums_relation', ''));
		echo $threadtypes;

?>
<tr>
<td class="td25"></td>
<td colspan="4"><div><a href="###" onclick="addrow(this, 0)" class="addtr"><?=$lang['threadtype_infotypes_add']?></a></div></td>
<?

echo $special ? '<td>&nbsp;</td>' : '<td></td>';

?>
</tr>
<?
		showsubmit('typesubmit', 'submit', 'del');
		showformfooter();

	} else {

		$updatefids = $modifiedtypes = array();

		if(is_array($delete)) {

			if($deleteids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}typeoptionvars WHERE sortid IN ($deleteids)");
				$db->query("DELETE FROM {$tablepre}tradeoptionvars WHERE sortid IN ($deleteids)");
				$db->query("DELETE FROM {$tablepre}typevars WHERE sortid IN ($deleteids)");
				$db->query("DELETE FROM {$tablepre}threadtypes WHERE typeid IN ($deleteids) AND special='$special'");
			}

			if($special == 1) {
				foreach($delete as $sortid) {
					$db->query("DROP TABLE IF EXISTS {$tablepre}optionvalue$sortid");
				}
			}

			if($deleteids && $db->affected_rows()) {
				$db->query("UPDATE {$tablepre}threads SET typeid='0' WHERE typeid IN ($deleteids)");
				foreach($delete as $id) {
					if(is_array($namenew) && isset($namenew[$id])) {
						unset($namenew[$id]);
					}
					if(!empty($fids[$id])) {
						foreach(explode(',', $fids[$id]) as $fid) {
							if($fid = intval($fid)) {
								$updatefids[$fid]['deletedids'][] = intval($id);
							}
						}
					}
				}
			}
		}

		if(is_array($namenew) && $namenew) {
			foreach($namenew as $typeid => $val) {
				$db->query("UPDATE {$tablepre}threadtypes SET name='".trim($namenew[$typeid])."', description='".dhtmlspecialchars(trim($descriptionnew[$typeid]))."', displayorder='$displayordernew[$typeid]', special='$special' WHERE typeid='$typeid'");
				if($db->affected_rows()) {
					$modifiedtypes[] = $typeid;
				}
			}

			if($modifiedtypes = array_unique($modifiedtypes)) {
				foreach($modifiedtypes as $id) {
					if(!empty($fids[$id])) {
						foreach(explode(',', $fids[$id]) as $fid) {
							if($fid = intval($fid)) {
								$updatefids[$fid]['modifiedids'][] = $id;
							}
						}
					}
				}
			}
		}

		if($updatefids) {
			$query = $db->query("SELECT fid, $changetype FROM {$tablepre}forumfields WHERE fid IN (".implodeids(array_keys($updatefids)).") AND $changetype<>''");
			while($forum = $db->fetch_array($query)) {
				$fid = $forum['fid'];
				$forum[$changetype] = unserialize($forum[$changetype]);
				if($updatefids[$fid]['deletedids']) {
					foreach($updatefids[$fid]['deletedids'] as $id) {
						unset($forum[$changetype]['types'][$id], $forum[$changetype]['flat'][$id], $forum[$changetype]['selectbox'][$id]);
					}
				}
				if($updatefids[$fid]['modifiedids']) {
					foreach($updatefids[$fid]['modifiedids'] as $id) {
						if(isset($forum[$changetype]['types'][$id])) {
							$namenew[$id] = trim($namenew[$id]);
							$forum[$changetype]['types'][$id] = $namenew[$id];
							if(isset($forum[$changetype]['selectbox'][$id])) {
								$forum[$changetype]['selectbox'][$id] = $namenew[$id];
							} else {
								$forum[$changetype]['flat'][$id] = $namenew[$id];
							}
						}
					}
				}
				$db->query("UPDATE {$tablepre}forumfields SET $changetype='".addslashes(serialize($forum[$changetype]))."' WHERE fid='$fid'");
			}
		}

		if(is_array($newname)) {
			foreach($newname as $key => $value) {
				if($newname1 = trim($value)) {
					$query = $db->query("SELECT typeid FROM {$tablepre}threadtypes WHERE name='$newname1'");
					if($db->num_rows($query)) {
						cpmsg('forums_threadtypes_duplicate', '', 'error');
					}
					$db->query("INSERT INTO	{$tablepre}threadtypes (name, description, displayorder, special) VALUES
							('$newname1', '".dhtmlspecialchars(trim($newdescription[$key]))."', '$newdisplayorder[$key]', '$special')");
				}
			}
		}

		cpmsg('forums_threadtypes_succeed', $BASESCRIPT.'?action=threadtypes&special='.$special, 'succeed');

	}

} elseif($operation == 'typeoption') {

	if(!submitcheck('typeoptionsubmit')) {

		$classid = $classid ? intval($classid) : $db->result_first("SELECT * FROM {$tablepre}typeoptions WHERE classid='0' ORDER BY displayorder LIMIT 1");
		$classoptions = array();
		$query = $db->query("SELECT * FROM {$tablepre}typeoptions WHERE classid='0' ORDER BY displayorder");
		while($option = $db->fetch_array($query)) {
			$classoptions[] = array($option[title], "threadtypes&operation=typeoption&classid=$option[optionid]", $classid == $option[optionid]);
		}

		if($classid) {
			if(!$typetitle = $db->result_first("SELECT title FROM {$tablepre}typeoptions WHERE optionid='$classid'")) {
				cpmsg('threadtype_infotypes_noexist', $BASESCRIPT.'?action=threadtypes', 'error');
			}

			$typeoptions = '';
			$query = $db->query("SELECT * FROM {$tablepre}typeoptions WHERE classid='$classid' ORDER BY displayorder");
			while($option = $db->fetch_array($query)) {
				$option['type'] = $lang['threadtype_edit_vars_type_'. $option['type']];
				$typeoptions .= showtablerow('', array('class="td25"', 'class="td28"'), array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$option[optionid]\">",
					"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayorder[$option[optionid]]\" value=\"$option[displayorder]\">",
					"<input type=\"text\" class=\"txt\" size=\"15\" name=\"title[$option[optionid]]\" value=\"".dhtmlspecialchars($option['title'])."\">",
					"$option[identifier]<input type=\"hidden\" name=\"identifier[$option[optionid]]\" value=\"$option[identifier]\">",
					$option['type'],
					"<a href=\"$BASESCRIPT?action=threadtypes&operation=optiondetail&optionid=$option[optionid]\" class=\"act\">$lang[detail]</a>"
				), TRUE);
			}
		}

		echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[
			[1, '', 'td25'],
			[1, '<input type="text" class="txt" size="2" name="newdisplayorder[]" value="0">', 'td28'],
			[1, '<input type="text" class="txt" size="15" name="newtitle[]">'],
			[1, '<input type="text" class="txt" size="15" name="newidentifier[]">'],
			[1, '<select name="newtype[]"><option value="number">$lang[threadtype_edit_vars_type_number]</option><option value="text" selected>$lang[threadtype_edit_vars_type_text]</option><option value="textarea">$lang[threadtype_edit_vars_type_textarea]</option><option value="radio">$lang[threadtype_edit_vars_type_radio]</option><option value="checkbox">$lang[threadtype_edit_vars_type_checkbox]</option><option value="select">$lang[threadtype_edit_vars_type_select]</option><option value="calendar">$lang[threadtype_edit_vars_type_calendar]</option><option value="email">$lang[threadtype_edit_vars_type_email]</option><option value="image">$lang[threadtype_edit_vars_type_image]</option><option value="url">$lang[threadtype_edit_vars_type_url]</option></select>'],
			[1, '']
		],
	];
</script>
EOT;
		shownav('forum', 'threadtype_infotypes_option');
		showsubmenu('threadtype_cat_manage', $classoptions);
		showformheader("threadtypes&operation=typeoption&typeid=$typeid");
		showhiddenfields(array('classid' => $classid));
		showtableheader();

		showsubtitle(array('', 'display_order', 'name', 'threadtype_variable', 'threadtype_type', ''));
		echo $typeoptions;
		echo '<tr><td></td><td colspan="5"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['threadtype_infotypes_add_option'].'</a></div></td></tr>';
		showsubmit('typeoptionsubmit', 'submit', 'del');

		showtablefooter();
		showformfooter();

	} else {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM {$tablepre}typeoptions WHERE optionid IN ($ids)");
			$db->query("DELETE FROM {$tablepre}typevars WHERE optionid IN ($ids)");
		}

		if(is_array($title)) {
			foreach($title as $id => $val) {
				$db->query("UPDATE {$tablepre}typeoptions SET displayorder='$displayorder[$id]', title='$title[$id]', identifier='$identifier[$id]' WHERE optionid='$id'");
			}
		}

		if(is_array($newtitle)) {
			foreach($newtitle as $key => $value) {
				$newtitle1 = dhtmlspecialchars(trim($value));
				$newidentifier1 = trim($newidentifier[$key]);
				if($newtitle1 && $newidentifier1) {
					$query = $db->query("SELECT optionid FROM {$tablepre}typeoptions WHERE identifier='$newidentifier1' LIMIT 1");
					if($db->num_rows($query) || strlen($newidentifier1) > 40  || !ispluginkey($newidentifier1)) {
						cpmsg('threadtype_infotypes_optionvariable_invalid', '', 'error');
					}
					$db->query("INSERT INTO {$tablepre}typeoptions (classid, displayorder, title, identifier, type)
						VALUES ('$classid', '$newdisplayorder[$key]', '$newtitle1', '$newidentifier1', '$newtype[$key]')");
				} elseif($newtitle1 && !$newidentifier1) {
					cpmsg('threadtype_infotypes_option_invalid', $BASESCRIPT.'?action=threadtypes&operation=typeoption&classid='.$classid, 'error');
				}
			}
		}
		updatecache('threadsorts');
		cpmsg('threadtype_infotypes_succeed', $BASESCRIPT.'?action=threadtypes&operation=typeoption&classid='.$classid, 'succeed');

	}

} elseif($operation == 'optiondetail') {

	$option = $db->fetch_first("SELECT * FROM {$tablepre}typeoptions WHERE optionid='$optionid'");
	if(!$option) {
		cpmsg('undefined_action', '', 'error');
	}

	if(!submitcheck('editsubmit')) {

		shownav('forum', 'threadtype_infotypes_option');
		showsubmenu('threadtype_infotypes_option');

		$typeselect = '<select name="typenew" onchange="var styles, key;styles=new Array(\'number\',\'text\',\'radio\', \'checkbox\', \'textarea\', \'select\', \'image\'); for(key in styles) {var obj=$(\'style_\'+styles[key]); obj.style.display=styles[key]==this.options[this.selectedIndex].value?\'\':\'none\';}">';
		foreach(array('number', 'text', 'radio', 'checkbox', 'textarea', 'select', 'calendar', 'email', 'url', 'image') as $type) {
			$typeselect .= '<option value="'.$type.'" '.($option['type'] == $type ? 'selected' : '').'>'.$lang['threadtype_edit_vars_type_'.$type].'</option>';
		}
		$typeselect .= '</select>';

		$option['rules'] = unserialize($option['rules']);

		showformheader("threadtypes&operation=optiondetail&optionid=$optionid");
		showtableheader();
		showtitle('threadtype_infotypes_option_config');
		showsetting('name', 'titlenew', $option['title'], 'text');
		showsetting('threadtype_variable', 'identifiernew', $option['identifier'], 'text');
		showsetting('type', '', '', $typeselect);
		showsetting('threadtype_edit_desc', 'descriptionnew', $option['description'], 'textarea');
		showsetting('threadtype_unit', 'unitnew', $option['unit'], 'text');

		showtagheader('tbody', "style_number", $option['type'] == 'number');
		showtitle('threadtype_edit_vars_type_number');
		showsetting('threadtype_edit_maxnum', 'rules[number][maxnum]', $option['rules']['maxnum'], 'text');
		showsetting('threadtype_edit_minnum', 'rules[number][minnum]', $option['rules']['minnum'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', "style_text", $option['type'] == 'text');
		showtitle('threadtype_edit_vars_type_text');
		showsetting('threadtype_edit_textmax', 'rules[text][maxlength]', $option['rules']['maxlength'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', "style_textarea", $option['type'] == 'textarea');
		showtitle('threadtype_edit_vars_type_textarea');
		showsetting('threadtype_edit_textmax', 'rules[textarea][maxlength]', $option['rules']['maxlength'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', "style_select", $option['type'] == 'select');
		showtitle('threadtype_edit_vars_type_select');
		showsetting('threadtype_edit_choices', 'rules[select][choices]', $option['rules']['choices'], 'textarea');
		showtagfooter('tbody');

		showtagheader('tbody', "style_radio", $option['type'] == 'radio');
		showtitle('threadtype_edit_vars_type_radio');
		showsetting('threadtype_edit_choices', 'rules[radio][choices]', $option['rules']['choices'], 'textarea');
		showtagfooter('tbody');

		showtagheader('tbody', "style_checkbox", $option['type'] == 'checkbox');
		showtitle('threadtype_edit_vars_type_checkbox');
		showsetting('threadtype_edit_choices', 'rules[checkbox][choices]', $option['rules']['choices'], 'textarea');
		showtagfooter('tbody');

		showtagheader('tbody', "style_image", $option['type'] == 'image');
		showtitle('threadtype_edit_vars_type_image');
		showsetting('threadtype_edit_images_weight', 'rules[image][maxwidth]', $option['rules']['maxwidth'], 'text');
		showsetting('threadtype_edit_images_height', 'rules[image][maxheight]', $option['rules']['maxheight'], 'text');
		showtagfooter('tbody');

		showsubmit('editsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$titlenew = trim($titlenew);
		if(!$titlenew || !$identifiernew) {
			cpmsg('threadtype_infotypes_option_invalid', '', 'error');
		}

		$query = $db->query("SELECT optionid FROM {$tablepre}typeoptions WHERE identifier='$identifiernew' AND optionid!='$optionid' LIMIT 1");
		if($db->num_rows($query) || strlen($identifiernew) > 40  || !ispluginkey($identifiernew)) {
			cpmsg('threadtype_infotypes_optionvariable_invalid', '', 'error');
		}

		$db->query("UPDATE {$tablepre}typeoptions SET title='$titlenew', description='$descriptionnew', identifier='$identifiernew', type='$typenew', unit='$unitnew', rules='".addslashes(serialize($rules[$typenew]))."' WHERE optionid='$optionid'");

		updatecache('threadsorts');
		cpmsg('threadtype_infotypes_option_succeed', $BASESCRIPT.'?action=threadtypes&operation=typeoption', 'succeed');
	}

} elseif($operation == 'sortdetail') {

	if(!submitcheck('sortdetailsubmit') && !submitcheck('sortpreviewsubmit')) {

		$threadtype = $db->fetch_first("SELECT name, template, stemplate, modelid, expiration FROM {$tablepre}threadtypes WHERE typeid='$sortid'");
		$threadtype['modelid'] = isset($modelid) ? intval($modelid) : $threadtype['modelid'];

		$typemodelopt = '';
		$existoption = $showoption = array();
		$query = $db->query("SELECT id, name, options, customoptions FROM {$tablepre}typemodels ORDER BY displayorder");
		while($typemodel = $db->fetch_array($query)) {
			if($typemodel['id'] == $threadtype['modelid']) {
				foreach(explode("\t", $typemodel['customoptions']) as $id) {
					$existoption[$id] = 0;
				}

				foreach(explode("\t", $typemodel['options']) as $id) {
					$existoption[$id] = 1;
				}
			}
			$typemodelopt .= "<option value=\"$typemodel[id]\" ".($typemodel['id'] == $threadtype['modelid'] ? 'selected="selected"' : '').">$typemodel[name]</option>";
		}

		$sortoptions = $jsoptionids = '';
		$query = $db->query("SELECT t.optionid, t.displayorder, t.available, t.required, t.unchangeable, t.search, t.subjectshow, tt.title, tt.type, tt.identifier
			FROM {$tablepre}typevars t, {$tablepre}typeoptions tt
			WHERE t.sortid='$sortid' AND t.optionid=tt.optionid ORDER BY t.displayorder");
		while($option = $db->fetch_array($query)) {
			$jsoptionids .= "optionids.push($option[optionid]);\r\n";
			$optiontitle[$option['identifier']] = $option['title'];
			$showoption[$option['optionid']]['optionid'] = $option['optionid'];
			$showoption[$option['optionid']]['title'] = $option['title'];
			$showoption[$option['optionid']]['type'] = $lang['threadtype_edit_vars_type_'. $option['type']];
			$showoption[$option['optionid']]['identifier'] = $option['identifier'];
			$showoption[$option['optionid']]['displayorder'] = $option['displayorder'];
			$showoption[$option['optionid']]['available'] = $option['available'];
			$showoption[$option['optionid']]['required'] = $option['required'];
			$showoption[$option['optionid']]['unchangeable'] = $option['unchangeable'];
			$showoption[$option['optionid']]['search'] = $option['search'];
			$showoption[$option['optionid']]['subjectshow'] = $option['subjectshow'];
		}

		if($existoption && is_array($existoption)) {
			$optionids = $comma = '';
			foreach($existoption as $optionid => $val) {
				$optionids .= $comma.$optionid;
				$comma = '\',\'';
			}
			$query = $db->query("SELECT * FROM {$tablepre}typeoptions WHERE optionid IN ('$optionids')");
			while($option = $db->fetch_array($query)) {
				$showoption[$option['optionid']]['optionid'] = $option['optionid'];
				$showoption[$option['optionid']]['title'] = $option['title'];
				$showoption[$option['optionid']]['type'] = $lang['threadtype_edit_vars_type_'. $option['type']];
				$showoption[$option['optionid']]['identifier'] = $option['identifier'];
				$showoption[$option['optionid']]['required'] = $existoption[$option['optionid']];
				$showoption[$option['optionid']]['available'] = 1;
				$showoption[$option['optionid']]['unchangeable'] = 0;
				$showoption[$option['optionid']]['model'] = 1;
			}
		}

		$searchtitle = $searchvalue = $searchunit = array();
		foreach($showoption as $optionid => $option) {
			$sortoptions .= showtablerow('id="optionid'.$optionid.'"', array('class="td25"', 'class="td28 td23"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$option[optionid]\" ".($option['model'] ? 'disabled' : '').">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayorder[$option[optionid]]\" value=\"$option[displayorder]\">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"available[$option[optionid]]\" value=\"1\" ".($option['available'] ? 'checked' : '')." ".($option['model'] ? 'disabled' : '').">",
				dhtmlspecialchars($option['title']),
				$option['type'],
				"<input class=\"checkbox\" type=\"checkbox\" name=\"required[$option[optionid]]\" value=\"1\" ".($option['required'] ? 'checked' : '')." ".($option['model'] ? 'disabled' : '').">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"unchangeable[$option[optionid]]\" value=\"1\" ".($option['unchangeable'] ? 'checked' : '').">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"search[$option[optionid]]\" value=\"1\" ".($option['search'] ? 'checked' : '').">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"subjectshow[$option[optionid]]\" value=\"1\" ".($option['subjectshow'] ? 'checked' : '').">",
				"<a href=\"###\" onclick=\"insertvar('$option[identifier]', 'typetemplate', 'message');doane(event);return false;\" class=\"act\">".$lang['threadtype_infotypes_add_template']."</a>",
				"<a href=\"###\" onclick=\"insertvar('$option[identifier]', 'stypetemplate', 'subject');doane(event);return false;\" class=\"act\">".$lang['threadtype_infotypes_add_stemplate']."</a>",
				"<a href=\"$BASESCRIPT?action=threadtypes&operation=optiondetail&optionid=$option[optionid]\" class=\"act\">".$lang['edit']."</a>"
			), TRUE);
			$searchtitle[] = '/{('.$option['identifier'].')}/e';
			$searchvalue[] = '/\[('.$option['identifier'].')value\]/e';
			$searchunit[] = '/\[('.$option['identifier'].')unit\]/e';
		}

		if($threadtype['template']) {
			$previewtemplate = preg_replace($searchtitle, "showoption('\\1', 'title')", $threadtype['template']);
			$previewtemplate = preg_replace($searchvalue, "showoption('\\1', 'value')", $previewtemplate);
			$previewtemplate = preg_replace($searchunit, "showoption('\\1', 'unit')", $previewtemplate);
		}

		if($threadtype['stemplate']) {
			$previewstemplate = preg_replace($searchtitle, "showoption('\\1', 'title')", $threadtype['stemplate']);
			$previewstemplate = preg_replace($searchvalue, "showoption('\\1', 'value')", $previewstemplate);
			$previewstemplate = preg_replace($searchunit, "showoption('\\1', 'unit')", $previewstemplate);
		}

		shownav('forum', 'forums_edit_threadsorts');
		showsubmenu('forums_edit_threadsorts');
		showtips('forums_edit_threadsorts_tips');

		showformheader("threadtypes&operation=sortdetail&sortid=$sortid");
		showtableheader('threadtype_models', 'nobottom');
		showsetting('threadtype_models_select', '', '', '<select name="modelid" onchange="window.location=\''.$BASESCRIPT.'?action=threadtypes&operation=sortdetail&sortid='.$sortid.'&amp;modelid=\'+this.options[this.selectedIndex].value"><option value="0">'.$lang['none'].'</option>'.$typemodelopt.'</select>');
		showsetting('threadtype_infotypes_validity', 'typeexpiration', $threadtype['expiration'], 'radio');
		showtablefooter();

		showtableheader("$threadtype[name] - $lang[threadtype_infotypes_add_option]", 'noborder fixpadding');
		showtablerow('', 'id="classlist"', '');
		showtablerow('', 'id="optionlist"', '');
		showtablefooter();

		showtableheader("$threadtype[name] - $lang[threadtype_infotypes_exist_option]", 'noborder fixpadding', 'id="sortlist"');
		showsubtitle(array('<input type="checkbox" name="chkall" id="chkall" class="checkbox" onclick="checkAll(\'prefix\', this.form,\'delete\')" /><label for="chkall">'.lang('del').'</label>', 'display_order', 'available', 'name', 'type', 'required', 'unchangeable', 'threadtype_infotypes_search', 'threadtype_infotypes_show', '', '', ''));
		echo $sortoptions;
		showtablefooter();

?>

<a name="template"></a>
<div class="colorbox">
<h4 style="margin-bottom:15px;"><?=$threadtype['name']?> - <?=$lang['threadtype_infotypes_template']?></h4>
<textarea cols="100" rows="5" id="typetemplate" name="typetemplate" style="width: 95%;" onkeyup="textareasize(this)"><?=$threadtype['template']?></textarea>
<br /><br />
<h4 style="margin-bottom:15px;"><?=$threadtype['name']?> - <?=$lang['threadtype_infotypes_stemplate']?></h4>
<textarea cols="100" rows="5" id="stypetemplate" name="stypetemplate" style="width: 95%;" onkeyup="textareasize(this)"><?=$threadtype['stemplate']?></textarea>
<br /><br />
<b><?=$lang['threadtype_infotypes_template']?>:</b>
<ul class="tpllist"><?=$lang['threadtype_infotypes_template_tips']?></ul>
<?php
	if($previewtemplate) {
		echo '<fieldset style="margin:1em 0; padding:1em 1.5em;"><legend><b>'.$lang['threadtype_infotypes_template_preview'].':</b></legend>';
		echo $previewtemplate;
		echo '</fieldset>';
	}

	if($previewstemplate) {
		echo '<fieldset style="margin:1em 0; padding:1em 1.5em;"><legend><b>'.$lang['threadtype_infotypes_stemplate_preview'].':</b></legend>';
		echo $previewstemplate;
		echo '</fieldset>';
	}
?>

<input type="submit" class="btn" name="sortdetailsubmit" value="<?=$lang['submit']?>"> &nbsp; <input type="submit" class="btn" name="sortpreviewsubmit" value="<?=$lang['threadtype_infotypes_template_preview']?>">
</div>

</form>
<script type="text/JavaScript">
	var optionids = new Array();
	<?=$jsoptionids?>
	function insertvar(text, focusarea, location) {
		$(focusarea).focus();
		selection = document.selection;
		if(selection && selection.createRange) {
			var sel = selection.createRange();
			sel.text = location == 'message' ? '<li><b>{' + text + '}</b>: [' + text + 'value] [' + text + "unit]</li>\r\n" : '{' + text + '}: [' + text + 'value] [' + text + 'unit]';
			sel.moveStart('character', -strlen(text));
		} else {
			$(focusarea).value += location == 'message' ? '<li><b>{' + text + '}</b>: [' + text + 'value] [' + text + "unit]</li>\r\n" : '{' + text + '}: [' + text + 'value] [' + text + 'unit]';
		}
	}

	function checkedbox() {
		var tags = $('optionlist').getElementsByTagName('input');
		for(var i=0; i<tags.length; i++) {
			if(in_array(tags[i].value, optionids)) {
				tags[i].checked = true;
			}
		}
	}
	function insertoption(optionid) {
		var x = new Ajax();
		x.optionid = optionid;
		x.get('<?=$BASESCRIPT?>?action=threadtypes&operation=sortlist&inajax=1&optionid=' + optionid, function(s, x) {
			if(!in_array(x.optionid, optionids)) {
				var div = document.createElement('div');
				div.style.display = 'none';
				$('append_parent').appendChild(div);
				div.innerHTML = '<table>' + s + '</table>';
				var tr = div.getElementsByTagName('tr');
				var trs = $('sortlist').getElementsByTagName('tr');
				tr[0].id = 'optionid' + optionid;
				trs[trs.length - 1].parentNode.appendChild(tr[0]);
				$('append_parent').removeChild(div);
				optionids.push(x.optionid);
			} else {
				$('optionid' + x.optionid).parentNode.removeChild($('optionid' + x.optionid));
				for(var i=0; i<optionids.length; i++) {
					if(optionids[i] == x.optionid) {
						optionids[i] = 0;
					}
				}
			}
		});
	}
</script>
<script type="text/JavaScript">ajaxget('<?=$BASESCRIPT?>?action=threadtypes&operation=classlist', 'classlist');</script>
<script type="text/JavaScript">ajaxget('<?=$BASESCRIPT?>?action=threadtypes&operation=optionlist&sortid=<?=$sortid?>', 'optionlist', '', '', '', checkedbox);</script>
<?

	} else {

		$db->query("UPDATE {$tablepre}threadtypes SET special='1', modelid='".intval($modelid)."', template='$typetemplate', stemplate='$stypetemplate', expiration='$typeexpiration' WHERE typeid='$sortid'");

		if(submitcheck('sortdetailsubmit')) {

			$orgoption = $orgoptions = $addoption = array();
			$query = $db->query("SELECT optionid FROM {$tablepre}typevars WHERE sortid='$sortid'");
			while($orgoption = $db->fetch_array($query)) {
				$orgoptions[] = $orgoption['optionid'];
			}

			if(intval($modelid)) {
				$modelopt = $db->fetch_first("SELECT options, customoptions FROM {$tablepre}typemodels WHERE id='$modelid'");
				if($modelopt['customoptions']) {
					foreach(explode("\t", $modelopt['customoptions']) as $id) {
						$addoption[$id] = $required[$id] = 0;
						$available[$id] = 1;
					}
				}

				if($modelopt['options']) {
					foreach(explode("\t", $modelopt['options']) as $id) {
						$addoption[$id] = $available[$id] = $required[$id] = 1;
					}
				}
			}

			$addoption = $addoption ? (array)$addoption + (array)$displayorder : (array)$displayorder;

			@$newoptions = array_keys($addoption);

			if(empty($addoption)) {
				cpmsg('threadtype_infotypes_invalid', '', 'error');
			}

			@$delete = array_merge((array)$delete, array_diff($orgoptions, $newoptions));

			if($delete) {
				if($ids = implodeids($delete)) {
					$db->query("DELETE FROM {$tablepre}typevars WHERE sortid='$sortid' AND optionid IN ($ids)");
				}
				foreach($delete as $id) {
					unset($addoption[$id]);
				}
			}

			$insertoptionid = $indexoption = array();
			$create_table_sql = $separator = $create_tableoption_sql = '';

			if(is_array($addoption)) {
				$query = $db->query("SELECT optionid, type, identifier FROM {$tablepre}typeoptions WHERE optionid IN (".implodeids(array_keys($addoption)).")");
				while($option = $db->fetch_array($query)) {
					$insertoptionid[$option['optionid']]['type'] = $option['type'];
					$insertoptionid[$option['optionid']]['identifier'] = $option['identifier'];
				}

				$query = $db->query("SHOW TABLES LIKE '{$tablepre}optionvalue$sortid'");
				if($db->num_rows($query) != 1) {
					$create_table_sql = "CREATE TABLE {$tablepre}optionvalue$sortid (";
					foreach($addoption as $optionid => $option) {
						$identifier = $insertoptionid[$optionid]['identifier'];
						if(in_array($insertoptionid[$optionid]['type'], array('radio', 'select', 'number')) || $search[$optionid]) {
							if(in_array($insertoptionid[$optionid]['type'], array('radio', 'select'))) {
								$create_tableoption_sql .= "$separator$identifier smallint(6) UNSIGNED NOT NULL DEFAULT '0'\r\n";
							} elseif($insertoptionid[$optionid]['type'] == 'number') {
								$create_tableoption_sql .= "$separator$identifier int(10) UNSIGNED NOT NULL DEFAULT '0'\r\n";
							} else {
								$create_tableoption_sql .= "$separator$identifier mediumtext NOT NULL\r\n";
							}
							$separator = ' ,';

							if(in_array($insertoptionid[$optionid]['type'], array('radio', 'select', 'number'))) {
								$indexoption[] = $identifier;
							}
						}
					}
					$create_table_sql .= ($create_tableoption_sql ? $create_tableoption_sql.',' : '')."tid mediumint(8) UNSIGNED NOT NULL DEFAULT '0',fid smallint(6) UNSIGNED NOT NULL DEFAULT '0',";
					$create_table_sql .= "KEY (fid)";
					if($indexoption) {
						foreach($indexoption as $index) {
							$create_table_sql .= "$separator KEY $index ($index)\r\n";
							$separator = ' ,';
						}
					}
					$create_table_sql .= ") TYPE=MyISAM;";
					$dbcharset = empty($dbcharset) ? str_replace('-','',$charset) : $dbcharset;
					$create_table_sql = syntablestruct($create_table_sql, $db->version() > '4.1', $dbcharset);
					$db->query($create_table_sql);
				} else {
					$tables = array();
					if($db->version() > '4.1') {
						$query = $db->query("SHOW FULL COLUMNS FROM {$tablepre}optionvalue$sortid", 'SILENT');
					} else {
						$query = $db->query("SHOW COLUMNS FROM {$tablepre}optionvalue$sortid", 'SILENT');
					}
					while($field = @$db->fetch_array($query)) {
						$tables[$field['Field']] = 1;
					}

					foreach($addoption as $optionid => $option) {
						$identifier = $insertoptionid[$optionid]['identifier'];
						if(!$tables[$identifier] && (in_array($insertoptionid[$optionid]['type'], array('radio', 'select', 'number')) || $search[$optionid])) {
							$fieldname = $identifier;
							if(in_array($insertoptionid[$optionid]['type'], array('radio', 'select'))) {
								$fieldtype = 'smallint(6) UNSIGNED NOT NULL DEFAULT \'0\'';
							} elseif($insertoptionid[$optionid]['type'] == 'number') {
								$fieldtype = 'int(10) UNSIGNED NOT NULL DEFAULT \'0\'';
							} else {
								$fieldtype = 'mediumtext NOT NULL';
							}
							$db->query("ALTER TABLE {$tablepre}optionvalue$sortid ADD $fieldname $fieldtype");

							if(in_array($insertoptionid[$optionid]['type'], array('radio', 'select', 'number'))) {
								$db->query("ALTER TABLE {$tablepre}optionvalue$sortid ADD INDEX ($fieldname)");
							}
						}
					}
				}
				foreach($addoption as $id => $val) {
					$optionid = $db->fetch_first("SELECT optionid FROM {$tablepre}typeoptions WHERE optionid='$id'");
					if($optionid) {
						$db->query("INSERT INTO {$tablepre}typevars (sortid, optionid, available, required) VALUES ('$sortid', '$id', '1', '".intval($val)."')", 'SILENT');
						$db->query("UPDATE {$tablepre}typevars SET displayorder='$displayorder[$id]', available='$available[$id]', required='$required[$id]', unchangeable='$unchangeable[$id]', search='$search[$id]', subjectshow='$subjectshow[$id]' WHERE sortid='$sortid' AND optionid='$id'");
					} else {
						$db->query("DELETE FROM {$tablepre}typevars WHERE sortid='$sortid' AND optionid IN ($id)");
					}
				}
			}

			updatecache('threadsorts');
			cpmsg('threadtype_infotypes_succeed', $BASESCRIPT.'?action=threadtypes&special=1', 'succeed');

		} elseif(submitcheck('sortpreviewsubmit')) {
			header("Location: $boardurl$BASESCRIPT?action=threadtypes&operation=sortdetail&sortid=$sortid#template");
		}

	}

} elseif($operation == 'typemodel') {

	if(!submitcheck('modelsubmit')) {
		$typemodels = '';
		$query = $db->query("SELECT * FROM {$tablepre}typemodels ORDER BY displayorder");
		while($model = $db->fetch_array($query)) {
			$typemodels .= showtablerow('', array('class="td25"', 'class="td28 td23"', 'class="td24"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$model[id]\" ".($model['type'] ? 'disabled' : '').">",
				"<input type=\"text\" class=\"txt\" size=\"10\" name=\"displayorder[$model[id]]\" value=\"$model[displayorder]\">",
				"<input type=\"text\" class=\"txt\" name=\"name[$model[id]]\" value=\"$model[name]\">",
				"<a href=\"$BASESCRIPT?action=threadtypes&operation=modeldetail&modelid=$model[id]\" class=\"act\">$lang[detail]</a>"
			), TRUE);
		}

		echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[
			[1, '', 'td25'],
			[1, '<input type="text" class="txt" size="10" name="newdisplayorder[]">', 'td28 td23'],
			[1, '<input type="text" class="txt" name="newtitle[]">', 'td24'],
			[1, '']
		],
	];
</script>
EOT;
		shownav('forum', 'threadtype_models');
		showsubmenu('threadtype_models', '');
		showformheader('threadtypes&operation=typemodel');
		showtableheader();
		showsubtitle(array('', 'display_order', 'name', ''));
		echo $typemodels;
		echo '<tr><td></td><td colspan="3"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['threadtype_infotypes_add_model'].'</a></div></td></tr>';
		showsubmit('modelsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM {$tablepre}typemodels WHERE id IN ($ids)");
		}

		if(is_array($name)) {
			foreach($name as $id => $val) {
				$db->query("UPDATE {$tablepre}typemodels SET displayorder='$displayorder[$id]', name='$name[$id]' WHERE id='$id'");
			}
		}

		if(is_array($newtitle)) {
			foreach($newtitle as $key => $value) {
				if($value) {
					$db->query("INSERT INTO {$tablepre}typemodels (name, displayorder, type)
						VALUES ('$value', '$newdisplayorder[$key]', '0')");
				}
			}
		}

		cpmsg('threadtype_infotypes_model_succeed', $BASESCRIPT.'?action=threadtypes&operation=typemodel', 'succeed');

	}

} elseif($operation == 'modeldetail') {

	if(!submitcheck('modeldetailsubmit')) {

		$classoptions = $modeloption = $sysoption = $sysoptselect = '';
		$query = $db->query("SELECT * FROM {$tablepre}typeoptions WHERE classid!='0' ORDER BY displayorder");
		while($option = $db->fetch_array($query)) {
			$classoptions .= "<option value=\"$option[optionid]\">$option[title]</option>";
		}

		$model = $db->fetch_first("SELECT * FROM {$tablepre}typemodels WHERE id='".intval($modelid)."'");
		if(!$model) {
			cpmsg('undefined_action', '', 'error');
		}

		$query = $db->query("SELECT * FROM {$tablepre}typeoptions WHERE optionid IN (".implodeids(explode("\t", $model['customoptions'])).")");
		while($modelopt = $db->fetch_array($query)){
			$modeloption .=  "<option value=\"$modelopt[optionid]\">$modelopt[title]</option>";
		}

		if($model['type']) {
			$query = $db->query("SELECT * FROM {$tablepre}typeoptions WHERE optionid IN (".implodeids(explode("\t", $model['options'])).")");
			while($modelopt = $db->fetch_array($query)){
				$sysoption .=  "<option value=\"$modelopt[optionid]\">$modelopt[title]</option>";
			}

			$sysoptselect = '<select name="" size="10" multiple="multiple">'.$sysoption.'</select>';
		}

		$optselect = '<select name="" size="10" multiple="multiple" id="coptselect">'.$classoptions.'</select>';
		$hoptselect = '<select name="customoptions[]" size="10" multiple="multiple" id="moptselect">'.$modeloption.'</select>';

		echo <<<EOT
<script type="text/JavaScript">
	function copyoption(s1, s2) {
		var s1 = $(s1);
		var s2 = $(s2);
		var len = s1.options.length;
		for(var i=0; i<len; i++) {
			op = s1.options[i];
			if(op.selected == true && !optionexists(s2, op.value)) {
				o = op.cloneNode(true);
				s2.appendChild(o);
			}
		}
	}

	function optionexists(s1, value) {
		var len = s1.options.length;
			for(var i=0; i<len; i++) {
				if(s1.options[i].value == value) {
					return true;
				}
			}
		return false;
	}

	function removeoption(s1) {
		var s1 = $(s1);
		var len = s1.options.length;
		for(var i=s1.options.length - 1; i>-1; i--) {
			op = s1.options[i];
			if(op.selected && op.selected == true) {
				s1.removeChild(op);
			}
		}
		return false;
	}

	function selectalloption(s1) {
		var s1 = $(s1);
		var len = s1.options.length;
		for(var i=s1.options.length - 1; i>-1; i--) {
			op = s1.options[i];
			op.selected = true;
		}
	}
</script>
EOT;
		showsubmenu('threadtype_models_option_setting');
		showformheader("threadtypes&operation=modeldetail&modelid=$modelid", 'onsubmit="selectalloption(\'moptselect\');"');
		showtableheader();
		showsetting('name', 'namenew', $model['name'], 'text');
		if($model['type']) {
			showsetting('threadtype_models_option_model', '', '', $sysoptselect);
		}
		showsetting('threadtype_models_option_user', '', '', $hoptselect.'<br /><a href="###" onclick="removeoption(\'moptselect\')">['.$lang['del'].']</a>');
		showsetting('threadtype_models_option_system', '', '', $optselect.'<br /><a href="###" onclick="copyoption(\'coptselect\', \'moptselect\')">['.$lang['threadtype_models_option_copy'].']</a>');
		showsubmit('modeldetailsubmit');
		showtablefooter();
		showformfooter();

	} else {
		$customoptionsnew = $customoptions && is_array($customoptions) ? implode("\t", $customoptions) : '';
		$db->query("UPDATE {$tablepre}typemodels SET name='$namenew', customoptions='$customoptionsnew' WHERE id='$modelid'");

		cpmsg('threadtype_infotypes_model_succeed', $BASESCRIPT.'?action=threadtypes&operation=typemodel', 'succeed');
	}

} elseif($operation == 'classlist') {

	$classoptions = '';
	$classidarray = array();
	!$classid && $classid = 0;
	$query = $db->query("SELECT optionid, title FROM {$tablepre}typeoptions WHERE classid='$classid' ORDER BY displayorder");
	while($option = $db->fetch_array($query)) {
		$classidarray[] = $option['optionid'];
		$classoptions .= "<a href=\"#ol\" onclick=\"ajaxget('$BASESCRIPT?action=threadtypes&operation=optionlist&typeid=$typeid&classid=$option[optionid]', 'optionlist', 'optionlist', 'Loading...', '', checkedbox)\">$option[title]</a> &nbsp; ";
	}

	include template('header');
	echo $classoptions;
	include template('footer');
	exit;

} elseif($operation == 'optionlist') {

	if(!$classid) {
		$classid = $db->result_first("SELECT optionid FROM {$tablepre}typeoptions WHERE classid='0' ORDER BY displayorder LIMIT 1");
	}
	$query = $db->query("SELECT optionid FROM {$tablepre}typevars WHERE sortid='$typeid'");
	$option = $options = array();
	while($option = $db->fetch_array($query)) {
		$options[] = $option['optionid'];
	}

	$optionlist = '';
	$query = $db->query("SELECT * FROM {$tablepre}typeoptions WHERE classid='$classid' ORDER BY displayorder");
	while($option = $db->fetch_array($query)) {
		$optionlist .= "<input ".(in_array($option['optionid'], $options) ? ' checked="checked" ' : '')."class=\"checkbox\" type=\"checkbox\" name=\"typeselect[]\" id=\"typeselect_$option[optionid]\" value=\"$option[optionid]\" onclick=\"insertoption(this.value);\" /><label for=\"typeselect_$option[optionid]\">".dhtmlspecialchars($option['title'])."</label>&nbsp;&nbsp;";
	}
	include template('header');
	echo $optionlist;
	include template('footer');
	exit;

} elseif($operation == 'sortlist') {

	$option = $db->fetch_first("SELECT * FROM {$tablepre}typeoptions WHERE optionid='$optionid' LIMIT 1");
	include template('header');
	$option['type'] = $lang['threadtype_edit_vars_type_'. $option['type']];
	$option['available'] = 1;
	showtablerow('', array('class="td25"', 'class="td28 td23"'), array(
		"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$option[optionid]\" ".($option['model'] ? 'disabled' : '').">",
		"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayorder[$option[optionid]]\" value=\"$option[displayorder]\">",
		"<input class=\"checkbox\" type=\"checkbox\" name=\"available[$option[optionid]]\" value=\"1\" ".($option['available'] ? 'checked' : '')." ".($option['model'] ? 'disabled' : '').">",
		dhtmlspecialchars($option['title']),
		$option[type],
		"<input class=\"checkbox\" type=\"checkbox\" name=\"required[$option[optionid]]\" value=\"1\" ".($option['required'] ? 'checked' : '')." ".($option['model'] ? 'disabled' : '').">",
		"<input class=\"checkbox\" type=\"checkbox\" name=\"unchangeable[$option[optionid]]\" value=\"1\" ".($option['unchangeable'] ? 'checked' : '').">",
		"<input class=\"checkbox\" type=\"checkbox\" name=\"search[$option[optionid]]\" value=\"1\" ".($option['search'] ? 'checked' : '').">",
		"<input class=\"checkbox\" type=\"checkbox\" name=\"subjectshow[$option[optionid]]\" value=\"1\" ".($option['subjectshow'] ? 'checked' : '').">",
		"<a href=\"###\" onclick=\"insertvar('$option[identifier]', 'typetemplate', 'message');doane(event);return false;\" class=\"act\">".$lang['threadtype_infotypes_add_template']."</a>",
		"<a href=\"###\" onclick=\"insertvar('$option[identifier]', 'stypetemplate', 'subject');doane(event);return false;\" class=\"act\">".$lang['threadtype_infotypes_add_stemplate']."</a>",
		"<a href=\"$BASESCRIPT?action=threadtypes&operation=optiondetail&optionid=$option[optionid]\" class=\"act\">".$lang['edit']."</a>"
	));
	include template('footer');
	exit;
}

function showoption($var, $type) {
	global $optiontitle, $lang;
	if($optiontitle[$var]) {
		$optiontitle[$var] = $type == 'title' ? $optiontitle[$var] : $optiontitle[$var].($type == 'value' ? $lang['value'] : $lang['unit']);
		return $optiontitle[$var];
	} else {
		return "!$var!";
	}
}

function syntablestruct($sql, $version, $dbcharset) {

	if(strpos(trim(substr($sql, 0, 18)), 'CREATE TABLE') === FALSE) {
		return $sql;
	}

	$sqlversion = strpos($sql, 'ENGINE=') === FALSE ? FALSE : TRUE;

	if($sqlversion === $version) {

		return $sqlversion && $dbcharset ? preg_replace(array('/ character set \w+/i', '/ collate \w+/i', "/DEFAULT CHARSET=\w+/is"), array('', '', "DEFAULT CHARSET=$dbcharset"), $sql) : $sql;
	}

	if($version) {
		return preg_replace(array('/TYPE=HEAP/i', '/TYPE=(\w+)/is'), array("ENGINE=MEMORY DEFAULT CHARSET=$dbcharset", "ENGINE=\\1 DEFAULT CHARSET=$dbcharset"), $sql);

	} else {
		return preg_replace(array('/character set \w+/i', '/collate \w+/i', '/ENGINE=MEMORY/i', '/\s*DEFAULT CHARSET=\w+/is', '/\s*COLLATE=\w+/is', '/ENGINE=(\w+)(.*)/is'), array('', '', 'ENGINE=HEAP', '', '', 'TYPE=\\1\\2'), $sql);
	}
}
?>