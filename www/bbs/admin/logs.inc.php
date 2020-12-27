<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: logs.inc.php 21053 2009-11-09 10:29:02Z wangjinbo $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

$lpp = empty($lpp) ? 20 : $lpp;
$checklpp = array();
$checklpp[$lpp] = 'selected="selected"';

if(!in_array($operation, array('illegal', 'rate', 'credit', 'mods', 'medal', 'ban', 'cp', 'magic', 'error', 'invite', 'payment'))) {
	cpmsg('undefined_action', '', 'error');
}
$logdir = DISCUZ_ROOT.'./forumdata/logs/';
$logfiles = get_log_files($logdir, $operation.'log');
$logs = array();
rsort($logfiles);
if($logfiles) {
	$logs = file(!empty($day) ? $logdir.$day.'_'.$operation.'log.php' : $logdir.$logfiles[0]);
}

$page = max(1, intval($page));
$start = ($page - 1) * $lpp;
$logs = array_reverse($logs);

if(empty($keyword)) {
	$num = count($logs);
	$multipage = multi($num, $lpp, $page, "$BASESCRIPT?action=logs&operation=$operation&lpp=$lpp".(!empty($day) ? '&day='.$day : ''), 0, 3);
	$logs = array_slice($logs, $start, $lpp);

} else {
	foreach($logs as $key => $value) {
		if(strpos($value, $keyword) === FALSE) {
			unset($logs[$key]);
		}
	}
	$multipage = '';
}

$usergroup = array();
if(in_array($operation, array('rate', 'mods', 'ban', 'cp'))) {
	$query = $db->query("SELECT groupid, grouptitle FROM {$tablepre}usergroups");
	while($group = $db->fetch_array($query)) {
		$usergroup[$group['groupid']] = $group['grouptitle'];
	}
}

