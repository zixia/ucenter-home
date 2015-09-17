<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: misc.inc.php 21184 2009-11-19 07:00:04Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

cpheader();

$operation = $operation ? $operation : 'custommenu';

if($operation == 'onlinelist') {

	if(!submitcheck('onlinesubmit')) {

		shownav('style', 'misc_onlinelist');
		showsubmenu('nav_misc_onlinelist');
		showtips('misc_onlinelist_tips');
		showformheader('misc&operation=onlinelist&');
		showtableheader('', 'fixpadding');
		showsubtitle(array('', 'display_order', 'usergroup', 'usergroups_title', 'misc_onlinelist_image'));

		$listarray = array();
		$query = $db->query("SELECT * FROM {$tablepre}onlinelist");
		while($list = $db->fetch_array($query)) {
			$list['title'] = dhtmlspecialchars($list['title']);
			$listarray[$list['groupid']] = $list;
		}

		$onlinelist = '';
		$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups WHERE type<>'member'");
		$group = array('groupid' => 0, 'grouptitle' => 'Member');
		do {
			$id = $group['groupid'];
			showtablerow('', array('class="td25"', 'class="td23 td28"', 'class="td24"', 'class="td24"', 'class="td21 td26"'), array(
				$listarray[$id]['url'] ? " <img src=\"images/common/{$listarray[$id]['url']}\">" : '',
				'<input type="text" class="txt" name="displayordernew['.$id.']" value="'.$listarray[$id]['displayorder'].'" size="3" />',
				$group['groupid'] <= 8 ? lang('usergroups_system_'.$id) : $group['grouptitle'],
				'<input type="text" class="txt" name="titlenew['.$id.']" value="'.($listarray[$id]['title'] ? $listarray[$id]['title'] : $group['grouptitle']).'" size="15" />',
				'<input type="text" class="txt" name="urlnew['.$id.']" value="'.$listarray[$id]['url'].'" size="20" />'
			));

		} while($group = $db->fetch_array($query));

		showsubmit('onlinesubmit', 'submit', 'td');
		showtablefooter();
		showformfooter();

	} else {

		if(is_array($urlnew)) {
			$db->query("DELETE FROM {$tablepre}onlinelist");
			foreach($urlnew as $id => $url) {
				$url = trim($url);
				if($id == 0 || $url) {
					$db->query("INSERT INTO {$tablepre}onlinelist (groupid, displayorder, title, url)
						VALUES ('$id', '$displayordernew[$id]', '$titlenew[$id]', '$url')");
				}
			}
		}

		updatecache(array('onlinelist', 'groupicon'));
		cpmsg('onlinelist_succeed', $BASESCRIPT.'?action=misc&operation=onlinelist', 'succeed');

	}

} elseif($operation == 'link') {

	if(!submitcheck('linksubmit')) {

?>
<script type="text/JavaScript">
var rowtypedata = [
	[
		[1,'', 'td25'],
		[1,'<input type="text" class="txt" name="newdisplayorder[]" size="3">', 'td28'],
		[1,'<input type="text" class="txt" name="newname[]" size="15">'],
		[1,'<input type="text" class="txt" name="newurl[]" size="20">'],
		[1,'<input type="text" class="txt" name="newdescription[]" size="30">', 'td26'],
		[1,'<input type="text" class="txt" name="newlogo[]" size="20">']
	]
]
</script>
<?

		shownav('adv', 'misc_link');
		showsubmenu('nav_misc_links');
		showtips('misc_link_tips');
		showformheader('misc&operation=link');
		showtableheader();
		showsubtitle(array('', 'display_order', 'misc_link_edit_name', 'misc_link_edit_url', 'misc_link_edit_description', 'misc_link_edit_logo'));

		$query = $db->query("SELECT * FROM {$tablepre}forumlinks ORDER BY displayorder");
		while($forumlink = $db->fetch_array($query)) {
			showtablerow('', array('class="td25"', 'class="td28"', '', '', 'class="td26"'), array(
				'<input type="checkbox" class="checkbox" name="delete[]" value="'.$forumlink['id'].'" />',
				'<input type="text" class="txt" name="displayorder['.$forumlink[id].']" value="'.$forumlink['displayorder'].'" size="3" />',
				'<input type="text" class="txt" name="name['.$forumlink[id].']" value="'.$forumlink['name'].'" size="15" />',
				'<input type="text" class="txt" name="url['.$forumlink[id].']" value="'.$forumlink['url'].'" size="20" />',
				'<input type="text" class="txt" name="description['.$forumlink[id].']" value="'.$forumlink['description'].'" size="30" />',
				'<input type="text" class="txt" name="logo['.$forumlink[id].']" value="'.$forumlink['logo'].'" size="20" />'
			));
		}

		echo '<tr><td></td><td colspan="3"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['misc_link_add'].'</a></div></td></tr>';
		showsubmit('linksubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if(is_array($delete)) {
			$ids = $comma =	'';
			foreach($delete	as $id)	{
				$ids .=	"$comma'$id'";
				$comma = ',';
			}
			$db->query("DELETE FROM	{$tablepre}forumlinks WHERE id IN ($ids)");
		}

		if(is_array($name)) {
			foreach($name as $id =>	$val) {
				$db->query("UPDATE {$tablepre}forumlinks SET displayorder='$displayorder[$id]', name='$name[$id]', url='$url[$id]',description='$description[$id]',logo='$logo[$id]' WHERE id='$id'");
			}
		}

		if(is_array($newname)) {
			foreach($newname as $key => $value) {
				if($value) {
					$db->query("INSERT INTO {$tablepre}forumlinks (displayorder, name, url, description, logo) VALUES ('$newdisplayorder[$key]', '$value', '$newurl[$key]', '$newdescription[$key]', '$newlogo[$key]')");
				}
			}
		}

		updatecache('forumlinks');
		cpmsg('forumlinks_succeed', $BASESCRIPT.'?action=misc&operation=link', 'succeed');

	}

} elseif($operation == 'bbcode') {

	if(!submitcheck('bbcodessubmit') && !$edit) {
		echo '<script type="text/JavaScript">loadcss("editor");</script>';
		shownav('style', 'settings_editor');

		showsubmenu('settings_editor', array(
			array('settings_editor_global', 'settings&operation=editor', 0),
			array('settings_editor_code', 'misc&operation=bbcode', 1),
		));

		showtips('misc_bbcode_edit_tips');
		showformheader('misc&operation=bbcode');
		showtableheader('', 'fixpadding');
		showsubtitle(array('', 'misc_bbcode_tag', 'available', 'display', 'display_order', 'misc_bbcode_icon', 'misc_bbcode_icon_file', ''));
		$query = $db->query("SELECT * FROM {$tablepre}bbcodes ORDER BY displayorder");
		while($bbcode = $db->fetch_array($query)) {
			showtablerow('', array('class="td25"', 'class="td21"', 'class="td25"', 'class="td25"', 'class="td28 td24"', 'class="td25"', 'class="td21"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$bbcode[id]\">",
				"<input type=\"text\" class=\"txt\" size=\"15\" name=\"tagnew[$bbcode[id]]\" value=\"$bbcode[tag]\">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$bbcode[id]]\" value=\"1\" ".($bbcode['available'] ? 'checked="checked"' : NULL).">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"displaynew[$bbcode[id]]\" value=\"1\" ".($bbcode['available'] == '2' ? 'checked="checked"' : NULL).">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[$bbcode[id]]\" value=\"$bbcode[displayorder]\">",
				$bbcode['icon'] ? "<em class=\"editor\"><a class=\"customedit\"><img src=\"images/common/$bbcode[icon]\" border=\"0\"></a></em>" : ' ',
				"<input type=\"text\" class=\"txt\" size=\"25\" name=\"iconnew[$bbcode[id]]\" value=\"$bbcode[icon]\">",
				"<a href=\"$BASESCRIPT?action=misc&operation=bbcode&edit=$bbcode[id]\" class=\"act\">$lang[detail]</a>"
			));
		}
		showtablerow('', array('class="td25"', 'class="td25"', 'class="td25"', 'class="td25"', 'class="td28 td24"', 'class="td25"', 'class="td21"'), array(
			lang('add_new'),
			'<input type="text" class="txt" size="15" name="newtag">',
			'',
			'',
			'<input type="text" class="txt" size="2" name="newdisplayorder">',
			'',
			'<input type="text" class="txt" size="25" name="newicon">',
			''
		));
		showsubmit('bbcodessubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} elseif(submitcheck('bbcodessubmit')) {

		if(is_array($delete)) {
			$ids = '\''.implode('\',\'', $delete).'\'';
			$db->query("DELETE FROM	{$tablepre}bbcodes WHERE id IN ($ids)");
		}

		if(is_array($tagnew)) {
			$custom_ids = array();
			$query = $db->query("SELECT id FROM {$tablepre}bbcodes");
			while($bbcode = $db->fetch_array($query)) {
				$custom_ids[] = $bbcode['id'];
			}
			foreach($tagnew as $id => $val) {
				if(in_array($id, $custom_ids) && !preg_match("/^[0-9a-z]+$/i", $tagnew[$id]) && strlen($tagnew[$id]) < 20) {
					cpmsg('dzcode_edit_tag_invalid', '', 'error');
				}
				$availablenew[$id] = in_array($id, $custom_ids) ? $availablenew[$id] : 1;
				$availablenew[$id] = $availablenew[$id] && $displaynew[$id] ? 2 : $availablenew[$id];
				$sqladd = in_array($id, $custom_ids) ? ", tag='$tagnew[$id]', icon='$iconnew[$id]'" : '';
				$db->query("UPDATE {$tablepre}bbcodes SET available='$availablenew[$id]', displayorder='$displayordernew[$id]' $sqladd WHERE id='$id'");
			}
		}

		if($newtag != '') {
			if(!preg_match("/^[0-9a-z]+$/i", $newtag && strlen($newtag) < 20)) {
				cpmsg('dzcode_edit_tag_invalid', '', 'error');
			}
			$db->query("INSERT INTO	{$tablepre}bbcodes (tag, icon, available, displayorder, params, nest)
				VALUES ('$newtag', '$newicon', '0', '$newdisplayorder', '1', '1')");
		}

		updatecache(array('bbcodes', 'bbcodes_display'));
		cpmsg('dzcode_edit_succeed', $BASESCRIPT.'?action=misc&operation=bbcode', 'succeed');

	} elseif($edit) {

		$bbcode = $db->fetch_first("SELECT * FROM {$tablepre}bbcodes WHERE id='$edit'");
		if(!$bbcode) {
			cpmsg('undefined_action', '', 'error');
		}

		if(!submitcheck('editsubmit')) {
			$bbcode['prompt'] = str_replace("\t", "\n", $bbcode['prompt']);

			shownav('style', 'nav_posting_bbcode');
			showsubmenu($lang['misc_bbcode_edit'].' - '.$bbcode['tag']);
			showformheader("misc&operation=bbcode&edit=$edit");
			showtableheader();
			showsetting('misc_bbcode_edit_tag', 'tagnew', $bbcode['tag'], 'text');
			showsetting('misc_bbcode_edit_replacement', 'replacementnew', $bbcode['replacement'], 'textarea');
			showsetting('misc_bbcode_edit_example', 'examplenew', $bbcode['example'], 'text');
			showsetting('misc_bbcode_edit_explanation', 'explanationnew', $bbcode['explanation'], 'text');
			showsetting('misc_bbcode_edit_params', 'paramsnew', $bbcode['params'], 'text');
			showsetting('misc_bbcode_edit_prompt', 'promptnew', $bbcode['prompt'], 'textarea');
			showsetting('misc_bbcode_edit_nest', 'nestnew', $bbcode['nest'], 'text');
			showsubmit('editsubmit');
			showtablefooter();
			showformfooter();

		} else {

			$tagnew = trim($tagnew);
			if(!preg_match("/^[0-9a-z]+$/i", $tagnew)) {
				cpmsg('dzcode_edit_tag_invalid', '', 'error');
			} elseif($paramsnew < 1 || $paramsnew > 3 || $nestnew < 1 || $nestnew > 3) {
				cpmsg('dzcode_edit_range_invalid', '', 'error');
			}
			$promptnew = trim(str_replace(array("\t", "\r", "\n"), array('', '', "\t"), $promptnew));

			$db->query("UPDATE {$tablepre}bbcodes SET tag='$tagnew', replacement='$replacementnew', example='$examplenew', explanation='$explanationnew', params='$paramsnew', prompt='$promptnew', nest='$nestnew' WHERE id='$edit'");

			updatecache(array('bbcodes', 'bbcodes_display'));
			cpmsg('dzcode_edit_succeed', $BASESCRIPT.'?action=misc&operation=bbcode', 'succeed');

		}
	}

} elseif($operation == 'censor') {

	$page = max(1, intval($page));
	$ppp = 30;

	$addcensors = isset($addcensors) ? trim($addcensors) : '';

	if($do == 'export') {

		ob_end_clean();
		dheader('Cache-control: max-age=0');
		dheader('Expires: '.gmdate('D, d M Y H:i:s', $timestamp - 31536000).' GMT');
		dheader('Content-Encoding: none');
		dheader('Content-Disposition: attachment; filename=CensorWords.txt');
		dheader('Content-Type: text/plain');

		$query = $db->query("SELECT find, replacement FROM {$tablepre}words ORDER BY find ASC");
		while($censor = $db->fetch_array($query)) {
			$censor['replacement'] = str_replace('*', '', $censor['replacement']) <> '' ? $censor['replacement'] : '';
			echo $censor['find'].($censor['replacement'] != '' ? '='.stripslashes($censor['replacement']) : '')."\n";
		}
		exit();

	} elseif(submitcheck('addcensorsubmit') && $addcensors != '') {
		$oldwords = array();
		if($adminid == 1 && $overwrite == 2) {
			$db->query("TRUNCATE {$tablepre}words");
		} else {
			$query = $db->query("SELECT find, admin FROM {$tablepre}words");
			while($censor = $db->fetch_array($query)) {
				$oldwords[md5($censor['find'])] = $censor['admin'];
			}
			$db->free_result($query);
		}

		$censorarray = explode("\n", $addcensors);
		$updatecount = $newcount = $ignorecount = 0;
		foreach($censorarray as $censor) {
			list($newfind, $newreplace) = array_map('trim', explode('=', $censor));
			$newreplace = $newreplace <> '' ? daddslashes(str_replace("\\\'", '\'', $newreplace), 1) : '**';
			if(strlen($newfind) < 3) {
				$ignorecount ++;
				continue;
			} elseif(isset($oldwords[md5($newfind)])) {
				if($overwrite && ($adminid == 1 || $oldwords[md5($newfind)] == $discuz_userss)) {
					$updatecount ++;
					$db->query("UPDATE {$tablepre}words SET replacement='$newreplace' WHERE `find`='$newfind'");
				} else {
					$ignorecount ++;
				}
			} else {
				$newcount ++;
				$db->query("INSERT INTO	{$tablepre}words (admin, find, replacement) VALUES
					('$discuz_user', '$newfind', '$newreplace')");
				$oldwords[md5($newfind)] = $discuz_userss;
			}
		}
		updatecache('censor');
		cpmsg('censor_batch_add_succeed', "$BASESCRIPT?action=misc&operation=censor&anchor=import", 'succeed');

	} elseif(!submitcheck('censorsubmit')) {

		$ppp = 50;
		$page = max(1, intval($page));
		$startlimit = ($page - 1) * $ppp;
		$totalcount = $db->result_first("SELECT count(*) FROM {$tablepre}words");
		$multipage = multi($totalcount, $ppp, $page, "$BASESCRIPT?action=misc&operation=censor");

		shownav('topic', 'nav_posting_censor');
		$anchor = in_array($anchor, array('list', 'import')) ? $anchor : 'list';
		showsubmenuanchors('nav_posting_censor', array(
			array('admin', 'list', $anchor == 'list'),
			array('misc_censor_batch_add', 'import', $anchor == 'import')
		));
		showtips('misc_censor_tips', 'list_tips', $anchor == 'list');
		showtips('misc_censor_batch_add_tips', 'import_tips', $anchor == 'import');

		showtagheader('div', 'list', $anchor == 'list');
		showformheader("misc&operation=censor&page=$page", '', 'listform');
		showtableheader('', 'fixpadding');
		showsubtitle(array('', 'misc_censor_word', 'misc_censor_replacement', 'operator'));

		$query = $db->query("SELECT * FROM {$tablepre}words ORDER BY find ASC LIMIT $startlimit, $ppp");
		while($censor =	$db->fetch_array($query)) {
			$censor['replacement'] = stripslashes($censor['replacement']);
			$disabled = $adminid != 1 && $censor['admin'] != $discuz_userss ? 'disabled' : NULL;
			showtablerow('', array('class="td25"', 'class="td26"', 'class="td26"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$censor[id]\" $disabled>",
				"<input type=\"text\" class=\"txt\" size=\"30\" name=\"find[$censor[id]]\" value=\"$censor[find]\" $disabled>",
				"<input type=\"text\" class=\"txt\" size=\"30\" name=\"replace[$censor[id]]\" value=\"$censor[replacement]\" $disabled>",
				$censor['admin']
			));
		}

		showtablerow('', array('class="td25"', 'class="td26"', 'class="td26"'), array(
			lang('add_new'),
			'<input type="text" class="txt" size="30" name="newfind">',
			'<input type="text" class="txt" size="30" name="newreplace">',
			''
		));
		showsubmit('censorsubmit', 'submit', 'del', '', $multipage);
		showtablefooter();
		showformfooter();
		showtagfooter('div');

		showtagheader('div', 'import', $anchor == 'import');
		showformheader("misc&operation=censor&page=$page", 'fixpadding');
		showtableheader('', 'fixpadding', 'importform');
		showtablerow('', 'class="vtop rowform"', '<br /><textarea name="addcensors" class="tarea" rows="10" cols="80"></textarea><br /><br />'.mradio('overwrite', array(
				0 => lang('misc_censor_batch_add_no_overwrite'),
				1 => lang('misc_censor_batch_add_overwrite'),
				2 => lang('misc_censor_batch_add_clear')
		), '', FALSE));
		showsubmit('addcensorsubmit');
		showtablefooter();
		showformfooter();
		showtagfooter('div');

	} else {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM	{$tablepre}words WHERE id IN ($ids) AND ('$adminid'='1' OR admin='$discuz_user')");
		}

		if(is_array($find)) {
			foreach($find as $id =>	$val) {
				$find[$id]  = $val = trim(str_replace('=', '', $find[$id]));
				if(strlen($val) < 3) {
					cpmsg('censor_keywords_tooshort', '', 'error');
				}
				$replace[$id] = daddslashes(str_replace("\\\'", '\'', $replace[$id]), 1);
				$db->query("UPDATE {$tablepre}words SET find='$find[$id]', replacement='$replace[$id]', extra='$extrav[$id]' WHERE id='$id' AND ('$adminid'='1' OR admin='$discuz_user')");
			}
		}

		$newfind = trim(str_replace('=', '', $newfind));
		$newreplace  = trim($newreplace);

		if($newfind != '') {
			if(strlen($newfind) < 3) {
				cpmsg('censor_keywords_tooshort', '', 'error');
			}
			$newreplace = daddslashes(str_replace("\\\'", '\'', $newreplace), 1);
			if($oldcenser = $db->fetch_first("SELECT admin FROM {$tablepre}words WHERE find='$newfind'")) {
				cpmsg('censor_keywords_existence', '', 'error');
			} else {
				$db->query("INSERT INTO	{$tablepre}words (admin, find, replacement, extra) VALUES
					('$discuz_user', '$newfind', '$newreplace', '$newextra')");
			}
		}

		updatecache('censor');
		cpmsg('censor_succeed', "$BASESCRIPT?action=misc&operation=censor&page=$page", 'succeed');

	}

} elseif($operation == 'icon') {

	if(!submitcheck('iconsubmit')) {

		$anchor = in_array($anchor, array('list', 'add')) ? $anchor : 'list';
		shownav('style', 'nav_thread_icon');
		showsubmenuanchors('nav_thread_icon', array(
			array('admin', 'list', $anchor == 'list'),
			array('add', 'add', $anchor == 'add')
		));

		showtagheader('div', 'list', $anchor == 'list');
		showformheader('misc&operation=icon');
		showtableheader();
		showsubtitle(array('', 'display_order', 'smilies_edit_image', 'smilies_edit_filename'));

		$imgfilter =  array();
		$query = $db->query("SELECT * FROM {$tablepre}smilies WHERE type='icon' ORDER BY displayorder");
		while($smiley =	$db->fetch_array($query)) {
			showtablerow('', array('class="td25"', 'class="td28 td24"', 'class="td23"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$smiley[id]\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayorder[$smiley[id]]\" value=\"$smiley[displayorder]\">",
				"<img src=\"images/icons/$smiley[url]\">",
				$smiley[url]
			));
			$imgfilter[] = $smiley[url];
		}

		showsubmit('iconsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();
		showtagfooter('div');

		showtagheader('div', 'add', $anchor == 'add');
		showformheader('misc&operation=icon');
		showtableheader();
		showsubtitle(array('', 'display_order', 'smilies_edit_image', 'smilies_edit_filename'));

		$newid = 0;
		$imgextarray = array('jpg', 'gif');
		$iconsdir = dir(DISCUZ_ROOT.'./images/icons');
		while($entry = $iconsdir->read()) {
			if(in_array(strtolower(fileext($entry)), $imgextarray) && !in_array($entry, $imgfilter) && is_file(DISCUZ_ROOT.'./images/icons/'.$entry)) {
				showtablerow('', array('class="td25"', 'class="td28 td24"', 'class="td23"'), array(
					"<input type=\"checkbox\" name=\"addcheck[$newid]\" class=\"checkbox\">",
					"<input type=\"text\" class=\"txt\" size=\"2\" name=\"adddisplayorder[$newid]\" value=\"0\">",
					"<img src=\"images/icons/$entry\">",
					"<input type=\"text\" class=\"txt\" size=\"35\" name=\"addurl[$newid]\" value=\"$entry\" readonly>"
				));
				$newid ++;
			}
		}
		$iconsdir->close();
		if(!$newid) {
			showtablerow('', array('class="td25"', 'colspan="3"'), array('', lang('misc_icon_tips')));
		} else {
			showsubmit('iconsubmit', 'submit', '<input type="checkbox" class="checkbox" name="chkall2" onclick="checkAll(\'prefix\', this.form, \'addcheck\', \'chkall2\')">'.lang('select_all'));
		}

		showtablefooter();
		showformfooter();
		showtagfooter('div');

	} else {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM	{$tablepre}smilies WHERE id IN ($ids)");
		}

		if(is_array($displayorder)) {
			foreach($displayorder as $id => $val) {
				$displayorder[$id] = intval($displayorder[$id]);
				$db->query("UPDATE {$tablepre}smilies SET displayorder='$displayorder[$id]' WHERE id='$id'");
			}
		}

		if(is_array($addurl)) {
			foreach($addurl as $k => $v) {
				if($addcheck[$k]) {
					$query = $db->query("INSERT INTO {$tablepre}smilies (displayorder, type, url)
						VALUES ('{$adddisplayorder[$k]}', 'icon', '$addurl[$k]')");
				}
			}
		}

		updatecache('icons');

		cpmsg('thread_icon_succeed', "$BASESCRIPT?action=misc&operation=icon", 'succeed');
	}

} elseif($operation == 'stamp') {

	if(!submitcheck('stampsubmit')) {

		$anchor = in_array($anchor, array('list', 'add')) ? $anchor : 'list';
		shownav('style', 'nav_thread_stamp');
		showsubmenuanchors('nav_thread_stamp', array(
			array('admin', 'list', $anchor == 'list'),
			array('add', 'add', $anchor == 'add')
		));

		showtagheader('div', 'list', $anchor == 'list');
		showtips('misc_stamp_listtips');
		showformheader('misc&operation=stamp');
		showtableheader();
		showsubtitle(array('', 'misc_stamp_id', 'misc_stamp_name', 'smilies_edit_image', 'smilies_edit_filename', 'misc_stamp_option'));

		$imgfilter = array();
		$tselect = '<select><option value="0">'.lang('none').'</option><option value="1">'.lang('misc_stamp_option_stick').'</option><option value="2">'.lang('misc_stamp_option_digest').'</option><option value="3">'.lang('misc_stamp_option_recommend').'</option></select>';
		$query = $db->query("SELECT * FROM {$tablepre}smilies WHERE type='stamp' ORDER BY displayorder");
		while($smiley =	$db->fetch_array($query)) {
			$s = $r = array();
			$s[] = '<select>';
			$r[] = '<select name="typeidnew['.$smiley['id'].']">';
			if($smiley['typeid']) {
				$s[] = '<option value="'.$smiley['typeid'].'">';
				$r[] = '<option value="'.$smiley['typeid'].'" selected="selected">';
				$s[] = '<option value="0">';
				$r[] = '<option value="-1">';
			}
			$tselectrow = str_replace($s, $r, $tselect);
			showtablerow('', array('class="td25"', 'class="td28 td24"', 'class="td23"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$smiley[id]\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayorder[$smiley[id]]\" value=\"$smiley[displayorder]\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"code[$smiley[id]]\" value=\"$smiley[code]\">",
				"<img src=\"images/stamps/$smiley[url]\">",
				$smiley['url'],
				$tselectrow,
			));
			$imgfilter[] = $smiley['url'];
		}

		showsubmit('stampsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();
		showtagfooter('div');

		showtagheader('div', 'add', $anchor == 'add');
		showformheader('misc&operation=stamp');
		showtableheader();
		showsubtitle(array('', 'misc_stamp_id', 'smilies_edit_image', 'smilies_edit_filename'));

		$newid = 0;
		$imgextarray = array('png', 'gif');
		$stampsdir = dir(DISCUZ_ROOT.'./images/stamps');
		while($entry = $stampsdir->read()) {
			if(in_array(strtolower(fileext($entry)), $imgextarray) && !in_array($entry, $imgfilter) && is_file(DISCUZ_ROOT.'./images/stamps/'.$entry)) {
				showtablerow('', array('class="td25"', 'class="td28 td24"', 'class="td23"'), array(
					"<input type=\"checkbox\" name=\"addcheck[$newid]\" class=\"checkbox\">",
					"<input type=\"text\" class=\"txt\" size=\"2\" name=\"adddisplayorder[$newid]\" value=\"0\">",
					"<img src=\"images/stamps/$entry\">",
					"<input type=\"text\" class=\"txt\" size=\"35\" name=\"addurl[$newid]\" value=\"$entry\" readonly>"
				));
				$newid ++;
			}
		}
		$stampsdir->close();
		if(!$newid) {
			showtablerow('', array('class="td25"', 'colspan="3"'), array('', lang('misc_stamp_tips')));
		} else {
			showsubmit('stampsubmit', 'submit', '<input type="checkbox" class="checkbox" name="chkall2" onclick="checkAll(\'prefix\', this.form, \'addcheck\', \'chkall2\')">'.lang('select_all'));
		}

		showtablefooter();
		showformfooter();
		showtagfooter('div');

	} else {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM	{$tablepre}smilies WHERE id IN ($ids)");
		}

		if(is_array($displayorder)) {
			$typeidset = array();
			foreach($displayorder as $id => $val) {
				$displayorder[$id] = intval($displayorder[$id]);
				if($displayorder[$id] >= 0 && $displayorder[$id] < 100) {
					$typeidadd = '';
					if($typeidnew[$id] && !isset($typeidset[$typeidnew[$id]])) {
						$typeidnew[$id] = $typeidnew[$id] > 0 ? $typeidnew[$id] : 0;
						$typeidadd = ",typeid='$typeidnew[$id]'";
						$typeidset[$typeidnew[$id]] = TRUE;
					}
					$db->query("UPDATE {$tablepre}smilies SET displayorder='$displayorder[$id]',code='$code[$id]'$typeidadd WHERE id='$id'");
				}
			}
		}

		if(is_array($addurl)) {
			$count = $db->result_first("SELECT COUNT(*) FROM {$tablepre}smilies WHERE type='stamp'");
			if($count < 100) {
				foreach($addurl as $k => $v) {
					if($addcheck[$k]) {
						$count++;
						$query = $db->query("INSERT INTO {$tablepre}smilies (displayorder, type, url)
							VALUES ('0', 'stamp', '$addurl[$k]')");
					}
				}
			}
		}

		updatecache('stamps');
		updatecache('stamptypeid');

		cpmsg('thread_stamp_succeed', "$BASESCRIPT?action=misc&operation=stamp", 'succeed');
	}

} elseif($operation == 'attachtype') {

	if(!submitcheck('typesubmit')) {

		$attachtypes = '';
		$query = $db->query("SELECT * FROM {$tablepre}attachtypes");
		while($type = $db->fetch_array($query)) {
			$attachtypes .= showtablerow('', array('class="td25"', 'class="td24"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$type[id]\" />",
				"<input type=\"text\" class=\"txt\" size=\"10\" name=\"extension[$type[id]]\" value=\"$type[extension]\" />",
				"<input type=\"text\" class=\"txt\" size=\"15\" name=\"maxsize[$type[id]]\" value=\"$type[maxsize]\" />"
			), TRUE);
		}

?>
<script type="text/JavaScript">
var rowtypedata = [
	[
		[1,'', 'td25'],
		[1,'<input name="newextension[]" type="text" class="txt" size="10">', 'td24'],
		[1,'<input name="newmaxsize[]" type="text" class="txt" size="15">']
	]
];
</script>
<?

		shownav('topic', 'nav_posting_attachtype');
		showsubmenu('nav_posting_attachtype');
		showtips('misc_attachtype_tips');
		showformheader('misc&operation=attachtype');
		showtableheader();
		showtablerow('class="partition"', array('class="td25"'), array('', lang('misc_attachtype_ext'), lang('misc_attachtype_maxsize')));
		echo $attachtypes;
		echo '<tr><td></td><td colspan="2"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['misc_attachtype_add'].'</a></div></tr>';
		showsubmit('typesubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM	{$tablepre}attachtypes WHERE id IN ($ids)");
		}

		if(is_array($extension)) {
			foreach($extension as $id => $val) {
				$db->query("UPDATE {$tablepre}attachtypes SET extension='$extension[$id]', maxsize='$maxsize[$id]' WHERE id='$id'");
			}
		}

		if(is_array($newextension)) {
			foreach($newextension as $key => $value) {
				if($newextension1 = trim($value)) {
					if($db->result_first("SELECT id FROM {$tablepre}attachtypes WHERE extension='$newextension1'")) {
						cpmsg('attachtypes_duplicate', '', 'error');
					}
					$db->query("INSERT INTO	{$tablepre}attachtypes (extension, maxsize) VALUES
							('$newextension1', '$newmaxsize[$key]')");
				}
			}
		}

		cpmsg('attachtypes_succeed', $BASESCRIPT.'?action=misc&operation=attachtype', 'succeed');

	}

} elseif($operation == 'cron') {

	if(empty($edit) && empty($run)) {

		if(!submitcheck('cronssubmit')) {

			shownav('tools', 'misc_cron');
			showsubmenu('nav_misc_cron');
			showtips('misc_cron_tips');
			showformheader('misc&operation=cron');
			showtableheader('', 'fixpadding');
			showsubtitle(array('', 'name', 'available', 'type', 'time', 'misc_cron_last_run', 'misc_cron_next_run', ''));

			$query = $db->query("SELECT * FROM {$tablepre}crons ORDER BY type DESC");
			while($cron = $db->fetch_array($query)) {
				$disabled = $cron['weekday'] == -1 && $cron['day'] == -1 && $cron['hour'] == -1 && $cron['minute'] == '' ? 'disabled' : '';

				if($cron['day'] > 0 && $cron['day'] < 32) {
					$cron['time'] = lang('misc_cron_permonth').$cron['day'].lang('misc_cron_day');
				} elseif($cron['weekday'] >= 0 && $cron['weekday'] < 7) {
					$cron['time'] = lang('misc_cron_perweek').lang('misc_cron_week_day_'.$cron['weekday']);
				} elseif($cron['hour'] >= 0 && $cron['hour'] < 24) {
					$cron['time'] = lang('misc_cron_perday');
				} else {
					$cron['time'] = lang('misc_cron_perhour');
				}

				$cron['time'] .= $cron['hour'] >= 0 && $cron['hour'] < 24 ? sprintf('%02d', $cron[hour]).lang('misc_cron_hour') : '';

				if(!in_array($cron['minute'], array(-1, ''))) {
					foreach($cron['minute'] = explode("\t", $cron['minute']) as $k => $v) {
						$cron['minute'][$k] = sprintf('%02d', $v);
					}
					$cron['minute'] = implode(',', $cron['minute']);
					$cron['time'] .= $cron['minute'].lang('misc_cron_minute');
				} else {
					$cron['time'] .= '00'.lang('misc_cron_minute');
				}

				$cron['lastrun'] = $cron['lastrun'] ? gmdate("$dateformat<\b\\r />$timeformat", $cron['lastrun'] + $_DCACHE['settings']['timeoffset'] * 3600) : '<b>N/A</b>';
				$cron['nextcolor'] = $cron['nextrun'] && $cron['nextrun'] + $_DCACHE['settings']['timeoffset'] * 3600 < $timestamp ? 'style="color: #ff0000"' : '';
				$cron['nextrun'] = $cron['nextrun'] ? gmdate("$dateformat<\b\\r />$timeformat", $cron['nextrun'] + $_DCACHE['settings']['timeoffset'] * 3600) : '<b>N/A</b>';

				showtablerow('', array('class="td25"', 'class="crons"', 'class="td25"', 'class="td25"', 'class="td23"', 'class="td23"', 'class="td23"', 'class="td25"'), array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$cron[cronid]\" ".($cron['type'] == 'system' ? 'disabled' : '').">",
					"<input type=\"text\" class=\"txt\" name=\"namenew[$cron[cronid]]\" size=\"20\" value=\"$cron[name]\"><br /><b>$cron[filename]</b>",
					"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$cron[cronid]]\" value=\"1\" ".($cron['available'] ? 'checked' : '')." $disabled>",
					lang($cron['type'] == 'system' ? 'inbuilt' : 'costom'),
					$cron[time],
					$cron[lastrun],
					$cron[nextrun],
					"<a href=\"$BASESCRIPT?action=misc&operation=cron&edit=$cron[cronid]\" class=\"act\">$lang[edit]</a><br />".
					($cron['available'] ? " <a href=\"$BASESCRIPT?action=misc&operation=cron&run=$cron[cronid]\" class=\"act\">$lang[misc_cron_run]</a>" : " <a href=\"###\" class=\"act\" disabled>$lang[misc_cron_run]</a>")
				));
			}

			showtablerow('', array('','colspan="10"'), array(
				lang('add_new'),
				'<input type="text" class="txt" name="newname" value="" size="20" />'
			));
			showsubmit('cronssubmit', 'submit', 'del');
			showtablefooter();
			showformfooter();

		} else {

			if($ids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}crons WHERE cronid IN ($ids) AND type='user'");
			}

			if(is_array($namenew)) {
				foreach($namenew as $id => $name) {
					$db->query("UPDATE {$tablepre}crons SET name='".dhtmlspecialchars($namenew[$id])."', available='".$availablenew[$id]."' ".($availablenew[$id] ? '' : ', nextrun=\'0\'')." WHERE cronid='$id'");
				}
			}

			if($newname = trim($newname)) {
				$db->query("INSERT INTO {$tablepre}crons (name, type, available, weekday, day, hour, minute, nextrun)
					VALUES ('".dhtmlspecialchars($newname)."', 'user', '0', '-1', '-1', '-1', '', '$timestamp')");
			}

			$query = $db->query("SELECT cronid, filename FROM {$tablepre}crons");
			while($cron = $db->fetch_array($query)) {
				if(!file_exists(DISCUZ_ROOT.'./include/crons/'.$cron['filename'])) {
					$db->query("UPDATE {$tablepre}crons SET available='0', nextrun='0' WHERE cronid='$cron[cronid]'");
				}
			}

			//updatecache('crons');
			updatecache('settings');
			cpmsg('crons_succeed', $BASESCRIPT.'?action=misc&operation=cron', 'succeed');

		}

	} else {

		$cronid = empty($run) ? $edit : $run;
		$cron = $db->fetch_first("SELECT * FROM {$tablepre}crons WHERE cronid='$cronid'");
		if(!$cron) {
			cpmsg('undefined_action', '', 'error');
		}
		$cron['filename'] = str_replace(array('..', '/', '\\'), array('', '', ''), $cron['filename']);
		$cronminute = str_replace("\t", ',', $cron['minute']);
		$cron['minute'] = explode("\t", $cron['minute']);

		if(!empty($edit)) {

			if(!submitcheck('editsubmit')) {

				shownav('tools', 'misc_cron');
				showsubmenu($lang['misc_cron_edit'].' - '.$cron['name']);
				showtips('misc_cron_edit_tips');

				$weekdayselect = $dayselect = $hourselect = '';

				for($i = 0; $i <= 6; $i++) {
					$weekdayselect .= "<option value=\"$i\" ".($cron['weekday'] == $i ? 'selected' : '').">".$lang['misc_cron_week_day_'.$i]."</option>";
				}

				for($i = 1; $i <= 31; $i++) {
					$dayselect .= "<option value=\"$i\" ".($cron['day'] == $i ? 'selected' : '').">$i $lang[misc_cron_day]</option>";
				}

				for($i = 0; $i <= 23; $i++) {
					$hourselect .= "<option value=\"$i\" ".($cron['hour'] == $i ? 'selected' : '').">$i $lang[misc_cron_hour]</option>";
				}

				shownav('tools', 'misc_cron');
				showformheader("misc&operation=cron&edit=$cronid");
				showtableheader();
				showsetting('misc_cron_edit_weekday', '', '', "<select name=\"weekdaynew\"><option value=\"-1\">*</option>$weekdayselect</select>");
				showsetting('misc_cron_edit_day', '', '', "<select name=\"daynew\"><option value=\"-1\">*</option>$dayselect</select>");
				showsetting('misc_cron_edit_hour', '', '', "<select name=\"hournew\"><option value=\"-1\">*</option>$hourselect</select>");
				showsetting('misc_cron_edit_minute', 'minutenew', $cronminute, 'text');
				showsetting('misc_cron_edit_filename', 'filenamenew', $cron['filename'], 'text');
				showsubmit('editsubmit');
				showtablefooter();
				showformfooter();

			} else {

				$daynew = $weekdaynew != -1 ? -1 : $daynew;
				if(strpos($minutenew, ',') !== FALSE) {
					$minutenew = explode(',', $minutenew);
					foreach($minutenew as $key => $val) {
						$minutenew[$key] = $val = intval($val);
						if($val < 0 || $var > 59) {
							unset($minutenew[$key]);
						}
					}
					$minutenew = array_slice(array_unique($minutenew), 0, 12);
					$minutenew = implode("\t", $minutenew);
				} else {
					$minutenew = intval($minutenew);
					$minutenew = $minutenew >= 0 && $minutenew < 60 ? $minutenew : '';
				}

				if(preg_match("/[\\\\\/\:\*\?\"\<\>\|]+/", $filenamenew)) {
					cpmsg('crons_filename_illegal', '', 'error');
				} elseif(!is_readable(DISCUZ_ROOT.($cronfile = "./include/crons/$filenamenew"))) {
					cpmsg('crons_filename_invalid', '', 'error');
				} elseif($weekdaynew == -1 && $daynew == -1 && $hournew == -1 && $minutenew === '') {
					cpmsg('crons_time_invalid', '', 'error');
				}

				$db->query("UPDATE {$tablepre}crons SET weekday='$weekdaynew', day='$daynew', hour='$hournew', minute='$minutenew', filename='".trim($filenamenew)."' WHERE cronid='$cronid'");

				updatecache('crons');

				require_once DISCUZ_ROOT.'./include/cron.func.php';
				cronnextrun($cron);

				cpmsg('crons_succeed', $BASESCRIPT.'?action=misc&operation=cron', 'succeed');

			}

		} else {

			if(!@include_once DISCUZ_ROOT.($cronfile = "./include/crons/$cron[filename]")) {
				cpmsg('crons_run_invalid', '', 'error');
			} else {
				require_once DISCUZ_ROOT.'./include/cron.func.php';
				cronnextrun($cron);
				cpmsg('crons_run_succeed', $BASESCRIPT.'?action=misc&operation=cron', 'succeed');
			}

		}

	}

} elseif($operation == 'tag') {

	if(!$tagstatus) {
		cpmsg('tags_not_open', "$BASESCRIPT?action=settings&operation=functions#subtitle_tags");
	}

	if(submitcheck('tagsubmit') && !empty($tag)) {
		$tagdelete = $tagclose = $tagopen = array();
		foreach($tag as $key => $value) {
			if($value == -1) {
				$tagdelete[] = $key;
			} elseif($value == 1) {
				$tagclose[] = $key;
			} elseif($value == 0) {
				$tagopen[] = $key;
			}
		}

		if($tagdelete) {
			$db->query("DELETE FROM {$tablepre}tags WHERE tagname IN (".implodeids($tagdelete).")", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}threadtags WHERE tagname IN (".implodeids($tagdelete).")", 'UNBUFFERED');
		}

		if($tagclose) {
			$db->query("UPDATE {$tablepre}tags SET closed=1 WHERE tagname IN (".implodeids($tagclose).")", 'UNBUFFERED');
		}

		if($tagopen) {
			$db->query("UPDATE {$tablepre}tags SET closed=0 WHERE tagname IN (".implodeids($tagopen).")", 'UNBUFFERED');
		}

		if($tagdelete || $tagclose || $tagopen) {
			updatecache(array('tags_index', 'tags_viewthread'));
		}

		cpmsg('tags_updated', $BASESCRIPT.'?action=misc&operation=tag&tagsearchsubmit=yes&tagname='.rawurlencode($tagname).'&threadnumlower='.intval($threadnumlower).'&threadnumhigher='.intval($threadnumhigher).'&status='.intval($status), 'succeed');

	}

	shownav('topic', 'nav_posting_tag');
	showsubmenu('nav_posting_tag');

	if(!submitcheck('tagsearchsubmit', 1)) {

		$tagcount[0] = $db->result_first("SELECT count(*) FROM {$tablepre}tags");
		$tagcount[1] = $db->result_first("SELECT count(*) FROM {$tablepre}tags WHERE closed=1");
		$tagcount[2] = $tagcount[0] - $tagcount[1];

		showformheader('misc&operation=tag');
		showtableheader('misc_tag_search');
		showsetting('misc_tag', 'tagname', '', 'text');
		showsetting('misc_tag_threadnum_between', array('threadnumhigher', 'threadnumlower'), array(), 'range');
		showsetting('misc_tag_status', array( 'status', array(
			array(-1, lang('all')."($tagcount[0])"),
			array(1, lang('misc_tag_status_1')."($tagcount[1])"),
			array(0, lang('misc_tag_status_0')."($tagcount[2])")
		), TRUE), -1, 'mradio');
		showsubmit('tagsearchsubmit', 'misc_tag_search');
		showtablefooter();
		showformfooter();

	} else {

		$tagpp = 100;
		$page = max(1, intval($page));

		$threadnumlower = !empty($threadnumlower) ? intval($threadnumlower) : '';
		$threadnumhigher = !empty($threadnumhigher) ? intval($threadnumhigher) : '';

		$sqladd = $tagname ? "tagname LIKE '%".str_replace(array('%', '*', '_'), array('\%', '%', '\_'), $tagname)."%'" : '1';
		$sqladd .= $threadnumlower ? " AND total<'".intval($threadnumlower)."'" : '';
		$sqladd .= $threadnumhigher ? " AND total>'".intval($threadnumhigher)."'" : '';
		$sqladd .= $status != -1 ? " AND closed='".intval($status)."'" : '';

		$pagetmp = $page;

		$num = $db->result_first("SELECT count(*) FROM {$tablepre}tags WHERE $sqladd");
		$multipage = multi($num, $tagpp, $page, $BASESCRIPT.'?action=misc&operation=tag&tagsearchsubmit=yes&tagname='.rawurlencode($tagname).'&threadnumlower='.intval($threadnumlower).'&threadnumhigher='.intval($threadnumhigher).'&status='.intval($status));

		do {
			$query = $db->query("SELECT * FROM {$tablepre}tags WHERE $sqladd ORDER BY total DESC LIMIT ".(($pagetmp - 1) * $tagpp).", $tagpp");
			$pagetmp--;
		} while(!$db->num_rows($query) && $pagetmp);

		showformheader('misc&operation=tag&page='.$page);
		showhiddenfields(array(
			'tagname' => $tagname,
			'threadnumlower' => $threadnumlower,
			'threadnumhigher' => $threadnumhigher,
			'tagname' => $tagname,
			'status' => $status,
		));
		showtableheader('nav_posting_tag', 'fixpadding');
		showtablerow('', array('class="td21"', 'class="td25"'), array(
			lang('misc_tag'),
			lang('misc_tag_threadnum'),
			''
		));

		while($tag = $db->fetch_array($query)) {
			showtablerow('', array('class="td21"', 'class="td25"'), array(
				'<a href="tag.php?name='.rawurlencode($tag['tagname']).'" target="_blank">'.$tag['tagname'].'</a>',
				$tag['total'],
				'<input name="tag['.$tag['tagname'].']" type="radio" class="radio" value="-1"> '.$lang['delete'].'&nbsp;<input name="tag['.$tag['tagname'].']" type="radio" class="radio" value="1"'.($tag['closed'] ? ' checked' : '').'> '.$lang['misc_tag_status_1'].'&nbsp;<input name="tag['.$tag['tagname'].']" type="radio" class="radio" value="0"'.(!$tag['closed'] ? ' checked' : '').'> '.$lang['misc_tag_status_0']
			));
		}

		showsubmit('tagsubmit', 'submit', '', '<a href="#" onclick="checkAll(\'option\', $(\'cpform\'), \'-1\')">'.lang('misc_tag_all_delete').'</a> &nbsp;<a href="#" onclick="checkAll(\'option\', $(\'cpform\'), \'1\')">'.lang('misc_tag_all_close').'</a> &nbsp;<a href="#" onclick="checkAll(\'option\', $(\'cpform\'), \'0\')">'.lang('misc_tag_all_open').'</a>', $multipage);
		showtablefooter();
		showformfooter();

	}

} elseif($operation == 'custommenu') {

	if(!$do) {

		if(!submitcheck('optionsubmit')) {
			$page = max(1, intval($page));
			$mpp = 10;
			$startlimit = ($page - 1) * $mpp;
			$num = $db->result_first("SELECT count(*) FROM {$tablepre}admincustom WHERE uid='$discuz_uid' AND sort='1'");
			$multipage = $inajax ? multi($num, $mpp, $page, $BASESCRIPT.'?action=misc&operation=custommenu', 0, 3, TRUE, TRUE) :
				multi($num, $mpp, $page, $BASESCRIPT.'?action=misc&operation=custommenu');
			$optionlist = $ajaxoptionlist = '';
			$query = $db->query("SELECT id, title, displayorder, url FROM {$tablepre}admincustom WHERE uid='$discuz_uid' AND sort='1' ORDER BY displayorder, dateline DESC, clicks DESC LIMIT $startlimit, $mpp");
			while($custom = $db->fetch_array($query)) {
				$optionlist .= showtablerow('', array('class="td25"', 'class="td28"', '', 'class="td26"'), array(
					"<input type=\"checkbox\" class=\"checkbox\" name=\"delete[]\" value=\"$custom[id]\">",
					"<input type=\"text\" class=\"txt\" size=\"3\" name=\"displayordernew[$custom[id]]\" value=\"$custom[displayorder]\">",
					"<input type=\"text\" class=\"txt\" size=\"25\" name=\"titlenew[$custom[id]]\" value=\"".lang($custom['title'])."\"><input type=\"hidden\" name=\"langnew[$custom[id]]\" value=\"$custom[title]\">",
					"<input type=\"text\" class=\"txt\" size=\"40\" name=\"urlnew[$custom[id]]\" value=\"$custom[url]\">"
				), TRUE);
				$ajaxoptionlist .= '<li><a href="'.$custom['url'].'" target="'.(substr($custom['url'], 0, 19) == $BASESCRIPT.'?action=' ? 'main' : '_blank').'">'.lang($custom['title']).'</a></li>';
			}

			if($inajax) {
				ajaxshowheader();
				echo $ajaxoptionlist.'<li>'.$multipage.'</li><script reload="1">initCpMenus(\'custommenu\');parent.cmcache=true;</script>';
				ajaxshowfooter();
				exit;
			}

			echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[
			[1,'', 'td25'],
			[1,'<input type="text" class="txt" name="newdisplayorder[]" size="3">', 'td28'],
			[1,'<input type="text" class="txt" name="newtitle[]" size="25">'],
			[1,'<input type="text" class="txt" name="newurl[]" size="40">', 'td26']
		]
	];
</script>
EOT;
			shownav('tools', 'nav_custommenu');
			showsubmenu('nav_custommenu');
			showformheader('misc&operation=custommenu');
			showtableheader();
			showsubtitle(array('', 'display_order', 'name', 'URL'));
			echo $optionlist;
			echo '<tr><td></td><td colspan="3"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['misc_custommenu_add'].'</a></div></td></tr>';
			showsubmit('optionsubmit', 'submit', 'del', '', $multipage);
			showtablefooter();
			showformfooter();

		} else {

			if($ids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}admincustom WHERE id IN ($ids) AND uid='$discuz_uid'");
			}

			if(is_array($titlenew)) {
				foreach($titlenew as $id => $title) {
					$title = dhtmlspecialchars($langnew[$id] && lang($langnew[$id], false) ? $langnew[$id] : $title);
					$db->query("UPDATE {$tablepre}admincustom SET title='$title', displayorder='$displayordernew[$id]', url='".dhtmlspecialchars($urlnew[$id])."' WHERE id='$id'");
				}
			}

			if(is_array($newtitle)) {
				foreach($newtitle as $k => $v) {
					$db->query("INSERT INTO {$tablepre}admincustom (title, displayorder, url, sort, uid) VALUES ('".dhtmlspecialchars($v)."', '".intval($newdisplayorder[$k])."', '".dhtmlspecialchars($newurl[$k])."', '1', '$discuz_uid')");
				}
			}

			cpmsg('custommenu_edit_succeed', $BASESCRIPT.'?action=misc&operation=custommenu', 'succeed', '<script type="text/JavaScript">parent.cmcache=false;</script>');

		}

	} elseif($do == 'add') {

		if($title && $url) {
			admincustom($title, dhtmlspecialchars($url), 1);
			cpmsg('custommenu_add_succeed', $BASESCRIPT.'?'.$url, 'succeed', '<script type="text/JavaScript">parent.cmcache=false;</script>');
		} else {
			cpmsg('undefined_action', '', 'error');
		}

	} elseif($do == 'clean') {

		if(!$confirmed) {
			cpmsg('custommenu_history_delete_confirm', "$BASESCRIPT?action=misc&operation=custommenu&do=clean", 'form');
		} else {
			$db->query("DELETE FROM {$tablepre}admincustom WHERE uid='$discuz_uid' AND sort='0'");
			cpmsg('custommenu_history_delete_succeed', '#', 'succeed', '<script type="text/JavaScript">setTimeout("parent.location.reload();", 2999);</script>');
		}

	} else {
		cpmsg('undefined_action');
	}

} elseif($operation == 'customnav') {

	if(!$do) {

		if(!submitcheck('submit')) {

			shownav('style', 'settings_styles');
			showsubmenu('settings_styles', array(
				array('nav_settings_global', 'settings&operation=styles&anchor=global', 0),
				array('nav_settings_index', 'settings&operation=styles&anchor=index', 0),
				array('nav_settings_forumdisplay', 'settings&operation=styles&anchor=forumdisplay', 0),
				array('nav_settings_viewthread', 'settings&operation=styles&anchor=viewthread', 0),
				array('nav_settings_member', 'settings&operation=styles&anchor=member', 0),
				array('nav_settings_customnav', 'misc&operation=customnav', 1),
				array(array('menu' => 'jswizard_infoside', 'submenu' => array(
					array('jswizard_infoside_global', 'jswizard&operation=infoside&from=style'),
					array('jswizard_infoside_2', 'jswizard&operation=infoside&sideid=2&from=style'),
					array('jswizard_infoside_0', 'jswizard&operation=infoside&sideid=0&from=style'),
				))),
				array('nav_settings_refresh', 'settings&operation=styles&anchor=refresh', 0),
				array('nav_settings_sitemessage', 'settings&operation=styles&anchor=sitemessage', 0)
			));
			showformheader('misc&operation=customnav');
			showtableheader();
			showsubtitle(array('', 'display_order', 'name', 'url', 'type', 'available', ''));

			$navlist = $subnavlist = array();
			$query = $db->query("SELECT * FROM {$tablepre}navs ORDER BY displayorder");
			while($nav = $db->fetch_array($query)) {
				if($nav['parentid']) {
					$subnavlist[$nav['parentid']][] = $nav;
				} else {
					$navlist[$nav['id']] = $nav;
				}
			}
			foreach($navlist as $nav) {
				showtablerow('', array('class="td25"', 'class="td25"', 'width="210"'), array(
					$nav['type'] == '0' ? '' : "<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$nav[id]\">",
					"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[$nav[id]]\" value=\"$nav[displayorder]\">",
					"<div><input type=\"text\" class=\"txt\" size=\"15\" name=\"namenew[$nav[id]]\" value=\"".dhtmlspecialchars($nav['name'])."\">".($nav['type'] == '1' ? "<a href=\"###\" onclick=\"addrowdirect=1;addrow(this, 1, $nav[id])\" class=\"addchildboard\">$lang[misc_customnav_add_submenu]</a>" : '').'</div>',
					$nav['type'] == '0' ? $nav['url'] : "<input type=\"text\" class=\"txt\" size=\"15\" name=\"urlnew[$nav[id]]\" value=\"".dhtmlspecialchars($nav['url'])."\">",
					lang($nav['type'] == '0' ? 'inbuilt' : 'costom'),
					$nav['id'] == 1 ? "<input type=\"hidden\" name=\"availablenew[$nav[id]]\" value=\"1\">" : "<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$nav[id]]\" value=\"1\" ".($nav['available'] ? 'checked' : '').">",
					"<a href=\"$BASESCRIPT?action=misc&operation=customnav&do=edit&id=$nav[id]\" class=\"act\">$lang[detail]</a>"
				));
				if(!empty($subnavlist[$nav['id']])) {
					$subnavnum = count($subnavlist[$nav['id']]);
					foreach($subnavlist[$nav['id']] as $sub) {
						$subnavnum--;
						showtablerow('', array('class="td25"', 'class="td25"'), array(
							$sub['type'] == '0' ? '' : "<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$sub[id]\">",
							"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[$sub[id]]\" value=\"$sub[displayorder]\">",
							"<div class=\"".($subnavnum ? 'board' : 'lastboard')."\"><input type=\"text\" class=\"txt\" size=\"15\" name=\"namenew[$sub[id]]\" value=\"".dhtmlspecialchars($sub['name'])."\"></div>",
							$sub['type'] == '0' ? $sub['url'] : "<input type=\"text\" class=\"txt\" size=\"15\" name=\"urlnew[$sub[id]]\" value=\"".dhtmlspecialchars($sub['url'])."\">",
							lang($sub['type'] == '0' ? 'inbuilt' : 'costom'),
							"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$sub[id]]\" value=\"1\" ".($sub['available'] ? 'checked' : '').">",
							"<a href=\"$BASESCRIPT?action=misc&operation=customnav&do=edit&id=$sub[id]\" class=\"act\">$lang[detail]</a>"
						));
					}
				}
			}
			echo '<tr><td colspan="2"></td><td colspan="5"><div><a href="###" onclick="addrow(this, 0, 0)" class="addtr">'.$lang['misc_customnav_add_menu'].'</a></div></td></tr>';
			showsubmit('submit', 'submit', 'del');
			showtablefooter();
			showformfooter();

			require_once DISCUZ_ROOT.'./uc_client/client.php';
			$ucapparray = uc_app_ls();

			$applist = '';
			if(count($ucapparray) > 1) {
				$applist = $lang['misc_customnav_add_ucenter'].'<select name="applist" onchange="app(this)"><option value=""></option>';
				foreach($ucapparray as $app) {
					if($app['appid'] != UC_APPID) {
						$applist .= "<option value=\"$app[url]\">$app[name]</option>";
					}
				}
				$applist .= '</select>';
			}

			echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[[1, '', 'td25'], [1,'<input name="newdisplayorder[]" value="" size="3" type="text" class="txt">', 'td28'], [1, '<input name="newname[]" value="" size="30" type="text" class="txt">'], [1, '<input name="newurl[]" value="" size="30" type="text" class="txt">', 'td29'], [3, '$applist <input type="hidden" name="newparentid[]" value="0" />']],
		[[1, '', 'td25'], [1,'<input name="newdisplayorder[]" value="" size="3" type="text" class="txt">', 'td28'], [1, '<div class=\"board\"><input name="newname[]" value="" size="30" type="text" class="txt"></div>'], [1, '<input name="newurl[]" value="" size="30" type="text" class="txt">', 'td29'], [3, '$applist <input type="hidden" name="newparentid[]" value="{1}" />']]
	];
	function app(obj) {
		var inputs = obj.parentNode.parentNode.getElementsByTagName('input');
		for(var i = 0; i < inputs.length; i++) {
			if(inputs[i].name == 'newname[]') {
				inputs[i].value = obj.options[obj.options.selectedIndex].innerHTML;
			} else if(inputs[i].name == 'newurl[]') {
				inputs[i].value = obj.value;
			}
		}
	}
</script>
EOT;

		} else {

			if($ids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}navs WHERE id IN ($ids)");
				$db->query("DELETE FROM {$tablepre}navs WHERE parentid IN ($ids)");
			}

			if(is_array($namenew)) {
				foreach($namenew as $id => $name) {
					$name = trim(dhtmlspecialchars($name));
					$urladd = !empty($urlnew[$id]) ? ", url='".str_replace('&amp;', '&', dhtmlspecialchars($urlnew[$id]))."'" : '';
					$availablenew[$id] = $name && (!isset($urlnew[$id]) || $urlnew[$id]) && $availablenew[$id];
					$displayordernew[$id] = intval($displayordernew[$id]);
					$nameadd = !empty($name) ? ", name='$name'" : '';
					$db->query("UPDATE {$tablepre}navs SET displayorder='$displayordernew[$id]', available='$availablenew[$id]' $urladd $nameadd WHERE id='$id'");
				}
			}

			if(is_array($newname)) {
				foreach($newname as $k => $v) {
					$v = dhtmlspecialchars(trim($v));
					if(!empty($v)) {
						$newavailable = $v && $newurl[$k];
						$newparentid[$k] = intval($newparentid[$k]);
						$newdisplayorder[$k] = intval($newdisplayorder[$k]);
						$newurl[$k] = str_replace('&amp;', '&', dhtmlspecialchars($newurl[$k]));
						$db->query("INSERT INTO {$tablepre}navs (parentid, name, displayorder, url, type, available) VALUES ('$newparentid[$k]', '$v', '$newdisplayorder[$k]', '$newurl[$k]', '1', '$newavailable')");
					}
				}
			}

			updatecache('settings');
			cpmsg('nav_add_succeed', $BASESCRIPT.'?action=misc&operation=customnav', 'succeed');

		}

	} elseif($do == 'edit' && $id) {

		$nav = $db->fetch_first("SELECT * FROM {$tablepre}navs WHERE id='$id'");
		if(!$nav) {
			cpmsg('undefined_action', '', 'error');
		}

		if(!submitcheck('editsubmit')) {

			$string = sprintf('%02d', $nav['highlight']);

			shownav('global', 'misc_customnav');
			showsubmenu('settings_styles', array(
				array('nav_settings_global', 'settings&operation=styles&anchor=global', 0),
				array('nav_settings_customnav', 'misc&operation=customnav', 1),
				array('nav_settings_index', 'settings&operation=styles&anchor=index', 0),
				array('nav_settings_forumdisplay', 'settings&operation=styles&anchor=forumdisplay', 0),
				array('nav_settings_viewthread', 'settings&operation=styles&anchor=viewthread', 0),
				array('nav_settings_member', 'settings&operation=styles&anchor=member', 0),
				array('nav_settings_refresh', 'settings&operation=styles&anchor=refresh', 0)
			));
			showformheader("misc&operation=customnav&do=edit&id=$id");
			showtableheader();
			showtitle('misc_customnav_detail');
			showsetting('misc_customnav_name', 'namenew', $nav['name'], 'text');
			showsetting('misc_customnav_title', 'titlenew', $nav['title'], 'text');
			showsetting('misc_customnav_url', 'urlnew', $nav['url'], 'text', $nav['type'] == '0');
			showsetting('misc_customnav_style', array('stylenew', array(lang('misc_customnav_style_underline'), lang('misc_customnav_style_italic'), lang('misc_customnav_style_bold'))), $string[0], 'binmcheckbox');
			showsetting('misc_customnav_style_color', array('colornew', array(
				array(0, '<span style="color: '.LINK.';">Default</span>'),
				array(1, '<span style="color: Red;">Red</span>'),
				array(2, '<span style="color: Orange;">Orange</span>'),
				array(3, '<span style="color: Yellow;">Yellow</span>'),
				array(4, '<span style="color: Green;">Green</span>'),
				array(5, '<span style="color: Cyan;">Cyan</span>'),
				array(6, '<span style="color: Blue;">Blue</span>'),
				array(7, '<span style="color: Purple;">Purple</span>'),
				array(8, '<span style="color: Gray;">Gray</span>'),
			)), $string[1], 'mradio');
			showsetting('misc_customnav_url_open', array('targetnew', array(
				array(0, lang('misc_customnav_url_open_default')),
				array(1, lang('misc_customnav_url_open_blank'))
			), TRUE), $nav['target'], 'mradio');
			showsetting('plugins_edit_modules_adminid', array('levelnew', array(
				array(0, lang('nolimit')),
				array(1, lang('member')),
				array(2, lang('usergroups_system_3')),
				array(3, lang('usergroups_system_1')),
			)), $nav['level'], 'select');
			showsubmit('editsubmit');
			showtablefooter();
			showformfooter();

		} else {

			$namenew = dhtmlspecialchars(trim($namenew));
			$titlenew = dhtmlspecialchars(trim($titlenew));
			$urlnew = dhtmlspecialchars(trim($urlnew));
			$stylebin = '';
			for($i = 3; $i >= 1; $i--) {
				$stylebin .= empty($stylenew[$i]) ? '0' : '1';
			}
			$stylenew = bindec($stylebin);
			$targetnew = intval($targetnew) ? 1 : 0;
			$levelnew = intval($levelnew) && $levelnew > 0 && $levelnew < 4 ? intval($levelnew) : 0 ;

			$urladd = $nav['type'] == '1' && $urlnew ? ", url='".dhtmlspecialchars($urlnew)."'" : '';

			$db->query("UPDATE {$tablepre}navs SET name='$namenew', title='$titlenew', highlight='$stylenew$colornew', target='$targetnew', level='$levelnew' $urladd WHERE id='$id'");

			updatecache('settings');
			cpmsg('nav_add_succeed', $BASESCRIPT.'?action=misc&operation=customnav', 'succeed');

		}

	}

} elseif($operation == 'custombar') {

	$id = '';
	if(!empty($title) && !empty($url)) {
		$id = admincustom($title, dhtmlspecialchars($url), 2);
	}
	if(!empty($deleteid)) {
		$deleteid = intval($deleteid);
		$db->query("DELETE FROM {$tablepre}admincustom WHERE id='$deleteid' AND uid='$discuz_uid' AND sort='2'");
	}

	$historymenus = '';
	$query = $db->query("SELECT id, title, url FROM {$tablepre}admincustom WHERE uid='$discuz_uid' AND sort='2' ORDER BY dateline");
	while($custom = $db->fetch_array($query)) {
		$historymenus .= '<em id="custombar_'.$custom['id'].'"><a onclick="mainFrame('.$custom['id'].', this.href);doane(event)" href="'.$custom['url'].'" hidefocus="true">'.lang($custom['title']).'</a><span onclick="custombar_update('.$custom['id'].')" title="'.$lang['custombar_del'].'">&nbsp;&nbsp;</span></em>';
	}
	include template('header_ajax');
	echo $historymenus;
	include template('footer_ajax');

} elseif($operation == 'focus') {

	require_once DISCUZ_ROOT.'./include/post.func.php';

	$focus = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='focus'");
	$focus = unserialize($focus);

	if(!$do) {

		if(!submitcheck('focussubmit')) {

			shownav('adv', 'misc_focus');
			showsubmenu('misc_focus', array(
				array('config', 'misc&operation=focus&do=config', 0),
				array('admin', 'misc&operation=focus', 1),
				array(array('menu' => 'add', 'submenu' => array(
					array('misc_focus_handadd', 'misc&operation=focus&do=handadd'),
					array('misc_focus_threadadd', 'misc&operation=focus&do=threadadd'),
					array('misc_focus_autoadd', 'misc&operation=focus&do=recommend')
				)), '', 0),
			));
			showtips('misc_focus_tips');
			showformheader('misc&operation=focus');
			showtableheader('admin', 'fixpadding');
			showsubtitle(array('', 'available', 'subject', ''));
			if(is_array($focus['data'])) {
				foreach($focus['data'] as $k => $v) {
					showtablerow('', array('class="td25"', 'class="td25"'), array(
						"<input type=\"checkbox\" class=\"checkbox\" name=\"delete[]\" value=\"$k\">",
						"<input type=\"checkbox\" class=\"checkbox\" name=\"available[$k]\" value=\"1\" ".($v['available'] ? 'checked' : '').">",
						'<a href="'.$v['url'].'" target="_blank">'.$v[subject].'</a>',
						"<a href=\"$BASESCRIPT?action=misc&operation=focus&do=edit&id=$k\" class=\"act\">$lang[edit]</a>",
					));
				}
			}

			showsubmit('focussubmit', 'submit', 'del');
			showtablefooter();
			showformfooter();

		} else {

			$newfocus = array();
			$newfocus['title'] = $focus['title'];
			$newfocus['data'] = array();
			if(isset($focus['data']) && is_array($focus['data'])) foreach($focus['data'] as $k => $v) {
				if(is_array($delete) && in_array($k, $delete)) {
					unset($focus['data'][$k]);
				} else {
					$v['available'] = $available[$k] ? 1 : 0;
					$newfocus['data'][$k] = $v;
				}
			}
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('focus', '".addslashes(serialize($newfocus))."')");
			updatecache('focus');

			cpmsg('focus_update_succeed', $BASESCRIPT.'?action=misc&operation=focus', 'succeed');

		}

	} elseif($do == 'handadd') {

		if(count($focus['data']) >= 10) {
			cpmsg('focus_add_num_limit', $BASESCRIPT.'?action=misc&operation=focus', 'error');
		}

		if(!submitcheck('addsubmit')) {

			shownav('adv', 'misc_focus');
			showsubmenu('misc_focus', array(
				array('config', 'misc&operation=focus&do=config', 0),
				array('admin', 'misc&operation=focus', 0),
				array(array('menu' => 'add', 'submenu' => array(
					array('misc_focus_handadd', 'misc&operation=focus&do=handadd'),
					array('misc_focus_threadadd', 'misc&operation=focus&do=threadadd'),
					array('misc_focus_autoadd', 'misc&operation=focus&do=recommend')
				)), '', 1),
			));
			showtips('misc_focus_add_tips');
			showformheader('misc&operation=focus&do=handadd');
			showtableheader('misc_focus_handadd', 'fixpadding');
			showsetting('misc_focus_handurl', 'focus_url', '', 'text');
			showsetting('misc_focus_handsubject' , 'focus_subject', '', 'text');
			showsetting('misc_focus_handsummary', 'focus_summary', '', 'textarea');
			showsetting('misc_focus_handimg', 'focus_image', '', 'text');
			showsubmit('addsubmit', 'submit', '', '');
			showtablefooter();
			showformfooter();

		} else {

			if($focus_url && $focus_subject && $focus_summary) {
				if(is_array($focus['data'])) {
					foreach($focus['data'] as $item) {
						if($item['url'] == $focus_url) {
							cpmsg('focus_topic_exists', $BASESCRIPT.'?action=misc&operation=focus&do=threadadd', 'error');
						}
					}
				}
				$focus['data'][] = array(
					'url' => $focus_url,
					'type' => 'hand',
					'available' => '1',
					'subject' => cutstr($focus_subject, 80),
					'summary' => messagecutstr($focus_summary, 150),
					'image' => $focus_image,
					'aid' => 0,
					'filename' => basename($focus_image),
				);
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('focus', '".addslashes(serialize($focus))."')");
				updatecache('focus');
			}

			cpmsg('focus_add_succeed', $BASESCRIPT.'?action=misc&operation=focus', 'succeed');

		}

	} elseif($do == 'threadadd') {

		if(count($focus['data']) >= 10) {
			cpmsg('focus_add_num_limit', $BASESCRIPT.'?action=misc&operation=focus', 'error');
		}

		if(!submitcheck('addsubmit')) {

			shownav('adv', 'misc_focus');
			showsubmenu('misc_focus', array(
				array('config', 'misc&operation=focus&do=config', 0),
				array('admin', 'misc&operation=focus', 0),
				array(array('menu' => 'add', 'submenu' => array(
					array('misc_focus_handadd', 'misc&operation=focus&do=handadd'),
					array('misc_focus_threadadd', 'misc&operation=focus&do=threadadd'),
					array('misc_focus_autoadd', 'misc&operation=focus&do=recommend')
				)), '', 1),
			));
			showtips('misc_focus_add_tips');
			showformheader('misc&operation=focus&do=threadadd');
			showtableheader('misc_focus_threadadd', 'fixpadding');
			showsetting('misc_focus_threadurl', 'focus_url', '', 'text');
			echo '<input type="hidden" value="" name="focus_tid" id="focus_tid" />';
			showtagheader('tbody', 'focus_detail');
			showtagfooter('tbody');
			showtagheader('tbody', 'fetchthread', TRUE);
			echo '<tr><td colspan="15"><div class="fixsel"><input type="button" value="'.lang('misc_focus_get_threadcontent').'" name="fetchthread" class="btn" onclick="fetchThread();" /></div></td></tr>';
			showtagfooter('tbody');
			showtagheader('tbody', 'addsubmit');
			showsubmit('addsubmit', 'submit', '', '<input type="button" class="btn" name="settingsubmit" value="'.lang('misc_focus_reget_threadcontent').'" onclick="fetchThread();" class="act lightlink normal" />');
			showtagfooter('tbody');
			showtablefooter();
			showformfooter();
			echo <<<EOT
<script type="text/JavaScript">
	function fetchThread() {
		var focustid = '';
		var focusurl = $('cpform').focus_url.value;
		var re = /tid=(\d+)/ig;
		var matches = re.exec(focusurl);

		if(matches != null) {
			focustid = matches[1];
		} else {
			re = /thread-(\d+)-/ig;
			matches = re.exec(focusurl);
			if(matches != null) {
				focustid = matches[1];
			}
		}
		if(focustid) {
			ajaxget('$BASESCRIPT?action=misc&operation=focus&do=fetchthread&id=' + focustid, 'focus_detail');
			$('fetchthread').style.display = 'none';
			$('focus_detail').style.display = '';
			$('addsubmit').style.display = '';
			$('focus_tid').value = focustid;
		} else {
			alert('$lang[misc_focus_invalidurl]');
		}
	}
</script>
EOT;
		} else {

			if($focus_tid && $focus_subject && $focus_summary) {
				$focus_url = 'viewthread.php?tid='.$focus_tid;
				foreach($focus['data'] as $item) {
					if($item['url'] == $focus_url) {
						cpmsg('focus_topic_exists', $BASESCRIPT.'?action=misc&operation=focus&do=threadadd', 'error');
					}
				}
				$focus['data'][] = array(
					'url' => $focus_url,
					'tid' => $focus_tid,
					'type' => 'thread',
					'available' => '1',
					'subject' => cutstr($focus_subject, 80),
					'summary' => messagecutstr($focus_summary, 150),
					'image' => ($focus_aid = intval($focus_aid)) ? "image.php?aid=$focus_aid&size=58x58&key=".rawurlencode(authcode($focus_aid."\t58\t58", 'ENCODE', $_DCACHE['settings']['authkey'])) : '',
					'aid' => $focus_aid,
					'filename' => $focus_filename,
				);
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('focus', '".addslashes(serialize($focus))."')");
				updatecache('focus');
			}

			cpmsg('focus_add_succeed', $BASESCRIPT.'?action=misc&operation=focus', 'succeed');

		}

	} elseif($do == 'recommend') {

		if(count($focus['data']) >= 10) {
			cpmsg('focus_add_num_limit', $BASESCRIPT.'?action=misc&operation=focus', 'error');
		}

		$focustids = $comma = '';
		if(is_array($focus['data'])) {
			foreach($focus['data'] as $k => $v) {
				$focustids .= $comma.$k;
				$comma = ',';
			}
		}

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * 10;

		$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}forumrecommend".($focustids ? " WHERE tid NOT IN ($focustids)" : ''));
		$multipage = multi($num, 10, $page, $BASESCRIPT.'?action=misc&operation=focus&do=recommend');

		$recommends = array();
		$query = $db->query("SELECT f.fid, f.tid, f.moderatorid, f.aid, m.username as moderator, p.authorid, p.author, p.subject, p.message, a.filename FROM {$tablepre}forumrecommend f
			LEFT JOIN {$tablepre}members m ON f.moderatorid=m.uid
			LEFT JOIN {$tablepre}posts p ON p.tid=f.tid AND p.first='1'
			LEFT JOIN {$tablepre}attachments a ON a.aid=f.aid
			".($focustids ? " WHERE f.tid NOT IN ($focustids)" : '')."
			LIMIT $start_limit, 10");
		while($recommend = $db->fetch_array($query)) {
			$recommends[$recommend['tid']] = $recommend;
		}

		if(!submitcheck('recommendsubmit')) {

			shownav('adv', 'misc_focus');
			showsubmenu('misc_focus', array(
				array('config', 'misc&operation=focus&do=config', 0),
				array('admin', 'misc&operation=focus', 0),
				array(array('menu' => 'add', 'submenu' => array(
					array('misc_focus_handadd', 'misc&operation=focus&do=handadd'),
					array('misc_focus_threadadd', 'misc&operation=focus&do=threadadd'),
					array('misc_focus_autoadd', 'misc&operation=focus&do=recommend')
				)), '', 1),
			));
			showtips('misc_focus_recommend_tips');
			showformheader('misc&operation=focus&do=recommend&page='.$page);
			showtableheader('misc_focus_autoadd', 'fixpadding');
			showsubtitle(array('', 'subject', 'forum', 'author', 'misc_focus_recommender'));

			require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
			foreach($recommends as $recommend) {
				showtablerow('', array('class="td25"', '', '', '', 'class="td25"'), array(
					"<input type=\"checkbox\" class=\"checkbox\" name=\"recommendtids[]\" value=\"$recommend[tid]\" checked=\"checked\" />",
					'<a href="viewthread.php?tid='.$recommend['tid'].'" target="_blank">'.$recommend['subject'].'</a>',
					'<a href="forumdisplay.php?fid='.$recommend['fid'].'" target="_blank">'.$_DCACHE['forums'][$recommend['fid']]['name'].'</a>',
					'<a href="space.php?uid='.$recommend['authorid'].'" target="_blank">'.$recommend['author'].'</a>',
					$recommend['moderatorid'] ? '<a href="space.php?uid='.$recommend['moderatorid'].'" target="_blank">'.$recommend['moderator'].'</a>' : 'System'
				));
			}

			showsubmit('recommendsubmit', 'submit', '<input name="chkall" id="chkall" type="checkbox" class="checkbox" checked="checked" onclick="checkAll(\'prefix\', this.form, \'recommendtids\', \'chkall\')" /><label for="chkall">'.lang('select_all').'</label>', '', $multipage);
			showtablefooter();
			showformfooter();

		} else {

			if(is_array($recommendtids) && !empty($recommendtids)) {

				$num = count($focus['data']);
				foreach($recommendtids as $recommendtid) {
					if($num >= 10) {
						break;
					}
					$focus_url = 'viewthread.php?tid='.$recommendtid;
					foreach($focus['data'] as $item) {
						if($item['url'] == $focus_url) {
							continue 2;
						}
					}
					$num++;
					$focus['data'][] = array(
						'url' => $focus_url,
						'tid' => $recommendtid,
						'type' => 'thread',
						'available' => '1',
						'subject' => cutstr($recommends[$recommendtid]['subject'], 80),
						'summary' => messagecutstr($recommends[$recommendtid]['message'], 150),
						'image' => $focus_image,
						'aid' => $recommends[$recommendtid]['aid'],
						'filename' => $recommends[$recommendtid]['filename'],
					);
				}

				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('focus', '".addslashes(serialize($focus))."')");
				updatecache('focus');
			}

			cpmsg('focus_add_succeed', $BASESCRIPT.'?action=misc&operation=focus', 'succeed');
		}

	} elseif($do == 'edit') {

		if(!$item = $focus['data'][$id]) {
			cpmsg('focus_topic_noexists', $BASESCRIPT.'?action=misc&operation=focus', 'error');
		}
		if(!submitcheck('editsubmit')) {

			shownav('adv', 'misc_focus');
			showsubmenu('misc_focus', array(
				array('config', 'misc&operation=focus&do=config', 0),
				array('admin', 'misc&operation=focus', 0),
				array(array('menu' => 'add', 'submenu' => array(
					array('misc_focus_handadd', 'misc&operation=focus&do=handadd'),
					array('misc_focus_threadadd', 'misc&operation=focus&do=threadadd'),
					array('misc_focus_autoadd', 'misc&operation=focus&do=recommend')
				)), '', 0),
			));

			showformheader('misc&operation=focus&do=edit&id='.$id);
			showtableheader('misc_focus_edit', 'fixpadding');
			if($item['type'] == 'thread') {
				showsetting('misc_focus_threadurl', '', '', '<a href="'.$item['url'].'" target="_blank">'.$item['url'].'</a>');
				showsetting('misc_focus_topic_subject', 'focus_subject', $item['subject'], 'text');
				showsetting('misc_focus_topic_msg', 'focus_summary', $item['summary'], 'textarea');

				$attachlist = $attachkeys = array();
				$attachlist[] = array('', lang('select'));
				$attachlist[] = array(0, lang('misc_focus_noimage'));
				$thread = $db->fetch_first("SELECT pid, attachment FROM {$tablepre}posts WHERE tid='$item[tid]' AND first='1'");
				if($thread['attachment']) {
					$query = $db->query("SELECT aid, filename FROM {$tablepre}attachments WHERE pid='$thread[pid]' AND isimage IN ('1', '-1')");
					while($attach = $db->fetch_array($query)) {
						$attachlist[] = array($attach['aid'], $attach['filename']);
						$attachkeys[$attach['aid']] = rawurlencode(authcode("$attach[aid]\t58\t58", 'ENCODE', $_DCACHE['settings']['authkey']));
					}
				}
				if(!$attachlist && $item['aid']) {
					$attachlist[] = array($item['aid'], $item['filename']);
					$attachkeys[$item['aid']] = rawurlencode(authcode($item['aid']."\t58\t58", 'ENCODE', $_DCACHE['settings']['authkey']));
				}
				if($attachkeys) {
					showsetting('misc_focus_image', array('focus_aid', $attachlist), $item['aid'], 'select');
					showsetting('', 'focus_img', '', '<div id="focus_img_preview">'.($item['aid'] ? '<img src="image.php?aid='.$item['aid'].'&size=58x58&key='.$attachkeys[$item['aid']].'" />' : '').'</div><input type="hidden" name="focus_filename" value="" />');
				}

				echo '<script type="text/JavaScript">var attachkeys = [];';
				foreach($attachkeys as $aid => $key) {
					echo 'attachkeys['.$aid.'] = \''.$key.'\';';
				}
				echo "$('cpform').focus_aid.onchange = function() {\n";
				echo "$('focus_img_preview').innerHTML = this.value > 0 ? '<img src=\"image.php?aid=' + this.value + '&size=58x58&key=' + attachkeys[this.value] + '&nocache=yes\" />' : '';";
				echo "this.form.focus_filename.value = this.options[this.options.selectedIndex].innerHTML;";
				echo "};";
				echo "</script>";
			} else {
				showsetting('misc_focus_handurl', 'focus_url', $item['url'], 'text');
				showsetting('misc_focus_handsubject' , 'focus_subject', $item['subject'], 'text');
				showsetting('misc_focus_handsummary', 'focus_summary', $item['summary'], 'textarea');
				showsetting('misc_focus_handimg', 'focus_image', $item['image'], 'text');
			}

			showsubmit('editsubmit', 'submit');
			showtablefooter();
			showformfooter();

		} else {

			if($focus_subject && $focus_summary) {
				if($item['type'] == 'thread') {
					$focus_url = $item['url'];
				} else {
					$focus_filename = basename($focus_image);
				}
				$item = array(
					'url' => $focus_url,
					'tid' => $item['tid'],
					'type' => $item['type'],
					'available' => '1',
					'subject' => cutstr($focus_subject, 80),
					'summary' => messagecutstr($focus_summary, 150),
					'image' => ($focus_aid = intval($focus_aid)) ? "image.php?aid=$focus_aid&size=58x58&key=".rawurlencode(authcode($focus_aid."\t58\t58", 'ENCODE', $_DCACHE['settings']['authkey'])) : $focus_image,
					'aid' => $focus_aid,
					'filename' => $focus_filename,
				);
				$focus['data'][$id] = $item;
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('focus', '".addslashes(serialize($focus))."')");
				updatecache('focus');
			}

			cpmsg('focus_edit_succeed', $BASESCRIPT.'?action=misc&operation=focus', 'succeed');

		}

	} elseif($do == 'config') {

		if(!submitcheck('confsubmit')) {

			shownav('adv', 'misc_focus');
			showsubmenu('misc_focus', array(
				array('config', 'misc&operation=focus&do=config', 1),
				array('admin', 'misc&operation=focus', 0),
				array(array('menu' => 'add', 'submenu' => array(
					array('misc_focus_handadd', 'misc&operation=focus&do=handadd'),
					array('misc_focus_threadadd', 'misc&operation=focus&do=threadadd'),
					array('misc_focus_autoadd', 'misc&operation=focus&do=recommend')
				)), '', 0),
			));
			showformheader('misc&operation=focus&do=config');
			showtableheader('config', 'fixpadding');
			showsetting('misc_focus_area_title', 'focus_title', empty($focus['title']) ? lang('misc_focus') : $focus['title'], 'text');
			showsubmit('confsubmit', 'submit');
			showtablefooter();
			showformfooter();

		} else {

			$focus['title'] = trim($focus_title);
			$focus['title'] = empty($focus['title']) ? lang('misc_focus') : $focus['title'];
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('focus', '".addslashes(serialize($focus))."')");
			updatecache('focus');

			cpmsg('focus_conf_succeed', $BASESCRIPT.'?action=misc&operation=focus&do=config', 'succeed');

		}

	} elseif($do == 'fetchthread' && $id) {

		$thread = $db->fetch_first("SELECT pid, subject, message, attachment FROM {$tablepre}posts WHERE tid='$id' AND first='1'");

		ajaxshowheader();
		if(!$thread) {
			echo '<script type="text/JavaScript">alert(\''.lang('misc_focus_nothread').'\');</script>';
			ajaxshowfooter();
			exit;
		}

		showsetting('misc_focus_topic_subject', 'focus_subject', $thread['subject'], 'text');
		showsetting('misc_focus_topic_msg', 'focus_summary', messagecutstr($thread['message'], 150), 'textarea');
		if($thread['attachment']) {
			$attachlist = $attachkeys = array();
			$attachlist[] = array('', lang('select'));
			$attachlist[] = array(0, lang('misc_focus_noimage'));
			$query = $db->query("SELECT aid, filename FROM {$tablepre}attachments WHERE pid='$thread[pid]' AND isimage IN ('1', '-1')");
			while($attach = $db->fetch_array($query)) {
				$attachlist[] = array($attach['aid'], $attach['filename']);
				$attachkeys[$attach['aid']] = rawurlencode(authcode("$attach[aid]\t58\t58", 'ENCODE', $_DCACHE['settings']['authkey']));
			}
			if($attachkeys) {
				showsetting('misc_focus_image', array('focus_aid', $attachlist), '', 'select');
				showsetting('', 'focus_img', '', '<div id="focus_img_preview"></div><input type="hidden" name="focus_filename" value="" />');
			}
		}
		echo '<script type="text/JavaScript">var attachkeys = [];';
		foreach($attachkeys as $aid => $key) {
			echo 'attachkeys['.$aid.'] = \''.$key.'\';';
		}
		echo <<<EOT
	$('cpform').focus_aid.onchange = function() {
		$('focus_img_preview').innerHTML = this.value > 0 ? '<img src="image.php?aid=' + this.value + '&size=58x58&key=' + attachkeys[this.value] + '&nocache=yes" />' : '';
		this.form.focus_filename.value = this.options[this.options.selectedIndex].innerHTML;
	};
</script>
EOT;
		ajaxshowfooter();
		exit;

	}

} elseif($operation == 'checkstat') {
	if($statid && $statkey) {
		$q = "statid=$statid&statkey=$statkey";
		$q=rawurlencode(base64_encode($q));
		$url = 'http://stat.discuz.com/stat_ins.php?action=checkstat&q='.$q;
		$key = dfopen($url);
		$newstatdisable = $key == $statkey ? 0 : 1;
		if($newstatdisable != $statdisable) {
			$db->query("REPLACE {$tablepre}settings SET variable='statdisable', value='$newstatdisable'");
			require_once DISCUZ_ROOT.'./include/cache.func.php';
			updatecache('settings');
		}
	}
}
?>