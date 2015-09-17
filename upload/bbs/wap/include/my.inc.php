<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: my.inc.php 19081 2009-08-12 09:26:57Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if(empty($discuz_uid)) {
	wapmsg('not_loggedin');
}

$uid = !empty($uid) ? intval($uid) : $discuz_uid;
$username = !empty($username) ? dhtmlspecialchars($username) : '';
$usernameadd = $uid ? "m.uid='$uid'" : "m.username='$username'";

if(empty($do)) {

	$member = $sdb->fetch_first("SELECT m.*, mf.* FROM {$tablepre}members m
		LEFT JOIN {$tablepre}memberfields mf ON mf.uid=m.uid
		WHERE $usernameadd LIMIT 1");

	if(!$member) {
		wapmsg('my_nonexistence');
	}

	if($member['gender'] == '1') {
		$member['gender'] = $lang['my_male'];
	} elseif($member['gender'] == '2') {
		$member['gender'] = $lang['my_female'];
	} else {
		$member['gender'] = $lang['my_secrecy'];
	}

	echo "<p>$lang[my]<br /><br />".
		"$lang[my_uid] $member[uid]<br />".
		"$lang[my_username] $member[username]<br />".
		"$lang[my_gender] $member[gender]<br />".
		($member['bday'] != '0000-00-00' ? "$lang[my_bday] $member[bday]<br />" : '').
		($member['location'] ? "$lang[my_location] $member[location]<br />" : '').
		($member['bio'] ? "$lang[my_bio] $member[bio]<br /><br />" : '');

	if($uid == $discuz_uid) {
		echo 	"<a href=\"index.php?action=myphone\">$lang[my_phone]</a><br />".
			"<a href=\"index.php?action=my&amp;do=fav\">$lang[my_favorites]</a><br />";
	}
	echo '</p>';

} else {

	if($do == 'fav') {

		if(!empty($favid)) {
			$selectid = $type == 'thread' ? 'tid' : 'fid';
			if($db->result_first("SELECT $selectid FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND $selectid='$favid' LIMIT 1")) {
				wapmsg('fav_existence');
			} else {
				$db->query("INSERT INTO {$tablepre}favorites (uid, $selectid)
					VALUES ('$discuz_uid', '$favid')");
				wapmsg('fav_add_succeed');
			}
		} else {
			echo "<p>$lang[my_threads]<br />";
			$query = $sdb->query("SELECT t.subject FROM {$tablepre}threads t
					WHERE t.authorid = '$discuz_uid' ORDER BY t.dateline DESC LIMIT 0, 3");
			while($mythread = $sdb->fetch_array($query)) {
				echo "<a href=\"index.php?action=thread&amp;tid=$mythread[tid]\">".cutstr($mythread['subject'], 15)."</a><br />";
			}

			echo "<br />$lang[my_replies]<br />";
			$query = $sdb->query("SELECT DISTINCT t.tid, t.subject FROM {$tablepre}posts p
					INNER JOIN {$tablepre}threads t ON t.tid=p.tid
					WHERE p.authorid = '$discuz_uid' ORDER BY p.dateline DESC LIMIT 0, 3");
			while($mypost = $sdb->fetch_array($query)) {
				echo "<a href=\"index.php?action=thread&amp;tid=$mypost[tid]\">".cutstr($mypost['subject'], 15)."</a><br />";
			}

			echo "<br />$lang[my_fav_thread]<br />";
			$query = $sdb->query("SELECT t.tid, t.subject FROM {$tablepre}favorites fav, {$tablepre}threads t
					WHERE fav.tid=t.tid AND t.displayorder>='0' AND fav.uid='$discuz_uid' ORDER BY t.lastpost DESC LIMIT 0, 3");
			while($favthread = $sdb->fetch_array($query)) {
				echo "<a href=\"index.php?action=thread&amp;tid=$favthread[tid]\">".cutstr($favthread['subject'], 24)."</a><br />";
			}

			echo "<br />$lang[my_fav_forum]<br />";
			$query = $sdb->query("SELECT f.fid, f.name FROM {$tablepre}favorites fav, {$tablepre}forums f WHERE fav.uid='$discuz_uid' AND fav.fid=f.fid ORDER BY f.displayorder DESC LIMIT 0, 3");
			while($favforum = $sdb->fetch_array($query)) {
				echo "<a href=\"index.php?action=forum&amp;fid=$favforum[fid]\">$favforum[name]</a><br />";
			}
			echo '</p>';
		}
	}
}

?>