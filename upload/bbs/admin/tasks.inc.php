<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: tasks.inc.php 21301 2009-11-25 15:02:22Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

$id = intval($id);
$membervars = array('act', 'num', 'time');
$postvars = array('act', 'forumid', 'num', 'time', 'threadid', 'authorid');
$modvars = array();
$sys_scripts = array('member', 'post', 'mod');
$sys_types = array('member' => array('name' => lang('nav_task_member'), 'version' => '1.0'), 'post' => array('name' => lang('nav_task_post'), 'version' => '1.0'));

$custom_types = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='tasktypes'");
$custom_types = $custom_types ? unserialize($custom_types) : array();
$custom_scripts = array_keys($custom_types);

$submenus = array();
foreach(array_merge($sys_types, $custom_types) as $k => $v) {
	$submenus[] = array($v['name'], "tasks&operation=add&script=$k");
}

if(!($operation)) {

	if(!submitcheck('tasksubmit')) {

		shownav('extended', 'nav_tasks');
		showsubmenu('nav_tasks', array(
			array('admin', 'tasks', 1),
			array(array('menu' => 'add', 'submenu' => $submenus), '', 0),
			array('nav_task_type', 'tasks&operation=type', 0)
		));
		showtips('tasks_tips');
		showformheader('tasks');
		showtableheader('config', 'fixpadding');
		showsetting('tasks_on', 'taskonnew', $taskon, 'radio');
		showtablefooter();
		showtableheader('admin', 'fixpadding');
		showsubtitle(array('name', 'available', 'display_order', 'type', 'tasks_reward', 'time', ''));

		$starttasks = array();
		$query = $db->query("SELECT * FROM {$tablepre}tasks ORDER BY displayorder, taskid DESC");
		while($task = $db->fetch_array($query)) {

			if($task['reward'] == 'credit') {
				$reward = lang('credits').' '.$extcredits[$task['prize']]['title'].' '.$task['bonus'].' '.$extcredits[$task['prize']]['unit'];
			} elseif($task['reward'] == 'magic') {
				$magicname = $db->result_first("SELECT name FROM {$tablepre}magics WHERE magicid='$task[prize]'");
				$reward = lang('tasks_reward_magic').' '.$magicname.' '.$task['bonus'];
			} elseif($task['reward'] == 'medal') {
				$medalname = $db->result_first("SELECT name FROM {$tablepre}medals WHERE medalid='$task[prize]'");
				$reward = lang('medals').' '.$medalname.($task['bonus'] ? ' '.lang('validity').$task['bonus'].' '.lang('days') : '');
			} elseif($task['reward'] == 'invite') {
				$reward = lang('tasks_reward_invite').' '.$task['prize'].($task['bonus'] ? ' '.lang('validity').$task['bonus'].' '.lang('days') : '');
			} elseif($task['reward'] == 'group') {
				$grouptitle = $db->result_first("SELECT grouptitle FROM {$tablepre}usergroups WHERE groupid='$task[prize]'");
				$reward = lang('usergroup').' '.$grouptitle.($task['bonus'] ? ' '.lang('validity').' '.$task['bonus'].' '.lang('days') : '');
			} else {
				$reward = lang('none');
			}
			if($task['available'] == '1' && (!$task['starttime'] || $task['starttime'] <= $timestamp) && (!$task['endtime'] || $task['endtime'] > $timestamp)) {
				$starttasks[] = $task['taskid'];
			}

			$checked = $task['available'] ? ' checked="checked"' : '';

			if($task['starttime'] && $task['endtime']) {
				$task['time'] = gmdate('y-m-d', $task['starttime'] + $timeoffset * 3600).' ~ '.gmdate('y-m-d', $task['endtime'] + $timeoffset * 3600);
			} elseif($task['starttime'] && !$task['endtime']) {
				$task['time'] = gmdate('y-m-d', $task['starttime'] + $timeoffset * 3600).' '.lang('tasks_online');
			} elseif(!$task['starttime'] && $task['endtime']) {
				$task['time'] = gmdate('y-m-d', $task['endtime'] + $timeoffset * 3600).' '.lang('tasks_offline');
			} else {
				$task['time'] = lang('nolimit');
			}

			showtablerow('', array('', 'class="td25"'), array(
				"<input type=\"text\" class=\"txt\" name=\"namenew[$task[taskid]]\" size=\"20\" value=\"$task[name]\"><input type=\"hidden\" name=\"nameold[$task[taskid]]\" value=\"$task[name]\">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$task[taskid]]\" value=\"1\"$checked><input type=\"hidden\" name=\"availableold[$task[taskid]]\" value=\"$task[available]\">",
				'<input type="text" class="txt" name="displayordernew['.$task['taskid'].']" value="'.$task['displayorder'].'" size="3" />',
				$task['newbietask'] == '1' ? lang('tasks_newbie_task') : (in_array($task['scriptname'], $custom_scripts) ? $custom_types[$task['scriptname']]['name'] : lang('nav_task_'.$task['scriptname'])),
				$reward,
				$task['time'].'<input type="hidden" name="newbietasknew['.$task['taskid'].']" value="'.$task['newbietask'].'"><input type="hidden" name="scriptnamenew['.$task['taskid'].']" value="'.$task['scriptname'].'">',
				"<a href=\"$BASESCRIPT?action=tasks&operation=edit&id=$task[taskid]\" class=\"act\">$lang[edit]</a>",
				$task['newbietask'] == '1' ? '' : "<a href=\"$BASESCRIPT?action=tasks&operation=delete&id=$task[taskid]\" class=\"act\">$lang[delete]</a>"
			));

		}

		if($starttasks) {
			$db->query("UPDATE {$tablepre}tasks SET available='2' WHERE taskid IN (".implodeids($starttasks).")", 'UNBUFFERED');
		}

		showsubmit('tasksubmit', 'submit');
		showtablefooter();
		showformfooter();

	} else {

		$checksettingsok = TRUE;
		if(is_array($namenew)) {
			foreach($namenew as $id => $name) {
				$availablenew[$id] = $availablenew[$id] && (!$starttimenew[$id] || $starttimenew[$id] <= $timestamp) && (!$endtimenew[$id] || $endtimenew[$id] > $timestamp) ? 2 : $availablenew[$id];
				if($newbietasknew[$id] && $availablenew[$id]) {
					switch(substr($scriptnamenew[$id], 7)) {
						case 'post_reply':
							$checkid = 'threadid';
							break;
						case 'post_newthread':
							$checkid = 'forumid';
							break;
						case 'sendpm':
						case 'addbuddy':
							$checkid = 'authorid';
							break;
						default:
							$checkid = '';
							break;
					}
					if($checkid) {
						$checkresult = $db->result_first("SELECT value FROM {$tablepre}taskvars WHERE taskid='$id' AND variable='$checkid'");
						if(empty($checkresult)) {
							$availablenew[$id] = 0;
							$checksettingsok = FALSE;
						}
					}
				}
				$displayorderadd = isset($displayordernew[$id]) ? ", displayorder='$displayordernew[$id]'" : '';
				$db->query("UPDATE {$tablepre}tasks SET name='".dhtmlspecialchars($namenew[$id])."', available='$availablenew[$id]'$displayorderadd WHERE taskid='$id'");
			}
		}

		$updatesettings = $updatenewbietask = FALSE;
		if($taskonnew != $taskon) {
			$updatesettings = TRUE;
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('taskon', '$taskonnew')");
		}

		if(is_array($newbietasknew)) {
			foreach($newbietasknew as $id => $v) {
				if($availablenew[$id] != $availableold[$id] || $namenew[$id] != $nameold[$id]) {
					$updatesettings = TRUE;
				}
				if($v == '2' && $availablenew[$id] && !$availableold[$id]) {
					$updatenewbietask = TRUE;
				}
			}
		}

		if($updatenewbietask) {
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('newbietaskupdate', '$timestamp')");
		}
		$updatesettings && updatecache('settings');

		cpmsg($checksettingsok ? 'tasks_succeed' : 'tasks_settings_invalid', $BASESCRIPT.'?action=tasks', $checksettingsok ? 'succeed' : 'error');

	}

} elseif($operation == 'add' && $script) {

	if(!in_array($script, $sys_scripts)) {
		if(in_array($script, $custom_scripts)) {
			include language('tasks');
			if((!@include DISCUZ_ROOT.'./include/tasks/'.$script.'.cfg.php') || (!@include DISCUZ_ROOT.'./include/tasks/'.$script.'.inc.php')) {
				cpmsg('tasks_noscript_or_nocfg', '', 'error');
			}
		} else {
			cpmsg('undefined_action', '', 'error');
		}
	}

	if(!submitcheck('addsubmit')) {

		echo '<script type="text/javascript" src="include/js/calendar.js"></script>';
		shownav('extended', 'nav_tasks');
		showsubmenu('nav_tasks', array(
			array('admin', 'tasks', 0),
			array(array('menu' => 'add', 'submenu' => $submenus), '', 1),
			array('nav_task_type', 'tasks&operation=type', 0)
		));

		if(in_array($script, $sys_scripts)) {
			showtips('tasks_tips_add_'.$script);
			$task_name = $task_description = $task_icon = $task_period = '';
		}

		showformheader('tasks&operation=add&script='.$script);
		showtableheader('tasks_add_basic', 'fixpadding');
		showsetting('tasks_add_name', 'name', $task_name, 'text');
		showsetting('tasks_add_desc', 'description', $task_description, 'textarea');
		showsetting('tasks_add_icon', 'icon', $task_icon, 'text');
		showsetting('tasks_add_starttime', 'starttime', '', 'calendar');
		showsetting('tasks_add_endtime', 'endtime', '', 'calendar');
		showsetting('tasks_add_period', 'period', $task_period, 'text');
		showsetting('tasks_add_reward', array('reward', array(
			array('', lang('none'), array('reward_credit' => 'none', 'reward_magic' => 'none', 'reward_medal' => 'none', 'reward_invite' => 'none', 'reward_group' => 'none')),
			array('credit', lang('credits'), array('reward_credit' => '', 'reward_magic' => 'none', 'reward_medal' => 'none', 'reward_invite' => 'none', 'reward_group' => 'none')),
			$magicstatus ? array('magic', lang('tasks_reward_magic'), array('reward_credit' => 'none', 'reward_magic' => '', 'reward_medal' => 'none', 'reward_invite' => 'none', 'reward_group' => 'none')) : '',
			$medalstatus ? array('medal', lang('medals'), array('reward_credit' => 'none', 'reward_magic' => 'none', 'reward_medal' => '', 'reward_invite' => 'none', 'reward_group' => 'none')) : '',
			$regstatus > 1 ? array('invite', lang('tasks_reward_invite'), array('reward_credit' => 'none', 'reward_magic' => 'none', 'reward_medal' => 'none', 'reward_invite' => '', 'reward_group' => 'none')) : '',
			array('group', lang('tasks_add_group'), array('reward_credit' => 'none', 'reward_magic' => 'none', 'reward_medal' => 'none', 'reward_invite' => 'none', 'reward_group' => ''))
		)), '', 'mradio');

		$extcreditarray = array(array(0, lang('select')));
		foreach($extcredits as $creditid => $extcredit) {
			$extcreditarray[] = array($creditid, $extcredit['title']);
		}

		showtagheader('tbody', 'reward_credit');
		showsetting('tasks_add_extcredit', array('prize_credit', $extcreditarray), 0, 'select');
		showsetting('tasks_add_credits', 'bonus_credit', '0', 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'reward_magic');
		showsetting('tasks_add_magicname', array('prize_magic', fetcharray('magicid', 'name', 'magics', "available='1' ORDER BY displayorder")), 0, 'select');
		showsetting('tasks_add_magicnum', 'bonus_magic', '0', 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'reward_medal');
		showsetting('tasks_add_medalname', array('prize_medal', fetcharray('medalid', 'name', 'medals', "available='1' ORDER BY displayorder")), 0, 'select');
		showsetting('tasks_add_medalexp', 'bonus_medal', '', 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'reward_invite');
		showsetting('tasks_add_invitenum', 'prize_invite', '1', 'text');
		showsetting('tasks_add_inviteexp', 'bonus_invite', '10', 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'reward_group');
		showsetting('tasks_add_group', array('prize_group', fetcharray('groupid', 'grouptitle', 'usergroups', "type='special' AND radminid='0'")), 0, 'select');
		showsetting('tasks_add_groupexp', 'bonus_group', '', 'text');
		showtagfooter('tbody');

		showtitle('tasks_add_appyperm');
		showsetting('tasks_add_groupperm', array('grouplimit', array(
			array('all', lang('tasks_add_group_all'), array('specialgroup' => 'none')),
			array('member', lang('tasks_add_group_member'), array('specialgroup' => 'none')),
			array('admin', lang('tasks_add_group_admin'), array('specialgroup' => 'none')),
			array('special', lang('tasks_add_group_special'), array('specialgroup' => ''))
		)), 'all', 'mradio');
		showtagheader('tbody', 'specialgroup');
		showsetting('tasks_add_usergroup', array('applyperm[]', fetcharray('groupid', 'grouptitle', 'usergroups', '')), 0, 'mselect');
		showtagfooter('tbody');
		showsetting('tasks_add_relatedtask', array('relatedtaskid', fetcharray('taskid', 'name', 'tasks', "available='2' ORDER BY displayorder, taskid DESC")), 0, 'select');
		showsetting('tasks_add_maxnum', 'tasklimits', '', 'text');

		if(in_array($script, $custom_scripts)) {
			if(is_array($task_conditions)) {
				foreach($task_conditions as $taskvar) {
					if($taskvar['sort'] == 'apply' && $taskvar['name']) {
						if($taskvar['variable']) {
							showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['type'], '', 0, $taskvar['description']);
						} else {
							showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['description']);
						}
					}
				}
			}
		}

		showtitle('tasks_add_conditions');

		if($script == 'member') {

			showsetting('tasks_add_limit_act', array('act', array(
				array('buddy', lang('tasks_add_act_buddy'), array('timelimit' => 'none')),
				array('favorite', lang('tasks_add_act_favorite'), array('timelimit' => 'none')),
				array('magic', lang('tasks_add_act_magic'), array('timelimit' => ''))
			)), 'buddy', 'mradio');
			showsetting('tasks_add_limit_num', 'num', '', 'text');
			showtagheader('tbody', 'timelimit');
			showsetting('tasks_add_limit_time', 'time', '', 'text');
			showtagfooter('tbody');

		} elseif($script == 'post') {

			showsetting('tasks_add_limit_act', array('act', array(
				array('newthread', lang('tasks_add_act_newthread'), array('forumlimit' => '', 'speciallimit' => 'none')),
				array('newreply', lang('tasks_add_act_newreply'), array('forumlimit' => 'none', 'speciallimit' => '')),
				array('newpost', lang('tasks_add_act_newpost'), array('forumlimit' => '', 'speciallimit' => 'none'))
			)), 'newpost', 'mradio');
			require_once DISCUZ_ROOT.'./include/forum.func.php';
			showtagheader('tbody', 'forumlimit', TRUE);
			showsetting('tasks_add_limit_forumid', '', '', '<SELECT name="forumid"><option value="">'.lang('none').'</option>'.forumselect(FALSE, 0, 0, TRUE).'</select>');
			showtagfooter('tbody');
			showtagheader('tbody', 'speciallimit');
			showsetting('tasks_add_limit_threadid', 'threadid', '', 'text');
			showsetting('tasks_add_limit_authorid', 'author', '', 'text');
			showtagfooter('tbody');
			showsetting('tasks_add_limit_num', 'num', '', 'text');
			showsetting('tasks_add_limit_time', 'time', '', 'text');

		} elseif($script == 'mod') {
		} elseif(in_array($script, $custom_scripts)) {

			$haveconditions = FALSE;
			if(is_array($task_conditions) && $task_conditions) {
				foreach($task_conditions as $taskvar) {
					if($taskvar['sort'] == 'complete' && $taskvar['name']) {
						$haveconditions = TRUE;
						if($taskvar['variable']) {
							showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['type'], '', 0, $taskvar['description']);
						} else {
							showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['description']);
						}
					}
				}
			}
			if(!$haveconditions) {
				showtablerow('', 'class="td27" colspan="2"', lang('nolimit'));
			}
			if(is_array($task_settings) && $task_settings) {
				$havesettings = FALSE;
				foreach($task_settings as $taskvar) {
					if($taskvar['name']) {
						if(!$havesettings) {
							showtitle('tasks_add_settings');
							$havesettings = TRUE;
						}
						if($taskvar['variable']) {
							showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['type'], '', 0, $taskvar['description']);
						} else {
							showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['description']);
						}
					}
				}
			}

		}

		showsubmit('addsubmit', 'submit');
		showtablefooter();
		showformfooter();

	} else {

		$applyperm = $grouplimit == 'special' && is_array($applyperm) ? implode("\t", $applyperm) : $grouplimit;
		$starttime = dmktime($starttime);
		$endtime = dmktime($endtime);
		$prize = ${'prize_'.$reward};
		$bonus = ${'bonus_'.$reward};

		if(!$name || !$description) {
			cpmsg('tasks_basic_invalid', '', 'error');
		} elseif(($endtime && $endtime <= $timestamp) || ($starttime && $endtime && $endtime <= $starttime)) {
			cpmsg('tasks_time_invalid', '', 'error');
		} elseif($reward && (!$prize || ($reward == 'credit' && !$bonus))) {
			cpmsg('tasks_reward_invalid', '', 'error');
		}

		$db->query("INSERT INTO {$tablepre}tasks (relatedtaskid, available, name, description, icon, tasklimits, applyperm, scriptname, starttime, endtime, period, reward, prize, bonus)
			VALUES ('$relatedtaskid', '0', '$name', '$description', '$icon', '$tasklimits', '$applyperm', '$script', '$starttime', '$endtime', '$period', '$reward', '$prize', '$bonus')");
		$taskid = $db->insert_id();

		if(in_array($script, $sys_scripts)) {
			if(!$threadid && $author) {
				$authorid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$author'");
			}
			foreach(${$script.'vars'} as $item) {
				if(in_array($item, array('num', 'time', 'threadid'))) {
					$$item = intval($$item);
				}
				$db->query("INSERT INTO {$tablepre}taskvars (taskid, name, description, variable, value)
					VALUES ('$taskid', '".lang('tasks_add_limit_'.$item)."', '".lang('tasks_add_limit_'.$item.'_comment')."', '$item', '".$$item."')");
			}
		} else {
			if(is_array($task_conditions) && $task_conditions) {
				foreach($task_conditions as $taskvars) {
					if($taskvars['name']) {
						$variable = is_array($taskvars['variable']) ? $taskvars['variable'][0] : $taskvars['variable'];
						$db->query("INSERT INTO {$tablepre}taskvars (taskid, sort, name, description, variable, value, type, extra)
							VALUES ('$taskid', '$taskvars[sort]', '$taskvars[name]', '$taskvars[description]', '{$variable}', '${$variable}', '$taskvars[type]', '$taskvars[extra]')");
					}
				}
			}
			if(is_array($task_settings) && $task_settings) {
				foreach($task_settings as $taskvars) {
					if($taskvars['name']) {
						$db->query("INSERT INTO {$tablepre}taskvars (taskid, sort, name, description, variable, value, type, extra)
							VALUES ('$taskid', 'setting', '$taskvars[name]', '$taskvars[description]', '$taskvars[variable]', '${$taskvars[variable]}', '$taskvars[type]', '$taskvars[extra]')");
					}
				}
			}
		}

		cpmsg('tasks_succeed', "$BASESCRIPT?action=tasks", 'succeed');

	}

} elseif($operation == 'edit' && $id) {

	$task = $db->fetch_first("SELECT * FROM {$tablepre}tasks WHERE taskid='$id'");

	include language('tasks');
	@include DISCUZ_ROOT.'./include/tasks/'.$task['scriptname'].'.cfg.php';
	$task_condition_variable = array();
	if(is_array($task_conditions) && $task_conditions) {
		foreach($task_conditions as $task_condition) {
			if($task_condition['variable']) {
				$task_condition_variable[$task_condition['variable'][0]] = $task_condition['variable'];
			}
		}
	}

	if(!submitcheck('editsubmit')) {

		echo '<script type="text/javascript" src="include/js/calendar.js"></script>';
		shownav('extended', 'nav_tasks');
		showsubmenu('nav_tasks', array(
			array('admin', 'tasks', 0),
			array(array('menu' => 'add', 'submenu' => $submenus), '', 0),
			array('nav_task_type', 'tasks&operation=type', 0)
		));

		showformheader('tasks&operation=edit&id='.$id);
		showtableheader('tasks_edit_basic', 'fixpadding');
		showsetting('tasks_add_name', 'name', $task['name'], 'text');
		showsetting('tasks_add_desc', 'description', $task['description'], 'textarea');
		showsetting('tasks_add_icon', 'icon', $task['icon'], 'text');
		if($task['newbietask'] == '0') {
			showsetting('tasks_add_starttime', 'starttime', $task['starttime'] ? gmdate('y-m-d', $task['starttime'] + $timeoffset * 3600) : '', 'calendar');
			showsetting('tasks_add_endtime', 'endtime', $task['endtime'] ? gmdate('y-m-d', $task['endtime'] + $timeoffset * 3600) : '', 'calendar');
			showsetting('tasks_add_period', 'period', $task['period'], 'text');
		}
		showsetting('tasks_add_reward', array('reward', array(
			array('', lang('none'), array('reward_credit' => 'none', 'reward_magic' => 'none', 'reward_medal' => 'none', 'reward_invite' => 'none', 'reward_group' => 'none')),
			array('credit', lang('credits'), array('reward_credit' => '', 'reward_magic' => 'none', 'reward_medal' => 'none', 'reward_invite'=> 'none', 'reward_group' => 'none')),
			$magicstatus ? array('magic', lang('tasks_reward_magic'), array('reward_credit' => 'none', 'reward_magic' => '', 'reward_medal' => 'none', 'reward_invite' => 'none', 'reward_group' => 'none')) : '',
			$medalstatus ? array('medal', lang('medals'), array('reward_credit' => 'none', 'reward_magic' => 'none', 'reward_medal' => '', 'reward_invite' => 'none', 'reward_group' => 'none')) : '',
			$regstatus > 1 ? array('invite', lang('tasks_reward_invite'), array('reward_credit' => 'none', 'reward_magic' => 'none', 'reward_medal' => 'none', 'reward_invite' => '', 'reward_group' => 'none')) : '',
			array('group', lang('tasks_add_group'), array('reward_credit' => 'none', 'reward_magic' => 'none', 'reward_medal' => 'none', 'reward_invite' => 'none', 'reward_group' => ''))
		)), $task['reward'], 'mradio');

		$extcreditarray = array(array(0, lang('select')));
		foreach($extcredits as $creditid => $extcredit) {
			$extcreditarray[] = array($creditid, $extcredit['title']);
		}

		showtagheader('tbody', 'reward_credit', $task['reward'] == 'credit');
		showsetting('tasks_add_extcredit', array('prize_credit', $extcreditarray), $task['prize'], 'select');
		showsetting('tasks_add_credits', 'bonus_credit', $task['bonus'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'reward_magic', $task['reward'] == 'magic');
		showsetting('tasks_add_magicname', array('prize_magic', fetcharray('magicid', 'name', 'magics', "available='1' ORDER BY displayorder")), $task['prize'], 'select');
		showsetting('tasks_add_magicnum', 'bonus_magic', $task['bonus'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'reward_medal', $task['reward'] == 'medal');
		showsetting('tasks_add_medalname', array('prize_medal', fetcharray('medalid', 'name', 'medals', "available='1' ORDER BY displayorder")), $task['prize'], 'select');
		showsetting('tasks_add_medalexp', 'bonus_medal', $task['bonus'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'reward_invite', $task['reward'] == 'invite');
		showsetting('tasks_add_invitenum', 'prize_invite', $task['prize'], 'text');
		showsetting('tasks_add_inviteexp', 'bonus_invite', $task['bonus'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'reward_group', $task['reward'] == 'group');
		showsetting('tasks_add_group', array('prize_group', fetcharray('groupid', 'grouptitle', 'usergroups', "type='special' AND radminid='0'")), $task['prize'], 'select');
		showsetting('tasks_add_groupexp', 'bonus_group', $task['bonus'], 'text');
		showtagfooter('tbody');

		showtitle('tasks_add_appyperm');
		if($task['newbietask'] == '1') {
			showsetting('tasks_newbie_task_item1', '', '', '');
		} elseif($task['newbietask'] == '2') {
			showsetting('tasks_newbie_task_item2', '', '', '');
		} else {
			if(!$task['applyperm']) {
				$task['applyperm'] = 'all';
			}
			$task['grouplimit'] = in_array($task['applyperm'], array('all', 'member', 'admin')) ? $task['applyperm'] : 'special';
			showsetting('tasks_add_groupperm', array('grouplimit', array(
				array('all', lang('tasks_add_group_all'), array('specialgroup' => 'none')),
				array('member', lang('tasks_add_group_member'), array('specialgroup' => 'none')),
				array('admin', lang('tasks_add_group_admin'), array('specialgroup' => 'none')),
				array('special', lang('tasks_add_group_special'), array('specialgroup' => ''))
			)), $task['grouplimit'], 'mradio');
			showtagheader('tbody', 'specialgroup', $task['grouplimit'] == 'special');
			showsetting('tasks_add_usergroup', array('applyperm[]', fetcharray('groupid', 'grouptitle', 'usergroups', '')), explode("\t", $task['applyperm']), 'mselect');
			showtagfooter('tbody');
			showsetting('tasks_add_relatedtask', array('relatedtaskid', fetcharray('taskid', 'name', 'tasks', "available='2' AND taskid!='$task[taskid]'")), $task['relatedtaskid'], 'select');
			showsetting('tasks_add_maxnum', 'tasklimits', $task['tasklimits'], 'text');
		}

		$taskvars = array();
		$query = $db->query("SELECT * FROM {$tablepre}taskvars WHERE taskid='$id'");
		while($taskvar = $db->fetch_array($query)) {
			if($taskvar['sort'] == 'apply') {
				$taskvars['apply'][] = $taskvar;
			} elseif($taskvar['sort'] == 'complete') {
				$taskvars['complete'][$taskvar['variable']] = $taskvar;
			} elseif($taskvar['sort'] == 'setting' && $taskvar['name']) {
				$taskvars['setting'][$taskvar['variable']] = $taskvar;
			}
		}

		if($taskvars['apply']) {
			foreach($taskvars['apply'] as $taskvar) {
				if($taskvar['variable']) {
					showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['type'], '', 0, $taskvar['description']);
				} else {
					showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['description']);
				}
			}
		}

		showtitle('tasks_add_conditions');

		if($task['scriptname'] == 'member') {

			showsetting('tasks_add_limit_act', array('act', array(
				array('buddy', lang('tasks_add_act_buddy'), array('timelimit' => 'none')),
				array('favorite', lang('tasks_add_act_favorite'), array('timelimit' => 'none')),
				array('magic', lang('tasks_add_act_magic'), array('timelimit' => ''))
			)), $taskvars['complete']['act']['value'], 'mradio');
			showsetting('tasks_add_limit_num', 'num', $taskvars['complete']['num']['value'], 'text');
			showtagheader('tbody', 'timelimit', $taskvars['complete']['act']['value'] == 'magic');
			showsetting('tasks_add_limit_time', 'time', $taskvars['complete']['time']['value'], 'text');
			showtagfooter('tbody');

		} elseif($task['scriptname'] == 'post') {

			showsetting('tasks_add_limit_act', array('act', array(
				array('newthread', lang('tasks_add_act_newthread'), array('forumlimit' => '', 'speciallimit' => 'none')),
				array('newreply', lang('tasks_add_act_newreply'), array('forumlimit' => 'none', 'speciallimit' => '')),
				array('newpost', lang('tasks_add_act_newpost'), array('forumlimit' => '', 'speciallimit' => 'none'))
			)), $taskvars['complete']['act']['value'], 'mradio');
			require_once DISCUZ_ROOT.'./include/forum.func.php';
			showtagheader('tbody', 'forumlimit', $taskvars['complete']['act']['value'] != 'newreply');
			showsetting('tasks_add_limit_forumid', '', '', '<SELECT name="forumid"><option value="">'.lang('nolimit').'</option>'.forumselect(FALSE, 0, $taskvars['complete']['forumid']['value']).'</select>');
			showtagfooter('tbody');
			showtagheader('tbody', 'speciallimit', $taskvars['complete']['act']['value'] == 'newreply');
			showsetting('tasks_add_limit_threadid', 'threadid', $taskvars['complete']['threadid']['value'] ? $taskvars['complete']['threadid']['value'] : '', 'text');
			$author = $taskvars['complete']['authorid']['value'] && ($author = $db->result_first("SELECT username FROM {$tablepre}members WHERE uid='{$taskvars[complete][authorid][value]}'")) ? $author : '';
			showsetting('tasks_add_limit_authorid', 'author', $author, 'text');
			showtagfooter('tbody');
			showsetting('tasks_add_limit_num', 'num', $taskvars['complete']['num']['value'], 'text');
			showsetting('tasks_add_limit_time', 'time', $taskvars['complete']['time']['value'], 'text');

		} else {

			if($taskvars['complete']) {
				foreach($taskvars['complete'] as $taskvar) {
					if($taskvar['variable']) {
						if($taskvar['variable'] == 'forumid') {
							require_once DISCUZ_ROOT.'./include/forum.func.php';
							showsetting($taskvar['name'], '', '', '<SELECT name="forumid"><option value="">'.lang('nolimit').'</option>'.forumselect(FALSE, 0, $taskvars['complete']['forumid']['value']).'</select>');
						} elseif($taskvar['variable'] == 'authorid') {
							$author = $taskvars['complete']['authorid']['value'] && ($author = $db->result_first("SELECT username FROM {$tablepre}members WHERE uid='{$taskvars[complete][authorid][value]}'")) ? $author : '';
							showsetting($taskvar['name'], 'author', $author, 'text', '', 0, $taskvar['description']);
						} else {
							showsetting($taskvar['name'], !empty($task_condition_variable[$taskvar['variable']]) ? $task_condition_variable[$taskvar['variable']] : $taskvar['variable'], $taskvar['value'], $taskvar['type'], '', 0, $taskvar['description']);
						}
					} else {
						showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['description']);
					}
				}
			} else {
				showtablerow('', 'class="td27" colspan="2"', lang('nolimit'));
			}
			if($taskvars['setting']) {
				showtitle('tasks_add_settings');
				foreach($taskvars['setting'] as $taskvar) {
					if($taskvar['variable']) {
						showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['type'], '', 0, $taskvar['description']);
					} else {
						showsetting($taskvar['name'], $taskvar['variable'], $taskvar['value'], $taskvar['description']);
					}
				}
			}

		}

		showsubmit('editsubmit', 'submit');
		showtablefooter();
		showformfooter();

	} else {

		$applyperm = $grouplimit == 'special' && is_array($applyperm) ? implode("\t", $applyperm) : $grouplimit;
		$starttime = dmktime($starttime);
		$endtime = dmktime($endtime);
		$prize = ${'prize_'.$reward};
		$bonus = ${'bonus_'.$reward};

		if(!$name || !$description) {
			cpmsg('tasks_basic_invalid', '', 'error');
		} elseif(($starttime != $task['starttime'] || $endtime != $task['endtime']) && (($endtime && $endtime <= $timestamp) || ($starttime && $endtime && $endtime <= $starttime))) {
			cpmsg('tasks_time_invalid', '', 'error');
		} elseif($reward && (!$prize || ($reward == 'credit' && !$bonus))) {
			cpmsg('tasks_reward_invalid', '', 'error');
		} elseif($task['newbietask'] == '1') {
			switch(substr($task['scriptname'], 7)) {
				case 'post_reply':
					$checkid = 'tid';
					$newbiesettingok = checksettings('tid', $threadid);
					break;
				case 'post_newthread':
					$checkid = 'fid';
					$newbiesettingok = checksettings('fid', $forumid);
					break;
				case 'sendpm':
				case 'addbuddy':
					$checkid = 'uid';
					$newbiesettingok = checksettings('uid', $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$author'"));
					break;
				default:
					$newbiesettingok = TRUE;
					break;
			}
			if(!$newbiesettingok) {
				cpmsg('tasks_newbie_setting_invalid_'.$checkid);
			}
		}

		if($task['available'] == '2' && ($starttime > $timestamp || ($endtime && $endtime <= $timestamp))) {
			$db->query("UPDATE {$tablepre}tasks SET available='1' WHERE taskid='$id'", 'UNBUFFERED');
		}
		if($task['available'] == '1' && (!$starttime || $starttime <= $timestamp) && (!$endtime || $endtime > $timestamp)) {
			$db->query("UPDATE {$tablepre}tasks SET available='2' WHERE taskid='$id'", 'UNBUFFERED');
		}

		if(in_array($task['scriptname'], $sys_scripts)) {
			if(!$threadid && $author) {
				$authorid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$author'");
			}
			$itemarray = ${$task['scriptname'].'vars'};
		} else {
			if($author) {
				$authorid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$author'");
			}
			$itemarray = array();
			$query = $db->query("SELECT variable FROM {$tablepre}taskvars WHERE taskid='$id' AND variable IS NOT NULL");
			while($taskvar = $db->fetch_array($query)) {
				$itemarray[] = $taskvar['variable'];
			}
		}

		$db->query("UPDATE {$tablepre}tasks SET relatedtaskid='$relatedtaskid', name='$name', description='$description', icon='$icon', tasklimits='$tasklimits', applyperm='$applyperm', starttime='$starttime', endtime='$endtime', period='$period', reward='$reward', prize='$prize', bonus='$bonus' WHERE taskid='$id'");

		foreach($itemarray as $item) {
			if(in_array($item, array('num', 'time', 'threadid'))) {
				$$item = intval($$item);
			}
			if(isset($$item)) {
				$db->query("UPDATE {$tablepre}taskvars SET value='".$$item."' WHERE taskid='$id' AND variable='$item'");
			}
		}

		if($task['newbietask'] == '1') {
			updatecache('settings');
		}

		cpmsg('tasks_succeed', "$BASESCRIPT?action=tasks", 'succeed');

	}

} elseif($operation == 'delete' && $id) {

	if(!$confirmed) {
		cpmsg('tasks_del_confirm', "$BASESCRIPT?action=tasks&operation=delete&id=$id", 'form');
	}

	$task = $db->fetch_first("SELECT newbietask FROM {$tablepre}tasks WHERE taskid='$id'");
	if(!$task || $task['newbietask'] == '1') {
		cpmsg('undefined_action', '', 'error');
	}

	$db->query("DELETE FROM {$tablepre}tasks WHERE taskid='$id'");
	$db->query("DELETE FROM {$tablepre}taskvars WHERE taskid='$id'");
	$db->query("DELETE FROM {$tablepre}mytasks WHERE taskid='$id'");

	cpmsg('tasks_del', $BASESCRIPT.'?action=tasks', 'succeed');

} elseif($operation == 'type') {

	include language('tasks');

	shownav('extended', 'nav_tasks');
	showsubmenu('nav_tasks', array(
		array('admin', 'tasks', 0),
		array(array('menu' => 'add', 'submenu' => $submenus), '', 0),
		array('nav_task_type', 'tasks&operation=type', 1)
	));
	showtips('tasks_tips_add_type');

	$taskdir = DISCUZ_ROOT.'./include/tasks';
	$tasksdir = dir($taskdir);
	$scripts = array();
	while($entry = $tasksdir->read()) {
		$script = substr($entry, 0, -8);
		if(!in_array($entry, array('.', '..')) && !in_array($script, $sys_scripts) && preg_match("/^[\w\.]+$/", $entry) && substr($entry, -8)== '.inc.php' && strlen($entry) < 30 && is_file($taskdir.'/'.$entry) && is_file($taskdir.'/'.$script.'.cfg.php')) {
			$scripts[] = array('filename' => $script, 'filemtime' => @filemtime($taskdir.'/'.$entry));
		}
	}
	uasort($scripts, 'filemtimesort');

	showtableheader('', 'fixpadding');
	showsubtitle(array('name', 'tasks_script', 'tasks_version', 'copyright', ''));

	foreach($scripts as $script) {
		require_once $taskdir.'/'.$script['filename'].'.cfg.php';
		showtablerow('', array('id="custom_task_'.$script['filename'].'" onmouseover="showMenu({\'ctrlid\':this.id})"'), array(
			$task_name.($script['filemtime'] > $timestamp - 86400 ? ' <font color="red">New!</font>' : '').'<div class="dropmenu1" id="custom_task_'.$script['filename'].'_menu" style="display: none; white-space: normal; width: 30%; padding: 10px;">'.$task_description.'</div>',
			$script['filename'].'.inc.php',
			$task_version,
			$task_copyright,
			in_array($script['filename'], $custom_scripts) ? "<a href=\"$BASESCRIPT?action=tasks&operation=upgrade&script=$script[filename]\" class=\"act\">$lang[tasks_upgrade]</a> <a href=\"$BASESCRIPT?action=tasks&operation=uninstall&script=$script[filename]\" class=\"act\">$lang[tasks_uninstall]</a><br />" : "<a href=\"$BASESCRIPT?action=tasks&operation=install&script=$script[filename]\" class=\"act\">$lang[tasks_install]</a>"
		));
	}
	foreach($sys_types as $script => $task) {
		showtablerow('', '', array(
			$task['name'],
			$script.'.inc.php',
			$task['version'],
			'<a href="http://www.comsenz.com" target="_blank">Comsenz Inc.</a>',
			''
		));
	}

	showtablefooter();

} elseif($operation == 'install' && $script) {

	include language('tasks');

	if((!@include DISCUZ_ROOT.'./include/tasks/'.$script.'.cfg.php') || (!@include DISCUZ_ROOT.'./include/tasks/'.$script.'.inc.php')) {
		cpmsg('tasks_noscript_or_nocfg', '', 'error');
	} elseif(!(function_exists('task_install') && function_exists('task_uninstall') && function_exists('task_upgrade') && function_exists('task_condition') && function_exists('task_preprocess') && function_exists('task_csc') && function_exists('task_sufprocess'))) {
		cpmsg('tasks_code_invalid', '', 'error');
	} elseif($db->result_first("SELECT COUNT(*) FROM {$tablepre}tasks WHERE scriptname='$script'")) {
		cpmsg('tasks_install_duplicate', '', 'error');
	}

	task_install();
	$custom_types[$script] = array('name' => $task_name, 'version' => $task_version);
	$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('tasktypes', '".addslashes(serialize($custom_types))."')");

	cpmsg('tasks_installed', $BASESCRIPT.'?action=tasks', 'succeed');

} elseif($operation == 'uninstall' && $script) {

	if(!$confirmed) {
		cpmsg('tasks_uninstall_confirm', "$BASESCRIPT?action=tasks&operation=uninstall&script=$script", 'form');
	}

	if(in_array($script, $sys_scripts)) {
		cpmsg('undefined_action', '', 'error');
	} elseif(!@include DISCUZ_ROOT.'./include/tasks/'.$script.'.inc.php') {
		cpmsg('tasks_noscript', '', 'error');
	}

	$ids = $comma = '';
	$query = $db->query("SELECT taskid FROM {$tablepre}tasks WHERE scriptname='$script'");
	while($task = $db->fetch_array($query)) {
		$ids = $comma.$task['taskid'];
		$comma = ',';
	}
	if($ids) {
		$db->query("DELETE FROM {$tablepre}tasks WHERE taskid IN ($ids)");
		$db->query("DELETE FROM {$tablepre}taskvars WHERE taskid IN ($ids)");
		$db->query("DELETE FROM {$tablepre}mytasks WHERE taskid IN ($ids)");
	}

	task_uninstall();
	unset($custom_types[$script]);
	$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('tasktypes', '".addslashes(serialize($custom_types))."')");

	cpmsg('tasks_uninstalled', $BASESCRIPT.'?action=tasks', 'succeed');

} elseif($operation == 'upgrade' && $script) {

	include language('tasks');

	if(in_array($script, $sys_scripts)) {
		cpmsg('undefined_action', '', 'error');
	} elseif((!@include DISCUZ_ROOT.'./include/tasks/'.$script.'.cfg.php') || (!@include DISCUZ_ROOT.'./include/tasks/'.$script.'.inc.php')) {
		cpmsg('tasks_noscript_or_nocfg', '', 'error');
	} elseif($custom_types[$script]['version'] >= $task_version) {
		cpmsg('tasks_newest', '', 'error');
	}

	task_upgrade();
	$db->query("UPDATE {$tablepre}tasks SET name='$task_name', description='$task_description', icon='$task_icon', tasklimits='$task_tasklimits', starttime='$task_starttime', endtime='$task_endtime', period='$task_period', reward='$task_reward', prize='$task_prize', bonus='$task_bonus', version='$task_version' WHERE scriptname='$script'");
	$custom_types[$script] = array('name' => $task_name, 'version' => $task_version);
	$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('tasktypes', '".addslashes(serialize($custom_types))."')");

	cpmsg('tasks_updated', $BASESCRIPT.'?action=tasks', 'succeed');

}

function fetcharray($id, $name, $table, $conditions = '1') {
	global $db, $tablepre;

	$array = array(array(0, lang('nolimit')));
	$query = $db->query("SELECT $id, $name FROM $tablepre$table".($conditions ? " WHERE $conditions" : ''));
	while($result = $db->fetch_array($query)) {
		$array[] = array($result[$id], $result[$name]);
	}
	return $array;
}

function runquery($sql) {
	global $dbcharset, $tablepre, $db;

	$sql = str_replace("\r", "\n", str_replace(' cdb_', ' '.$tablepre, $sql));
	$ret = array();
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
		}
		$num++;
	}
	unset($sql);

	foreach($ret as $query) {
		$query = trim($query);
		if($query) {

			if(substr($query, 0, 12) == 'CREATE TABLE') {
				$name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
				$db->query(createtable($query, $dbcharset));

			} else {
				$db->query($query);
			}

		}
	}
}

function createtable($sql, $dbcharset) {
	$type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
	$type = in_array($type, array('MYISAM', 'HEAP')) ? $type : 'MYISAM';
	return preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql).
	(mysql_get_server_info() > '4.1' ? " ENGINE=$type DEFAULT CHARSET=$dbcharset" : " TYPE=$type");
}

function dmktime($date) {
	if(strpos($date, '-')) {
		$time = explode('-', $date);
		return mktime(0, 0, 0, $time[1], $time[2], $time[0]);
	}
	return 0;
}

function checksettings($id, $v) {
	global $db, $tablepre;
	$v = intval($v);
	if(!$v) {
		return FALSE;
	}
	switch($id) {
		case 'tid':
			$result = $db->query("SELECT COUNT(*) FROM {$tablepre}threads WHERE tid='$v' AND displayorder>='0'");
			break;
		case 'fid':
			$result = $db->query("SELECT COUNT(*) FROM {$tablepre}forums WHERE fid='$v'");
			break;
		case 'uid':
			$result = $db->query("SELECT COUNT(*) FROM {$tablepre}members WHERE uid='$v'");
			break;
		default:
			$result = 0;
			break;
	}
	return $result ? TRUE : FALSE;
}

?>