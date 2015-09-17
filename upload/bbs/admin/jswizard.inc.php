<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: jswizard.inc.php 20835 2009-10-27 02:59:05Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

if(!empty($previewurl)) {
	include_once DISCUZ_ROOT.'./forumdata/cache/cache_request.php';
	require_once DISCUZ_ROOT.'./include/request.func.php';
	parse_str($jsurl.'&nocache=yes', $requestdata);
	if($requestdata['function'] == 'side') {
		$tags = '<div id="wrap" class="wrap with_side s_clear" style="width:90%;height:auto;min-height:50px !important"><div class="main"><div class="content"></div></div><div id="sidebar" class="side"><div>';
	} else {
		$tags = '<div id="wrap" class="wrap s_clear" style="width:90%;height:auto;min-height:50px !important"><div class="main"><div class="content">';
	}
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><meta http-equiv="Content-Type" content="text/html; charset='.$charset.'" /><head><script type="text/javascript" src="include/js/common.js"></script></head><link rel="stylesheet" type="text/css" href="forumdata/cache/style_'.STYLEID.'_common.css" />';
	echo "\n\n<body><div id=\"append_parent\"></div><div id=\"ajaxwaitid\"></div>$tags\n\n".parse_request($requestdata, '', 0)."\n\n</div></div></div></body>\n\n</html>";
	exit;
}

cpheader();

$jstypes = array(0 => 'threads', 1 => 'forums', 2 => 'memberrank', 3 => 'stats', 4 => 'images', 5 => 'module', -2 => 'side', -1 => 'custom');

$from = $action == 'jswizard' && $operation != 'infoside' ? 'jswizard' : $from;
$infosidemenu = array(array('menu' => 'nav_infoside', 'submenu' => array(
	array('jswizard_infoside_global', 'jswizard&operation=infoside&from='.$from),
	array('jswizard_infoside_2', 'jswizard&operation=infoside&sideid=2&from='.$from),
	array('jswizard_infoside_0', 'jswizard&operation=infoside&sideid=0&from='.$from),
)), '', $operation == 'infoside' ? 1 : 0);

$addmenu = array();
foreach($jstypes as $k => $v) {
	if($k != 5) {
		$addmenu[] = array('jswizard_'.$v, 'jswizard&type='.$v);
	}
}