shownav('tools', 'nav_logs', 'nav_logs_'.$operation);
if($logfiles) {
	$sel = '<select class="right" onchange="location.href=\''.$BASESCRIPT.'?action=logs&operation='.$operation.'&day=\'+this.value">';
	foreach($logfiles as $logfile) {
		list($date) = explode('_', $logfile);
		$sel .= '<option value="'.$date.'"'.($date == $day ? ' selected="selected"' : '').'>'.$date.'</option>';
	}
	$sel .= '</select>';
} else {
	$sel = '';
}
showsubmenu('nav_logs', array(
	array(array('menu' => 'nav_logs_member', 'submenu' => array(
		array('nav_logs_illegal', 'logs&operation=illegal'),
		array('nav_logs_ban', 'logs&operation=ban'),
		array('nav_logs_mods', 'logs&operation=mods'),
	)), '', in_array($operation, array('illegal', 'ban', 'mods'))),
	array(array('menu' => 'nav_logs_system', 'submenu' => array(
		array('nav_logs_cp', 'logs&operation=cp'),
		array('nav_logs_error', 'logs&operation=error'),
	)), '', in_array($operation, array('cp', 'error'))),
	array(array('menu' => 'nav_logs_extended', 'submenu' => array(
		array('nav_logs_rate', 'logs&operation=rate'),
		array('nav_logs_credit', 'logs&operation=credit'),
		array('nav_logs_magic', 'logs&operation=magic'),
		array('nav_logs_medal', 'logs&operation=medal'),
		array('nav_logs_invite', 'logs&operation=invite'),
		array('nav_logs_payment', 'logs&operation=payment'),
	)), '', in_array($operation, array('rate', 'credit', 'magic', 'medal', 'invite', 'payment')))
), $sel);
showformheader("logs&operation=$operation");
showtableheader('', 'fixpadding');
$filters = '';
if($operation == 'illegal') {

	showtablerow('class="header"', array('class="td23"','class="td23"','class="td23"','class="td23"','class="td23"'), array(
		lang('logs_passwd_username'),
		lang('logs_passwd_password'),
		lang('logs_passwd_security'),
		lang('ip'),
		lang('time'),
	));

	foreach($logs as $logrow) {
		$log = explode("\t", $logrow);
		if(empty($log[1])) {
			continue;
		}
		$log[1] = gmdate('y-n-j H:i', $log[1] + $timeoffset * 3600);
		if(strtolower($log[2]) == strtolower($discuz_userss)) {
			$log[2] = "<b>$log[2]</b>";
		}
		$log[5] = $allowviewip ? $log[5] : '-';

		showtablerow('', array('class="bold"', '', '', 'class="smallefont"', 'class="smallefont"'), array(
			$log[2],
			$log[3],
			$log[4],
			$log[5],
			$log[1]
		));

	}

} elseif($operation == 'rate') {

	showtablerow('class="header"', array('class="td23"','class="td23"','class="td23"','class="td23"','class="td23"','class="td24"'), array(
		lang('username'),
		lang('usergroup'),
		lang('time'),
		lang('logs_rating_username'),
		lang('logs_rating_rating'),
		lang('subject'),
		lang('reason'),
	));

	foreach($logs as $logrow) {
		$log = explode("\t", $logrow);
		if(empty($log[1])) {
			continue;
		}
		$log[1] = gmdate('y-n-j H:i', $log[1] + $timeoffset * 3600);
		$log[2] = "<a href=\"space.php?username=".rawurlencode($log[2])."\" target=\"_blank\">$log[2]</a>";
		$log[3] = $usergroup[$log[3]];
		if($log[4] == $discuz_userss) {
			$log[4] = "<b>$log[4]</b>";
		}
		$log[4] = "<a href=\"space.php?username=".rawurlencode($log[4])."\" target=\"_blank\">$log[4]</a>";
		$log[6] = $extcredits[$log[5]]['title'].' '.($log[6] < 0 ? "<b>$log[6]</b>" : "+$log[6]").' '.$extcredits[$log[5]]['unit'];
		$log[7] = $log[7] ? "<a href=\"./viewthread.php?tid=$log[7]\" target=\"_blank\" title=\"$log[8]\">".cutstr($log[8], 20)."</a>" : "<i>$lang[logs_rating_manual]</i>";

		showtablerow('', array('class="bold"'), array(
			$log[2],
			$log[3],
			$log[1],
			$log[4],
			(trim($log[10]) == 'D' ? $lang['logs_rating_delete'] : '').$log[6],
			$log[7],
			$log[9]
		));
	}

} elseif($operation == 'credit') {

	showtablerow('class="header"', array('class="td23"','class="td23"','class="td23"','class="td24"','class="td24"'), array(
		lang('username'),
		lang('logs_credit_fromto'),
		lang('time'),
		lang('logs_credit_send'),
		lang('logs_credit_receive'),
		lang('action'),
	));

	$lpp = max(5, empty($lpp) ? 50 : intval($lpp));
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $lpp;

	$keywordadd = !empty($keyword) ? "AND c.fromto LIKE '%$keyword%'" : '';

	$mpurl = "$BASESCRIPT?action=logs&operation=$operation&keyword=".rawurlencode($keyword)."&lpp=$lpp";
	if(in_array($opt, array('TFR', 'RCV', 'EXC', 'UGP', 'AFD'))) {
		$optadd = "AND c.operation='$opt'";
		$mpurl .= '&opt='.$opt;
	} else {
		$optadd = '';
	}

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}creditslog c WHERE 1 $keywordadd $optadd");

	$multipage = multi($num, $lpp, $page, $mpurl, 0, 3);

	$filters .= '<select onchange="window.location=\''.$BASESCRIPT.'?action=logs&operation=credit&opt=\'+this.options[this.selectedIndex].value"><option value="">'.$lang['action'].'</option><option value="">'.$lang['all'].'</option>';
	foreach(array('TFR', 'RCV', 'EXC', 'UGP', 'AFD') as $o) {
		$filters .= '<option value="'.$o.'" '.(!empty($opt) && $o == $opt ? 'selected="selected"' : '').'>'.$lang['logs_credit_operation_'.strtolower($o)].'</option>';
	}
	$filters .= '</select>';

	$total['send'] = $total['receive'] = array();
	$query = $db->query("SELECT c.*, m.username FROM {$tablepre}creditslog c
		LEFT JOIN {$tablepre}members m USING (uid)
		WHERE 1 $keywordadd $optadd ORDER BY dateline DESC LIMIT $start_limit, $lpp");

	while($log = $db->fetch_array($query)) {
		$total['send'][$log['sendcredits']] += $log['send'];
		$total['receive'][$log['receivecredits']] += $log['receive'];
		$log['dateline'] = gmdate('y-n-j H:i', $log['dateline'] + $timeoffset * 3600);
		$log['operation'] = $lang['logs_credit_operation_'.strtolower($log['operation'])];
		showtablerow('', array('class="bold"'), array(
			"<a href=\"space.php?username=".rawurlencode($log['username'])."\" target=\"_blank\">$log[username]",
			$log[fromto],
			$log[dateline],
			isset($extcredits[$log['sendcredits']]) ? $extcredits[$log['sendcredits']]['title'].' '.$log['send'].' '.$extcredits[$log['sendcredits']]['unit'] : $log['send'],
			isset($extcredits[$log['receivecredits']]) ? $extcredits[$log['receivecredits']]['title'].' '.$log['receive'].' '.$extcredits[$log['receivecredits']]['unit'] : $log['receive'],
			$log[operation],
		));
	}

	$result = array('send' => array(), 'receive' => array());
	foreach(array('send', 'receive') as $key) {
		foreach($total[$key] as $id => $amount) {
			if(isset($extcredits[$id])) {
				$result[$key][] = $extcredits[$id]['title'].' '.$amount.' '.$extcredits[$id]['unit'];
			}
		}
	}

	showtablerow('', '', array(
		'',
		'',
		'',
		$lang['logs_credit_total'].implode('; ', $result['send']),
		$lang['logs_credit_total'].implode(', ', $result['receive']),
		''
	));

} elseif($operation == 'mods') {

	include language('modactions');

	showtablerow('class="header"', array('class="td23"','class="td23"','class="td23"','class="td23"','class="td24"','class="td24"','class="td23"'), array(
		lang('operator'),
		lang('usergroup'),
		lang('ip'),
		lang('time'),
		lang('forum'),
		lang('thread'),
		lang('action'),
		lang('reason'),
	));

	foreach($logs as $logrow) {
		$log = explode("\t", $logrow);
		if(empty($log[1])) {
			continue;
		}
		$log[1] = gmdate('y-n-j H:i', $log[1] + $timeoffset * 3600);
		$log[2] = stripslashes($log[2]);
		$log[3] = $usergroup[$log[3]];
		$log[4] = $allowviewip ? $log[4] : '-';
		$log[6] = "<a href=\"./forumdisplay.php?fid=$log[5]\" target=\"_blank\">$log[6]</a>";
		$log[8] = "<a href=\"./viewthread.php?tid=$log[7]\" target=\"_blank\" title=\"$log[8]\">".cutstr($log[8], 15)."</a>";
		$log[9] = $modactioncode[trim($log[9])];
		showtablerow('', array('class="bold"'), array(
			"<a href=\"space.php?username=".rawurlencode($log[2])."\" target=\"_blank\">".($log[2] != $discuz_userss ? "<b>$log[2]</b>" : $log[2]),
			$log[3],
			$log[4],
			$log[1],
			$log[6],
			$log[8],
			$log[9],
			$log[10],
		));
	}

} elseif($operation == 'ban') {

	showtablerow('class="header"', array('class="td23"', 'class="td23"', 'class="td23"', 'class="td23"', 'class="td23"', 'class="td23"', 'class="td24"', 'class="td23"'), array(
		lang('operator'),
		lang('usergroup'),
		lang('ip'),
		lang('time'),
		lang('username'),
		lang('operation'),
		lang('logs_banned_group'),
		lang('validity'),
		lang('reason'),
	));

	foreach($logs as $logrow) {
		$log = explode("\t", $logrow);
		if(empty($log[1])) {
			continue;
		}
		$log[1] = gmdate('y-n-j H:i', $log[1] + $timeoffset * 3600);
		$log[2] = "<a href=\"space.php?username=".rawurlencode($log[2])."\" target=\"_blank\">$log[2]";
		$log[3] = $usergroup[$log[3]];
		$log[4] = $allowviewip ? $log[4] : '-';
		$log[5] = "<a href=\"space.php?username=".rawurlencode($log[5])."\" target=\"_blank\">$log[5]</a>";
		$log[8] = trim($log[8]) ? gmdate('y-n-j', $log[8] + $timeoffset * 3600) : '';
		showtablerow('', array('class="bold"'), array(
			$log[2],
			$log[3],
			$log[4],
			$log[1],
			$log[5],
			(in_array($log[6], array(4, 5)) && !in_array($log[7], array(4, 5)) ? '<i>'.$lang['logs_banned_unban'].'</i>' : '<b>'.$lang['logs_banned_ban'].'</b>'),
			"{$usergroup[$log[6]]} / {$usergroup[$log[7]]}",
			$log[8],
			$log[9]
		));
	}

} elseif($operation == 'cp') {

	showtablerow('class="header"', array('class="td23"','class="td23"','class="td23"','class="td24"','class="td24"', ''), array(
		lang('operator'),
		lang('usergroup'),
		lang('ip'),
		lang('time'),
		lang('action'),
		lang('other')
	));

	foreach($logs as $logrow) {
		$log = explode("\t", $logrow);
		if(empty($log[1])) {
			continue;
		}
		$log[1] = gmdate('y-n-j H:i', $log[1] + $timeoffset * 3600);
		$log[2] = stripslashes($log[2]);
		$log[2] = "<a href=\"space.php?username=".rawurlencode($log[2])."\" target=\"_blank\">".($log[2] != $discuz_userss ? "<b>$log[2]</b>" : $log[2])."</a>";
		$log[3] = $usergroup[$log[3]];
		$log[4] = $allowviewip ? $log[4] : '-';
		$log[5] = $lang['cplog_'.rtrim($log[5])];
		$log[6] = cutstr($log[6], 200);
 		showtablerow('', array('class="bold"'), array($log[2], $log[3], $log[4], $log[1], $log[5], $log[6]));
	}

} elseif($operation == 'error') {

	showtablerow('class="header"', array('class="td23"', 'class="td24"', 'class="td24"'), array(
		lang('type'),
		lang('username'),
		lang('time'),
		lang('message'),
	));

	foreach($logs as $logrow) {
		$log = explode("\t", $logrow);
		if(empty($log[1])) {
			continue;
		}
		$log[1] = gmdate('y-n-j H:i', $log[1] + $timeoffset * 3600);
		$tmp = explode('&lt;br /&gt;', $log[3]);
		$username = $tmp[1] ? "<a href=\"space.php?username=".rawurlencode($tmp[0])."\" target=\"_blank\">$tmp[0]</a>" : '';
		$ip = $tmp[1] ? $tmp[1] : $tmp[0];

		showtablerow('', array('class="bold"'), array(
			$log[2],
			($username ? $username."<br />" : '').$ip,
			$log[1],
			$log[4]
		));

	}

} elseif($operation == 'invite') {

	if(!submitcheck('invitesubmit')) {
		showtablerow('class="header"', array('width="35"','class="td23"','class="td24"','class="td24"','class="td23"','class="td24"','class="td24"'), array(
			'',
			lang('logs_invite_buyer'),
			lang('logs_invite_buydate'),
			lang('logs_invite_expiration'),
			lang('logs_invite_ip'),
			lang('logs_invite_code'),
			lang('logs_invite_status'),
		));

		$tpp = $lpp ? intval($lpp) : $tpp;
		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$addstatus = '';
		if(in_array($status, array(1,2,3,4))) {
			$statusurl .= '&status='.$status;
			$addstatus = "AND status='$status'";
		}

		$invitecount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}invites WHERE 1 $addstatus");
		$multipage = multi($invitecount, $tpp, $page, "$BASESCRIPT?action=logs&operation=invite&lpp=$lpp$statusurl", 0, 3);

		$filters .= '<select onchange="window.location=\''.$BASESCRIPT.'?action=logs&operation=invite&status=\'+this.options[this.selectedIndex].value"><option value="">'.$lang['action'].'</option><option value="">'.$lang['all'].'</option>';
		foreach(array(1,2,3,4) as $s) {
			$filters .= '<option value="'.$s.'" '.(!empty($status) && $s == $status ? 'selected="selected"' : '').'>'.lang('logs_invite_status_'.$s).'</option>';
		}
		$filters .= '</select>';

		$query = $db->query("SELECT i.*, m.username FROM {$tablepre}invites i, {$tablepre}members m
				WHERE i.uid=m.uid $addstatus
				ORDER BY i.dateline LIMIT $start_limit,$tpp");
		while($invite = $db->fetch_array($query)) {
			$invite['statuslog'] = $lang['logs_invite_status_'.$invite['status']];
			$username = "<a href=\"space.php?uid=$invite[uid]\">$invite[username]</a>";
			$invite['dateline'] = gmdate('Y-n-j H:i', $invite['dateline'] + $timeoffset * 3600);
			$invite['expiration'] = gmdate('Y-n-j H:i', $invite['expiration'] + $timeoffset * 3600);
			$stats = $invite['statuslog'].($invite['status'] == 2 ? '&nbsp;[<a href="space.php?uid='.$invite['reguid'].'">'.$lang['logs_invite_target'].'</a>]' : '');

			showtablerow('', array('', 'class="bold"'), array(
				'<input type="checkbox" class="checkbox" name="delete[]" value="'.$invite[invitecode].'" />',
				$username,
				$invite['dateline'],
				$invite['expiration'],
				$invite['inviteip'],
				$invite['invitecode'],
				$stats
			));
		}

	} else {

		if($deletelist = implodeids($delete)) {
			$db->query("DELETE FROM {$tablepre}invites WHERE invitecode IN ($deletelist)");
		}

		header("Location: $boardurl$BASESCRIPT?action=logs&operation=invite");
	}

} elseif($operation == 'magic') {

	require_once DISCUZ_ROOT.'./forumdata/cache/cache_magics.php';

	$lpp = empty($lpp) ? 50 : $lpp;
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $lpp;

	$mpurl = "$BASESCRIPT?action=logs&operation=magic&lpp=$lpp";

	if(in_array($opt, array('1', '2', '3', '4', '5'))) {
		$optadd = "AND ma.action='$opt'";
		$mpurl .= '&opt='.$opt;
	} else {
		$optadd = '';
	}

	if(!empty($magicid)) {
		$magicidadd = "AND ma.magicid='".intval($magicid)."'";
	} else {
		$magicidadd = '';
	}

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}magiclog ma WHERE 1 $magicidadd $optadd");

	$multipage = multi($num, $lpp, $page, $mpurl, 0, 3);

	$check1 = $check2 = array();
	$check1[$magicid] = 'selected="selected"';
	$check2[$opt] = 'selected="selected"';

	$filters .= '<select onchange="window.location=\''.$BASESCRIPT.'?action=logs&operation=magic&opt='.$opt.'&lpp='.$lpp.'&magicid=\'+this.options[this.selectedIndex].value"><option value="">'.$lang['magics_type'].'</option><option value="">'.$lang['magics_type_all'].'</option>';
	foreach($_DCACHE['magics'] as $id => $magic) {
		$filters .= '<option value="'.$id.'" '.$check1[$id].'>'.$magic['name'].'</option>';
	}
	$filters .= '</select>';

	$filters .= '<select onchange="window.location=\''.$BASESCRIPT.'?action=logs&operation=magic&magicid='.$magicid.'&lpp='.$lpp.'&opt=\'+this.options[this.selectedIndex].value"><option value="">'.$lang['action'].'</option><option value="">'.$lang['all'].'</option>';
	foreach(array('1', '2', '3', '4', '5') as $o) {
		$filters .= '<option value="'.$o.'" '.$check2[$o].'>'.$lang['logs_magic_operation_'.$o].'</option>';
	}
	$filters .= '</select>';

	showtablerow('class="header"', array('class="td23"', 'class="td23"', 'class="td24"', 'class="td23"', 'class="td23"', 'class="td24"'), array(
		lang('username'),
		lang('name'),
		lang('time'),
		lang('num'),
		lang('price'),
		lang('action')
	));

	$query = $db->query("SELECT ma.*, m.username FROM {$tablepre}magiclog ma
		LEFT JOIN {$tablepre}members m USING (uid)
		WHERE 1 $magicidadd $optadd ORDER BY dateline DESC LIMIT $start_limit, $lpp");

	while($log = $db->fetch_array($query)) {
		$log['name'] = $_DCACHE['magics'][$log['magicid']]['name'];
		$log['dateline'] = gmdate('Y-n-j H:i', $log['dateline'] + $timeoffset * 3600);
		$log['action'] = $lang['logs_magic_operation_'.$log['action']];
		showtablerow('', array('class="bold"'), array(
			"<a href=\"space.php?username=".rawurlencode($log['username'])."\" target=\"_blank\">$log[username]",
			$log['name'],
			$log['dateline'],
			$log['amount'],
			$log['price'],
			$log['action']
		));
	}

} elseif($operation == 'medal') {

	require_once DISCUZ_ROOT.'./forumdata/cache/cache_medals.php';

	$lpp = empty($lpp) ? 50 : $lpp;
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $lpp;

	$mpurl = "$BASESCRIPT?action=logs&operation=medal&lpp=$lpp";

	if(in_array($opt, array('0', '1', '2', '3'))) {
		$optadd = "AND me.type='$opt'";
		$mpurl .= '&opt='.$opt;
	} else {
		$optadd = '';
	}

	if(!empty($medalid)) {
		$medalidadd = "AND me.medalid='".intval($medalid)."'";
	} else {
		$medalidadd = '';
	}

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}medallog me WHERE 1 $medalidadd $optadd");

	$multipage = multi($num, $lpp, $page, $mpurl, 0, 3);

	$check1 = $check2 = array();
	$check1[$medalid] = 'selected="selected"';
	$check2[$opt] = 'selected="selected"';

	$filters .= '<select onchange="window.location=\''.$BASESCRIPT.'?action=logs&operation=medal&opt='.$opt.'&lpp='.$lpp.'&medalid=\'+this.options[this.selectedIndex].value"><option value="">'.$lang['medals'].'</option><option value="">'.$lang['all'].'</option>';
	foreach($_DCACHE['medals'] as $id => $medal) {
		$filters .= '<option value="'.$id.'" '.$check1[$id].'>'.$medal['name'].'</option>';
	}
	$filters .= '</select>';

	$filters .= '<select onchange="window.location=\''.$BASESCRIPT.'?action=logs&operation=medal&medalid='.$medalid.'&lpp='.$lpp.'&opt=\'+this.options[this.selectedIndex].value"><option value="">'.$lang['action'].'</option><option value="">'.$lang['all'].'</option>';
	foreach(array('0', '1', '2', '3') as $o) {
		$filters .= '<option value="'.$o.'" '.$check2[$o].'>'.$lang['logs_medal_operation_'.$o].'</option>';
	}
	$filters .= '</select>';

	showtablerow('class="header"', array('class="td23"', 'class="td24"', 'class="td23"', 'class="td23"'), array(
		lang('username'),
		lang('logs_medal_name'),
		lang('type'),
		lang('time'),
		lang('logs_medal_expiration')
	));

	$query = $db->query("SELECT me.*, m.username FROM {$tablepre}medallog me
		LEFT JOIN {$tablepre}members m USING (uid)
		WHERE 1 $medalidadd $optadd ORDER BY dateline DESC LIMIT $start_limit, $lpp");

	while($log = $db->fetch_array($query)) {
		$log['name'] = $_DCACHE['medals'][$log['medalid']]['name'];
		$log['dateline'] = gmdate('Y-n-j H:i', $log['dateline'] + $timeoffset * 3600);
		$log['expiration'] = empty($log['expiration']) ? lang('logs_noexpire') : gmdate('Y-n-j H:i', $log['expiration'] + $timeoffset * 3600);
		showtablerow('', array('class="td23"', 'class="td24"', 'class="td23"', 'class="td24"'), array(
			"<a href=\"space.php?username=".rawurlencode($log['username'])."\" target=\"_blank\">$log[username]",
			$log['name'],
			$lang['logs_medal_operation_'.$log['type']],
			$log['dateline'],
			$log['expiration']
		));
	}

} elseif($operation == 'payment') {

	showtablerow('class="header"', array('width="30%"','class="td23"','class="td23"','class="td24"','class="td23"','class="td24"','class="td24"'), array(
		lang('subject'),
		lang('logs_payment_amount'),
		lang('logs_payment_netamount'),
		lang('logs_payment_seller'),
		lang('logs_payment_buyer'),
		lang('logs_payment_dateline'),
		lang('logs_payment_buydateline'),
	));

	$tpp = $lpp ? intval($lpp) : $tpp;
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $tpp;

	$threadcount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}paymentlog");
	$multipage = multi($threadcount, $tpp, $page, "$BASESCRIPT?action=logs&operation=payment&lpp=$lpp", 0, 3);
	$paythreadlist = array();

	$query = $db->query("SELECT p.*, m.username, t.subject, t.dateline AS postdateline, t.author, t.authorid AS tauthorid
			FROM {$tablepre}paymentlog p
			LEFT JOIN {$tablepre}members m ON m.uid=p.uid
			LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
			ORDER BY p.dateline LIMIT $start_limit,$tpp");
	while($paythread = $db->fetch_array($query)) {
		$paythread['seller'] = $paythread['tauthorid'] ? "<a href=\"space.php?uid=$paythread[tauthorid]\">$paythread[author]</a>" : lang('logs_payment_del')."(<a href=\"space.php?uid=$paythread[authorid]\">".lang('logs_payment_view')."</a>)";;
		$paythread['buyer'] = "<a href=\"space.php?uid=$paythread[uid]\">$paythread[username]</a>";
		$paythread['subject'] = $paythread['subject'] ? "<a href=\"viewthread.php?tid=$paythread[tid]\">$paythread[subject]</a>" : lang('logs_payment_del');
		$paythread['dateline'] = gmdate('Y-n-j H:i', $paythread['dateline'] + $timeoffset * 3600);
		$paythread['postdateline'] = $paythread['postdateline'] ? gmdate('Y-n-j H:i', $paythread['postdateline'] + $timeoffset * 3600) : lang('logs_payment_del');
		$paythreadlist[] = $paythread;
	}

	foreach($paythreadlist as $paythread) {
		showtablerow('', array('', 'class="bold"'), array(
			$paythread['subject'],
			$paythread['amount'],
			$paythread['netamount'],
			$paythread['seller'],
			$paythread['buyer'],
			$paythread['postdateline'],
			$paythread['dateline']
		));
	}
}

