<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forums.inc.php 21337 2010-01-06 08:09:58Z tiger $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

$operation = empty($operation) ? 'admin' : $operation;

if($operation == 'admin') {

	if(!submitcheck('editsubmit')) {
		shownav('forum', 'forums_admin');
		showsubmenu('forums_admin');
		showtips('forums_admin_tips');

		$projectselect = '<option value="0" selected="selected">'.$lang['none'].'</option>';
		$query = $db->query("SELECT id, name FROM {$tablepre}projects WHERE type='forum'");
		while($project = $db->fetch_array($query)) {
			$projectselect .= '<option value="'.$project['id'].'">'.$project['name'].'</option>';
		}

?>
<script type="text/JavaScript">
var rowtypedata = [
	[[1,'<input type="text" class="txt" name="newcatorder[]" value="0" />', 'td25'], [3, '<input name="newcat[]" value="<?=$lang['forums_admin_add_category_name']?>" size="20" type="text" class="txt" />']],
	[[1,'<input type="text" class="txt" name="neworder[{1}][]" value="0" />', 'td25'], [3, '<div class="board"><input name="newforum[{1}][]" value="<?=$lang['forums_admin_add_forum_name']?>" size="20" type="text" class="txt" /><select name="projectid[{1}][]"><?=$projectselect?></select></div>']],
	[[1,'<input type="text" class="txt" name="neworder[{1}][]" value="0" />', 'td25'], [3, '<div class="childboard"><input name="newforum[{1}][]" value="<?=$lang['forums_admin_add_forum_name']?>" size="20" type="text" class="txt" /><select name="projectid[{1}][]" id="projectid[{1}]"><?=$projectselect?></select>&nbsp;<input name="inherited[{1}][]" type="checkbox" class="checkbox" value="1" onclick="if($(\'projectid[{1}]\').disabled) {$(\'projectid[{1}]\').disabled = false;} else {$(\'projectid[{1}]\').disabled = true;}">&nbsp;<?=$lang['forums_edit_inherited']?></div>']],
];
</script>
<?
		showformheader('forums');
		showtableheader('');
		showsubtitle(array('display_order', 'forums_admin_name', 'forums_moderators', ''));

		$forums = $showedforums = array();
		$query = $db->query("SELECT f.fid, f.type, f.status, f.name, f.fup, f.displayorder, f.inheritedmod, ff.moderators, ff.password, ff.redirect
			FROM {$tablepre}forums f LEFT JOIN {$tablepre}forumfields ff USING(fid)
			ORDER BY f.type<>'group', f.displayorder");

		while($forum = $db->fetch_array($query)) {
			$forums[] = $forum;
		}
		for($i = 0; $i < count($forums); $i++) {
			if($forums[$i]['type'] == 'group') {
				echo showforum($i, 'group');
				for($j = 0; $j < count($forums); $j++) {
					if($forums[$j]['fup'] == $forums[$i]['fid'] && $forums[$j]['type'] == 'forum') {
						echo showforum($j);
						$lastfid = 0;
						for($k = 0; $k < count($forums); $k++) {
							if($forums[$k]['fup'] == $forums[$j]['fid'] && $forums[$k]['type'] == 'sub') {
								echo showforum($k, 'sub');
								$lastfid = $forums[$k]['fid'];
							}
						}
						echo showforum($j, $lastfid, 'lastchildboard');
					}
				}
				echo showforum($i, '', 'lastboard');
			} elseif(!$forums[$i]['fup'] && $forums[$i]['type'] == 'forum') {
				echo showforum($i);
				for($j = 0; $j < count($forums); $j++) {
					if($forums[$j]['fup'] == $forums[$i]['fid'] && $forums[$j]['type'] == 'sub') {
						echo showforum($j, 'sub');
					}
				}
				echo showforum($i, '', 'lastchildboard');
			}
		}

		foreach($forums as $key => $forum) {
			if(!in_array($key, $showedforums)) {
				$db->query("UPDATE {$tablepre}forums SET fup='0', type='forum' WHERE fid='$forum[fid]'");
				echo showforum($key);
			}
		}

		echo showforum($i, '', 'last');

		showsubmit('editsubmit');
		showtablefooter();
		showformfooter();

	} else {
		// read from groups
		$usergroups = array();
		$query = $db->query("SELECT groupid, type, creditshigher, creditslower FROM {$tablepre}usergroups");
		while($group = $db->fetch_array($query)) {
			$usergroups[$group['groupid']] = $group;
		}

		if(is_array($order)) {
			foreach($order as $fid => $value) {
				$db->query("UPDATE {$tablepre}forums SET name='$name[$fid]', displayorder='$order[$fid]' WHERE fid='$fid'");
			}
		}

		if(is_array($newcat)) {
			foreach($newcat as $key => $forumname) {
				if(empty($forumname)) {
					continue;
				}
				$db->query("INSERT INTO {$tablepre}forums (type, name, status, displayorder)
					VALUES ('group', '$forumname', '1', '$newcatorder[$key]')");
				$fid = $db->insert_id();
				$db->query("INSERT INTO {$tablepre}forumfields (fid)
					VALUES ('$fid')");
			}
		}

		$table_forum_columns = array('fup', 'type', 'name', 'status', 'displayorder', 'styleid', 'allowsmilies', 'allowhtml', 'allowbbcode', 'allowimgcode', 'allowmediacodenew', 'allowanonymous', 'allowshare', 'allowpostspecial', 'alloweditrules', 'alloweditpost', 'modnewposts', 'recyclebin', 'jammer', 'forumcolumns', 'threadcaches', 'disablewatermark', 'autoclose', 'simple');
		$table_forumfield_columns = array('fid', 'attachextensions', 'threadtypes', 'postcredits', 'replycredits', 'digestcredits', 'postattachcredits', 'getattachcredits', 'viewperm', 'postperm', 'replyperm', 'getattachperm', 'postattachperm');
		$projectdata = array();

		if(is_array($newforum)) {
			foreach($newforum as $fup => $forums) {
				$forum = $db->fetch_first("SELECT * FROM {$tablepre}forums WHERE fid='$fup'");
				foreach($forums as $key => $forumname) {
					if(empty($forumname)) {
						continue;
					}
					$forumfields = array();
					$id = $projectid[$fup][$key];
					$inheritedid = $inherited[$fup] ? $fup : '';


					if(!empty($id)) {

						$projectdata[$id] = empty($projectdata[$id]) ? unserialize($db->result_first("SELECT value FROM {$tablepre}projects WHERE id='$id'")) : $projectdata[$id];

						foreach($table_forum_columns as $field) {
							$forumfields[$field] = $projectdata[$id][$field];
						}

						foreach($table_forumfield_columns as $field) {
							$forumfields[$field] = $projectdata[$id][$field];
						}

					} elseif(!empty($inheritedid)) {

						$query = $db->query("SELECT * FROM {$tablepre}forums WHERE fid='$inheritedid'");
						$forum = $db->fetch_array($query);

						$query = $db->query("SELECT * FROM {$tablepre}forumfields WHERE fid='$inheritedid'");
						$forumfield = $db->fetch_array($query);

						foreach($table_forum_columns as $field) {
							$forumfields[$field] = $forum[$field];
						}

						foreach($table_forumfield_columns as $field) {
							$forumfields[$field] = $forumfield[$field];
						}

					} else {
						$forumfields['allowsmilies'] = $forumfields['allowbbcode'] = $forumfields['allowimgcode'] = $forumfields['allowshare'] = 1;
						$forumfields['allowpostspecial'] = 127;
					}

					$forumfields['fup'] = $forum ? $fup : 0;
					$forumfields['type'] = $forum['type'] == 'forum' ? 'sub' : 'forum';
					$forumfields['name'] = $forumname;
					$forumfields['status'] = 1;
					$forumfields['displayorder'] = $neworder[$fup][$key];

					$sql1 = $sql2 = $comma = '';
					foreach($table_forum_columns as $field) {
						if(isset($forumfields[$field])) {
							$sql1 .= "$comma$field";
							$sql2 .= "$comma'{$forumfields[$field]}'";
							$comma = ', ';
						}
					}

					$db->query("INSERT INTO {$tablepre}forums ($sql1) VALUES ($sql2)");
					$forumfields['fid'] = $fid = $db->insert_id();

					$sql1 = $sql2 = $comma = '';
					foreach($table_forumfield_columns as $field) {
						if(isset($forumfields[$field])) {
							$sql1 .= "$comma$field";
							$sql2 .= "$comma'{$forumfields[$field]}'";
							$comma = ', ';
						}
					}

					$db->query("INSERT INTO {$tablepre}forumfields ($sql1) VALUES ($sql2)");

					$query = $db->query("SELECT uid, inherited FROM {$tablepre}moderators WHERE fid='$fup'");
					while($mod = $db->fetch_array($query)) {
						if($mod['inherited'] || $forum['inheritedmod']) {
							$db->query("REPLACE INTO {$tablepre}moderators (uid, fid, inherited)
								VALUES ('$mod[uid]', '$fid', '1')");
						}
					}
				}
			}
		}


		updatecache('forums');

		cpmsg('forums_update_succeed', $BASESCRIPT.'?action=forums', 'succeed');
	}

} elseif($operation == 'moderators' && $fid) {

	if(!submitcheck('modsubmit')) {

		shownav('forum', 'forums_moderators_edit');
		showsubmenu(lang('forums_moderators_edit').' - '.$forum['name']);
		showtips('forums_moderators_tips');
		showformheader("forums&operation=moderators&fid=$fid&");
		showtableheader('', 'fixpadding');
		showsubtitle(array('', 'display_order', 'username', 'usergroups', 'forums_moderators_inherited'));

		$query = $db->query("SELECT a.admingid, u.radminid, u.grouptitle FROM {$tablepre}admingroups a
			INNER JOIN {$tablepre}usergroups u ON u.groupid=a.admingid
			WHERE u.radminid>'0'
			ORDER BY u.type, a.admingid");
		$modgroups = array();
		$groupselect = '<select name="newgroup">';
		while($modgroup = $db->fetch_array($query)) {
			if($modgroup['radminid'] == 3) {
				$groupselect .= '<option value="'.$modgroup['admingid'].'">'.$modgroup['grouptitle'].'</option>';
			}
			$modgroups[$modgroup['admingid']] = $modgroup['grouptitle'];
		}
		$groupselect .= '</select>';

		$query = $db->query("SELECT m.username, m.groupid, mo.* FROM {$tablepre}members m, {$tablepre}moderators mo WHERE mo.fid='$fid' AND m.uid=mo.uid ORDER BY mo.inherited, mo.displayorder");
		while($mod = $db->fetch_array($query)) {
			showtablerow('', array('class="td25"', 'class="td28"'), array(
				'<input type="checkbox" class="checkbox" name="delete[]" value="'.$mod[uid].'"'.($mod['inherited'] ? ' disabled' : '').' />',
				'<input type="text" class="txt" name="displayordernew['.$mod[uid].']" value="'.$mod[displayorder].'" size="2" />',
				"<a href=\"$BASESCRIPT?action=members&operation=group&uid=$mod[uid]\" target=\"_blank\">$mod[username]</a>",
				$modgroups[$mod['groupid']],
				lang($mod['inherited'] ? 'yes' : 'no'),
			));
		}

		if($forum['type'] == 'group' || $forum['type'] == 'sub') {
			$checked = $forum['type'] == 'group' ? 'checked' : '';
			$disabled = 'disabled';
		} else {
			$checked = $forum['inheritedmod'] ? 'checked' : '';
			$disabled = '';
		}

		showtablerow('', array('class="td25"', 'class="td28"'), array(
			lang('add_new'),
			'<input type="text" class="txt" name="newdisplayorder" value="0" size="2" />',
			'<input type="text" class="txt" name="newmoderator" value="" size="20" />',
			$groupselect,
			''
		));

		showsubmit('modsubmit', 'submit', 'del', '<input class="checkbox" type="checkbox" name="inheritedmodnew" value="1" '.$checked.' '.$disabled.' id="inheritedmodnew" /><label for="inheritedmodnew">'.lang('forums_moderators_inherit').'</label>');
		showtablefooter();
		showformfooter();

	} else {

		if($forum['type'] == 'group') {
			$inheritedmodnew = 1;
		} elseif($forum['type'] == 'sub') {
			$inheritedmodnew = 0;
		}

		if(!empty($delete) || $newmoderator || (bool)$forum['inheritedmod'] != (bool)$inheritedmodnew) {

			$fidarray = $newmodarray = $origmodarray = array();

			if($forum['type'] == 'group') {
				$query = $db->query("SELECT fid FROM {$tablepre}forums WHERE type='forum' AND fup='$fid'");
				while($sub = $db->fetch_array($query)) {
					$fidarray[] = $sub['fid'];
				}
				$query = $db->query("SELECT fid FROM {$tablepre}forums WHERE type='sub' AND fup IN ('".implode('\',\'', $fidarray)."')");
				while($sub = $db->fetch_array($query)) {
					$fidarray[] = $sub['fid'];
				}
			} elseif($forum['type'] == 'forum') {
				$query = $db->query("SELECT fid FROM {$tablepre}forums WHERE type='sub' AND fup='$fid'");
				while($sub = $db->fetch_array($query)) {
					$fidarray[] = $sub['fid'];
				}
			}

			if(is_array($delete)) {
				foreach($delete as $uid) {
					$db->query("DELETE FROM {$tablepre}moderators WHERE uid='$uid' AND ((fid='$fid' AND inherited='0') OR (fid IN ('".implode('\',\'', $fidarray)."') AND inherited='1'))");
				}

				$excludeuids = 0;
				$deleteuids = '\''.implode('\',\'', $delete).'\'';
				$query = $db->query("SELECT uid FROM {$tablepre}moderators WHERE uid IN ($deleteuids)");
				while($mod = $db->fetch_array($query)) {
					$excludeuids .= ','.$mod['uid'];
				}

				$usergroups = array();
				$query = $db->query("SELECT groupid, type, radminid, creditshigher, creditslower FROM {$tablepre}usergroups");
				while($group = $db->fetch_array($query)) {
					$usergroups[$group['groupid']] = $group;
				}

				$query = $db->query("SELECT uid, groupid, credits FROM {$tablepre}members WHERE uid IN ($deleteuids) AND uid NOT IN ($excludeuids) AND adminid NOT IN (1,2)");
				while($member = $db->fetch_array($query)) {
					if($usergroups[$member['groupid']]['type'] == 'special' && $usergroups[$member['groupid']]['radminid'] != 3) {
						$adminidnew = -1;
						$groupidnew = $member['groupid'];
					} else {
						$adminidnew = 0;
						foreach($usergroups as $group) {
							if($group['type'] == 'member' && $member['credits'] >= $group['creditshigher'] && $member['credits'] < $group['creditslower']) {
								$groupidnew = $group['groupid'];
								break;
							}
						}
					}
					$db->query("UPDATE {$tablepre}members SET adminid='$adminidnew', groupid='$groupidnew' WHERE uid='$member[uid]'");
				}
			}

			if((bool)$forum['inheritedmod'] != (bool)$inheritedmodnew) {
				$query = $db->query("SELECT uid FROM {$tablepre}moderators WHERE fid='$fid' AND inherited='0'");
				while($mod = $db->fetch_array($query)) {
					$origmodarray[] = $mod['uid'];
					if(!$forum['inheritedmod'] && $inheritedmodnew) {
						$newmodarray[] = $mod['uid'];
					}
				}
				if($forum['inheritedmod'] && !$inheritedmodnew) {
					$db->query("DELETE FROM {$tablepre}moderators WHERE uid IN ('".implode('\',\'', $origmodarray)."') AND fid IN ('".implode('\',\'', $fidarray)."') AND inherited='1'");
				}
			}

			if($newmoderator) {
				$member = $db->fetch_first("SELECT uid FROM {$tablepre}members WHERE username='$newmoderator'");
				if(!$member) {
					cpmsg('members_edit_nonexistence', '', 'error');
				} else {
					$newmodarray[] = $member['uid'];
					$db->query("UPDATE {$tablepre}members SET groupid='$newgroup' WHERE uid='$member[uid]' AND adminid NOT IN (1,2,3,4,5,6,7,8,-1)");
					$db->query("UPDATE {$tablepre}members SET adminid='3' WHERE uid='$member[uid]' AND adminid NOT IN (1,2)");
					$db->query("REPLACE INTO {$tablepre}moderators (uid, fid, displayorder, inherited)
						VALUES ('$member[uid]', '$fid', '$newdisplayorder', '0')");
				}
			}

			foreach($newmodarray as $uid) {
				$db->query("REPLACE INTO {$tablepre}moderators (uid, fid, displayorder, inherited)
					VALUES ('$uid', '$fid', '$newdisplayorder', '0')");

				if($inheritedmodnew) {
					foreach($fidarray as $ifid) {
						$db->query("REPLACE INTO {$tablepre}moderators (uid, fid, inherited)
							VALUES ('$uid', '$ifid', '1')");
					}
				}
			}

			if($forum['type'] == 'group') {
				$inheritedmodnew = 1;
			} elseif($forum['type'] == 'sub') {
				$inheritedmodnew = 0;
			}
			$db->query("UPDATE {$tablepre}forums SET inheritedmod='$inheritedmodnew' WHERE fid='$fid'");

		}

		if(is_array($displayordernew)) {
			foreach($displayordernew as $uid => $order) {
				$db->query("UPDATE {$tablepre}moderators SET displayorder='$order' WHERE fid='$fid' AND uid='$uid'");
			}
		}

		$moderators = $tab = '';
		$query = $db->query("SELECT m.username FROM {$tablepre}members m, {$tablepre}moderators mo WHERE mo.fid='$fid' AND mo.inherited='0' AND m.uid=mo.uid ORDER BY mo.displayorder");
		while($mod = $db->fetch_array($query)) {
			$moderators .= $tab.addslashes($mod['username']);
			$tab = "\t";
		}
		$db->query("UPDATE {$tablepre}forumfields SET moderators='$moderators' WHERE fid='$fid'");

		cpmsg('forums_moderators_update_succeed', "$BASESCRIPT?action=forums&operation=moderators&fid=$fid", 'succeed');

	}

} elseif($operation == 'merge') {

	if(!submitcheck('mergesubmit') || $source == $target) {

		require_once DISCUZ_ROOT.'./include/forum.func.php';
		require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
		$forumselect = "<select name=\"%s\">\n<option value=\"\">&nbsp;&nbsp;> $lang[select]</option><option value=\"\">&nbsp;</option>".str_replace('%', '%%', forumselect(FALSE, 0, 0, TRUE)).'</select>';
		shownav('forum', 'forums_merge');
		showsubmenu('forums_merge');
		showformheader('forums&operation=merge');
		showtableheader();
		showsetting('forums_merge_source', '', '', sprintf($forumselect, 'source'));
		showsetting('forums_merge_target', '', '', sprintf($forumselect, 'target'));
		showsubmit('mergesubmit');
		showtablefooter();
		showformfooter();

	} else {

		if($db->result_first("SELECT COUNT(*) FROM {$tablepre}forums WHERE fid IN ('$source', '$target') AND type<>'group'") != 2) {
			cpmsg('forums_nonexistence', '', 'error');
		}

		if($db->result_first("SELECT COUNT(*) FROM {$tablepre}forums WHERE fup='$source'")) {
			cpmsg('forums_merge_source_sub_notnull', '', 'error');
		}

		$db->query("UPDATE {$tablepre}threads SET fid='$target' WHERE fid='$source'");
		$db->query("UPDATE {$tablepre}posts SET fid='$target' WHERE fid='$source'");

		$sourceforum = $db->fetch_first("SELECT threads, posts FROM {$tablepre}forums WHERE fid='$source'");

		$db->query("UPDATE {$tablepre}forums SET threads=threads+$sourceforum[threads], posts=posts+$sourceforum[posts] WHERE fid='$target'");
		$db->query("DELETE FROM {$tablepre}forums WHERE fid='$source'");
		$db->query("DELETE FROM {$tablepre}forumfields WHERE fid='$source'");
		$db->query("DELETE FROM {$tablepre}moderators WHERE fid='$source'");

		$query = $db->query("SELECT * FROM {$tablepre}access WHERE fid='$source'");
		while($access = $db->fetch_array($query)) {
			$db->query("INSERT INTO {$tablepre}access (uid, fid, allowview, allowpost, allowreply, allowgetattach)
				VALUES ('$access[uid]', '$target', '$access[allowview]', '$access[allowpost]', '$access[allowreply]', '$access[allowgetattach]')", 'SILENT');
		}
		$db->query("DELETE FROM {$tablepre}access WHERE fid='$source'");

		updatecache('forums');

		cpmsg('forums_merge_succeed', $BASESCRIPT.'?action=forums', 'succeed');
	}

} elseif($operation == 'edit') {

	require_once DISCUZ_ROOT.'./include/forum.func.php';
	if(empty($fid)) {
		cpmsg('forums_edit_nonexistence', $BASESCRIPT.'?action=forums&operation=edit'.(!empty($highlight) ? "&highlight=$highlight" : '').(!empty($anchor) ? "&anchor=$anchor" : ''), 'form', '<select name="fid">'.forumselect(FALSE, 0, 0, TRUE).'</select>');
	}

	$perms = array('viewperm', 'postperm', 'replyperm', 'getattachperm', 'postattachperm');

	$forum = $db->fetch_first("SELECT *, f.fid AS fid FROM {$tablepre}forums f
		LEFT JOIN {$tablepre}forumfields ff USING (fid)
		WHERE f.fid='$fid'");

	if(!$forum) {
		cpmsg('forums_nonexistence', '', 'error');
	}

	$query = $db->query("SELECT disabledactions FROM {$tablepre}adminactions WHERE admingid='$groupid'");
	$dactionarray = ($dactionarray = unserialize($db->result($query, 0))) ? $dactionarray : array();
	$allowthreadtypes = !in_array('threadtypes', $dactionarray);

	if(!empty($projectid)) {
		$query = $db->query("SELECT value FROM {$tablepre}projects WHERE id='$projectid'");
		$forum = @array_merge($forum, unserialize($db->result($query, 0)));
	}

	$forumdomains = array();
	$query = $db->query("SELECT value FROM {$tablepre}settings WHERE variable='forumdomains'");
	$forumdomains = @unserialize($db->result($query, 0));
	if(!submitcheck('detailsubmit') && !submitcheck('saveconfigsubmit')) {
		$anchor = in_array($anchor, array('basic', 'extend', 'posts', 'credits', 'threadtypes', 'threadsorts', 'perm')) ? $anchor : 'basic';
		shownav('forum', 'forums_edit');

		require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
		$forumselect = '';
		foreach($_DCACHE['forums'] as $forums) {
			if($forums['type'] == 'group') {
				$forumselect .= '<em class="hover" onclick="location.href=\''.$BASESCRIPT.'?action=forums&operation=edit&switch=yes&fid='.$forums['fid'].'\'">'.$forums['name'].'</em>';
			} elseif($forums['type'] == 'forum') {
				$forumselect .= '<a class="f'.($fid == $forums['fid'] ? ' current"' : '').'" href="###" onclick="location.href=\''.$BASESCRIPT.'?action=forums&operation=edit&switch=yes&fid='.$forums['fid'].($forum['type'] != 'group' ? '&anchor=\'+currentAnchor' : '\'').'+\'&scrolltop=\'+document.documentElement.scrollTop">'.$forums['name'].'</a>';
			} elseif($forums['type'] == 'sub') {
				$forumselect .= '<a class="s'.($fid == $forums['fid'] ? ' current"' : '').'" href="###" onclick="location.href=\''.$BASESCRIPT.'?action=forums&operation=edit&switch=yes&fid='.$forums['fid'].($forum['type'] != 'group' ? '&anchor=\'+currentAnchor' : '\'').'+\'&scrolltop=\'+document.documentElement.scrollTop">'.$forums['name'].'</a>';
			}
		}
		$forumselect = '<span id="fselect" class="right popupmenu_dropmenu" onmouseover="showMenu({\'ctrlid\':this.id,\'pos\':\'34\'});$(\'fselect_menu\').style.top=(parseInt($(\'fselect_menu\').style.top)-document.documentElement.scrollTop)+\'px\'">'.$lang['forums_edit_switch'].'<em>&nbsp;&nbsp;</em></span>'.
			'<div id="fselect_menu" class="popupmenu_popup" style="width:200px;display:none">'.$forumselect.'</div>';

		if($forum['type'] == 'group') {
			showsubmenu("$lang[forums_cat_detail] - $forum[name]", array(), $forumselect);
			if(!empty($switch)) {
				echo '<script type="text/javascript">showMenu({\'ctrlid\':\'fselect\',\'pos\':\'34\'});</script>';
			}
		} else {
			showsubmenuanchors(lang('forums_edit').' - '.$forum['name'], array(
				array('forums_edit_basic', 'basic', $anchor == 'basic'),
				array('forums_edit_extend', 'extend', $anchor == 'extend'),
				array('forums_edit_posts', 'posts', $anchor == 'posts'),
				array('forums_edit_credits', 'credits', $anchor == 'credits'),
				array('forums_edit_threadtypes', 'threadtypes', $anchor == 'threadtypes'),
				array('forums_edit_threadsorts', 'threadsorts', $anchor == 'threadsorts'),
				array('forums_edit_perm', 'perm', $anchor == 'perm')
			), $forumselect);
			if(!empty($switch)) {
				echo '<script type="text/javascript">showMenu({\'ctrlid\':\'fselect\',\'pos\':\'34\'});</script>';
			}
		}
		showtips('forums_edit_tips');
		showformheader("forums&operation=edit&fid=$fid&");
		showhiddenfields(array('type' => $forum['type']));

		$sideselect = array();
		$infosidestatus[0] && $sideselect[0] = '<select name="foruminfosidestatus[0]">
			<option value="">'.$lang['forums_edit_extend_sideselect_global'].'</option>
			<option value="-1"'.($infosidestatus['f'.$fid][0] == -1 ? ' selected="selected"' : '').'>'.$lang['forums_edit_extend_sideselect_off'].'</option>';
		if($sideselect) {
			$query = $db->query("SELECT variable FROM {$tablepre}request WHERE type=-2");
			while($side = $db->fetch_array($query)) {
				$infosidestatus[0] && $sideselect[0] .= "<option value=\"$side[variable]\" ".($infosidestatus['f'.$fid][0] == $side['variable'] ? 'selected="selected"' : NULL).">$side[variable]</option>\n";
			}
			$infosidestatus[0] && $sideselect[0] .= '</select>';
		}

		if($forum['type'] == 'group') {
			$forum['extra'] = unserialize($forum['extra']);
			showtableheader();
			showsetting('forums_edit_basic_cat_name', 'namenew', $forum['name'], 'text');
			showsetting('forums_edit_basic_cat_name_color', 'extra[namecolor]', $forum['extra']['namecolor'], 'color');
			showsetting('forums_edit_extend_sub_horizontal', 'forumcolumnsnew', $forum['forumcolumns'], 'text');
			showsetting('forums_cat_display', 'statusnew', $forum['status'], 'radio');
			if($sideselect) {
				showsetting('forums_cat_extend_sideselect', '', '', $sideselect[0]);
			}
			showsubmit('detailsubmit');
			showtablefooter();

		} else {

			require_once DISCUZ_ROOT.'./include/editor.func.php';

			$projectselect = "<select name=\"projectid\" onchange=\"window.location='$BASESCRIPT?action=forums&operation=edit&fid=$fid&projectid='+this.options[this.options.selectedIndex].value\"><option value=\"0\" selected=\"selected\">".$lang['none']."</option>";
			$query = $db->query("SELECT id, name FROM {$tablepre}projects WHERE type='forum'");
			while($project = $db->fetch_array($query)) {
				$projectselect .= "<option value=\"$project[id]\" ".($project['id'] == $projectid ? 'selected="selected"' : NULL).">$project[name]</option>\n";
			}
			$projectselect .= '</select>';

			$fupselect = "<select name=\"fupnew\">\n";
			$query = $db->query("SELECT fid, type, name, fup FROM {$tablepre}forums WHERE fid<>'$fid' AND type<>'sub' ORDER BY displayorder");
			while($fup = $db->fetch_array($query)) {
				$fups[] = $fup;
			}
			if(is_array($fups)) {
				foreach($fups as $forum1) {
					if($forum1['type'] == 'group') {
						$selected = $forum1['fid'] == $forum['fup'] ? "selected=\"selected\"" : NULL;
						$fupselect .= "<option value=\"$forum1[fid]\" $selected>$forum1[name]</option>\n";
						foreach($fups as $forum2) {
							if($forum2['type'] == 'forum' && $forum2['fup'] == $forum1['fid']) {
								$selected = $forum2['fid'] == $forum['fup'] ? "selected=\"selected\"" : NULL;
								$fupselect .= "<option value=\"$forum2[fid]\" $selected>&nbsp; &gt; $forum2[name]</option>\n";
							}
						}
					}
				}
				foreach($fups as $forum0) {
					if($forum0['type'] == 'forum' && $forum0['fup'] == 0) {
						$selected = $forum0['fid'] == $forum['fup'] ? "selected=\"selected\"" : NULL;
						$fupselect .= "<option value=\"$forum0[fid]\" $selected>$forum0[name]</option>\n";
					}
				}
			}
			$fupselect .= '</select>';

			$groups = array();
			$query = $db->query("SELECT type, groupid, grouptitle, radminid FROM {$tablepre}usergroups ORDER BY (creditshigher<>'0' || creditslower<>'0'), creditslower, groupid");
			while($group = $db->fetch_array($query)) {
				$group['type'] = $group['type'] == 'special' && $group['radminid'] ? 'specialadmin' : $group['type'];
				$groups[$group['type']][] = $group;
			}

			$styleselect = "<select name=\"styleidnew\"><option value=\"0\">$lang[use_default]</option>";
			$query = $db->query("SELECT styleid, name FROM {$tablepre}styles");
			while($style = $db->fetch_array($query)) {
				$styleselect .= "<option value=\"$style[styleid]\" ".
					($style['styleid'] == $forum['styleid'] ? 'selected="selected"' : NULL).
					">$style[name]</option>\n";
			}
			$styleselect .= '</select>';

			if($forum['autoclose']) {
				$forum['autoclosetime'] = abs($forum['autoclose']);
				$forum['autoclose'] = $forum['autoclose'] / abs($forum['autoclose']);
			}

			$viewaccess = $postaccess = $replyaccess = $getattachaccess = $postattachaccess = '';

			$query = $db->query("SELECT m.username, a.* FROM {$tablepre}access a LEFT JOIN {$tablepre}members m USING (uid) WHERE a.fid='$fid'");
			while($access = $db->fetch_array($query)) {
				$member = ", <a href=\"$BASESCRIPT?action=members&operation=access&uid=$access[uid]\" target=\"_blank\">$access[username]</a>";
				$viewaccess .= $access['allowview'] > 0 ? $member : NULL;
				$postaccess .= $access['allowpost'] > 0  ? $member : NULL;
				$replyaccess .= $access['allowreply'] > 0  ? $member : NULL;
				$getattachaccess .= $access['allowgetattach'] > 0  ? $member : NULL;
				$postattachaccess .= $access['allowpostattach'] > 0  ? $member : NULL;
			}
			unset($member);

			$forum['typemodels'] = unserialize($forum['typemodels']);

                	if($forum['threadtypes']) {
				$forum['threadtypes'] = unserialize($forum['threadtypes']);
				$forum['threadtypes']['status'] = 1;
			} else {
				$forum['threadtypes'] = array('status' => 0, 'required' => 0, 'listable' => 0, 'prefix' => 0, 'options' => array());
			}

			if($forum['threadsorts']) {
				$forum['threadsorts'] = unserialize($forum['threadsorts']);
				$forum['threadsorts']['status'] = 1;
			} else {
				$forum['threadsorts'] = array('status' => 0, 'required' => 0, 'prefix' => 0, 'options' => array());
			}

			if($forum['threadplugin']) {
				$forum['threadplugin'] = unserialize($forum['threadplugin']);
			}

			$typeselect = $sortselect = '';
			$typemodelid = array();

			$query = $db->query("SELECT * FROM {$tablepre}threadtypes ORDER BY displayorder");
			while($type = $db->fetch_array($query)) {
				$typemodelid[] = $type['modelid'];
				$typeselected = array();
				$enablechecked = '';

				$keysort = $type['special'] ? 'threadsorts' : 'threadtypes';
				if(isset($forum[$keysort]['flat'][$type['typeid']])) {
					$enablechecked = ' checked="checked"';
					$typeselected[1] = ' selected="selected"';
				} elseif(isset($forum[$keysort]['selectbox'][$type['typeid']])) {
					$enablechecked = ' checked="checked"';
					$typeselected[2] = ' selected="selected"';
				} else {
					$typeselected[1] = ' selected="selected"';
				}

				$showtype = TRUE;
				if($type['special'] && !@include_once DISCUZ_ROOT.'./forumdata/cache/threadsort_'.$type['typeid'].'.php') {
					$showtype = FALSE;
				}
				if(!$type['special']) {
					$typeselect .= showtablerow('', array('class="td25"'), array(
						'<input type="checkbox" name="threadtypesnew[options][enable]['.$type['typeid'].']" value="1" class="checkbox"'.$enablechecked.' />',
						$type['name'],
						$type['description'],
						"<select name=\"threadtypesnew[options][{$type[typeid]}]\"><option value=\"1\" $typeselected[1]>$lang[forums_edit_threadtypes_use_cols]</option><option value=\"2\" $typeselected[2]>$lang[forums_edit_threadtypes_use_choice]</option></select>",
					), TRUE);
				} else {
					$typeselected[3] = $forum['threadsorts']['show'][$type['typeid']] ? ' checked="checked"' : '';
					$sortselect .= $showtype ? showtablerow('', array('class="td25"'), array(
						'<input type="checkbox" name="threadsortsnew[options][enable]['.$type['typeid'].']" value="1" class="checkbox"'.$enablechecked.' />',
						$type['name'],
						$type['description'],
						"<select name=\"threadsortsnew[options][{$type[typeid]}]\"><option value=\"1\" $typeselected[1]>$lang[forums_edit_threadtypes_use_cols]</option><option value=\"2\" $typeselected[2]>$lang[forums_edit_threadtypes_use_choice]</option></select>",
						"<input class=\"checkbox\" type=\"checkbox\" name=\"threadsortsnew[options][show][{$type[typeid]}]\" value=\"3\" $typeselected[3] />",
						"<input class=\"radio\" type=\"radio\" name=\"threadsortsnew[defaultshow]\" value=\"$type[typeid]\" ".($forum['threadsorts']['defaultshow'] == $type['typeid'] ? 'checked' : '')." />"
					), TRUE) : '';
				}
			}

			$typeselect = $typeselect ? $typeselect : showtablerow('', array('class="td25"'), $lang['forums_edit_threadtypes_nonexistence'], TRUE);
			$sortselect = $sortselect ? $sortselect : showtablerow('', array('class="td25"'), $lang['forums_edit_threadsorts_nonexistence'], TRUE);

                	$num = 0;
                	$typemodelshow = FALSE;
                	$typemodelselect = '<ul class="nofloat" onmouseover="altStyle(this);">';
			$query = $db->query("SELECT * FROM {$tablepre}typemodels ORDER BY displayorder");
			while($model = $db->fetch_array($query)) {
				$num++;
				$modelchecked = $forum['typemodels'][$model['id']] ? 'checked' : '';
				if(in_array($model['id'], $typemodelid)) {
					$typemodelshow = TRUE;
					$typemodelselect .=  "<li".($modelchecked ? ' class="checked"' : '')."><input class=\"checkbox\" type=\"checkbox\" name=\"typemodel[]\" value=\"$model[id]\" $modelchecked>$model[name]</li>";
				}
       			}
       			$typemodelselect .= '</ul>';

			$forum['postcredits'] = $forum['postcredits'] ? unserialize($forum['postcredits']) : array();
			$forum['replycredits'] = $forum['replycredits'] ? unserialize($forum['replycredits']) : array();
			$forum['digestcredits'] = $forum['digestcredits'] ? unserialize($forum['digestcredits']) : array();
			$forum['postattachcredits'] = $forum['postattachcredits'] ? unserialize($forum['postattachcredits']) : array();
			$forum['getattachcredits'] = $forum['getattachcredits'] ? unserialize($forum['getattachcredits']) : array();
			$simplebin = sprintf('%08b', $forum['simple']);
			$forum['defaultorderfield'] = bindec(substr($simplebin, 0, 2));
			$forum['defaultorder'] = ($forum['simple'] & 32) ? 1 : 0;
			$forum['subforumsindex'] = bindec(substr($simplebin, 3, 2));
			$forum['subforumsindex'] = $forum['subforumsindex'] == 0 ? -1 : ($forum['subforumsindex'] == 2 ? 0 : 1);
			$forum['simple'] = $forum['simple'] & 1;
			$forum['modrecommend'] = $forum['modrecommend'] ? unserialize($forum['modrecommend']) : '';
			$forum['formulaperm'] = unserialize($forum['formulaperm']);
			$forum['medal'] = $forum['formulaperm']['medal'];
			$forum['formulapermmessage'] = $forum['formulaperm']['message'];
			$forum['formulapermusers'] = $forum['formulaperm']['users'];
			$forum['formulaperm'] = $forum['formulaperm'][0];
			$forum['extra'] = unserialize($forum['extra']);
			$forum['threadsorts']['default'] = $forum['threadsorts']['defaultshow'] ? 1 : 0;

			showtagheader('div', 'basic', $anchor == 'basic');
			showtableheader('forums_edit_basic', 'nobottom');
			showsetting('forums_edit_basic_name', 'namenew', $forum['name'], 'text');
			showsetting('forums_edit_base_name_color', 'extra[namecolor]', $forum['extra']['namecolor'], 'color');
			showsetting('forums_edit_basic_scheme', '', '', $projectselect);
			showsetting('forums_edit_basic_display', array('statusnew', array(
					array(1, $lang['forums_edit_basic_display_yes']),
					array(0, $lang['forums_edit_basic_display_no']),
					array(2, $lang['forums_edit_basic_display_select'])
			)), $forum['status'], 'mradio');
			showsetting('forums_edit_basic_up', '', '', $fupselect);
			showsetting('forums_edit_basic_redirect', 'redirectnew', $forum['redirect'], 'text');
			showsetting('forums_edit_basic_icon', 'iconnew', $forum['icon'], 'text');
			showsetting('forums_edit_basic_description', 'descriptionnew', html2bbcode($forum['description']), 'textarea');
			showsetting('forums_edit_basic_rules', 'rulesnew', html2bbcode($forum['rules']), 'textarea');
			showsetting('forums_edit_basic_keyword', 'keywordsnew', $forum['keywords'], 'text');
			showsetting('forums_edit_basic_binddomain', 'binddomainnew', $forumdomains[$fid], 'text');
			showtablefooter();
			showtagfooter('div');

			showtagheader('div', 'extend', $anchor == 'extend');
			showtableheader('forums_edit_extend', 'nobottom');
			showsetting('forums_edit_extend_style', '', '', $styleselect);
			showsetting('forums_edit_extend_sub_horizontal', 'forumcolumnsnew', $forum['forumcolumns'], 'text');
			showsetting('forums_edit_extend_subforumsindex', array('subforumsindexnew', array(
				array(-1, $lang['default']),
				array(1, $lang['yes']),
				array(0, $lang['no'])
			), 1), $forum['subforumsindex'], 'mradio');
			showsetting('forums_edit_extend_simple', 'simplenew', $forum['simple'], 'radio');
			showsetting('forums_edit_extend_recommend_top', 'allowglobalsticknew', $forum['allowglobalstick'], 'radio');
			showsetting('forums_edit_extend_defaultorderfield', array('defaultorderfieldnew', array(
					array(0, $lang['forums_edit_extend_order_lastpost']),
					array(1, $lang['forums_edit_extend_order_starttime']),
					array(2, $lang['forums_edit_extend_order_replies']),
					array(3, $lang['forums_edit_extend_order_views'])
			)), $forum['defaultorderfield'], 'mradio');
			showsetting('forums_edit_extend_defaultorder', array('defaultordernew', array(
					array(0, $lang['forums_edit_extend_order_desc']),
					array(1, $lang['forums_edit_extend_order_asc'])
			)), $forum['defaultorder'], 'mradio');
			showsetting('forums_edit_extend_threadcache', 'threadcachesnew', $forum['threadcaches'], 'text');
			showsetting('forums_edit_extend_edit_rules', 'alloweditrulesnew', $forum['alloweditrules'], 'radio');
			if($sideselect) {
				showsetting('forums_edit_extend_sideselect', '', '', $sideselect[0]);
			}
			showsetting('forums_edit_extend_recommend', 'modrecommendnew[open]', $forum['modrecommend']['open'], 'radio', '', 1);
			showsetting('forums_edit_extend_recommend_sort', array('modrecommendnew[sort]', array(
				array(1, $lang['forums_edit_extend_recommend_sort_auto']),
				array(0, $lang['forums_edit_extend_recommend_sort_manual']),
				array(2, $lang['forums_edit_extend_recommend_sort_mix']))), $forum['modrecommend']['sort'], 'mradio');
			showsetting('forums_edit_extend_recommend_orderby', array('modrecommendnew[orderby]', array(
				array(0, $lang['forums_edit_extend_recommend_orderby_dateline']),
				array(1, $lang['forums_edit_extend_recommend_orderby_lastpost']),
				array(2, $lang['forums_edit_extend_recommend_orderby_views']),
				array(3, $lang['forums_edit_extend_recommend_orderby_replies']),
				array(4, $lang['forums_edit_extend_recommend_orderby_digest']),
				array(5, $lang['forums_edit_extend_recommend_orderby_recommend']),
				array(6, $lang['forums_edit_extend_recommend_orderby_heats']),
				)), $forum['modrecommend']['orderby'], 'mradio');
			showsetting('forums_edit_extend_recommend_num', 'modrecommendnew[num]', $forum['modrecommend']['num'], 'text');
			showsetting('forums_edit_extend_recommend_imagenum', 'modrecommendnew[imagenum]', $forum['modrecommend']['imagenum'], 'text');
			showsetting('forums_edit_extend_recommend_imagesize', array('modrecommendnew[imagewidth]', 'modrecommendnew[imageheight]'), array(intval($forum['modrecommend']['imagewidth']), intval($forum['modrecommend']['imageheight'])), 'multiply');
			showsetting('forums_edit_extend_recommend_maxlength', 'modrecommendnew[maxlength]', $forum['modrecommend']['maxlength'], 'text');
			showsetting('forums_edit_extend_recommend_cachelife', 'modrecommendnew[cachelife]', $forum['modrecommend']['cachelife'], 'text');
			showsetting('forums_edit_extend_recommend_dateline', 'modrecommendnew[dateline]', $forum['modrecommend']['dateline'], 'text');
			showtablefooter();
			showtagfooter('div');

			showtagheader('div', 'posts', $anchor == 'posts');
			showtableheader('forums_edit_posts', 'nobottom');
			showsetting('forums_edit_posts_modposts', array('modnewpostsnew', array(
				array(0, $lang['none']),
				array(1, $lang['forums_edit_posts_modposts_threads']),
				array(2, $lang['forums_edit_posts_modposts_posts'])
			)), $forum['modnewposts'], 'mradio');
			showsetting('forums_edit_posts_alloweditpost', 'alloweditpostnew', $forum['alloweditpost'], 'radio');
			showsetting('forums_edit_posts_recyclebin', 'recyclebinnew', $forum['recyclebin'], 'radio');
			showsetting('forums_edit_posts_html', 'allowhtmlnew', $forum['allowhtml'], 'radio');
			showsetting('forums_edit_posts_bbcode', 'allowbbcodenew', $forum['allowbbcode'], 'radio');
			showsetting('forums_edit_posts_imgcode', 'allowimgcodenew', $forum['allowimgcode'], 'radio');
			showsetting('forums_edit_posts_mediacode', 'allowmediacodenew', $forum['allowmediacode'], 'radio');
			showsetting('forums_edit_posts_smilies', 'allowsmiliesnew', $forum['allowsmilies'], 'radio');
			showsetting('forums_edit_posts_jammer', 'jammernew', $forum['jammer'], 'radio');
			showsetting('forums_edit_posts_anonymous', 'allowanonymousnew', $forum['allowanonymous'], 'radio');
			showsetting('forums_edit_posts_disablewatermark', 'disablewatermarknew', $forum['disablewatermark'], 'radio');

			if($tagstatus) {
				showsetting('forums_edit_posts_tagstatus', array('allowtagnew', array(
					array(0, $lang['forums_edit_posts_tagstatus_none']),
					array(1, $lang['forums_edit_posts_tagstatus_use']),
					array(2, $lang['forums_edit_posts_tagstatus_quired'])
				)), $forum['allowtag'], 'mradio');
			}

			showsetting('forums_edit_posts_allowpostspecial', array('allowpostspecialnew', array(
				$lang['thread_poll'],
				$lang['thread_trade'],
				$lang['thread_reward'],
				$lang['thread_activity'],
				$lang['thread_debate']
			)), $forum['allowpostspecial'], 'binmcheckbox');
			$threadpluginarray = '';
			foreach($threadplugins as $tpid => $data) {
				$threadpluginarray[] = array($tpid, $data['name']);
			}
			if($threadpluginarray) {
				showsetting('forums_edit_posts_threadplugin', array('threadpluginnew', $threadpluginarray), $forum['threadplugin'], 'mcheckbox');
			}
			showsetting('forums_edit_posts_allowspecialonly', 'allowspecialonlynew', $forum['allowspecialonly'], 'radio');
			if(!empty($tradetypes) && is_array($tradetypes)) {
				$forum['tradetypes'] = unserialize($forum['tradetypes']);
				$alldefault = !$forum['tradetypes'];
				$tradetypearray = '';
				foreach($tradetypes as $typeid => $typename) {
					$tradetypearray[] = array($typeid, $typename);
					if($alldefault) {
						$forum['tradetypes'][] = $typeid;
					}
				}
				showsetting('forums_edit_posts_trade_type', array('tradetypesnew', $tradetypearray), $forum['tradetypes'], 'mcheckbox');
			}
			showsetting('forums_edit_posts_autoclose', array('autoclosenew', array(
				array(0, $lang['forums_edit_posts_autoclose_none'], array('autoclose_time' => 'none')),
				array(1, $lang['forums_edit_posts_autoclose_dateline'], array('autoclose_time' => '')),
				array(-1, $lang['forums_edit_posts_autoclose_lastpost'], array('autoclose_time' => ''))
			)), $forum['autoclose'], 'mradio');
			showtagheader('tbody', 'autoclose_time', $forum['autoclose'], 'sub');
			showsetting('forums_edit_posts_autoclose_time', 'autoclosetimenew', $forum['autoclosetime'], 'text');
			showtagfooter('tbody');
			showsetting('forums_edit_posts_attach_ext', 'attachextensionsnew', $forum['attachextensions'], 'text');
			showtablefooter();
			showtagfooter('div');

			showtagheader('div', 'credits', $anchor == 'credits');
			showtableheader('forums_edit_credits_policy', 'fixpadding');
			$customcreditspolicy = '';
			echo '<tr><th>'.$lang['credits_id'].'</th>';
			foreach($extcredits as $i => $extcredit) {
				echo '<th valign="top">extcredits'.$i.'<br />('.$extcredit['title'].')</th>';
			}
			echo '</tr>';
			if(is_array($extcredits)) {
				foreach(array('post', 'reply', 'digest', 'postattach', 'getattach') as $policy) {
					$row = array($lang['settings_credits_policy_'.$policy]);
					$rowclass = array('class="td22"');
					foreach($extcredits as $i => $extcredit) {
	        				$row[] = '<input type="text" class="txt" size="2" name="'.$policy.'creditsnew['.$i.']" value="'.$forum[$policy.'credits'][$i].'" />';
	        				$rowclass[] = 'class="td28"';
					}
					$customcreditspolicy .= showtablerow('title="'.$lang['settings_credits_policy_'.$policy.'_comment'].'"', $rowclass, $row, TRUE);
				}
			}
			echo $customcreditspolicy;
			showtablerow('', 'class="lineheight" colspan="9"', $lang['forums_edit_credits_comment']);

			showtablefooter();
			showtagfooter('div');

			if($allowthreadtypes) {
				echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[
			[1,'<input type="checkbox" class="checkbox" name="newenable[]" checked="checked" />', 'td25'],
			[1,'<input type="text" class="txt" name="newname[]" size="15" />'],
			[1,'<input type="text" class="txt" name="newdescription[]" size="15" />'],
			[1,'<select name="newoptions[]"><option value="1" selected="selected">$lang[forums_edit_threadtypes_use_cols]</option><option value="2">$lang[forums_edit_threadtypes_use_choice]</option></select>'],
			[1,'']
		],
	];
</script>
EOT;
				showtagheader('div', 'threadtypes', $anchor == 'threadtypes');

				showtableheader('forums_edit_threadtypes', 'nobottom');
				showsetting('forums_edit_threadtypes_status', array('threadtypesnew[status]', array(
					array(1, $lang['yes'], array('threadtypes_config' => '', 'threadtypes_manage' => '')),
					array(0, $lang['no'], array('threadtypes_config' => 'none', 'threadtypes_manage' => 'none'))
				), TRUE), $forum['threadtypes']['status'], 'mradio');
				showtagheader('tbody', 'threadtypes_config', $forum['threadtypes']['status']);
				showsetting('forums_edit_threadtypes_required', 'threadtypesnew[required]', $forum['threadtypes']['required'], 'radio');
				showsetting('forums_edit_threadtypes_listable', 'threadtypesnew[listable]', $forum['threadtypes']['listable'], 'radio');
				showsetting('forums_edit_threadtypes_prefix', 'threadtypesnew[prefix]', $forum['threadtypes']['prefix'], 'radio');
				showtagfooter('tbody');
				showtablefooter();

				showtagheader('div', 'threadtypes_manage', $forum['threadtypes']['status']);
				showtableheader('', 'noborder fixpadding');
				showsubtitle(array('enable', 'forums_edit_threadtypes_name', 'forums_edit_threadtypes_note', 'forums_edit_threadtypes_showtype', 'forums_edit_threadtypes_show'));
				echo $typeselect;
				echo '<tr><td colspan="6"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.lang('threadtype_infotypes_add').'</a></div></td></tr>';
				echo '<tr><td class="lineheight" colspan="6">'.$lang['forums_edit_threadtypes_comment'].'</td></tr>';
				showtablefooter();
				showtagfooter('div');

				showtagfooter('div');

				showtagheader('div', 'threadsorts', $anchor == 'threadsorts');

				showtableheader('forums_edit_threadsorts', 'nobottom');
				showsetting('forums_edit_threadsorts_status', array('threadsortsnew[status]', array(
					array(1, $lang['yes'], array('threadsorts_config' => '', 'threadsorts_manage' => '')),
					array(0, $lang['no'], array('threadsorts_config' => 'none', 'threadsorts_manage' => 'none'))
				), TRUE), $forum['threadsorts']['status'], 'mradio');
				showtagheader('tbody', 'threadsorts_config', $forum['threadsorts']['status']);
				showsetting('forums_edit_threadtypes_required', 'threadsortsnew[required]', $forum['threadsorts']['required'], 'radio');
				showsetting('forums_edit_threadtypes_prefix', 'threadsortsnew[prefix]', $forum['threadsorts']['prefix'], 'radio');
				showsetting('forums_edit_threadsorts_default', 'threadsortsnew[default]', $forum['threadsorts']['default'], 'radio');
				if($typemodelshow) {
					showsetting('forums_edit_threadsorts_typemodel', '', '', $typemodelselect);
				}
				showtagfooter('tbody');
				showtablefooter();

				showtagheader('div', 'threadsorts_manage', $forum['threadsorts']['status']);
				showtableheader('', 'noborder fixpadding');
				showsubtitle(array('enable', 'forums_edit_threadtypes_name', 'forums_edit_threadtypes_note', 'forums_edit_threadtypes_showtype', 'forums_edit_threadtypes_show', 'forums_edit_threadtypes_defaultshow'));
				echo $sortselect;
				showtablefooter();
				showtagfooter('div');

				showtagfooter('div');
			}

			showtagheader('div', 'perm', $anchor == 'perm');
			showtableheader('forums_edit_perm_forum', 'nobottom');
			showsetting('forums_edit_perm_passwd', 'passwordnew', $forum['password'], 'text');
			showsetting('forums_edit_perm_users', 'formulapermusersnew', stripslashes($forum['formulapermusers']), 'textarea');
			$colums = array();
			@include_once DISCUZ_ROOT.'./forumdata/cache/cache_medals.php';
			foreach($_DCACHE['medals'] as $medalid => $medal) {
				$colums[] = array($medalid, $medal['name']);
			}
			showtagheader('tbody', '', $medalstatus);
			showsetting('forums_edit_perm_medal', array('medalnew', $colums), $forum['medal'], 'mcheckbox');
			showtagfooter('tbody');
			showtablefooter();
			showtableheader('forums_edit_perm_forum', 'noborder fixpadding');
			showsubtitle(array(
				'',
				'<input class="checkbox" type="checkbox" name="chkall1" onclick="checkAll(\'prefix\', this.form, \'viewperm\', \'chkall1\')" id="chkall1" /><label for="chkall1"> '.$lang['forums_edit_perm_view'].'</label>',
				'<input class="checkbox" type="checkbox" name="chkall2" onclick="checkAll(\'prefix\', this.form, \'postperm\', \'chkall2\')" id="chkall2" /><label for="chkall2"> '.$lang['forums_edit_perm_post'].'</label>',
				'<input class="checkbox" type="checkbox" name="chkall3" onclick="checkAll(\'prefix\', this.form, \'replyperm\', \'chkall3\')" id="chkall3" /><label for="chkall3"> '.$lang['forums_edit_perm_reply'].'</label>',
				'<input class="checkbox" type="checkbox" name="chkall4" onclick="checkAll(\'prefix\', this.form, \'getattachperm\', \'chkall4\')" id="chkall4" /><label for="chkall4"> '.$lang['forums_edit_perm_getattach'].'</label>',
				'<input class="checkbox" type="checkbox" name="chkall5" onclick="checkAll(\'prefix\', this.form, \'postattachperm\', \'chkall5\')" id="chkall5" /><label for="chkall5"> '.$lang['forums_edit_perm_postattach'].'</label>'

			));

			foreach(array('member', 'special', 'specialadmin', 'system') as $type) {
				$tgroups = is_array($groups[$type]) ? $groups[$type] : array();
				showtablerow('', '', array('<b>'.$lang['usergroups_'.$type].'</b>'));
				foreach($tgroups as $group) {
					$colums = array('<input class="checkbox" title="'.$lang['select_all'].'" type="checkbox" name="chkallv'.$group['groupid'].'" onclick="checkAll(\'value\', this.form, '.$group['groupid'].', \'chkallv'.$group['groupid'].'\')" id="chkallv_'.$group['groupid'].'" /><label for="chkallv_'.$group['groupid'].'"> '.$group['grouptitle'].'</label>');
					foreach($perms as $perm) {
						$checked = strstr($forum[$perm], "\t$group[groupid]\t") ? 'checked="checked"' : NULL;
						$colums[] = '<input class="checkbox" type="checkbox" name="'.$perm.'[]" value="'.$group['groupid'].'" chkvalue="'.$group['groupid'].'" '.$checked.'>';
					}
					showtablerow('', '', $colums);
				}
			}
			showtablerow('', 'class="lineheight" colspan="6"', $lang['forums_edit_perm_forum_comment']);
			showtablefooter();

			showtableheader('forums_edit_perm_formula', 'fixpadding');
			$formulareplace .= '\'<u>'.$lang['settings_credits_formula_digestposts'].'</u>\',\'<u>'.$lang['settings_credits_formula_posts'].'</u>\',\'<u>'.$lang['settings_credits_formula_oltime'].'</u>\',\'<u>'.$lang['settings_credits_formula_pageviews'].'</u>\'';

?>
<script type="text/JavaScript">

	function isUndefined(variable) {
		return typeof variable == 'undefined' ? true : false;
	}

	function insertunit(text, textend) {
		$('formulapermnew').focus();
		textend = isUndefined(textend) ? '' : textend;
		if(!isUndefined($('formulapermnew').selectionStart)) {
			var opn = $('formulapermnew').selectionStart + 0;
			if(textend != '') {
				text = text + $('formulapermnew').value.substring($('formulapermnew').selectionStart, $('formulapermnew').selectionEnd) + textend;
			}
			$('formulapermnew').value = $('formulapermnew').value.substr(0, $('formulapermnew').selectionStart) + text + $('formulapermnew').value.substr($('formulapermnew').selectionEnd);
		} else if(document.selection && document.selection.createRange) {
			var sel = document.selection.createRange();
			if(textend != '') {
				text = text + sel.text + textend;
			}
			sel.text = text.replace(/\r?\n/g, '\r\n');
			sel.moveStart('character', -strlen(text));
		} else {
			$('formulapermnew').value += text;
		}
		formulaexp();
	}

	var formulafind = new Array('digestposts', 'posts', 'oltime', 'pageviews');
	var formulareplace = new Array(<?php echo $formulareplace?>);
	function formulaexp() {
		var result = $('formulapermnew').value;
<?php

	$extcreditsbtn = '';
	for($i = 1; $i <= 8; $i++) {
		$extcredittitle = $extcredits[$i]['title'] ? $extcredits[$i]['title'] : $lang['settings_credits_formula_extcredits'].$i;
		echo 'result = result.replace(/extcredits'.$i.'/g, \'<u>'.str_replace("'", "\'", $extcredittitle).'</u>\');';
		$extcreditsbtn .= '<a href="###" onclick="insertunit(\'extcredits'.$i.'\')">'.$extcredittitle.'</a> &nbsp;';
	}

	$profilefields = '';
	$query = $db->query("SELECT * FROM {$tablepre}profilefields WHERE available='1' AND unchangeable='1'");
	while($profilefield = $db->fetch_array($query)) {
		echo 'result = result.replace(/field_'.$profilefield['fieldid'].'/g, \'<u>'.str_replace("'", "\'", $profilefield['title']).'</u>\');';
		$profilefields .= '<a href="###" onclick="insertunit(\' field_'.$profilefield['fieldid'].' \')">&nbsp;'.$profilefield['title'].'&nbsp;</a>&nbsp;';
	}

	echo 'result = result.replace(/regdate/g, \'<u>'.$lang['forums_edit_perm_formula_regdate'].'</u>\');';
	echo 'result = result.replace(/regday/g, \'<u>'.$lang['forums_edit_perm_formula_regday'].'</u>\');';
	echo 'result = result.replace(/regip/g, \'<u>'.$lang['forums_edit_perm_formula_regip'].'</u>\');';
	echo 'result = result.replace(/lastip/g, \'<u>'.$lang['forums_edit_perm_formula_lastip'].'</u>\');';
	echo 'result = result.replace(/buyercredit/g, \'<u>'.$lang['forums_edit_perm_formula_buyercredit'].'</u>\');';
	echo 'result = result.replace(/sellercredit/g, \'<u>'.$lang['forums_edit_perm_formula_sellercredit'].'</u>\');';
	echo 'result = result.replace(/digestposts/g, \'<u>'.$lang['settings_credits_formula_digestposts'].'</u>\');';
	echo 'result = result.replace(/posts/g, \'<u>'.$lang['settings_credits_formula_posts'].'</u>\');';
	echo 'result = result.replace(/threads/g, \'<u>'.$lang['settings_credits_formula_threads'].'</u>\');';
	echo 'result = result.replace(/oltime/g, \'<u>'.$lang['settings_credits_formula_oltime'].'</u>\');';
	echo 'result = result.replace(/pageviews/g, \'<u>'.$lang['settings_credits_formula_pageviews'].'</u>\');';
	echo 'result = result.replace(/and/g, \'&nbsp;&nbsp;<b>'.$lang['forums_edit_perm_formula_and'].'</b>&nbsp;&nbsp;\');';
	echo 'result = result.replace(/or/g, \'&nbsp;&nbsp;<b>'.$lang['forums_edit_perm_formula_or'].'</b>&nbsp;&nbsp;\');';
	echo 'result = result.replace(/>=/g, \'&ge;\');';
	echo 'result = result.replace(/<=/g, \'&le;\');';
	echo 'result = result.replace(/==/g, \'=\');';

?>
		$('formulapermexp').innerHTML = result;
	}
</script>
<tr><td colspan="2"><div class="extcredits">
<?php echo $extcreditsbtn?>
<a href="###" onclick="insertunit(' regdate ')">&nbsp;<?php echo lang('forums_edit_perm_formula_regdate')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' regday ')">&nbsp;<?php echo lang('forums_edit_perm_formula_regday')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' regip ')">&nbsp;<?php echo lang('forums_edit_perm_formula_regip')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' lastip ')">&nbsp;<?php echo lang('forums_edit_perm_formula_lastip')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' buyercredit ')">&nbsp;<?php echo lang('forums_edit_perm_formula_buyercredit')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' sellercredit ')">&nbsp;<?php echo lang('forums_edit_perm_formula_sellercredit')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' digestposts ')"><?php echo lang('forums_edit_perm_formula_digestposts')?></a>&nbsp;
<a href="###" onclick="insertunit(' posts ')"><?php echo lang('forums_edit_perm_formula_posts')?></a>&nbsp;
<a href="###" onclick="insertunit(' threads ')"><?php echo lang('forums_edit_perm_formula_threads')?></a>&nbsp;
<a href="###" onclick="insertunit(' oltime ')"><?php echo lang('forums_edit_perm_formula_oltime')?></a>&nbsp;
<a href="###" onclick="insertunit(' pageviews ')"><?php echo lang('forums_edit_perm_formula_pageviews')?></a><?php echo $profilefields;?><br />
<a href="###" onclick="insertunit(' + ')">&nbsp;+&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' - ')">&nbsp;-&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' * ')">&nbsp;*&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' / ')">&nbsp;/&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' > ')">&nbsp;>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' >= ')">&nbsp;>=&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' < ')">&nbsp;<&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' <= ')">&nbsp;<=&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' == ')">&nbsp;=&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' != ')">&nbsp;!=&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' (', ') ')">&nbsp;(&nbsp;)&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' and ')">&nbsp;<?php echo lang('forums_edit_perm_formula_and')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' or ')">&nbsp;<?php echo lang('forums_edit_perm_formula_or')?>&nbsp;</a>&nbsp;<br />



<div id="formulapermexp" class="margintop marginbot diffcolor2"><?php echo $formulapermexp?></div>
</div>
<textarea name="formulapermnew" id="formulapermnew" class="marginbot" style="width:80%" rows="3" onkeyup="formulaexp()"><?php echo dhtmlspecialchars($forum['formulaperm'])?></textarea>
<script type="text/JavaScript">formulaexp()</script>
<br /><span class="smalltxt"><?=$lang['forums_edit_perm_formula_comment']?></span>
<br /><?php echo lang('creditwizard_current_formula_notice')?>
</td></tr>
<?php

			showtablefooter();
			showtableheader('', 'noborder fixpadding');
			showsetting('forums_edit_perm_formulapermmessage', 'formulapermmessagenew', $forum['formulapermmessage'], 'textarea');
			showtablefooter();
			showtagfooter('div');

			showtableheader('', 'notop');
			showsubmit('detailsubmit', 'submit', '', $forum['type'] != 'group' ? '<input type="submit" class="btn" name="saveconfigsubmit" value="'.$lang['saveconf'].'">' : '');
			showtablefooter();

		}

	showformfooter();

	} else {

		if(strlen($namenew) > 50) {
			cpmsg('forums_name_toolong', '', 'error');
		}

		if($formulapermnew && !preg_match("/^(\{|\}|\+|\-|\*|\/|\.|>|<|=|!|\d|\s|\(|\)|extcredits[1-8]|regdate|regday|regip|lastip|buyercredit|sellercredit|field\_\d+|digestposts|posts|threads|pageviews|oltime|and|or)+$/", $formulapermnew) ||
			!is_null(@eval(preg_replace(
				array("/(regdate|regday|regip|lastip|buyercredit|sellercredit|field\_\d+|digestposts|posts|threads|pageviews|oltime|extcredits[1-8])/", "/\{([\d\.\-]+?)\}/"),
				array("\$\\1", "'\\1'"), $formulapermnew).';'))) {
			cpmsg('forums_formulaperm_error', '', 'error');
		}

		$formulapermary[0] = $formulapermnew;
		$formulapermary[1] = preg_replace(
			array("/(digestposts|posts|threads|pageviews|oltime|extcredits[1-8])/", "/(regdate|regday|regip|lastip|buyercredit|sellercredit|field\_\d+)/"),
			array("\$_DSESSION['\\1']", "\$memberformula['\\1']"),
			$formulapermnew);
		$formulapermary['medal'] = $medalnew;
		$formulapermary['message'] = $formulapermmessagenew;
		$formulapermary['users'] = $formulapermusersnew;
		$formulapermnew = addslashes(serialize($formulapermary));

		if($type == 'group') {

			if($namenew) {

				if($foruminfosidestatus) {
					$infosidestatusnew = $infosidestatus;
					unset($infosidestatusnew['f'.$fid]);
					$foruminfosidestatus[0] != $infosidestatus[2] && $foruminfosidestatus[0] != '' && $infosidestatusnew['f'.$fid][0] = $foruminfosidestatus[0];
					$foruminfosidestatus['posts'] != $infosidestatus['posts'] && $foruminfosidestatus['posts'] != '' && $infosidestatusnew['f'.$fid]['posts'] = $foruminfosidestatus['posts'];
					if($infosidestatus != $infosidestatusnew) {
						$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('infosidestatus', '".(addslashes(serialize($infosidestatusnew)))."')");
						updatecache('settings');
					}
				}
				$forumcolumnsnew = $forumcolumnsnew > 1 ? intval($forumcolumnsnew) : 0;

				$db->query("UPDATE {$tablepre}forums SET name='$namenew',forumcolumns='".$forumcolumnsnew."',status='".intval($statusnew)."' WHERE fid='$fid'");

				$extranew = is_array($_POST['extra']) ? $_POST['extra'] : array();
				$extranew = serialize($extranew);
				$db->query("UPDATE {$tablepre}forumfields SET extra='$extranew' WHERE fid='$fid'");
				updatecache('forums');

				cpmsg('forums_edit_succeed', $BASESCRIPT.'?action=forums', 'succeed');
			} else {
				cpmsg('forums_edit_name_invalid', '', 'error');
			}

		} else {

			require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

			$extensionarray = array();
			foreach(explode(',', $attachextensionsnew) as $extension) {
				if($extension = trim($extension)) {
					$extensionarray[] = $extension;
				}
			}
			$attachextensionsnew = implode(', ', $extensionarray);

			foreach($perms as $perm) {
				${$perm.'new'} = is_array($$perm) && !empty($$perm) ? "\t".implode("\t", $$perm)."\t" : '';
			}

			$fupadd = '';
			if($fupnew != $forum['fup']) {
				$query = $db->query("SELECT fid FROM {$tablepre}forums WHERE fup='$fid'");
				if($db->num_rows($query)) {
					cpmsg('forums_edit_sub_notnull', '', 'error');
				}

				$fup = $db->fetch_first("SELECT fid, type, inheritedmod FROM {$tablepre}forums WHERE fid='$fupnew'");

				$fupadd = ", type='".($fup['type'] == 'forum' ? 'sub' : 'forum')."', fup='$fup[fid]'";
				$db->query("DELETE FROM {$tablepre}moderators WHERE fid='$fid' AND inherited='1'");
				$query = $db->query("SELECT * FROM {$tablepre}moderators WHERE fid='$fupnew' ".($fup['inheritedmod'] ? '' : "AND inherited='1'"));
				while($mod = $db->fetch_array($query)) {
					$db->query("REPLACE INTO {$tablepre}moderators (uid, fid, displayorder, inherited)
						VALUES ('$mod[uid]', '$fid', '0', '1')");
				}

				$moderators = $tab = '';
				$query = $db->query("SELECT m.username FROM {$tablepre}members m, {$tablepre}moderators mo WHERE mo.fid='$fid' AND mo.inherited='0' AND m.uid=mo.uid ORDER BY mo.displayorder");
				while($mod = $db->fetch_array($query)) {
					$moderators .= $tab.addslashes($mod['username']);
					$tab = "\t";
				}
				$db->query("UPDATE {$tablepre}forumfields SET moderators='$moderators' WHERE fid='$fid'");
			}

			$allowpostspecialtrade = intval($allowpostspecialnew[2]);
			$allowpostspecialnew = bindec(intval($allowpostspecialnew[6]).intval($allowpostspecialnew[5]).intval($allowpostspecialnew[4]).intval($allowpostspecialnew[3]).intval($allowpostspecialnew[2]).intval($allowpostspecialnew[1]));
			$allowspecialonlynew = $allowpostspecialnew || $threadplugins && $threadpluginnew ? $allowspecialonlynew : 0;
			$forumcolumnsnew = $forumcolumnsnew > 1 ? intval($forumcolumnsnew) : 0;
			$threadcachesnew = max(0, min(100, intval($threadcachesnew)));
			$subforumsindexnew = $subforumsindexnew == -1 ? 0 : ($subforumsindexnew == 0 ? 2 : 1);
			$simplenew = bindec(sprintf('%02d', decbin($defaultorderfieldnew)).$defaultordernew.sprintf('%02d', decbin($subforumsindexnew)).'00'.$simplenew);
			$allowglobalsticknew = $allowglobalsticknew ? 1 : 0;

			$db->query("UPDATE {$tablepre}forums SET status='$statusnew', name='$namenew', styleid='$styleidnew', alloweditpost='$alloweditpostnew',
				allowpostspecial='$allowpostspecialnew', allowspecialonly='$allowspecialonlynew', allowhtml='$allowhtmlnew', allowbbcode='$allowbbcodenew', allowimgcode='$allowimgcodenew', allowmediacode='$allowmediacodenew',
				allowsmilies='$allowsmiliesnew', alloweditrules='$alloweditrulesnew', modnewposts='$modnewpostsnew',
				recyclebin='$recyclebinnew', jammer='$jammernew', allowanonymous='$allowanonymousnew', forumcolumns='$forumcolumnsnew', threadcaches='$threadcachesnew',
				simple='$simplenew', allowglobalstick='$allowglobalsticknew', disablewatermark='$disablewatermarknew', allowtag='$allowtagnew', autoclose='".intval($autoclosenew * $autoclosetimenew)."' $fupadd
				WHERE fid='$fid'");

			$query = $db->query("SELECT fid FROM {$tablepre}forumfields WHERE fid='$fid'");
			if(!($db->num_rows($query))) {
				$db->query("INSERT INTO {$tablepre}forumfields (fid)
					VALUES ('$fid')");
			}

			foreach(array('post', 'reply', 'digest', 'postattach', 'getattach') as $item) {
				if(${$item.'creditsnew'}) {
					foreach(${$item.'creditsnew'} as $i => $v) {
						if($v == '') {
							unset(${$item.'creditsnew'}[$i]);
						} else {
							$v = intval($v);
							${$item.'creditsnew'}[$i]  = $v < -99 ? -99 : $v;
							${$item.'creditsnew'}[$i]  = $v > 99 ? 99 : $v;
						}
					}
				}
				${$item.'creditsnew'} = ${$item.'creditsnew'} ? addslashes(serialize(${$item.'creditsnew'})) : '';
			}

			$threadtypesnew['types'] = $threadtypesnew['flat'] = $threadtypes['selectbox'] = $threadtypes['special'] = $threadtypes['show'] = array();
			$threadsortsnew['types'] = $threadsortsnew['flat'] = $threadsorts['selectbox'] = $threadsorts['special'] = $threadsorts['show'] = array();

			if($allowthreadtypes) {
				if(is_array($newname) && $newname) {
					$newname = array_unique($newname);
					if($newname) {
						foreach($newname as $key => $val) {
							$val = trim($val);
							if($newenable[$key] && $val) {
								$newtypeid = $db->result_first("SELECT typeid FROM {$tablepre}threadtypes WHERE name='$val'");
								if(!$newtypeid) {
									$db->query("INSERT INTO	{$tablepre}threadtypes (name, description) VALUES
										('$val', '".dhtmlspecialchars(trim($newdescription[$key]))."')");
									$newtypeid = $db->insert_id();
								}
								if($newoptions[$key] == 1) {
									$threadtypesnew['types'][$newtypeid] = $threadtypesnew['flat'][$newtypeid] = $val;
								} elseif($newoptions[$key] == 2) {
									$threadtypesnew['types'][$newtypeid] = $threadtypesnew['selectbox'][$newtypeid] = $val;
								}
							}
						}
					}
					$threadtypesnew['status'] = 1;
				} else {
					$newname = array();
				}
				if($threadtypesnew['status']) {
					if(is_array($threadtypesnew['options']) && $threadtypesnew['options']) {

						$typeids = '0';
						foreach($threadtypesnew['options'] as $key => $val) {
							$typeids .= $val ? ', '.intval($key) : '';
						}

						$query = $db->query("SELECT * FROM {$tablepre}threadtypes WHERE typeid IN ($typeids) AND special='' ORDER BY displayorder");
						while($type = $db->fetch_array($query)) {
							if($threadtypesnew['options']['enable'][$type['typeid']]) {
								if($threadtypesnew['options'][$type['typeid']] == 1) {
									$threadtypesnew['types'][$type['typeid']] = $threadtypesnew['flat'][$type['typeid']] = $type['name'];
								} elseif($threadtypesnew['options'][$type['typeid']] == 2) {
									$threadtypesnew['types'][$type['typeid']] = $threadtypesnew['selectbox'][$type['typeid']] = $type['name'];
								}
							}
						}
					}
					$threadtypesnew = $threadtypesnew['types'] ? addslashes(serialize(array
						(
						'required' => (bool)$threadtypesnew['required'],
						'listable' => (bool)$threadtypesnew['listable'],
						'prefix' => (bool)$threadtypesnew['prefix'],
						'types' => $threadtypesnew['types'],
						'selectbox' => $threadtypesnew['selectbox'],
						'flat' => $threadtypesnew['flat'],
						))) : '';
				} else {
					$threadtypesnew = '';
				}
				$threadtypesadd = "threadtypes='$threadtypesnew',";

				if($threadsortsnew['status']) {
					if(is_array($threadsortsnew['options']) && $threadsortsnew['options']) {
						$sortids = '0';
						foreach($threadsortsnew['options'] as $key => $val) {
							$sortids .= $val ? ', '.intval($key) : '';
						}

						$query = $db->query("SELECT * FROM {$tablepre}threadtypes WHERE typeid IN ($sortids) AND special='1' ORDER BY displayorder");
						while($sort = $db->fetch_array($query)) {
							if($threadsortsnew['options']['enable'][$sort['typeid']]) {
								if($threadsortsnew['options'][$sort['typeid']] == 1) {
									$threadsortsnew['types'][$sort['typeid']] = $threadsortsnew['flat'][$sort['typeid']] = $sort['name'];
								} elseif($threadsortsnew['options'][$sort['typeid']] == 2) {
									$threadsortsnew['types'][$sort['typeid']] = $threadsortsnew['selectbox'][$sort['typeid']] = $sort['name'];
								}
							}
							$threadsortsnew['expiration'][$sort['typeid']] = $sort['expiration'];
							$threadsortsnew['show'][$sort['typeid']] = $threadsortsnew['options']['show'][$sort['typeid']] ? 1 : 0;
							$threadsortsnew['typemodelid'][$sort['typeid']] = $sort['modelid'];
						}
					}
					
					if($threadsortsnew['default'] && !$threadsortsnew['defaultshow']) {
						cpmsg('forums_edit_threadsort_nonexistence', '', 'error');
					}
					
					$threadsortsnew = $threadsortsnew['types'] ? addslashes(serialize(array
						(
						'required' => (bool)$threadsortsnew['required'],
						'prefix' => (bool)$threadsortsnew['prefix'],
						'types' => $threadsortsnew['types'],
						'selectbox' => $threadsortsnew['selectbox'],
						'flat' => $threadsortsnew['flat'],
						'show' => $threadsortsnew['show'],
						'expiration' => $threadsortsnew['expiration'],
						'modelid' => $threadsortsnew['typemodelid'],
						'defaultshow' => $threadsortsnew['default'] ? $threadsortsnew['defaultshow'] : '',
						))) : '';
				} else {
					$threadsortsnew = '';
				}

				$threadsortsadd = "threadsorts='$threadsortsnew',";
				if($typemodel) {
					$query = $db->query("SELECT id, name FROM {$tablepre}typemodels WHERE id IN (".implodeids($typemodel).") ORDER BY displayorder");
					while($model = $db->fetch_array($query)) {
						$threadtypemodel[$model['id']]['name'] = $model['name'];
					}
					$threadtypemodeladd = addslashes(serialize($threadtypemodel));
				}

			} else {
				$threadtypesadd = $threadsortsadd = $threadtypemodeladd = '';
			}

			if(!empty($tradetypes) && is_array($tradetypes) && $allowpostspecialtrade) {
				if(count($tradetypes) == count($tradetypesnew)) {
					$tradetypesnew = '';
				} else {
					$tradetypesnew = addslashes(serialize($tradetypesnew));
				}
			} else {
				$tradetypesnew = '';
			}

			$threadpluginnew = addslashes(serialize($threadpluginnew));
			$modrecommendnew['num'] = $modrecommendnew['num'] ? intval($modrecommendnew['num']) : 10;
			$modrecommendnew['cachelife'] = $modrecommendnew['cachelife'] ? intval($modrecommendnew['cachelife']) : 900;
			$modrecommendnew['maxlength'] = $modrecommendnew['maxlength'] ? intval($modrecommendnew['maxlength']) : 0;
			$modrecommendnew['dateline'] = $modrecommendnew['dateline'] ? intval($modrecommendnew['dateline']) : 0;
			$modrecommendnew['imagenum'] = $modrecommendnew['imagenum'] ? intval($modrecommendnew['imagenum']) : 5;
			$modrecommendnew['imagewidth'] = $modrecommendnew['imagewidth'] ? intval($modrecommendnew['imagewidth']) : 200;
			$modrecommendnew['imageheight'] = $modrecommendnew['imageheight'] ? intval($modrecommendnew['imageheight']): 150;
			$modrecommendnew = $modrecommendnew && is_array($modrecommendnew) ? addslashes(serialize($modrecommendnew)) : '';
			$descriptionnew = addslashes(preg_replace('/on(mousewheel|mouseover|click|load|onload|submit|focus|blur)="[^"]*"/i', '', discuzcode(stripslashes($descriptionnew), 1, 0, 0, 0, 1, 1, 0, 0, 1)));
			$rulesnew = addslashes(preg_replace('/on(mousewheel|mouseover|click|load|onload|submit|focus|blur)="[^"]*"/i', '', discuzcode(stripslashes($rulesnew), 1, 0, 0, 0, 1, 1, 0, 0, 1)));
			$extranew = is_array($_POST['extra']) ? $_POST['extra'] : array();
			$extranew = serialize($extranew);

			$db->query("UPDATE {$tablepre}forumfields SET description='$descriptionnew', icon='$iconnew', password='$passwordnew', redirect='$redirectnew', rules='$rulesnew',
				attachextensions='$attachextensionsnew', $threadtypesadd $threadsortsadd postcredits='$postcreditsnew', replycredits='$replycreditsnew', digestcredits='$digestcreditsnew',
				postattachcredits='$postattachcreditsnew', getattachcredits='$getattachcreditsnew', viewperm='$viewpermnew', postperm='$postpermnew', replyperm='$replypermnew', tradetypes='$tradetypesnew', typemodels='$threadtypemodeladd',
				getattachperm='$getattachpermnew', postattachperm='$postattachpermnew', formulaperm='$formulapermnew', modrecommend='$modrecommendnew', keywords='$keywordsnew', threadplugin='$threadpluginnew', extra='$extranew' WHERE fid='$fid'");

			if($modrecommendnew && !$modrecommendnew['sort']) {
				require_once DISCUZ_ROOT.'./include/forum.func.php';
				recommendupdate($fid, $modrecommendnew, '1');
			}

			updatecache('forums');

			$update_setting_cache = false;
			if($binddomainnew) {
				$binddomainnew = preg_replace('/^https?:\/\//is', '', trim($binddomainnew));
			} else {
				$binddomainnew = '';
			}
			if(!isset($forumdomains[$fid]) || $forumdomains[$fid] != $binddomainnew) {
				if(empty($binddomainnew)) {
					unset($forumdomains[$fid]);
				} else {
					$forumdomains[$fid] = $binddomainnew;
				}
				if($forumdomains) {
					$binddomains = array_flip($forumdomains);
				} else {
					$binddomains = array();
				}
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('forumdomains', '".(addslashes(serialize($forumdomains)))."')");
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('binddomains', '".(addslashes(serialize($binddomains)))."')");
				$update_setting_cache = true;
			}

			if($foruminfosidestatus) {
				$infosidestatusnew = $infosidestatus;
				unset($infosidestatusnew['f'.$fid]);
				$foruminfosidestatus[0] != $infosidestatus[0] && $foruminfosidestatus[0] != '' && $infosidestatusnew['f'.$fid][0] = $foruminfosidestatus[0];
				$foruminfosidestatus['posts'] != $infosidestatus['posts'] && $foruminfosidestatus['posts'] != '' && $infosidestatusnew['f'.$fid]['posts'] = $foruminfosidestatus['posts'];
				if($infosidestatus != $infosidestatusnew) {
					$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('infosidestatus', '".(addslashes(serialize($infosidestatusnew)))."')");
					$update_setting_cache = true;
				}
			}

			if($update_setting_cache) {
				updatecache('settings');
			}

			if(submitcheck('saveconfigsubmit') && $type != 'group') {
				$projectid = intval($projectid);
				dheader("Location: $boardurl$BASESCRIPT?action=project&operation=add&id=$fid&type=forum&projectid=$projectid");
			} else {
				cpmsg('forums_edit_succeed', "$BASESCRIPT?action=forums&operation=edit&fid=$fid".($anchor ? "&anchor=$anchor" : ''), 'succeed');
			}
		}

	}

} elseif($operation == 'delete') {

	if($ajax) {
		ob_end_clean();
		require_once DISCUZ_ROOT.'./include/post.func.php';
		$tids = array();

		$total = intval($total);
		$pp = intval($pp);
		$currow = intval($currow);

		$query = $db->query("SELECT tid FROM {$tablepre}threads WHERE fid='$fid' LIMIT $pp");
		while($thread = $db->fetch_array($query)) {
			$tids[] = $thread['tid'];
		}
		$tids = implode(',', $tids);

		if($tids) {
			$query = $db->query("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE tid IN ($tids)");
			while($attach = $db->fetch_array($query)) {
				dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
			}

			foreach(array('threads', 'threadsmod', 'relatedthreads', 'posts', 'polls', 'polloptions', 'trades', 'activities', 'activityapplies', 'debates', 'debateposts', 'attachments', 'favorites', 'typeoptionvars', 'forumrecommend') as $value) {
				$db->query("DELETE FROM {$tablepre}$value WHERE tid IN ($tids)", 'UNBUFFERED');
				if($value == 'attachments') {
					$db->query("DELETE FROM {$tablepre}attachmentfields WHERE tid IN ($tids)", 'UNBUFFERED');
				}
			}
		}

		if($currow + $pp > $total) {
			$db->query("DELETE FROM {$tablepre}forums WHERE fid='$fid'");
			$db->query("DELETE FROM {$tablepre}forumfields WHERE fid='$fid'");
			$db->query("DELETE FROM {$tablepre}moderators WHERE fid='$fid'");
			$db->query("DELETE FROM {$tablepre}access WHERE fid='$fid'");
			echo 'TRUE';
			exit;
		}

		echo 'GO';
		exit;

	} else {

		if($finished) {
			updatecache('forums');
			cpmsg('forums_delete_succeed', $BASESCRIPT.'?action=forums', 'succeed');

		}

		if($db->result_first("SELECT COUNT(*) FROM {$tablepre}forums WHERE fup='$fid'")) {
			cpmsg('forums_delete_sub_notnull', '', 'error');
		}

		if(!$confirmed) {

			cpmsg('forums_delete_confirm', "$BASESCRIPT?action=forums&operation=delete&fid=$fid", 'form');

		} else {

			$threads = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE fid='$fid'");

			cpmsg('forums_delete_alarm', "$BASESCRIPT?action=forums&operation=delete&fid=$fid&confirmed=1", 'loadingform', '<div id="percent">0%</div>', FALSE);

			echo "
			<div id=\"statusid\" style=\"display:none\"></div>
			<script type=\"text/JavaScript\">
				var xml_http_building_link = '".$lang['xml_http_building_link']."';
				var xml_http_sending = '".$lang['xml_http_sending']."';
				var xml_http_loading = '".$lang['xml_http_loading']."';
				var xml_http_load_failed = '".$lang['xml_http_load_failed']."';
				var xml_http_data_in_processed = '".$lang['xml_http_data_in_processed']."';
				var adminfilename = '$BASESCRIPT';
				function forumsdelete(url, total, pp, currow) {

					var x = new Ajax('HTML', 'statusid');
					x.get(url+'&ajax=1&pp='+pp+'&total='+total+'&currow='+currow, function(s) {
						if(s != 'GO') {
							location.href = adminfilename + '?action=forums&operation=delete&finished=1';
						}

						currow += pp;
						var percent = ((currow / total) * 100).toFixed(0);
						percent = percent > 100 ? 100 : percent;
						document.getElementById('percent').innerHTML = percent+'%';
						document.getElementById('percent').style.backgroundPosition = '-'+percent+'%';

						if(currow < total) {
							forumsdelete(url, total, pp, currow);
						}
					});
				}
				forumsdelete(adminfilename + '?action=forums&operation=delete&fid=$fid&confirmed=1', $threads, 2000, 0);
			</script>
			";
		}
	}

} elseif($operation == 'copy') {

	require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';

	$source = intval($source);
	$sourceforum = $_DCACHE['forums'][$source];

	if(empty($sourceforum) || $sourceforum['type'] == 'group') {
		cpmsg('forums_copy_source_invalid', '', 'error');
	}

	$delfields = array(
		'forums'	=> array('fid', 'fup', 'type', 'name', 'status', 'displayorder', 'threads', 'posts', 'todayposts', 'lastpost', 'modworks', 'icon'),
		'forumfields'	=> array('description', 'password', 'redirect', 'moderators', 'rules', 'threadsorts', 'typemodels', 'tradetypes', 'threadplugin'),
	);
	$fields = array(
		'forums' 	=> fetch_table_struct('forums'),
		'forumfields'	=> fetch_table_struct('forumfields'),
	);

	if(!submitcheck('copysubmit')) {

		require_once DISCUZ_ROOT.'./include/forum.func.php';

		$forumselect = '<select name="target[]" size="10" multiple="multiple">'.forumselect(FALSE, 0, 0, TRUE).'</select>';
		$optselect = '<select name="options[]" size="10" multiple="multiple">';
		$fieldarray = array_merge($fields['forums'], $fields['forumfields']);
		$listfields = array_diff($fieldarray, array_merge($delfields['forums'], $delfields['forumfields']));
		foreach($listfields as $field) {
			$optselect .= '<option value="'.$field.'">'.($lang['project_option_forum_'.$field] ? $lang['project_option_forum_'.$field] : $field).'</option>';
		}
		$optselect .= '</select>';
		shownav('forum', 'forums_copy');
		showsubmenu('forums_copy');
		showtips('forums_copy_tips');
		showformheader('forums&operation=copy');
		showhiddenfields(array('source' => $source));
		showtableheader();
		showtitle($lang['forums_copy']);
		showsetting(lang('forums_copy_source').':','','', $sourceforum['name']);
		showsetting('forums_copy_target', '', '', $forumselect);
		showsetting('forums_copy_options', '', '', $optselect);
		showsubmit('copysubmit');
		showtablefooter();
		showformfooter();

	} else {

		$fids = $comma = '';
		if(is_array($target) && count($target)) {
			foreach($target as $fid) {
				if(($fid = intval($fid)) && $fid != $source ) {
					$fids .= $comma.$fid;
					$comma = ',';
				}
			}
		}
		if(empty($fids)) {
			cpmsg('forums_copy_target_invalid', '', 'error');
		}

		$forumoptions = array();
		if(is_array($options) && !empty($options)) {
			foreach($options as $option) {
				if($option = trim($option)) {
					if(in_array($option, $fields['forums'])) {
						$forumoptions['forums'][] = $option;
					} elseif(in_array($option, $fields['forumfields'])) {
						$forumoptions['forumfields'][] = $option;
					}
				}
			}
		}

		if(empty($forumoptions)) {
			cpmsg('forums_copy_options_invalid', '', 'error');
		}

		foreach(array('forums', 'forumfields') as $table) {
			if(is_array($forumoptions[$table]) && !empty($forumoptions[$table])) {
				$sourceforum = $db->fetch_first("SELECT ".implode($forumoptions[$table],',')." FROM {$tablepre}$table WHERE fid='$source'");
				if(!$sourceforum) {
					cpmsg('forums_copy_source_invalid', '', 'error');
				}

				$updatequery = 'fid=fid';
				foreach($sourceforum as $key => $val) {
					$updatequery .= ", $key='".addslashes($val)."'";
				}
				$db->query("UPDATE {$tablepre}$table SET $updatequery WHERE fid IN ($fids)");
			}
		}

		updatecache('forums');
		cpmsg('forums_copy_succeed', $BASESCRIPT.'?action=forums', 'succeed');

	}

}

function showforum($key, $type = '', $last = '') {
	global $forums, $showedforums, $lang, $indexname;

	$forum = $forums[$key];
	$showedforums[] = $key;

	if($last == '') {
		$return = '<tr class="hover"><td class="td25"><input type="text" class="txt" name="order['.$forum['fid'].']" value="'.$forum['displayorder'].'" /></td><td>';
		if($type == 'group') {
			$return .= '<div class="parentboard">';
		} elseif($type == '') {
			$return .= '<div class="board">';
		} elseif($type == 'sub') {
			$return .= '<div id="cb_'.$forum['fid'].'" class="childboard">';
		}

		$boardattr = '';
		if(!$forum['status']  || $forum['password'] || $forum['redirect']) {
			$boardattr = '<div class="boardattr">';
			$boardattr .= $forum['status'] ? '' : $lang['forums_admin_hidden'];
			$boardattr .= !$forum['password'] ? '' : ' '.$lang['forums_admin_password'];
			$boardattr .= !$forum['redirect'] ? '' : ' '.$lang['forums_admin_url'];
			$boardattr .= '</div>';
		}

		$return .= '<input type="text" name="name['.$forum['fid'].']" value="'.htmlspecialchars($forum['name']).'" class="txt" />'.
			($type == '' ? '<a href="###" onclick="addrowdirect = 1;addrow(this, 2, '.$forum['fid'].')" class="addchildboard">'.$lang['forums_admin_add_sub'].'</a>' : '').
			'</div>'.$boardattr.
			'</td><td>'.showforum_moderators($forum).'</td>
			<td><a href="'.$BASESCRIPT.'?action=forums&operation=edit&fid='.$forum['fid'].'" title="'.$lang['forums_edit_comment'].'" class="act">'.$lang['edit'].'</a>'.
			($type != 'group' ? '<a href="'.$BASESCRIPT.'?action=forums&operation=copy&source='.$forum['fid'].'" title="'.$lang['forums_copy_comment'].'" class="act">'.$lang['forums_copy'].'</a>' : '').
			'<a href="'.$BASESCRIPT.'?action=forums&operation=delete&fid='.$forum['fid'].'" title="'.$lang['forums_delete_comment'].'" class="act">'.$lang['delete'].'</a></td></tr>';
	} else {
		if($last == 'lastboard') {
			$return = '<tr><td></td><td colspan="3"><div class="lastboard"><a href="###" onclick="addrow(this, 1, '.$forum['fid'].')" class="addtr">'.$lang['forums_admin_add_forum'].'</a></div></td></tr>';
		} elseif($last == 'lastchildboard' && $type) {
			$return = '<script type="text/JavaScript">$(\'cb_'.$type.'\').className = \'lastchildboard\';</script>';
		} elseif($last == 'last') {
			$return = '<tr><td></td><td colspan="3"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['forums_admin_add_category'].'</a></div></td></tr>';
		}
	}

	return $return;
}

function showforum_moderators($forum) {
	if($forum['moderators']) {
		$moderators = explode("\t", $forum['moderators']);
		$count = count($moderators);
		$max = $count > 2 ? 2 : $count;
		$mods = array();
		for($i = 0;$i < $max;$i++) {
			$mods[] = $forum['inheritedmod'] ? '<b>'.$moderators[$i].'</b>' : $moderators[$i];
		}
		$r = implode(', ', $mods);
		if($count > 2) {
			$r = '<span onmouseover="showMenu({\'ctrlid\':this.id})" id="mods_'.$forum['fid'].'">'.$r.'</span>';
			$mods = array();
			foreach($moderators as $moderator) {
				$mods[] = $forum['inheritedmod'] ? '<b>'.$moderator.'</b>' : $moderator;
			}
			$r = '<a href="'.$BASESCRIPT.'?action=forums&operation=moderators&fid='.$forum['fid'].'" title="'.lang('forums_moderators_comment').'">'.$r.'</a> ...';
			$r .= '<div class="dropmenu1" id="mods_'.$forum['fid'].'_menu" style="display: none">'.implode('<br />', $mods).'</div>';
		} else {
			$r = '<a href="'.$BASESCRIPT.'?action=forums&operation=moderators&fid='.$forum['fid'].'" title="'.lang('forums_moderators_comment').'">'.$r.'</a>';
		}


	} else {
		$r = '<a href="'.$BASESCRIPT.'?action=forums&operation=moderators&fid='.$forum['fid'].'" title="'.lang('forums_moderators_comment').'">'.lang('forums_admin_no_moderator').'</a>';
	}
	return $r;
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