if(!$operation) {

	shownav('tools', 'nav_javascript');

	$jswizard = array();
	$query = $db->query("SELECT * FROM {$tablepre}request WHERE variable LIKE '".($jssetting != '' ? $jssetting : ($jskey != '' ? $jskey : '%'))."'");
	while($settings = $db->fetch_array($query)) {
		if($settings['system']) {
			continue;
		}
		$jswizard[$settings['variable']] = unserialize($settings['value']);
		$jswizard[$settings['variable']]['type'] = $settings['type'];
	}

	$type = !empty($function) ? $function : (isset($type) ? $type : '');
	$edit = isset($edit) ? $edit : NULL;
	$jskeyempty = 0;
	$editext = ($jssetting || $edit) ? '<input type="hidden" name="edit" value="'.dhtmlspecialchars($jssetting ? $jssetting : $edit).'">' : '';
	ksort($jswizard);

	if(!empty($type)) {
		list($keypre, $keyp) = explode('_', $jssetting);
		showsubmenu('nav_javascript', array(
			array('config', 'jswizard&operation=config', 0),
			$infosidemenu,
			array('admin', 'jswizard'.($keyp ? '&openkeypre='.$keypre : ''), 0),
			array(array('menu' => 'jswizard_addmodule', 'submenu' => $addmenu), '', $type != 'module' ? 1 : 0),
			array('jswizard_module', 'jswizard&type=module', $type != 'module' ? 0 : 1),
			array('import', 'jswizard&operation=import', 0),
		));
		if(empty($jskey)) {
			$jskey = $lang['jswizard_'.$type].'_'.random(3);
			$jskeyempty = 1;
		}
		$jspreview = '';
		$comment = !empty($comment) ? $comment : $jswizard[$jssetting]['comment'];
		if(!empty($function) && !empty($jssetting) && isset($jswizard[$jssetting]['url'])) {
			$parameter = $jswizard[$jssetting]['parameter'];
			$jskey = $jssetting;
			$jssetting = $jswizard[$jssetting]['url'];
			$preview = $jssubmit = TRUE;
			$jskeyempty = 0;
		} else {
			$jssetting = '';
		}
	}
	$jskey = stripslashes(trim($jskey));

	if(empty($type)) {

		list($keypre, $keyp) = explode('_', $jssetting);
		showsubmenu('nav_javascript', array(
			array('config', 'jswizard&operation=config', 0),
			$infosidemenu,
			array('admin', 'jswizard', 1),
			array(array('menu' => 'jswizard_addmodule', 'submenu' => $addmenu), '', 0),
			array('jswizard_module', 'jswizard&type=module', 0),
			array('import', 'jswizard&operation=import', 0)
		));

		if(!submitcheck('jsdelsubmit') && !submitcheck('jsexportsubmit')) {

			showformheader('jswizard');
			showtableheader();
			showsubtitle(array('', 'jswizard_key', 'jswizard_desc', 'jswizard_cache', 'type', ''));
			$lastpre = '';
			$openkeypre = isset($openkeypre) ? $openkeypre : '';
			echo '<tbody>';
			foreach($jswizard as $key => $jssetting) {
				list($keypre, $keyp) = explode('_', $key);
				if($keyp) {
					if($keypre != $lastpre) {
						echo '</tbody>';
						showtablerow('', array('class="td25"', '', 'width="40%"', 'width="10%"', 'width="20%"'), array(
							'',
							'<a href="javascript:;" onclick="display(\'key_'.$keypre.'\')"><span class="bold">['.$keypre.']</span></a>',
							'',
							'',
							'',
							''
						));
						echo '<tbody id="key_'.$keypre.'"'.($keypre != $openkeypre ? 'style="display: none"' : '').'>';
						$inpre = '&nbsp;&nbsp;&nbsp;&nbsp;';
					}
				} else {
					echo '<tbody>';
					$inpre = '';
				}
				$lastpre = $keypre;
				showtablerow('', array('class="td25"', '', 'width="40%"', 'width="10%"', 'width="20%"'), array(
					!@in_array($key, $infosidestatus) ? '<input class="checkbox" type="checkbox" name="keyarray[]" value="'.dhtmlspecialchars($key).'">' : '<input class="checkbox" type="checkbox" disabled="disabled">',
					$inpre.'<a href="'.$BASESCRIPT.'?action=jswizard&function='.$jstypes[$jssetting['type']].'&jssetting='.rawurlencode($key).'"><span class="bold">'.($inpre ? substr($key, strlen($keypre) + 1) : $key).'</span></a>',
					$jssetting['comment'].' ',
					($jssetting['parameter']['cachelife'] !== '' ? $jssetting['parameter']['cachelife'] : '<font class="lightfont">'.$jscachelife.'</font>'),
					(in_array($jstypes[$jssetting['type']], array('custom', 'side')) ? '<b>'.$lang['jswizard_'.$jstypes[$jssetting['type']]].'</b>' : $lang['jswizard_'.$jstypes[$jssetting['type']]]).
					($jssetting['parameter']['sidestatus'] ? ' &nbsp;('.$lang['jswizard_for_side'].')' : ''),
					'<a href="'.$BASESCRIPT.'?action=jswizard&function='.$jstypes[$jssetting['type']].'&jssetting='.rawurlencode($key).'">'.$lang['edit'].'</a> '.
					'<a href="'.$BASESCRIPT.'?action=jswizard&operation=copy&jssetting='.rawurlencode($key).'">'.$lang['copy'].'</a> '
				));
			}
			echo '</tbody>';

			showtablerow('', array('', 'colspan="5"'), array(
				'<input type="checkbox" name="chkall" class="checkbox" onclick="checkAll(\'prefix\', this.form, \'keyarray\')">'.lang('select_all'),
				'<input type="submit" class="btn" name="jsdelsubmit" value="'.$lang['delete'].'">&nbsp; &nbsp;<input type="submit" class="btn" name="jsexportsubmit" value="'.$lang['export'].'">'
			));
			showtablefooter();
			showformfooter();

		} elseif(submitcheck('jsdelsubmit')) {
			if(is_array($keyarray)) {
				$keys = implode("','", $keyarray);
				$db->query("DELETE FROM {$tablepre}request WHERE variable IN ('$keys')");
				updatecache('request');
				cpmsg('jswizard_succeed', $BASESCRIPT.'?action=jswizard', 'succeed');
			} else {
				header("location: $boardurl$BASESCRIPT?action=jswizard");
				dexit();
			}
		} elseif(submitcheck('jsexportsubmit')) {
			if(is_array($keyarray)) {
				$keys = implode("','", $keyarray);
				$query = $db->query("SELECT * FROM {$tablepre}request WHERE variable IN ('$keys')");
				$exportdataarray = array();
				while($exportdata = $db->fetch_array($query)) {
					$value = unserialize($exportdata['value']);
					$value['type'] = $exportdata['type'];
					switch($value['type']) {
						case 0:
							unset($value['parameter']['threads_forums']);
							unset($value['parameter']['tids']);
							unset($value['parameter']['typeids']);
						break;
						case 1:
							unset($value['parameter']['forums_forums']);
						break;
						case 4:
							unset($value['parameter']['images_forums']);
						break;
					}
					$exportdataarray[$exportdata['variable']] = serialize($value);
				}
				
				exportdata('Discuz! Request', date('Ymd'), $exportdataarray);
			} else {
				header("location: {$boardurl}$BASESCRIPT?action=jswizard");
				dexit();
			}
		}

	} elseif($type == 'threads') {

		$tcheckorderby = array((isset($parameter['orderby']) ? $parameter['orderby'] : 'lastpost') => 'checked');
		for($i = 0; $i <= 6; $i++) {
			$tcheckspecial[$i] = !empty($parameter['special'][$i]) ? 'checked' : '';
			$tcheckdigest[$i] = !empty($parameter['digest'][$i]) ? 'checked' : '';
			$tcheckstick[$i] = !empty($parameter['stick'][$i]) ? 'checked' : '';
		}
		$parameter['newwindow'] = isset($parameter['newwindow']) ? intval($parameter['newwindow']) : 1;
		$tradionewwindow[$parameter['newwindow']] = 'checked';

		$jsthreadtypeselect = '<select multiple="multiple" name="parameter[typeids][]" size="10"><option value="all">'.$lang['jswizard_all_typeids'].'</optoin><option value="">&nbsp;</option>';
		$jsthreadsortselect = '<select multiple="multiple" name="parameter[sortids][]" size="10"><option value="all">'.$lang['jswizard_all_sortids'].'</optoin><option value="">&nbsp;</option>';
		$query = $db->query("SELECT typeid, name, special FROM {$tablepre}threadtypes ORDER BY typeid DESC");
		while($threadtype = $db->fetch_array($query)) {
			if($threadtype['special']) {
				$jsthreadsortselect .= '<option value="'.$threadtype['typeid'].'" '.(isset($parameter['sortids']) && in_array($threadtype['typeid'], $parameter['sortids']) ? 'selected' : '').'>'.$threadtype['name'].'</option>';
			} else {
				$jsthreadtypeselect .= '<option value="'.$threadtype['typeid'].'" '.(isset($parameter['typeids']) && in_array($threadtype['typeid'], $parameter['typeids']) ? 'selected' : '').'>'.$threadtype['name'].'</option>';
			}
		}
		$jsthreadtypeselect .= '</select>';
		$jsthreadsortselect .= '</select>';
		$trewardstatus = array(intval($parameter['rewardstatus']) => 'checked');

		if($jssubmit && $function == 'threads') {

			$jsurl = $jssetting ? $jssetting : "function=$function".
				($parameter['threads_forums'] && !in_array('all', $parameter['threads_forums'])? '&fids='.jsfids($parameter['threads_forums']) : '').
				"&sidestatus=$parameter[sidestatus]".
				"&maxlength=$parameter[maxlength]".
				"&fnamelength=$parameter[fnamelength]".
				"&messagelength=$parameter[messagelength]".
				"&startrow=$parameter[startrow]".
				"&picpre=".rawurlencode($parameter['picpre']).
				"&items=$parameter[items]".
				"&tag=".rawurlencode($parameter[tag]).
				'&tids='.str_replace(',', '_', $parameter['tids']).
				($parameter['keyword'] ? '&keyword='.rawurlencode($parameter['keyword']) : '').
				($parameter['typeids'] && !in_array('all', $parameter['typeids'])? '&typeids='.jsfids($parameter['typeids']) : '').
				($parameter['sortids'] && !in_array('all', $parameter['sortids'])? '&sortids='.jsfids($parameter['sortids']) : '').
				"&special=".bindec(intval($parameter['special'][1]).intval($parameter['special'][2]).intval($parameter['special'][3]).intval($parameter['special'][4]).intval($parameter['special'][5]).intval($parameter['special'][6]).intval($parameter['special'][0])).
				"&rewardstatus=$parameter[rewardstatus]".
				"&digest=".bindec(intval($parameter['digest'][1]).intval($parameter['digest'][2]).intval($parameter['digest'][3]).intval($parameter['digest'][4])).
				"&stick=".bindec(intval($parameter['stick'][1]).intval($parameter['stick'][2]).intval($parameter['stick'][3]).intval($parameter['stick'][4])).
				"&recommend=$parameter[recommend]".
				"&newwindow=$parameter[newwindow]".
				"&threadtype=$parameter[threadtype]".
				"&threadsort=$parameter[threadsort]".
				"&highlight=$parameter[highlight]".
				"&orderby=$parameter[orderby]".
				"&hours=".intval($parameter['hours']).
				($parameter['boardurl'] ? "&boardurl=".rawurlencode($parameter['boardurl']) : '').
				"&jscharset=$parameter[jscharset]".
				($parameter['cachelife'] != '' ? "&cachelife=$parameter[cachelife]" : '').
				(!empty($parameter['jstemplate']) ? '&jstemplate='.rawurlencode($parameter['jstemplate']) : '');

			if(!$preview) {
				jssavesetting(0);
			}
			$jspreview = $lang['jswizard_innerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">{eval request(\''.str_replace("'", "\'", $jskey).'\');}</textarea><br />'.
				$lang['jswizard_outerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">'.
				dhtmlspecialchars("<script type=\"text/javascript\" src=\"{$boardurl}api/javascript.php?key=".rawurlencode($jskey)."\"></script>").
				'</textarea><br />'.jspreviewcode($jsurl).'<br />';
		}

		echo '<div class="colorbox">';
		if($jspreview) {
			echo '<h4 style="margin-bottom:15px;">'.lang('preview').'</h4>'.$jspreview;
		}

		showformheader('jswizard&function=threads#'.$lang['jswizard_threads']);
		echo '<h4 style="margin-bottom:15px;">'.lang('jswizard_threads').' - '.lang('jswizard_jstemplate').'</h4><div class="extcredits">'.$lang['jswizard_threads_jstemplate_comment'].'</div><br />';
		jsinsertunit();
		echo '<textarea cols="100" rows="5" id="jstemplate" name="parameter[jstemplate]" style="width: 95%;" onkeyup="textareasize(this)">'.($parameter['jstemplate'] != '' ? stripslashes($parameter['jstemplate']) : '{prefix} {subject}<br />').'</textarea>';
		echo '<br /><input type="button" class="btn" onclick="this.form.jssubmit.click()" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"></div><br /><br />';

		showtableheader();
		showtitle('jswizard_threads');
		showsetting('jswizard_jskey', 'jskey', $jskey, 'text');
		showsetting('jswizard_comment', 'comment', $comment, 'text');
		showsetting('jswizard_cachelife', 'parameter[cachelife]', $parameter['cachelife'] != '' ? intval($parameter['cachelife']) : '', 'text');
		showsetting('jswizard_threads_fids', '', '', jsforumselect('threads'));
		showsetting('jswizard_sidestatus', 'parameter[sidestatus]', $parameter['sidestatus'], 'radio');
		showsetting('jswizard_threads_startrow', 'parameter[startrow]', intval($parameter['startrow']), 'text');
		showsetting('jswizard_threads_items', 'parameter[items]', isset($parameter['items']) ? $parameter['items'] : 10, 'text');
		showsetting('jswizard_threads_maxlength', 'parameter[maxlength]', isset($parameter['maxlength']) ? $parameter['maxlength'] : 50, 'text');
		showsetting('jswizard_threads_fnamelength', 'parameter[fnamelength]', $parameter['fnamelength'], 'radio');
		showsetting('jswizard_threads_messagelength', 'parameter[messagelength]', $parameter['messagelength'], 'text');
		showsetting('jswizard_threads_picpre', 'parameter[picpre]', $parameter['picpre'], 'text');
		showsetting('jswizard_threads_tids', 'parameter[tids]', ($parameter['tids'] ? str_replace('_', ',', $parameter['tids']) : ''), 'text');
		showsetting('jswizard_threads_keyword', 'parameter[keyword]', $parameter['keyword'], 'text');
		showsetting('jswizard_threads_tag', 'parameter[tag]', $parameter['tag'], 'text');
		showsetting('jswizard_threads_typeids', '', '', $jsthreadtypeselect);
		showsetting('jswizard_threads_threadtype', 'parameter[threadtype]', $parameter['threadtype'], 'radio');
		showsetting('jswizard_threads_sortids', '', '', $jsthreadsortselect);
		showsetting('jswizard_threads_threadsort', 'parameter[threadsort]', $parameter['threadsort'], 'radio');
		showsetting('jswizard_threads_highlight', 'parameter[highlight]', $parameter['highlight'], 'radio');
		showsetting('jswizard_threads_special', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tcheckspecial[1] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[special][1]" value="1" '.$tcheckspecial[1].'> '.$lang['thread_poll'].'</li>
			<li'.($tcheckspecial[2] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[special][2]" value="1" '.$tcheckspecial[2].'> '.$lang['thread_trade'].'</li>
			<li'.($tcheckspecial[3] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" onclick="$(\'special_reward_ext\').style.display = this.checked ? \'\' : \'none\'" name="parameter[special][3]" value="1" '.$tcheckspecial[3].'> '.$lang['thread_reward'].'</li>
			<li'.($tcheckspecial[4] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[special][4]" value="1" '.$tcheckspecial[4].'> '.$lang['thread_activity'].'</li>
			<li'.($tcheckspecial[5] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[special][5]" value="1" '.$tcheckspecial[5].'> '.$lang['thread_debate'].'</li>
			<li'.($tcheckspecial[0] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[special][0]" value="1" '.$tcheckspecial[0].'> '.$lang['jswizard_special_0'].'</li></ul>'
		);
		showtagheader('tbody', 'special_reward_ext', $tcheckspecial[3], 'sub');
		showsetting('jswizard_threads_special_reward', array('parameter[rewardstatus]', array(
			array(0, $lang['jswizard_threads_special_reward_0']),
			array(1, $lang['jswizard_threads_special_reward_1']),
			array(2, $lang['jswizard_threads_special_reward_2'])
		), 1), $parameter['rewardstatus'], 'mradio');
		showtagfooter('tbody');
		showsetting('jswizard_threads_digest', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tcheckdigest[1] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[digest][1]" value="1" '.$tcheckdigest[1].'> '.$lang['jswizard_digest_1'].'</li>
			<li'.($tcheckdigest[2] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[digest][2]" value="1" '.$tcheckdigest[2].'> '.$lang['jswizard_digest_2'].'</li>
			<li'.($tcheckdigest[3] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[digest][3]" value="1" '.$tcheckdigest[3].'> '.$lang['jswizard_digest_3'].'</li>
			<li'.($tcheckdigest[4] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[digest][4]" value="1" '.$tcheckdigest[4].'> '.$lang['jswizard_digest_0'].'</li></ul>'
		);
		showsetting('jswizard_threads_stick', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tcheckstick[1] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[stick][1]" value="1" '.$tcheckstick[1].'> '.$lang['jswizard_stick_1'].'</li>
			<li'.($tcheckstick[2] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[stick][2]" value="1" '.$tcheckstick[2].'> '.$lang['jswizard_stick_2'].'</li>
			<li'.($tcheckstick[3] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[stick][3]" value="1" '.$tcheckstick[3].'> '.$lang['jswizard_stick_3'].'</li>
			<li'.($tcheckstick[4] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[stick][4]" value="1" '.$tcheckstick[4].'> '.$lang['jswizard_stick_0'].'</li></ul>'
		);
		showsetting('jswizard_threads_recommend', 'parameter[recommend]', $parameter['recommend'], 'radio');
		showsetting('jswizard_threads_newwindow', 'parameter[newwindow]', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tradionewwindow[0] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="0" '.$tradionewwindow[0].'> '.$lang['jswizard_newwindow_self'].'</li>
			<li'.($tradionewwindow[1] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="1" '.$tradionewwindow[1].'> '.$lang['jswizard_newwindow_blank'].'</li>
			<li'.($tradionewwindow[2] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="2" '.$tradionewwindow[2].'> '.$lang['jswizard_newwindow_main'].'</li></ul>'
		);
		showsetting('jswizard_threads_orderby', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tcheckorderby['lastpost'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="lastpost" '.$tcheckorderby['lastpost'].'> '.$lang['jswizard_threads_orderby_lastpost'].'</li>
			<li'.($tcheckorderby['dateline'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="dateline" '.$tcheckorderby['dateline'].'> '.$lang['jswizard_threads_orderby_dateline'].'</li>
			<li'.($tcheckorderby['replies'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="replies" '.$tcheckorderby['replies'].'> '.$lang['jswizard_threads_orderby_replies'].'</li>
			<li'.($tcheckorderby['views'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="views" '.$tcheckorderby['views'].'> '.$lang['jswizard_threads_orderby_views'].'</li>
			<li'.($tcheckorderby['heats'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="heats" '.$tcheckorderby['heats'].'> '.$lang['jswizard_threads_orderby_heats'].'</li>
			<li'.($tcheckorderby['recommends'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="recommends" '.$tcheckorderby['recommends'].'> '.$lang['jswizard_threads_orderby_recommends'].'</li>
			<li'.($tcheckorderby['hourviews'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="hourviews" '.$tcheckorderby['hourviews'].'> <input type="text" class="txt" name="parameter[hours]" value="'.$parameter['hours'].'" style="width: 50px;" size="6"> '.$lang['jswizard_threads_orderby_hourviews'].'</li>
			<li'.($tcheckorderby['todayviews'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="todayviews" '.$tcheckorderby['todayviews'].'> '.$lang['jswizard_threads_orderby_todayviews'].'</li>
			<li'.($tcheckorderby['weekviews'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="weekviews" '.$tcheckorderby['weekviews'].'> '.$lang['jswizard_threads_orderby_weekviews'].'</li>
			<li'.($tcheckorderby['monthviews'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="monthviews" '.$tcheckorderby['monthviews'].'> '.$lang['jswizard_threads_orderby_monthviews'].'</li></ul>'
		);
		if(strtoupper($charset) != 'UTF-8') {
			showsetting('jswizard_charset', 'parameter[jscharset]', $parameter['jscharset'], 'radio');
		} else {
			showsetting('jswizard_charsetr', array('parameter[jscharset]', array(array(0, $lang['none']), array(1, 'GBK'), array(2, 'BIG5'))), intval($parameter['jscharset']), 'mradio');
		}
		showsetting('jswizard_boardurl', 'parameter[boardurl]', $parameter['boardurl'], 'text');
		echo '<tr><td colspan="2">'.$editext.'<br /><input type="submit" class="btn" name="jssubmit" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"><input name="preview" type="hidden" value="1"></td></tr>';
		showtablefooter();
		showformfooter();

	} elseif($type == 'forums') {

		$fcheckorderby = array((isset($parameter['orderby']) ? $parameter['orderby'] : 'displayorder') => 'checked');
		$parameter['newwindow'] = isset($parameter['newwindow']) ? intval($parameter['newwindow']) : 1;
		$tradionewwindow[$parameter['newwindow']] = 'checked';

		if($jssubmit && $function == 'forums') {

			$jsurl = $jssetting ? $jssetting : "function=$function".
				($parameter['forums_forums'] && !in_array('all', $parameter['forums_forums'])? '&fups='.jsfids($parameter['forums_forums']) : '').
				"&startrow=$parameter[startrow]".
				"&items=$parameter[items]".
				"&newwindow=$parameter[newwindow]".
				"&orderby=$parameter[orderby]".
				($parameter['boardurl'] ? "&boardurl=".rawurlencode($parameter['boardurl']) : '').
				"&jscharset=$parameter[jscharset]".
				($parameter['cachelife'] != '' ? "&cachelife=$parameter[cachelife]" : '').
				(!empty($parameter['jstemplate']) ? '&jstemplate='.rawurlencode($parameter['jstemplate']) : '');

			if(!$preview) {
				jssavesetting(1);
			}
			$jspreview = $lang['jswizard_innerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">{eval request(\''.str_replace("'", "\'", $jskey).'\');}</textarea><br />'.
				$lang['jswizard_outerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">'.
				dhtmlspecialchars("<script type=\"text/javascript\" src=\"{$boardurl}api/javascript.php?key=".rawurlencode($jskey)."\"></script>").
				'</textarea><br />'.jspreviewcode($jsurl).'<br />';

		}

		echo '<div class="colorbox">';
		if($jspreview) {
			echo '<h4 style="margin-bottom:15px;">'.lang('preview').'</h4>'.$jspreview;
		}

		showformheader('jswizard&function=forums#'.$lang['jswizard_forums']);
		echo '<h4 style="margin-bottom:15px;">'.lang('jswizard_forums').' - '.lang('jswizard_jstemplate').'</h4><div class="extcredits">'.$lang['jswizard_forums_jstemplate_comment'].'</div><br />';
		jsinsertunit();
		echo '<textarea cols="100" rows="5" id="jstemplate" name="parameter[jstemplate]" style="width: 95%;" onkeyup="textareasize(this)">'.($parameter['jstemplate'] != '' ? stripslashes($parameter['jstemplate']) : '{forumname}<br />').'</textarea>';
		echo '<br /><input type="button" class="btn" onclick="this.form.jssubmit.click()" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"></div><br /><br />';

		showtableheader();
		showtitle('jswizard_forums');
		showsetting('jswizard_jskey', 'jskey', $jskey, 'text');
		showsetting('jswizard_comment', 'comment', $comment, 'text');
		showsetting('jswizard_cachelife', 'parameter[cachelife]', $parameter['cachelife'] != '' ? intval($parameter['cachelife']) : '', 'text');
		showsetting('jswizard_forums_fups', '', '', jsforumselect('forums'));
		showsetting('jswizard_forums_startrow', 'parameter[startrow]', intval($parameter['startrow']), 'text');
		showsetting('jswizard_forums_items', 'parameter[items]', intval($parameter['items']), 'text');
		showsetting('jswizard_forums_newwindow', 'parameter[newwindow]', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tradionewwindow[0] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="0" '.$tradionewwindow[0].'> '.$lang['jswizard_newwindow_self'].'</li>
			<li'.($tradionewwindow[1] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="1" '.$tradionewwindow[1].'> '.$lang['jswizard_newwindow_blank'].'</li>
			<li'.($tradionewwindow[2] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="2" '.$tradionewwindow[2].'> '.$lang['jswizard_newwindow_main'].'</li></ul>'
		);
		showsetting('jswizard_forums_orderby', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($fcheckorderby['displayorder'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="displayorder" '.$fcheckorderby['displayorder'].'> '.$lang['jswizard_forums_orderby_displayorder'].'</li>
			<li'.($fcheckorderby['threads'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="threads" '.$fcheckorderby['threads'].'> '.$lang['jswizard_forums_orderby_threads'].'</li>
			<li'.($fcheckorderby['posts'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="posts" '.$fcheckorderby['posts'].'> '.$lang['jswizard_forums_orderby_posts'].'</li></ul>'
		);
		if(strtoupper($charset) != 'UTF-8') {
			showsetting('jswizard_charset', 'parameter[jscharset]', $parameter['jscharset'], 'radio');
		} else {
			showsetting('jswizard_charsetr', array('parameter[jscharset]', array(array(0, $lang['none']), array(1, 'GBK'), array(2, 'BIG5'))), intval($parameter['jscharset']), 'mradio');
		}
		showsetting('jswizard_boardurl', 'parameter[boardurl]', $parameter['boardurl'], 'text');
		echo '<tr><td colspan="2">'.$editext.'<br /><input type="submit" class="btn" name="jssubmit" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"><input name="preview" type="hidden" value="1"></td></tr>';
		showtablefooter();
		showformfooter();

	} elseif($type == 'memberrank') {

		$mcheckorderby = array((isset($parameter['orderby']) ? $parameter['orderby'] : 'credits') => 'checked');
		$parameter['newwindow'] = isset($parameter['newwindow']) ? intval($parameter['newwindow']) : 1;
		$tradionewwindow[$parameter['newwindow']] = 'checked';

		if($jssubmit && $function == 'memberrank') {
			$jsurl = $jssetting ? $jssetting : "function=$function".
				"&startrow=$parameter[startrow]".
				"&items=$parameter[items]".
				"&newwindow=$parameter[newwindow]".
				"&extcredit=$parameter[extcredit]".
				"&orderby=$parameter[orderby]".
				"&hours=".intval($parameter['hours']).
				($parameter['boardurl'] ? "&boardurl=".rawurlencode($parameter['boardurl']) : '').
				"&jscharset=$parameter[jscharset]".
				($parameter['cachelife'] != '' ? "&cachelife=$parameter[cachelife]" : '').
				(!empty($parameter['jstemplate']) ? '&jstemplate='.rawurlencode($parameter['jstemplate']) : '');

			if(!$preview) {
				jssavesetting(2);
			}
			$jspreview = $lang['jswizard_innerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">{eval request(\''.str_replace("'", "\'", $jskey).'\');}</textarea><br />'.
				$lang['jswizard_outerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">'.
				dhtmlspecialchars("<script type=\"text/javascript\" src=\"{$boardurl}api/javascript.php?key=".rawurlencode($jskey)."\"></script>").
				'</textarea><br />'.jspreviewcode($jsurl).'<br />';

		}

		echo '<div class="colorbox">';
		if($jspreview) {
			echo '<h4 style="margin-bottom:15px;">'.lang('preview').'</h4>'.$jspreview;
		}

		showformheader('jswizard&function=memberrank#'.$lang['jswizard_memberrank']);
		echo '<h4 style="margin-bottom:15px;">'.lang('jswizard_memberrank').' - '.lang('jswizard_jstemplate').'</h4><div class="extcredits">'.$lang['jswizard_memberrank_jstemplate_comment'].'</div><br />';
		jsinsertunit();
		echo '<textarea cols="100" rows="5" id="jstemplate" name="parameter[jstemplate]" style="width: 95%;" onkeyup="textareasize(this)">'.($parameter['jstemplate'] != '' ? stripslashes($parameter['jstemplate']) : '{regdate} {member} {value}<br />').'</textarea>';
		echo '<br /><input type="button" class="btn" onclick="this.form.jssubmit.click()" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"></div><br /><br />';

		$extcreditsselect = '<select name="parameter[extcredit]">';
		for($i = 1;$i <= 8;$i++) {
			$extcreditsselect .= '<option value="'.$i.'"'.($parameter['extcredit'] == $i ? ' selected' : '').'>extcredits'.$i.($extcredits[$i]['title'] != '' ? ' ('.$extcredits[$i]['title'].')' : '').'</option>';
		}
		$extcreditsselect .= '</select>';

		showtableheader();
		showtitle('jswizard_memberrank');
		showsetting('jswizard_jskey', 'jskey', $jskey, 'text');
		showsetting('jswizard_comment', 'comment', $comment, 'text');
		showsetting('jswizard_cachelife', 'parameter[cachelife]', $parameter['cachelife'] != '' ? intval($parameter['cachelife']) : '', 'text');
		showsetting('jswizard_memberrank_startrow', 'parameter[startrow]', intval($parameter['startrow']), 'text');
		showsetting('jswizard_memberrank_items', 'parameter[items]', isset($parameter['items']) ? $parameter['items'] : 10, 'text');
		showsetting('jswizard_memberrank_newwindow', 'parameter[newwindow]', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tradionewwindow[0] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="0" '.$tradionewwindow[0].'> '.$lang['jswizard_newwindow_self'].'</li>
			<li'.($tradionewwindow[1] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="1" '.$tradionewwindow[1].'> '.$lang['jswizard_newwindow_blank'].'</li>
			<li'.($tradionewwindow[2] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="2" '.$tradionewwindow[2].'> '.$lang['jswizard_newwindow_main'].'</li></ul>'
		);
		showsetting('jswizard_memberrank_orderby', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($mcheckorderby['credits'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="credits" '.$mcheckorderby['credits'].'> '.$lang['jswizard_memberrank_orderby_credits'].'</li>
			<li'.($mcheckorderby['extcredits'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="extcredits" '.$mcheckorderby['extcredits'].'> '.$lang['jswizard_memberrank_orderby_extcredits'].'<br />'.$extcreditsselect.'</li>
			<li'.($mcheckorderby['posts'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="posts" '.$mcheckorderby['posts'].'> '.$lang['jswizard_memberrank_orderby_posts'].'</li>
			<li'.($mcheckorderby['digestposts'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="digestposts" '.$mcheckorderby['digestposts'].'> '.$lang['jswizard_memberrank_orderby_digestposts'].'</li>
			<li'.($mcheckorderby['regdate'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="regdate" '.$mcheckorderby['regdate'].'> '.$lang['jswizard_memberrank_orderby_regdate'].'</li>
			<li'.($mcheckorderby['hourposts'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="hourposts" '.$mcheckorderby['hourposts'].'> <input name="parameter[hours]" value="'.$parameter['hours'].'" size="6"> '.$lang['jswizard_memberrank_orderby_hourposts'].'</li>
			<li'.($mcheckorderby['todayposts'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="todayposts" '.$mcheckorderby['todayposts'].'> '.$lang['jswizard_memberrank_orderby_todayposts'].'</li>
			<li'.($mcheckorderby['weekposts'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="weekposts" '.$mcheckorderby['weekposts'].'> '.$lang['jswizard_memberrank_orderby_weekposts'].'</li>
			<li'.($mcheckorderby['monthposts'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="monthposts" '.$mcheckorderby['monthposts'].'> '.$lang['jswizard_memberrank_orderby_monthposts'].'</li></ul>'
		);
		if(strtoupper($charset) != 'UTF-8') {
			showsetting('jswizard_charset', 'parameter[jscharset]', $parameter['jscharset'], 'radio');
		} else {
			showsetting('jswizard_charsetr', array('parameter[jscharset]', array(array(0, $lang['none']), array(1, 'GBK'), array(2, 'BIG5'))), intval($parameter['jscharset']), 'mradio');
		}
		showsetting('jswizard_boardurl', 'parameter[boardurl]', $parameter['boardurl'], 'text');
		echo '<tr><td colspan="2">'.$editext.'<br /><input type="submit" class="btn" name="jssubmit" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"><input name="preview" type="hidden" value="1"></td></tr>';
		showtablefooter();
		showformfooter();

	} elseif($type == 'stats') {

		$predefined = array('forums', 'threads', 'posts', 'members', 'online', 'onlinemembers');

		if($jssubmit && $function == 'stats') {
			if($jssetting) {
				$jsurl = $jssetting;
			} else {
				$jsurl = "function=$function".
					($parameter['boardurl'] ? "&boardurl=".rawurlencode($parameter['boardurl']) : '').
					"&jscharset=$parameter[jscharset]";
				asort($displayorder);
				foreach($displayorder as $key => $order) {
					if($parameter[$key]['display']) {
						$jsurl .= "&info[$key]=".rawurlencode($parameter[$key]['title']);
					}
				}
			}
			$jsurl .= ($parameter['cachelife'] != '' ? "&cachelife=$parameter[cachelife]" : '').
				(!empty($parameter['jstemplate']) ? '&jstemplate='.rawurlencode($parameter['jstemplate']) : '');

			if(!$preview) {
				jssavesetting(3);
			}
			$jspreview = $lang['jswizard_innerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">{eval request(\''.str_replace("'", "\'", $jskey).'\');}</textarea><br />'.
				$lang['jswizard_outerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">'.
				dhtmlspecialchars("<script type=\"text/javascript\" src=\"{$boardurl}api/javascript.php?key=".rawurlencode($jskey)."\"></script>").
				'</textarea><br />'.jspreviewcode($jsurl).'<br />';

		}

		echo '<div class="colorbox">';
		if($jspreview) {
			echo '<h4 style="margin-bottom:15px;">'.lang('preview').'</h4>'.$jspreview;
		}

		showformheader('jswizard&function=stats#'.$lang['jswizard_stats']);
		echo '<a name="'.$lang['jswizard_stats'].'"></a>';
		echo '<h4 style="margin-bottom:15px;">'.lang('jswizard_stats').' - '.lang('jswizard_jstemplate').'</h4><div class="extcredits">'.$lang['jswizard_stats_jstemplate_comment'].'</div><br />';
		jsinsertunit();
		echo '<textarea cols="100" rows="5" id="jstemplate" name="parameter[jstemplate]" style="width: 95%;" onkeyup="textareasize(this)">'.($parameter['jstemplate'] != '' ? stripslashes($parameter['jstemplate']) : '{name} {value}<br />').'</textarea>';
		echo '<br /><input type="button" class="btn" onclick="this.form.jssubmit.click()" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"></div><br /><br />';

		showtableheader('jswizard_stats', 'nobottom');
		showsetting('jswizard_jskey', 'jskey', $jskey, 'text');
		showsetting('jswizard_comment', 'comment', $comment, 'text');
		showsetting('jswizard_cachelife', 'parameter[cachelife]', $parameter['cachelife'] != '' ? intval($parameter['cachelife']) : '', 'text');
		if(strtoupper($charset) != 'UTF-8') {
			showsetting('jswizard_charset', 'parameter[jscharset]', $parameter['jscharset'], 'radio');
		} else {
			showsetting('jswizard_charsetr', array('parameter[jscharset]', array(array(0, $lang['none']), array(1, 'GBK'), array(2, 'BIG5'))), intval($parameter['jscharset']), 'mradio');
		}
		showsetting('jswizard_boardurl', 'parameter[boardurl]', $parameter['boardurl'], 'text');
		showtablefooter();
		showtableheader('', 'noborder fixpadding');
		showsubtitle(array('jswizard_stats_display', 'display_order', 'jswizard_stats_display_title', 'jswizard_stats_display_name'));
		$order = 0;
		foreach($predefined as $key) {
			showtablerow('', array('class="td25"', 'class="td25"'), array(
				'<input class="checkbox" type="checkbox" name="parameter['.$key.'][display]" value="1" '.(!isset($parameter[$key]) || $parameter[$key]['display'] ? 'checked' : '').'>',
				'<input type="text" class="txt" name="displayorder['.$key.']" size="3" value="'.(isset($displayorder[$key]) ? intval($displayorder[$key]) : ++$order).'">',
				$lang['jswizard_stats_'.$key],
				'<input type="text" class="txt" name="parameter['.$key.'][title]" size="15" value="'.($parameter[$key]['title'] ? $parameter[$key]['title'] : $lang['jswizard_stats_'.$key].':').'">'
			));
		}
		showtablefooter();
		showtableheader('', 'notop');
		echo '<tr><td colspan="2">'.$editext.'<br /><input type="submit" class="btn" name="jssubmit" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"><input name="preview" type="hidden" value="1" /></td></tr>';
		showtablefooter();
		showformfooter();

	} elseif($type == 'images') {

		$tcheckorderby = array((isset($parameter['orderby']) ? $parameter['orderby'] : 'dateline') => 'checked');
		for($i = 1; $i <= 4; $i++) {
			$icheckdigest[$i] = !empty($parameter['digest'][$i]) ? 'checked' : '';
		}
		$parameter['newwindow'] = isset($parameter['newwindow']) ? intval($parameter['newwindow']) : 1;
		$parameter['isimage'] = isset($parameter['isimage']) ? $parameter['isimage'] : 1;
		$parameter['threadmethod'] = isset($parameter['threadmethod']) ? $parameter['threadmethod'] : 1;
		$tradionewwindow[$parameter['newwindow']] = 'checked';

		if($jssubmit && $function == 'images') {
			$jsurl = $jssetting ? $jssetting : "function=$function".
				($parameter['images_forums'] && !in_array('all', $parameter['images_forums'])? '&fids='.jsfids($parameter['images_forums']) : '').
				"&sidestatus=$parameter[sidestatus]".
				"&isimage=$parameter[isimage]".
				"&threadmethod=$parameter[threadmethod]".
				"&maxwidth=$parameter[maxwidth]".
				"&maxheight=$parameter[maxheight]".
				"&startrow=$parameter[startrow]".
				"&items=$parameter[items]".
				"&orderby=$parameter[orderby]".
				"&hours=".intval($parameter['hours']).
				"&digest=".bindec(intval($parameter['digest'][1]).intval($parameter['digest'][2]).intval($parameter['digest'][3]).intval($parameter['digest'][4])).
				"&newwindow=$parameter[newwindow]".
				($parameter['boardurl'] ? "&boardurl=".rawurlencode($parameter['boardurl']) : '').
				"&jscharset=$parameter[jscharset]".
				($parameter['cachelife'] != '' ? "&cachelife=$parameter[cachelife]" : '').
				(!empty($parameter['jstemplate']) ? '&jstemplate='.rawurlencode($parameter['jstemplate']) : '');

			if(!$preview) {
				jssavesetting(4);
			}
			$jspreview = $lang['jswizard_innerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">{eval request(\''.str_replace("'", "\'", $jskey).'\');}</textarea><br />'.
				$lang['jswizard_outerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">'.
				dhtmlspecialchars("<script type=\"text/javascript\" src=\"{$boardurl}api/javascript.php?key=".rawurlencode($jskey)."\"></script>").
				'</textarea><br />'.jspreviewcode($jsurl).'<br />';
		}

		echo '<div class="colorbox">';
		if($jspreview) {
			echo '<h4 style="margin-bottom:15px;">'.lang('preview').'</h4>'.$jspreview;
		}

		showformheader('jswizard&function=images#'.$lang['jswizard_images']);
		echo '<h4 style="margin-bottom:15px;">'.lang('jswizard_images').' - '.lang('jswizard_jstemplate').'</h4><div class="extcredits">'.$lang['jswizard_images_jstemplate_comment'].'</div><br />';
		jsinsertunit();
		echo '<textarea cols="100" rows="5" id="jstemplate" name="parameter[jstemplate]" style="width: 95%;" onkeyup="textareasize(this)">'.($parameter['jstemplate'] != '' ? stripslashes($parameter['jstemplate']) : '{file} ({filesize} Bytes)<br />').'</textarea>';
		echo '<br /><input type="button" class="btn" onclick="this.form.jssubmit.click()" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"></div><br /><br />';

		showtableheader();
		showtitle('jswizard_images');
		showsetting('jswizard_jskey', 'jskey', $jskey, 'text');
		showsetting('jswizard_comment', 'comment', $comment, 'text');
		showsetting('jswizard_cachelife', 'parameter[cachelife]', $parameter['cachelife'] != '' ? intval($parameter['cachelife']) : '', 'text');
		showsetting('jswizard_images_fids', '', '', jsforumselect('images'));
		showsetting('jswizard_sidestatus', 'parameter[sidestatus]', $parameter['sidestatus'], 'radio');
		showsetting('jswizard_images_startrow', 'parameter[startrow]', intval($parameter['startrow']), 'text');
		showsetting('jswizard_images_items', 'parameter[items]', isset($parameter['items']) ? $parameter['items'] : 5, 'text');
		showsetting('jswizard_images_isimage', array('parameter[isimage]', array(
			array(0, $lang['jswizard_images_isimage_0'], array('imgsetting' => 'none')),
			array(1, $lang['jswizard_images_isimage_1'], array('imgsetting' => '')),
			array(2, $lang['jswizard_images_isimage_2'], array('imgsetting' => 'none'))
		), 1), $parameter['isimage'], 'mradio');
		showtagheader('tbody', 'imgsetting', $parameter['isimage'] == 1, 'sub');
		showsetting('jswizard_images_maxwidth', 'parameter[maxwidth]', isset($parameter['maxwidth']) ? $parameter['maxwidth'] : 200, 'text');
		showsetting('jswizard_images_maxheight', 'parameter[maxheight]', isset($parameter['maxheight']) ? $parameter['maxheight'] : 200, 'text');
		showtagfooter('tbody');
		showsetting('jswizard_images_thread', 'parameter[threadmethod]', $parameter['threadmethod'], 'radio');
		showsetting('jswizard_images_digest', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($icheckdigest[1] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[digest][1]" value="1" '.$icheckdigest[1].'> '.$lang['jswizard_digest_1'].'</li>
			<li'.($icheckdigest[2] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[digest][2]" value="1" '.$icheckdigest[2].'> '.$lang['jswizard_digest_2'].'</li>
			<li'.($icheckdigest[3] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[digest][3]" value="1" '.$icheckdigest[3].'> '.$lang['jswizard_digest_3'].'</li>
			<li'.($icheckdigest[4] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="parameter[digest][4]" value="1" '.$icheckdigest[4].'> '.$lang['jswizard_digest_0'].'</li></ul>'
		);
		showtagfooter('tbody');
		showsetting('jswizard_images_newwindow', 'parameter[newwindow]', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tradionewwindow[0] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="0" '.$tradionewwindow[0].'> '.$lang['jswizard_newwindow_self'].'</li>
			<li'.($tradionewwindow[1] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="1" '.$tradionewwindow[1].'> '.$lang['jswizard_newwindow_blank'].'</li>
			<li'.($tradionewwindow[2] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[newwindow]" value="2" '.$tradionewwindow[2].'> '.$lang['jswizard_newwindow_main'].'</li></ul>'
		);
		showsetting('jswizard_images_orderby', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tcheckorderby['dateline'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="dateline" '.$tcheckorderby['dateline'].'> '.$lang['jswizard_images_orderby_dateline'].'</li>
			<li'.($tcheckorderby['downloads'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="downloads" '.$tcheckorderby['downloads'].'> '.$lang['jswizard_images_orderby_downloads'].'</li>
			<li'.($tcheckorderby['hourdownloads'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="hourdownloads" '.$tcheckorderby['hourdownloads'].'> <input type="text" class="txt" style="width: 50px;" name="parameter[hours]" value="'.$parameter['hours'].'" size="6"> '.$lang['jswizard_images_orderby_hourdownloads'].'</li>
			<li'.($tcheckorderby['todaydownloads'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="todaydownloads" '.$tcheckorderby['todaydownloads'].'> '.$lang['jswizard_images_orderby_todaydownloads'].'</li>
			<li'.($tcheckorderby['weekdownloads'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="weekdownloads" '.$tcheckorderby['weekdownloads'].'> '.$lang['jswizard_images_orderby_weekdownloads'].'</li>
			<li'.($tcheckorderby['monthdownloads'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="parameter[orderby]" value="monthdownloads" '.$tcheckorderby['monthdownloads'].'> '.$lang['jswizard_images_orderby_monthdownloads'].'</li></ul>'
		);
		if(strtoupper($charset) != 'UTF-8') {
			showsetting('jswizard_charset', 'parameter[jscharset]', $parameter['jscharset'], 'radio');
		} else {
			showsetting('jswizard_charsetr', array('parameter[jscharset]', array(array(0, $lang['none']), array(1, 'GBK'), array(2, 'BIG5'))), intval($parameter['jscharset']), 'mradio');
		}
		showsetting('jswizard_boardurl', 'parameter[boardurl]', $parameter['boardurl'], 'text');
		echo '<tr><td colspan="2">'.$editext.'<br /><input type="submit" class="btn" name="jssubmit" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"><input name="preview" type="hidden" value="1"></td></tr>';
		showtablefooter();
		showformfooter();

	} elseif($type == 'module') {

		$parameter['module'] = !empty($module) ? $module : $parameter['module'];
		$parameter['module'] = str_replace(array('..', '/', '\\'), array('', '', ''), $parameter['module']);
		$parameter['module'] = file_exists(DISCUZ_ROOT.'./include/request/'.$parameter['module']) ? $parameter['module'] : '';

		include language('request');
		if($parameter['module']) {
			$requestrun = FALSE;
			include_once DISCUZ_ROOT.'./include/request/'.$parameter['module'];
		}
		if($jssubmit && $function == 'module' && $parameter['module']) {
			$settingsenc = rawurlencode(serialize($parameter['settings']));
			$jsurl = $jssetting ? $jssetting : "function=$function".
				"&module=$parameter[module]".
				"&settings=$settingsenc".
				($parameter['boardurl'] ? "&boardurl=".rawurlencode($parameter['boardurl']) : '').
				"&jscharset=$parameter[jscharset]".
				($parameter['cachelife'] != '' ? "&cachelife=$parameter[cachelife]" : '');

			if(!$preview) {
				jssavesetting(5);
			}
			$jspreview = $lang['jswizard_innerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">{eval request(\''.str_replace("'", "\'", $jskey).'\');}</textarea><br />'.
				$lang['jswizard_outerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">'.
				dhtmlspecialchars("<script type=\"text/javascript\" src=\"{$boardurl}api/javascript.php?key=".rawurlencode($jskey)."\"></script>").
				'</textarea><br /><br />'.jspreviewcode($jsurl).'<br />';
		}

		if(!empty($request_name)) {
			echo '<div class="colorbox"><h4>'.$request_name.' '.$request_version.'</h4>'.$request_description.'<div style="width:95%" align="right">'.$request_copyright.'</div></div><br /><br />';
		}

		if($jspreview) {
			echo '<div class="colorbox">';
			echo '<h4 style="margin-bottom:15px;">'.lang('preview').'</h4>'.$jspreview;
			echo '<br /><input type="button" class="btn" onclick="$(\'cpform\').jssubmit.click()" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="$(\'cpform\').preview.value=0;$(\'cpform\').jssubmit.click()" value="'.$lang['submit'].'"></div><br /><br />';
		}

		showformheader('jswizard&function=module#'.$lang['jswizard_module']);
		jsinsertunit();

		showtableheader();
		if($parameter['module']) {
			if($jskeyempty) {
				$jskey = str_replace(' ', '_', $request_name).'_'.random(3);
			}
			showtitle($lang['jswizard_module']);
			echo '<input type="hidden" name="parameter[module]" value="'.$parameter['module'].'">';
			showsetting('jswizard_jskey', 'jskey', $jskey, 'text');
			showsetting('jswizard_comment', 'comment', $comment, 'text');
			showsetting('jswizard_cachelife', 'parameter[cachelife]', $parameter['cachelife'] != '' ? intval($parameter['cachelife']) : '', 'text');
			if(is_array($request_settings)) {
				foreach($request_settings as $settingvar => $setting) {
					$varname = in_array($setting[2], array('mradio', 'mcheckbox', 'select', 'mselect')) ? array('parameter[settings]['.$settingvar.']', $setting[3]) : 'parameter[settings]['.$settingvar.']';
					$value = $parameter['settings'][$settingvar] != '' ? stripslashes($parameter['settings'][$settingvar]) : $setting[4];
					showsetting($setting[0].':', $varname, $value, $setting[2], '', 0, $setting[1]);
				}
			}
			if(strtoupper($charset) != 'UTF-8') {
				showsetting('jswizard_charset', 'parameter[jscharset]', $parameter['jscharset'], 'radio');
			} else {
				showsetting('jswizard_charsetr', array('parameter[jscharset]', array(array(0, $lang['none']), array(1, 'GBK'), array(2, 'BIG5'))), intval($parameter['jscharset']), 'mradio');
			}
			showsetting('jswizard_boardurl', 'parameter[boardurl]', $parameter['boardurl'], 'text');
			echo '<tr><td colspan="2">'.$editext.'<br /><input type="submit" class="btn" name="jssubmit" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"><input name="preview" type="hidden" value="1"></td></tr>';
		} else {
			$requests = jsgetrequests();
			showtips('jswizard_module_tips');
			showtableheader('', 'fixpadding');

			if($requests) {
				showsubtitle(array('name', 'jswizard_module', 'jswizard_module_version', 'copyright', ''));
				foreach($requests as $request) {
					showtablerow('', '', array(
						$request[1].($request['filemtime'] > $timestamp - 86400 ? ' <font color="red">New!</font>' : ''),
						$request[0],
						$request[2],
						$request[3],
						"<a href=\"$BASESCRIPT?action=jswizard&type=module&module=$request[0]\">$lang[add]</a>"
					));
				}
			} else {
				showtablerow('', '', $lang['jswizard_request_nonexistence']);
			}
			echo '<input type="hidden" id="parametermodule" name="parameter[module]"><input name="preview" type="hidden" value="1">';

		}
		showtablefooter();
		showformfooter();

	} elseif(in_array($type, array('custom', 'side'))) {

		if($type == 'side') {
			$jskey = substr($jskey, 0, strlen($lang['jswizard_infoside_pre'])) == $lang['jswizard_infoside_pre'] ? $jskey : $lang['jswizard_infoside_pre'].$jskey;
		}
		if($jssubmit && $function == $type) {
			if($function == 'side' && !empty($parameter['selectmodule'])) {
				$parameter['jstemplate'] = '';
				$splitbar = '';
				foreach($parameter['selectmodule'] as $value) {
					$parameter['jstemplate'] .= $splitbar.'[module]'.$value.'[/module]';
					$splitbar = '<hr class="shadowline"/>';
				}
			}
			$jsurl = $jssetting ? $jssetting : "function=$function".
				($parameter['boardurl'] ? "&boardurl=".rawurlencode($parameter['boardurl']) : '').
				"&jscharset=$parameter[jscharset]".
				($parameter['cachelife'] != '' ? "&cachelife=$parameter[cachelife]" : '').
				(!empty($parameter['jstemplate']) ? '&jstemplate='.rawurlencode($parameter['jstemplate']) : '');

			if(!$preview) {
				jssavesetting($type == 'custom' ? -1 : -2);
			}
			$jspreview = ($type == 'custom' ? $lang['jswizard_innerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">{eval request(\''.str_replace("'", "\'", $jskey).'\');}</textarea><br />'.
				$lang['jswizard_outerrequest'].'<textarea rows="2" style="width: 95%; word-break: break-all" onFocus="this.select()">'.
				dhtmlspecialchars("<script type=\"text/javascript\" src=\"{$boardurl}api/javascript.php?key=".rawurlencode($jskey)."\"></script>").
				'</textarea><br />' : '').jspreviewcode($jsurl).'<br />';
		}

		echo '<div class="colorbox">';
		if($jspreview && in_array($type, array('custom', 'side'))) {
			echo '<h4 style="margin-bottom:15px;">'.lang('preview').'</h4>'.$jspreview;
		}

		$jsmodule = '';$selectarray = $requests = array();
		$requests = array();
		$query = $db->query("SELECT * FROM {$tablepre}request WHERE type>=0");
		while($settings = $db->fetch_array($query)) {
			$value = unserialize($settings['value']);
			$optionitem = '<option value="'.$settings['variable'].'">'.($value['parameter']['sidestatus'] ? '* ' : '').($value['comment'] != '' ? $value['comment'] : '').'['.$settings['variable'].']'.'</option>';
			if($type == 'side' && !empty($parameter['selectmodule'])) {
				$key = array_search($settings['variable'], $parameter['selectmodule']);
				if($key !== FALSE && $key !== NULL) {
					$selectarray[$key] = $optionitem;
				} else {
					$jsmodule .= $optionitem;
				}
			} else {
				$jsmodule .= $optionitem;
			}
		}
		if($type == 'side' && !empty($selectarray)) {
			ksort($selectarray);
			$selectmodule = implode($selectarray);
		}

		showformheader('jswizard&function='.$type.'#'.$lang['jswizard_'.$type]);

		if($type == 'custom') {
			echo '<h4 style="margin-bottom:15px;">'.lang('jswizard_custom').' - '.lang('jswizard_jstemplate').'</h4><div class="extcredits">';
			echo $lang['jswizard_custom_jstemplate_comment'].'</div><select onchange="insertunit(\'[module]\'+this.value+\'[/module]\')"><option>'.$lang['jswizard_custom_jstemplate_current_module'].'</option>'.$jsmodule.'</select><br /><br />';
			jsinsertunit();
			echo '<textarea cols="100" rows="5" id="jstemplate" name="parameter[jstemplate]" style="width: 95%;" onkeyup="textareasize(this)">'.($parameter['jstemplate'] != '' ? stripslashes($parameter['jstemplate']) : '').'</textarea>';
		} else {
			echo '<h4 style="margin-bottom:15px;">'.lang('jswizard_side').'</h4><div class="extcredits">';
			echo '<tr><td colspan="2">'.$lang['jswizard_sidemodule_comment'].'</div>';
			echo '<script type="text/JavaScript">
			function moveselect(fromitem, toitem) {
				var selectindex = $(fromitem).selectedIndex;
				if(selectindex == -1) return;
				var itemtext = $(fromitem).options[selectindex].text;
				var itemvalue = $(fromitem).value;
				$(fromitem).removeChild($(fromitem).options[selectindex]);
				var newoption = new Option(itemtext, itemvalue);
				$(toitem).options.add(newoption);
			}
			function orderselect(option) {
				var selectindex = $(\'selectmodule\').selectedIndex;
				if(selectindex == -1) return;
				var itemtext = $(\'selectmodule\').options[selectindex].text;
				var itemvalue = $(\'selectmodule\').value;
				var itemcount = $(\'selectmodule\').options.length;
				if(option == 1 && selectindex == 0 || option == 0 && selectindex == itemcount - 1) return;
				if(option == 1) {
					if(selectindex == 0) return;var swapindex = selectindex - 1;
				} else {
					if(selectindex == itemcount - 1) return;var swapindex = selectindex + 1;
				}
				var tmptext = $(\'selectmodule\').options[swapindex].text;
				var tmpvalue = $(\'selectmodule\').options[swapindex].value;
				$(\'selectmodule\').options[swapindex].text = $(\'selectmodule\').options[selectindex].text;
				$(\'selectmodule\').options[swapindex].value = $(\'selectmodule\').options[selectindex].value;
				$(\'selectmodule\').options[selectindex].text = tmptext;
				$(\'selectmodule\').options[selectindex].value = tmpvalue;
				$(\'selectmodule\').selectedIndex = swapindex;
			}
			function selectall() {
				var itemcount = $(\'selectmodule\').options.length;
				for(i = 0;i < itemcount;i++) {
					$(\'selectmodule\').options[i].selected = true;
				}
			}
			</script>
			<table width="95%" border="0" cellpadding="0" cellspacing="0"><tr><td style="text-align:center;border: 0">
			<button class=button onclick="orderselect(1);return false">'.$lang['jswizard_custom_jstemplate_up'].'</button>
			<br /><br /><button class=button onclick="orderselect(0);return false">'.$lang['jswizard_custom_jstemplate_down'].'</button>
			</td><td width="40%" style="border: 0">
			'.$lang['jswizard_custom_jstemplate_selected_module'].'<select ondblclick="moveselect(\'selectmodule\', \'currentmodule\')" id="selectmodule" name="parameter[selectmodule][]" multiple="multiple" size="10" style="width: 100%">'.$selectmodule.'</select>
			</td><td style="text-align:center;border: 0">
			<button class=button onclick="moveselect(\'currentmodule\', \'selectmodule\');return false">'.$lang['jswizard_custom_jstemplate_select'].'</button>
			<br /><br /><button class=button onclick="moveselect(\'selectmodule\', \'currentmodule\');return false">'.$lang['jswizard_custom_jstemplate_remove'].'</button>
			</td><td width="40%" style="border: 0">
			'.$lang['jswizard_custom_jstemplate_current_module'].'<select ondblclick="moveselect(\'currentmodule\', \'selectmodule\')" id="currentmodule" multiple="multiple" size="10" style="width:100%">'.$jsmodule.'</select>
			</td></tr></table>
			';
		}

		echo '<br /><input type="button" class="btn" onclick="this.form.jssubmit.click()" value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"></div><br /><br />';

		showtableheader();
		showtitle('jswizard_'.$type);
		showsetting('jswizard_jskey', 'jskey', $jskey, 'text');
		showsetting('jswizard_comment', 'comment', $comment, 'text');
		showsetting('jswizard_cachelife_custom', 'parameter[cachelife]', $parameter['cachelife'] != '' ? intval($parameter['cachelife']) : '', 'text');
		if($type == 'custom') {
			if(strtoupper($charset) != 'UTF-8') {
				showsetting('jswizard_charset', 'parameter[jscharset]', $parameter['jscharset'], 'radio');
			} else {
				showsetting('jswizard_charsetr', array('parameter[jscharset]', array(array(0, $lang['none']), array(1, 'GBK'), array(2, 'BIG5'))), intval($parameter['jscharset']), 'mradio');
			}
		}
		showsetting('jswizard_boardurl', 'parameter[boardurl]', $parameter['boardurl'], 'text');
		echo '<tr><td colspan="2">'.$editext.'<br /><input type="submit" class="btn" name="jssubmit"'.($type == 'side' ? ' onclick="selectall()"' : '').' value="'.$lang['preview'].'">&nbsp; &nbsp;<input type="button" class="btn" onclick="this.form.preview.value=0;this.form.jssubmit.click()" value="'.$lang['submit'].'"><input name="preview" type="hidden" value="1"></td></tr>';
		showtablefooter();
		showformfooter();

	}

} elseif($operation == 'import') {

	if(!submitcheck('importsubmit')) {
		shownav('tools', 'nav_javascript');
		showsubmenu('nav_javascript', array(
			array('config', 'jswizard&operation=config', 0),
			$infosidemenu,
			array('admin', 'jswizard', 0),
			array(array('menu' => 'jswizard_addmodule', 'submenu' => $addmenu), '', 0),
			array('jswizard_module', 'jswizard&type=module', 0),
			array('import', 'jswizard&operation=import', 1)
		));

		showformheader('jswizard&operation=import', 'enctype');

		showtableheader('jswizard_import');
		showimportdata();
		showtablerow('', 'class="rowform"', mradio('importrewrite', array(
			0 => lang('jswizard_import_default'),
			1 => lang('jswizard_import_norewrite'),
			2 => lang('jswizard_import_rewrite'),
		), 0, FALSE));
		showsubmit('importsubmit');
		showtablefooter();
		showformfooter();
	} else {
		require_once DISCUZ_ROOT.'./admin/importdata.func.php';
		import_request($importrewrite);
		cpmsg('jswizard_succeed', $BASESCRIPT.'?action=jswizard', 'succeed');
	}

} elseif($operation == 'copy') {

	$requestdata = $db->fetch_first("SELECT * FROM {$tablepre}request WHERE variable='$jssetting'");
	$requestdata = daddslashes($requestdata, 1);
	$requestdata['variable'] = $requestdata['variable'].'_'.random(3);
	$db->query("INSERT INTO {$tablepre}request (variable, value, `type`) VALUES ('$requestdata[variable]', '$requestdata[value]', '$requestdata[type]')");
	cpmsg('jswizard_copy_succeed', $BASESCRIPT.'?action=jswizard', 'succeed');

} elseif($operation == 'infoside') {

	if(submitcheck('globalsubmit')) {
		$infosidestatus['allow'] = $infosidestatusnew['allow'];
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('infosidestatus', '".addslashes(serialize($infosidestatus))."')");
		updatecache('settings');
	} elseif(submitcheck('submit')) {
		if($infosidenew == -1) {
			$sidekey = $infosidenew = $newproject ? $lang['jswizard_infoside_pre'].$newproject : '';
		}
		if($infosidenew) {
			$modulearray = array();
			$displayorder = 0;
			if($availablenew) {
				foreach($availablenew as $key) {
					if(!$modulearray[$displayordernew[$key]]) {
						$modulearray[$displayordernew[$key]] = $key;
					} else {
						$modulearray[] = $key;
					}
				}
			}
			ksort($modulearray);
			$sidedata = unserialize($db->result_first("SELECT value FROM {$tablepre}request WHERE variable='$sidekey'"));
			$parameter = array(
				'selectmodule' => $modulearray,
				'cachelife' => intval($cachelifenew),
				'jstemplate' => $modulearray ? '[module]'.implode('[/module]<hr class="shadowline"/>[module]', $modulearray).'[/module]' : ''
			);
			$jsurl = 'function=side&jscharset='.($cachelifenew != '' ? "&cachelife=$cachelifenew" : '').(!empty($parameter['jstemplate']) ? '&jstemplate='.rawurlencode($parameter['jstemplate']) : '');
			$comment = $sidedata['comment'];
			$infosidestatus[$sideid] = $jskey = $sidekey;
			$edit = $sidedata ? $jskey : '';
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('infosidestatus', '".addslashes(serialize($infosidestatus))."')");
			updatecache('settings');
			jssavesetting(-2);
		} else {
			$infosidestatus[$sideid] = '';
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('infosidestatus', '".addslashes(serialize($infosidestatus))."')");
			updatecache('settings');
			cpmsg('jswizard_succeed', $BASESCRIPT.'?action=jswizard&operation=infoside&sideid='.$sideid.'&from='.$from, 'succeed');
		}

	}

	if($from == 'style') {
		shownav('style', 'settings_styles');
		showsubmenu('settings_styles', array(
			array('settings_styles_global', 'settings&operation=styles&anchor=global', 0),
			array('settings_styles_index', 'settings&operation=styles&anchor=index', 0),
			array('settings_styles_forumdisplay', 'settings&operation=styles&anchor=forumdisplay', 0),
			array('settings_styles_viewthread', 'settings&operation=styles&anchor=viewthread', 0),
			array('settings_styles_member', 'settings&operation=styles&anchor=member', 0),
			array('settings_styles_customnav', 'misc&operation=customnav', 0),
			$infosidemenu,
			array('settings_styles_refresh', 'settings&operation=styles&anchor=refresh', 0),
			array('settings_styles_sitemessage', 'settings&operation=styles&anchor=sitemessage', 0)
		));
	} else {
		shownav('tools', 'nav_javascript');
		showsubmenu('nav_javascript', array(
			array('config', 'jswizard&operation=config', 0),
			$infosidemenu,
			array('admin', 'jswizard'.($keyp ? '&openkeypre='.$keypre : ''), 0),
			array(array('menu' => 'jswizard_addmodule', 'submenu' => $addmenu), '', 0),
			array('jswizard_module', 'jswizard&type=module', 0),
			array('import', 'jswizard&operation=import', 0),
		));
	}



	if(!isset($sideid)) {

		showformheader('jswizard&operation=infoside&from='.$from);
		showtableheader('jswizard_infoside');
		showsetting('jswizard_infoside_allow', 'infosidestatusnew[allow]', $infosidestatus['allow'], 'radio');
		showsubmit('globalsubmit', 'submit');
		showtablefooter();
		showformfooter();

	} else {

		$query = $db->query("SELECT type, variable FROM {$tablepre}request");
		$modules = array();
		$sides[] = array('', lang('jswizard_infoside_off'));
		while($request = $db->fetch_array($query)) {
			if($request['type'] == -2) {
				$v = substr($request['variable'], 0, strlen($lang['jswizard_infoside_pre'])) == $lang['jswizard_infoside_pre'] ? substr($request['variable'], strlen($lang['jswizard_infoside_pre'])) : $request['variable'];
				$sides[] = array(rawurlencode($request['variable']), $v);
			} elseif($request['type'] >= 0) {
				$modules[$request['variable']] = $request;
			}
		}
		$sides[] = array('-1', lang('jswizard_infoside_newproject'));
		$sidekey = empty($sidekey) ? addslashes($infosidestatus[$sideid]) : $sidekey;
		if($sidekey) {
			$sidedata = unserialize($db->result_first("SELECT value FROM {$tablepre}request WHERE variable='$sidekey'"));
			$selectmodules = $sidedata['parameter']['selectmodule'];
			@ksort($selectmodules);
		}

		showformheader('jswizard&operation=infoside&sideid='.$sideid.'&sidekey='.rawurlencode($sidekey).'&from='.$from);

		showtableheader(lang('jswizard_infoside').' - '.lang('jswizard_infoside_'.$sideid), 'nobottom');
		showsetting('jswizard_infoside_project', array('infosidenew', $sides), rawurlencode($sidekey), 'select', '', 0, '', "onchange=\"if(this.value && this.value != -1) {window.location='$BASESCRIPT?action=jswizard&operation=infoside&sideid=$sideid&from=$from&sidekey='+this.value} else if(this.value == -1) {\$('newproject').style.display = '';} else {\$('newproject').style.display = 'none';}if(this.value) {\$('modulelist').style.display = '';} else {\$('modulelist').style.display = 'none';}\"");
		showtagheader('tbody', 'newproject', 0, 'sub');
		showsetting('jswizard_infoside_newprojectname', 'newproject', '', 'text');
		showtagfooter('tbody');
		showsetting('jswizard_infoside_cachelife', 'cachelifenew', $sidedata['parameter']['cachelife'] != '' ? intval($sidedata['parameter']['cachelife']) : '', 'text');
		showtablefooter();

		showtableheader('', 'fixpadding', 'id="modulelist"'.(empty($sidekey) ? ' style="display:none"': ''));
		showsubtitle(array('jswizard_key', 'available', 'display_order', 'type', ''));

		if($selectmodules) {
			foreach($selectmodules as $displayorder => $selectmodule) {
				$module = $modules[$selectmodule];
				$key = htmlspecialchars($module['variable']);
				showtablerow('', array('', '', 'class="td28 td24"'), array(
					$module['variable'],
					"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[]\" value=\"$key\" checked=\"checked\">",
					"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[$key]\" value=\"$displayorder\">",
					$lang['jswizard_'.$jstypes[$module['type']]],
					'<a href="'.$BASESCRIPT.'?action=jswizard&function='.$jstypes[$module['type']].'&jssetting='.rawurlencode($module['variable']).'">'.lang('edit').'</a>'
					));
				unset($modules[$selectmodule]);
			}
		}
		foreach($modules as $module) {
			$key = htmlspecialchars($module['variable']);
			showtablerow('', array('', '', 'class="td28 td24"'), array(
				$module['variable'],
				"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[]\" value=\"$key\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[$key]\" value=\"$module[displayorder]\">",
				$lang['jswizard_'.$jstypes[$module['type']]],
				'<a href="'.$BASESCRIPT.'?action=jswizard&function='.$jstypes[$module['type']].'&jssetting='.rawurlencode($module['variable']).'">'.lang('edit').'</a>'
				));
		}
		showtablefooter();

		showtableheader('', 'notop');
		showsubmit('submit', 'submit', '', '<a href="'.$BASESCRIPT.'?action=jswizard">'.lang('jswizard_infoside_addcomment').'</a>');
		showtablefooter();
		showformfooter();
	}

} elseif($operation == 'config') {

	if(!submitcheck('settingsubmit')) {

		showsubmenu('nav_javascript', array(
			array('config', 'jswizard&operation=config', 1),
			$infosidemenu,
			array('admin', 'jswizard', 0),
			array(array('menu' => 'jswizard_addmodule', 'submenu' => $addmenu), '', 0),
			array('jswizard_module', 'jswizard&type=module', 0),
			array('import', 'jswizard&operation=import', 0),
		));
		$query = $db->query("SELECT * FROM {$tablepre}settings WHERE variable IN ('jsstatus', 'jsdateformat', 'jsrefdomains', 'infosidestatus', 'jscachelife')");
		while($setting = $db->fetch_array($query)) {
			$settings[$setting['variable']] = $setting['value'];
		}

		showformheader('jswizard&operation=config', '', 'settings');
		showhiddenfields(array('operation' => $operation));

		$settings['jsdateformat'] = dateformat($settings['jsdateformat']);
		showtableheader();
		showsetting('jswizard_config_jsstatus', 'settingsnew[jsstatus]', $settings['jsstatus'], 'radio', '', 1);
		showsetting('jswizard_config_jsdateformat', 'settingsnew[jsdateformat]', $settings['jsdateformat'], 'text');
		showsetting('jswizard_config_jsrefdomains', 'settingsnew[jsrefdomains]', $settings['jsrefdomains'], 'textarea');
		showtagfooter('tbody');
		showsetting('jswizard_config_jscachelife', 'settingsnew[jscachelife]', $settings['jscachelife'], 'text');
		showtablerow('', 'colspan="2"', '<input type="submit" class="btn" name="settingsubmit" value="'.lang('submit').'"  />');
		showtablefooter();
		showformfooter();

	} else {

		foreach($settingsnew as $key => $val) {
			$db->query("REPLACE INTO {$tablepre}settings (variable, value)
				VALUES ('$key', '$val')");
		}
		updatecache('settings');
		cpmsg('jswizard_succeed', $BASESCRIPT.'?action=jswizard&operation=config', 'succeed');

	}

}

function jsforumselect($function) {
	global $parameter, $lang, $db, $tablepre;
	if(empty($function) || in_array($function, array('forums', 'threads', 'images'))) {
		$forumselect = '<select name="parameter['.$function.'_forums][]" size="10" multiple="multiple">'.
			'<option value="all" '.(is_array($parameter[$function.'_forums']) && in_array('all', $parameter[$function.'_forums']) ? 'selected="selected"' : '').'> '.$lang['jswizard_all_forums'].'</option>'.
			'<option value="">&nbsp;</option>';
		if($function == 'forums') {
			$query = $db->query("SELECT fid, name FROM {$tablepre}forums WHERE type='group' AND status='1' ORDER BY displayorder");
			while($category = $db->fetch_array($query)) {
				$forumselect .= '<option value="'.$category['fid'].'">'.strip_tags($category['name']).'</option>';
			};
		} else {
			require_once DISCUZ_ROOT.'./include/forum.func.php';
			$forumselect .= forumselect(FALSE, 0, 0, TRUE);
		}
		$forumselect .= '</select>';

		if(is_array($parameter[$function.'_forums'])) {
			foreach($parameter[$function.'_forums'] as $key => $value) {
				if(!$value) {
					unset($parameter[$function.'_forums'][$key]);
				}
			}
			if(!in_array('all', $parameter[$function.'_forums'])) {
				$forumselect = preg_replace("/(\<option value=\"(".implode('|', $parameter[$function.'_forums']).")\")(\>)/", "\\1 selected=\"selected\"\\3", $forumselect);
			}
		}
		return $forumselect;
	}
}

function jspreviewcode($jsurlview) {
	global $BASESCRIPT;
	return '<div class="jswizard"><iframe id="preview" name="preview" frameborder="0" allowtransparency="true" onload="this.style.height = this.contentWindow.document.body.scrollHeight + \'px\'" width="95%" height="0"></iframe></div>
		<form id="previewform" action="'.$BASESCRIPT.'?action=jswizard&previewurl=yes" method="post" target="preview"><input name="jsurl" type="hidden" value="'.htmlspecialchars($jsurlview).'" /></form>
		<script type="text/javascript">$(\'previewform\').submit()</script>';
}

function jsfids($fidarray) {
	foreach($fidarray as $key => $val) {
		if(empty($val)) {
			unset($fidarray[$key]);
		}
	}
	return implode('_', $fidarray);
}

function jssavesetting($type) {
	global $operation, $db, $tablepre, $jswizard, $jsurl, $parameter, $comment, $jskey, $edit, $BASESCRIPT, $sideid, $from;
	$editadd = $edit ? "AND variable!='$edit'" : '';
	if($db->result_first("SELECT variable FROM {$tablepre}request WHERE variable='$jskey' $editadd")) {
		cpmsg('jswizard_jskey_exists', '', 'error');
	}
	$jskey = str_replace('&', '', $jskey);
	$jswizard = addslashes(serialize(array('url' => $jsurl, 'parameter' => $parameter, 'comment' => $comment)));
	if(strlen($jswizard) > 65535) {
		cpmsg('jswizard_overflow', '', 'error');
	}
	if($edit) {
		$db->query("UPDATE {$tablepre}request SET variable='$jskey', value='$jswizard' WHERE variable='$edit'");
	} else {
		$db->query("INSERT INTO {$tablepre}request (variable, value, `type`) VALUES ('$jskey', '$jswizard', '$type')");
	}
	updatecache('request');
	list($keypre, $keyp) = explode('_', $jskey);
	$msg = $type != -2 ? 'jswizard_succeed' : 'jswizard_infoside_succeed';
	if($operation == 'infoside') {
		cpmsg($msg, $BASESCRIPT.'?action=jswizard&operation=infoside&sideid='.$sideid.'&from='.$from, 'succeed');
	} else {
		cpmsg($msg, $BASESCRIPT.'?action=jswizard'.($keyp ? '&openkeypre='.$keypre : ''), 'succeed');
	}
}

function jsinsertunit() {
?>
<script type="text/JavaScript">
	function isUndefined(variable) {
		return typeof variable == 'undefined' ? true : false;
	}

	function insertunit(text, obj) {
		if(!obj) {
			obj = 'jstemplate';
		}
		$(obj).focus();
		if(!isUndefined($(obj).selectionStart)) {
			var opn = $(obj).selectionStart + 0;
			$(obj).value = $(obj).value.substr(0, $(obj).selectionStart) + text + $(obj).value.substr($(obj).selectionEnd);
		} else if(document.selection && document.selection.createRange) {
			var sel = document.selection.createRange();
			sel.text = text.replace(/\r?\n/g, '\r\n');
			sel.moveStart('character', -strlen(text));
		} else {
			$(obj).value += text;
		}
	}
</script>
<?
}

function jsgetrequests() {
	global $requestlang;
	$dir = DISCUZ_ROOT.'./include/request';
	$requestdir = dir($dir);
	$requests = array();
	$requestrun = 0;
	while($entry = $requestdir->read()) {
		if(!in_array($entry, array('.', '..')) && preg_match("/^[\w\.]+$/", $entry) && substr($entry, -8) == '.inc.php' && strlen($entry) < 30 && is_file($dir.'/'.$entry)) {
			include $dir.'/'.$entry;
			$requests[$entry] = array($entry, $request_name, $request_version, $request_copyright, 'filemtime' => @filemtime($dir.'/'.$entry));
		}
	}
	uasort($requests, 'filemtimesort');
	return $requests;
}

function dateformat($string, $operation = 'formalise') {
	$string = htmlspecialchars(trim($string));
	$replace = $operation == 'formalise' ? array(array('n', 'j', 'y', 'Y'), array('mm', 'dd', 'yy', 'yyyy')) : array(array('mm', 'dd', 'yyyy', 'yy'), array('n', 'j', 'Y', 'y'));
	return str_replace($replace[0], $replace[1], $string);
}

?>