<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: admincp.inc.php 20873 2009-10-28 04:42:10Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!submitcheck('settingsubmit')) {

	$query = $db->query("SELECT * FROM {$tablepre}settings WHERE variable='uchome'");
	while($setting = $db->fetch_array($query)) {
		$settings[$setting['variable']] = $setting['value'];
	}

	$query = $db->query("SELECT fid, allowfeed FROM {$tablepre}forums");
	while($forum = $db->fetch_array($query)) {
		if($forum['allowfeed']) {
			$allowfeeds[] = $forum['fid'];
		}
	}

	require_once DISCUZ_ROOT.'./uc_client/client.php';
	$ucapparray = uc_app_ls();

	$uchomeopen = FALSE;
	$apparraylist = array();
	foreach($ucapparray as $apparray) {
		if($apparray['appid'] != UC_APPID) {
			$apparraylist[] = $apparray;
		}
		if($apparray['type'] == 'UCHOME') {
			$uchomeopen = TRUE;
		}
	}

	require_once DISCUZ_ROOT.'./include/forum.func.php';
	$allowfeedsels = '<select name="allowfeedsnew[]" size="10" multiple="multiple"><option value="">'.lang('plugins_empty').'</option>'.forumselect().'</select>';
	foreach($allowfeeds as $v) {
		$allowfeedsels = str_replace('<option value="'.$v.'">', '<option value="'.$v.'" selected>', $allowfeedsels);
	}

	if($uchomeopen) {
		showformheader('plugins&operation=config&identifier=uchome&mod=admincp');
		showtableheader();

		$settings['uchome'] = unserialize($settings['uchome']);

		foreach($apparraylist as $apparray) {
			$checked = $settings['uchome']['navlist'][$apparray['appid']] ? 'checked="checked"': '';
			$applist .= "<input type=\"checkbox\" class=\"checkbox\" name=\"appnew[navlist][$apparray[appid]]\" value=\"$apparray[name]\" $checked>$apparray[name]&nbsp;&nbsp;";
		}

		showsetting($scriptlang['uchome']['settings_uc_feed'], array('settingsnew[uchome][addfeed]', array(
			$scriptlang['uchome']['settings_uc_feed_thread'],
			$scriptlang['uchome']['settings_uc_feed_sepcialthread'],
			$scriptlang['uchome']['settings_uc_feed_reply'])), $settings['uchome']['addfeed'], 'binmcheckbox', 0, '', $scriptlang['uchome']['settings_uc_feed_comment']);

		showsetting($scriptlang['uchome']['settings_uc_home_allowfeed'], '', '', $allowfeedsels, 0, '', $scriptlang['uchome']['settings_uc_home_allowfeed_comment']);

		showsetting($scriptlang['uchome']['settings_uc_home_show'], array('settingsnew[uchome][homeshow]', array(
			$scriptlang['uchome']['settings_uc_home_avatarshow'],
			$scriptlang['uchome']['settings_uc_home_viewproshow'],
			$scriptlang['uchome']['settings_uc_home_adshow'],
			$scriptlang['uchome']['settings_uc_home_side'])), $settings['uchome']['homeshow'], 'binmcheckbox', 0, '', $scriptlang['uchome']['settings_uc_home_show_comment']);
		showsubmit('settingsubmit');
		showtablefooter();
		showformfooter();
	} else {
		cpmsg('uchome:settings_uc_home_nonexistence', '', '', '', FALSE);
		exit();
	}

} else {

	$settingsnew['uchome']['addfeed'] = bindec(intval($settingsnew['uchome']['addfeed'][3]).intval($settingsnew['uchome']['addfeed'][2]).intval($settingsnew['uchome']['addfeed'][1]));
	$settingsnew['uchome']['homeshow'] = bindec(intval($settingsnew['uchome']['homeshow'][4]).intval($settingsnew['uchome']['homeshow'][3]).intval($settingsnew['uchome']['homeshow'][2]).intval($settingsnew['uchome']['homeshow'][1]));
	$settingsnew['uchome'] = addslashes(serialize($settingsnew['uchome']));
	$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('uchome', '$settingsnew[uchome]')");
	$db->query("UPDATE {$tablepre}plugins SET available='1' WHERE identifier='uchome'");
	$db->query("UPDATE {$tablepre}forums SET allowfeed='0'");
	if($allowfeedsnew) {
		$db->query("UPDATE {$tablepre}forums SET allowfeed='1' WHERE fid IN (".implodeids($allowfeedsnew).")");
	}
	updatecache('plugins');
	updatecache('settings');
	cpmsg('plugins_settings_succeed', $BASESCRIPT.'?action=plugins&operation=config&identifier=uchome&mod=admincp', 'succeed');

}

?>