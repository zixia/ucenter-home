<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: adv.inc.php 20881 2009-10-28 09:07:58Z liuqiang $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';

cpheader();

if(empty($operation)) {

	if(!submitcheck('advsubmit')) {

		require_once DISCUZ_ROOT.'./include/forum.func.php';

		shownav('adv', 'adv_admin');
		showsubmenu('adv_admin', array(
			array('config', 'adv&operation=config', 0),
			array('admin', 'adv', 1),
			array(array('menu' => 'add', 'submenu' => array(
				array('adv_type_headerbanner', 'adv&operation=advadd&type=headerbanner'),
				array('adv_type_footerbanner', 'adv&operation=advadd&type=footerbanner'),
				array('adv_type_text', 'adv&operation=advadd&type=text'),
				array('adv_type_thread', 'adv&operation=advadd&type=thread'),
				array('adv_type_interthread', 'adv&operation=advadd&type=interthread'),
				array('adv_type_float', 'adv&operation=advadd&type=float'),
				array('adv_type_couplebanner', 'adv&operation=advadd&type=couplebanner'),
				array('adv_type_intercat', 'adv&operation=advadd&type=intercat'),

			)), '', 0)
		));
		showformheader('adv');
		showtableheader();
		showsubtitle(array('', 'display_order', 'available', 'subject', 'type', 'adv_style', 'start_time', 'end_time', 'adv_targets', ''));

		$advppp = 25;
		$conditions = '';
		$order_by = 'displayorder, advid DESC, targets DESC';
		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $advppp;

		$conditions .= $title ? " AND title LIKE '%$title%'" : '';
		$conditions .= $type ? " AND type='$type'" : '';
		$conditions .= $starttime ? " AND starttime>='".($timestamp - $starttime)."'" : '';
		$order_by = $orderby == 'starttime' ? 'starttime' : ($orderby == 'type' ? 'type' : ($orderby == 'displayorder' ? 'displayorder' : 'advid DESC'));

		$advnum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}advertisements WHERE 1 $conditions");

		$query = $db->query("SELECT * FROM {$tablepre}advertisements WHERE 1 $conditions ORDER BY available DESC, $order_by LIMIT $start_limit, $advppp");
		while($adv = $db->fetch_array($query)) {
			$adv['type'] = $lang['adv_type_'.$adv['type']];
			$adv['targets'] = showtargets($adv);
			$adv['parameters'] = unserialize($adv['parameters']);

			showtablerow('', array('class="td25"', 'class="td28"', 'class="td25"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$adv[advid]\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[$adv[advid]]\" value=\"$adv[displayorder]\">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$adv[advid]]\" value=\"1\" ".($adv['available'] ? 'checked' : '').">",
				"<input type=\"text\" class=\"txt\" size=\"15\" name=\"titlenew[$adv[advid]]\" value=\"".dhtmlspecialchars($adv['title'])."\">",
				$adv[type],
				$lang['adv_style_'.$adv['parameters']['style']],
				$adv['starttime'] ? gmdate($dateformat, $adv['starttime'] + $_DCACHE['settings']['timeoffset'] * 3600) : $lang['unlimited'],
				$adv['endtime'] ? gmdate($dateformat, $adv['endtime'] + $_DCACHE['settings']['timeoffset'] * 3600) : $lang['unlimited'],
				$adv[targets],
				"<a href=\"$BASESCRIPT?action=adv&operation=advedit&advid=$adv[advid]\" class=\"act\">$lang[detail]</a>"
			));
		}

		$multipage = multi($advnum, $advppp, $page, $BASESCRIPT.'?action=adv'.($title ? "&title=$title" : '').($type ? "&type=$type" : '').($starttime ? "&starttime=$starttime" : '').($orderby ? "&orderby=$orderby" : ''), 0, 3, TRUE, TRUE);

		$starttimecheck = array($starttime => 'selected="selected"');
		$typecheck = array($type => 'selected="selected"');
		$orderbycheck = array($orderby => 'selected="selected"');
		$title = isset($title) ? $title : $lang['adv_inputtitle'];

		showsubmit('advsubmit', 'submit', 'del', '', $multipage.'
<input type="text" class="txt" name="title" value="'.$title.'" size="15" onclick="this.value=\'\'"> &nbsp;&nbsp;
<select name="starttime">
<option value=""> '.lang('start_time').'</option>
<option value="0" '.$starttimecheck['0'].'> '.lang('all').'</option>
<option value="86400" '.$starttimecheck['86400'].'> '.lang('1_day').'</option>
<option value="604800" '.$starttimecheck['604800'].'> '.lang('7_day').'</option>
<option value="2592000" '.$starttimecheck['2592000'].'> '.lang('30_day').'</option>
<option value="7776000" '.$starttimecheck['7776000'].'> '.lang('90_day').'</option>
<option value="15552000" '.$starttimecheck['15552000'].'> '.lang('180_day').'</option>
<option value="31536000" '.$starttimecheck['31536000'].'> '.lang('365_day').'</option>
</select> &nbsp;&nbsp;
<select name="type">
<option value=""> '.lang('adv_type').'</option>
<option value="0" '.$typecheck['0'].'> '.lang('all').'</option>
<option value="headerbanner" '.$typecheck['headerbanner'].'> '.lang('adv_type_headerbanner').'
</option><option value="footerbanner" '.$typecheck['footerbanner'].'> '.lang('adv_type_footerbanner').'</option>
<option value="text" '.$typecheck['text'].'> '.lang('adv_type_text').'</option>
<option value="thread" '.$typecheck['thread'].'> '.lang('adv_type_thread').'</option>
<option value="interthread" '.$typecheck['interthread'].'> '.lang('adv_type_interthread').'</option>
<option value="float" '.$typecheck['float'].'> '.lang('adv_type_float').'</option>
<option value="couplebanner" '.$typecheck['couplebanner'].'> '.lang('adv_type_couplebanner').'</option>
<option value="intercat" '.$typecheck['intercat'].'> '.lang('adv_type_intercat').'</option>
</select>
<select name="orderby">
<option value=""> '.lang('adv_orderby').'</option>
<option value="starttime" '.$orderbycheck['starttime'].'> '.lang('adv_addtime').'</option>
<option value="type" '.$orderbycheck['type'].'> '.lang('adv_type').'</option>
<option value="displayorder" '.$orderbycheck['displayorder'].'> '.lang('display_order').'</option>
</select> &nbsp;&nbsp;
<input type="submit" class="btn" name="searchsubmit" value="'.lang('search').'" onclick="if(this.form.title.value==\''.lang('adv_inputtitle').'\'){this.form.title.value=\'\'}window.loacation=\''.$BASESCRIPT.'?action=adv&title=\'+this.form.title.value+\'&starttime=\'+this.form.starttime.value+\'&type=\'+this.form.type.value+\'&orderby=\'+this.form.orderby.value;"> &nbsp;
		');
		showtablefooter();
		showformfooter();

	} else {

		if($advids = implodeids($delete)) {
			$db->query("DELETE FROM {$tablepre}advertisements WHERE advid IN ($advids)");
		}

		if(is_array($titlenew)) {
			foreach($titlenew as $advid => $title) {
				$db->query("UPDATE {$tablepre}advertisements SET available='$availablenew[$advid]', displayorder='$displayordernew[$advid]', title='".cutstr($titlenew[$advid], 50)."' WHERE advid='$advid'", 'UNBUFFERED');
			}
		}

		updatecache(array('settings', 'advs_archiver', 'advs_register', 'advs_index', 'advs_forumdisplay', 'advs_viewthread'));

		cpmsg('adv_update_succeed', $BASESCRIPT.'?action=adv', 'succeed');

	}

} elseif($operation == 'advadd' && in_array($type, array('headerbanner', 'footerbanner', 'text', 'thread', 'interthread', 'float', 'couplebanner', 'intercat')) || ($operation == 'advedit' && $advid)) {

	if(!submitcheck('advsubmit')) {

		require_once DISCUZ_ROOT.'./include/forum.func.php';

		shownav('adv', 'adv_admin');
		showsubmenu('adv_admin', array(
			array('config', 'adv&operation=config', 0),
			array('admin', 'adv', 0),
			array(array('menu' => 'add', 'submenu' => array(
				array('adv_type_headerbanner', 'adv&operation=advadd&type=headerbanner'),
				array('adv_type_footerbanner', 'adv&operation=advadd&type=footerbanner'),
				array('adv_type_text', 'adv&operation=advadd&type=text'),
				array('adv_type_thread', 'adv&operation=advadd&type=thread'),
				array('adv_type_interthread', 'adv&operation=advadd&type=interthread'),
				array('adv_type_float', 'adv&operation=advadd&type=float'),
				array('adv_type_couplebanner', 'adv&operation=advadd&type=couplebanner'),
				array('adv_type_intercat', 'adv&operation=advadd&type=intercat'),

			)), '', $operation == 'advadd' ? 1 : 0)
		));

		if($operation == 'advedit') {
			$adv = $db->fetch_first("SELECT * FROM {$tablepre}advertisements WHERE advid='$advid'");
			if(!$adv) {
				cpmsg('undefined_action', '', 'error');
			}
			$adv['parameters'] = unserialize($adv['parameters']);
			if(in_array($adv['type'], array('footerbanner', 'thread'))) {
				if($adv['type'] == 'thread') {
					$dispchecked = array();
					foreach((isset($adv['parameters']['displayorder']) ? explode("\t", $adv['parameters']['displayorder']) : array('0')) as $postcount) {
						$dispchecked[$postcount] = ' selected="selected"';
					}
				}
			} elseif($adv['type'] == 'intercat') {
				if(is_array($adv['parameters']['position'])) {
					$positionchecked = array();
					foreach($adv['parameters']['position'] as $position) {
						$positionchecked[$position] = ' selected="selected"';
					}
				} else {
					$positionchecked = array(0 => ' selected="selected"');
				}
			}
			$type = $adv['type'];
		} else {
		        $title = cutstr($title, 50);
		        $style = in_array($style, array('text', 'image', 'flash')) ? $style : 'code';
			$adv = array('type' => $type, 'title' => $title, 'parameters' => array('style' => $style), 'starttime' => $timestamp);
			$positionchecked = $type == 'intercat' ? array(0 => ' selected="selected"') : array(1 => 'checked');
			$dispchecked = array(0 => ' selected="selected"');
		}

		$adv['targets'] = $adv['targets'] != '' && $adv['targets'] != 'forum' ? explode("\t", $adv['targets']) : array('all');

		if($type == 'intercat') {
			$targetsselect = '<select name="advnew[targets][]" disabled="disabled"><option value="0">&nbsp;&nbsp;> '.$lang['home'].'</option></select>';
		} else {
			$targetsselect = '<select name="advnew[targets][]" size="10" multiple="multiple"><option value="all">&nbsp;&nbsp;> '.$lang['all'].'</option>'.
				'<option value="">&nbsp;</option>'.
				(in_array($type, array('thread', 'interthread')) ? '' : '<option value="0">&nbsp;&nbsp;> '.$lang['home'].'</option>').
				(in_array($type, array('headerbanner', 'footerbanner')) ? '</option><option value="register">&nbsp;&nbsp;> '.$lang['adv_register'].'</option>'.
				'</option><option value="redirect">&nbsp;&nbsp;> '.$lang['adv_jump'].'</option>'.
				'</option><option value="archiver">&nbsp;&nbsp;> Archiver</option>' : '').
				'</option>'.forumselect(in_array($type, array('headerbanner', 'footerbanner', 'text', 'float', 'couplebanner')) ? TRUE : FALSE, 0, 0, TRUE).'</select>';

			foreach($adv['targets'] as $target) {
				$target = substr($target, 0, 3) == 'gid' ? substr($target, 3) : $target;
				$targetsselect = preg_replace("/(\<option value=\"$target\")([^\>]*)(\>)/", "\\1 \\2 selected=\"selected\" \\3", $targetsselect);
			}
		}
		if($type == 'thread') {
			$dispselect = '<select name="advnew[displayorder][]" size="10" multiple="multiple"><option value="0"'.$dispchecked[0].'>&nbsp;&nbsp;> '.$lang['all'].'</option><option value="0">&nbsp;</option>';
			for($i = 1; $i <= $ppp; $i ++) {
				$dispselect .= '<option value="'.$i.'"'.$dispchecked[$i].'>&nbsp;&nbsp;> #'.$i.'</option>';
			}
			$dispselect .= '</select>';
		} elseif($type == 'intercat') {
			$positionselect = '<select name="advnew[position][]" size="10" multiple="multiple"><option value="0"'.$positionchecked[0].'>&nbsp;&nbsp;> '.$lang['all'].'</option><option value="">&nbsp;</option>';
			foreach($_DCACHE['forums'] as $fid => $forum) {
				if($forum['type'] == 'group') {
					$positionselect .= '<option value="'.$fid.'"'.$positionchecked[$fid].'>'.$forum['name'].'</option>';
				}
			}
			$positionselect .= '</select>';
		}

		$adv['starttime'] = $adv['starttime'] ? gmdate('Y-n-j', $adv['starttime'] + $_DCACHE['settings']['timeoffset'] * 3600) : '';
		$adv['endtime'] = $adv['endtime'] ? gmdate('Y-n-j', $adv['endtime'] + $_DCACHE['settings']['timeoffset'] * 3600) : '';

		$styleselect = array($adv['parameters']['style'] => 'selected');

		showtips('adv_type_'.$adv['type'].'_tips');

		echo '<script type="text/javascript" src="include/js/calendar.js"></script>';
		showformheader("adv&operation=$operation".($operation == 'advadd' ? '&type='.$type : '&advid='.$advid));

		if($operation == 'advadd') {
			$title = $lang['adv_add'].' - '.$lang['adv_type_'.$type];
		} else {
			$title = $lang['adv_edit'].' - '.$lang['adv_type_'.$adv['type']].' - '.$adv['title'];
		}

		showtableheader();
		showtitle($title);

		showsetting('adv_edit_title', 'advnew[title]', $adv['title'], 'text');
		showsetting('adv_edit_targets', '', '', $targetsselect);
		if($adv['type'] == 'thread') {
			showsetting('adv_edit_position_thread', array('advnew[position]', array(
				array(1, $lang['adv_thread_down']),
				array(2, $lang['adv_thread_up']),
				array(3, $lang['adv_thread_right'])
			), TRUE), $adv['parameters']['position'], 'mradio');
			showsetting('adv_edit_display_position', '', '', $dispselect);
		} elseif($adv['type'] == 'footerbanner') {
			showsetting('adv_edit_position_footerbanner', array('advnew[position]', array(
				array(1, $lang['adv_up']),
				array(2, $lang['adv_middle']),
				array(3, $lang['adv_down'])
			), TRUE), $adv['parameters']['position'], 'mradio');
		} elseif($adv['type'] == 'intercat') {
			showsetting('adv_edit_position_intercat', '', '', $positionselect);
		}
		showsetting('adv_edit_starttime', 'advnew[starttime]', $adv['starttime'], 'calendar');
		showsetting('adv_edit_endtime', 'advnew[endtime]', $adv['endtime'], 'calendar');
		showsetting('adv_edit_style', '', '', '<select name="advnew[style]" onchange="var styles, key;styles=new Array(\'code\',\'text\',\'image\',\'flash\'); for(key in styles) {var obj=$(\'style_\'+styles[key]); obj.style.display=styles[key]==this.options[this.selectedIndex].value?\'\':\'none\';}"><option value="code" '.$styleselect['code'].'> '.$lang['adv_style_code'].'</option><option value="text" '.$styleselect['text'].'> '.$lang['adv_style_text'].'</option><option value="image" '.$styleselect['image'].'> '.$lang['adv_style_image'].'</option><option value="flash" '.$styleselect['flash'].'> '.$lang['adv_style_flash'].'</option></select>');

		showtagheader('tbody', 'style_code', $adv['parameters']['style'] == 'code');
		showtitle('adv_edit_style_code');
		showsetting('adv_edit_style_code_html', 'advnew[code][html]', $adv['parameters']['html'], 'textarea');
		showtagfooter('tbody');

		showtagheader('tbody', 'style_text', $adv['parameters']['style'] == 'text');
		showtitle('adv_edit_style_text');
		showsetting('adv_edit_style_text_title', 'advnew[text][title]', $adv['parameters']['title'], 'text');
		showsetting('adv_edit_style_text_link', 'advnew[text][link]', $adv['parameters']['link'], 'text');
		showsetting('adv_edit_style_text_size', 'advnew[text][size]', $adv['parameters']['size'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'style_image', $adv['parameters']['style'] == 'image');
		showtitle('adv_edit_style_image');
		showsetting('adv_edit_style_image_url', 'advnew[image][url]', $adv['parameters']['url'], 'text');
		showsetting('adv_edit_style_image_link', 'advnew[image][link]', $adv['parameters']['link'], 'text');
		showsetting('adv_edit_style_image_width', 'advnew[image][width]', $adv['parameters']['width'], 'text');
		showsetting('adv_edit_style_image_height', 'advnew[image][height]', $adv['parameters']['height'], 'text');
		showsetting('adv_edit_style_image_alt', 'advnew[image][alt]', $adv['parameters']['alt'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'style_flash', $adv['parameters']['style'] == 'flash');
		showtitle('adv_edit_style_flash');
		showsetting('adv_edit_style_flash_url', 'advnew[flash][url]', $adv['parameters']['url'], 'text');
		showsetting('adv_edit_style_flash_width', 'advnew[flash][width]', $adv['parameters']['width'], 'text');
		showsetting('adv_edit_style_flash_height', 'advnew[flash][height]', $adv['parameters']['height'], 'text');
		showtagfooter('tbody');

		showsubmit('advsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$advnew['starttime'] = $advnew['starttime'] ? strtotime($advnew['starttime']) : 0;
		$advnew['endtime'] = $advnew['endtime'] ? strtotime($advnew['endtime']) : 0;

		if(!$advnew['title']) {
			cpmsg('adv_title_invalid', '', 'error');
		} elseif(strlen($advnew['title']) > 50) {
			cpmsg('adv_title_more', '', 'error');
		} elseif($advnew['endtime'] && ($advnew['endtime'] <= $timestamp || $advnew['endtime'] <= $advnew['starttime'])) {
			cpmsg('adv_endtime_invalid', '', 'error');
		} elseif(($advnew['style'] == 'code' && !$advnew['code']['html'])
			|| ($advnew['style'] == 'text' && (!$advnew['text']['title'] || !$advnew['text']['link']))
			|| ($advnew['style'] == 'image' && (!$advnew['image']['url'] || !$advnew['image']['link']))
			|| ($advnew['style'] == 'flash' && (!$advnew['flash']['url'] || !$advnew['flash']['width'] || !$advnew['flash']['height']))) {
			cpmsg('adv_parameter_invalid', '', 'error');
		}

		if($operation == 'advadd') {
			$db->query("INSERT INTO {$tablepre}advertisements (available, type)
				VALUES ('1', '$type')");
			$advid = $db->insert_id();
		} else {
			$type = $db->result_first("SELECT type FROM {$tablepre}advertisements WHERE advid='$advid'");
		}

		foreach($advnew[$advnew['style']] as $key => $val) {
			$advnew[$advnew['style']][$key] = stripslashes($val);
		}

		$targetsarray = array();
		if(is_array($advnew['targets'])) {
			$gids = array();
			foreach($_DCACHE['forums'] as $fid => $forum) {
				if($forum['type'] == 'group') {
					$gids[] = $fid;
				}
			}
			foreach($advnew['targets'] as $target) {
				if(in_array($type, array('headerbanner', 'footerbanner', 'text', 'float', 'couplebanner')) && in_array($target, $gids)) {
					$targetsarray[] = 'gid'.$target;
				} elseif($target == 'all') {
					$targetsarray = in_array($type, array('thread', 'interthread')) ? array('forum') : array();
					break;
				} elseif(in_array($target, array('register', 'redirect', 'archiver')) || preg_match("/^\d+$/", $target) && ($target == 0 || in_array($_DCACHE['forums'][$target]['type'], array('forum', 'sub')))) {
					$targetsarray[] = $target;
				}
			}
		}
		$advnew['targets'] = implode("\t", $targetsarray);
		$advnew['displayorder'] = isset($advnew['displayorder']) ? implode("\t", $advnew['displayorder']) : '';
		switch($advnew['style']) {
			case 'code':
				$advnew['code'] = $advnew['code']['html'];
				break;
			case 'text':
				$advnew['code'] = '<a href="'.$advnew['text']['link'].'" target="_blank" '.($advnew['text']['size'] ? 'style="font-size: '.$advnew['text']['size'].'"' : '').'>'.$advnew['text']['title'].'</a>';
				break;
			case 'image':
				$advnew['code'] = '<a href="'.$advnew['image']['link'].'" target="_blank"><img src="'.$advnew['image']['url'].'"'.($advnew['image']['height'] ? ' height="'.$advnew['image']['height'].'"' : '').($advnew['image']['width'] ? ' width="'.$advnew['image']['width'].'"' : '').($advnew['image']['alt'] ? ' alt="'.$advnew['image']['alt'].'"' : '').' border="0"></a>';
				break;
			case 'flash':
				$advnew['code'] = '<embed width="'.$advnew['flash']['width'].'" height="'.$advnew['flash']['height'].'" src="'.$advnew['flash']['url'].'" type="application/x-shockwave-flash" wmode="transparent"></embed>';
				break;
		}

		if($type == 'intercat') {
			$advnew['position'] = is_array($advnew['position']) && !in_array('0', $advnew['position']) ? $advnew['position'] : '';
		}

		$advnew['parameters'] = addslashes(serialize(array_merge(array('style' => $advnew['style']), $advnew['style'] == 'code' ? array() : $advnew[$advnew['style']], array('html' => $advnew['code']), array('position' => $advnew['position']), array('displayorder' => $advnew['displayorder']))));
		$advnew['code'] = addslashes($advnew['code']);

		$query = $db->query("UPDATE {$tablepre}advertisements SET title='$advnew[title]', targets='$advnew[targets]', parameters='$advnew[parameters]', code='$advnew[code]', starttime='$advnew[starttime]', endtime='$advnew[endtime]' WHERE advid='$advid'");

		if($type == 'intercat') {
			updatecache('advs_index');
		} elseif(in_array($type, array('thread', 'interthread'))) {
			updatecache('advs_viewthread');
		} elseif($type == 'text') {
			updatecache(array('advs_index', 'advs_forumdisplay', 'advs_viewthread'));
		} else {
			updatecache(array('settings', 'advs_archiver', 'advs_register', 'advs_index', 'advs_forumdisplay', 'advs_viewthread'));
		}

		cpmsg('adv_succeed', $BASESCRIPT.'?action=adv', 'succeed');

	}

} elseif($operation == 'config') {

	if(!submitcheck('configsubmit')) {

		$query = $db->query("SELECT value FROM {$tablepre}settings WHERE variable='admode'");
		$admode = $db->result($query, 0);

		shownav('adv', 'adv_admin');
		showsubmenu('adv_admin', array(
			array('config', 'adv&operation=config', 1),
			array('admin', 'adv', 0),
			array(array('menu' => 'add', 'submenu' => array(
				array('adv_type_headerbanner', 'adv&operation=advadd&type=headerbanner'),
				array('adv_type_footerbanner', 'adv&operation=advadd&type=footerbanner'),
				array('adv_type_text', 'adv&operation=advadd&type=text'),
				array('adv_type_thread', 'adv&operation=advadd&type=thread'),
				array('adv_type_interthread', 'adv&operation=advadd&type=interthread'),
				array('adv_type_float', 'adv&operation=advadd&type=float'),
				array('adv_type_couplebanner', 'adv&operation=advadd&type=couplebanner'),
				array('adv_type_intercat', 'adv&operation=advadd&type=intercat'),

			)), '', 0)
		));
		showformheader('adv&operation=config');
		showtableheader();
		showsetting('adv_config_mode', array('admodenew', array(array(0, lang('adv_config_mode_0')), array(1, lang('adv_config_mode_1')))), $admode, 'select');
		showsubmit('configsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('admode', '$admodenew')");
		updatecache('settings');
		cpmsg('adv_config_succeed', $BASESCRIPT.'?action=adv&operation=config', 'succeed');

	}

}

function showtargets($adv) {
	if($adv['targets'] == '' || $adv['targets'] == 'forum') {
		return lang('all');
	} else {
		$targets = explode("\t", $adv['targets']);
		$count = count($targets);
		$max = $count > 2 ? 2 : $count;
		$t = array();
		for($i = 0;$i < $max;$i++) {
			$t[] = showtargetlink($targets[$i]);
		}
		$r = implode(', ', $t);
		if($count > 2) {
			$r .= ' ...';
		}
		return $r;
	}
}

function showtargetlink($target) {
	global $_DCACHE, $indexname, $regname;
	return substr($target, 0, 3) == 'gid' ? '<a href="'.$indexname.'?gid='.substr($target, 3).'" target="_blank">'.$_DCACHE['forums'][substr($target, 3)]['name'].'</a>' :
		($target == 'register' ? '<a href="'.$regname.'" target="_blank">'.lang('adv_register').'</a>' :
		($target == 'redirect' ? lang('adv_jump') :
		($target == 'archiver' ? '<a href="archiver/" target="_blank">Archiver</a>' :
		($target ? '<a href="forumdisplay.php?fid='.$target.'" target="_blank">'.$_DCACHE['forums'][$target]['name'].'</a>' : '<a href="'.$indexname.'" target="_blank">'.lang('home').'</a>'))));
}

?>