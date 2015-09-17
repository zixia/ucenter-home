<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: tools.inc.php 17386 2008-12-17 05:10:00Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if($operation == 'updatecache') {
	
	$step = max(1, intval($step));
	shownav('tools', 'nav_updatecache');
	showsubmenusteps('nav_updatecache', array(
		array('nav_updatecache_confirm', $step == 1),
		array('nav_updatecache_verify', $step == 2),
		array('nav_updatecache_completed', $step == 3)
	));

	showtips('tools_updatecache_tips');

	if($step == 1) {
		cpmsg('<input type=\"checkbox\" name=\"type[]\" value=\"data\" id=\"datacache\" class=\"checkbox\" checked /><label for=\"datacache\">'.$lang[tools_updatecache_data].'</label><input type=\"checkbox\" name=\"type[]\" value=\"tpl\" id=\"tplcache\" class=\"checkbox\" checked /><label for=\"tplcache\">'.$lang[tools_updatecache_tpl].'</label>', $BASESCRIPT.'?action=tools&operation=updatecache&step=2', 'form', '', FALSE);
	} elseif($step == 2) {
		$type = implode('_', (array)$type);
		cpmsg(lang('tools_updatecache_waiting'), "$BASESCRIPT?action=tools&operation=updatecache&step=3&type=$type", 'loading', '', FALSE);
	} elseif($step == 3) {
		$type = explode('_', $type);
		if(in_array('data', $type)) {
			updatecache();
		}
		if(in_array('tpl', $type) && $tplrefresh) {
			$tpl = dir(DISCUZ_ROOT.'./forumdata/templates');
			while($entry = $tpl->read()) {
				if(preg_match("/\.tpl\.php$/", $entry)) {
					@unlink(DISCUZ_ROOT.'./forumdata/templates/'.$entry);
				}
			}
			$tpl->close();
		}
		cpmsg('update_cache_succeed', '', 'succeed', '', FALSE);
	}

} elseif($operation == 'tag') {

	include_once DISCUZ_ROOT.'./uc_client/client.php';
	$applist = uc_app_ls();

	if(!submitcheck('submit')) {

		$query = $db->query("SELECT variable, value FROM {$tablepre}settings WHERE variable IN ('relatedtag', 'relatedtagstatus')");

		$settings = array();
		while($setting = $db->fetch_array($query)) {
			$settings[$setting['variable']] = $setting['value'];
		}

		$relatedtag = unserialize($settings['relatedtag']);

		shownav('tools', 'extended_tag');
		showsubmenu('extended_tag');
		showformheader('tools&operation=tag');
		
		if(!$tagstatus) {
			showtips('extended_tag_tips');
		}
		
		showtableheader();
		showsetting('extended_tag_on', 'relatedtagstatusnew', $settings['relatedtagstatus'], 'radio');

		jsinsertunit();
		foreach($applist as $data) {
			$appid = $data['appid'];
			$status = array(intval($relatedtag['status'][$appid]) => 'checked="checked"');
			$template = stripslashes(htmlspecialchars($relatedtag['template'][$appid]['template'] != '' ? $relatedtag['template'][$appid]['template'] : '<a href="{url}" target="_blank">{subject}</a>'));
			$name = $relatedtag['name'][$appid] != '' ? $relatedtag['name'][$appid] : $data['name'];
			showtitle($data['name']);
			showsetting('extended_tag_name', 'relatedtagnew[name]['.$appid.']', $name, 'text');
			showsetting('extended_tag_status', 'relatedtagnew[status]['.$appid.']', $status[0] ? 0 : 1, 'radio');
			showsetting('extended_tag_order', 'relatedtagnew[order]['.$appid.']', $relatedtag['order'][$appid], 'text');
			showsetting('extended_tag_items', 'relatedtagnew[limit]['.$appid.']', intval($relatedtag['limit'][$appid]), 'text');
			echo '<tr><td class="td27" colspan="2">'.$lang['extended_tag_tpl'].'</td></tr><tr><td class="vtop rowform">'.
				'<textarea cols="100" rows="8" id="jstemplate_'.$appid.'" name="relatedtagnew[template]['.$appid.'][template]" class="tarea">'.$template.'</textarea>'.
				'<input type="hidden" name="relatedtagnew[template]['.$appid.'][name]" value="'.$data['name'].'" /></td><td class="vtop"><div class="extcredits">';
			if(is_array($data['tagtemplates']['fields'])) {
				foreach($data['tagtemplates']['fields'] as $field => $memo) {
					echo '<a onclick="insertunit(\'{'.$field.'}\', \'jstemplate_'.$appid.'\')" href="###">{'.$field.'}</a> '.$lang['extended_tag_memo'].' '.$memo.'<br />';
				}
			}
			echo '</td></tr>';
			showhiddenfields(array('relatedtagnew[template]['.$appid.'][name]' => $data['name']));
		}


		showsubmit('submit');
		showtablefooter();
		showformfooter();

	} else {

		$value = addslashes(serialize($relatedtagnew));
		$relatedtagstatusnew = intval($relatedtagstatusnew);
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('relatedtag', '$value'),('relatedtagstatus', '$relatedtagstatusnew')");
		updatecache('settings');
		cpmsg('jswizard_relatedtag_succeed', $BASESCRIPT.'?action=tools&operation=tag', 'succeed');

	}

} elseif($operation == 'fileperms') {

	$step = max(1, intval($step));

	shownav('tools', 'nav_fileperms');
	showsubmenusteps('nav_fileperms', array(
		array('nav_fileperms_confirm', $step == 1),
		array('nav_fileperms_verify', $step == 2),
		array('nav_fileperms_completed', $step == 3)
	));

	if($step == 1) {
		cpmsg(lang('fileperms_check_note'), $BASESCRIPT.'?action=tools&operation=fileperms&step=2', 'button', '', FALSE);
	} elseif($step == 2) {
		cpmsg(lang('fileperms_check_waiting'), $BASESCRIPT.'?action=tools&operation=fileperms&step=3', 'loading', '', FALSE);
	} elseif($step == 3) {

		showtips('fileperms_tips');

		$entryarray = array(
			'attachments',
			'forumdata',
			'forumdata/cache',
			'forumdata/logs',
			'forumdata/templates',
			'forumdata/threadcaches'
		);

		foreach(array('templates', 'forumdata/cache', 'forumdata/logs', 'forumdata/templates') as $directory) {
			getdirentry($directory);
		}

		$result = '';
		foreach($entryarray as $entry) {
			$fullentry = DISCUZ_ROOT.'./'.$entry;
			if(!is_dir($fullentry) && !file_exists($fullentry)) {
				continue;
			} else {
				if(!is_writeable($fullentry)) {
					$result .= '<li class="error">'.(is_dir($fullentry) ? $lang['dir'] : $lang['file'])." ./$entry $lang[fileperms_unwritable]</li>";
				}
			}
		}
		$result = $result ? $result : '<li>'.$lang['fileperms_check_ok'].'</li>';
		echo '<div class="colorbox"><ul class="fileperms">'.$result.'</ul></div>';
	}
}

function getdirentry($directory) {
	global $entryarray;
	$dir = dir(DISCUZ_ROOT.'./'.$directory);
	while($entry = $dir->read()) {
		if(!in_array($entry, array('.', '..', 'index.htm'))) {
			if(is_dir(DISCUZ_ROOT.'./'.$directory.'/'.$entry)) {
				getdirentry($directory."/".$entry);
			}
			$entryarray[] = $directory.'/'.$entry;
		}
	}
	$dir->close();
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

?>