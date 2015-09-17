<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: usergroups.inc.php 20965 2009-11-04 06:37:32Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!$operation) {

	if(!submitcheck('groupsubmit')) {

		$sgroups = $smembers = array();
		$sgroupids = '0';
		$smembernum = $membergroup = $specialgroup = $sysgroup = $membergroupoption = $specialgroupoption = '';

		$query = $db->query("SELECT groupid, radminid, type, grouptitle, creditshigher, creditslower, stars, color, groupavatar FROM {$tablepre}usergroups ORDER BY creditshigher");
		while($group = $db->fetch_array($query)) {
			if($group['type'] == 'member') {

				$membergroupoption .= "<option value=\"g{$group[groupid]}\">$group[grouptitle]</option>";

				$membergroup .= showtablerow('', array('class="td25"', '', 'class="td28"', 'class=td28'), array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[$group[groupid]]\" value=\"$group[groupid]\">",
					"<input type=\"text\" class=\"txt\" size=\"12\" name=\"groupnew[$group[groupid]][grouptitle]\" value=\"$group[grouptitle]\">",
					"<input type=\"text\" class=\"txt\" size=\"6\" name=\"groupnew[$group[groupid]][creditshigher]\" value=\"$group[creditshigher]\" /> ~ <input type=\"text\" class=\"txt\" size=\"6\" name=\"groupnew[$group[groupid]][creditslower]\" value=\"$group[creditslower]\" disabled />",
					"<input type=\"text\" class=\"txt\" size=\"2\" name=\"groupnew[$group[groupid]][stars]\" value=\"$group[stars]\">",
					"<input type=\"text\" class=\"txt\" size=\"6\" name=\"groupnew[$group[groupid]][color]\" value=\"$group[color]\">",
					"<input type=\"text\" class=\"txt\" size=\"12\" name=\"groupnew[$group[groupid]][groupavatar]\" value=\"$group[groupavatar]\">",
					"<a href=\"$BASESCRIPT?action=usergroups&operation=edit&id=$group[groupid]\" class=\"act\">$lang[detail]</a>"
				), TRUE);
			} elseif($group['type'] == 'system') {
				$sysgroup .= showtablerow('', array('', '', 'class="td28"'), array(
					"<input type=\"text\" class=\"txt\" size=\"12\" name=\"group_title[$group[groupid]]\" value=\"$group[grouptitle]\">",
					$lang['usergroups_system_'.$group['groupid']],
					"<input type=\"text\" class=\"txt\" size=\"2\"name=\"group_stars[$group[groupid]]\" value=\"$group[stars]\">",
					"<input type=\"text\" class=\"txt\" size=\"6\"name=\"group_color[$group[groupid]]\" value=\"$group[color]\">",
					"<input type=\"text\" class=\"txt\" size=\"12\" name=\"group_avatar[$group[groupid]]\" value=\"$group[groupavatar]\">",
					"<a href=\"$BASESCRIPT?action=usergroups&operation=edit&id=$group[groupid]\" class=\"act\">$lang[detail]</a>"
				), TRUE);
			} elseif($group['type'] == 'special' && $group['radminid'] == '0') {

				$specialgroupoption .= "<option value=\"g{$group[groupid]}\">$group[grouptitle]</option>";

				$sgroups[] = $group;
				$sgroupids .= ','.$group['groupid'];
			}
		}

		$projectselect = $membergroupoption.'<option value=""> ------------ </option>';
		$specialprojectselect = $specialgroupoption.'<option value=""> ------------ </option>';
		$project = array();
		$query = $db->query("SELECT id, name FROM {$tablepre}projects WHERE type='group'");
		while($project = $db->fetch_array($query)) {
			$projectselect = $specialprojectselect .= '<option value="'.$project['id'].'">'.$project['name'].'</option>';
		}

		foreach($sgroups as $group) {
			if(is_array($smembers[$group['groupid']])) {
				$num = count($smembers[$group['groupid']]);
				$specifiedusers = implode('', $smembers[$group['groupid']]).($num > $smembernum[$group['groupid']] ? '<br /><div style="float: right; clear: both; margin:5px"><a href="'.$BASESCRIPT.'?action=members&submit=yes&usergroupid[]='.$group['groupid'].'" style="text-align: right;">'.$lang['more'].'&raquo;</a>&nbsp;</div>' : '<br /><br/>');
				unset($smembers[$group['groupid']]);
			} else {
				$specifiedusers = '';
				$num = 0;
			}
			$specifiedusers = "<style>#specifieduser span{width: 9em; height: 2em; float: left; overflow: hidden; margin: 2px;}</style><div id=\"specifieduser\">$specifiedusers</div>";

			$specialgroup .= showtablerow('', array('class="td25"', '', 'class="td28"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[$group[groupid]]\" value=\"$group[groupid]\">",
				"<input type=\"text\" class=\"txt\" size=\"12\" name=\"group_title[$group[groupid]]\" value=\"$group[grouptitle]\">",
				"<input type=\"text\" class=\"txt\" size=\"2\"name=\"group_stars[$group[groupid]]\" value=\"$group[stars]\">",
				"<input type=\"text\" class=\"txt\" size=\"6\"name=\"group_color[$group[groupid]]\" value=\"$group[color]\">",
				"<input type=\"text\" class=\"txt\" size=\"12\" name=\"group_avatar[$group[groupid]]\" value=\"$group[groupavatar]\">",
				"<a href=\"$BASESCRIPT?action=usergroups&operation=viewsgroup&sgroupid=$group[groupid]\" onclick=\"ajaxget(this.href, 'sgroup_$group[groupid]', 'sgroup_$group[groupid]', 'auto');doane(event);\" class=\"act\">$lang[view]</a>",
				"<a href=\"$BASESCRIPT?action=usergroups&operation=edit&id=$group[groupid]\" class=\"act\">$lang[detail]</a>"
			), TRUE);
			$specialgroup .= showtablerow('', array('', 'colspan="6" id="sgroup_'.$group['groupid'].'" style="display: none"'), array(
				'',
				''
			), TRUE);
		}

		echo <<<EOT
<script type="text/JavaScript">
var rowtypedata = [
	[
		[1,'', 'td25'],
		[1,'<input type="text" class="txt" size="12" name="groupnewadd[grouptitle][]">'],
		[1,'<input type="text" class="txt" size="6" name="groupnewadd[creditshigher][]">', 'td28'],
		[1,'<input type="text" class="txt" size="2" name="groupnewadd[stars][]">', 'td28'],
		[4,'<select name="groupnewadd[projectid][]"><option value="">$lang[usergroups_project]</option><option value="0">------------</option>$projectselect</select>']
	],
	[
		[1,'', 'td25'],
		[1,'<input type="text" class="txt" size="12" name="grouptitlenewadd[]">'],
		[1,'<input type="text" class="txt" size="2" name="starsnewadd[]">', 'td28'],
		[1,'<input type="text" class="txt" size="6" name="colornewadd[]">'],
		[1,'<select name="groupnewaddproject[]"><option value="">$lang[usergroups_project]</option><option value="0">------------</option>$specialprojectselect</select>'],
		[2, '']
	]
];
</script>
EOT;
		shownav('user', 'nav_usergroups');
		showsubmenuanchors('nav_usergroups', array(
			array('usergroups_member', 'membergroups', !$type || $type == 'member'),
			array('usergroups_special', 'specialgroups', $type == 'special'),
			array('usergroups_system', 'systemgroups', $type == 'system')
		));
		showtips('usergroups_tips');

		showformheader('usergroups&type=member');
		showtableheader('usergroups_member', 'fixpadding', 'id="membergroups"'.($type && $type != 'member' ? ' style="display: none"' : ''));
		showsubtitle(array('', 'usergroups_title', 'usergroups_creditsrange', 'usergroups_stars', 'usergroups_color', 'usergroups_avatar', ''));
		echo $membergroup;
		echo '<tr><td>&nbsp;</td><td colspan="8"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['usergroups_add'].'</a></div></td></tr>';
		showsubmit('groupsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

		showformheader('usergroups&type=special');
		showtableheader('usergroups_special', 'fixpadding', 'id="specialgroups"'.($type != 'special' ? ' style="display: none"' : ''));
		showsubtitle(array('', 'usergroups_title', 'usergroups_stars', 'usergroups_color', 'usergroups_avatar', '', ''));
		echo $specialgroup;
		echo '<tr><td>&nbsp;</td><td colspan="8"><div><a href="###" onclick="addrow(this, 1)" class="addtr">'.$lang['usergroups_sepcial_add'].'</a></div></td></tr>';
		showsubmit('groupsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

		showformheader('usergroups&type=system');
		showtableheader('usergroups_system', 'fixpadding', 'id="systemgroups"'.($type != 'system' ? ' style="display: none"' : ''));
		showsubtitle(array('usergroups_title', 'usergroups_status', 'usergroups_stars', 'usergroups_color', 'usergroups_avatar', ''));
		echo $sysgroup;
		showsubmit('groupsubmit');
		showtablefooter();
		showformfooter();

	} else {

		if(empty($type) || !in_array($type, array('member', 'special', 'system'))) {
			cpmsg('usergroups_type_nonexistence');
		}

		$oldgroups = $extadd = array();
		$query = $db->query("SELECT * FROM {$tablepre}usergroups WHERE `type`='$type'");
		while ($gp = $db->fetch_array($query)) {
			$oldgroups[$gp['groupid']] = $gp;
		}

		$sqladd = '';
		$query = $db->query("SELECT * FROM {$tablepre}projects WHERE type='group'");
		while($project = $db->fetch_array($query)) {
			$sqladd = '';
			$project['value'] = unserialize($project['value']);
			foreach($project['value'] as $k=>$v) {
				$v = addslashes($v);
				$k = addslashes($k);
				$sqladd .= ",`$k`='$v'";
			}
			$project['sqladd'] = $sqladd;
			$extadd[$project['id']] = $project['sqladd'];
		}

		foreach($oldgroups as $id => $vals) {
			$sqladd = '';
			foreach($vals as $k => $v) {
				$v = addslashes($v);
				if(!in_array($k, array('groupid', 'radminid', 'type', 'system', 'grouptitle', 'creditshigher', 'creditslower', 'stars', 'color', 'groupavatar'))) {
					$sqladd .= ",`$k`='$v'";
				}
			}
			$extadd['g'.$id] = $sqladd;
		}

		if($type == 'member') {
			$groupnewadd = array_flip_keys($groupnewadd);
			foreach($groupnewadd as $k => $v) {
				if(!$v['grouptitle'] || !$v['creditshigher']) {
					unset($groupnewadd[$k]);
				}
			}
			$groupnewkeys = array_keys($groupnew);
			$maxgroupid = max($groupnewkeys);
			foreach($groupnewadd as $k=>$v) {
				$groupnew[$k+$maxgroupid+1] = $v;
			}
			$orderarray = array();
			if(is_array($groupnew)) {
				foreach($groupnew as $id => $group) {
					if((is_array($delete) && in_array($id, $delete)) || ($id == 0 && (!$group['grouptitle'] || $group['creditshigher'] == ''))) {
						unset($groupnew[$id]);
					} else {
						$orderarray[$group['creditshigher']] = $id;
					}
				}
			}

			if(empty($orderarray[0]) || min(array_flip($orderarray)) >= 0) {
				cpmsg('usergroups_update_credits_invalid', '', 'error');
			}

			ksort($orderarray);
			$rangearray = array();
			$lowerlimit = array_keys($orderarray);
			for($i = 0; $i < count($lowerlimit); $i++) {
				$rangearray[$orderarray[$lowerlimit[$i]]] = array
					(
					'creditshigher' => isset($lowerlimit[$i - 1]) ? $lowerlimit[$i] : -999999999,
					'creditslower' => isset($lowerlimit[$i + 1]) ? $lowerlimit[$i + 1] : 999999999
					);
			}

			foreach($groupnew as $id => $group) {
				$creditshighernew = $rangearray[$id]['creditshigher'];
				$creditslowernew = $rangearray[$id]['creditslower'];
				if($creditshighernew == $creditslowernew) {
					cpmsg('usergroups_update_credits_duplicate', '', 'error');
				}
				if(in_array($id, $groupnewkeys)) {
					$db->query("UPDATE {$tablepre}usergroups SET grouptitle='$group[grouptitle]', creditshigher='$creditshighernew', creditslower='$creditslowernew', stars='$group[stars]', color='$group[color]', groupavatar='$group[groupavatar]' WHERE groupid='$id' AND type='member'");
				} elseif($group['grouptitle'] && $group['creditshigher'] != '') {
					$sqladd = !empty($group['projectid']) && !empty($extadd[$group['projectid']]) ? $extadd[$group['projectid']] : '';
					$db->query("INSERT INTO {$tablepre}usergroups SET grouptitle='$group[grouptitle]', creditshigher='$creditshighernew', creditslower='$creditslowernew', stars='$group[stars]' $sqladd");
					if($sqladd) {
						$newgid = $db->insert_id();
						$query = $db->query("SELECT fid, viewperm, postperm, replyperm, getattachperm, postattachperm FROM {$tablepre}forumfields");
						while($row = $db->fetch_array($query)) {
							$upforumperm = array();
							$projectid = substr($group['projectid'], 1);
							if($row['viewperm'] && in_array($projectid, explode("\t", $row['viewperm']))) {
								$upforumperm[] = "viewperm='$row[viewperm]$newgid\t'";
							}
							if($row['postperm'] && in_array($projectid, explode("\t", $row['postperm']))) {
								$upforumperm[] = "postperm='$row[postperm]$newgid\t'";
							}
							if($row['replyperm'] && in_array($projectid, explode("\t", $row['replyperm']))) {
								$upforumperm[] = "replyperm='$row[replyperm]$newgid\t'";
							}
							if($row['getattachperm'] && in_array($projectid, explode("\t", $row['getattachperm']))) {
								$upforumperm[] = "getattachperm='$row[getattachperm]$newgid\t'";
							}
							if($row['postattachperm'] && in_array($projectid, explode("\t", $row['postattachperm']))) {
								$upforumperm[] = "postattachperm='$row[postattachperm]$newgid\t'";
							}
							if($upforumperm) {
								$db->query("UPDATE {$tablepre}forumfields SET ".implode(',', $upforumperm)." WHERE fid='$row[fid]'");
							}
						}
					}
				}
			}

			if($ids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}usergroups WHERE groupid IN ($ids) AND type='member'");
				deletegroupcache($delete);
			}

		} elseif($type == 'special') {
			if(is_array($grouptitlenewadd)) {
				foreach($grouptitlenewadd as $k => $v) {
					if($v) {
						$sqladd = !empty($groupnewaddproject[$k]) && !empty($extadd[$groupnewaddproject[$k]]) ? $extadd[$groupnewaddproject[$k]] : '';
						$db->query("INSERT INTO {$tablepre}usergroups SET type='special', grouptitle='$grouptitlenewadd[$k]', color='$colornewadd[$k]', stars='$starsnewadd[$k]' $sqladd");
					}
				}
			}

			if(is_array($group_title)) {
				foreach($group_title as $id => $title) {
					if(!$delete[$id]) {
						$db->query("UPDATE {$tablepre}usergroups SET grouptitle='$group_title[$id]', stars='$group_stars[$id]', color='$group_color[$id]', groupavatar='$group_avatar[$id]' WHERE groupid='$id'");
					}
				}
			}

			if($ids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}usergroups WHERE groupid IN ($ids) AND type='special'");
				$db->query("DELETE FROM {$tablepre}admingroups WHERE admingid IN ($ids)");
				$db->query("DELETE FROM {$tablepre}adminactions WHERE admingid IN ($ids)");
				$newgroupid = $db->result_first("SELECT groupid FROM {$tablepre}usergroups WHERE type='member' AND creditslower>'0' ORDER BY creditslower LIMIT 1");
				$db->query("UPDATE {$tablepre}members SET groupid='$newgroupid', adminid='0' WHERE groupid IN ($ids)", 'UNBUFFERED');
				deletegroupcache($delete);
			}

		} elseif($type == 'system') {
			if(is_array($group_title)) {
				foreach($group_title as $id => $title) {
					$db->query("UPDATE {$tablepre}usergroups SET grouptitle='$group_title[$id]', stars='$group_stars[$id]', color='$group_color[$id]', groupavatar='$group_avatar[$id]' WHERE groupid='$id'");
				}
			}
		}

		updatecache('usergroups');
		cpmsg('usergroups_update_succeed', $BASESCRIPT.'?action=usergroups&type='.$type, 'succeed');
	}

} elseif($operation == 'viewsgroup') {

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}members WHERE groupid='$sgroupid'");
	$query = $db->query("SELECT uid, username FROM {$tablepre}members WHERE groupid='$sgroupid' LIMIT 80");
	$sgroups = '';
	while($member = $db->fetch_array($query)) {
		$sgroups .= '<li><a href="space.php?uid='.$member['uid'].'" target="_blank">'.$member['username'].'</a></li>';
	}
	ajaxshowheader();
	echo '<ul class="userlist"><li class="unum">'.$lang['usernum'].$num.($num > 80 ? '&nbsp;<a href="'.$BASESCRIPT.'?action=members&submit=yes&usergroupid[]='.$sgroupid.'">'.$lang['more'].'&raquo;</a>' : '').'</li>'.$sgroups.'</ul>';
	ajaxshowfooter();
	exit;

} elseif($operation == 'edit') {

	$return = isset($return) && $return ? 'admin' : '';

	if(empty($id)) {
		$grouplist = "<select name=\"id\" style=\"width:150px\">\n";
		$conditions = !empty($anchor) && $anchor == 'system' ? "WHERE type='special'" : '';
		$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups $conditions");
		while($group = $db->fetch_array($query)) {
			$grouplist .= "<option value=\"$group[groupid]\">$group[grouptitle]</option>\n";
		}
		$grouplist .= '</select>';
		cpmsg('usergroups_edit_nonexistence', $BASESCRIPT.'?action=usergroups&operation=edit'.(!empty($highlight) ? "&highlight=$highlight" : '').(!empty($highlight) ? "&anchor=$anchor" : ''), 'form', $grouplist);
	}

	$group = $db->fetch_first("SELECT * FROM {$tablepre}usergroups WHERE groupid='$id'");

	if($group['radminid'] > 0 && !isfounder()) {
		if(!checkacpaction('admingroups', '', false)) {
			cpmsg('usergroups_edit_fail', '', 'error');
		}
	}

	$group['allowthreadplugin'] = $threadplugins ? unserialize($db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='allowthreadplugin'")) : array();

	if(!submitcheck('detailsubmit') && !submitcheck('saveconfigsubmit')) {

		$projectselect = "<select name=\"projectid\" onchange=\"window.location='$BASESCRIPT?action=usergroups&operation=edit&id=$id&projectid='+this.options[this.options.selectedIndex].value\"><option value=\"0\" selected=\"selected\">".$lang['none']."</option>";
		$query = $db->query("SELECT id, name FROM {$tablepre}projects WHERE type='group'");
		while($project = $db->fetch_array($query)) {
			$projectselect .= "<option value=\"$project[id]\" ".($project['id'] == $projectid ? 'selected="selected"' : NULL).">$project[name]</option>";
		}
		$projectselect .= '</select>';

		if(!empty($projectid)) {
			$group = @array_merge($group, unserialize($db->result_first("SELECT value FROM {$tablepre}projects WHERE id='$projectid'")));
		}

		$group['exempt'] = strrev(sprintf('%0'.strlen($group['exempt']).'b', $group['exempt']));

		$query = $db->query("SELECT type, groupid, grouptitle, radminid FROM {$tablepre}usergroups ORDER BY (creditshigher<>'0' || creditslower<>'0'), creditslower, groupid");
		$grouplist = array();
		while($ggroup = $db->fetch_array($query)) {
			$ggroup['type'] = $ggroup['type'] == 'special' && $ggroup['radminid'] ? 'specialadmin' : $ggroup['type'];
			$grouplist[$ggroup['type']] .= '<a href="###" onclick="location.href=\''.$BASESCRIPT.'?action=usergroups&operation=edit&switch=yes&id='.$ggroup['groupid'].'&anchor=\'+currentAnchor+\'&scrolltop=\'+document.documentElement.scrollTop"'.($id == $ggroup['groupid'] ? ' class="current"' : '').'>'.$ggroup['grouptitle'].'</a>';
		}
		$gselect = '<span id="ugselect" class="right popupmenu_dropmenu" onmouseover="showMenu({\'ctrlid\':this.id,\'pos\':\'34\'});$(\'ugselect_menu\').style.top=(parseInt($(\'ugselect_menu\').style.top)-document.documentElement.scrollTop)+\'px\'">'.$lang['usergroups_switch'].'<em>&nbsp;&nbsp;</em></span>'.
			'<div id="ugselect_menu" class="popupmenu_popup" style="display:none">'.
			'<em>'.$lang['usergroups_member'].'</em>'.$grouplist['member'].'<br />'.
			($grouplist['special'] ? '<em>'.$lang['usergroups_special'].'</em>'.$grouplist['special'].'<br />' : '').
			($grouplist['specialadmin'] ? '<em>'.$lang['usergroups_specialadmin'].'</em>'.$grouplist['specialadmin'].'<br />' : '').
			'<em>'.$lang['usergroups_system'].'</em>'.$grouplist['system'].'</div>';

		$anchor = in_array($anchor, array('basic', 'system', 'special', 'post', 'attach', 'magic', 'invite', 'credit')) ? $anchor : 'basic';
		showsubmenuanchors(lang('usergroups_edit').' - '.$group['grouptitle'], array(
			array('usergroups_edit_basic', 'basic', $anchor == 'basic'),
			$group['type'] == 'special' && $group['radminid'] < 1 ? array('usergroups_edit_system', 'system', $anchor == 'system') : array(),
			array('usergroups_edit_special', 'special', $anchor == 'special'),
			array('usergroups_edit_post', 'post', $anchor == 'post'),
			array('usergroups_edit_attach', 'attach', $anchor == 'attach'),
			array('usergroups_edit_magic', 'magic', $anchor == 'magic'),
			array('usergroups_edit_invite', 'invite', $anchor == 'invite'),
			array('usergroups_edit_credit', 'credit', $anchor == 'credit')
		), $gselect);
		if(!empty($switch)) {
			echo '<script type="text/javascript">showMenu({\'ctrlid\':\'ugselect\',\'pos\':\'34\'});</script>';
		}

		if($group['type'] == 'special' && $group['radminid'] < 1) {
			showtips('usergroups_edit_system_tips', 'system_tips', $anchor == 'system');
		}

		showtips('usergroups_edit_magic_tips', 'magic_tips', $anchor == 'magic');
		showtips('usergroups_edit_invite_tips', 'invite_tips', $anchor == 'invite');
		showformheader("usergroups&operation=edit&id=$id&return=$return");
		showtableheader();

		if($group['type'] == 'special' && $group['radminid'] < 1) {
			showtagheader('tbody', 'system', $anchor == 'system');
			if($group['system'] == 'private') {
				$system = array('public' => 0, 'dailyprice' => 0, 'minspan' => 0);
			} else {
				$system = array('public' => 1, 'dailyprice' => 0, 'minspan' => 0);
				list($system['dailyprice'], $system['minspan']) = explode("\t", $group['system']);
			}
			showsetting('usergroups_edit_system_public', 'system_publicnew', $system['public'], 'radio');
			showsetting('usergroups_edit_system_dailyprice', 'system_dailypricenew', $system['dailyprice'], 'text');
			showsetting('usergroups_edit_system_minspan', 'system_minspannew', $system['minspan'], 'text');
			showtagfooter('tbody');
		}

		showtagheader('tbody', 'basic', $anchor == 'basic');
		showtitle('usergroups_edit_basic');
		showsetting('usergroups_edit_basic_title', 'grouptitlenew', $group['grouptitle'], 'text');
		showsetting('usergroups_edit_basic_scheme', '', '', $projectselect);

		if(in_array($group['groupid'], array(1, 7))) {
			echo '<input type="hidden" name="allowvisitnew" value="1">';
		} else {
			showsetting('usergroups_edit_basic_visit', 'allowvisitnew', $group['allowvisit'], 'radio');
		}

		showsetting('usergroups_edit_basic_read_access', 'readaccessnew', $group['readaccess'], 'text');
		showsetting('usergroups_edit_basic_view_profile', 'allowviewpronew', $group['allowviewpro'], 'radio');
		showsetting('usergroups_edit_basic_view_stats', 'allowviewstatsnew', $group['allowviewstats'], 'radio');
		showsetting('usergroups_edit_basic_invisible', 'allowinvisiblenew', $group['allowinvisible'], 'radio');
		showsetting('usergroups_edit_basic_multigroups', 'allowmultigroupsnew', $group['allowmultigroups'], 'radio');
		showsetting('usergroups_edit_basic_allowtransfer', 'allowtransfernew', $group['allowtransfer'], 'radio');
		showsetting('usergroups_edit_basic_allowsendpm', 'allowsendpmnew', $group['allowsendpm'], 'radio');
		showsetting('usergroups_edit_basic_search', array('allowsearchnew', array(
			array(0, $lang['usergroups_edit_basic_search_disable']),
			array(1, $lang['usergroups_edit_basic_search_thread']),
			array(2, $lang['usergroups_edit_basic_search_post'])
		)), $group['allowsearch'], 'mradio');
		showsetting('usergroups_edit_basic_reasonpm', array('reasonpmnew', array(
			array(0, $lang['usergroups_edit_basic_reasonpm_none']),
			array(1, $lang['usergroups_edit_basic_reasonpm_reason']),
			array(2, $lang['usergroups_edit_basic_reasonpm_pm']),
			array(3, $lang['usergroups_edit_basic_reasonpm_both'])
		)), $group['reasonpm'], 'mradio');
		showsetting('usergroups_edit_basic_nickname', 'allownicknamenew', $group['allownickname'], 'radio');
		showsetting('usergroups_edit_basic_cstatus', 'allowcstatusnew', $group['allowcstatus'], 'radio');
		showsetting('usergroups_edit_basic_disable_periodctrl', 'disableperiodctrlnew', $group['disableperiodctrl'], 'radio');
		showsetting('usergroups_edit_basic_hour_posts', 'maxpostsperhournew', $group['maxpostsperhour'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'special', $anchor == 'special');
		showtitle('usergroups_edit_special');
		showsetting('usergroups_edit_special_activity', 'allowpostactivitynew', $group['allowpostactivity'], 'radio');
		showsetting('usergroups_edit_special_poll', 'allowpostpollnew', $group['allowpostpoll'], 'radio');
		showsetting('usergroups_edit_special_vote', 'allowvotenew', $group['allowvote'], 'radio');
		showsetting('usergroups_edit_special_reward', 'allowpostrewardnew', $group['allowpostreward'], 'radio');
		showsetting('usergroups_edit_special_reward_min', 'minrewardpricenew', $group['minrewardprice'], "text");
		showsetting('usergroups_edit_special_reward_max', 'maxrewardpricenew', $group['maxrewardprice'], "text");
		showsetting('usergroups_edit_special_trade', 'allowposttradenew', $group['allowposttrade'], 'radio');
		showsetting('usergroups_edit_special_trade_min', 'mintradepricenew', $group['mintradeprice'], "text");
		showsetting('usergroups_edit_special_trade_max', 'maxtradepricenew', $group['maxtradeprice'], "text");
		showsetting('usergroups_edit_special_trade_stick', 'tradesticknew', $group['tradestick'], "text");
		showsetting('usergroups_edit_special_debate', 'allowpostdebatenew', $group['allowpostdebate'], "radio");
		showsetting('usergroups_edit_special_rushreply', 'allowpostrushreplynew', $group['allowpostrushreply'], "radio");
		$threadpluginselect = '';
		if(is_array($threadplugins)) foreach($threadplugins as $tpid => $data) {
			$threadpluginselect .= '<input class="checkbox" type="checkbox" name="allowthreadpluginnew[]" value="'.$tpid.'" '.(@in_array($tpid, $group['allowthreadplugin'][$id]) ? 'checked' : '').'> '.$data['name'].'<br />';
		}
		if($threadpluginselect) {
			showsetting('usergroups_edit_special_allowthreadplugin', '', '', $threadpluginselect);
		}
		showtagfooter('tbody');

		showtagheader('tbody', 'post', $anchor == 'post');
		showtitle('usergroups_edit_post');
		showsetting('usergroups_edit_post_new', 'allowpostnew', $group['allowpost'], 'radio');
		showsetting('usergroups_edit_post_reply', 'allowreplynew', $group['allowreply'], 'radio');
		showsetting('usergroups_edit_post_direct', array('allowdirectpostnew', array(
			array(0, $lang['usergroups_edit_post_direct_none']),
			array(1, $lang['usergroups_edit_post_direct_reply']),
			array(2, $lang['usergroups_edit_post_direct_thread']),
			array(3, $lang['usergroups_edit_post_direct_all'])
		)), $group['allowdirectpost'], 'mradio');
		showsetting('usergroups_edit_post_url', array('allowposturlnew', array(
			array(0, $lang['usergroups_edit_post_url_banned']),
			array(1, $lang['usergroups_edit_post_url_mod']),
			array(2, $lang['usergroups_edit_post_url_unhandle']),
			array(3, $lang['usergroups_edit_post_url_enable'])
		)), $group['allowposturl'], 'mradio');
		showsetting('usergroups_edit_post_anonymous', 'allowanonymousnew', $group['allowanonymous'], 'radio');
		showsetting('usergroups_edit_post_set_read_perm', 'allowsetreadpermnew', $group['allowsetreadperm'], 'radio');
		showsetting('usergroups_edit_post_maxprice', 'maxpricenew', $group['maxprice'], 'text');
		showsetting('usergroups_edit_post_hide_code', 'allowhidecodenew', $group['allowhidecode'], 'radio');
		showsetting('usergroups_edit_post_html', 'allowhtmlnew', $group['allowhtml'], 'radio');
		showsetting('usergroups_edit_post_custom_bbcode', 'allowcusbbcodenew', $group['allowcusbbcode'], 'radio');
		showsetting('usergroups_edit_post_bio_bbcode', 'allowbiobbcodenew', $group['allowbiobbcode'], 'radio');
		showsetting('usergroups_edit_post_bio_img_code', 'allowbioimgcodenew', $group['allowbioimgcode'], 'radio');
		showsetting('usergroups_edit_post_max_bio_size', 'maxbiosizenew', $group['maxbiosize'], 'text');
		showsetting('usergroups_edit_post_sig_bbcode', 'allowsigbbcodenew', $group['allowsigbbcode'], 'radio');
		showsetting('usergroups_edit_post_sig_img_code', 'allowsigimgcodenew', $group['allowsigimgcode'], 'radio');
		showsetting('usergroups_edit_post_max_sig_size', 'maxsigsizenew', $group['maxsigsize'], 'text');
		if($group['groupid'] != 7) {
			showsetting('usergroups_edit_post_recommend', 'allowrecommendnew', $group['allowrecommend'], 'text');
		}
		showsetting('usergroups_edit_post_edit_time_limit', 'edittimelimitnew', $group['edittimelimit'], 'text');
		showtagfooter('tbody');

		$group['maxattachsize'] = intval($group['maxattachsize'] / 1024);
		$group['maxsizeperday'] = intval($group['maxsizeperday'] / 1024);

		showtagheader('tbody', 'attach', $anchor == 'attach');
		showtitle('usergroups_edit_attach');
		showsetting('usergroups_edit_attach_get', 'allowgetattachnew', $group['allowgetattach'], 'radio');
		showsetting('usergroups_edit_attach_post', 'allowpostattachnew', $group['allowpostattach'], 'radio');
		showsetting('usergroups_edit_attach_set_perm', 'allowsetattachpermnew', $group['allowsetattachperm'], 'radio');
		showsetting('usergroups_edit_attach_max_size', 'maxattachsizenew', $group['maxattachsize'], 'text');
		showsetting('usergroups_edit_attach_max_size_per_day', 'maxsizeperdaynew', $group['maxsizeperday'], 'text');
		showsetting('usergroups_edit_attach_max_number_per_day', 'maxattachnumnew', $group['maxattachnum'], 'text');
		showsetting('usergroups_edit_attach_ext', 'attachextensionsnew', $group['attachextensions'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'magic', $anchor == 'magic');
		showtitle('usergroups_edit_magic');
		showsetting('usergroups_edit_magic_permission', array('allowmagicsnew', array(
			array(0, $lang['usergroups_edit_magic_unallowed']),
			array(1, $lang['usergroups_edit_magic_allow']),
			array(2, $lang['usergroups_edit_magic_allow_and_pass'])
		)), $group['allowmagics'], 'mradio');
		showsetting('usergroups_edit_magic_discount', 'magicsdiscountnew', $group['magicsdiscount'], 'text');
		showsetting('usergroups_edit_magic_max', 'maxmagicsweightnew', $group['maxmagicsweight'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'invite', $anchor == 'invite');
		showtitle('usergroups_edit_invite');
		showsetting('usergroups_edit_invite_permission', 'allowinvitenew', $group['allowinvite'], 'radio');
		showsetting('usergroups_edit_invite_send_permission', 'allowmailinvitenew', $group['allowmailinvite'], 'radio');
		showsetting('usergroups_edit_invite_price', 'invitepricenew', $group['inviteprice'], 'text');
		showsetting('usergroups_edit_invite_buynum', 'maxinvitenumnew', $group['maxinvitenum'], 'text');
		showsetting('usergroups_edit_invite_maxinviteday', 'maxinvitedaynew', $group['maxinviteday'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'credit', $anchor == 'credit');
		showtitle('usergroups_edit_credit');
		showsetting('usergroups_edit_credit_exempt_sendpm', 'exemptnew[0]', $group['exempt'][0], 'radio');
		showsetting('usergroups_edit_credit_exempt_search', 'exemptnew[1]', $group['exempt'][1], 'radio');
		if($group['radminid']) {
			if($group['radminid'] == 3) {
				showsetting($lang['usergroups_edit_credit_exempt_outperm'].$lang['usergroups_edit_credit_exempt_getattch'], 'exemptnew[2]', $group['exempt'][2], 'radio');
				showsetting($lang['usergroups_edit_credit_exempt_inperm'].$lang['usergroups_edit_credit_exempt_getattch'], 'exemptnew[5]', $group['exempt'][5], 'radio');
				showsetting($lang['usergroups_edit_credit_exempt_outperm'].$lang['usergroups_edit_credit_exempt_attachpay'], 'exemptnew[3]', $group['exempt'][3], 'radio');
				showsetting($lang['usergroups_edit_credit_exempt_inperm'].$lang['usergroups_edit_credit_exempt_attachpay'], 'exemptnew[6]', $group['exempt'][6], 'radio');
				showsetting($lang['usergroups_edit_credit_exempt_outperm'].$lang['usergroups_edit_credit_exempt_threadpay'], 'exemptnew[4]', $group['exempt'][4], 'radio');
				showsetting($lang['usergroups_edit_credit_exempt_inperm'].$lang['usergroups_edit_credit_exempt_threadpay'], 'exemptnew[7]', $group['exempt'][7], 'radio');
			} else {
				echo '<input name="exemptnew[2]" type="hidden" value="1" /><input name="exemptnew[3]" type="hidden" value="1" /><input name="exemptnew[4]" type="hidden" value="1" />'.
					'<input name="exemptnew[5]" type="hidden" value="1" /><input name="exemptnew[6]" type="hidden" value="1" /><input name="exemptnew[7]" type="hidden" value="1" />';
			}
		} else {
			showsetting('usergroups_edit_credit_exempt_getattch', 'exemptnew[2]', $group['exempt'][2], 'radio');
			showsetting('usergroups_edit_credit_exempt_attachpay', 'exemptnew[3]', $group['exempt'][3], 'radio');
			showsetting('usergroups_edit_credit_exempt_threadpay', 'exemptnew[4]', $group['exempt'][4], 'radio');
		}
		echo '<tr><td colspan="2">'.$lang['usergroups_edit_credit_exempt_comment'].'</td></tr>';

		$raterangearray = array();
		foreach(explode("\n", $group['raterange']) as $range) {
			$range = explode("\t", $range);
			$raterangearray[$range[0]] = array('min' => $range[1], 'max' => $range[2], 'mrpd' => $range[3]);
		}

		echo '<tr><td colspan="2">';
		showtableheader('usergroups_edit_credit_allowrate', 'noborder');
		showsubtitle(array('', 'credits_id', 'credits_title', 'usergroups_edit_credit_rate_min', 'usergroups_edit_credit_rate_max', 'usergroups_edit_credit_rate_mrpd'));
		for($i = 1; $i <= 8; $i++) {
			if(isset($extcredits[$i])) {
				echo '<tr><td><input class="checkbox" type="checkbox" name="raterangenew['.$i.'][allowrate]" value="1" '.(empty($raterangearray[$i]) ? '' : 'checked').'></td>'.
					'<td>extcredits'.$i.'</td>'.
					'<td>'.$extcredits[$i]['title'].'</td>'.
					'<td><input type="text" class="txt" name="raterangenew['.$i.'][min]" size="3" value="'.$raterangearray[$i]['min'].'"></td>'.
					'<td><input type="text" class="txt" name="raterangenew['.$i.'][max]" size="3" value="'.$raterangearray[$i]['max'].'"></td>'.
					'<td><input type="text" class="txt" name="raterangenew['.$i.'][mrpd]" size="3" value="'.$raterangearray[$i]['mrpd'].'"></td></tr>';
			}
		}
		echo '<tr><td colspan="6">'.$lang['usergroups_edit_credit_allowrate_comment'].'</td></tr></td></tr>';
		showtablefooter();
		echo '</td></tr>';
		showtagfooter('tbody');
		showsubmit('detailsubmit', 'submit', '', "<input type=\"submit\" class=\"btn\" name=\"saveconfigsubmit\" value=\"".$lang['saveconf']."\">");
		showtablefooter();
		showformfooter();

	} else {

		$systemnew = 'private';

		if($group['type'] == 'special' && $group['radminid'] > 0) {

			$radminidnew = $group['radminid'];

		} elseif($group['type'] == 'special') {

			$radminidnew = '0';
			if($system_publicnew) {
				if($system_dailypricenew > 0) {
					if(!$creditstrans) {
						cpmsg('usergroups_edit_creditstrans_disabled', '', 'error');
					} else {
						$system_minspannew = $system_minspannew <= 0 ? 1 : $system_minspannew;
						$systemnew = intval($system_dailypricenew)."\t".intval($system_minspannew);
					}
				} else {
					$systemnew = "0\t0";
				}
			}

		} else {
			$radminidnew = in_array($group['groupid'], array(1, 2, 3)) ? $group['groupid'] : 0;
		}

		if(is_array($raterangenew)) {
			foreach($raterangenew as $key => $rate) {
				if($key >= 1 && $key <= 8 && $rate['allowrate']) {
					$rate['min'] = intval($rate['min'] < -999 ? -999 : $rate['min']);
					$rate['max'] = intval($rate['max'] > 999 ? 999 : $rate['max']);
					$rate['mrpd'] = intval($rate['mrpd'] > 99999 ? 99999 : $rate['mrpd']);
					if(!$rate['mrpd'] || $rate['max'] <= $rate['min'] || $rate['mrpd'] < max(abs($rate['min']), abs($rate['max']))) {
						cpmsg('usergroups_edit_rate_invalid', '', 'error');
					} else {
						$raterangenew[$key] = implode("\t", array($key, $rate['min'], $rate['max'], $rate['mrpd']));
					}
				} else {
					unset($raterangenew[$key]);
				}
			}
		}

		if(in_array($group['groupid'], array(1, 7))) {
			$allowvisitnew = 1;
		}

		$raterangenew = $raterangenew ? implode("\n", $raterangenew) : '';
		$maxpricenew = $maxpricenew < 0 ? 0 : intval($maxpricenew);
		$maxpostsperhournew = $maxpostsperhournew > 255 ? 255 : intval($maxpostsperhournew);

		$extensionarray = array();
		foreach(explode(',', $attachextensionsnew) as $extension) {
			if($extension = trim($extension)) {
				$extensionarray[] = $extension;
			}
		}
		$attachextensionsnew = implode(', ', $extensionarray);

		if($maxtradepricenew == $mintradepricenew || $maxtradepricenew < 0 || $mintradepricenew <= 0 || ($maxtradepricenew && $maxtradepricenew < $mintradepricenew)) {
			cpmsg('trade_fee_error', '', 'error');
		} elseif(($maxrewardpricenew != 0 && $minrewardpricenew >= $maxrewardpricenew) || $minrewardpricenew < 1 || $minrewardpricenew< 0 || $maxrewardpricenew < 0) {
			cpmsg('reward_credits_error', '', 'error');
		}

		$exemptnewbin = '';
		for($i = 0;$i < 8;$i++) {
			$exemptnewbin = intval($exemptnew[$i]).$exemptnewbin;
		}
		$exemptnew = bindec($exemptnewbin);

		$tradesticknew = $tradesticknew > 0 ? intval($tradesticknew) : 0;
		$maxinvitedaynew = $maxinvitedaynew > 0 ? intval($maxinvitedaynew) : 10;
		$maxattachsizenew = $maxattachsizenew > 0 ? intval($maxattachsizenew * 1024) : 0;
		$maxsizeperdaynew = $maxsizeperdaynew > 0 ? intval($maxsizeperdaynew * 1024) : 0;
		$maxattachnumnew = $maxattachnumnew > 0 ? intval($maxattachnumnew) : 0;
		$allowrecommendnew = $allowrecommendnew > 0 ? intval($allowrecommendnew) : 0;
		$edittimelimitnew = $edittimelimitnew > 0 ? intval($edittimelimitnew) : 0;

		$db->query("UPDATE {$tablepre}usergroups SET grouptitle='$grouptitlenew', radminid='$radminidnew', system='$systemnew', allowvisit='$allowvisitnew',
			readaccess='$readaccessnew', allowmultigroups='$allowmultigroupsnew', allowtransfer='$allowtransfernew', allowsendpm='$allowsendpmnew', allowviewpro='$allowviewpronew',
			allowviewstats='$allowviewstatsnew', allowinvisible='$allowinvisiblenew', allowsearch='$allowsearchnew',
			reasonpm='$reasonpmnew', allownickname='$allownicknamenew', allowcstatus='$allowcstatusnew',
			disableperiodctrl='$disableperiodctrlnew', maxpostsperhour='$maxpostsperhournew', maxinvitenum='$maxinvitenumnew', maxinviteday='$maxinvitedaynew', allowpost='$allowpostnew', allowreply='$allowreplynew',
			allowanonymous='$allowanonymousnew', allowsetreadperm='$allowsetreadpermnew', maxprice='$maxpricenew', allowhidecode='$allowhidecodenew',
			allowhtml='$allowhtmlnew', allowpostpoll='$allowpostpollnew', allowdirectpost='$allowdirectpostnew', allowposturl='$allowposturlnew', allowvote='$allowvotenew',
			allowcusbbcode='$allowcusbbcodenew', allowsigbbcode='$allowsigbbcodenew', allowsigimgcode='$allowsigimgcodenew', allowinvite='$allowinvitenew', allowmailinvite='$allowmailinvitenew', raterange='$raterangenew',
			maxsigsize='$maxsigsizenew', allowrecommend='$allowrecommendnew', allowgetattach='$allowgetattachnew', allowpostattach='$allowpostattachnew',
			edittimelimit='$edittimelimitnew',
			allowsetattachperm='$allowsetattachpermnew', allowpostreward='$allowpostrewardnew', maxrewardprice='$maxrewardpricenew', minrewardprice='$minrewardpricenew', inviteprice='$invitepricenew',
			maxattachsize='$maxattachsizenew', maxsizeperday='$maxsizeperdaynew', maxattachnum='$maxattachnumnew', attachextensions='$attachextensionsnew',
			allowbiobbcode='$allowbiobbcodenew', allowbioimgcode='$allowbioimgcodenew', maxbiosize='$maxbiosizenew', exempt='$exemptnew',
			maxtradeprice='$maxtradepricenew', mintradeprice='$mintradepricenew', tradestick='$tradesticknew', allowposttrade='$allowposttradenew',
			allowpostactivity='$allowpostactivitynew', allowmagics='$allowmagicsnew', maxmagicsweight='$maxmagicsweightnew', magicsdiscount='$magicsdiscountnew', allowpostdebate='$allowpostdebatenew', allowpostrushreply='$allowpostrushreplynew' WHERE groupid='$id'");

		if($allowinvisiblenew == 0 && $group['allowinvisible'] != $allowinvisiblenew) {
			$db->query("UPDATE {$tablepre}members SET invisible='0' WHERE groupid='$id'");
		}
		if($threadplugins) {
			$group['allowthreadplugin'][$id] = $allowthreadpluginnew;
			$allowthreadpluginnew = addslashes(serialize($group['allowthreadplugin']));
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('allowthreadplugin', '$allowthreadpluginnew')");
		}

		updatecache('usergroups');

		if(submitcheck('saveconfigsubmit')) {
			$projectid = intval($projectid);
			dheader("Location: $boardurl$BASESCRIPT?action=project&operation=add&id=$id&type=group&projectid=$projectid");
		} elseif($return == 'admin') {
			cpmsg('usergroups_edit_succeed', $BASESCRIPT.'?action=admingroups', 'succeed');
		} else {
			cpmsg('usergroups_edit_succeed', $BASESCRIPT.'?action=usergroups&operation=edit&id='.$id.'&anchor='.$anchor, 'succeed');
		}
	}

}

function array_flip_keys($arr) {
	$arr2 = array();
	$arrkeys = @array_keys($arr);
	list(, $first) = @each(array_slice($arr, 0, 1));
	if($first) {
		foreach($first as $k=>$v) {
			foreach($arrkeys as $key) {
				$arr2[$k][$key] = $arr[$key][$k];
			}
		}
	}
	return $arr2;
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