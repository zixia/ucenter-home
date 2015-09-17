<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: task.php 13890 2008-07-08 02:21:38Z liuqiang $
*/

define('CURSCRIPT', 'task');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';

if(!$taskon && $adminid != 1) {
	showmessage('task_close');
}

$discuz_action = 180;
$id = intval($id);

if(empty($action)) {

	$multipage = '';
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $tpp;
	$tasklist = $endtaskids = $magics = $magicids = $medals = $medalids = $groups = $groupids = array();

	switch($item) {
		case 'doing':
			$sql = "mt.status='0'";
			break;
		case 'done':
			$sql = "mt.status='1'";
			break;
		case 'failed':
			$sql = "mt.status='-1'";
			break;
		default:
			$item = 'new';
			$sql = "(mt.taskid IS NULL OR (ABS(mt.status)='1' AND t.period>0 AND $timestamp-mt.dateline>=t.period*3600))";
			break;
	}

	$newbieadd = $item == 'done' || ($prompts['newbietask'] && $newbietaskid) ? '' : "AND t.newbietask!='1'";
	if($prompts['newbietask'] && $newbietaskid) {
		$taskrequired = $db->result_first("SELECT name FROM {$tablepre}tasks WHERE taskid='$newbietaskid'");
	}

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}tasks t
		LEFT JOIN {$tablepre}mytasks mt ON mt.taskid=t.taskid AND mt.uid='$discuz_uid'
		WHERE $sql AND t.available='2' $newbieadd");

	if($num) {
		$updated = FALSE;
		$multipage = multi($num, $tpp, $page, "task.php?item=$item");
		$query = $db->query("SELECT t.*, mt.csc, mt.dateline FROM {$tablepre}tasks t
			LEFT JOIN {$tablepre}mytasks mt ON mt.taskid=t.taskid AND mt.uid='$discuz_uid'
			WHERE $sql AND t.available='2' $newbieadd ORDER BY displayorder, taskid DESC LIMIT $start_limit, $tpp");
		while($task = $db->fetch_array($query)) {
			if($task['reward'] == 'magic') {
				$magicids[] = $task['prize'];
			} elseif($task['reward'] == 'medal') {
				$medalids[] = $task['prize'];
			} elseif($task['reward'] == 'group') {
				$groupids[] = $task['prize'];
			}
			if($task['available'] == '2' && ($task['starttime'] > $timestamp || ($task['endtime'] && $task['endtime'] <= $timestamp))) {
				$endtaskids[] = $task['taskid'];
			}
			$csc = explode("\t", $task['csc']);
			$task['csc'] = floatval($csc[0]);
			$task['lastupdate'] = intval($csc[1]);
			if(!$updated && $item == 'doing' && $task['csc'] < 100 && $timestamp - $task['lastupdate'] > 60) {
				$updated = TRUE;
				require_once DISCUZ_ROOT.'./include/tasks/'.$task['scriptname'].'.inc.php';
				$task['applytime'] = $task['dateline'];
				$result = task_csc($task);
				if($result === TRUE) {
					$task['csc'] = '100';
					$db->query("UPDATE {$tablepre}mytasks SET csc='100' WHERE uid='$discuz_uid' AND taskid='$task[taskid]'");
				} elseif($result === FALSE) {
					$db->query("UPDATE {$tablepre}mytasks SET status='-1' WHERE uid='$discuz_uid' AND taskid='$task[taskid]'", 'UNBUFFERED');
				} else {
					$task['csc'] = floatval($result['csc']);
					$db->query("UPDATE {$tablepre}mytasks SET csc='$task[csc]\t$timestamp' WHERE uid='$discuz_uid' AND taskid='$task[taskid]'", 'UNBUFFERED');
				}
			}
			if(in_array($item, array('done', 'failed')) && $task['period']) {
				$task['t'] = tasktimeformat($task['period'] * 3600 - $timestamp + $task['dateline']);
				$task['allowapply'] = $timestamp - $task['dateline'] >= $task['period'] * 3600 ? 1 : 0;
			}
			if($task['newbietask'] == '1' && $task['taskid'] == $newbietaskid) {
				$taskvars = array();
				$query = $db->query("SELECT sort, name, description, variable, value FROM {$tablepre}taskvars WHERE taskid='$newbietaskid'");
				while($taskvar = $db->fetch_array($query)) {
					if(!$taskvar['variable'] || $taskvar['value']) {
						if($taskvar['sort'] == 'complete') {
							$taskvars['complete'][$taskvar['variable']] = $taskvar;
						} elseif($taskvar['sort'] == 'setting') {
							$taskvars['setting'][$taskvar['variable']] = $taskvar;
						}
					}
				}
				$task['entrance'] = $taskvars['setting']['entrance']['value'].'.php';
				if(isset($taskvars['complete']['threadid'])) {
					$task['entrance'] .= '?tid='.$taskvars['complete']['threadid']['value'];
				} elseif(isset($taskvars['complete']['forumid'])) {
					$task['entrance'] .= '?fid='.$taskvars['complete']['forumid']['value'];
				} elseif(isset($taskvars['complete']['authorid'])) {
					$task['entrance'] .= '?uid='.$taskvars['complete']['authorid']['value'];
				}
			}
			$task['icon'] = $task['icon'] ? $task['icon'] : 'task.gif';
			$task['icon'] = strtolower(substr($task['icon'], 0, 7)) == 'http://' ? $task['icon'] : "images/tasks/$task[icon]";
			$task['dateline'] = $task['dateline'] ? dgmdate("$dateformat $timeformat", $task['dateline'] + $timeoffset * 3600) : '';
			$tasklist[] = $task;
		}
	}

	if($magicids) {
		$query = $db->query("SELECT magicid, name FROM {$tablepre}magics WHERE magicid IN (".implodeids($magicids).")");
		while($magic = $db->fetch_array($query)) {
			$magics[$magic['magicid']] = $magic['name'];
		}
	}

	if($medalids) {
		$query = $db->query("SELECT medalid, name FROM {$tablepre}medals WHERE medalid IN (".implodeids($medalids).")");
		while($medal = $db->fetch_array($query)) {
			$medals[$medal['medalid']] = $medal['name'];
		}
	}

	if($groupids) {
		$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups WHERE groupid IN (".implodeids($groupids).")");
		while($group = $db->fetch_array($query)) {
			$groups[$group['groupid']] = $group['grouptitle'];
		}
	}

	if($item == 'doing' && ($prompts['task']['new'] != $num)) {
		updateprompt('task', $discuz_uid, $num);
	}

	if($endtaskids) {
		$db->query("UPDATE {$tablepre}tasks SET available='1' WHERE taskid IN (".implodeids($endtaskids).")", 'UNBUFFERED');
	}

} elseif($action == 'view' && $id) {

	if(!$task = $db->fetch_first("SELECT t.*, mt.status, mt.csc, mt.dateline, mt.dateline AS applytime FROM {$tablepre}tasks t LEFT JOIN {$tablepre}mytasks mt ON mt.uid='$discuz_uid' AND mt.taskid=t.taskid WHERE t.taskid='$id' AND t.available='2'")) {
		showmessage('undefined_action');
	}

	if($task['reward'] == 'magic') {
		$magic = $db->fetch_first("SELECT name, identifier FROM {$tablepre}magics WHERE magicid='$task[prize]'");
		$magicname = $magic['name'];
		$magic['identifier'] = strtolower($magic['identifier']);
	} elseif($task['reward'] == 'medal') {
		$medal = $db->fetch_first("SELECT name, image FROM {$tablepre}medals WHERE medalid='$task[prize]'");
		$medalname = $medal['name'];
	} elseif($task['reward'] == 'group') {
		$grouptitle = $db->result_first("SELECT grouptitle FROM {$tablepre}usergroups WHERE groupid='$task[prize]'");
	}
	$task['icon'] = $task['icon'] ? $task['icon'] : 'task.gif';
	$task['icon'] = strtolower(substr($task['icon'], 0, 7)) == 'http://' ? $task['icon'] : "images/tasks/$task[icon]";
	$task['endtime'] = $task['endtime'] ? dgmdate("$dateformat $timeformat", $task['endtime'] + $timeoffset * 3600) : '';
	$task['description'] = nl2br($task['description']);

	$taskvars = array();
	$query = $db->query("SELECT sort, name, description, variable, value FROM {$tablepre}taskvars WHERE taskid='$id'");
	while($taskvar = $db->fetch_array($query)) {
		if(!$taskvar['variable'] || $taskvar['value']) {
			if(!$taskvar['variable']) {
				$taskvar['value'] = $taskvar['description'];
			} elseif($taskvar['variable'] == 'forumid') {
				require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
			} elseif($taskvar['variable'] == 'threadid') {
				$subject = $db->result_first("SELECT subject FROM {$tablepre}threads WHERE tid='$taskvar[value]'");
				$subject = $subject ? $subject : "TID $taskvar[value]";
			} elseif($taskvar['variable'] == 'authorid') {
				$author = $db->result_first("SELECT username FROM {$tablepre}members WHERE uid='$taskvar[value]'");
				$author = $author ? $author : "TID $taskvar[value]";
			}
			if($taskvar['sort'] == 'apply') {
				$taskvars['apply'][] = $taskvar;
			} elseif($taskvar['sort'] == 'complete') {
				$taskvars['complete'][$taskvar['variable']] = $taskvar;
			} elseif($taskvar['sort'] == 'setting') {
				$taskvars['setting'][$taskvar['variable']] = $taskvar;
			}
		}
	}

	$grouprequired = $comma = '';
	$task['applyperm'] = $task['applyperm'] == 'all' ? '' : $task['applyperm'];
	if(!in_array($task['applyperm'], array('', 'member', 'admin'))) {
		$query = $db->query("SELECT grouptitle FROM {$tablepre}usergroups WHERE groupid IN (".str_replace("\t", ',', $task['applyperm']).")");
		while($group = $db->fetch_array($query)) {
			$grouprequired .= $comma.$group[grouptitle];
			$comma = ', ';
		}
	}

	if($task['relatedtaskid']) {
		$taskrequired = $db->result_first("SELECT name FROM {$tablepre}tasks WHERE taskid='$task[relatedtaskid]'");
	}

	if($task['status'] == '-1') {
		if($task['period']) {
			$allowapply = $timestamp - $task['dateline'] >= $task['period'] * 3600 ? 3 : -7;
			$task['t'] = tasktimeformat($task['period'] * 3600 - $timestamp + $task['dateline']);
		} else {
			$allowapply = -4;
		}
	} elseif($task['status'] == '0') {
		$allowapply = -1;
		if($task['newbietask'] == '1') {
			$allowapply = -8;
			$entrance = $taskvars['setting']['entrance']['value'].'.php';
			if(isset($taskvars['complete']['threadid'])) {
				$entrance .= '?tid='.$taskvars['complete']['threadid']['value'];
			} elseif(isset($taskvars['complete']['forumid'])) {
				$entrance .= '?fid='.$taskvars['complete']['forumid']['value'];
			} elseif(isset($taskvars['complete']['authorid'])) {
				$entrance .= '?uid='.$taskvars['complete']['authorid']['value'];
			}
		}
		$csc = explode("\t", $task['csc']);
		$task['csc'] = floatval($csc[0]);
		$task['lastupdate'] = intval($csc[1]);
		if($task['csc'] < 100 && $timestamp - $task['lastupdate'] > 60) {
			require_once DISCUZ_ROOT.'./include/tasks/'.$task['scriptname'].'.inc.php';
			$result = task_csc($task);
			if($result === TRUE) {
				$task['csc'] = '100';
				$db->query("UPDATE {$tablepre}mytasks SET csc='100' WHERE uid='$discuz_uid' AND taskid='$id'");
			} elseif($result === FALSE) {
				$db->query("UPDATE {$tablepre}mytasks SET status='-1' WHERE uid='$discuz_uid' AND taskid='$id'", 'UNBUFFERED');
				dheader("Location: task.php?action=view&id=$id");
			} else {
				$task['csc'] = floatval($result['csc']);
				$db->query("UPDATE {$tablepre}mytasks SET csc='$task[csc]\t$timestamp' WHERE uid='$discuz_uid' AND taskid='$id'", 'UNBUFFERED');
			}
		}
	} elseif($task['status'] == '1') {
		if($task['period']) {
			$allowapply = $timestamp - $task['dateline'] >= $task['period'] * 3600 ? 2 : -6;
			$task['t'] = tasktimeformat($task['period'] * 3600 - $timestamp + $task['dateline']);
		} else {
			$allowapply = -5;
		}
	} else {
		$allowapply = 1;
		if($task['newbietask'] == '1' && $newbietaskid) {
			$taskrequired = $db->result_first("SELECT name FROM {$tablepre}tasks WHERE taskid='$newbietaskid'");
		}
	}

	if($allowapply > 0) {
		if($task['applyperm'] && $task['applyperm'] != 'all' && !(($task['applyperm'] == 'member' && $adminid == '0') || ($task['applyperm'] == 'admin' && $adminid > '0') || forumperm($task['applyperm']))) {
			$allowapply = -2;
		} elseif($task['tasklimits'] && $task['achievers'] >= $task['tasklimits']) {
			$allowapply = -3;
		}
	}

	$task['dateline'] = dgmdate("$dateformat $timeformat", $task['dateline'] + $timeoffset * 3600);

	if($inajax) {
		include template('task_newbie_detail');
		dexit();
	}

} elseif($action == 'apply' && $id) {

	if(!$discuz_uid) {
		showmessage('not_loggedin', NULL, 'NOPERM');
	}

	if(!$task = $db->fetch_first("SELECT * FROM {$tablepre}tasks WHERE taskid='$id' AND available='2' AND newbietask!='1'")) {
		showmessage('task_nonexistence', NULL, 'HALTED');
	} elseif(($task['starttime'] && $task['starttime'] > $timestamp) || ($task['endtime'] && $task['endtime'] <= $timestamp)) {
		showmessage('task_offline', NULL, 'HALTED');
	} elseif($task['tasklimits'] && $task['achievers'] >= $task['tasklimits']) {
		showmessage('task_full', NULL, 'HALTED');
	}

	if($task['relatedtaskid'] && !$db->result_first("SELECT COUNT(*) FROM {$tablepre}mytasks WHERE uid='$discuz_uid' AND taskid='$task[relatedtaskid]' AND status='1'")) {
		showmessage('task_relatedtask', 'task.php?action=view&id='.$task['relatedtaskid']);
	} elseif($task['applyperm'] && $task['applyperm'] != 'all' && !(($task['applyperm'] == 'member' && $adminid == '0') || ($task['applyperm'] == 'admin' && $adminid > '0') || forumperm($task['applyperm']))) {
		showmessage('task_grouplimit', 'task.php?item=new');
	} else {

		if(!$task['period'] && $db->result_first("SELECT COUNT(*) FROM {$tablepre}mytasks WHERE uid='$discuz_uid' AND taskid='$id'")) {
			showmessage('task_duplicate', 'task.php?item=new');
		} elseif($task['period'] && $db->result_first("SELECT COUNT(*) FROM {$tablepre}mytasks WHERE uid='$discuz_uid' AND taskid='$id' AND dateline>=$timestamp-$task[period]*3600")) {
			showmessage('task_nextperiod', 'task.php?item=new');
		}
	}

	require_once DISCUZ_ROOT.'./include/task.func.php';
	task_apply($task);

	showmessage('task_applied', "task.php?action=view&id=$id");

} elseif($action == 'draw' && $id) {

	if(!$discuz_uid) {
		showmessage('not_loggedin', NULL, 'NOPERM');
	}

	if(!$task = $db->fetch_first("SELECT t.*, mt.dateline AS applytime, mt.status FROM {$tablepre}tasks t, {$tablepre}mytasks mt WHERE mt.uid='$discuz_uid' AND mt.taskid=t.taskid AND t.taskid='$id' AND t.available='2'")) {
		showmessage('task_nonexistence', NULL, 'HALTED');
	} elseif($task['status'] != 0) {
		showmessage('undefined_action', NULL, 'HALTED');
	} elseif($task['tasklimits'] && $task['achievers'] >= $task['tasklimits']) {
		showmessage('task_up_to_limit', 'task.php');
	}

	require_once DISCUZ_ROOT.'./include/tasks/'.$task['scriptname'].'.inc.php';
	$result = task_csc($task);
	if($result === TRUE) {

		if($task['reward']) {
			require_once DISCUZ_ROOT.'./include/task.func.php';
			$rewards = task_reward($task);
			if($task['reward'] == 'magic') {
				$magicname = $db->result_first("SELECT name FROM {$tablepre}magics WHERE magicid='$task[prize]'");
			} elseif($task['reward'] == 'medal') {
				$medalname = $db->result_first("SELECT name FROM {$tablepre}medals WHERE medalid='$task[prize]'");
			} elseif($task['reward'] == 'group') {
				$grouptitle = $db->result_first("SELECT grouptitle FROM {$tablepre}usergroups WHERE groupid='$task[prize]'");
			}
			sendnotice($discuz_uid, 'task_reward_'.$task['reward'], 'systempm');
		}

		task_sufprocess();

		$db->query("UPDATE {$tablepre}mytasks SET status='1', csc='100', dateline='$timestamp' WHERE uid='$discuz_uid' AND taskid='$id'");
		$db->query("UPDATE {$tablepre}tasks SET achievers=achievers+1 WHERE taskid='$id'", 'UNBUFFERED');

		if(!$db->result_first("SELECT COUNT(*) FROM {$tablepre}mytasks WHERE uid='$discuz_uid' AND status='0'")) {
			updateprompt('task', $discuz_uid, 0);
		}

		if($inajax) {
			taskmessage('100', $task['reward'] ? 'task_reward_'.$task['reward'] : 'task_completed');
		} else {
			showmessage('task_completed', 'task.php?item=done');
		}

	} elseif($result === FALSE) {

		$db->query("UPDATE {$tablepre}mytasks SET status='-1' WHERE uid='$discuz_uid' AND taskid='$id'", 'UNBUFFERED');
		$inajax ? taskmessage('-1', 'task_failed') : showmessage('task_failed', 'task.php?item=failed');

	} else {

		$result['t'] = tasktimeformat($result['remaintime']);
		if($result['csc']) {
			$db->query("UPDATE {$tablepre}mytasks SET csc='$result[csc]\t$timestamp' WHERE uid='$discuz_uid' AND taskid='$id'", 'UNBUFFERED');
			$msg = $result['t'] ? 'task_doing_rt' : 'task_doing';
			$inajax ? taskmessage($result['csc'], $msg) : showmessage($msg, "task.php?action=view&id=$id");
		} else {
			$msg = $result['t'] ? 'task_waiting_rt' : 'task_waiting';
			$inajax ? taskmessage('0', $msg) : showmessage($msg, "task.php?action=view&id=$id");
		}

	}

} elseif($action == 'giveup' && $id && !empty($formhash)) {

	if($formhash != FORMHASH) {
		showmessage('undefined_action', NULL, 'HALTED');
	} elseif(!$task = $db->fetch_first("SELECT t.taskid, mt.status FROM {$tablepre}tasks t LEFT JOIN {$tablepre}mytasks mt ON mt.taskid=t.taskid AND mt.uid='$discuz_uid' WHERE t.taskid='$id' AND t.available='2' AND t.newbietask!='1'")) {
		showmessage('task_nonexistence', NULL, 'HALTED');
	} elseif($task['status'] != '0') {
		showmessage('undefined_action');
	}

	$db->query("DELETE FROM {$tablepre}mytasks WHERE uid='$discuz_uid' AND taskid='$id'", 'UNBUFFERED');
	$db->query("UPDATE {$tablepre}tasks SET applicants=applicants-1 WHERE taskid='$id'", 'UNBUFFERED');

	if(!$db->result_first("SELECT COUNT(*) FROM {$tablepre}mytasks WHERE uid='$discuz_uid' AND status='0'")) {
		updateprompt('task', $discuz_uid, 0);
	}

	showmessage('task_giveup', "task.php?item=view&id=$id");

} elseif($action == 'parter' && $id) {

	$query = $db->query("SELECT * FROM {$tablepre}mytasks WHERE taskid='$id' ORDER BY dateline DESC LIMIT 0, 8");
	while($parter = $db->fetch_array($query)) {
		$parter['avatar'] = discuz_uc_avatar($parter['uid'], 'small');
		$csc = explode("\t", $parter['csc']);
		$parter['csc'] = floatval($csc[0]);
		$parterlist[] = $parter;
	}

	include template('task_parter');
	dexit();

} elseif($action == 'newbie' && $prompts['newbietask'] && $newbietasks) {

	$tpp = 8;
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $tpp;
	$num = count($newbietasks);
	$multipage = multi($num, $tpp, $page, "task.php?action=newbie");

	$ids = $doneids = $sortids = $thisids = array();
	$sortids[] = $newbietaskid;
	foreach($newbietasks as $id => $v) {
		if($newbietaskid != $id) {
			$ids[] = $id;
		}
	}

	if($ids) {
		$query = $db->query("SELECT taskid FROM {$tablepre}mytasks WHERE uid='$discuz_uid' AND taskid IN (".implodeids($ids).") AND status='1'");
		while($mytask = $db->fetch_array($query)) {
			$doneids[] = $mytask['taskid'];
		}
	}

	foreach($ids as $id) {
		if(!in_array($id, $doneids)) {
			$sortids[] = $id;
		}
	}
	$sortids = array_merge($sortids, $doneids);
	for($i = $start_limit; $i < $tpp + $start_limit; $i++) {
		if(!isset($sortids[$i])) {
			break;
		}
		$thisids[] = $sortids[$i];
	}

	$doings = $num - count($doneids);

	include template('task_newbie');
	dexit();

} elseif($action == 'updatenewbietask' && $prompts['newbietask'] && $newbietaskid && $newbietasks[$newbietaskid]['scriptname'] == $scriptname) {

	require_once DISCUZ_ROOT.'./include/task.func.php';
	task_newbie_complete();

	include template('task_newbie');
	dexit();

} else {

	showmessage('undefined_action', NULL, 'HALTED');

}

include template('task');

function taskmessage($csc, $msg) {
	extract($GLOBALS, EXTR_SKIP);
	include_once language('messages');
	include template('header_ajax');
	eval("\$msg = \"$language[$msg]\";");
	echo "$csc|$msg";
	include template('footer_ajax');
	exit;
}

function tasktimeformat($t) {
	global $dlang;

	if($t) {
		$h = floor($t / 3600);
		$m = floor(($t - $h * 3600) / 60);
		$s = floor($t - $h * 3600 - $m * 60);
		return ($h ? "$h{$dlang[date][4]}" : '').($m ? "$m{$dlang[date][6]}" : '').($h || !$s ? '' : "$s{$dlang[date][7]}");
	}
	return '';
}

?>