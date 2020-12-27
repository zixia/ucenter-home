<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magics.inc.php 18917 2009-08-03 01:48:48Z liuqiang $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();
if(!isfounder()) cpmsg('noaccess_isfounder', '', 'error');
$operation = $operation ? $operation : 'admin';

if($operation == 'admin') {

	if(!submitcheck('magicsubmit')) {

		shownav('extended', 'magics', 'admin');
		showsubmenu('nav_magics', array(
			array('config', 'magics&operation=config', 0),
			array('admin', 'magics&operation=admin', 1),
			array('nav_magics_market', 'magics&operation=market', 0)
		));
		showtips('magics_tips');

		echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[
			[1,'', 'td25'],
			[1,'<input type="text" class="txt" size="3"	name="newdisplayorder[]">', 'td28'],
			[1,'', 'td25'],
			[1,'', 'td28'],
			[1,'<input type="text" class="txt" size="10" name="newname[]">', ''],
			[1,'<input type="text" class="txt" size="25" name="newdescription[]">', 'td28'],
			[1,'<input type="text" class="txt" size="5" name="newprice[]">', 'td28'],
			[1,'<input type="text" class="txt" size="5" name="newnum[]">', 'td23'],
			[1,'<select name="newtype[]"><option value="1" selected>$lang[magics_type_1]</option><option value="2">$lang[magics_type_2]</option><option value="4">$lang[magics_type_4]</option><option value="3">$lang[magics_type_3]</option></select>', 'td25'],
			[1,'<input type="text" class="txt" size="5" name="newidentifier[]">', 'td25'],
			[1,'', 'td25']
		]
	];
</script>
EOT;
		showformheader('magics&operation=admin');
		showtableheader('magics_edit');
		showsubtitle(array('', 'display_order', 'available', 'magic_recommend', 'name', 'description', 'price', 'num', 'type', 'magics_identifier'));
		$magiclist = '';
		$addtype = $typeid ? "WHERE type='".intval($typeid)."'" : '';

		$query = $db->query("SELECT * FROM {$tablepre}magics $addtype ORDER BY displayorder");
		while($magic = $db->fetch_array($query)) {
			$magictype = $lang['magics_type_'.$magic['type']];
			showtablerow('', array('class="td25"', 'class="td28"', 'class="td25"', 'class="td28"', '', 'class="td28"', 'class="td28"', 'class="td23"', 'class="td25"', 'class="td25"'), array(
				"<input type=\"checkbox\" class=\"checkbox\" name=\"delete[]\" value=\"$magic[magicid]\">",
				"<input type=\"text\" class=\"txt\" size=\"3\" name=\"displayorder[$magic[magicid]]\" value=\"$magic[displayorder]\">",
				"<input type=\"checkbox\" class=\"checkbox\" name=\"available[$magic[magicid]]\" value=\"1\" ".(!$magic['name'] || !$magic['identifier'] || !$magic['filename'] ? 'disabled' : ($magic['available'] ? 'checked' : '')).">",
				"<input type=\"checkbox\" class=\"checkbox\" name=\"recommend[$magic[magicid]]\" value=\"1\" ".(!$magic['name'] || !$magic['identifier'] || !$magic['filename'] ? 'disabled' : ($magic['recommend'] ? 'checked' : '')).">",
				"<input type=\"text\" class=\"txt\" size=\"10\" name=\"name[$magic[magicid]]\" value=\"$magic[name]\">",
				"<input type=\"text\" class=\"txt\" size=\"25\" name=\"description[$magic[magicid]]\" value=\"$magic[description]\">",
				"<input type=\"text\" class=\"txt\" size=\"5\" name=\"price[$magic[magicid]]\" value=\"$magic[price]\">",
				"<input type=\"text\" class=\"txt\" size=\"5\" name=\"num[$magic[magicid]]\" value=\"$magic[num]\">",
				"<a href=\"$BASESCRIPT?action=magics&operation=admin&typeid=$magic[type]\">$magictype</a>",
				"<input type=\"hidden\" name=\"identifier[$magic[magicid]]\" value=\"$magic[identifier]\">$magic[identifier]",
				"<a href=\"$BASESCRIPT?action=magics&operation=edit&magicid=$magic[magicid]\" class=\"act\">$lang[detail]</a>"
			));
		}
		echo '<tr><td></td><td colspan="9"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['magics_add'].'</a></div></td></tr>';
		showsubmit('magicsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {
		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM {$tablepre}magics WHERE magicid IN ($ids)");
			$db->query("DELETE FROM {$tablepre}membermagics WHERE magicid IN ($ids)");
			$db->query("DELETE FROM {$tablepre}magicmarket WHERE magicid IN ($ids)");
			$db->query("DELETE FROM {$tablepre}magiclog WHERE magicid IN ($ids)");
		}

		if(is_array($name)) {
			foreach($name as $id =>	$val) {
				$db->query("UPDATE {$tablepre}magics SET available='$available[$id]', name='$name[$id]', identifier='$identifier[$id]', description='$description[$id]', displayorder='$displayorder[$id]', price='$price[$id]', num='$num[$id]', recommend='$recommend[$id]' WHERE magicid='$id'");
			}
		}

		if(is_array($newname)) {

			foreach($newname as $key => $value) {
				$value = dhtmlspecialchars(trim($value));
				$newidentifier = dhtmlspecialchars(trim(strtoupper($newidentifier[$key])));
				if($value) {
					$query = $db->query("SELECT magicid FROM {$tablepre}magics WHERE identifier='$newidentifier[$key]'");
					if($db->num_rows($query)) {
						cpmsg('magics_identifier_invalid', '', 'error');
					}
					$db->query("INSERT INTO {$tablepre}magics (type, name, identifier, description, displayorder, price, num) VALUES ('$newtype[$key]', '$value', '$newidentifier', '$newdescription[$key]', '$newdisplayorder[$key]', '$newprice[$key]', '$newnum[$key]')");
				}
			}
		}

		updatecache('magics');
		cpmsg('magics_data_succeed', $BASESCRIPT.'?action=magics&operation=admin', 'succeed');

	}

} elseif($operation == 'config') {

	if(!submitcheck('magicsubmit')) {

		$settings = array();
		$query = $db->query("SELECT variable, value FROM {$tablepre}settings WHERE variable IN ('magicstatus', 'magicmarket', 'maxmagicprice', 'magicdiscount')");
		while($setting = $db->fetch_array($query)) {
			$settings[$setting['variable']] = $setting['value'];
		}

		shownav('extended', 'magics', 'config');
		showsubmenu('nav_magics', array(
			array('config', 'magics&operation=config', 1),
			array('admin', 'magics&operation=admin', 0),
			array('nav_magics_market', 'magics&operation=market', 0)
		));
		showformheader('magics&operation=config');
		showtableheader();
		showsetting('magics_config_open', 'settingsnew[magicstatus]', $settings['magicstatus'], 'radio');
		showsetting('magics_config_market_open', 'settingsnew[magicmarket]', $settings['magicmarket'], 'radio');
		showsetting('magics_config_market_percent', 'settingsnew[maxmagicprice]', $settings['maxmagicprice'], 'text');
		showsetting('magics_config_discount', 'settingsnew[magicdiscount]', $settings['magicdiscount'], 'text');
		showtablerow('', 'colspan="2"', '<input type="submit" class="btn" name="magicsubmit" value="'.$lang['submit'].'"  />');
		showtablefooter();
		showformfooter();

	} else {

		if(is_array($settingsnew)) {
			foreach($settingsnew as $variable => $value) {
				$db->query("UPDATE {$tablepre}settings SET value='$value' WHERE variable='$variable'");
			}
		}

		updatecache('settings');

		cpmsg('magics_config_succeed', $BASESCRIPT.'?action=magics&operation=config', 'succeed');
	}

} elseif($operation == 'edit') {

	if(!submitcheck('magiceditsubmit')) {

		$magicid = intval($magicid);

		$magic = $db->fetch_first("SELECT * FROM {$tablepre}magics WHERE magicid='$magicid'");

		$magicperm = unserialize($magic['magicperm']);

		$groups = $fourms = array();
		$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups");
		while($group = $db->fetch_array($query)) {
			$groups[$group['groupid']] = $group['grouptitle'];
		}
		$query = $db->query("SELECT fid, name FROM {$tablepre}forums WHERE type NOT IN ('group') AND status='1'");
		while($forum = $db->fetch_array($query)) {
			$forums[$forum['fid']] = $forum['name'];
		}

		$typeselect = array($magic['type'] => 'selected');

		shownav('extended', 'magics', 'admin');
		showsubmenu('nav_magics', array(
			array('config', 'magics&operation=config', 0),
			array('admin', 'magics&operation=admin', 1),
			array('nav_magics_market', 'magics&operation=market', 0)
		));
		showtips('magics_edit_tips');
		showformheader('magics&operation=edit&magicid='.$magicid);
		showtableheader();
		showtitle($lang['magics_edit'].' - '.$magic['name']);
		showsetting('magics_edit_name', 'namenew', $magic['name'], 'text');
		showsetting('magics_edit_identifier', 'identifiernew', $magic['identifier'], 'text');
		showsetting('magics_edit_type', '', '', '<select name="typenew"><option value="1" '.$typeselect[1].'>'.$lang['magics_type_1'].'</option><option value="2" '.$typeselect[2].'>'.$lang['magics_type_2'].'</option><option value="4" '.$typeselect[4].'>'.$lang['magics_type_4'].'</option><option value="3" '.$typeselect[3].'>'.$lang['magics_type_3'].'</option></select>');

		showsetting('magics_edit_price', 'pricenew', $magic['price'], 'text');
		showsetting('magics_edit_num', 'numnew', $magic['num'], 'text');
		showsetting('magics_edit_weight', 'weightnew', $magic['weight'], 'text');
		showsetting('magics_edit_supplytype', array('supplytypenew', array(
			array(0, $lang['magics_goods_stack_none']),
			array(1, $lang['magics_goods_stack_day']),
			array(2, $lang['magics_goods_stack_week']),
			array(3, $lang['magics_goods_stack_month']),
		)), $magic['supplytype'], 'mradio');
		showsetting('magics_edit_supplynum', 'supplynumnew', $magic['supplynum'], 'text');
		showsetting('magics_edit_filename', 'filenamenew', $magic['filename'], 'text');
		showsetting('magics_edit_description', 'descriptionnew', $magic['description'], 'textarea');


		if($magic['type'] == 4) {
			showtablefooter();
			showtableheader('magics_edit_present');
			showsubtitle(array('', 'name', 'num'));
			$presentmagiclist = '';
			$query = $db->query("SELECT magicid, name, weight FROM {$tablepre}magics WHERE type!='4' ORDER BY displayorder");
			while($magic = $db->fetch_array($query)) {
				$num = $magicperm['presentcontent'][$magic['magicid']]['num'];
				$check = $magicperm['presentcontent'][$magic['magicid']] ? 'checked="checked"' : '';
				showtablerow('', array('class="td25"'), array(
				"<input type=\"checkbox\" class=\"checkbox\" name=\"magiccontent[$magic[magicid]]\" value=\"$magic[magicid]\" $check>",
				"$magic[name] <input type=\"hidden\" name=\"weight[$magic[magicid]]\" value=\"$magic[weight]\">",
				"<input type=\"text\" class=\"text\" name=\"getnum[$magic[magicid]]\" value=\"$num\">"
				));
			}
			showtablefooter();
			showtableheader();
		}

		showtitle('magics_edit_perm');
		showtablerow('', 'class="td27"', $lang['magics_edit_usergroupperm'].':<input class="checkbox" type="checkbox" name="chkall1" onclick="checkAll(\'prefix\', this.form, \'usergroupsperm\', \'chkall1\', true)" id="chkall1" /><label for="chkall1"> '.lang('select_all').'</label>');
		showtablerow('', 'colspan="2"', mcheckbox('usergroupsperm', $groups, explode("\t", $magicperm['usergroups'])));

		if($magic['type'] == 2 || $magic['type'] == 3) {
			showtablerow('', 'class="td27"', $lang['magics_edit_targetgroupperm'].':<input class="checkbox" type="checkbox" name="chkall2" onclick="checkAll(\'prefix\', this.form, \'targetgroupsperm\', \'chkall2\', true)" id="chkall2" /><label for="chkall2"> '.lang('select_all').'</label>');
			showtablerow('', 'colspan="2"', mcheckbox('targetgroupsperm', $groups, explode("\t", $magicperm['targetgroups'])));
		}
		if($magic['type'] == 1) {
			showtablerow('', 'class="td27"', $lang['magics_edit_forumperm'].':<input class="checkbox" type="checkbox" name="chkall3" onclick="checkAll(\'prefix\', this.form, \'forumperm\', \'chkall3\', true)" id="chkall3" /><label for="chkall3"> '.lang('select_all').'</label>');
			showtablerow('', 'colspan="2"', mcheckbox('forumperm', $forums, explode("\t", $magicperm['forum'])));
		}
		showsubmit('magiceditsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$namenew	= dhtmlspecialchars(trim($namenew));
		$identifiernew	= dhtmlspecialchars(trim(strtoupper($identifiernew)));
		$descriptionnew	= dhtmlspecialchars($descriptionnew);
		$filenamenew	= dhtmlspecialchars($filenamenew);
		$typenew	= ($typenew > 0 && $typenew <= 4) ? $typenew : 1;
		$availablenew   = !$identifiernew || !$filenamenew ? 0 : 1;

		if($typenew == 4) {
			$magicperm['presentcontent'] = array();
			if(is_array($magiccontent)) {
				foreach($magiccontent as $id => $val) {
					$num = $getnum[$id] ? intval($getnum[$id]) : 1;
					$magicperm['presentcontent'][$id] = array('num' => $num, 'weight' => $weight[$id]);
				}
			} else {
				cpmsg('magics_present_invalid', '', 'error');
			}
		}

		$magicperm['usergroups'] = is_array($usergroupsperm) && !empty($usergroupsperm) ? "\t".implode("\t",$usergroupsperm)."\t" : '';
		$magicperm['targetgroups'] = is_array($targetgroupsperm) && !empty($targetgroupsperm) ? "\t".implode("\t",$targetgroupsperm)."\t" : '';
		$magicperm['forum'] = is_array($forumperm) && !empty($forumperm) ? "\t".implode("\t",$forumperm)."\t" : '';
		$magicpermnew = addslashes(serialize($magicperm));

		$supplytypenew = intval($supplytypenew);
		$supplynumnew = $supplytypenew ? intval($supplynumnew) : 0;

		if(!$namenew) {
			cpmsg('magics_parameter_invalid', '', 'error');
		}

		$query = $db->query("SELECT magicid FROM {$tablepre}magics WHERE identifier='$identifiernew' AND magicid!='$magicid'");
		if($db->num_rows($query)) {
			cpmsg('magics_identifier_invalid', '', 'error');
		}

		if(preg_match("/[\\\\\/\:\*\?\"\<\>\|]+/", $filenamenew)) {
			cpmsg('magics_filename_illegal', '', 'error');
		} elseif(!is_readable(DISCUZ_ROOT.($magicfile = "./include/magic/$filenamenew"))) {
			cpmsg('magics_filename_invalid', '', 'error');
		}

		$db->query("UPDATE {$tablepre}magics SET available='$availablenew', type='$typenew', name='$namenew', identifier='$identifiernew', description='$descriptionnew', price='$pricenew', num='$numnew', supplytype='$supplytypenew', supplynum='$supplynumnew', weight='$weightnew', filename='$filenamenew', magicperm='$magicpermnew' WHERE magicid='$magicid'");

		updatecache('magics');
		cpmsg('magics_data_succeed', $BASESCRIPT.'?action=magics&operation=admin', 'succeed');

	}

} elseif($operation == 'market') {

	if(!submitcheck('marketsubmit')) {

		shownav('extended', 'magics', 'nav_magics_market');
		showsubmenu('nav_magics', array(
			array('config', 'magics&operation=config', 0),
			array('admin', 'magics&operation=admin', 0),
			array('nav_magics_market', 'magics&operation=market', 1)
		));
		showformheader('magics&operation=market');
		showtableheader('magics_market');
		showsubtitle(array('', 'name', 'description', 'magics_market_seller', 'price', 'num', 'weight'));

		$marketlist = '';
		$query = $db->query("SELECT ma.*, m.name, m.description, m.weight FROM {$tablepre}magicmarket ma, {$tablepre}magics m WHERE m.magicid=ma.magicid");
		while($market = $db->fetch_array($query)) {
			$market['weight'] = $market['weight'] * $market['num'];
			showtablerow('', array('class="td25"', 'class="bold"', '', 'class="td28"', 'class="td28"'), array(
				"<input type=\"checkbox\" class=\"checkbox\" name=\"delete[]\" value=\"$market[mid]\">",
				$market[name],
				$market[description],
				$market[username],
				"<input type=\"text\" class=\"txt\" size=\"5\" name=\"price[$market[mid]]\" value=\"$market[price]\">",
				"<input type=\"text\" class=\"txt\" size=\"5\" name=\"num[$market[mid]]\" value=\"$market[num]\">",
				$market[weight]
			));
		}

		showsubmit('marketsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM {$tablepre}magicmarket WHERE mid IN ($ids)");
		}

		if(is_array($price)) {
			foreach($price as $id => $val) {
				$db->query("UPDATE {$tablepre}magicmarket SET price='$price[$id]', num='$num[$id]' WHERE mid='$id'");
			}
		}

		cpmsg('magics_data_succeed', $BASESCRIPT.'?action=magics&operation=market', 'succeed');

	}

}

?>