function get_log_files($logdir = '', $action = 'action') {
	$dir = opendir($logdir);
	$files = array();
	while($entry = readdir($dir)) {
		$files[] = $entry;
	}
	closedir($dir);

	if($files) {
		sort($files);
		$logfile = $action;
		$logfiles = array();
		$ym = '';
		foreach($files as $file) {
			if(strpos($file, $logfile) !== FALSE) {
				if(substr($file, 0, 6) != $ym) {
					$ym = substr($file, 0, 6);
				}
				$logfiles[$ym][] = $file;
			}
		}
		if($logfiles) {
			$lfs = array();
			foreach($logfiles as $ym => $lf) {
				$lastlogfile = $lf[0];
				unset($lf[0]);
				$lf[] = $lastlogfile;
				$lfs = array_merge($lfs, $lf);
			}
			return array_slice($lfs, -2, 2);
		}
		return array();
	}
	return array();
}

showsubmit($operation == 'invite' ? 'invitesubmit' : '', 'submit', 'del', $filters, $multipage.lang('logs_lpp').':<select onchange="if(this.options[this.selectedIndex].value != \'\') {window.location=\''.$BASESCRIPT.'?action=logs&operation='.$operation.'&lpp=\'+this.options[this.selectedIndex].value+\'&sid='.$sid.'\' }"><option value="20" '.$checklpp[20].'> 20 </option><option value="40" '.$checklpp[40].'> 40 </option><option value="80" '.$checklpp[80].'> 80 </option></select> &nbsp;<input type="text" class="txt" name="keyword" value="'.$keyword.'" /><input type="submit" class="btn" value="'.$lang['search'].'"  />');
showtablefooter();
showformfooter();

?>