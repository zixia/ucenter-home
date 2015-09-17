<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: project.inc.php 21337 2010-01-06 08:09:58Z tiger $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

if(!isfounder()) {
	cpheader();
	cpmsg('noaccess', '', 'error');
}

$query = $db->query("SELECT disabledactions FROM {$tablepre}adminactions WHERE admingid='$groupid'");
$dactionarray = ($dactionarray = unserialize($db->result($query, 0))) ? $dactionarray : array();
$allowforumsedit = !in_array('forums', $dactionarray) ? 1 : 0;
$allowusergroups = !in_array('groups_user', $dactionarray) ? 1 : 0;
$allowcreditwizard = !in_array('creditwizard', $dactionarray) ? 1 : 0;

if(empty($allowforumsedit) && empty($allowusergroups) && empty($allowcreditwizard)) {
	cpheader();
	cpmsg('action_noaccess', '', 'error');
}

if($operation == 'export' && $id) {
	$projectarray = $db->fetch_first("SELECT * FROM {$tablepre}projects WHERE id='$id'");
	if(!$projectarray) {
		cpheader();
		cpmsg('undefined_action', '', 'error');
	}

	if(($projectarray['type'] == 'forum' && empty($allowforumsedit)) || ($projectarray['type'] == 'group' && empty($allowusergroups)) || ($projectarray['type'] == 'extcredit' && empty($allowcreditwizard))) {
		cpheader();
		cpmsg('action_noaccess', '', 'error');
	}

	$projectarray['version'] = strip_tags($version);
	exportdata('Discuz! Project', $projectarray['type'].'_'.$projectarray['name'], $projectarray);
}

cpheader();

