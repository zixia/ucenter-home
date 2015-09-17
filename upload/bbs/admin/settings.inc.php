<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: settings.inc.php 21057 2009-11-10 01:05:36Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

cpheader();

$settings = array();
$query = $db->query("SELECT * FROM {$tablepre}settings");
while($setting = $db->fetch_array($query)) {
	$settings[$setting['variable']] = $setting['value'];
}

if(!$isfounder) {
	unset($settings['ftp']);
}

$extbutton = '';
$operation = $operation ? $operation : 'basic';

if($operation == 'styles') {
	$floatwinkeys = array('login', 'register', 'sendpm', 'newthread', 'reply', 'viewratings', 'viewwarning', 'viewthreadmod', 'viewvote', 'tradeorder', 'activity', 'debate', 'nav', 'usergroups', 'task');
	$floatwinarray = array();
	foreach($floatwinkeys as $k) {
		$floatwinarray[] = array($k, $lang['settings_styles_global_allowfloatwin_'.$k]);
	}
}

if(!submitcheck('settingsubmit')) {

	if($operation == 'ec') {
		if($from == 'creditwizard') {
			shownav('tools', 'nav_creditwizard', 'settings_ec');
		} else {
			shownav('extended', 'nav_ec', 'nav_ec_config');
		}
	} elseif(in_array($operation, array('seo', 'cachethread', 'serveropti'))) {
		shownav('global', 'settings_optimize', 'settings_'.$operation);
	} elseif($operation == 'styles') {
		shownav('style', 'settings_styles');
	} elseif($operation == 'editor') {
		shownav('style', 'settings_editor');
	} else {
		shownav('global', 'settings_'.$operation);
	}

	if(in_array($operation, array('seo', 'cachethread', 'serveropti'))) {
		$current = array($operation => 1);
		showsubmenu('settings_optimize', array(
			array('settings_seo', 'settings&operation=seo', $current['seo']),
			array('settings_cachethread', 'settings&operation=cachethread', $current['cachethread']),
			array('settings_serveropti', 'settings&operation=serveropti', $current['serveropti'])
		));
	} elseif($operation == 'ec') {
		if($from == 'creditwizard') {
			showsubmenu('nav_creditwizard', array(
				array('creditwizard_step_menu_1', 'creditwizard&step=1', 0),
				array('creditwizard_step_menu_2', 'creditwizard&step=2', 0),
				array('creditwizard_step_menu_3', 'creditwizard&step=3', 0),
				array('creditwizard_step_menu_4', 'settings&operation=ec&from=creditwizard', 1),
				array('ec_alipay', 'ec&operation=alipay&from=creditwizard', 0),
				array('ec_tenpay', 'ec&operation=tenpay&from=creditwizard', 0),
			));
		} else {
			showsubmenu('nav_ec', array(
				array('nav_ec_config', 'settings&operation=ec', 1),
				array('nav_ec_alipay', 'ec&operation=alipay', 0),
				array('nav_ec_tenpay', 'ec&operation=tenpay', 0),
				array('nav_ec_credit', 'ec&operation=credit', 0),
				array('nav_ec_orders', 'ec&operation=orders', 0),
				array('nav_ec_tradelog', 'tradelog', 0)
			));
		}
	} elseif($operation == 'access') {
		$anchor = in_array($anchor, array('register', 'access')) ? $anchor : 'register';
		showsubmenuanchors('settings_access', array(
			array('settings_access_register', 'register', $anchor == 'register'),
			array('settings_access_access', 'access', $anchor == 'access')
		));
	} elseif($operation == 'mail') {
		$anchor = in_array($anchor, array('settings', 'check')) ? $anchor : 'settings';
		showsubmenuanchors('settings_mail', array(
			array('settings_mail_settings', 'mailsettings', $anchor == 'settings'),
			array('settings_mail_check', 'mailcheck', $anchor == 'check')
		));
	} elseif($operation == 'sec') {
		$anchor = in_array($anchor, array('seclevel', 'seccode', 'secqaa')) ? $anchor : 'seclevel';
		showsubmenuanchors('settings_sec', array(
			array('settings_sec_seclevel', 'seclevel', $anchor == 'seclevel'),
			array('settings_sec_seccode', 'seccode', $anchor == 'seccode'),
			array('settings_sec_secqaa', 'secqaa', $anchor == 'secqaa')
		));
	} elseif($operation == 'attach') {
		$anchor = in_array($anchor, array('basic', 'image', 'remote', 'antileech')) ? $anchor : 'basic';
		showsubmenuanchors('settings_attach', array(
			array('settings_attach_basic', 'basic', $anchor == 'basic'),
			array('settings_attach_image', 'image', $anchor == 'image'),
			$isfounder ? array('settings_attach_remote', 'remote', $anchor == 'remote') : '',
			array('settings_attach_antileech', 'antileech', $anchor == 'antileech'),
		));
	} elseif($operation == 'styles') {
		$anchor = in_array($anchor, array('global', 'index', 'forumdisplay', 'viewthread', 'member', 'refresh', 'sitemessage')) ? $anchor : 'global';
		$current = array($anchor => 1);
		showsubmenu('settings_styles', array(
			array('settings_styles_global', 'settings&operation=styles&anchor=global', $current['global']),
			array('settings_styles_index', 'settings&operation=styles&anchor=index', $current['index']),
			array('settings_styles_forumdisplay', 'settings&operation=styles&anchor=forumdisplay', $current['forumdisplay']),
			array('settings_styles_viewthread', 'settings&operation=styles&anchor=viewthread', $current['viewthread']),
			array('settings_styles_member', 'settings&operation=styles&anchor=member', $current['member']),
			array('settings_styles_customnav', 'misc&operation=customnav', 0),
			array(array('menu' => 'jswizard_infoside', 'submenu' => array(
				array('jswizard_infoside_global', 'jswizard&operation=infoside&from=style'),
				array('jswizard_infoside_2', 'jswizard&operation=infoside&sideid=2&from=style'),
				array('jswizard_infoside_0', 'jswizard&operation=infoside&sideid=0&from=style'),
			))),
			array('settings_styles_refresh', 'settings&operation=styles&anchor=refresh', $current['refresh']),
			array('settings_styles_sitemessage', 'settings&operation=styles&anchor=sitemessage', $current['sitemessage'])
		));
	} elseif($operation == 'functions') {
		$anchor = in_array($anchor, array('stat', 'mod', 'tags', 'heatthread', 'recommend', 'other')) ? $anchor : 'stat';
		showsubmenuanchors('settings_functions', array(
			array('settings_functions_stat', 'stat', $anchor == 'stat'),
			array('settings_functions_mod', 'mod', $anchor == 'mod'),
			array('settings_functions_tags', 'tags', $anchor == 'tags'),
			array('settings_functions_heatthread', 'heatthread', $anchor == 'heatthread'),
			array('settings_functions_recommend', 'recommend', $anchor == 'recommend'),
			array('settings_functions_other', 'other', $anchor == 'other'),
		));
	} elseif($operation == 'editor') {
		showsubmenu('settings_editor', array(
			array('settings_editor_global', 'settings&operation=editor', 1),
			array('settings_editor_code', 'misc&operation=bbcode', 0),
		));
	} elseif($operation == 'msn') {
		shownav('extended', 'settings_msn');
	} else {
		showsubmenu('settings_'.$operation);
	}
	showformheader('settings&edit=yes');
	showhiddenfields(array('operation' => $operation));

	if($operation == 'basic') {

		showtableheader();
		showsetting('settings_basic_bbname', 'settingsnew[bbname]', $settings['bbname'], 'text');
		showsetting('settings_basic_sitename', 'settingsnew[sitename]', $settings['sitename'], 'text');
		showsetting('settings_basic_siteurl', 'settingsnew[siteurl]', $settings['siteurl'], 'text');
		showsetting('settings_basic_index_name', 'settingsnew[indexname]', $settings['indexname'], 'text');
		showsetting('settings_basic_icp', 'settingsnew[icp]', $settings['icp'], 'text');
		showsetting('settings_basic_boardlicensed', 'settingsnew[boardlicensed]', $settings['boardlicensed'], 'radio');
		showsetting('settings_basic_bbclosed', 'settingsnew[bbclosed]', $settings['bbclosed'], 'radio');
		showsetting('settings_basic_closedreason', 'settingsnew[closedreason]', $settings['closedreason'], 'textarea');
		showsetting('settings_basic_stat', 'settingsnew[statcode]', $settings['statcode'], 'textarea');

	} elseif($operation == 'access') {

		$wmsgcheck = array($settings['welcomemsg'] =>'checked');
		$settings['inviteconfig'] = unserialize($settings['inviteconfig']);
		$settings['extcredits'] = unserialize($settings['extcredits']);

		$buycredits = $rewardcredist = '';
		for($i = 0; $i <= 8; $i++) {
			if($settings['extcredits'][$i]['available']) {
				$extcredit = 'extcredits'.$i.' ('.$settings['extcredits'][$i]['title'].')';
				$buycredits .= '<option value="'.$i.'" '.($i == intval($settings['inviteconfig']['invitecredit']) ? 'selected' : '').'>'.($i ? $extcredit : $lang['none']).'</option>';
				$rewardcredits .= '<option value="'.$i.'" '.($i == intval($settings['inviteconfig']['inviterewardcredit']) ? 'selected' : '').'>'.($i ? $extcredit : $lang['none']).'</option>';
			}
		}

		$groupselect = '';
		$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups WHERE type='special'");
		while($group = $db->fetch_array($query)) {
			$groupselect .= "<option value=\"$group[groupid]\" ".($group['groupid'] == $settings['inviteconfig']['invitegroupid'] ? 'selected' : '').">$group[grouptitle]</option>\n";
		}

		$taskarray = array(array('', lang('select')));
		$query = $db->query("SELECT taskid, name FROM {$tablepre}tasks WHERE available='2'");
		while($task = $db->fetch_array($query)) {
			$taskarray[] = array($task['taskid'], $task['name']);
		}

		showtableheader('', 'nobottom', 'id="register"'.($anchor != 'register' ? ' style="display: none"' : ''));
		showsetting('settings_access_register_status', array('settingsnew[regstatus]', array(
			array(0, $lang['settings_access_register_close'], array('showinvite' => 'none')),
			array(1, $lang['settings_access_register_open'], array('showinvite' => 'none')),
			array(2, $lang['settings_access_register_invite'], array('showinvite' => '')),
			array(3, $lang['settings_access_register_open_invite'], array('showinvite' => ''))
		)), $settings['regstatus'], 'mradio');

		showtagheader('tbody', 'showinvite', $settings['regstatus'] > 1, 'sub');
		showsetting('settings_access_register_invite_credit', '', '', '<select name="settingsnew[inviteconfig][inviterewardcredit]">'.$rewardcredits.'</select>');
		showsetting('settings_access_register_invite_addcredit', 'settingsnew[inviteconfig][inviteaddcredit]', $settings['inviteconfig']['inviteaddcredit'], 'text');
		showsetting('settings_access_register_invite_invitedcredit', 'settingsnew[inviteconfig][invitedaddcredit]', $settings['inviteconfig']['invitedaddcredit'], 'text');
		showsetting('settings_access_register_invite_addfriend', 'settingsnew[inviteconfig][inviteaddbuddy]', $settings['inviteconfig']['inviteaddbuddy'], 'radio');
		showsetting('settings_access_register_invite_group', '', '', '<select name="settingsnew[inviteconfig][invitegroupid]"><option value="0">'.$lang['usergroups_system_0'].'</option>'.$groupselect.'</select>');
		showtagfooter('tbody');
		showsetting('settings_access_register_name', 'settingsnew[regname]', $settings['regname'], 'text');
		showsetting('settings_access_register_link_name', 'settingsnew[reglinkname]', $settings['reglinkname'], 'text');
		showsetting('settings_access_register_censoruser', 'settingsnew[censoruser]', $settings['censoruser'], 'textarea');
		showsetting('settings_access_register_verify', array('settingsnew[regverify]', array(
			array(0, $lang['none']),
			array(1, $lang['settings_access_register_verify_email']),
			array(2, $lang['settings_access_register_verify_manual'])
		)), $settings['regverify'], 'select');
		showsetting('settings_access_register_verify_ipwhite', 'settingsnew[ipverifywhite]', $settings['ipverifywhite'], 'textarea');
		showsetting('settings_access_register_ctrl', 'settingsnew[regctrl]', $settings['regctrl'], 'text');
		showsetting('settings_access_register_floodctrl', 'settingsnew[regfloodctrl]', $settings['regfloodctrl'], 'text');
		showsetting('settings_access_register_ipctrl', 'settingsnew[ipregctrl]', $settings['ipregctrl'], 'textarea');
		showsetting('settings_access_register_welcomemsg', array('settingsnew[welcomemsg]', array(
			array(0, $lang['settings_access_register_welcomemsg_nosend'], array('welcomemsgext' => 'none')),
			array(1, $lang['settings_access_register_welcomemsg_pm'], array('welcomemsgext' => '')),
			array(2, $lang['settings_access_register_welcomemsg_email'], array('welcomemsgext' => ''))
		)), $settings['welcomemsg'], 'mradio');
		showtagheader('tbody', 'welcomemsgext', $settings['welcomemsg'], 'sub');
		showsetting('settings_access_register_welcomemsgtitle', 'settingsnew[welcomemsgtitle]', $settings['welcomemsgtitle'], 'text');
		showsetting('settings_access_register_welcomemsgtxt', 'settingsnew[welcomemsgtxt]', $settings['welcomemsgtxt'], 'textarea');
		showtagfooter('tbody');
		showsetting('settings_access_register_bbrules', 'settingsnew[bbrules]', $settings['bbrules'], 'radio', '', 1);
		showsetting('settings_access_register_bbrulestxt', 'settingsnew[bbrulestxt]', $settings['bbrulestxt'], 'textarea');
		showtagfooter('tbody');
		showtablefooter();

		showtableheader('', 'nobottom', 'id="access"'.($anchor != 'access' ? ' style="display: none"' : ''));
		showsetting('settings_access_access_newbiespan', 'settingsnew[newbiespan]', $settings['newbiespan'], 'text');
		showsetting('settings_access_access_ipaccess', 'settingsnew[ipaccess]', $settings['ipaccess'], 'textarea');
		showsetting('settings_access_access_adminipaccess', 'settingsnew[adminipaccess]', $settings['adminipaccess'], 'textarea');
		showsetting('settings_access_access_domainwhitelist', 'settingsnew[domainwhitelist]', $settings['domainwhitelist'], 'textarea');
		showtablefooter();

		showtableheader('', 'notop');
		showsubmit('settingsubmit');
		showtablefooter();
		showformfooter();
		cpfooter();
		exit;

	} elseif($operation == 'styles') {

		$showsettings = str_pad(decbin($settings['showsettings']), 3, 0, STR_PAD_LEFT);
		$settings['showsignatures'] = $showsettings{0};
		$settings['showavatars'] = $showsettings{1};
		$settings['showimages'] = $showsettings{2};
		$settings['postnocustom'] = implode("\n", (array)unserialize($settings['postnocustom']));
		$settings['sitemessage'] = unserialize($settings['sitemessage']);
		$settings['disallowfloat'] = $settings['disallowfloat'] ? unserialize($settings['disallowfloat']) : array();
		$settings['allowfloatwin'] = array_diff($floatwinkeys, $settings['disallowfloat']);
		$settings['indexhot'] = unserialize($settings['indexhot']);

		$settings['customauthorinfo'] = unserialize($settings['customauthorinfo']);
		$settings['customauthorinfo'] = $settings['customauthorinfo'][0];
		list($settings['zoomstatus'], $settings['imagemaxwidth']) = explode("\t", $settings['zoomstatus']);
		$settings['imagemaxwidth'] = !empty($settings['imagemaxwidth']) ? $settings['imagemaxwidth'] : 600;

		$stylelist = "<select name=\"settingsnew[styleid]\">\n";
		$query = $db->query("SELECT styleid, name FROM {$tablepre}styles");
		while($style = $db->fetch_array($query)) {
			$selected = $style['styleid'] == $settings['styleid'] ? 'selected="selected"' : NULL;
			$stylelist .= "<option value=\"$style[styleid]\" $selected>$style[name]</option>\n";
		}
		$stylelist .= '</select>';

		showtips('settings_tips', 'global_tips', $anchor == 'global');
		showtips('settings_tips', 'index_tips', $anchor == 'index');
		showtips('settings_tips', 'forumdisplay_tips', $anchor == 'forumdisplay');

		showtableheader('', 'nobottom', 'id="global"'.($anchor != 'global' ? ' style="display: none"' : ''));
		showsetting('settings_styles_global_styleid', '', '', $stylelist);
		showsetting('settings_styles_global_stylejump', 'settingsnew[stylejump]', $settings['stylejump'], 'radio');
		showsetting('settings_styles_global_jsmenu', 'settingsnew[forumjump]', $settings['forumjump'], 'radio');
		showsetting('settings_styles_global_frameon', array('settingsnew[frameon]', array(
			array(0, $lang['settings_styles_global_frameon_0'], array('frameonext' => 'none')),
			array(1, $lang['settings_styles_global_frameon_1'], array('frameonext' => '')),
			array(2, $lang['settings_styles_global_frameon_2'], array('frameonext' => ''))
		)), $settings['frameon'], 'mradio');
		showtagheader('tbody', 'frameonext', $settings['frameon'], 'sub');
		showsetting('settings_styles_global_framewidth', 'settingsnew[framewidth]', $settings['framewidth'], 'text');
		showtagfooter('tbody');
		showsetting('settings_styles_global_allowfloatwin', array('settingsnew[allowfloatwin]', $floatwinarray), $settings['allowfloatwin'], 'mcheckbox');
		showsetting('settings_styles_global_creditnotice', 'settingsnew[creditnotice]', $settings['creditnotice'], 'radio');

		showtableheader('', 'nobottom', 'id="index"'.($anchor != 'index' ? ' style="display: none"' : ''));
		showsetting('settings_styles_index_allowindextype', 'settingsnew[allowindextype]', $settings['indextype'] ? 1 : 0, 'radio', 0, 1);
		showsetting('settings_styles_index_indextype', array('settingsnew[indextype]', array(
			array('classics', $lang['settings_styles_index_indextype_classics']),
			array('feeds', $lang['settings_styles_index_indextype_feeds'])
		)), $settings['indextype'], 'mradio');
		showtagfooter('tbody');

		showsetting('settings_styles_index_indexhot_status', 'settingsnew[indexhot][status]', $settings['indexhot']['status'], 'radio', 0, 1);
		showsetting('settings_styles_index_indexhot_limit', 'settingsnew[indexhot][limit]', $settings['indexhot']['limit'], 'text');
		showsetting('settings_styles_index_indexhot_days', 'settingsnew[indexhot][days]', $settings['indexhot']['days'], 'text');
		showsetting('settings_styles_index_indexhot_expiration', 'settingsnew[indexhot][expiration]', $settings['indexhot']['expiration'], 'text');
		showsetting('settings_styles_index_indexhot_messagecut', 'settingsnew[indexhot][messagecut]', $settings['indexhot']['messagecut'], 'text');
		showtagfooter('tbody');
		showsetting('settings_styles_index_subforumsindex', 'settingsnew[subforumsindex]', $settings['subforumsindex'], 'radio');
		showsetting('settings_styles_index_forumlinkstatus', 'settingsnew[forumlinkstatus]', $settings['forumlinkstatus'], 'radio');
		showsetting('settings_styles_index_members', 'settingsnew[maxbdays]', $settings['maxbdays'], 'text');
		showsetting('settings_styles_index_moddisplay', array('settingsnew[moddisplay]', array(
			array('flat', $lang['settings_styles_index_moddisplay_flat']),
			array('selectbox', $lang['settings_styles_index_moddisplay_selectbox'])
		)), $settings['moddisplay'], 'mradio');
		showsetting('settings_styles_index_whosonline', array('settingsnew[whosonlinestatus]', array(
			array(0, $lang['settings_styles_index_display_none']),
			array(1, $lang['settings_styles_index_whosonline_index']),
			array(2, $lang['settings_styles_index_whosonline_forum']),
			array(3, $lang['settings_styles_index_whosonline_both'])
		)), $settings['whosonlinestatus'], 'select');
		showsetting('settings_styles_index_whosonline_contract', 'settingsnew[whosonline_contract]', $settings['whosonline_contract'], 'radio');
		showsetting('settings_styles_index_online_more_members', 'settingsnew[maxonlinelist]', $settings['maxonlinelist'], 'text');
		showsetting('settings_styles_index_hideprivate', 'settingsnew[hideprivate]', $settings['hideprivate'], 'radio');
		showtablefooter();

		showtableheader('', 'nobottom', 'id="forumdisplay"'.($anchor != 'forumdisplay' ? ' style="display: none"' : ''));

		showsetting('settings_styles_forumdisplay_tpp', 'settingsnew[topicperpage]', $settings['topicperpage'], 'text');
		showsetting('settings_styles_forumdisplay_threadmaxpages', 'settingsnew[threadmaxpages]', $settings['threadmaxpages'], 'text');
		showsetting('settings_styles_forumdisplay_globalstick', 'settingsnew[globalstick]', $settings['globalstick'], 'radio');
		showsetting('settings_styles_forumdisplay_stick', 'settingsnew[threadsticky]', $settings['threadsticky'], 'text');
		showsetting('settings_styles_forumdisplay_part', 'settingsnew[forumseparator]', $settings['forumseparator'], 'radio');
		showsetting('settings_styles_forumdisplay_visitedforums', 'settingsnew[visitedforums]', $settings['visitedforums'], 'text');
		showtablefooter();

		showtagheader('div', 'viewthread', $anchor == 'viewthread');
		showtableheader('nav_settings_viewthread', 'nobottom');
		showsetting('settings_styles_viewthread_ppp', 'settingsnew[postperpage]', $settings['postperpage'], 'text');
		showsetting('settings_styles_viewthread_starthreshold', 'settingsnew[starthreshold]', $settings['starthreshold'], 'text');
		showsetting('settings_styles_viewthread_maxsigrows', 'settingsnew[maxsigrows]', $settings['maxsigrows'], 'text');
		showsetting('settings_styles_viewthread_sigviewcond', 'settingsnew[sigviewcond]', $settings['sigviewcond'], 'text');
		showsetting('settings_styles_viewthread_rate_on', 'settingsnew[ratelogon]', $settings['ratelogon'], 'radio');
		showsetting('settings_styles_viewthread_rate_number', 'settingsnew[ratelogrecord]', $settings['ratelogrecord'], 'text');
		showsetting('settings_styles_viewthread_show_signature', 'settingsnew[showsignatures]', $settings['showsignatures'], 'radio');
		showsetting('settings_styles_viewthread_show_face', 'settingsnew[showavatars]', $settings['showavatars'], 'radio');
		showsetting('settings_styles_viewthread_show_images', 'settingsnew[showimages]', $settings['showimages'], 'radio');
		showsetting('settings_styles_viewthread_imagemaxwidth', 'settingsnew[imagemaxwidth]', $settings['imagemaxwidth'], 'text');
		showsetting('settings_styles_viewthread_zoomstatus', 'settingsnew[zoomstatus]', $settings['zoomstatus'], 'radio');
		showsetting('settings_styles_viewthread_fastpost', 'settingsnew[fastpost]', $settings['fastpost'], 'radio');
		showsetting('settings_styles_viewthread_vtonlinestatus', array('settingsnew[vtonlinestatus]', array(
			array(0, $lang['settings_styles_viewthread_display_none']),
			array(1, $lang['settings_styles_viewthread_online_easy']),
			array(2, $lang['settings_styles_viewthread_online_exactitude'])
		)), $settings['vtonlinestatus'], 'select');
		showsetting('settings_styles_viewthread_userstatusby', array('settingsnew[userstatusby]', array(
			array(0, $lang['settings_styles_viewthread_display_none']),
			array(1, $lang['settings_styles_viewthread_userstatusby_usergroup']),
			array(2, $lang['settings_styles_viewthread_userstatusby_rank'])
		)), $settings['userstatusby'], 'select');
		showsetting('settings_styles_viewthread_postno', 'settingsnew[postno]', $settings['postno'], 'text');
		showsetting('settings_styles_viewthread_postnocustom', 'settingsnew[postnocustom]', $settings['postnocustom'], 'textarea');
		showsetting('settings_styles_viewthread_maxsmilies', 'settingsnew[maxsmilies]', $settings['maxsmilies'], 'text');

		showsetting('settings_styles_viewthread_author_onleft', array('settingsnew[authoronleft]', array(
			array(1, lang('settings_styles_viewthread_author_onleft_yes')),
			array(0, lang('settings_styles_viewthread_author_onleft_no')))), $settings['authoronleft'], 'mradio');

		showtableheader('settings_styles_viewthread_customauthorinfo', 'fixpadding');
		$authorinfoitems = array(
			'uid' => 'UID',
			'posts' => $lang['settings_styles_viewthread_userinfo_posts'],
			'threads' => $lang['settings_styles_viewthread_userinfo_threads'],
			'digest' => $lang['settings_styles_viewthread_userinfo_digest'],
			'credits' => $lang['settings_styles_viewthread_userinfo_credits'],
		);
		if(!empty($extcredits)) {
			foreach($extcredits as $key => $value) {
				$authorinfoitems['extcredits'.$key] = $value['title'];
			}
		}
		$query = $db->query("SELECT fieldid,title FROM {$tablepre}profilefields WHERE available='1' AND invisible='0' ORDER BY displayorder");
		while($profilefields = $db->fetch_array($query)) {
			$authorinfoitems['field_'.$profilefields['fieldid']] = $profilefields['title'];
		}
		$authorinfoitems = array_merge($authorinfoitems, array(
			'readperm' => $lang['settings_styles_viewthread_userinfo_readperm'],
			'gender' => $lang['settings_styles_viewthread_userinfo_gender'],
			'location' => $lang['settings_styles_viewthread_userinfo_location'],
			'oltime' => $lang['settings_styles_viewthread_userinfo_oltime'],
			'regtime' => $lang['settings_styles_viewthread_userinfo_regtime'],
			'lastdate' => $lang['settings_styles_viewthread_userinfo_lastdate'],
		));

		showsubtitle(array('', 'settings_styles_viewthread_userinfo_left', 'settings_styles_viewthread_userinfo_menu'));

		$authorinfoitemsetting = '';
		foreach($authorinfoitems as $key => $value) {
			$authorinfoitemsetting .= '<tr><td>'.$value.
				'</td><td><input name="settingsnew[customauthorinfo]['.$key.'][left]" type="checkbox" class="checkbox" value="1" '.($settings['customauthorinfo'][$key]['left'] ? 'checked' : '').'>'.
				'</td><td><input name="settingsnew[customauthorinfo]['.$key.'][menu]" type="checkbox" class="checkbox" value="1" '.($settings['customauthorinfo'][$key]['menu'] ? 'checked' : '').'>'.
				'</td></tr>';
		}

		echo $authorinfoitemsetting;
		showtablefooter();
		showtagfooter('div');

		showtableheader('', 'nobottom', 'id="member"'.($anchor != 'member' ? ' style="display: none"' : ''));
		showsetting('settings_styles_member_mpp', 'settingsnew[memberperpage]', $settings['memberperpage'], 'text');
		showsetting('settings_styles_member_maxpages', 'settingsnew[membermaxpages]', $settings['membermaxpages'], 'text');

		$settings['msgforward'] = !empty($settings['msgforward']) ? unserialize($settings['msgforward']) : array();
		$settings['msgforward']['messages'] = !empty($settings['msgforward']['messages']) ? implode("\n", $settings['msgforward']['messages']) : '';
		showtablefooter();

		showtableheader('', 'nobottom', 'id="refresh"'.($anchor != 'refresh' ? ' style="display: none"' : ''));
		showsetting('settings_styles_refresh_refreshtime', 'settingsnew[msgforward][refreshtime]', $settings['msgforward']['refreshtime'], 'text');
		showsetting('settings_styles_refresh_quick', 'settingsnew[msgforward][quick]', $settings['msgforward']['quick'], 'radio', '', 1);
		showsetting('settings_styles_refresh_messages', 'settingsnew[msgforward][messages]', $settings['msgforward']['messages'], 'textarea');
		showtagfooter('tbody');
		showtablefooter();

		showtableheader('', 'nobottom', 'id="sitemessage"'.($anchor != 'sitemessage' ? ' style="display: none"' : ''));
		showsetting('settings_styles_sitemessage_time', 'settingsnew[sitemessage][time]', $settings['sitemessage']['time'], 'text');
		showsetting('settings_styles_sitemessage_register', 'settingsnew[sitemessage][register]', $settings['sitemessage']['register'], 'textarea');
		showsetting('settings_styles_sitemessage_login', 'settingsnew[sitemessage][login]', $settings['sitemessage']['login'], 'textarea');
		showsetting('settings_styles_sitemessage_newthread', 'settingsnew[sitemessage][newthread]', $settings['sitemessage']['newthread'], 'textarea');
		showsetting('settings_styles_sitemessage_reply', 'settingsnew[sitemessage][reply]', $settings['sitemessage']['reply'], 'textarea');
		showtagfooter('tbody');
		showtablefooter();

		showtableheader('', 'notop');
		showsubmit('settingsubmit');
		showtablefooter();
		showformfooter();
		cpfooter();
		exit;

	} elseif($operation == 'seo') {

		showtips('settings_tips');
		showtableheader();
		showtitle('settings_seo');
		showsetting('settings_seo_archiverstatus', array('settingsnew[archiverstatus]', array(
			array(0, $lang['settings_seo_archiverstatus_none']),
			array(1, $lang['settings_seo_archiverstatus_full']),
			array(2, $lang['settings_seo_archiverstatus_searchengine']),
			array(3, $lang['settings_seo_archiverstatus_browser']))), $settings['archiverstatus'], 'mradio');
		showsetting('settings_seo_rewritestatus', array('settingsnew[rewritestatus]', array(
			$lang['settings_seo_rewritestatus_forumdisplay'],
			$lang['settings_seo_rewritestatus_viewthread'],
			$lang['settings_seo_rewritestatus_space'],
			$lang['settings_seo_rewritestatus_tag'],
			$lang['settings_seo_rewritestatus_archiver'])), $settings['rewritestatus'], 'binmcheckbox');
		showsetting('settings_seo_rewritecompatible', 'settingsnew[rewritecompatible]', $settings['rewritecompatible'], 'radio');
		showsetting('settings_seo_seotitle', 'settingsnew[seotitle]', $settings['seotitle'], 'text');
		showsetting('settings_seo_seokeywords', 'settingsnew[seokeywords]', $settings['seokeywords'], 'text');
		showsetting('settings_seo_seodescription', 'settingsnew[seodescription]', $settings['seodescription'], 'text');
		showsetting('settings_seo_seohead', 'settingsnew[seohead]', $settings['seohead'], 'textarea');

		showtitle('nav_settings_sitemap');
		showsetting('settings_seo_sitemap_baidu_open', 'settingsnew[baidusitemap]', $settings['baidusitemap'], 'radio', '', 1);
		showsetting('settings_seo_sitemap_baidu_expire', 'settingsnew[baidusitemap_life]', $settings['baidusitemap_life'], 'text');
		showtagfooter('tbody');

	} elseif($operation == 'cachethread') {

		include_once DISCUZ_ROOT.'./include/forum.func.php';
		$forumselect = '<select name="fids[]" multiple="multiple" size="10"><option value="all">'.$lang['all'].'</option><option value="">&nbsp;</option>'.forumselect(FALSE, 0, 0, TRUE).'</select>';
		showtableheader();
		showtitle('settings_cachethread');
		showsetting('settings_cachethread_indexlife', 'settingsnew[cacheindexlife]', $settings['cacheindexlife'], 'text');
		showsetting('settings_cachethread_life', 'settingsnew[cachethreadlife]', $settings['cachethreadlife'], 'text');
		showsetting('settings_cachethread_dir', 'settingsnew[cachethreaddir]', $settings['cachethreaddir'], 'text');

		showtitle('settings_cachethread_coefficient_set');
		showsetting('settings_cachethread_coefficient', 'settingsnew[threadcaches]', '', "<input type=\"text\" class=\"txt\" size=\"30\" name=\"settingsnew[threadcaches]\" value=\"\">");
		showsetting('settings_cachethread_coefficient_forum', '', '', $forumselect);

	} elseif($operation == 'serveropti') {

		$checkgzipfunc = !function_exists('ob_gzhandler') ? 1 : 0;
		if($settings['jspath'] == 'include/js/') {
			$tjspath['default'] = 'checked="checked"';
			$settings['jspath'] = '';
		} elseif($settings['jspath'] == 'forumdata/cache/') {
			$tjspath['cache'] =  'checked="checked"';
			$settings['jspath'] = '';
		} else {
			$tjspath['custom'] =  'checked="checked"';
		}

		showtips('settings_tips');
		showtableheader();
		showtitle('settings_serveropti');
		showsetting('settings_serveropti_gzipcompress', 'settingsnew[gzipcompress]', $settings['gzipcompress'], 'radio', $checkgzipfunc);
		showsetting('settings_serveropti_delayviewcount', array('settingsnew[delayviewcount]', array(
			array(0, lang('none')),
			array(1, lang('settings_serveropti_delayviewcount_thread')),
			array(2, lang('settings_serveropti_delayviewcount_attach')),
			array(3, lang('settings_serveropti_delayviewcount_thread_attach'))
		)), $settings['delayviewcount'], 'select');
		showsetting('settings_serveropti_nocacheheaders', 'settingsnew[nocacheheaders]', $settings['nocacheheaders'], 'radio');
		showsetting('settings_serveropti_transsidstatus', 'settingsnew[transsidstatus]', $settings['transsidstatus'], 'radio');
		showsetting('settings_serveropti_maxonlines', 'settingsnew[maxonlines]', $settings['maxonlines'], 'text');
		showsetting('settings_serveropti_onlinehold', 'settingsnew[onlinehold]', $settings['onlinehold'], 'text');
		showsetting('settings_serveropti_loadctrl', 'settingsnew[loadctrl]', $settings['loadctrl'], 'text');
		showsetting('settings_serveropti_floodctrl', 'settingsnew[floodctrl]', $settings['floodctrl'], 'text');
		showsetting('settings_serveropti_jspath', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($tjspath['default'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="settingsnew[jspath]" value="include/js/" '.$tjspath['default'].'> '.$lang['settings_serveropti_jspath_default'].'</li>
			<li'.($tjspath['cache'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="settingsnew[jspath]" value="forumdata/cache/" '.$tjspath['cache'].'> '.$lang['settings_serveropti_jspath_cache'].'</li>
			<li'.($tjspath['custom'] ? ' class="checked"' : '').'><input class="radio" type="radio" name="settingsnew[jspath]" value="" '.$tjspath['custom'].'> '.$lang['settings_serveropti_jspath_custom'].' <input type="text" class="txt" style="width: 100px" name="settingsnew[jspathcustom]" value="'.$settings['jspath'].'" size="6"></li></ul>'
		);

		showtitle('nav_settings_search');
		showsetting('settings_serveropti_searchctrl', 'settingsnew[searchctrl]', $settings['searchctrl'], 'text');
		showsetting('settings_serveropti_maxspm', 'settingsnew[maxspm]', $settings['maxspm'], 'text');
		showsetting('settings_serveropti_maxsearchresults', 'settingsnew[maxsearchresults]', $settings['maxsearchresults'], 'text');

	} elseif($operation == 'editor') {

		$editoroptions = str_pad(decbin($settings['editoroptions']), 2, 0, STR_PAD_LEFT);
		$settings['defaulteditormode'] = $editoroptions{0};
		$settings['allowswitcheditor'] = $editoroptions{1};

		showtableheader();
		showsetting('settings_editor_mode_default', array('settingsnew[defaulteditormode]', array(
			array(0, $lang['settings_editor_mode_discuzcode']),
			array(1, $lang['settings_editor_mode_wysiwyg']))), $settings['defaulteditormode'], 'mradio');
		showsetting('settings_editor_swtich_enable', 'settingsnew[allowswitcheditor]', $settings['allowswitcheditor'], 'radio');
		showsetting('settings_editor_smthumb', 'settingsnew[smthumb]', $settings['smthumb'], 'text');
		showsetting('settings_editor_smcols', 'settingsnew[smcols]', $settings['smcols'], 'text');
		showsetting('settings_editor_smrows', 'settingsnew[smrows]', $settings['smrows'], 'text');
		showtablefooter();

	} elseif($operation == 'functions') {

		showtips('settings_tips', 'stat_tips', $anchor == 'stat');
		showtips('settings_tips', 'mod_tips', $anchor == 'mod');
		showtips('settings_tips', 'other_tips', $anchor == 'other');
		showtips('settings_functions_recommend_tips', 'recommend_tips', $anchor == 'recommend');

		showtableheader('', 'nobottom', 'id="stat"'.($anchor != 'stat' ? ' style="display: none"' : ''));
		showsetting('settings_functions_stat_status', 'settingsnew[statstatus]', $settings['statstatus'], 'radio');
		showsetting('settings_functions_stat_cachelife', 'settingsnew[statscachelife]', $settings['statscachelife'], 'text');
		showsetting('settings_functions_stat_pvfrequence', 'settingsnew[pvfrequence]', $settings['pvfrequence'], 'text');
		showsetting('settings_functions_stat_oltimespan', 'settingsnew[oltimespan]', $settings['oltimespan'], 'text');
		showtablefooter();

		showtableheader('', 'nobottom', 'id="mod"'.($anchor != 'mod' ? ' style="display: none"' : ''));
		showsetting('settings_functions_mod_status', 'settingsnew[modworkstatus]', $settings['modworkstatus'], 'radio');
		showsetting('settings_functions_mod_maxmodworksmonths', 'settingsnew[maxmodworksmonths]', $settings['maxmodworksmonths'], 'text');
		showsetting('settings_functions_mod_losslessdel', 'settingsnew[losslessdel]', $settings['losslessdel'], 'text');
		showsetting('settings_functions_mod_reasons', 'settingsnew[modreasons]', $settings['modreasons'], 'textarea');
		showsetting('settings_functions_mod_bannedmessages', array('settingsnew[bannedmessages]', array(
			$lang['settings_functions_mod_bannedmessages_thread'],
			$lang['settings_functions_mod_bannedmessages_avatar'],
			$lang['settings_functions_mod_bannedmessages_signature'])), $settings['bannedmessages'], 'binmcheckbox');
		showsetting('settings_functions_mod_warninglimit', 'settingsnew[warninglimit]', $settings['warninglimit'], 'text');
		showsetting('settings_functions_mod_warningexpiration', 'settingsnew[warningexpiration]', $settings['warningexpiration'], 'text');
		showtablefooter();

		showtableheader('', 'nobottom', 'id="tags"'.($anchor != 'tags' ? ' style="display: none"' : ''));
		showsetting('settings_functions_tags_status', array('settingsnew[tagstatus]', array(
			array(0, $lang['settings_functions_tags_status_none'], array('tagext' => 'none')),
			array(1, $lang['settings_functions_tags_status_use'], array('tagext' => '')),
			array(2, $lang['settings_functions_tags_status_quired'], array('tagext' => ''))
		)), $settings['tagstatus'], 'mradio');
		showtagheader('tbody', 'tagext', $settings['tagstatus'], 'sub');
		showsetting('settings_functions_tags_viewthtrad_hottags', 'settingsnew[viewthreadtags]', $settings['viewthreadtags'], 'text');
		showtagfooter('tbody');
		showtablefooter();

		$settings['heatthread'] = unserialize($settings['heatthread']);
		$settings['recommendthread'] = unserialize($settings['recommendthread']);
		$recommendcreditstrans = '<select name="settingsnew[recommendthread][creditstrans]"><option value="">'.$lang['none'].'</option>';
		foreach($extcredits as $key => $value) {
			$recommendcreditstrans .= '<option'.($settings['recommendthread']['creditstrans'] == $key ? ' selected="selected"' : '').' value="'.$key.'">extcredits'.$key.' ('.$value['title'].')</option>';
		}
		$recommendcreditstrans .= '</select>';
		$count = count(explode(',', $settings['heatthread']['iconlevels']));
		$heatthreadicons = '';
		for($i = 0;$i < $count;$i++) {
			$heatthreadicons .= '<img src="images/default/hot_'.($i + 1).'.gif" /> ';
		}
		$count = count(explode(',', $settings['recommendthread']['iconlevels']));
		$recommendicons = '';
		for($i = 0;$i < $count;$i++) {
			$recommendicons .= '<img src="images/default/recommend_'.($i + 1).'.gif" /> ';
		}

		showtableheader('', 'nobottom', 'id="heatthread"'.($anchor != 'heatthread' ? ' style="display: none"' : ''));
		showsetting('settings_functions_heatthread_reply', 'settingsnew[heatthread][reply]', $settings['heatthread']['reply'], 'text');
		showsetting('settings_functions_heatthread_recommend', 'settingsnew[heatthread][recommend]', $settings['heatthread']['recommend'], 'text');
		showsetting('settings_functions_heatthread_iconlevels', '', '', '<input name="settingsnew[heatthread][iconlevels]" class="txt" type="text" value="'.$settings['heatthread']['iconlevels'].'" /><br />'.$heatthreadicons);
		showtablefooter();

		showtableheader('', 'nobottom', 'id="recommend"'.($anchor != 'recommend' ? ' style="display: none"' : ''));
		showsetting('settings_functions_recommend_status', 'settingsnew[recommendthread][status]', $settings['recommendthread']['status'], 'radio', 0, 1);
		showsetting('settings_functions_recommend_addtext', 'settingsnew[recommendthread][addtext]', $settings['recommendthread']['addtext'], 'text');
		showsetting('settings_functions_recommend_subtracttext', 'settingsnew[recommendthread][subtracttext]', $settings['recommendthread']['subtracttext'], 'text');
		showsetting('settings_functions_recommend_defaultshow', array('settingsnew[recommendthread][defaultshow]', array(
			array(0, $lang['settings_functions_recommend_defaultshow_0']),
			array(1, $lang['settings_functions_recommend_defaultshow_1']))), $settings['recommendthread']['defaultshow'], 'mradio');
		showsetting('settings_functions_recommend_daycount', 'settingsnew[recommendthread][daycount]', intval($settings['recommendthread']['daycount']), 'text');
		showsetting('settings_functions_recommend_ownthread', 'settingsnew[recommendthread][ownthread]', $settings['recommendthread']['ownthread'], 'radio');
		showsetting('settings_functions_recommend_iconlevels', '', '', '<input name="settingsnew[recommendthread][iconlevels]" class="txt" type="text" value="'.$settings['recommendthread']['iconlevels'].'" /><br />'.$recommendicons);
		showtablefooter();

		showtableheader('', 'nobottom', 'id="other"'.($anchor != 'other' ? ' style="display: none"' : ''));
		showsetting('settings_functions_other_pwdsafety', 'settingsnew[pwdsafety]', $settings['pwdsafety'], 'radio');
		showsetting('settings_functions_other_rssstatus', 'settingsnew[rssstatus]', $settings['rssstatus'], 'radio');
		showsetting('settings_functions_other_rssttl', 'settingsnew[rssttl]', $settings['rssttl'], 'text');
		showsetting('settings_functions_other_send_birthday', 'settingsnew[bdaystatus]', $settings['bdaystatus'], 'radio');
		showsetting('settings_functions_other_debug', 'settingsnew[debug]', $settings['debug'], 'radio');
		showsetting('settings_functions_other_activity_type', 'settingsnew[activitytype]', $settings['activitytype'], 'textarea');
		showtablefooter();

		showtableheader('', 'notop');
		showsubmit('settingsubmit');
		showtablefooter();
		showformfooter();
		cpfooter();
		exit;

	} elseif($operation == 'permissions') {

		include_once DISCUZ_ROOT.'./include/forum.func.php';
		$forumselect = '<select name="settingsnew[allowviewuserthread][fids][]" multiple="multiple" size="10">'.forumselect(FALSE, 0, 0, TRUE).'</select>';
		$settings['allowviewuserthread'] = unserialize($settings['allowviewuserthread']);
		if($settings['allowviewuserthread']['fids']) {
			foreach($settings['allowviewuserthread']['fids'] as $v) {
				$forumselect = str_replace('<option value="'.$v.'">', '<option value="'.$v.'" selected>', $forumselect);
			}
		}

		showtableheader();
		showsetting('settings_permissions_allowviewuserthread', 'settingsnew[allowviewuserthread][allow]', $settings['allowviewuserthread']['allow'], 'radio', 0, 1);
		showsetting('settings_permissions_allowviewuserthread_fids', '', '', $forumselect);
		showtagfooter('tbody');
		showsetting('settings_permissions_memliststatus', 'settingsnew[memliststatus]', $settings['memliststatus'], 'radio');
		showsetting('settings_permissions_reportpost', 'settingsnew[reportpost]', $settings['reportpost'], 'radio');
		showsetting('settings_permissions_minpostsize', 'settingsnew[minpostsize]', $settings['minpostsize'], 'text');
		showsetting('settings_permissions_maxpostsize', 'settingsnew[maxpostsize]', $settings['maxpostsize'], 'text');
		showsetting('settings_permissions_favorite_storage', 'settingsnew[maxfavorites]', $settings['maxfavorites'], 'text');
		showsetting('settings_permissions_maxpolloptions', 'settingsnew[maxpolloptions]', $settings['maxpolloptions'], 'text');
		showsetting('settings_permissions_editby', 'settingsnew[editedby]', $settings['editedby'], 'radio');

		showtitle('nav_settings_rate');
		showsetting('settings_permissions_karmaratelimit', 'settingsnew[karmaratelimit]', $settings['karmaratelimit'], 'text');
		showsetting('settings_permissions_modratelimit', 'settingsnew[modratelimit]', $settings['modratelimit'], 'radio');
		showsetting('settings_permissions_dupkarmarate', 'settingsnew[dupkarmarate]', $settings['dupkarmarate'], 'radio');

	} elseif($operation == 'credits') {

		showtips('settings_credits_tips');

		if(!empty($projectid)) {
			$settings = @array_merge($settings, unserialize($db->result_first("SELECT value FROM {$tablepre}projects WHERE id='$projectid'")));
		}

		$projectselect = "<select name=\"projectid\" onchange=\"window.location='$BASESCRIPT?action=settings&operation=credits&projectid='+this.options[this.options.selectedIndex].value\"><option value=\"0\" selected=\"selected\">".$lang['none']."</option>";
		$query = $db->query("SELECT id, name FROM {$tablepre}projects WHERE type='extcredit'");
		while($project = $db->fetch_array($query)) {
			$projectselect .= "<option value=\"$project[id]\" ".($project['id'] == $projectid ? 'selected="selected"' : NULL).">$project[name]</option>\n";
		}
		$projectselect .= '</select>';

		showtableheader('settings_credits_scheme_title', 'nobottom');
		showsetting('settings_credits_scheme', '', '', $projectselect);
		showtablefooter();
		echo <<<EOT
<script type="text/JavaScript">
	function switchpolicy(obj, col) {
		var status = !obj.checked;
		$("policy" + col).disabled = status;
		var policytable = $("policytable");
		for(var row=2; row<14; row++) {
			if(is_opera) {
				policytable.rows[row].cells[col].firstChild.disabled = true;
			} else {
				policytable.rows[row].cells[col].disabled = status;
			}
		}
	}
</script>
EOT;
		showtableheader('settings_credits_extended', 'fixpadding');
		showsubtitle(array('settings_credits_available', 'credits_id', 'credits_img', 'credits_title', 'credits_unit', 'settings_credits_ratio', 'settings_credits_init', 'credits_inport', 'credits_import'), '');

		$settings['extcredits'] = unserialize($settings['extcredits']);
		$settings['initcredits'] = explode(',', $settings['initcredits']);
		for($i = 1; $i <= 8; $i++) {
			showtablerow('', array('width="40"', 'class="td22"', 'class="td22"', 'class="td28"', 'class="td28"', 'class="td28"', 'class="td28"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"settingsnew[extcredits][$i][available]\" value=\"1\" ".($settings['extcredits'][$i]['available'] ? 'checked' : '')." onclick=\"switchpolicy(this, $i)\">",
				'extcredits'.$i,
				"<input type=\"text\" class=\"txt\" size=\"8\" name=\"settingsnew[extcredits][$i][img]\" value=\"{$settings['extcredits'][$i]['img']}\">",
				"<input type=\"text\" class=\"txt\" size=\"8\" name=\"settingsnew[extcredits][$i][title]\" value=\"{$settings['extcredits'][$i]['title']}\">",
				"<input type=\"text\" class=\"txt\" size=\"5\" name=\"settingsnew[extcredits][$i][unit]\" value=\"{$settings['extcredits'][$i]['unit']}\">",
				"<input type=\"text\" class=\"txt\" size=\"3\" name=\"settingsnew[extcredits][$i][ratio]\" value=\"".(float)$settings['extcredits'][$i]['ratio']."\" onkeyup=\"if(this.value != '0' && \$('allowexchangeout$i').checked == false && \$('allowexchangein$i').checked == false) {\$('allowexchangeout$i').checked = true;\$('allowexchangein$i').checked = true;} else if(this.value == '0') {\$('allowexchangeout$i').checked = false;\$('allowexchangein$i').checked = false;}\">",
				"<input type=\"text\" class=\"txt\" size=\"3\" name=\"settingsnew[initcredits][$i]\" value=\"".intval($settings['initcredits'][$i])."\">",
				"<input class=\"checkbox\" type=\"checkbox\" size=\"3\" name=\"settingsnew[extcredits][$i][allowexchangeout]\" value=\"1\" ".($settings['extcredits'][$i]['allowexchangeout'] ? 'checked' : '')." id=\"allowexchangeout$i\">",
				"<input class=\"checkbox\" type=\"checkbox\" size=\"3\" name=\"settingsnew[extcredits][$i][allowexchangein]\" value=\"1\" ".($settings['extcredits'][$i]['allowexchangein'] ? 'checked' : '')." id=\"allowexchangein$i\">"
			));
		}
		showtablerow('', 'colspan="10" class="lineheight"', $lang['settings_credits_extended_comment']);
		showtablefooter();

		showtableheader('settings_credits_policy', 'fixpadding', 'id="policytable"');
		echo '<tr><th>'.$lang['credits_id'].'</th>';
		$settings['creditspolicy'] = unserialize($settings['creditspolicy']);
		for($i = 1; $i <= 8; $i++) {
			echo "<th id=\"policy$i\" ".($settings['extcredits'][$i]['available'] ? '' : 'disabled')." valign=\"top\"> extcredits$i<br />".($settings['extcredits'][$i]['title'] ? '('.$settings['extcredits'][$i]['title'].')' : '')."</th>";
		}
		echo '</tr>';
		foreach(array('post', 'reply', 'digest', 'postattach', 'getattach', 'sendpm', 'search', 'promotion_visit', 'promotion_register', 'tradefinished', 'votepoll', 'lowerlimit') as $policy) {
			showtablerow('title="'.$lang['settings_credits_policy_'.$policy.'_comment'].'"', array('class="td22"', 'class="td28"',  'class="td28"',  'class="td28"',  'class="td28"',  'class="td28"',  'class="td28"',  'class="td28"',  'class="td28"'), creditsrow($policy));
		}
		showtablerow('', 'class="lineheight" colspan="9"', $lang['settings_credits_policy_comment']);
		showtablefooter();
		showtableheader('settings_credits', 'fixpadding');
		showsetting('settings_credits_formula', 'settingsnew[creditsformula]', $settings['creditsformula'], 'textarea');

		$settings['creditstrans'] = explode(',', $settings['creditstrans']);
		$creditstrans = array();
		for($si = 0; $si < 6; $si++) {
			$creditstrans[$si] = '';
			for($i = 0; $i <= 8; $i++) {
				$creditstrans[$si] .= '<option value="'.$i.'" '.($i == $settings['creditstrans'][$si] ? 'selected' : '').'>'.($i ? 'extcredits'.$i.($settings['extcredits'][$i]['title'] ? '('.$settings['extcredits'][$i]['title'].')' : '') : ($si > 0 ? $lang['settings_credits_trans_used'] : $lang['none'])).'</option>';
			}
		}
		showsetting('settings_credits_trans', '', '', '<select onchange="if(this.value > 0) {$(\'creditstransextra\').style.display = \'\';} else {$(\'creditstransextra\').style.display = \'none\';}" name="settingsnew[creditstrans][0]">'.$creditstrans[0].'</select>');
		showtagheader('tbody', 'creditstransextra', $settings['creditstrans'][0], 'sub');
		showsetting('settings_credits_trans1', '', '' ,'<select name="settingsnew[creditstrans][1]">'.$creditstrans[1].'</select>');
		showsetting('settings_credits_trans2', '', '' ,'<select name="settingsnew[creditstrans][2]">'.$creditstrans[2].'</select>');
		showsetting('settings_credits_trans3', '', '' ,'<select name="settingsnew[creditstrans][3]">'.$creditstrans[3].'</select>');
		showsetting('settings_credits_trans4', '', '' ,'<select name="settingsnew[creditstrans][4]">'.$creditstrans[4].'</select>');
		showsetting('settings_credits_trans5', '', '' ,'<select name="settingsnew[creditstrans][5]"><option value="-1">'.$lang['settings_credits_trans5_none'].'</option>'.$creditstrans[5].'</select>');
		showtagfooter('tbody');
		showsetting('settings_credits_tax', 'settingsnew[creditstax]', $settings['creditstax'], 'text');
		showsetting('settings_credits_mintransfer', 'settingsnew[transfermincredits]', $settings['transfermincredits'], 'text');
		showsetting('settings_credits_minexchange', 'settingsnew[exchangemincredits]', $settings['exchangemincredits'], 'text');
		showsetting('settings_credits_maxincperthread', 'settingsnew[maxincperthread]', $settings['maxincperthread'], 'text');
		showsetting('settings_credits_maxchargespan', 'settingsnew[maxchargespan]', $settings['maxchargespan'], 'text');

		$extbutton = '&nbsp;&nbsp;&nbsp;<input name="projectsave" type="hidden" value="0"><input type="button" class="btn" onclick="$(\'cpform\').projectsave.value=1;$(\'cpform\').settingsubmit.click()" value="'.$lang['saveconf'].'">';

	} elseif($operation == 'mail' && $isfounder) {

		$settings['mail'] = unserialize($settings['mail']);
		$passwordmask = $settings['mail']['auth_password'] ? $settings['mail']['auth_password']{0}.'********'.substr($settings['mail']['auth_password'], -2) : '';

		showtableheader('', '', 'id="mailsettings"'.($anchor != 'settings' ? ' style="display: none"' : ''));
		showsetting('settings_mail_settings_send', array('settingsnew[mail][mailsend]', array(
			array(1, $lang['settings_mail_settings_send_1'], array('hidden1' => 'none', 'hidden2' => 'none')),
			array(2, $lang['settings_mail_settings_send_2'], array('hidden1' => '', 'hidden2' => '')),
			array(3, $lang['settings_mail_settings_send_3'], array('hidden1' => '', 'hidden2' => 'none'))
		)), $settings['mail']['mailsend'], 'mradio');
		showtagheader('tbody', 'hidden1', $settings['mail']['mailsend'] != 1, 'sub');
		showsetting('settings_mail_settings_server', 'settingsnew[mail][server]', $settings['mail']['server'], 'text');
		showsetting('settings_mail_settings_port', 'settingsnew[mail][port]', $settings['mail']['port'], 'text');
		showtagfooter('tbody');
		showtagheader('tbody', 'hidden2', $settings['mail']['mailsend'] == 2, 'sub');
		showsetting('settings_mail_settings_auth', 'settingsnew[mail][auth]', $settings['mail']['auth'], 'radio');
		showsetting('settings_mail_settings_from', 'settingsnew[mail][from]', $settings['mail']['from'], 'text');
		showsetting('settings_mail_settings_username', 'settingsnew[mail][auth_username]', $settings['mail']['auth_username'], 'text');
		showsetting('settings_mail_settings_password', 'settingsnew[mail][auth_password]', $passwordmask, 'text');
		showtagfooter('tbody');
		showsetting('settings_mail_settings_delimiter', array('settingsnew[mail][maildelimiter]', array(
			array(1, $lang['settings_mail_settings_delimiter_crlf']),
			array(0, $lang['settings_mail_settings_delimiter_lf']),
			array(2, $lang['settings_mail_settings_delimiter_cr']))),  $settings['mail']['maildelimiter'], 'mradio');
		showsetting('settings_mail_settings_includeuser', 'settingsnew[mail][mailusername]', $settings['mail']['mailusername'], 'radio');
		showsetting('settings_mail_settings_silent', 'settingsnew[mail][sendmail_silent]', $settings['mail']['sendmail_silent'], 'radio');
		showsubmit('settingsubmit');
		showtablefooter();

		showtableheader('', '', 'id="mailcheck"'.($anchor != 'check' ? ' style="display: none"' : ''));
		showsetting('settings_mail_check_test_from', 'test_from', '', 'text');
		showsetting('settings_mail_check_test_to', 'test_to', '', 'textarea');
		showsubmit('', '', '<input type="submit" class="btn" name="mailcheck" value="'.lang('settings_mail_check_submit').'" onclick="this.form.action=\''.$BASESCRIPT.'?action=checktools&operation=mailcheck&frame=no\';this.form.target=\'mailcheckiframe\'">', '<iframe name="mailcheckiframe" style="display: none"></iframe>');
		showtablefooter();

		showformfooter();
		cpfooter();
		exit;

	} elseif($operation == 'sec') {

		echo '<script type="text/JavaScript">
		function updateseccode(op) {
			if(isUndefined(op)) {
				ajaxget(\'ajax.php?action=updateseccode\', \'seccodeimage\', \'seccodeimage\');
			} else {
				window.document.seccodeplayer.SetVariable("isPlay", "1");
			}
		}
		</script>';

		$checksc = array();
		$settings['seccodedata'] = unserialize($settings['seccodedata']);

		$seccodetypearray = array(
			array(0, lang('settings_sec_seccode_type_image'), array('seccodeimageext' => '', 'seccodeimagewh' => '')),
			array(1, lang('settings_sec_seccode_type_chnfont'), array('seccodeimageext' => '', 'seccodeimagewh' => '')),
			array(2, lang('settings_sec_seccode_type_flash'), array('seccodeimageext' => 'none', 'seccodeimagewh' => '')),
			array(3, lang('settings_sec_seccode_type_wav'), array('seccodeimageext' => 'none', 'seccodeimagewh' => 'none')),
		);

		showtips('settings_sec_code_tips', 'seccode_tips', $anchor == 'seccode');
		showtips('settings_sec_qaa_tips', 'secqaa_tips', $anchor == 'secqaa');
		showtableheader('', '', 'id="seclevel"'.($anchor != 'seclevel' ? ' style="display: none"' : ''));
		showsetting('settings_sec_seclevel', array('settingsnew[seclevel]', array(
			array(0, $lang['settings_sec_seclevel_lower']),
			array(1, $lang['settings_sec_seclevel_higher'])
		)), $settings['seclevel'], 'mradio');
		showsubmit('settingsubmit');
		showtablefooter();

		showtableheader('', '', 'id="seccode"'.($anchor != 'seccode' ? ' style="display: none"' : ''));
		showsetting('settings_sec_seccode_status', array('settingsnew[seccodestatus]', array(
			lang('settings_sec_seccode_status_register'),
			lang('settings_sec_seccode_status_login'),
			lang('settings_sec_seccode_status_post'),
			lang('settings_sec_seccode_status_profile')
		)), $settings['seccodestatus'], 'binmcheckbox');
		showsetting('settings_sec_seccode_minposts', 'settingsnew[seccodedata][minposts]', $settings['seccodedata']['minposts'], 'text');
		showsetting('settings_sec_seccode_loginfailedcount', 'settingsnew[seccodedata][loginfailedcount]', $settings['seccodedata']['loginfailedcount'], 'radio');
		showsetting('settings_sec_seccode_type', array('settingsnew[seccodedata][type]', $seccodetypearray), $settings['seccodedata']['type'], 'mradio');
		showtagheader('tbody', 'seccodeimagewh', $settings['seccodedata']['type'] != 3, 'sub');
		showsetting('settings_sec_seccode_width', 'settingsnew[seccodedata][width]', $settings['seccodedata']['width'], 'text');
		showsetting('settings_sec_seccode_height', 'settingsnew[seccodedata][height]', $settings['seccodedata']['height'], 'text');
		showtagfooter('tbody');
		showtagheader('tbody', 'seccodeimageext', $settings['seccodedata']['type'] != 2 && $settings['seccodedata']['type'] != 3, 'sub');
		showsetting('settings_sec_seccode_background', 'settingsnew[seccodedata][background]', $settings['seccodedata']['background'], 'radio');
		showsetting('settings_sec_seccode_adulterate', 'settingsnew[seccodedata][adulterate]', $settings['seccodedata']['adulterate'], 'radio');
		showsetting('settings_sec_seccode_ttf', 'settingsnew[seccodedata][ttf]', $settings['seccodedata']['ttf'], 'radio', !function_exists('imagettftext'));
		showsetting('settings_sec_seccode_angle', 'settingsnew[seccodedata][angle]', $settings['seccodedata']['angle'], 'radio');
		showsetting('settings_sec_seccode_color', 'settingsnew[seccodedata][color]', $settings['seccodedata']['color'], 'radio');
		showsetting('settings_sec_seccode_size', 'settingsnew[seccodedata][size]', $settings['seccodedata']['size'], 'radio');
		showsetting('settings_sec_seccode_shadow', 'settingsnew[seccodedata][shadow]', $settings['seccodedata']['shadow'], 'radio');
		showsetting('settings_sec_seccode_animator', 'settingsnew[seccodedata][animator]', $settings['seccodedata']['animator'], 'radio', !function_exists('imagegif'));
		showtagfooter('tbody');
		showsubmit('settingsubmit');
		showtablefooter();
		echo '<script language="JavaScript">updateseccode()</script>';

		$settings['secqaa'] = unserialize($settings['secqaa']);
		$page = max(1, intval($page));
		$start_limit = ($page - 1) * 10;
		$secqaanums = $db->result_first("SELECT COUNT(*) FROM {$tablepre}itempool");
		$multipage = multi($secqaanums, 10, $page, $BASESCRIPT.'?action=settings&operation=sec&anchor=secqaa');

		$query = $db->query("SELECT * FROM {$tablepre}itempool LIMIT $start_limit, 10");

		echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[[1,''], [1,'<input name="newquestion[]" type="text" class="txt">','td26'], [1, '<input name="newanswer[]" type="text" class="txt">']],
	];
	</script>
EOT;
		showtagheader('div', 'secqaa', $anchor == 'secqaa');
		showtableheader('settings_sec_secqaa', 'nobottom');
		showsetting('settings_sec_secqaa_status', array('settingsnew[secqaa][status]', array(
			lang('settings_sec_seccode_status_register'),
			lang('settings_sec_seccode_status_post')
		)), $settings['secqaa']['status'], 'binmcheckbox');
		showsetting('settings_sec_secqaa_minposts', 'settingsnew[secqaa][minposts]', $settings['secqaa']['minposts'], 'text');
		showtablefooter();

		showtableheader('settings_sec_secqaa_qaa', 'noborder fixpadding');
		showsubtitle(array('', 'settings_sec_secqaa_question', 'settings_sec_secqaa_answer'));

		while($item = $db->fetch_array($query)) {
			showtablerow('', array('', 'class="td26"'), array(
				'<input class="checkbox" type="checkbox" name="delete[]" value="'.$item['id'].'">',
				'<input type="text" class="txt" name="question['.$item['id'].']" value="'.dhtmlspecialchars($item['question']).'" class="txtnobd" onblur="this.className=\'txtnobd\'" onfocus="this.className=\'txt\'">',
				'<input type="text" class="txt" name="answer['.$item['id'].']" value="'.$item['answer'].'" class="txtnobd" onblur="this.className=\'txtnobd\'" onfocus="this.className=\'txt\'">'
			));
		}

		echo '<tr><td></td><td class="td26"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['settings_sec_secqaa_add'].'</a></div></td><td></td></tr>';
		showsubmit('settingsubmit', 'submit', 'del', '', $multipage);
		showtablefooter();
		showtagfooter('div');

		showformfooter();
		cpfooter();
		exit;


	} elseif($operation == 'datetime') {

		$checktimeformat = array($settings['timeformat'] == 'H:i' ? 24 : 12 => 'checked');

		$settings['userdateformat'] = dateformat($settings['userdateformat']);
		$settings['dateformat'] = dateformat($settings['dateformat']);

		showtableheader();
		showtitle('settings_datetime_format');
		showsetting('settings_datetime_dateformat', 'settingsnew[dateformat]', $settings['dateformat'], 'text');
		showsetting('settings_datetime_timeformat', '', '', '<input class="radio" type="radio" name="settingsnew[timeformat]" value="24" '.$checktimeformat[24].'> 24 '.$lang['hour'].' <input class="radio" type="radio" name="settingsnew[timeformat]" value="12" '.$checktimeformat[12].'> 12 '.$lang['hour'].'');
		showsetting('settings_datetime_dateconvert', 'settingsnew[dateconvert]', $settings['dateconvert'], 'radio');
		showsetting('settings_datetime_timeoffset', 'settingsnew[timeoffset]', $settings['timeoffset'], 'text');
		showsetting('settings_datetime_customformat', 'settingsnew[userdateformat]', $settings['userdateformat'], 'textarea');

		showtitle('settings_datetime_periods');
		showsetting('settings_datetime_visitbanperiods', 'settingsnew[visitbanperiods]', $settings['visitbanperiods'], 'textarea');
		showsetting('settings_datetime_postbanperiods', 'settingsnew[postbanperiods]', $settings['postbanperiods'], 'textarea');
		showsetting('settings_datetime_postmodperiods', 'settingsnew[postmodperiods]', $settings['postmodperiods'], 'textarea');
		showsetting('settings_datetime_ban_downtime', 'settingsnew[attachbanperiods]', $settings['attachbanperiods'], 'textarea');
		showsetting('settings_datetime_searchbanperiods', 'settingsnew[searchbanperiods]', $settings['searchbanperiods'], 'textarea');

	} elseif($operation == 'attach') {

		$checkwm = array($settings['watermarkstatus'] => 'checked');
		$checkmkdirfunc = !function_exists('mkdir') ? 'disabled' : '';
		$settings['watermarktext'] = unserialize($settings['watermarktext']);
		$settings['watermarktext']['fontpath'] = str_replace(array('ch/', 'en/'), '', $settings['watermarktext']['fontpath']);

		$fontlist = '<select name="settingsnew[watermarktext][fontpath]">';
		$dir = opendir(DISCUZ_ROOT.'./images/fonts/en');
		while($entry = readdir($dir)) {
			if(in_array(strtolower(fileext($entry)), array('ttf', 'ttc'))) {
				$fontlist .= '<option value="'.$entry.'"'.($entry == $settings['watermarktext']['fontpath'] ? ' selected>' : '>').$entry.'</option>';
			}
		}
		$dir = opendir(DISCUZ_ROOT.'./images/fonts/ch');
		while($entry = readdir($dir)) {
			if(in_array(strtolower(fileext($entry)), array('ttf', 'ttc'))) {
				$fontlist .= '<option value="'.$entry.'"'.($entry == $settings['watermarktext']['fontpath'] ? ' selected>' : '>').$entry.'</option>';
			}
		}
		$fontlist .= '</select>';

		showtableheader('', '', 'id="basic"'.($anchor != 'basic' ? ' style="display: none"' : ''));
		showsetting('settings_attach_basic_dir', 'settingsnew[attachdir]', $settings['attachdir'], 'text');
		showsetting('settings_attach_basic_url', 'settingsnew[attachurl]', $settings['attachurl'], 'text');
		showsetting('settings_attach_basic_imgpost', 'settingsnew[attachimgpost]', $settings['attachimgpost'], 'radio');
		showsetting('settings_attach_basic_save', array('settingsnew[attachsave]', array(
			array(0, $lang['settings_attach_basic_save_default']),
			array(1, $lang['settings_attach_basic_save_forum']),
			array(2, $lang['settings_attach_basic_save_type']),
			array(3, $lang['settings_attach_basic_save_month']),
			array(4, $lang['settings_attach_basic_save_day'])
		)), $settings['attachsave'], 'select', $checkmkdirfunc);
		$settings['swfupload'] = $settings['swfupload'] == 2 ? array(0, 1) : array($settings['swfupload']);
		showsetting('settings_attach_basic_swfupload', array('settingsnew[swfupload]', array(array(0, $lang['settings_attach_basic_simple']), array(1, $lang['settings_attach_basic_multi']))), $settings['swfupload'], 'mcheckbox');
		showsetting('settings_attach_basic_allowattachurl', 'settingsnew[allowattachurl]', $settings['allowattachurl'], 'radio');
		showsubmit('settingsubmit');
		showtablefooter();

		showtableheader('', '', 'id="image"'.($anchor != 'image' ? ' style="display: none"' : ''));
		showsetting('settings_attach_image_lib', array('settingsnew[imagelib]', array(
			array(0, $lang['settings_attach_image_watermarktype_GD'], array('imagelibext' => 'none')),
			array(1, $lang['settings_attach_image_watermarktype_IM'], array('imagelibext' => ''))
		)), $settings['imagelib'], 'mradio');
		showtagheader('tbody', 'imagelibext', $settings['imagelib'], 'sub');
		showsetting('settings_attach_image_impath', 'settingsnew[imageimpath]', $settings['imageimpath'], 'text');
		showtagfooter('tbody');
		showsetting('settings_attach_image_thumbstatus', array('settingsnew[thumbstatus]', array(
			array(0, $lang['settings_attach_image_thumbstatus_none'], array('thumbext' => 'none')),
			array(1, $lang['settings_attach_image_thumbstatus_add'], array('thumbext' => '')),
			array(3, $lang['settings_attach_image_thumbstatus_addfix'], array('thumbext' => '')),
			array(2, $lang['settings_attach_image_thumbstatus_replace'], array('thumbext' => ''))
		)), $settings['thumbstatus'], 'mradio');
		showtagheader('tbody', 'thumbext', $settings['thumbstatus'], 'sub');
		showsetting('settings_attach_image_thumbquality', 'settingsnew[thumbquality]', $settings['thumbquality'], 'text');
		showsetting('settings_attach_image_thumbwidthheight', array('settingsnew[thumbwidth]', 'settingsnew[thumbheight]'), array(intval($settings['thumbwidth']), intval($settings['thumbheight'])), 'multiply');
		showtagfooter('tbody');
		showsetting('settings_attach_image_watermarkstatus', '', '', '<table cellspacing="'.INNERBORDERWIDTH.'" cellpadding="'.TABLESPACE.'" style="margin-bottom: 3px; margin-top:3px;"><tr><td colspan="3"><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="0" '.$checkwm[0].'>'.$lang['settings_attach_image_watermarkstatus_none'].'</td></tr><tr><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="1" '.$checkwm[1].'> #1</td><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="2" '.$checkwm[2].'> #2</td><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="3" '.$checkwm[3].'> #3</td></tr><tr><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="4" '.$checkwm[4].'> #4</td><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="5" '.$checkwm[5].'> #5</td><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="6" '.$checkwm[6].'> #6</td></tr><tr><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="7" '.$checkwm[7].'> #7</td><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="8" '.$checkwm[8].'> #8</td><td><input class="radio" type="radio" name="settingsnew[watermarkstatus]" value="9" '.$checkwm[9].'> #9</td></tr></table>');
		showsetting('settings_attach_image_watermarkminwidthheight', array('settingsnew[watermarkminwidth]', 'settingsnew[watermarkminheight]'), array(intval($settings['watermarkminwidth']), intval($settings['watermarkminheight'])), 'multiply');
		showsetting('settings_attach_image_watermarktype', array('settingsnew[watermarktype]', array(
			array(0, $lang['settings_attach_image_watermarktype_gif'], array('watermarktypeext' => 'none')),
			array(1, $lang['settings_attach_image_watermarktype_png'], array('watermarktypeext' => 'none')),
			array(2, $lang['settings_attach_image_watermarktype_text'], array('watermarktypeext' => ''))
		)), $settings['watermarktype'], 'mradio');
		showsetting('settings_attach_image_watermarktrans', 'settingsnew[watermarktrans]', $settings['watermarktrans'], 'text');
		showsetting('settings_attach_image_watermarkquality', 'settingsnew[watermarkquality]', $settings['watermarkquality'], 'text');
		showtagheader('tbody', 'watermarktypeext', $settings['watermarktype'] == 2, 'sub');
		showsetting('settings_attach_image_watermarktext_text', 'settingsnew[watermarktext][text]', $settings['watermarktext']['text'], 'textarea');
		showsetting('settings_attach_image_watermarktext_fontpath', '', '', $fontlist);
		showsetting('settings_attach_image_watermarktext_size', 'settingsnew[watermarktext][size]', $settings['watermarktext']['size'], 'text');
		showsetting('settings_attach_image_watermarktext_angle', 'settingsnew[watermarktext][angle]', $settings['watermarktext']['angle'], 'text');
		showsetting('settings_attach_image_watermarktext_color', 'settingsnew[watermarktext][color]', $settings['watermarktext']['color'], 'color');
		showsetting('settings_attach_image_watermarktext_shadowx', 'settingsnew[watermarktext][shadowx]', $settings['watermarktext']['shadowx'], 'text');
		showsetting('settings_attach_image_watermarktext_shadowy', 'settingsnew[watermarktext][shadowy]', $settings['watermarktext']['shadowy'], 'text');
		showsetting('settings_attach_image_watermarktext_shadowcolor', 'settingsnew[watermarktext][shadowcolor]', $settings['watermarktext']['shadowcolor'], 'color');
		showsetting('settings_attach_image_watermarktext_imtranslatex', 'settingsnew[watermarktext][translatex]', $settings['watermarktext']['translatex'], 'text');
		showsetting('settings_attach_image_watermarktext_imtranslatey', 'settingsnew[watermarktext][translatey]', $settings['watermarktext']['translatey'], 'text');
		showsetting('settings_attach_image_watermarktext_imskewx', 'settingsnew[watermarktext][skewx]', $settings['watermarktext']['skewx'], 'text');
		showsetting('settings_attach_image_watermarktext_imskewy', 'settingsnew[watermarktext][skewy]', $settings['watermarktext']['skewy'], 'text');
		showtagfooter('tbody');
		showsubmit('settingsubmit');
		showtablefooter();

		if($isfounder) {
			$settings['ftp'] = unserialize($settings['ftp']);
			$settings['ftp'] = is_array($settings['ftp']) ? $settings['ftp'] : array();
			$settings['ftp']['password'] = authcode($settings['ftp']['password'], 'DECODE', md5($authkey));
			$settings['ftp']['password'] = $settings['ftp']['password'] ? $settings['ftp']['password']{0}.'********'.$settings['ftp']['password']{strlen($settings['ftp']['password']) - 1} : '';

			showtableheader('', '', 'id="remote"'.($anchor != 'remote' ? ' style="display: none"' : ''));
			showsetting('settings_attach_remote_enabled', array('settingsnew[ftp][on]', array(
				array(1, $lang['yes'], array('ftpext' => '', 'ftpcheckbutton' => '')),
				array(0, $lang['no'], array('ftpext' => 'none', 'ftpcheckbutton' => 'none'))
			), TRUE), $settings['ftp']['on'], 'mradio');
			showtagheader('tbody', 'ftpext', $settings['ftp']['on'], 'sub');
			showsetting('settings_attach_remote_enabled_ssl', 'settingsnew[ftp][ssl]', $settings['ftp']['ssl'], 'radio');
			showsetting('settings_attach_remote_ftp_host', 'settingsnew[ftp][host]', $settings['ftp']['host'], 'text');
			showsetting('settings_attach_remote_ftp_port', 'settingsnew[ftp][port]', $settings['ftp']['port'], 'text');
			showsetting('settings_attach_remote_ftp_user', 'settingsnew[ftp][username]', $settings['ftp']['username'], 'text');
			showsetting('settings_attach_remote_ftp_pass', 'settingsnew[ftp][password]', $settings['ftp']['password'], 'text');
			showsetting('settings_attach_remote_ftp_pasv', 'settingsnew[ftp][pasv]', $settings['ftp']['pasv'], 'radio');
			showsetting('settings_attach_remote_dir', 'settingsnew[ftp][attachdir]', $settings['ftp']['attachdir'], 'text');
			showsetting('settings_attach_remote_url', 'settingsnew[ftp][attachurl]', $settings['ftp']['attachurl'], 'text');
			showsetting('settings_attach_remote_timeout', 'settingsnew[ftp][timeout]', $settings['ftp']['timeout'], 'text');
			showsetting('settings_attach_remote_mirror', array('settingsnew[ftp][mirror]', array(
				array(1, lang('settings_attach_remote_mirror_1')),
				//array(2, lang('settings_attach_remote_mirror_2')),
				array(0, lang('settings_attach_remote_mirror_0'))
			)), intval($settings['ftp']['mirror']), 'mradio');
			showsetting('settings_attach_remote_allowedexts', 'settingsnew[ftp][allowedexts]', $settings['ftp']['allowedexts'], 'textarea');
			showsetting('settings_attach_remote_disallowedexts', 'settingsnew[ftp][disallowedexts]', $settings['ftp']['disallowedexts'], 'textarea');
			showsetting('settings_attach_remote_minsize', 'settingsnew[ftp][minsize]', $settings['ftp']['minsize'], 'text');
			showtagfooter('tbody');
			showsubmit('settingsubmit', 'submit', '', '<span id="ftpcheckbutton" style="display: '.($settings['ftp']['on'] ? '' : 'none').'"><input type="submit" class="btn" name="ftpcheck" value="'.$lang['settings_attach_remote_ftpcheck'].'" onclick="this.form.action=\''.$BASESCRIPT.'?action=checktools&operation=ftpcheck&frame=no\';this.form.target=\'ftpcheckiframe\';"></span><iframe name="ftpcheckiframe" style="display: none"></iframe>');
			showtablefooter();
		}

		showtableheader('', '', 'id="antileech"'.($anchor != 'antileech' ? ' style="display: none"' : ''));
		showsetting('settings_attach_antileech_expire', 'settingsnew[attachexpire]', $settings['attachexpire'], 'text');
		showsetting('settings_attach_antileech_refcheck', 'settingsnew[attachrefcheck]', $settings['attachrefcheck'], 'radio');
		showsetting('settings_attach_antileech_remote_hide_dir', 'settingsnew[ftp][hideurl]', $settings['ftp']['hideurl'], 'radio');
		showsubmit('settingsubmit');
		showtablefooter();

		showformfooter();
		cpfooter();
		exit;

	} elseif($operation == 'wap') {

		$settings['wapdateformat'] = dateformat($settings['wapdateformat']);

		showtableheader();
		showsetting('settings_wap_status', 'settingsnew[wapstatus]', $settings['wapstatus'], 'radio', '', 1);
		showsetting('settings_wap_register', 'settingsnew[wapregister]', $settings['wapregister'], 'radio');
		showsetting('settings_wap_charset', array('settingsnew[wapcharset]', array(
			array(1, 'UTF-8'),
			array(2, 'UNICODE'))), $settings['wapcharset'], 'mradio');
		showsetting('settings_wap_tpp', 'settingsnew[waptpp]', $settings['waptpp'], 'text');
		showsetting('settings_wap_ppp', 'settingsnew[wapppp]', $settings['wapppp'], 'text');
		showsetting('settings_wap_dateformat', 'settingsnew[wapdateformat]', $settings['wapdateformat'], 'text');
		showsetting('settings_wap_mps', 'settingsnew[wapmps]', $settings['wapmps'], 'text');
		showtagfooter('tbody');

	} elseif($operation == 'dzfeed') {

		$usergroups_member = array();
		$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups WHERE type='member'");
		while($row = $db->fetch_array($query)) {
			$usergroups_member[] = array($row['groupid'], $row['grouptitle']);
		}

		$settings['dzfeed_limit'] = unserialize($settings['dzfeed_limit']);
		foreach(array('thread_replies', 'thread_views', 'thread_rate', 'post_rate', 'user_credit', 'user_threads', 'user_posts', 'user_digest') as $v) {
			$settings['dzfeed_limit'][$v] = isset($settings['dzfeed_limit'][$v]) && is_array($settings['dzfeed_limit'][$v]) ? implode(', ', $settings['dzfeed_limit'][$v]) : 0;
		}
		showtableheader();
		showsetting('settings_dzfeed_thread_replies', 'settingsnew[dzfeed_limit][thread_replies]', $settings['dzfeed_limit']['thread_replies'], 'text');
		showsetting('settings_dzfeed_thread_views', 'settingsnew[dzfeed_limit][thread_views]', $settings['dzfeed_limit']['thread_views'], 'text');
		showsetting('settings_dzfeed_thread_rate', 'settingsnew[dzfeed_limit][thread_rate]', $settings['dzfeed_limit']['thread_rate'], 'text');
		showsetting('settings_dzfeed_post_rate', 'settingsnew[dzfeed_limit][post_rate]', $settings['dzfeed_limit']['post_rate'], 'text');
		showsetting('settings_dzfeed_user_usergroup', array('settingsnew[dzfeed_limit][user_usergroup]', $usergroups_member), $settings['dzfeed_limit']['user_usergroup'], 'mcheckbox');
		showsetting('settings_dzfeed_user_credit', 'settingsnew[dzfeed_limit][user_credit]', $settings['dzfeed_limit']['user_credit'], 'text');
		showsetting('settings_dzfeed_user_threads', 'settingsnew[dzfeed_limit][user_threads]', $settings['dzfeed_limit']['user_threads'], 'text');
		showsetting('settings_dzfeed_user_posts', 'settingsnew[dzfeed_limit][user_posts]', $settings['dzfeed_limit']['user_posts'], 'text');
		showsetting('settings_dzfeed_user_digest', 'settingsnew[dzfeed_limit][user_digest]', $settings['dzfeed_limit']['user_digest'], 'text');

	} elseif($operation == 'uc' && $isfounder) {

		$disable = !is_writeable('./config.inc.php');

		showtips('settings_uc_tips');
		showtableheader();
		showsetting('settings_uc_appid', 'settingsnew[uc][appid]', UC_APPID, 'text', $disable);
		showsetting('settings_uc_key', 'settingsnew[uc][key]', UC_KEY, 'text', $disable);
		showsetting('settings_uc_api', 'settingsnew[uc][api]', UC_API, 'text', $disable);
		showsetting('settings_uc_ip', 'settingsnew[uc][ip]', UC_IP, 'text', $disable);
		showsetting('settings_uc_connect', array('settingsnew[uc][connect]', array(
			array('mysql', $lang['settings_uc_connect_mysql'], array('ucmysql' => '')),
			array('', $lang['settings_uc_connect_api'], array('ucmysql' => 'none')))), UC_CONNECT, 'mradio', $disable);
		list($ucdbname, $uctablepre) = explode('.', str_replace('`', '', UC_DBTABLEPRE));
		showtagheader('tbody', 'ucmysql', UC_CONNECT, 'sub');
		showsetting('settings_uc_dbhost', 'settingsnew[uc][dbhost]', UC_DBHOST, 'text', $disable);
		showsetting('settings_uc_dbuser', 'settingsnew[uc][dbuser]', UC_DBUSER, 'text', $disable);
		showsetting('settings_uc_dbpass', 'settingsnew[uc][dbpass]', '********', 'text', $disable);
		showsetting('settings_uc_dbname', 'settingsnew[uc][dbname]', $ucdbname, 'text', $disable);
		showsetting('settings_uc_dbtablepre', 'settingsnew[uc][dbtablepre]', $uctablepre, 'text', $disable);
		showtagfooter('tbody');
		showsetting('settings_uc_activation', 'settingsnew[ucactivation]', $settings['ucactivation'], 'radio');
		showsetting('settings_uc_avatarmethod', array('settingsnew[avatarmethod]', array(
			array(0, $lang['settings_uc_avatarmethod_0']),
			array(1, $lang['settings_uc_avatarmethod_1']),
			)), $settings['avatarmethod'], 'mradio');

	} elseif($operation == 'ec') {

		$settings['tradetypes'] = unserialize($settings['tradetypes']);

		$query = $db->query("SELECT * FROM {$tablepre}threadtypes WHERE special='1' ORDER BY displayorder");
		$tradetypeselect = '<select name="settingsnew[tradetypes][]" size="10" multiple="multiple">';
		while($type = $db->fetch_array($query)) {
			$checked = @in_array($type['typeid'], $settings['tradetypes']);
			$tradetypeselect .= '<option value="'.$type['typeid'].'"'.($checked ? ' selected="selected"' : '').'>'.$type['name'].'</option>';
		}
		$tradetypeselect .= '</select>';

		showtableheader();
		showtitle('settings_ec_credittrade');
		showsetting('settings_ec_ratio', 'settingsnew[ec_ratio]', $settings['ec_ratio'], 'text');
		showsetting('settings_ec_mincredits', 'settingsnew[ec_mincredits]', $settings['ec_mincredits'], 'text');
		showsetting('settings_ec_maxcredits', 'settingsnew[ec_maxcredits]', $settings['ec_maxcredits'], 'text');
		showsetting('settings_ec_maxcreditspermonth', 'settingsnew[ec_maxcreditspermonth]', $settings['ec_maxcreditspermonth'], 'text');

		showtitle('settings_ec_goodstrade');
		showsetting('settings_ec_type', '', '', $tradetypeselect);

	} elseif($operation == 'msn') {

		$settings['msn'] = unserialize($settings['msn']);

		showtips('settings_msn_tips');
		showtableheader();
		showtitle('settings_msn_basic');
		showsetting('settings_msn_on', 'settingsnew[msn][on]', $settings['msn']['on'], 'radio');
		showsetting('settings_msn_domain', 'msndomain', $settings['msn']['domain'], 'text');

		showsubmit('settingsubmit', 'submit', '', '<input type="button" class="btn" name="msncheck" value="'.$lang['settings_msn_domain_check'].'" onclick="window.open(\'https://domains.live.com/members/signup.aspx?domain=\'+this.form.msndomain.value)" />');
		showtablefooter();
		exit;

	} else {

		cpmsg('undefined_action');

	}

	showsubmit('settingsubmit', 'submit', '', $extbutton.(!empty($from) ? '<input type="hidden" name="from" value="'.$from.'">' : ''));
	showtablefooter();
	showformfooter();

} else {

	if($operation == 'credits') {
		$extcredits_exists = 0;
		foreach($settingsnew['extcredits'] as $val) {
			if(isset($val['available']) && $val['available'] == 1) {
				$extcredits_exists = 1;
				break;
			}
		}
		if(!$extcredits_exists) {
			cpmsg('settings_extcredits_must_available');
		}
	}

	if($operation == 'uc' && is_writeable('./config.inc.php') && $isfounder) {
		$ucdbpassnew = $settingsnew['uc']['dbpass'] == '********' ? UC_DBPW : $settingsnew['uc']['dbpass'];
		if($settingsnew['uc']['connect']) {
			$uc_dblink = @mysql_connect($settingsnew['uc']['dbhost'], $settingsnew['uc']['dbuser'], $ucdbpassnew, 1);
			if(!$uc_dblink) {
				cpmsg('uc_database_connect_error', '', 'error');
			} else {
				mysql_close($uc_dblink);
			}
		}

		$fp = fopen('./config.inc.php', 'r');
		$configfile = fread($fp, filesize('./config.inc.php'));
		$configfile = trim($configfile);
		$configfile = substr($configfile, -2) == '?>' ? substr($configfile, 0, -2) : $configfile;
		fclose($fp);

		$connect = '';
		if($settingsnew['uc']['connect']) {
			require './config.inc.php';
			$connect = 'mysql';
			$samelink = ($dbhost == $settingsnew['uc']['dbhost'] && $dbuser == $settingsnew['uc']['dbuser'] && $dbpw == $ucdbpassnew);
			$samecharset = !($dbcharset == 'gbk' && UC_DBCHARSET == 'latin1' || $dbcharset == 'latin1' && UC_DBCHARSET == 'gbk');
			$configfile = insertconfig($configfile, "/define\('UC_DBHOST',\s*'.*?'\);/i", "define('UC_DBHOST', '".$settingsnew['uc']['dbhost']."');");
			$configfile = insertconfig($configfile, "/define\('UC_DBUSER',\s*'.*?'\);/i", "define('UC_DBUSER', '".$settingsnew['uc']['dbuser']."');");
			$configfile = insertconfig($configfile, "/define\('UC_DBPW',\s*'.*?'\);/i", "define('UC_DBPW', '".$ucdbpassnew."');");
			$configfile = insertconfig($configfile, "/define\('UC_DBNAME',\s*'.*?'\);/i", "define('UC_DBNAME', '".$settingsnew['uc']['dbname']."');");
			$configfile = insertconfig($configfile, "/define\('UC_DBTABLEPRE',\s*'.*?'\);/i", "define('UC_DBTABLEPRE', '`".$settingsnew['uc']['dbname'].'`.'.$settingsnew['uc']['dbtablepre']."');");
			//$configfile = insertconfig($configfile, "/define\('UC_LINK',\s*'?.*?'?\);/i", "define('UC_LINK', ".($samelink && $samecharset ? 'TRUE' : 'FALSE').");");
		}
		$configfile = insertconfig($configfile, "/define\('UC_CONNECT',\s*'.*?'\);/i", "define('UC_CONNECT', '$connect');");
		$configfile = insertconfig($configfile, "/define\('UC_KEY',\s*'.*?'\);/i", "define('UC_KEY', '".$settingsnew['uc']['key']."');");
		$configfile = insertconfig($configfile, "/define\('UC_API',\s*'.*?'\);/i", "define('UC_API', '".$settingsnew['uc']['api']."');");
		$configfile = insertconfig($configfile, "/define\('UC_IP',\s*'.*?'\);/i", "define('UC_IP', '".$settingsnew['uc']['ip']."');");
		$configfile = insertconfig($configfile, "/define\('UC_APPID',\s*'?.*?'?\);/i", "define('UC_APPID', '".$settingsnew['uc']['appid']."');");

		$fp = fopen('./config.inc.php', 'w');
		if(!($fp = @fopen('./config.inc.php', 'w'))) {
			cpmsg('uc_config_write_error', '', 'error');
		}
		@fwrite($fp, trim($configfile));
		@fclose($fp);
	}

	$nohtmlarray = array('bbname', 'regname', 'reglinkname', 'icp', 'sitemessage');
	foreach($nohtmlarray as $k) {
		if(isset($settingsnew[$k])) {
			$settingsnew[$k] = dhtmlspecialchars($settingsnew[$k]);
		}
	}

	if(isset($settingsnew['censoruser'])) {
		$settingsnew['censoruser'] = trim(preg_replace("/\s*(\r\n|\n\r|\n|\r)\s*/", "\r\n", $settingsnew['censoruser']));
	}

	if(isset($settingsnew['ipregctrl'])) {
		$settingsnew['ipregctrl'] = trim(preg_replace("/\s*(\r\n|\n\r|\n|\r)\s*/", "\r\n", $settingsnew['ipregctrl']));
	}

	if(isset($settingsnew['ipaccess'])) {
		if($settingsnew['ipaccess'] = trim(preg_replace("/(\s*(\r\n|\n\r|\n|\r)\s*)/", "\r\n", $settingsnew['ipaccess']))) {
			if(!ipaccess($onlineip, $settingsnew['ipaccess'])) {
				cpmsg('settings_ipaccess_invalid', '', 'error');
			}
		}
	}

	if(isset($settingsnew['adminipaccess'])) {
		if($settingsnew['adminipaccess'] = trim(preg_replace("/(\s*(\r\n|\n\r|\n|\r)\s*)/", "\r\n", $settingsnew['adminipaccess']))) {
			if(!ipaccess($onlineip, $settingsnew['adminipaccess'])) {
				cpmsg('settings_adminipaccess_invalid', '', 'error');
			}
		}
	}

	if(isset($settingsnew['welcomemsgtitle'])) {
		$settingsnew['welcomemsgtitle'] = cutstr(trim(dhtmlspecialchars($settingsnew['welcomemsgtitle'])), 75);
	}

	if(isset($settingsnew['showsignatures']) && isset($settingsnew['showavatars']) && isset($settingsnew['showimages'])) {
		$settingsnew['showsettings'] = bindec($settingsnew['showsignatures'].$settingsnew['showavatars'].$settingsnew['showimages']);
	}

	if(!empty($settingsnew['globalstick'])) {
		updatecache('globalstick');
	}

	if(isset($settingsnew['inviteconfig'])) {
		$settingsnew['inviteconfig'] = addslashes(serialize($settingsnew['inviteconfig']));
	}

	if(isset($settingsnew['sitemessage'])) {
		$settingsnew['sitemessage'] = addslashes(serialize($settingsnew['sitemessage']));
	}

	if(isset($settingsnew['smthumb'])) {
		$settingsnew['smthumb'] = intval($settingsnew['smthumb']) >= 20 && intval($settingsnew['smthumb']) <= 40 ? intval($settingsnew['smthumb']) : 20;
	}

	if(isset($settingsnew['allowindextype'])) {
		if(!$settingsnew['allowindextype']) {
			$settingsnew['indextype'] = '';
		}
		unset($settingsnew['allowindextype']);
	}

	if(isset($settingsnew['indexhot'])) {
		$settingsnew['indexhot']['limit'] = intval($settingsnew['indexhot']['limit']) ? $settingsnew['indexhot']['limit'] : 10;
		$settingsnew['indexhot']['days'] = intval($settingsnew['indexhot']['days']) ? $settingsnew['indexhot']['days'] : 7;
		$settingsnew['indexhot']['expiration'] = intval($settingsnew['indexhot']['expiration']) ? $settingsnew['indexhot']['expiration'] : 900;
		$settingsnew['indexhot']['width'] = intval($settingsnew['indexhot']['width']) ? $settingsnew['indexhot']['width'] : 100;
		$settingsnew['indexhot']['height'] = intval($settingsnew['indexhot']['height']) ? $settingsnew['indexhot']['height'] : 70;
		$settingsnew['indexhot']['messagecut'] = intval($settingsnew['indexhot']['messagecut']) ? $settingsnew['indexhot']['messagecut'] : 200;
		$indexhot = $settingsnew['indexhot'];
		$settingsnew['indexhot'] = addslashes(serialize($settingsnew['indexhot']));
		updatecache('heats');
	}

	if(isset($settingsnew['defaulteditormode']) && isset($settingsnew['allowswitcheditor'])) {
		$settingsnew['editoroptions'] = bindec($settingsnew['defaulteditormode'].$settingsnew['allowswitcheditor']);
	}

	if(isset($settingsnew['myrecorddays'])) {
		$settingsnew['myrecorddays'] = intval($settingsnew['myrecorddays']) > 0 ? intval($settingsnew['myrecorddays']) : 30;
	}

	if(!empty($settingsnew['thumbstatus']) && !function_exists('imagejpeg')) {
		$settingsnew['thumbstatus'] = 0;
	}

	if(isset($settingsnew['creditsformula']) && isset($settingsnew['extcredits']) && isset($settingsnew['creditspolicy']) && isset($settingsnew['initcredits']) && isset($settingsnew['creditstrans']) && isset($settingsnew['creditstax'])) {
		if(!preg_match("/^([\+\-\*\/\.\d\(\)]|((extcredits[1-8]|digestposts|posts|threads|pageviews|oltime)([\+\-\*\/\(\)]|$)+))+$/", $settingsnew['creditsformula']) || !is_null(@eval(preg_replace("/(digestposts|posts|threads|pageviews|oltime|extcredits[1-8])/", "\$\\1", $settingsnew['creditsformula']).';'))) {
			cpmsg('settings_creditsformula_invalid', '', 'error');
		}

		$extcreditsarray = array();
		if(is_array($settingsnew['extcredits'])) {
			foreach($settingsnew['extcredits'] as $key => $value) {
				if($value['available'] && !$value['title']) {
					cpmsg('settings_credits_title_invalid', '', 'error');
				}
				$extcreditsarray[$key] = array
					(
					'img' => dhtmlspecialchars(stripslashes($value['img'])),
					'title'	=> dhtmlspecialchars(stripslashes($value['title'])),
					'unit' => dhtmlspecialchars(stripslashes($value['unit'])),
					'ratio' => ($value['ratio'] > 0 ? (float)$value['ratio'] : 0),
					'available' => $value['available'],
					'lowerlimit' => intval($settingsnew['creditspolicy']['lowerlimit'][$key]),
					'showinthread' => $value['showinthread'],
					'allowexchangein' => $value['allowexchangein'],
					'allowexchangeout' => $value['allowexchangeout']
					);
				$settingsnew['initcredits'][$key] = intval($settingsnew['initcredits'][$key]);
			}
		}
		if(is_array($settingsnew['creditspolicy'])) {
			foreach($settingsnew['creditspolicy'] as $key => $value) {
				for($i = 1; $i <= 8; $i++) {
					if(empty($value[$i])) {
						unset($settingsnew['creditspolicy'][$key][$i]);
					} else {
						$value[$i] = $value[$i] > 99 ? 99 : ($value[$i] < -99 ? -99 : $value[$i]);
						$settingsnew['creditspolicy'][$key][$i] = intval($value[$i]);
					}
				}
			}
		} else {
			$settingsnew['creditspolicy'] = array();
		}

		for($si = 0; $si < 6; $si++) {
			$creditstransi = $si > 0 && !$settingsnew['creditstrans'][$si] ? $settingsnew['creditstrans'][0] : $settingsnew['creditstrans'][$si];
			if($creditstransi && empty($settingsnew['extcredits'][$creditstransi]['available']) && $settingsnew['creditstrans'][$si] != -1) {
				cpmsg('settings_creditstrans_invalid', '', 'error');
			}
		}
		$settingsnew['creditspolicy'] = addslashes(serialize($settingsnew['creditspolicy']));

		$settingsnew['creditsformulaexp'] = $settingsnew['creditsformula'];
		foreach(array('digestposts', 'posts', 'threads', 'oltime', 'pageviews', 'extcredits1', 'extcredits2', 'extcredits3', 'extcredits4', 'extcredits5', 'extcredits6', 'extcredits7', 'extcredits8') as $var) {
			if($extcreditsarray[$creditsid = preg_replace("/^extcredits(\d{1})$/", "\\1", $var)]['available']) {
				$replacement = $extcreditsarray[$creditsid]['title'];
			} else {
				$replacement = $lang['settings_credits_formula_'.$var];
			}
			$settingsnew['creditsformulaexp'] = str_replace($var, '<u>'.$replacement.'</u>', $settingsnew['creditsformulaexp']);
		}
		$settingsnew['creditsformulaexp'] = addslashes('<u>'.$lang['settings_credits_formula_credits'].'</u>='.$settingsnew['creditsformulaexp']);

		$initformula = str_replace('posts', '0', $settingsnew['creditsformula']);
		for($i = 1; $i <= 8; $i++) {
			$initformula = str_replace('extcredits'.$i, $settingsnew['initcredits'][$i], $initformula);
		}
		eval("\$initcredits = round($initformula);");

		$settingsnew['extcredits'] = addslashes(serialize($extcreditsarray));
		$settingsnew['initcredits'] = $initcredits.','.implode(',', $settingsnew['initcredits']);
		if($settingsnew['creditstax'] < 0 || $settingsnew['creditstax'] >= 1) {
			$settingsnew['creditstax'] = 0;
		}

		$settingsnew['creditstrans'] = implode(',', $settingsnew['creditstrans']);
	}

	if(isset($settingsnew['gzipcompress'])) {
		if(!function_exists('ob_gzhandler') && $settingsnew['gzipcompress']) {
			cpmsg('settings_gzip_invalid', '', 'error');
		}
	}

	if(isset($settingsnew['maxonlines'])) {
		if($settingsnew['maxonlines'] > 65535 || !is_numeric($settingsnew['maxonlines'])) {
			cpmsg('settings_maxonlines_invalid', '', 'error');
		}

		$db->query("ALTER TABLE {$tablepre}sessions MAX_ROWS=$settingsnew[maxonlines]");
		if($settingsnew['maxonlines'] < $settings['maxonlines']) {
			$db->query("DELETE FROM {$tablepre}sessions");
		}
	}

	if(isset($settingsnew['seccodedata'])) {
		$settingsnew['seccodedata']['width'] = intval($settingsnew['seccodedata']['width']);
		$settingsnew['seccodedata']['height'] = intval($settingsnew['seccodedata']['height']);
		if($settingsnew['seccodedata']['type'] != 3) {
			$settingsnew['seccodedata']['width'] = $settingsnew['seccodedata']['width'] < 100 ? 100 : ($settingsnew['seccodedata']['width'] > 200 ? 200 : $settingsnew['seccodedata']['width']);
			$settingsnew['seccodedata']['height'] = $settingsnew['seccodedata']['height'] < 50 ? 50 : ($settingsnew['seccodedata']['height'] > 80 ? 80 : $settingsnew['seccodedata']['height']);
		} else {
			$settingsnew['seccodedata']['width'] = 85;
			$settingsnew['seccodedata']['height'] = 25;
		}
		$settingsnew['seccodedata']['loginfailedcount'] = !empty($settingsnew['seccodedata']['loginfailedcount']) ? 3 : 0;
		$settingsnew['seccodedata'] = addslashes(serialize($settingsnew['seccodedata']));
	}

	if(isset($settingsnew['allowviewuserthread'])) {
		$settingsnew['allowviewuserthread'] = addslashes(serialize($settingsnew['allowviewuserthread']));
	}

	if($operation == 'sec') {
		$settingsnew['seccodestatus'] = bindec(intval($settingsnew['seccodestatus'][5]).intval($settingsnew['seccodestatus'][4]).intval($settingsnew['seccodestatus'][3]).intval($settingsnew['seccodestatus'][2]).intval($settingsnew['seccodestatus'][1]));
		if(is_array($delete)) {
			$db->query("DELETE FROM	{$tablepre}itempool WHERE id IN (".implodeids($delete).")");
		}

		if(is_array($question)) {
			foreach($question as $key => $q) {
				$q = trim($q);
				$a = cutstr(dhtmlspecialchars(trim($answer[$key])), 50);
				if($q !== '' && $a !== '') {
					$db->query("UPDATE {$tablepre}itempool SET question='$q', answer='$a' WHERE id='$key'");
				}
			}
		}

		if(is_array($newquestion) && is_array($newanswer)) {
			foreach($newquestion as $key => $q) {
				$q = trim($q);
				$a = cutstr(dhtmlspecialchars(trim($newanswer[$key])), 50);
				if($q !== '' && $a !== '') {
					$db->query("INSERT INTO	{$tablepre}itempool (question, answer) VALUES ('$q', '$a')");
				}
			}
		}

		updatecache('secqaa');

		$settingsnew['secqaa']['status'] = bindec(intval($settingsnew['secqaa']['status'][3]).intval($settingsnew['secqaa']['status'][2]).intval($settingsnew['secqaa']['status'][1]));
		$settingsnew['secqaa'] = serialize($settingsnew['secqaa']);
	}

	if($operation == 'seo') {
		$settingsnew['rewritestatus'] = bindec(intval($settingsnew['rewritestatus'][5]).intval($settingsnew['rewritestatus'][4]).intval($settingsnew['rewritestatus'][3]).intval($settingsnew['rewritestatus'][2]).intval($settingsnew['rewritestatus'][1]));
		$settingsnew['baidusitemap_life'] = max(1, min(24, intval($settingsnew['baidusitemap_life'])));
	}

	if($operation == 'functions') {
		$settingsnew['bannedmessages'] = bindec(intval($settingsnew['bannedmessages'][3]).intval($settingsnew['bannedmessages'][2]).intval($settingsnew['bannedmessages'][1]));
	}

	if($operation == 'ec') {
		if($settingsnew['ec_ratio']) {
			if($settingsnew['ec_ratio'] < 0) {
				cpmsg('alipay_ratio_invalid', '', 'error');
			}
		} else {
			$settingsnew['ec_mincredits'] = $settingsnew['ec_maxcredits'] = 0;
		}
		foreach(array('ec_ratio', 'ec_mincredits', 'ec_maxcredits', 'ec_maxcreditspermonth', 'tradeimagewidth', 'tradeimageheight') as $key) {
			$settingsnew[$key] = intval($settingsnew[$key]);
		}
		$settingsnew['tradetypes'] = addslashes(serialize($settingsnew['tradetypes']));
	}

	if(isset($settingsnew['visitbanperiods']) && isset($settingsnew['postbanperiods']) && isset($settingsnew['postmodperiods']) && isset($settingsnew['searchbanperiods'])) {
		foreach(array('visitbanperiods', 'postbanperiods', 'postmodperiods', 'searchbanperiods') as $periods) {
			$periodarray = array();
			foreach(explode("\n", $settingsnew[$periods]) as $period) {
				if(preg_match("/^\d{1,2}\:\d{2}\-\d{1,2}\:\d{2}$/", $period = trim($period))) {
					$periodarray[] = $period;
				}
			}
			$settingsnew[$periods] = implode("\r\n", $periodarray);
		}
	}

	if(isset($settingsnew['infosidestatus'])) {
		$settingsnew['infosidestatus'] = addslashes(serialize($settingsnew['infosidestatus']));
	}

	if(isset($settingsnew['heatthread'])) {
		$settingsnew['heatthread']['reply'] = $settingsnew['heatthread']['reply'] > 0 ? intval($settingsnew['heatthread']['reply']) : 5;
		$settingsnew['heatthread']['recommend'] = $settingsnew['heatthread']['recommend'] > 0 ? intval($settingsnew['heatthread']['recommend']) : 3;
		$settingsnew['heatthread']['hottopic'] = !empty($settingsnew['heatthread']['hottopic']) ? $settingsnew['heatthread']['hottopic'] : '50,100,200';
		$settingsnew['heatthread'] = addslashes(serialize($settingsnew['heatthread']));
	}

	if(isset($settingsnew['recommendthread'])) {
		$settingsnew['recommendthread'] = addslashes(serialize($settingsnew['recommendthread']));
	}

	if(isset($settingsnew['timeformat'])) {
		$settingsnew['timeformat'] = $settingsnew['timeformat'] == '24' ? 'H:i' : 'h:i A';
	}

	if(isset($settingsnew['dateformat'])) {
		$settingsnew['dateformat'] = dateformat($settingsnew['dateformat'], 'format');
	}

	if(isset($settingsnew['userdateformat'])) {
		$settingsnew['userdateformat'] = dateformat($settingsnew['userdateformat'], 'format');
	}

	if($isfounder && isset($settingsnew['ftp'])) {
		$settings['ftp'] = unserialize($settings['ftp']);
		$settings['ftp']['password'] = authcode($settings['ftp']['password'], 'DECODE', md5($authkey));
		if(!empty($settingsnew['ftp']['password'])) {
			$pwlen = strlen($settingsnew['ftp']['password']);
			if($pwlen < 3) {
				cpmsg('ftp_password_short', '', 'error');
			}
			if($settingsnew['ftp']['password']{0} == $settings['ftp']['password']{0} && $settingsnew['ftp']['password']{$pwlen - 1} == $settings['ftp']['password']{strlen($settings['ftp']['password']) - 1} && substr($settingsnew['ftp']['password'], 1, $pwlen - 2) == '********') {
				$settingsnew['ftp']['password'] = $settings['ftp']['password'];
			}
			$settingsnew['ftp']['password'] = authcode($settingsnew['ftp']['password'], 'ENCODE', md5($authkey));
		}
		$settingsnew['ftp'] = serialize($settingsnew['ftp']);
	}

	if($isfounder && isset($settingsnew['mail'])) {
		$settings['mail'] = unserialize($settings['mail']);
		$passwordmask = $settings['mail']['auth_password'] ? $settings['mail']['auth_password']{0}.'********'.substr($settings['mail']['auth_password'], -2) : '';
		$settingsnew['mail']['auth_password'] = $settingsnew['mail']['auth_password'] == $passwordmask ? $settings['mail']['auth_password'] : $settingsnew['mail']['auth_password'];
		$settingsnew['mail'] = serialize($settingsnew['mail']);
	}

	if(isset($settingsnew['jsrefdomains'])) {
		$settingsnew['jsrefdomains'] = trim(preg_replace("/(\s*(\r\n|\n\r|\n|\r)\s*)/", "\r\n", $settingsnew['jsrefdomains']));
	}

	if(isset($settingsnew['jsdateformat'])) {
		$settingsnew['jsdateformat'] = dateformat($settingsnew['jsdateformat'], 'format');
	}

	if(isset($settingsnew['wapdateformat'])) {
		$settingsnew['wapdateformat'] = dateformat($settingsnew['wapdateformat'], 'format');
	}

	if(isset($settingsnew['cachethreaddir']) && isset($settingsnew['threadcaches'])) {
		if($settingsnew['cachethreaddir'] && !is_writable(DISCUZ_ROOT.'./'.$settingsnew['cachethreaddir'])) {
			cpmsg('cachethread_dir_noexists', '', 'error');
		}
		if(!empty($fids)) {
			$sqladd = in_array('all', $fids) ? '' :  " WHERE fid IN ('".implode("', '", $fids)."')";
			$db->query("UPDATE {$tablepre}forums SET threadcaches='$settingsnew[threadcaches]'$sqladd");
		}
	}

	if($operation == 'attach') {
		$settingsnew['thumbwidth'] = intval($settingsnew['thumbwidth']) > 0 ? intval($settingsnew['thumbwidth']) : 200;
		$settingsnew['thumbheight'] = intval($settingsnew['thumbheight']) > 0 ? intval($settingsnew['thumbheight']) : 300;
		$settingsnew['swfupload'] = isset($settingsnew['swfupload'][1]) ? 2 : (isset($settingsnew['swfupload'][0]) ? $settingsnew['swfupload'][0] : 0);
	}

	if(isset($settingsnew['watermarktext'])) {
		$settingsnew['watermarktext']['size'] = intval($settingsnew['watermarktext']['size']);
		$settingsnew['watermarktext']['angle'] = intval($settingsnew['watermarktext']['angle']);
		$settingsnew['watermarktext']['shadowx'] = intval($settingsnew['watermarktext']['shadowx']);
		$settingsnew['watermarktext']['shadowy'] = intval($settingsnew['watermarktext']['shadowy']);
		$settingsnew['watermarktext']['fontpath'] = str_replace(array('\\', '/'), '', $settingsnew['watermarktext']['fontpath']);
		if($settingsnew['watermarktype'] == 2 && $settingsnew['watermarktext']['fontpath']) {
			$fontpath = $settingsnew['watermarktext']['fontpath'];
			$fontpathnew = 'ch/'.$fontpath;
			$settingsnew['watermarktext']['fontpath'] = file_exists('images/fonts/'.$fontpathnew) ? $fontpathnew : '';
			if(!$settingsnew['watermarktext']['fontpath']) {
				$fontpathnew = 'en/'.$fontpath;
				$settingsnew['watermarktext']['fontpath'] = file_exists('images/fonts/'.$fontpathnew) ? $fontpathnew : '';
			}
			if(!$settingsnew['watermarktext']['fontpath']) {
				cpmsg('watermarkpreview_fontpath_error', '', 'error');
			}
		}
		$settingsnew['watermarktext'] = addslashes(serialize($settingsnew['watermarktext']));
	}

	if(isset($settingsnew['msgforward'])) {
		if(!empty($settingsnew['msgforward']['messages'])) {
			$tempmsg = explode("\n", $settingsnew['msgforward']['messages']);
			$settingsnew['msgforward']['messages'] = array();
			foreach($tempmsg as $msg) {
				if($msg = strip_tags(trim($msg))) {
					$settingsnew['msgforward']['messages'][] = $msg;
				}
			}
		} else {
			$settingsnew['msgforward']['messages'] = array();
		}

		$tmparray = array(
			'refreshtime' => intval($settingsnew['msgforward']['refreshtime']),
			'quick' => $settingsnew['msgforward']['quick'] ? 1 : 0,
			'messages' => $settingsnew['msgforward']['messages']
		);
		$settingsnew['msgforward'] = addslashes(serialize($tmparray));
	}

	if(isset($settingsnew['onlinehold'])) {
		$settingsnew['onlinehold'] = intval($settingsnew['onlinehold']) > 0 ? intval($settingsnew['onlinehold']) : 15;
	}

	if(isset($settingsnew['postno'])) {
		$settingsnew['postno'] = trim($settingsnew['postno']);
	}
	if(isset($settingsnew['postnocustom'])) {
		$settingsnew['postnocustom'] = addslashes(serialize(explode("\n", $settingsnew['postnocustom'])));
	}

	$updatestyles = FALSE;
	if($operation == 'styles') {
		$settingsnew['disallowfloat'] = array_diff($floatwinkeys, isset($settingsnew['allowfloatwin']) ? $settingsnew['allowfloatwin'] : array());
		$settingsnew['disallowfloat'] = addslashes(serialize($settingsnew['disallowfloat']));
		$settingsnew['customauthorinfo'] = addslashes(serialize(array($settingsnew['customauthorinfo'])));
		list(, $imagemaxwidth) = explode("\t", $settings['zoomstatus']);
		if($imagemaxwidth != $settingsnew['imagemaxwidth']) {
			$updatestyles = TRUE;
		}
		$settingsnew['zoomstatus'] = $settingsnew['zoomstatus']."\t".$settingsnew['imagemaxwidth'];
	}

	if(isset($settingsnew['smcols'])) {
		$settingsnew['smcols'] = $settingsnew['smcols'] >= 8 && $settingsnew['smcols'] <= 12 ? $settingsnew['smcols'] : 8;
	}

	if(isset($settingsnew['msn'])) {
		$settingsnew['msn']['domain'] = $msndomain;
		$settingsnew['msn'] = addslashes(serialize($settingsnew['msn']));
	}
	if(isset($settingsnew['jspath'])) {
		if(!$settingsnew['jspath']) {
			$settingsnew['jspath'] = $settingsnew['jspathcustom'];
		}
	}

	if(isset($settingsnew['domainwhitelist'])) {
		$settingsnew['domainwhitelist'] = trim(preg_replace("/(\s*(\r\n|\n\r|\n|\r)\s*)/", "\r\n", $settingsnew['domainwhitelist']));
	}

	$updatecache = FALSE;
	foreach($settingsnew as $key => $val) {
		if(isset($settings[$key]) && $settings[$key] != $val) {
			$$key = $val;
			$updatecache = TRUE;
			if(in_array($key, array('newbiespan', 'topicperpage', 'postperpage', 'memberperpage', 'hottopic', 'starthreshold', 'delayviewcount', 'attachexpire',
				'visitedforums', 'maxsigrows', 'timeoffset', 'statscachelife', 'pvfrequence', 'oltimespan', 'seccodestatus',
				'maxprice', 'rssttl', 'rewritestatus', 'bdaystatus', 'maxonlines', 'loadctrl', 'floodctrl', 'regctrl', 'regfloodctrl',
				'searchctrl', 'extcredits1', 'extcredits2', 'extcredits3', 'extcredits4', 'extcredits5', 'extcredits6',
				'extcredits7', 'extcredits8', 'transfermincredits', 'exchangemincredits', 'maxincperthread', 'maxchargespan',
				'maxspm', 'maxsearchresults', 'maxsmilies', 'threadmaxpages', 'membermaxpages', 'maxpostsize', 'minpostsize',
				'maxpolloptions', 'karmaratelimit', 'losslessdel', 'smcols',
				'watermarktrans', 'watermarkquality', 'jscachelife', 'waptpp', 'wapppp', 'wapmps', 'maxmodworksmonths', 'frameon', 'maxonlinelist'))) {
				$val = (float)$val;
			}
			if($key == 'dzfeed_limit') {
				foreach(array('thread_replies', 'thread_views', 'thread_rate', 'post_rate', 'user_credit', 'user_threads', 'user_posts', 'user_digest') as $v) {
					if(preg_match_all('/(\d+)/is', $val[$v], $match)) {
						$val[$v] = $match[1];
					} else {
						$val[$v] = 0;
					}
				}
				$val = addslashes(serialize($val));
			}
			$db->query("REPLACE INTO {$tablepre}settings (variable, value)
				VALUES ('$key', '$val')");
		}
	}

	if($updatecache) {
		updatecache('settings');
		if(isset($settingsnew['forumlinkstatus']) && $settingsnew['forumlinkstatus'] != $settings['forumlinkstatus']) {
			updatecache('forumlinks');
		}
		if(isset($settingsnew['userstatusby']) && $settingsnew['userstatusby'] != $settings['userstatusby']) {
			updatecache('usergroups');
			updatecache('ranks');
		}
		if((isset($settingsnew['tagstatus']) && $settingsnew['tagstatus'] != $settings['tagstatus']) || (isset($settingsnew['viewthreadtags']) && $settingsnew['viewthreadtags'] != $settings['viewthreadtags'])) {
			updatecache(array('tags_index', 'tags_viewthread'));
		}
		if((isset($settingsnew['smthumb']) && $settingsnew['smthumb'] != $settings['smthumb']) || (isset($settingsnew['smcols']) && $settingsnew['smcols'] != $settings['smcols']) || (isset($settingsnew['smrows']) && $settingsnew['smrows'] != $settings['smrows'])) {
			updatecache('smilies_js');
		}
		if(isset($settingsnew['customauthorinfo']) && $settingsnew['customauthorinfo'] != $settings['customauthorinfo']) {
			updatecache('custominfo');
		}
		if($operation == 'credits') {
			updatecache('custominfo');
		}
		if($operation == 'access') {
			updatecache('ipctrl');
		}
		if($updatestyles) {
			updatecache('styles');
		}
		if(isset($settingsnew['domainwhitelist'])) {
			updatecache('domainwhitelist');
		}
	}

	if($operation == 'credits' && $projectsave) {
		$projectid = intval($projectid);
		dheader("Location: $boardurl$BASESCRIPT?action=project&operation=add&type=extcredit&projectid=$projectid");
	}
	cpmsg('settings_update_succeed', $BASESCRIPT.'?action=settings&operation='.$operation.(!empty($anchor) ? '&anchor='.$anchor : '').(!empty($from) ? '&from='.$from : ''), 'succeed');
}

function creditsrow($policy) {
	global $settings;
	$policyarray = array(lang('settings_credits_policy_'.$policy));
	for($i = 1; $i <= 8; $i++) {
		$policyarray[] = "<input type=\"text\" class=\"txt\" size=\"2\" name=\"settingsnew[creditspolicy][$policy][$i]\" ".($settings['extcredits'][$i]['available'] ? '' : 'readonly')." value=\"".intval($settings['creditspolicy'][$policy][$i])."\">";
	}
	return $policyarray;
}

function dateformat($string, $operation = 'formalise') {
	$string = htmlspecialchars(trim($string));
	$replace = $operation == 'formalise' ? array(array('n', 'j', 'y', 'Y'), array('mm', 'dd', 'yy', 'yyyy')) : array(array('mm', 'dd', 'yyyy', 'yy'), array('n', 'j', 'Y', 'y'));
	return str_replace($replace[0], $replace[1], $string);
}

function insertconfig($s, $find, $replace) {
	if(preg_match($find, $s)) {
		$s = preg_replace($find, $replace, $s);
	} else {
		$s .= "\r\n".$replace;
	}
	return $s;
}

?>