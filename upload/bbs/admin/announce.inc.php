<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: announce.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();
echo '<script type="text/javascript" src="include/js/calendar.js"></script>';

if(empty($operation)) {

	if(!submitcheck('announcesubmit')) {

		shownav('tools', 'announce', 'admin');
		showsubmenu('announce', array(
			array('admin', 'announce', 1),
			array('add', 'announce&operation=add', 0)
		));
		showtips('announce_tips');
		showformheader('announce');
		showtableheader();
		showsubtitle(array('', 'display_order', 'author', 'subject', 'message', 'announce_type', 'start_time', 'end_time', ''));

		$announce_type = array(0=>$lang['announce_words'], 1=>$lang['announce_url']);
		$query = $db->query("SELECT * FROM {$tablepre}announcements ORDER BY displayorder, starttime DESC, id DESC");
		while($announce = $db->fetch_array($query)) {
			$disabled = $adminid != 1 && $announce['author'] != $discuz_userss ? 'disabled' : NULL;
			$announce['starttime'] = $announce['starttime'] ? gmdate($dateformat, $announce['starttime'] + $_DCACHE['settings']['timeoffset'] * 3600) : $lang['unlimited'];
			$announce['endtime'] = $announce['endtime'] ? gmdate($dateformat, $announce['endtime'] + $_DCACHE['settings']['timeoffset'] * 3600) : $lang['unlimited'];
			showtablerow('', array('class="td25"', 'class="td28"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$announce[id]\" $disabled>",
				"<input type=\"text\" class=\"txt\" name=\"displayordernew[{$announce[id]}]\" value=\"$announce[displayorder]\" size=\"2\" $disabled>",
				"<a href=\"./space.php?username=".rawurlencode($announce['author'])."\" target=\"_blank\">$announce[author]</a>",
				dhtmlspecialchars($announce['subject']),
				cutstr(strip_tags($announce['message']), 20),
				$announce_type[$announce['type']],
				$announce['starttime'],
				$announce['endtime'],
				"<a href=\"$BASESCRIPT?action=announce&operation=edit&announceid=$announce[id]\" $disabled>$lang[edit]</a>"
			));
		}
		showsubmit('announcesubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if(is_array($delete)) {
			$ids = $comma = '';
			foreach($delete as $id) {
				$ids .= "$comma'$id'";
				$comma = ',';
			}
			$db->query("DELETE FROM {$tablepre}announcements WHERE id IN ($ids) AND ('$adminid'='1' OR author='$discuz_user')");
		}

		if(is_array($displayordernew)) {
			foreach($displayordernew as $id => $displayorder) {
				$db->query("UPDATE {$tablepre}announcements SET displayorder='$displayorder' WHERE id='$id' AND ('$adminid'='1' OR author='$discuz_user')");
			}
		}

		updatecache(array('pmlist', 'announcements', 'announcements_forum'));
		cpmsg('announce_update_succeed', $BASESCRIPT.'?action=announce', 'succeed');

	}

} elseif($operation == 'add') {

	if(!submitcheck('addsubmit')) {

		$newstarttime = gmdate('Y-n-j', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);
		$newendtime = gmdate('Y-n-j', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600 + 86400* 7);

		shownav('tools', 'announce', 'add');
		showsubmenu('announce', array(
			array('admin', 'announce', 0),
			array('add', 'announce&operation=add', 1)
		));
		showformheader('announce&operation=add');
		showtableheader('announce_add');
		showsetting($lang[subject], 'newsubject', '', 'text');
		showsetting($lang['start_time'], 'newstarttime', $newstarttime, 'calendar');
		showsetting($lang['end_time'], 'newendtime', $newendtime, 'calendar');
		showsetting('announce_type', array('newtype', array(
			array(0, $lang['announce_words']),
			array(1, $lang['announce_url']))), 0, 'mradio');
		showsetting('announce_message', 'newmessage', '', 'textarea');
		showsubmit('addsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$newstarttime = $newstarttime ? strtotime($newstarttime) : 0;
		$newendtime = $newendtime ? strtotime($newendtime) : 0;

		if(!$newstarttime) {
			cpmsg('announce_time_invalid', '', 'error');
		} elseif(!($newsubject = trim($newsubject)) || !($newmessage = trim($newmessage))) {
			cpmsg('announce_invalid', '', 'error');
		} else {
			$newmessage = $newtype == 1 ? explode("\n", $newmessage) : array(0 => $newmessage);
			$db->query("INSERT INTO {$tablepre}announcements (author, subject, type, starttime, endtime, message)
				VALUES ('$discuz_user', '$newsubject', '$newtype', '$newstarttime', '$newendtime', '{$newmessage[0]}')");
			updatecache(array('announcements', 'announcements_forum', 'pmlist'));
			cpmsg('announce_succeed', $BASESCRIPT.'?action=announce', 'succeed');
		}

	}

} elseif($operation == 'edit' && $announceid) {

	$announce = $db->fetch_first("SELECT * FROM {$tablepre}announcements WHERE id='$announceid' AND ('$adminid'='1' OR author='$discuz_user')");
	if(!$announce) {
		cpmsg('announce_nonexistence', '', 'error');
	}

	if(!submitcheck('editsubmit')) {

		$announce['starttime'] = $announce['starttime'] ? gmdate('Y-n-j', $announce['starttime'] + $_DCACHE['settings']['timeoffset'] * 3600) : "";
		$announce['endtime'] = $announce['endtime'] ? gmdate('Y-n-j', $announce['endtime'] + $_DCACHE['settings']['timeoffset'] * 3600) : "";

		shownav('tools', 'announce');
		showsubmenu('announce', array(
			array('admin', 'announce', 0),
			array('add', 'announce&operation=add', 0)
		));
		showformheader("announce&operation=edit&announceid=$announceid");
		showtableheader();
		showtitle('announce_edit');
		showsetting('subject', 'subjectnew', $announce['subject'], 'text');
		showsetting('start_time', 'starttimenew', $announce['starttime'], 'calendar');
		showsetting('end_time', 'endtimenew', $announce['endtime'], 'calendar');
		showsetting('announce_type', array('typenew', array(
			array(0, $lang['announce_words']),
			array(1, $lang['announce_url'])
		)), $announce['type'], 'mradio');
		showsetting('announce_message', 'messagenew', $announce['message'], 'textarea');
		showsubmit('editsubmit');
		showtablefooter();
		showformfooter();

	} else {

		if(strpos($starttimenew, '-')) {
			$time = explode('-', $starttimenew);
			$starttimenew = gmmktime(0, 0, 0, $time[1], $time[2], $time[0]) - $_DCACHE['settings']['timeoffset'] * 3600;
		} else {
			$starttimenew = 0;
		}
		if(strpos($endtimenew, '-')) {
			$time = explode('-', $endtimenew);
			$endtimenew = gmmktime(0, 0, 0, $time[1], $time[2], $time[0]) - $_DCACHE['settings']['timeoffset'] * 3600;
		} else {
			$endtimenew = 0;
		}

		if(!$starttimenew || ($endtimenew && $endtimenew <= $timestamp)) {
			cpmsg('announce_time_invalid', '', 'error');
		} elseif(!($subjectnew = trim($subjectnew)) || !($messagenew = trim($messagenew))) {
			cpmsg('announce_invalid', '', 'error');
		} else {
			$messagenew = $typenew == 1 ? explode("\n", $messagenew) : array(0 => $messagenew);
			$db->query("UPDATE {$tablepre}announcements SET subject='$subjectnew', type='$typenew', starttime='$starttimenew', endtime='$endtimenew', message='{$messagenew[0]}' WHERE id='$announceid' AND ('$adminid'='1' OR author='$discuz_user')");
			updatecache('announcements', 'announcements_forum', 'pmlist');
			cpmsg('announce_succeed', $BASESCRIPT.'?action=announce', 'succeed');
		}
	}

}

?>