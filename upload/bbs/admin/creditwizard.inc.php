<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: creditwizard.inc.php 18655 2009-07-08 10:28:06Z wangjinbo $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

$step = in_array($step, array(1, 2, 3)) ? $step : 1;

$query = $db->query("SELECT * FROM {$tablepre}settings WHERE variable IN ('extcredits', 'initcredits', 'creditspolicy', 'creditsformula', 'creditsformulaexp', 'creditstrans', 'creditstax', 'transfermincredits', 'exchangemincredits', 'maxincperthread', 'maxchargespan')");
while($setting = $db->fetch_array($query)) {
	$$setting['variable'] = $setting['value'];
}
$extcredits = unserialize($extcredits);
$initcredits = explode(',', $initcredits);
$creditspolicy = unserialize($creditspolicy);

cpheader();

if($step == 1) {

	if($resetcredit >= 1 && $resetcredit <= 8) {
		$initcredits[$resetcredit] = intval($initcredits[$resetcredit]);
		if(!submitcheck('confirmed')) {
			cpmsg('creditwizard_resetusercredit_warning', $BASESCRIPT.'?action=creditwizard&step=1&resetcredit='.$resetcredit, 'form');
		} else {
			$db->query("UPDATE {$tablepre}members SET extcredits$resetcredit = $initcredits[$resetcredit]", 'UNBUFFERED');
			cpmsg('creditwizard_resetusercredit_ok', $BASESCRIPT.'?action=creditwizard&step=1', 'succeed');
		}
		exit;
	}

	if(!$credit) {

		shownav('tools', 'nav_creditwizard');
		showsubmenu('nav_creditwizard', array(
			array('creditwizard_step_menu_1', 'creditwizard&step=1', $step == 1),
			array('creditwizard_step_menu_2', 'creditwizard&step=2', $step == 2),
			array('creditwizard_step_menu_3', 'creditwizard&step=3', $step == 3),
			array('creditwizard_step_menu_4', 'settings&operation=ec&from=creditwizard', 0),
			array('ec_alipay', 'ec&operation=alipay&from=creditwizard', 0),
			array('ec_tenpay', 'ec&operation=tenpay&from=creditwizard', 0),
		));
		showtableheader();
		showsubtitle(array('credits_id', 'credits_title', 'creditwizard_status', ''));

		for($i = 1; $i <= 8; $i++) {
			showtablerow('', array('class="td21"'), array(
				'extcredits'.$i,
				$extcredits[$i]['title'].($i == $creditstrans ? $lang['creditwizard_iscreditstrans'] : ''),
				$extcredits[$i]['available'] ? '<div class="staton">&#x221A;</div>' : '<div class="statoff">-</div>',
				'<a href="'.$BASESCRIPT.'?action=creditwizard&step=1&credit='.$i.'" class="act">'.$lang['detail'].'</a><a href="'.$BASESCRIPT.'?action=creditwizard&step=1&resetcredit='.$i.'" class="act">'.$lang['reset'].'</a>'
			));
		}

		showtablefooter();

	} else {

		if(!submitcheck('settingsubmit')) {

			$credit = $credit >=1 && $credit <= 8 ? $credit : 1;
			$type = $type >=1 && $type <= 3 ? $type : 1;
			$typeselected = array($type => ' selected="selected"');
			$typeselect = '<select onchange="location.href=\''.$BASESCRIPT.'?action=creditwizard&step=1&credit='.$credit.'&type=\' + this.value"><option value="1"'.$typeselected[1].'>'.$lang['creditwizard_settingtype_global'].'</option><option value="2"'.$typeselected[2].'>'.$lang['creditwizard_settingtype_forum'].'</option><option value="3"'.$typeselected[3].'>'.$lang['creditwizard_settingtype_usergroup'].'</option></select>';
			$creditselect = '<select onchange="location.href=\''.$BASESCRIPT.'?action=creditwizard&step=1&type='.$type.'&credit=\' + this.value">';
			for($i = 1;$i <= 8;$i++) {
				$creditselect .= '<option value="'.$i.'"'.($credit == $i ? ' selected="selected"' : '').'>extcredits'.$i.($extcredits[$i]['title'] ? ' ('.$extcredits[$i]['title'].')' : '').'</option>';
			}
			$creditselect .= '</select>';
			$tips = 'creditwizard_settingtype_'.(empty($type) || $type == 1 ? 'global' : ($type == 2 ? 'forum' : ($type == 3 ? 'usergroup' : 'global'))).'_tips';

			shownav('tools', 'nav_creditwizard');
			showsubmenu('<a href="'.$BASESCRIPT.'?action=creditwizard&step=1">'.$lang['creditwizard_step_menu_1'].'</a> - extcredits'.$credit.($extcredits[$credit]['title'] ? '('.$extcredits[$credit]['title'].')' : ''));
			showtips($tips);
			showformheader("creditwizard&step=1&credit=$credit&type=$type");
			showtableheader();
			showtablerow('', 'class="lineheight" colspan="15"', "$lang[select]: $creditselect $typeselect");

			if($type == 1) {

				showtitle('settings_credits_extended');
				showsetting('creditwizard_credit_title', 'settingsnew[title]', $extcredits[$credit]['title'], 'text');
				showsetting('creditwizard_credits_unit', 'settingsnew[unit]', $extcredits[$credit]['unit'], 'text');
				showsetting('creditwizard_credits_ratio', 'settingsnew[ratio]', $extcredits[$credit]['ratio'], 'text');
				showsetting('creditwizard_credits_init', 'settingsnew[init]', intval($initcredits[$credit]), 'text');
				showsetting('creditwizard_credits_available', 'settingsnew[available]', intval($extcredits[$credit]['available']), 'radio');
				showsetting('creditwizard_credits_show_in_thread', 'settingsnew[showinthread]', intval($extcredits[$credit]['showinthread']), 'radio');
				showsetting('settings_credits_export', 'settingsnew[allowexchangeout]', intval($extcredits[$credit]['allowexchangeout']), 'radio');
				showsetting('settings_credits_import', 'settingsnew[allowexchangein]', intval($extcredits[$credit]['allowexchangein']), 'radio');
				showtitle('settings_credits_policy');
				showsetting('settings_credits_policy_post', 'settingsnew[policy_post]', intval($creditspolicy['post'][$credit]), 'text');
				showsetting('settings_credits_policy_reply', 'settingsnew[policy_reply]', intval($creditspolicy['reply'][$credit]), 'text');
				showsetting('settings_credits_policy_digest', 'settingsnew[policy_digest]', intval($creditspolicy['digest'][$credit]), 'text');
				showsetting('settings_credits_policy_postattach', 'settingsnew[policy_postattach]', intval($creditspolicy['postattach'][$credit]), 'text');
				showsetting('settings_credits_policy_getattach', 'settingsnew[policy_getattach]', intval($creditspolicy['getattach'][$credit]), 'text');
				showsetting('settings_credits_policy_search', 'settingsnew[policy_search]', intval($creditspolicy['search'][$credit]), 'text');
				showsetting('settings_credits_policy_promotion_visit', 'settingsnew[policy_promotion_visit]', intval($creditspolicy['promotion_visit'][$credit]), 'text');
				showsetting('settings_credits_policy_promotion_register', 'settingsnew[policy_promotion_register]', intval($creditspolicy['promotion_register'][$credit]), 'text');
				showsetting('settings_credits_policy_tradefinished', 'settingsnew[policy_tradefinished]', intval($creditspolicy['tradefinished'][$credit]), 'text');
				showsetting('settings_credits_policy_votepoll', 'settingsnew[policy_votepoll]', intval($creditspolicy['votepoll'][$credit]), 'text');
				showsetting('settings_credits_policy_lowerlimit', 'settingsnew[lowerlimit]', intval($extcredits[$credit]['lowerlimit']), 'text');
				showtablerow('', 'class="lineheight" colspan="2"', $lang['settings_credits_policy_comment']);
				showsubmit('settingsubmit', 'submit', '<input type="reset" class="btn" name="settingsubmit" value="'.$lang['reset'].'" />');
				showtablefooter();
				showformfooter();

			} elseif($type == 2) {

				require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
				$fids = implode(',', array_keys($_DCACHE['forums']));
				$query = $db->query("SELECT fid, postcredits, replycredits, getattachcredits, postattachcredits, digestcredits
					FROM {$tablepre}forumfields WHERE fid in ($fids)");
				while($forumcredit = $db->fetch_array($query)) {
					$forumcredit['postcreditsstatus'] = $forumcredit['postcredits'] ? 'checked' : '';
					$forumcredit['postcredits'] = $forumcredit['postcredits'] ? unserialize($forumcredit['postcredits']) : array();
					$forumcredit['postcredits'] = intval($forumcredit['postcredits'][$credit]);
					$forumcredit['replycreditsstatus'] = $forumcredit['replycredits'] ? 'checked' : '';
					$forumcredit['replycredits'] = $forumcredit['replycredits'] ? unserialize($forumcredit['replycredits']) : array();
					$forumcredit['replycredits'] = intval($forumcredit['replycredits'][$credit]);
					$forumcredit['getattachcreditsstatus'] = $forumcredit['getattachcredits'] ? 'checked' : '';
					$forumcredit['getattachcredits'] = $forumcredit['getattachcredits'] ? unserialize($forumcredit['getattachcredits']) : array();
					$forumcredit['getattachcredits'] = intval($forumcredit['getattachcredits'][$credit]);
					$forumcredit['postattachcreditsstatus'] = $forumcredit['postattachcredits'] ? 'checked' : '';
					$forumcredit['postattachcredits'] = $forumcredit['postattachcredits'] ? unserialize($forumcredit['postattachcredits']) : array();
					$forumcredit['postattachcredits'] = intval($forumcredit['postattachcredits'][$credit]);
					$forumcredit['digestcreditsstatus'] = $forumcredit['digestcredits'] ? 'checked' : '';
					$forumcredit['digestcredits'] = $forumcredit['digestcredits'] ? unserialize($forumcredit['digestcredits']) : array();
					$forumcredit['digestcredits'] = intval($forumcredit['digestcredits'][$credit]);
					$forumcredits[$forumcredit['fid']] = $forumcredit;
				}

				$credittable = '';
				foreach($_DCACHE['forums'] as $fid => $forum) {
					if($forum['type'] != 'group') {
						$credittable .= showtablerow('', array('', 'class="td28"', 'class="td28"', 'class="td28"', 'class="td28"', 'class="td28"'), array(
							"<input class=\"checkbox\" title=\"$lang[select_all]\" type=\"checkbox\" name=\"chkallv$fid\" onclick=\"checkAll('value', this.form, $fid, 'chkallv$fid')\">".
							($forum['type'] == 'forum' ? '' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;').
							"&nbsp;<a href=\"$BASESCRIPT?frames=yes&action=forums&operation=edit&fid=$fid&anchor=credits\" target=\"_blank\">$forum[name]</a>",
							"<input class=\"checkbox\" type=\"checkbox\" name=\"postcreditsstatus[$fid]\" value=\"$fid\" chkvalue=\"$fid\" {$forumcredits[$fid][postcreditsstatus]}>&nbsp;<input type=\"text\" class=\"txt\" name=\"postcredits[$fid]\" size=\"2\" value=\"{$forumcredits[$fid][postcredits]}\">",
							"<input class=\"checkbox\" type=\"checkbox\" name=\"replycreditsstatus[$fid]\" value=\"$fid\" chkvalue=\"$fid\" {$forumcredits[$fid][replycreditsstatus]}>&nbsp;<input type=\"text\" class=\"txt\" name=\"replycredits[$fid]\" size=\"2\" value=\"{$forumcredits[$fid][replycredits]}\">",
							"<input class=\"checkbox\" type=\"checkbox\" name=\"digestcreditsstatus[$fid]\" value=\"$fid\" chkvalue=\"$fid\" {$forumcredits[$fid][digestcreditsstatus]}>&nbsp;<input type=\"text\" class=\"txt\" name=\"digestcredits[$fid]\" size=\"2\" value=\"{$forumcredits[$fid][digestcredits]}\">",
							"<input class=\"checkbox\" type=\"checkbox\" name=\"postattachcreditsstatus[$fid]\" value=\"$fid\" chkvalue=\"$fid\" {$forumcredits[$fid][postattachcreditsstatus]}>&nbsp;<input type=\"text\" class=\"txt\" name=\"postattachcredits[$fid]\" size=\"2\" value=\"{$forumcredits[$fid][postattachcredits]}\">",
							"<input class=\"checkbox\" type=\"checkbox\" name=\"getattachcreditsstatus[$fid]\" value=\"$fid\" chkvalue=\"$fid\" {$forumcredits[$fid][getattachcreditsstatus]}>&nbsp;<input type=\"text\" class=\"txt\" name=\"getattachcredits[$fid]\" size=\"2\" value=\"{$forumcredits[$fid][getattachcredits]}\">"
						), TRUE);
					}
				}

				showtitle('creditwizard_forum_creditspolicy');

?>
<tr ><td><?=$lang['forum']?></td>
<td><input class="checkbox" type="checkbox" name="chkall1" id="chkall1" onclick="checkAll('prefix', this.form, 'postcreditsstatus', 'chkall1')" /><label for="chkall1"> <?=$lang['settings_credits_policy_post']?></label></td>
<td><input class="checkbox" type="checkbox" name="chkall2" id="chkall2" onclick="checkAll('prefix', this.form, 'replycreditsstatus', 'chkall2')" /><label for="chkall2"> <?=$lang['settings_credits_policy_reply']?></label></td>
<td><input class="checkbox" type="checkbox" name="chkall3" id="chkall3" onclick="checkAll('prefix', this.form, 'digestcreditsstatus', 'chkall3')" /><label for="chkall3"> <?=$lang['settings_credits_policy_digest']?></label></td>
<td><input class="checkbox" type="checkbox" name="chkall4" id="chkall4" onclick="checkAll('prefix', this.form, 'postattachcreditsstatus', 'chkall4')" /><label for="chkall4"> <?=$lang['settings_credits_policy_postattach']?></label></td>
<td><input class="checkbox" type="checkbox" name="chkall5" id="chkall5" onclick="checkAll('prefix', this.form, 'getattachcreditsstatus', 'chkall5')" /><label for="chkall5"> <?=$lang['settings_credits_policy_getattach']?></label></td></tr>

<?
				echo $credittable;
				showsubmit('settingsubmit', 'submit', '<input type="button" class="btn" value="'.$lang['creditwizard_return'].'" onclick="location.href=\''.$BASESCRIPT.'?action=creditwizard&step=1\'" /> &nbsp;<input type="reset" class="btn" name="settingsubmit" value="'.$lang['reset'].'" />');
				showtablefooter();
				showformfooter();

			} else {

				$query = $db->query("SELECT groupid, grouptitle, raterange FROM {$tablepre}usergroups ORDER BY type DESC, groupid");
				$raterangetable = '';
				while($group = $db->fetch_array($query)) {
					$ratemin = $ratemax = $ratemrpd = '';
					foreach(explode("\n", $group['raterange']) as $range) {
						$range = explode("\t", $range);
						if($range[0] == $credit) {
							$ratemin = $range[1];$ratemax = $range[2];$ratemrpd = $range[3];break;
						}
					}
					$raterangetable .= showtablerow('', array('', 'class="td28"', 'class="td28"', 'class="td28"'), array(
						"<input class=\"checkbox\" type=\"checkbox\" name=\"raterangestatus[$group[groupid]]\" value=\"1\" ".($ratemin && $ratemax && $ratemax ? 'checked' : '')."> <a href=\"$BASESCRIPT?frames=yes&action=groups&operation=user&do=edit&id=$group[groupid]&anchor=exempt\" target=\"_blank\">$group[grouptitle]</a>",
						"<input type=\"text\" class=\"txt\" name=\"ratemin[$group[groupid]]\" size=\"3\" value=\"$ratemin\">",
						"<input type=\"text\" class=\"txt\" name=\"ratemax[$group[groupid]]\" size=\"3\" value=\"$ratemax\">",
						"<input type=\"text\" class=\"txt\" name=\"ratemrpd[$group[groupid]]\" size=\"3\" value=\"$ratemrpd\">"
					), TRUE);
				}

				showtitle('creditwizard_forum_groupraterange');
				showsubtitle(array('forum', 'usergroups_edit_raterange_min', 'usergroups_edit_raterange_max', 'usergroups_edit_raterange_mrpd'));
				echo $raterangetable;
				showsubmit('settingsubmit', 'submit', '<input type="reset" class="btn" name="settingsubmit" value="'.$lang['reset'].'" />');
				showtablefooter();
				showformfooter();
			}

		} else {

			if($type == 1) {

				if($creditstrans == $credit && empty($settingsnew['available'])) {
					cpmsg('settings_creditstrans_invalid', '', 'error');
				}

				$initcredits[$credit] = intval($settingsnew['init']);
				$initcredits = implode(',', $initcredits);

				$extcredits[$credit] = array(
					'title' => dhtmlspecialchars(stripslashes($settingsnew['title'])),
					'unit' => dhtmlspecialchars(stripslashes($settingsnew['unit'])),
					'ratio' => ($settingsnew['ratio'] > 0 ? (float)$settingsnew['ratio'] : 0),
					'available' => $settingsnew['available'],
					'showinthread' => $settingsnew['showinthread'],
					'allowexchangeout' => $settingsnew['allowexchangeout'],
					'allowexchangein' => $settingsnew['allowexchangein'],
					'lowerlimit' => intval($settingsnew['lowerlimit']));
				$extcredits = addslashes(serialize($extcredits));

				$creditspolicy['post'][$credit] = intval($settingsnew['policy_post']);
				$creditspolicy['reply'][$credit] = intval($settingsnew['policy_reply']);
				$creditspolicy['digest'][$credit] = intval($settingsnew['policy_digest']);
				$creditspolicy['postattach'][$credit] = intval($settingsnew['policy_postattach']);
				$creditspolicy['getattach'][$credit] = intval($settingsnew['policy_getattach']);
				$creditspolicy['pm'][$credit] = intval($settingsnew['policy_pm']);
				$creditspolicy['search'][$credit] = intval($settingsnew['policy_search']);
				$creditspolicy['promotion_visit'][$credit] = intval($settingsnew['policy_promotion_visit']);
				$creditspolicy['promotion_register'][$credit] = intval($settingsnew['policy_promotion_register']);
				$creditspolicy['tradefinished'][$credit] = intval($settingsnew['policy_tradefinished']);
				$creditspolicy['votepoll'][$credit] = intval($settingsnew['policy_votepoll']);
				$creditspolicy = serialize($creditspolicy);

				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('initcredits', '$initcredits')");
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('extcredits', '$extcredits')");
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('creditspolicy', '$creditspolicy')");

				updatecache('settings');
				cpmsg('creditwizard_edit_succeed', $BASESCRIPT.'?action=creditwizard&step=1&credit='.$credit.'&type=1', 'succeed');

			} elseif($type == 2) {

				require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
				$fids = implode(',', array_keys($_DCACHE['forums']));
				$query = $db->query("SELECT fid, postcredits, replycredits, getattachcredits, postattachcredits, digestcredits
					FROM {$tablepre}forumfields WHERE fid in ($fids)");
				$sqls = array();
				while($forumcredit = $db->fetch_array($query)) {
					$forumcredit['postcredits'] = $forumcredit['postcredits'] ? unserialize($forumcredit['postcredits']) : array();
					$forumcredit['postcredits'][$credit] = intval($postcredits[$forumcredit['fid']]);
					$forumcredit['postcredits'][$credit]  = $forumcredit['postcredits'][$credit] < -99 ? -99 : $forumcredit['postcredits'][$credit];
					$forumcredit['postcredits'][$credit]  = $forumcredit['postcredits'][$credit] > 99 ? 99 : $forumcredit['postcredits'][$credit];
					$sql = "postcredits='".($postcreditsstatus[$forumcredit['fid']] ? addslashes(serialize($forumcredit['postcredits'])) : '')."'";

					$forumcredit['replycredits'] = $forumcredit['replycredits'] ? unserialize($forumcredit['replycredits']) : array();
					$forumcredit['replycredits'][$credit] = intval($replycredits[$forumcredit['fid']]);
					$forumcredit['replycredits'][$credit]  = $forumcredit['replycredits'][$credit] < -99 ? -99 : $forumcredit['replycredits'][$credit];
					$forumcredit['replycredits'][$credit]  = $forumcredit['replycredits'][$credit] > 99 ? 99 : $forumcredit['replycredits'][$credit];
					$sql .= ",replycredits='".($replycreditsstatus[$forumcredit['fid']] ? addslashes(serialize($forumcredit['replycredits'])) : '')."'";

					$forumcredit['getattachcredits'] = $forumcredit['getattachcredits'] ? unserialize($forumcredit['getattachcredits']) : array();
					$forumcredit['getattachcredits'][$credit] = intval($getattachcredits[$forumcredit['fid']]);
					$forumcredit['getattachcredits'][$credit]  = $forumcredit['getattachcredits'][$credit] < -99 ? -99 : $forumcredit['getattachcredits'][$credit];
					$forumcredit['getattachcredits'][$credit]  = $forumcredit['getattachcredits'][$credit] > 99 ? 99 : $forumcredit['getattachcredits'][$credit];
					$sql .= ",getattachcredits='".($getattachcreditsstatus[$forumcredit['fid']] ? addslashes(serialize($forumcredit['getattachcredits'])) : '')."'";

					$forumcredit['postattachcredits'] = $forumcredit['postattachcredits'] ? unserialize($forumcredit['postattachcredits']) : array();
					$forumcredit['postattachcredits'][$credit] = intval($postattachcredits[$forumcredit['fid']]);
					$forumcredit['postattachcredits'][$credit]  = $forumcredit['postattachcredits'][$credit] < -99 ? -99 : $forumcredit['postattachcredits'][$credit];
					$forumcredit['postattachcredits'][$credit]  = $forumcredit['postattachcredits'][$credit] > 99 ? 99 : $forumcredit['postattachcredits'][$credit];
					$sql .= ",postattachcredits='".($postattachcreditsstatus[$forumcredit['fid']] ? addslashes(serialize($forumcredit['postattachcredits'])) : '')."'";

					$forumcredit['digestcredits'] = $forumcredit['digestcredits'] ? unserialize($forumcredit['digestcredits']) : array();
					$forumcredit['digestcredits'][$credit] = intval($digestcredits[$forumcredit['fid']]);
					$forumcredit['digestcredits'][$credit]  = $forumcredit['digestcredits'][$credit] < -99 ? -99 : $forumcredit['digestcredits'][$credit];
					$forumcredit['digestcredits'][$credit]  = $forumcredit['digestcredits'][$credit] > 99 ? 99 : $forumcredit['digestcredits'][$credit];
					$sql .= ",digestcredits='".($digestcreditsstatus[$forumcredit['fid']] ? addslashes(serialize($forumcredit['digestcredits'])) : '')."'";

					$db->query("UPDATE {$tablepre}forumfields SET $sql WHERE fid=$forumcredit[fid]", 'UNBUFFERED');
				}

				cpmsg('creditwizard_edit_succeed', $BASESCRIPT.'?action=creditwizard&step=1&credit='.$credit.'&type=2', 'succeed');

			} else {

				$query = $db->query("SELECT groupid, grouptitle, raterange FROM {$tablepre}usergroups");
				$raterangetable = '';
				while($group = $db->fetch_array($query)) {
					$raterangenew = '';
					$rangearray = array();
					foreach(explode("\n", $group['raterange']) as $range) {
						$ranges = explode("\t", $range);
						$rangearray[$ranges[0]] = $range;
					}
					$range = array();
					if($raterangestatus[$group['groupid']]) {
						$range[0] = $credit;
						$range[1] = intval($ratemin[$group['groupid']] < -999 ? -999 : $ratemin[$group['groupid']]);
						$range[2] = intval($ratemax[$group['groupid']] > 999 ? 999 : $ratemax[$group['groupid']]);
						$range[3] = intval($ratemrpd[$group['groupid']] > 99999 ? 99999 : $ratemrpd[$group['groupid']]);
						if(!$range[3] || $range[2] <= $range[1] || $range[3]< max(abs($range[1]), abs($range[2]))) {
							cpmsg('creditwizard_edit_rate_invalid', '', 'error');
						}
						$rangearray[$credit] = implode("\t", $range);
					} else {
						unset($rangearray[$credit]);
					}
					$raterangenew = $rangearray ? implode("\n", $rangearray) : '';
					$db->query("UPDATE {$tablepre}usergroups SET raterange='$raterangenew' WHERE groupid=$group[groupid]", 'UNBUFFERED');
				}

				updatecache('usergroups');
				updatecache('admingroups');
				cpmsg('creditwizard_edit_succeed', $BASESCRIPT.'?action=creditwizard&step=1&credit='.$credit.'&type=3', 'succeed');

			}

		}

	}

} elseif($step == 2) {

	if(!submitcheck('settingsubmit')) {

		$formulareplace .= '\'<u>'.$lang['settings_credits_formula_digestposts'].'</u>\',\'<u>'.$lang['settings_credits_formula_posts'].'</u>\',\'<u>'.$lang['settings_credits_formula_threads'].'</u>\',\'<u>'.$lang['settings_credits_formula_oltime'].'</u>\',\'<u>'.$lang['settings_credits_formula_pageviews'].'</u>\'';

?>
<script type="text/JavaScript">

function isUndefined(variable) {
	return typeof variable == 'undefined' ? true : false;
}

function insertunit(text, textend) {
	$('creditsformulanew').focus();
	textend = isUndefined(textend) ? '' : textend;
	if(!isUndefined($('creditsformulanew').selectionStart)) {
		var opn = $('creditsformulanew').selectionStart + 0;
		if(textend != '') {
			text = text + $('creditsformulanew').value.substring($('creditsformulanew').selectionStart, $('creditsformulanew').selectionEnd) + textend;
		}
		$('creditsformulanew').value = $('creditsformulanew').value.substr(0, $('creditsformulanew').selectionStart) + text + $('creditsformulanew').value.substr($('creditsformulanew').selectionEnd);
	} else if(document.selection && document.selection.createRange) {
		var sel = document.selection.createRange();
		if(textend != '') {
			text = text + sel.text + textend;
		}
		sel.text = text.replace(/\r?\n/g, '\r\n');
		sel.moveStart('character', -strlen(text));
	} else {
		$('creditsformulanew').value += text;
	}
	formulaexp();
}

var formulafind = new Array('digestposts', 'posts', 'threads', 'oltime', 'pageviews');
var formulareplace = new Array(<?=$formulareplace?>);
function formulaexp() {
	var result = $('creditsformulanew').value;
<?

		$extcreditsbtn = '';
		for($i = 1; $i <= 8; $i++) {
			$extcredittitle = $extcredits[$i]['available'] ? $extcredits[$i]['title'] : $lang['settings_credits_formula_extcredits'].$i;
			echo 'result = result.replace(/extcredits'.$i.'/g, \'<u>'.$extcredittitle.'</u>\');';
			$extcreditsbtn .= '<a href="###" onclick="insertunit(\'extcredits'.$i.'\')">'.$extcredittitle.'</a> &nbsp;';
		}

		echo 'result = result.replace(/digestposts/g, \'<u>'.$lang['settings_credits_formula_digestposts'].'</u>\');';
		echo 'result = result.replace(/posts/g, \'<u>'.$lang['settings_credits_formula_posts'].'</u>\');';
		echo 'result = result.replace(/threads/g, \'<u>'.$lang['settings_credits_formula_threads'].'</u>\');';
		echo 'result = result.replace(/oltime/g, \'<u>'.$lang['settings_credits_formula_oltime'].'</u>\');';
		echo 'result = result.replace(/pageviews/g, \'<u>'.$lang['settings_credits_formula_pageviews'].'</u>\');';

?>
	$('creditsformulaexp').innerHTML = '<u><?=$lang['settings_credits_formula_credits']?></u>=' + result;
}
</script>
<?

		shownav('tools', 'nav_creditwizard');
		showsubmenu('nav_creditwizard', array(
			array('creditwizard_step_menu_1', 'creditwizard&step=1', $step == 1),
			array('creditwizard_step_menu_2', 'creditwizard&step=2', $step == 2),
			array('creditwizard_step_menu_3', 'creditwizard&step=3', $step == 3),
			array('creditwizard_step_menu_4', 'settings&operation=ec&from=creditwizard', 0),
			array('ec_alipay', 'ec&operation=alipay&from=creditwizard', 0),
			array('ec_tenpay', 'ec&operation=tenpay&from=creditwizard', 0),
		));
		showtips('creditwizard_tips_formula');
		showformheader('creditwizard&step=2');
		showtableheader();
		showtitle('settings_credits_formula');
		showtablerow('', 'colspan="2"', '<div class="extcredits">'.$extcreditsbtn.'<br /><a href="###" onclick="insertunit(\'digestposts\')">'.$lang['settings_credits_formula_digestposts'].'</a>&nbsp;<a href="###" onclick="insertunit(\'posts\')">'.$lang['settings_credits_formula_posts'].'</a>&nbsp;<a href="###" onclick="insertunit(\'threads\')">'.$lang['settings_credits_formula_threads'].'</a>&nbsp;<a href="###" onclick="insertunit(\'oltime\')">'.$lang['settings_credits_formula_oltime'].'</a>&nbsp;<a href="###" onclick="insertunit(\'pageviews\')">'.$lang['settings_credits_formula_pageviews'].'</a>&nbsp;<a href="###" onclick="insertunit(\'+\')">&nbsp;+&nbsp;</a>&nbsp;<a href="###" onclick="insertunit(\'-\')">&nbsp;-&nbsp;</a>&nbsp;<a href="###" onclick="insertunit(\'*\')">&nbsp;*&nbsp;</a>&nbsp;<a href="###" onclick="insertunit(\'/\')">&nbsp;/&nbsp;</a>&nbsp;<a href="###" onclick="insertunit(\'(\', \')\')">&nbsp;(&nbsp;)&nbsp;</a></div><div id="creditsformulaexp" class="marginbot diffcolor2">'.$creditsformulaexp.'</div><textarea name="creditsformulanew" id="creditsformulanew" style="width: 80%" rows="3" onkeyup="formulaexp()">'.dhtmlspecialchars($creditsformula).'</textarea>');

		showsubmit('settingsubmit');
		showtablefooter();
		showformfooter();

	} else {

		if(!preg_match("/^([\+\-\*\/\.\d\(\)]|((extcredits[1-8]|digestposts|posts|threads|pageviews|oltime)([\+\-\*\/\(\)]|$)+))+$/", $creditsformulanew) || !is_null(@eval(preg_replace("/(digestposts|posts|threads|pageviews|oltime|extcredits[1-8])/", "\$\\1", $creditsformulanew).';'))) {
			cpmsg('settings_creditsformula_invalid', '', 'error');
		}

		$creditsformulaexpnew = $creditsformulanew;
		foreach(array('digestposts', 'posts', 'threads', 'oltime', 'pageviews', 'extcredits1', 'extcredits2', 'extcredits3', 'extcredits4', 'extcredits5', 'extcredits6', 'extcredits7', 'extcredits8') as $var) {
			if($extcredits[$creditsid = preg_replace("/^extcredits(\d{1})$/", "\\1", $var)]['available']) {
				$replacement = $extcredits[$creditsid]['title'];
			} else {
				$replacement = $lang['settings_credits_formula_'.$var];
			}
			$creditsformulaexpnew = str_replace($var, '<u>'.$replacement.'</u>', $creditsformulaexpnew);
		}
		$creditsformulaexpnew = addslashes('<u>'.$lang['settings_credits_formula_credits'].'</u>='.$creditsformulaexpnew);

		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('creditsformula', '".addslashes($creditsformulanew)."')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('creditsformulaexp', '".addslashes($creditsformulaexpnew)."')");

		updatecache('settings');

		cpmsg('creditwizard_edit_succeed', $BASESCRIPT.'?action=creditwizard&step=2', 'succeed');

	}

} else {

	if(!submitcheck('settingsubmit')) {

		$creditstransselect = '';
		$creditstrans = explode(',', $creditstrans);
		for($i = 0; $i <= 8; $i++) {
			if($i == 0 || $extcredits[$i]['available']) {
				$creditstransselect .= '<option value="'.$i.'" '.($i == intval($creditstrans[0]) ? 'selected' : '').'>'.($i ? 'extcredits'.$i.' ('.$extcredits[$i]['title'].')' : $lang['none']).'</option>';
			}
		}

		function showtextradio($textname, $textvalue, $radioname, $radioes = array()) {
			$a = '<input type="text" class="txt marginbot" name="'.$textname.'" id="'.$textname.'" value="'.$textvalue.'" /><ul style="width: 340px;" onmouseover="altStyle(this);">';
			if(is_array($radioes)) {
				foreach($radioes as $radio) {
					$a .= '<li><input type="radio" name="'.$radioname.'" value="'.$radio[1].'" class="radio" onclick="$(\''.$textname.'\').value = this.value"'.($radio[2] ? ' checked="checked"' : '').' /> '.$radio[0].'</li>';
				}
			}
			$a .= '</ul>';
			return $a;
		}

		shownav('tools', 'nav_creditwizard');
		showsubmenu('nav_creditwizard', array(
			array('creditwizard_step_menu_1', 'creditwizard&step=1', $step == 1),
			array('creditwizard_step_menu_2', 'creditwizard&step=2', $step == 2),
			array('creditwizard_step_menu_3', 'creditwizard&step=3', $step == 3),
			array('creditwizard_step_menu_4', 'settings&operation=ec&from=creditwizard', 0),
			array('ec_alipay', 'ec&operation=alipay&from=creditwizard', 0),
			array('ec_tenpay', 'ec&operation=tenpay&from=creditwizard', 0),
		));
		showtips('creditwizard_tips_creditsuse');
		showformheader('creditwizard&step=3');
		showtableheader('creditwizard_step_menu_3');
		showsetting('settings_credits_trans', '', '', '<select onchange="$(\'allowcreditstrans\').style.display = this.value != 0 ? \'\' : \'none\'" name="creditstransnew">'.$creditstransselect.'</select>');
		showsetting('settings_credits_tax', '', '', showtextradio('creditstaxnew', $creditstax, 'creditstaxradio', array(
			array($lang['low'].' (0.01)', '0.01', $creditstax == '0.01'),
			array($lang['middle'].' (0.1)', '0.1', $creditstax == '0.1'),
			array($lang['high'].' (0.5)', '0.5', $creditstax == '0.5')
		)));
		showsetting('settings_credits_minexchange', '', '', showtextradio('exchangemincreditsnew', $exchangemincredits, 'exchangemincreditsradio', array(
			array($lang['low'].' (100)', 100, $exchangemincredits == 100),
			array($lang['middle'].' (1000)', 1000, $exchangemincredits == 1000),
			array($lang['high'].' (5000)', 5000, $exchangemincredits == 5000)
		)));

		showtagheader('tbody', 'allowcreditstrans', $creditstrans);
		showtitle('creditwizard_allowcreditstrans');
		showsetting('settings_credits_mintransfer', '', '', showtextradio('transfermincreditsnew', $transfermincredits, 'transfermincreditsradio', array(
			array($lang['low'].' (100)', 100, $transfermincredits == 100),
			array($lang['middle'].' (1000)', 1000, $transfermincredits == 1000),
			array($lang['high'].' (5000)', 5000, $transfermincredits == 5000)
		)));
		showsetting('settings_credits_maxincperthread', '', '', showtextradio('maxincperthreadnew', $maxincperthread, 'maxincperthreadradio', array(
			array($lang['nolimit'].' (0)', 0, $maxincperthread == 0),
			array($lang['low'].' (10)', 10, $maxincperthread == 10),
			array($lang['middle'].' (50)', 50, $maxincperthread == 50),
			array($lang['high'].' (100)', 100, $maxincperthread == 100)
		)));
		showsetting('settings_credits_maxchargespan', '', '', showtextradio('maxchargespannew', $maxchargespan, 'maxchargespanradio', array(
			array($lang['nolimit'].' (0)', 0, $maxchargespan == 0),
			array($lang['low'].' (5)', 5, $maxchargespan == 5),
			array($lang['middle'].' (24)', 24, $maxchargespan == 24),
			array($lang['high'].' (48)', 48, $maxchargespan == 48)
		)));
		showtagfooter('tbody');

		showsubmit('settingsubmit');
		showtablefooter();
		showformfooter();

	} else {
		if($creditstaxnew < 0 || $creditstaxnew >= 1) {
			$creditstaxnew = 0;
		}
		$creditstrans = explode(',', $creditstrans);
		$creditstrans[0] = (float)$creditstransnew;

		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('creditstrans', '".implode(',', $creditstrans)."')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('creditstax', '".((float)$creditstaxnew)."')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('transfermincredits', '".((float)$transfermincreditsnew)."')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('exchangemincredits', '".((float)$exchangemincreditsnew)."')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('maxincperthread', '".((float)$maxincperthreadnew)."')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('maxchargespan', '".((float)$maxchargespannew)."')");

		updatecache('settings');
		cpmsg('creditwizard_edit_succeed', $BASESCRIPT.'?action=creditwizard&step=3', 'succeed');
	}
}

?>