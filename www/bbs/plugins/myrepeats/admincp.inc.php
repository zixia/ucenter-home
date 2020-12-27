<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: admincp.inc.php 21275 2009-11-24 08:21:28Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

$Plang = $scriptlang['myrepeats'];

if($op == 'lock') {
	$lock = $db->result_first("SELECT locked FROM {$tablepre}myrepeats WHERE uid='$uid' AND username='$username'");
	$locknew = $lock ? 0 : 1;
	$db->query("UPDATE {$tablepre}myrepeats SET locked='$locknew' WHERE uid='$uid' AND username='$username'");
	ajaxshowheader();
	echo $lock ? $Plang['normal'] : $Plang['lock'];
	ajaxshowfooter();
	exit;
} elseif($op == 'delete') {
	$db->query("DELETE FROM {$tablepre}myrepeats WHERE uid='$uid' AND username='$username'");
	ajaxshowheader();
	echo $Plang['deleted'];
	ajaxshowfooter();
	exit;
}

$ppp = 100;
$resultempty = FALSE;
$srchadd = $searchtext = $extra = '';
$page = max(1, intval($page));
if(!empty($srchuid)) {
	$srchuid = intval($srchuid);
	$srchadd = "AND mr.uid='$srchuid'";
} elseif(!empty($srchusername)) {
	$srchuid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$srchusername'");
	if($srchuid) {
		$srchadd = "AND mr.uid='$srchuid'";
	} else {
		$resultempty = TRUE;
	}
} elseif(!empty($srchrepeat)) {
	$extra = '&srchrepeat='.rawurlencode(stripslashes($srchrepeat));
	$srchadd = "AND mr.username='$srchrepeat'";
	$searchtext = $Plang['search'].' "'.stripslashes($srchrepeat).'" '.$Plang['repeats'].'&nbsp;';
}

if($srchuid) {
	$extra = '&srchuid='.$srchuid;
	$srchusername = $db->result_first("SELECT username FROM {$tablepre}members WHERE uid='$srchuid'");
	$searchtext = $Plang['search'].' "'.$srchusername.'" '.$Plang['repeatusers'].'&nbsp;';
}

$statary = array(-1 => $Plang['status'], 0 => $Plang['normal'], 1 => $Plang['lock']);
$status = isset($status) ? $status : -1;

if(isset($status) && $status >= 0) {
	$srchadd .= " AND mr.locked='$status'";
	$searchtext .= $Plang['search'].$statary[$status].$Plang['statuss'];
}

if($searchtext) {
	$searchtext = '<a href="'.$BASESCRIPT.'?action=plugins&operation=config&identifier=myrepeats&mod=admincp">'.$Plang['viewall'].'</a>&nbsp'.$searchtext;
}

@include_once DISCUZ_ROOT.'./forumdata/cache/cache_usergroups.php';

showtableheader();
showformheader('plugins&operation=config&identifier=myrepeats&mod=admincp', 'repeatsubmit');
showsubmit('repeatsubmit', $Plang['search'], $lang['username'].': <input name="srchusername" value="'.htmlspecialchars(stripslashes($srchusername)).'" class="txt" />&nbsp;&nbsp;'.$Plang['repeat'].': <input name="srchrepeat" value="'.htmlspecialchars(stripslashes($srchrepeat)).'" class="txt" />', $searchtext);
showformfooter();

$statselect = '<select onchange="location.href=\''.$BASESCRIPT.'?action=plugins&operation=config&identifier=myrepeats&mod=admincp'.$extra.'&status=\' + this.value">';
foreach($statary as $k => $v) {
	$statselect .= '<option value="'.$k.'"'.($k == $status ? ' selected' : '').'>'.$v.'</option>';
}
$statselect .= '</select>';

echo '<tr class="header"><th>'.$Plang['username'].'</th><th>'.$lang['usergroup'].'</th><th>'.$Plang['repeat'].'</th><th>'.$Plang['lastswitch'].'</th><th>'.$statselect.'</th><th></th></tr>';
if(!$resultempty) {
	$count = $db->result_first("SELECT COUNT(*) FROM {$tablepre}myrepeats mr WHERE 1 $srchadd");
	$query = $db->query("SELECT mr.*, m.username AS user,m.groupid FROM {$tablepre}myrepeats mr LEFT JOIN {$tablepre}members m ON m.uid=mr.uid WHERE 1 $srchadd ORDER BY mr.uid LIMIT ".(($page - 1) * $ppp).",$ppp");
	$i = 0;
	while($myrepeat = $db->fetch_array($query)) {
		$myrepeat['lastswitch'] = $myrepeat['lastswitch'] ? dgmdate("$dateformat $timeformat", $myrepeat['lastswitch'] + $timeoffset * 3600) : '';
		$myrepeat['usernameenc'] = rawurlencode($myrepeat['username']);
		$opstr = !$myrepeat['locked'] ? $Plang['normal'] : $Plang['lock'];
		$i++;
		echo '<tr><td><a href="'.$BASESCRIPT.'?action=plugins&operation=config&identifier=myrepeats&mod=admincp&srchuid='.$myrepeat['uid'].'">'.$myrepeat['user'].'</a></td>'.
			'<td>'.$_DCACHE['usergroups'][$myrepeat['groupid']]['grouptitle'].'</td>'.
			'<td><a href="'.$BASESCRIPT.'?action=plugins&operation=config&identifier=myrepeats&mod=admincp&srchrepeat='.rawurlencode($myrepeat['username']).'" title="'.htmlspecialchars($myrepeat['comment']).'">'.$myrepeat['username'].'</a>'.'</td>'.
			'<td>'.($myrepeat['lastswitch'] ? $myrepeat['lastswitch'] : '').'</td>'.
			'<td><a id="d'.$i.'" onclick="ajaxget(this.href, this.id, \'\');return false" href="'.$BASESCRIPT.'?action=plugins&operation=config&identifier=myrepeats&mod=admincp&uid='.$myrepeat['uid'].'&username='.$myrepeat['usernameenc'].'&op=lock">'.$opstr.'</a></td>'.
			'<td><a id="p'.$i.'" onclick="ajaxget(this.href, this.id, \'\');return false" href="'.$BASESCRIPT.'?action=plugins&operation=config&identifier=myrepeats&mod=admincp&uid='.$myrepeat['uid'].'&username='.$myrepeat['usernameenc'].'&op=delete">['.$lang['delete'].']</a></td></tr>';
	}
}
showtablefooter();

echo multi($count, $ppp, $page, "$BASESCRIPT?action=plugins&operation=config&identifier=myrepeats&mod=admincp$extra");

?>