if(!$operation) {

	if(!submitcheck('projectsubmit')) {

		$listarray = array();
		$projectlist = $typeadd = $selecttype = '';
		$page = max(1, intval($page));
		$start_limit = ($page - 1) * 10;

		$allowaction = array(
			'forum' => $allowforumsedit,
			'group' => $allowusergroups,
			'extcredit' => $allowcreditwizard,
		);

		if(!empty($type) && in_array($type, array('forum', 'group', 'extcredit'))) {

			foreach($allowaction as $key => $val) {
				if($type == $key && empty($val)) {
					cpmsg('action_noaccess', '', 'error');
				}
			}

			$typeadd = "WHERE type='$type'";
			$selecttype = '&amp;type='.$type;

		} else {

			$typeadd = $comma = '';
			foreach($allowaction as $key => $val) {
				if(!empty($val)) {
					$typeadd .= $comma."'$key'";
					$comma = ', ';
				}
			}
			$typeadd = 'WHERE type IN ('.$typeadd.')';

		}

		$projectnum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}projects $typeadd");

		$query = $db->query("SELECT id, name, type, description FROM {$tablepre}projects $typeadd LIMIT $start_limit, 10");
		while($list = $db->fetch_array($query)) {
			$list['name'] = dhtmlspecialchars($list['name']);
			$list['description'] = dhtmlspecialchars($list['description']);
			$projecttype = 'project_'.$list['type'].'_scheme';
			$projectlist .= showtablerow('', array('class="td25"', 'class="td24"', 'class="td26"', 'class="td24"', 'class="td23"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$list[id]\">",
				"<input type=\"text\" class=\"txtnobd\" onblur=\"this.className='txtnobd'\" onfocus=\"this.className='txt'\" size=\"15\" name=\"name[$list[id]]\" value=\"$list[name]\">",
				"<input type=\"text\" class=\"txtnobd\" onblur=\"this.className='txtnobd'\" onfocus=\"this.className='txt'\" size=\"40\" name=\"description[$list[id]]\" value=\"$list[description]\">",
				$lang[$projecttype],
				"<a href=\"$BASESCRIPT?action=project&operation=export&id=$list[id]\" class=\"act\">".$lang['export']."</a> <a href=\"$BASESCRIPT?action=project&operation=apply&amp;projectid=$list[id]&amp;type=$list[type]\" class=\"act\">".$lang['apply']."</a>"
			), TRUE);
		}

		$multipage = multi($projectnum, 10, $page, "$BASESCRIPT?action=project$selecttype");
		shownav('tools', 'project_scheme');
		showsubmenu('project_scheme', array(
			$allowforumsedit ? array('project_forum_scheme', 'project&type=forum', $type == 'forum') : array(),
			$allowusergroups ? array('project_group_scheme', 'project&type=group', $type == 'group') : array(),
			$allowcreditwizard ? array('project_extcredit_scheme', 'project&type=extcredit', $type == 'extcredit') : array(),
			array('project_import_stick', 'project&operation=import', $operation == 'import')
		));
		showformheader('project');
		showtableheader();
		showsubtitle(array('', 'name', 'description', 'type', ''));
		echo $projectlist;
		showsubmit('projectsubmit', 'submit', 'del', '', $multipage);
		showtablefooter();
		showformfooter();

	} else {

		if(is_array($delete)) {
			$ids = $comma =	'';
			foreach($delete	as $id)	{
				$ids .=	"$comma'$id'";
				$comma = ',';
			}
			$db->query("DELETE FROM {$tablepre}projects WHERE id IN ($ids)");
		}

		if(is_array($name)) {
			foreach($name as $id =>	$val) {
				$db->query("UPDATE {$tablepre}projects SET name='$name[$id]', description='$description[$id]' WHERE id='$id'");
			}
		}

		cpmsg('project_update_forum', $BASESCRIPT.'?action=project', 'succeed');

	}

} elseif($operation == 'import') {

	if(!submitcheck('importsubmit')) {

		shownav('tools', 'project_scheme');
		showsubmenu('project_scheme', array(
			$allowforumsedit ? array('project_forum_scheme', 'project&type=forum', $type == 'forum') : array(),
			$allowusergroups ? array('project_group_scheme', 'project&type=group', $type == 'group') : array(),
			$allowcreditwizard ? array('project_extcredit_scheme', 'project&type=extcredit', $type == 'extcredit') : array(),
			array('project_import_stick', 'project&operation=import', $operation == 'import')
		));
		showformheader('project&operation=import', 'enctype');
		showtableheader('project_import_stick');
		showimportdata();
		showsubmit('importsubmit');
		showtablefooter();
		showformfooter();

	} else {

		require_once DISCUZ_ROOT.'./admin/importdata.func.php';
		import_project();
		cpmsg('project_import_succeed', $BASESCRIPT.'?action=project', 'succeed');

	}

} elseif($operation == 'add') {

	$delfields = array(
		'forum'	=> array('fid', 'fup', 'type', 'name', 'status', 'displayorder', 'threads', 'posts', 'todayposts', 'lastpost', 'description', 'modworks', 'password', 'icon', 'redirect', 'moderators', 'rules', 'threadtypes', 'threadsorts', 'typemodels', 'tradetypes', 'threadplugin'),
		'group'	=> array('groupid', 'radminid', 'type', 'system', 'grouptitle', 'creditshigher', 'creditslower', 'stars', 'color', 'groupavatar')
	);

	if(!submitcheck('addsubmit')) {
		shownav('tools', 'project_scheme_add');
		showsubmenu('project_scheme_add');

		if(!empty($projectid)) {
			$query = $db->query("SELECT name, description, value FROM {$tablepre}projects WHERE id='$projectid'");
			$project = $db->fetch_array($query);
		}

		if(($type == 'forum' && empty($allowforumsedit)) || ($type == 'group' && empty($allowusergroups)) || ($type == 'extcredit' && empty($allowcreditwizard))) {
			cpmsg('action_noaccess', '', 'error');
		}

		$allselected = 'selected';
		if($type == 'forum' || $type == 'group') {
			$listoption = '';
			$fieldarray = $type == 'forum' ? array_merge(fetch_table_struct('forums'), fetch_table_struct('forumfields')) : fetch_table_struct('usergroups');
			$listfields = array_diff($fieldarray, $delfields[$type]);
			foreach($listfields as $field) {
				$listoption .= '<option value="'.$field.'">'.($lang['project_option_'.$type.'_'.$field] ? $lang['project_option_'.$type.'_'.$field] : $field).'</option>';
			}
		} elseif($type == 'extcredit') {
			$value = unserialize($project['value']);
			$savemethod = $value['savemethod'];
			$allselected = '';
			$listoption = '<option value="1"'.(@in_array(1, $savemethod) ? ' selected': '').'>'.$lang['project_credits_item_config'].'</option>';
			$listoption .= '<option value="2"'.(@in_array(2, $savemethod) ? ' selected': '').'>'.$lang['project_credits_rule_config'].'</option>';
			$listoption .= '<option value="3"'.(@in_array(3, $savemethod) ? ' selected': '').'>'.$lang['project_credits_use_config'].'</option>';
		}

		showformheader("project&operation=add&id=$id");
		showhiddenfields(array('projectid' => $projectid, 'type' => $type, 'detailsubmit' => 'submit'));
		showtableheader();
		showtitle('project_scheme_save');

		if(!empty($projectid)) {
			showsetting('project_scheme_cover', 'coverwith', '', 'radio');
		}

		showsetting('project_scheme_option', '', '', '<select name="fieldoption[]" size="10" multiple="multiple"><option value="all" '.$allselected.'>'.$lang['all'].'</option>'.$listoption.'</select>');

		showsetting('project_scheme_title', 'name', $project['name'], 'text');
		showsetting('project_scheme_description', 'description', $project['description'], 'textarea');
		showsubmit('addsubmit');
		showtablefooter();

	} else {

		$type = !empty($type) && in_array($type, array('forum', 'group', 'extcredit')) ? $type : '';

		if(empty($name)) {
			cpmsg('project_no_title', '', 'error');
		}

		if($type == 'forum') {
			$value = $db->fetch_first("SELECT f.*, ff.* FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff USING (fid)
				WHERE f.fid='$id'");
			if(!$value) {
				cpmsg('forums_nonexistence', '', 'error');
			}

		} elseif($type == 'group') {
			$value = $db->fetch_first("SELECT * FROM {$tablepre}usergroups WHERE groupid='$id'");
			if(!$value) {
				cpmsg('project_no_usergroup', '', 'error');
			}
		} elseif($type == 'extcredit') {
			if(empty($fieldoption)) {
				cpmsg('project_no_item', '', 'error');
			}
			$delfields = array();
			$fieldoption = in_array('all', $fieldoption) ? array(1, 2, 3) : $fieldoption;
			$variables = in_array(1, $fieldoption) ? ", 'extcredits', 'creditspolicy'" : '';
			$variables .= in_array(2, $fieldoption) ? ", 'creditsformula'" : '';
			$variables .= in_array(3, $fieldoption) ? ", 'creditstrans', 'creditstax', 'transfermincredits', 'exchangemincredits', 'maxincperthread', 'maxchargespan'" : '';

			$query = $db->query("SELECT * FROM {$tablepre}settings WHERE variable IN (''$variables)");
			$value['savemethod'] = $fieldoption;
			while($data = $db->fetch_array($query)) {
				$value[$data['variable']] = $data['value'];
			}
		}

		if($type == 'forum' || $type == 'group') {
			if(in_array('all', $fieldoption)) {
				foreach($delfields[$type] as $field) {
					unset($value[$field]);
				}
			} else {
				$selectlist = '';
				foreach($value as $key => $val) {
					if(in_array($key, $fieldoption)) {
						$selectlist[$key] .= $val;
					}
				}
				$value = $selectlist;
			}
		}

		$value = !empty($value) ? addslashes(serialize($value)) : '';

		if(!empty($projectid) && !empty($coverwith)) {
			$db->query("UPDATE {$tablepre}projects SET name='$name', description='$description', value='$value' WHERE id='$projectid'");
		} else {
			$db->query("INSERT INTO {$tablepre}projects (name, type, description, value) VALUES ('$name', '$type', '$description', '$value')");
		}

		if($type == 'forum') {
			cpmsg('project_sava_succeed', $BASESCRIPT.'?action=forums&operation=edit&fid='.$id, 'succeed');
		} elseif($type == 'group') {
			cpmsg('project_sava_succeed', $BASESCRIPT.'?action=usergroups&operation=edit&id='.$id, 'succeed');
		} elseif($type == 'extcredit') {
			cpmsg('project_sava_succeed', $BASESCRIPT.'?action=settings&operation=credits', 'succeed');
		}

	}

} elseif($operation == 'apply') {

	$type = !empty($type) && in_array($type, array('forum', 'group', 'extcredit')) ? $type : 'forum';

	if(($type == 'forum' && empty($allowforumsedit)) || ($type == 'group' && empty($allowusergroups)) || ($type == 'extcredit' && empty($allowcreditwizard))) {
		cpmsg('action_noaccess', '', 'error');
	}

	$projectselect = "<select name=\"projectid\"><option value=\"0\" selected=\"selected\">".$lang['none']."</option>";
	$query = $db->query("SELECT id, name, type FROM {$tablepre}projects WHERE type='$type'");
	while($project = $db->fetch_array($query)) {
		$projectselect .= "<option value=\"$project[id]\" ".($project['id'] == $projectid ? 'selected="selected"' : NULL).">$project[name]</option>\n";
	}
	$projectselect .= '</select>';

	if(!submitcheck('applysubmit')) {

		if($type == 'forum') {

			require_once DISCUZ_ROOT.'./include/forum.func.php';
			$forumselect = '<select name="target[]" size="10" multiple="multiple">'.forumselect(FALSE, 0, 0, TRUE).'</select>';

		} elseif($type == 'group') {

			$groupselect = '<select name="target[]" size="10" multiple="multiple">';
			$query = $db->query("SELECT groupid, type, grouptitle, creditshigher, creditslower, stars, color, groupavatar FROM {$tablepre}usergroups ORDER BY creditshigher");
			while($group = $db->fetch_array($query)) {
				$groupselect .= '<option value="'.$group['groupid'].'">'.$group['grouptitle'].'</option>';
			}
			$groupselect .= '</select>';

		} elseif($type == 'extcredit') {

			dheader("location: $BASESCRIPT?action=settings&operation=credits&projectid=$projectid");

		}

		shownav('tools', 'project_scheme');
		showsubmenu('project_global_forum');
		showformheader("project&operation=apply&projectid=$projectid");
		showtableheader();
		showtitle($type == 'forum' ? 'project_scheme_forum' : 'project_group_scheme');
		showsetting('project_scheme_name', '', '', $projectselect);
		if($type == 'forum') {
			showsetting('forums_copy_target', '', '', $forumselect);
		} elseif($type == 'group') {
			showsetting('project_target_usergroup', '', '', $groupselect);
		}
		showsubmit('applysubmit');
		showtablefooter();
		showformfooter();

	} else {

		if(empty($target)) {
			cpmsg('project_target_item_invalid', '', 'error');
		}

		$applyids = implodeids($target);

		$project = $db->fetch_first("SELECT type, value FROM {$tablepre}projects WHERE id='$projectid'");
		if(!$project) {
			cpmsg('project_no_scheme', '', 'error');
		}

		if(!$value = unserialize($project['value'])) {
			cpmsg('project_invalid', '', 'error');
		}

		if($project['type'] == 'forum') {

			$table_forum_columns = array('styleid', 'allowsmilies', 'allowhtml', 'allowbbcode', 'allowimgcode', 'allowmediacodenew', 'allowanonymous', 'allowshare', 'allowpostspecial', 'alloweditrules', 'alloweditpost', 'allowspecialonly', 'modnewposts', 'recyclebin', 'jammer', 'forumcolumns', 'threadcaches', 'disablewatermark', 'autoclose', 'simple');
			$table_forumfield_columns = array('attachextensions', 'postcredits', 'replycredits', 'digestcredits', 'postattachcredits', 'getattachcredits', 'viewperm', 'postperm', 'replyperm', 'getattachperm', 'postattachperm', 'modrecommend', 'formulaperm');

			$updatesql = $comma = '';
			foreach($table_forum_columns as $field) {
				if(isset($value[$field])) {
					$updatesql .= "$comma$field='".addslashes($value[$field])."'";
					$comma = ', ';
				}
			}

			if($updatesql && $applyids) {
				$db->query("UPDATE {$tablepre}forums SET $updatesql WHERE fid IN ($applyids)");
			}

			$updatesql = $comma = '';
			foreach($table_forumfield_columns as $field) {
				if(isset($value[$field])) {
					$updatesql .= "$comma$field='".addslashes($value[$field])."'";
					$comma = ', ';
				}
			}

			if($updatesql && $applyids) {
				$db->query("UPDATE {$tablepre}forumfields SET $updatesql WHERE fid IN ($applyids)");
			}

		} elseif($project['type'] == 'group') {

			$usergroup_columns = array('readaccess', 'allowvisit', 'allowpost', 'allowreply', 'allowpostpoll', 'allowpostreward', 'allowposttrade', 'allowpostactivity', 'allowdirectpost', 'allowgetattach', 'allowpostattach', 'allowvote', 'allowmultigroups', 'allowsearch', 'allowcstatus', 'allowinvisible', 'allowtransfer', 'allowsetreadperm', 'allowsetattachperm', 'allowhidecode', 'allowhtml', 'allowcusbbcode', 'allowanonymous', 'allownickname', 'allowsigbbcode', 'allowsigimgcode', 'allowviewpro', 'allowviewstats', 'disableperiodctrl', 'reasonpm', 'maxprice', 'maxsigsize', 'maxattachsize', 'maxsizeperday', 'maxpostsperhour', 'attachextensions', 'raterange', 'mintradeprice', 'maxtradeprice', 'minrewardprice', 'maxrewardprice', 'magicsdiscount', 'allowmagics', 'maxmagicsweight', 'allowbiobbcode', 'allowbioimgcode', 'maxbiosize', 'allowinvite', 'allowmailinvite', 'inviteprice', 'maxinvitenum', 'maxinviteday', 'allowpostdebate', 'tradestick', 'exempt');

			$updatesql = $comma = '';
			foreach($usergroup_columns as $field) {
				if(isset($value[$field])) {
					$updatesql .= "$comma$field='".addslashes($value[$field])."'";
					$comma = ', ';
				}
			}

			if($updatesql && $applyids) {
				$db->query("UPDATE {$tablepre}usergroups SET $updatesql WHERE groupid IN ($applyids)");
			}
		}

		cpmsg('project_scheme_succeed', '', 'succeed');

	}
}

function fetch_table_struct($tablename, $result = 'FIELD') {
	global $db, $tablepre;
	$datas = array();
	$query = $db->query("DESCRIBE $tablepre$tablename");
	while($data = $db->fetch_array($query)) {
		$datas[$data['Field']] = $result == 'FIELD' ? $data['Field'] : $data;
	}
	return $datas;
}

?>