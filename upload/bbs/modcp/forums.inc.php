<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: forums.inc.php 20596 2009-10-10 06:52:20Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

$forumupdate = $listupdate = false;

$op = !in_array($op , array('editforum', 'recommend')) ? 'editforum' : $op;

if(empty($fid)) {
	if(!empty($_DCOOKIE['modcpfid'])) {
		$fid = $_DCOOKIE['modcpfid'];
	} else {
		list($fid) = array_keys($modforums['list']);
	}
	dheader("Location: {$cpscript}?action=$action&op=$op&fid=$fid");
}

if($fid && $forum['ismoderator']) {

	if($op == 'editforum') {

		require_once DISCUZ_ROOT.'./include/editor.func.php';

		$alloweditrules = $adminid == 1 || $forum['alloweditrules'] ? true : false;

		if(!submitcheck('editsubmit')) {

			$forum['description'] = html2bbcode($forum['description']);
			$forum['rules'] = html2bbcode($forum['rules']);

		} else {

			require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
			$forumupdate = true;
			$descnew = addslashes(preg_replace('/on(mousewheel|mouseover|click|load|onload|submit|focus|blur)="[^"]*"/i', '', discuzcode(stripslashes($descnew), 1, 0, 0, 0, 1, 1, 0, 0, 1)));
			$rulesnew = $alloweditrules ? addslashes(preg_replace('/on(mousewheel|mouseover|click|load|onload|submit|focus|blur)="[^"]*"/i', '', discuzcode(stripslashes($rulesnew), 1, 0, 0, 0, 1, 1, 0, 0, 1))) : addslashes($forum['rules']);
			$db->query("UPDATE {$tablepre}forumfields SET description='$descnew', rules='$rulesnew' WHERE fid='$fid'");

			$forum['description'] = html2bbcode(stripslashes($descnew));
			$forum['rules'] = html2bbcode(stripslashes($rulesnew));

		}

	} elseif($op == 'recommend') {

		if($adminid == 3) {
			$useradd = "AND moderatorid IN ('$discuz_uid', 0)";
		}
		$ordernew = !empty($ordernew) && is_array($ordernew) ? $ordernew : array();

		if(submitcheck('editsubmit') && $forum['modrecommend']['sort'] != 1) {
			$threads = array();
			foreach($order as $id => $position) {
				$threads[$id]['order'] = $position;
			}
			foreach($subject as $id => $title) {
				$threads[$id]['subject'] = $title;
			}
			foreach($expirationrecommend as $id => $expiration) {
				$expiration = trim($expiration);
				if(!empty($expiration)) {
					if(!preg_match('/^\d{4}-\d{1,2}-\d{1,2} +\d{1,2}:\d{1,2}$/', $expiration)) {
						showmessage('recommend_expiration_invalid');
					}
					list($expiration_date, $expiration_time) = explode(' ', $expiration);
					list($expiration_year, $expiration_month, $expiration_day) = explode('-', $expiration_date);
					list($expiration_hour, $expiration_min) = explode(':', $expiration_time);
					$expiration_sec = 0;

					$expiration_timestamp = mktime($expiration_hour, $expiration_min, $expiration_sec, $expiration_month, $expiration_day, $expiration_year);
				} else {
					$expiration_timestamp = 0;
				}
				$threads[$id]['expiration'] = $expiration_timestamp;
			}
			if($ids = implodeids($delete)) {
				$listupdate = true;
				$db->query("DELETE FROM {$tablepre}forumrecommend WHERE fid='$fid' AND tid IN($ids)");
			}
			if(is_array($delete)) {
				foreach($delete as $id) {
					$threads[$id]['delete'] = true;
					unset($threads[$id]);
				}
			}
			foreach($threads as $id => $item) {
				$item['displayorder'] = intval($item['order']);
				$item['subject'] = dhtmlspecialchars($item['subject']);
				$db->query("UPDATE {$tablepre}forumrecommend SET subject='$item[subject]', displayorder='$item[displayorder]', moderatorid='$discuz_uid', expiration='$item[expiration]' WHERE tid='$id'");
			}
			$listupdate = true;
		}

		switch($show) {
			case 'forumdisplay':
				$useradd .= "AND position='1'";
				break;
			case 'viewthread':
				$useradd .= "AND position='2'";
				break;
			case 'unlimited':
				$useradd .= "AND position='0'";
				break;
		}

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$threadcount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}forumrecommend WHERE fid='$fid' $useradd");
		$multipage = multi($threadcount, $tpp, $page, "$cpscript?action=$action&fid=$fid&page=$page");

		$threadlist = array();
		$query = $db->query("SELECT f.*, m.username as moderator
				FROM {$tablepre}forumrecommend f
				LEFT JOIN {$tablepre}members m ON f.moderatorid=m.uid
				WHERE f.fid='$fid' $useradd LIMIT $start_limit,$tpp");
		while($thread = $db->fetch_array($query)) {
			$thread['authorlink'] = $thread['authorid'] ? "<a href=\"space.php?uid=$thread[authorid]\" target=\"_blank\">$thread[author]</a>" : 'Guest';
			$thread['moderatorlink'] = $thread['moderator'] ? "<a href=\"space.php?uid=$thread[moderatorid]\" target=\"_blank\">$thread[moderator]</a>" : 'System';
			$thread['expiration'] = $thread['expiration'] ? gmdate("$dateformat $timeformat", $thread['expiration'] + ($timeoffset * 3600)) : '';
			$threadlist[] = $thread;
		}

	}
}

?>