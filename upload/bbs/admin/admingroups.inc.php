<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: admingroups.inc.php 21044 2009-11-09 03:14:02Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!$operation) {

	if(submitcheck('groupsubmit') && $ids = implodeids($delete)) {
		$gids = array();
		$query = $db->query("SELECT groupid FROM {$tablepre}usergroups WHERE groupid IN ($ids) AND type='special' AND radminid>'0'");
		while($g = $db->fetch_array($query)) {
			$gids[] = $g['groupid'];
		}
		if($ids = implodeids($gids)) {
			$db->query("DELETE FROM {$tablepre}usergroups WHERE groupid IN ($ids)");
			$db->query("DELETE FROM {$tablepre}admingroups WHERE admingid IN ($ids)");
			$db->query("DELETE FROM {$tablepre}adminactions WHERE admingid IN ($ids)");
			$newgroupid = $db->result_first("SELECT groupid FROM {$tablepre}usergroups WHERE type='member' AND creditslower>'0' ORDER BY creditslower LIMIT 1");
			$db->query("UPDATE {$tablepre}members SET groupid='$newgroupid', adminid='0' WHERE groupid IN ($ids)", 'UNBUFFERED');
			deletegroupcache($gids);
		}
	}

	$grouplist = array();
	$query = $db->query("SELECT a.*, u.groupid, u.radminid, u.grouptitle, u.stars, u.color, u.groupavatar, u.type FROM {$tablepre}admingroups a
			LEFT JOIN {$tablepre}usergroups u ON u.groupid=a.admingid
			ORDER BY u.type, u.radminid, a.admingid");
	while ($group = $db->fetch_array($query)) {
		$grouplist[$group['groupid']] = $group;
	}

	if(!submitcheck('groupsubmit')) {

		shownav('user', 'nav_admingroups');
		showsubmenu('nav_admingroups');
		showtips('admingroups_tips');

		showformheader('admingroups');
		showtableheader('', 'fixpadding');
		showsubtitle(array('', 'name', 'type', 'admingroups_level', 'usergroups_stars', 'usergroups_color', 'usergroups_avatar', '', ''));

		foreach($grouplist as $gid => $group) {
			showtablerow('', array('', '', 'class="td25"', '', 'class="td25"'), array(
				$group['type'] == 'system' ? '' : "<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$group[groupid]\">",
				$group['grouptitle'],
				$group['type'] == 'system' ? lang('inbuilt') : lang('costom'),
				$lang['usergroups_system_'.$group['radminid']],
				"<input type=\"text\" class=\"txt\" size=\"2\"name=\"group_stars[$group[groupid]]\" value=\"$group[stars]\">",
				"<input type=\"text\" class=\"txt\" size=\"6\"name=\"group_color[$group[groupid]]\" value=\"$group[color]\">",
				"<input type=\"text\" class=\"txt\" size=\"12\" name=\"group_avatar[$group[groupid]]\" value=\"$group[groupavatar]\">",
				"<a href=\"$BASESCRIPT?action=usergroups&operation=edit&id={$group[admingid]}&return=admin\">$lang[admingroups_settings_user]</a>",
				"<a href=\"$BASESCRIPT?action=admingroups&operation=edit&id=$group[admingid]\">$lang[admingroups_settings_admin]</a>"
			));
		}

		showtablerow('', array('class="td25"', '', '', 'colspan="6"'), array(
			lang('add_new'),
			'<input type="text" class="txt" size="12" name="grouptitlenew">',
			lang('costom'),
			"<select name=\"radminidnew\"><option value=\"1\">$lang[usergroups_system_1]</option><option value=\"2\">$lang[usergroups_system_2]</option><option value=\"3\" selected=\"selected\">$lang[usergroups_system_3]</option>",
		));
		showsubmit('groupsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		foreach($grouplist as $gid => $group) {
			$stars = intval($group_stars[$gid]);
			$color = dhtmlspecialchars($group_color[$gid]);
			$avatar = dhtmlspecialchars($group_avatar[$gid]);
			if($group['color'] != $color || $group['stars'] != $stars || $group['groupavatar'] != $avatar) {
				$db->query("UPDATE {$tablepre}usergroups SET stars='$stars', color='$color', groupavatar='$avatar' WHERE groupid='$gid'");
			}
		}

		$grouptitlenew = dhtmlspecialchars(trim($grouptitlenew));
		$radminidnew = intval($radminidnew);

		if($grouptitlenew && in_array($radminidnew, array(1, 2, 3))) {

			$ufields = '';
			$usergroup = $db->fetch_first("SELECT * FROM {$tablepre}usergroups WHERE groupid='$radminidnew'");
			foreach ($usergroup as $key => $val) {
				if(!in_array($key, array('groupid', 'radminid', 'type', 'system', 'grouptitle'))) {
					$val = addslashes($val);
					$ufields .= ", `$key`='$val'";
				}
			}

			$afields = '';
			$admingroup = $db->fetch_first("SELECT * FROM {$tablepre}admingroups WHERE admingid='$radminidnew'");
			foreach ($admingroup as $key => $val) {
				if(!in_array($key, array('admingid'))) {
					$val = addslashes($val);
					$afields .= ", `$key`='$val'";
				}
			}

			$db->query("INSERT INTO {$tablepre}usergroups SET radminid='$radminidnew', type='special', grouptitle='$grouptitlenew' $ufields");
			if($newgroupid = $db->insert_id()) {
				$db->query("REPLACE INTO {$tablepre}admingroups SET admingid='$newgroupid' $afields");
				if($radminidnew == 1) {
					$dactionarray = array('members_edit', 'members_group', 'db_runquery', 'db_import', 'usergroups', 'admingroups', 'templates', 'plugins');
					$db->query("REPLACE INTO {$tablepre}adminactions (admingid, disabledactions)
						VALUES ('$newgroupid', '".addslashes(serialize($dactionarray))."')");
				}
			}
		}

		cpmsg('admingroups_edit_succeed', $BASESCRIPT.'?action=admingroups', 'succeed');

	}

} elseif($operation == 'edit') {

	$actionarray = array(
		'_readonly' => array(),
		'settings' => array('basic', 'access', 'styles', 'seo', 'cachethread', 'serveropti', 'editor', 'functions', 'permissions', 'credits', 'mail', 'sec', 'datetime', 'attach', 'wap', 'dzfeed', 'uc', 'ec', 'msn'),
		'forums' => array('edit', 'moderators', 'delete', 'merge', 'copy'),
		'threadtypes' => array(),
		'members' => array('add', 'group', 'access', 'credit', 'medal', 'edit', 'ban', 'ipban', 'reward', 'newsletter', 'confermedal', 'clean', 'repeat'),
		'profilefields' => array(),
		'usergroups' => array(),
		'admingroups' => array(),
		'ranks' => array(),
		'styles' => array(),
		'templates' => array('add', 'edit', 'copy'),
		'moderate' => array('members', 'threads', 'replies'),
		'threads' => array(),
		'prune' => array(),
		'recyclebin' => array(),
		'announce' => array(),
		'smilies' => array(),
		'misc' => array('link', 'onlinelist', 'censor', 'bbcode', 'tag', 'icon', 'focus', 'customnav', 'stamp', 'attachtype', 'cron'),
		'adv' => array('config', 'advadd', 'advedit'),
		'db' => array('runquery', 'optimize', 'export', 'import', 'dbcheck'),
		'tools' => array('updatecache', 'fileperms', 'tag'),
		'attach' => array(),
		'counter' => array(),
		'jswizard' => array(),
		'creditwizard' => array(),
		'qihoo' => array('config', 'topics', 'relatedthreads'),
		'tasks' => array(),
		'ec' => array('alipay', 'tenpay', 'orders', 'credit'),
		'medals' => array(),
		'plugins' => array('config', 'edit', 'import', 'export', 'upgrade', 'add', 'hooks', 'vars'),
		'logs' => array('illegal', 'ban', 'mods', 'cp', 'error', 'rate', 'credit', 'magic', 'medal', 'invite', 'payment'),
		'addons' => array(),
		'faq' => array(),
		'magic' => array(),
		'project' => array(),
	);

	$actioncat = array (
		'admingroups_edit_action_cat_basesetting' => array(array('_readonly', 'settings', 'templates'), array('profilefields', 'ranks', 'styles', 'smilies')),
		'admingroups_edit_action_cat_resourcesetting' => array(array('forums', 'members'), array('threadtypes', 'usergroups', 'admingroups')),
		'admingroups_edit_action_cat_resourceadmin' => array(array('moderate'), array('threads', 'prune', 'recyclebin', 'attach')),
		'admingroups_edit_action_cat_extraadmin' => array(array('ec', 'plugins', 'adv', 'qihoo'), array('tasks', 'magic', 'medals', 'addons')),
		'admingroups_edit_action_cat_otheradmin' => array(array('misc', 'tools'), array('announce', 'faq', 'jswizard')),
		'admingroups_edit_action_cat_maint' => array(array('db', 'logs'), array('counter', 'creditwizard', 'project')),
	);

	$submitcheck = submitcheck('groupsubmit');
	if(!$submitcheck && empty($gset)) {

		$id = isset($id) ? intval($id) : 0;

		if(empty($id)) {
			$grouplist = "<select name=\"id\" style=\"width: 150px\">\n";
			$query = $db->query("SELECT u.groupid, u.grouptitle FROM {$tablepre}admingroups a LEFT JOIN {$tablepre}usergroups u ON u.groupid=a.admingid ORDER BY u.type, u.radminid, a.admingid");
			while($group = $db->fetch_array($query)) {
				$grouplist .= "<option value=\"$group[groupid]\">$group[grouptitle]</option>\n";
			}
			$grouplist .= '</select>';
			cpmsg('admingroups_edit_nonexistence', $BASESCRIPT.'?action=admingroups&operation=edit'.(!empty($highlight) ? "&highlight=$highlight" : ''), 'form', $grouplist);
		}

		$group = $db->fetch_first("SELECT a.*, aa.disabledactions, u.radminid, u.grouptitle FROM {$tablepre}admingroups a
			LEFT JOIN {$tablepre}usergroups u ON u.groupid=a.admingid
			LEFT JOIN {$tablepre}adminactions aa ON aa.admingid=a.admingid
			WHERE a.admingid='$id'");

		if(!$group) {
			cpmsg('undefined_action', '', 'error');
		}

		$query = $db->query("SELECT u.radminid, u.groupid, u.grouptitle FROM {$tablepre}admingroups a LEFT JOIN {$tablepre}usergroups u ON u.groupid=a.admingid ORDER BY u.radminid, a.admingid");
		$grouplist = $gutype = '';
		while($ggroup = $db->fetch_array($query)) {
			if($gutype != $ggroup['radminid']) {
				$grouplist .= '<em>'.($ggroup['radminid'] == 1 ? $lang['usergroups_system_1'] : ($ggroup['radminid'] == 2 ? $lang['usergroups_system_2'] : $lang['usergroups_system_3'])).'</em>';
				$gutype = $ggroup['radminid'];
			}
			$grouplist .= '<a href="###" onclick="location.href=\''.$BASESCRIPT.'?action=admingroups&operation=edit&switch=yes&id='.$ggroup['groupid'].'&anchor=\'+currentAnchor+\'&scrolltop=\'+document.documentElement.scrollTop"'.($id == $ggroup['groupid'] ? ' class="current"' : '').'>'.$ggroup['grouptitle'].'</a>';
		}
		$gselect = '<span id="ugselect" class="right popupmenu_dropmenu" onmouseover="showMenu({\'ctrlid\':this.id,\'pos\':\'34\'});$(\'ugselect_menu\').style.top=(parseInt($(\'ugselect_menu\').style.top)-document.documentElement.scrollTop)+\'px\'">'.$lang['usergroups_switch'].'<em>&nbsp;&nbsp;</em></span>'.
			'<div id="ugselect_menu" class="popupmenu_popup" style="display:none">'.$grouplist.'</div>';

		$anchor = $group['radminid'] == 1 ?
			(in_array($anchor, array('admincpperm', 'threadperm', 'postperm', 'modcpperm', 'otherperm')) ? $anchor : 'admincpperm') :
			(in_array($anchor, array('threadperm', 'postperm', 'modcpperm', 'otherperm')) ? $anchor : 'threadperm');
		$anchorarray = array(
			$group['radminid'] == 1 ? array('admingroups_edit_admincpperm', 'admincpperm', $anchor == 'admincpperm') : array(),
			array('admingroups_edit_threadperm', 'threadperm', $anchor == 'threadperm'),
			array('admingroups_edit_postperm', 'postperm', $anchor == 'postperm'),
			array('admingroups_edit_modcpperm', 'modcpperm', $anchor == 'modcpperm'),
			array('admingroups_edit_otherperm', 'otherperm', $anchor == 'otherperm'),
		);

		showsubmenuanchors($lang['admingroups_edit'].' - '.$group['grouptitle'], $anchorarray, $gselect);
		if(!empty($switch)) {
			echo '<script type="text/javascript">showMenu({\'ctrlid\':\'ugselect\',\'pos\':\'34\'});</script>';
		}
		if($id == 1) {
			showtips('admingroups_admin_tips');
		}
		showformheader("admingroups&operation=edit&id=$id");

		if($group['radminid'] == 1) {
			echo '<style>'.
				'.item{ float:left;width: 180px;line-height: 25px; }'.
				'.vtop .right, .item .right{ padding: 0 10px; line-height: 22px; background: url(\'images/admincp/bg_repno.gif\') no-repeat -286px -145px; font-weight: normal;margin-right:10px; }'.
				'.vtop a:hover.right, .item a:hover.right { text-decoration:none; }'.
				'</style>';
			showtagheader('div', 'admincpperm', $anchor == 'admincpperm');
			showtableheader();
			$group['disabledactions'] = $group['disabledactions'] ? (array)unserialize($group['disabledactions']) : array();
			foreach($actioncat as $catname => $catdatas) {
				showtitle($catname);
				$rowtype = 0;
				foreach($catdatas as $k => $catdata) {
					if($k) {
						echo '<tr><td class="vtop td27" width="200"><b>'.$lang['admingroups_edit_action_other'].'</b></td><td>';
					}
					$j = 1;
					foreach($catdata as $actionstr) {
						if(!$k) {
							$operationstr = $actionarray[$actionstr];
							echo '<tr><td class="vtop td27" width="200"><a class="right" title="'.lang('config').'" href="'.$BASESCRIPT.'?frames=yes&action=admingroups&operation=edit&gset='.$actionstr.'" target="_blank">&nbsp;</a><input name="disabledactionnew[]" value="'.$actionstr.'" class="checkbox" type="checkbox" '.(!in_array($actionstr, $group['disabledactions']) ? 'checked="checked" ' : '').'/>'.lang('admingroups_edit_action_'.$actionstr).'</td><td class="vtop">';
							$i = 1;
							if($operationstr) {
								foreach($operationstr as $opstr) {
									$str = $actionstr.'_'.$opstr;
									echo '<div class="item"><a class="right" title="'.lang('config').'" href="'.$BASESCRIPT.'?frames=yes&action=admingroups&operation=edit&gset='.$str.'" target="_blank">&nbsp;</a><label><input name="disabledactionnew[]" value="'.$str.'" class="checkbox" type="checkbox" '.(!in_array($str, $group['disabledactions']) ? 'checked="checked" ' : '').'/>'.lang('admingroups_edit_action_'.$str).'</label></div>';
									if($i == 3) {
										echo '<br style="clear:both" />';
										$i = 0;
									}
									$i++;
								}
							} else {
								if(isset($lang['admingroups_edit_action_'.$actionstr.'_comment'])) {
									echo '<span class="tips2">'.lang('admingroups_edit_action_'.$actionstr.'_comment').'</span>';
								}
							}
							echo '</td></tr>';
						} else {
							echo '<div class="item"><a class="right" title="'.lang('config').'" href="'.$BASESCRIPT.'?frames=yes&action=admingroups&operation=edit&gset='.$actionstr.'" target="_blank">&nbsp;</a><input name="disabledactionnew[]" value="'.$actionstr.'" class="checkbox" type="checkbox" '.(!in_array($actionstr, $group['disabledactions']) ? 'checked="checked" ' : '').'/>'.lang('admingroups_edit_action_'.$actionstr).'</div>';
							if($j == 3) {
								echo '<br style="clear:both" />';
								$j = 0;
							}
							$j++;
						}
					}
					if($k) {
						echo '</td></tr>';
					}
				}
			}
			showtablefooter();
			showtagfooter('div');
		}

		showtableheader();
		showtagheader('tbody', 'threadperm', $anchor == 'threadperm');
		showtitle('admingroups_edit_threadperm');
		showsetting('admingroups_edit_stick_thread', array('allowstickthreadnew', array(
			array(0, $lang['admingroups_edit_stick_thread_none']),
			array(1, $lang['admingroups_edit_stick_thread_1']),
			array(2, $lang['admingroups_edit_stick_thread_2']),
			array(3, $lang['admingroups_edit_stick_thread_3'])
		)), $group['allowstickthread'], 'mradio');
		showsetting('admingroups_edit_digest_thread', array('allowdigestthreadnew', array(
			array(0, $lang['admingroups_edit_digest_thread_none']),
			array(1, $lang['admingroups_edit_digest_thread_1']),
			array(2, $lang['admingroups_edit_digest_thread_2']),
			array(3, $lang['admingroups_edit_digest_thread_3'])
		)), $group['allowdigestthread'], 'mradio');
		showsetting('admingroups_edit_bump_thread', 'allowbumpthreadnew', $group['allowbumpthread'], 'radio');
		showsetting('admingroups_edit_highlight_thread', 'allowhighlightthreadnew', $group['allowhighlightthread'], 'radio');
		showsetting('admingroups_edit_recommend_thread', 'allowrecommendthreadnew', $group['allowrecommendthread'], 'radio');
		showsetting('admingroups_edit_stamp_thread', 'allowstampthreadnew', $group['allowstampthread'], 'radio');
		showsetting('admingroups_edit_close_thread', 'allowclosethreadnew', $group['allowclosethread'], 'radio');
		showsetting('admingroups_edit_move_thread', 'allowmovethreadnew', $group['allowmovethread'], 'radio');
		showsetting('admingroups_edit_edittype_thread', 'allowedittypethreadnew', $group['allowedittypethread'], 'radio');
		showsetting('admingroups_edit_copy_thread', 'allowcopythreadnew', $group['allowcopythread'], 'radio');
		showsetting('admingroups_edit_merge_thread', 'allowmergethreadnew', $group['allowmergethread'], 'radio');
		showsetting('admingroups_edit_split_thread', 'allowsplitthreadnew', $group['allowsplitthread'], 'radio');
		showsetting('admingroups_edit_repair_thread', 'allowrepairthreadnew', $group['allowrepairthread'], 'radio');
		showsetting('admingroups_edit_refund', 'allowrefundnew', $group['allowrefund'], 'radio');
		showsetting('admingroups_edit_edit_poll', 'alloweditpollnew', $group['alloweditpoll'], 'radio');
		showsetting('admingroups_edit_remove_reward', 'allowremoverewardnew', $group['allowremovereward'], 'radio');
		showsetting('admingroups_edit_edit_activity', 'alloweditactivitynew', $group['alloweditactivity'], 'radio');
		showsetting('admingroups_edit_edit_trade', 'allowedittradenew', $group['allowedittrade'], 'radio');
		showtagfooter('tbody');

		showtagheader('tbody', 'postperm', $anchor == 'postperm');
		showtitle('admingroups_edit_postperm');
		showsetting('admingroups_edit_edit_post', 'alloweditpostnew', $group['alloweditpost'], 'radio');
		showsetting('admingroups_edit_warn_post', 'allowwarnpostnew', $group['allowwarnpost'], 'radio');
		showsetting('admingroups_edit_ban_post', 'allowbanpostnew', $group['allowbanpost'], 'radio');
		showsetting('admingroups_edit_del_post', 'allowdelpostnew', $group['allowdelpost'], 'radio');
		showtagfooter('tbody');

		showtagheader('tbody', 'modcpperm', $anchor == 'modcpperm');
		showtitle('admingroups_edit_modcpperm');
		showsetting('admingroups_edit_view_report', 'allowviewreportnew', $group['allowviewreport'], 'radio');
		showsetting('admingroups_edit_mod_post', 'allowmodpostnew', $group['allowmodpost'], 'radio');
		showsetting('admingroups_edit_mod_user', 'allowmodusernew', $group['allowmoduser'], 'radio');
		showsetting('admingroups_edit_ban_user', 'allowbanusernew', $group['allowbanuser'], 'radio');
		showsetting('admingroups_edit_ban_ip', 'allowbanipnew', $group['allowbanip'], 'radio');
		showsetting('admingroups_edit_edit_user', 'alloweditusernew', $group['allowedituser'], 'radio');
		showsetting('admingroups_edit_mass_prune', 'allowmassprunenew', $group['allowmassprune'], 'radio');
		showsetting('admingroups_edit_edit_forum', 'alloweditforumnew', $group['alloweditforum'], 'radio');
		showsetting('admingroups_edit_post_announce', 'allowpostannouncenew', $group['allowpostannounce'], 'radio');
		showsetting('admingroups_edit_view_log', 'allowviewlognew', $group['allowviewlog'], 'radio');
		showtagfooter('tbody');

		showtagheader('tbody', 'otherperm', $anchor == 'otherperm');
		showtitle('admingroups_edit_otherperm');
		showsetting('admingroups_edit_disable_postctrl', 'disablepostctrlnew', $group['disablepostctrl'], 'radio');
		showsetting('admingroups_edit_view_ip', 'allowviewipnew', $group['allowviewip'], 'radio');
		showtagfooter('tbody');

		if($id != 1) {
			showsubmit('groupsubmit');
		}
		showtablefooter();
		showformfooter();

	} elseif(!$submitcheck && !empty($gset)) {

		list($act, $opr) = explode('_', $gset);
		if(!array_key_exists($gset, $actionarray) && !in_array($opr, $actionarray[$act])) {
			cpmsg('undefined_action', '', 'error');
		}

		$groups = array();
		$query = $db->query("SELECT ug.type, ug.groupid, ug.grouptitle, ug.radminid, aa.disabledactions
			FROM {$tablepre}usergroups ug
			LEFT JOIN {$tablepre}adminactions aa ON aa.admingid=ug.groupid
			WHERE ug.radminid='1' ORDER BY (ug.creditshigher<>'0' || ug.creditslower<>'0'), ug.creditslower, ug.groupid");
		while($group = $db->fetch_array($query)) {
			$group['disabledactions'] = $group['disabledactions'] ? unserialize($group['disabledactions']) : '';
			$groups[] = $group;
		}

		shownav('user', 'nav_admingroups');
		showsubmenu($lang['admingroups_edit'].' - '.lang('admingroups_edit_permdetail').' - '.lang('admingroups_edit_action_'.$gset));

		showformheader("admingroups&operation=edit&id=$id&gset=$gset");
		showtableheader();
		showtitle('admingroups_edit_action_'.$gset);
		foreach($groups as $group) {
			echo '<tr><td class="vtop td27" width="150" style="height:20px">'.$group['grouptitle'].'</td>';
			echo '<td><input name="gsetnew[]" type="checkbox" class="checkbox" '.($group['groupid'] != 1 ? '' : 'disabled="disabled"').' value="'.$group['groupid'].'" '.($group['disabledactions'] && in_array($gset, $group['disabledactions']) ? '' : 'checked="checked" ').'/></td>';
		}
		showtablefooter();
		showsubmit('groupsubmit');
		showformfooter();

	} elseif($id != 1) {

		if(!empty($gset)) {
			list($act, $opr) = explode('_', $gset);
			if(!array_key_exists($gset, $actionarray) && !in_array($opr, $actionarray[$act])) {
				cpmsg('undefined_action', '', 'error');
			}
			$query = $db->query("SELECT admingid, disabledactions FROM {$tablepre}adminactions");
			$groups = array();
			while($group = $db->fetch_array($query)) {
				$group['disabledactions'] = unserialize($group['disabledactions']);
				if(!in_array($group['admingid'], $gsetnew)) {
					$group['disabledactions'][] = $gset;
				} else {
					$group['disabledactions'] = array_diff($group['disabledactions'], array($gset));
				}
				$db->query("UPDATE {$tablepre}adminactions SET disabledactions='".addslashes(serialize($group['disabledactions']))."' WHERE admingid='".$group['admingid']."'");
			}
			cpmsg('admingroups_edit_succeed', $BASESCRIPT.'?action=admingroups&operation=edit&gset='.$gset, 'succeed');
		}

		$group = $db->fetch_first("SELECT groupid, radminid FROM {$tablepre}usergroups WHERE groupid='$id'");
		if(!$group) {
			cpmsg('undefined_action', '', 'error');
		}

		if($group['radminid'] == 1) {

			$actions = array();
			foreach($actionarray as $key => $val) {
				$actions[] = $key;
				if(!empty($val) && is_array($val)) {
					foreach ($val as $temp) {
						$actions[] = "{$key}_{$temp}";
					}
				}
			}
			$dactionarray = array_diff($actions, $disabledactionnew);

			$db->query("REPLACE INTO {$tablepre}adminactions (admingid, disabledactions)
				VALUES ('$group[groupid]', '".addslashes(serialize($dactionarray))."')");

		}

		$db->query("UPDATE {$tablepre}admingroups SET alloweditpost='$alloweditpostnew', alloweditpoll='$alloweditpollnew', allowedittrade='$allowedittradenew', allowremovereward='$allowremoverewardnew', alloweditactivity='$alloweditactivitynew',
			allowstickthread='$allowstickthreadnew', allowmodpost='$allowmodpostnew', allowbanpost='$allowbanpostnew', allowdelpost='$allowdelpostnew',
			allowmassprune='$allowmassprunenew', allowrefund='$allowrefundnew', allowcensorword='$allowcensorwordnew',
			allowviewip='$allowviewipnew', allowbanip='$allowbanipnew', allowedituser='$alloweditusernew', allowbanuser='$allowbanusernew',
			allowmoduser='$allowmodusernew', allowpostannounce='$allowpostannouncenew', allowhighlightthread='$allowhighlightthreadnew',
			allowdigestthread='$allowdigestthreadnew', allowrecommendthread='$allowrecommendthreadnew', allowbumpthread='$allowbumpthreadnew',
			allowclosethread='$allowclosethreadnew', allowmovethread='$allowmovethreadnew', allowedittypethread='$allowedittypethreadnew',
			allowstampthread='$allowstampthreadnew', allowcopythread='$allowcopythreadnew', allowmergethread='$allowmergethreadnew',
			allowsplitthread='$allowsplitthreadnew', allowrepairthread='$allowrepairthreadnew', allowwarnpost='$allowwarnpostnew',
			allowviewreport='$allowviewreportnew', alloweditforum='$alloweditforumnew', allowviewlog='$allowviewlognew',
			disablepostctrl='$disablepostctrlnew' WHERE admingid='$group[groupid]'");

		updatecache('usergroups');
		updatecache('admingroups');
		cpmsg('admingroups_edit_succeed', $BASESCRIPT.'?action=admingroups&operation=edit&id='.$group['groupid'].'&anchor='.$anchor, 'succeed');
	}
}

function deletegroupcache($groupidarray) {
	if(!empty($groupidarray) && is_array($groupidarray)) {
		foreach ($groupidarray as $id) {
			if(is_numeric($id) && $id = intval($id)) {
				@unlink(DISCUZ_ROOT.'./forumdata/cache/usergroup_'.$id.'.php');
				@unlink(DISCUZ_ROOT.'./forumdata/cache/admingroup_'.$id.'.php');
			}
		}
	}
}

?>