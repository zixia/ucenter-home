<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: post.inc.php 19146 2009-08-14 02:29:31Z tiger $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
require_once DISCUZ_ROOT.'./include/post.func.php';
require_once DISCUZ_ROOT.'./include/forum.func.php';

if(empty($forum) || $forum['type'] == 'group') {
	wapmsg('forum_nonexistence');
}

if(empty($forum['allowview']) && ((!$forum['viewperm'] && !$readaccess) || ($forum['viewperm'] && !forumperm($forum['viewperm'])))) {
	wapmsg('forum_nopermission');
}

if(empty($bbcodeoff) && !$allowhidecode && preg_match("/\[hide=?\d*\].+?\[\/hide\]/is", preg_replace("/(\[code\].*\[\/code\])/is", '', $message))) {
	wapmsg('post_hide_nopermission');
}

formulaperm($forum['formulaperm']);

if(!$adminid && $newbiespan && (!$lastpost || $timestamp - $lastpost < $newbiespan * 3600)) {
	$regdate = $db->result_first("SELECT regdate FROM {$tablepre}members WHERE uid='$discuz_uid'");
	if($timestamp - $regdate < $newbiespan * 3600) {
		showmessage('post_newbie_span');
	}
}

$postcredits = $forum['postcredits'] ? $forum['postcredits'] : $creditspolicy['post'];
$replycredits = $forum['replycredits'] ? $forum['replycredits'] : $creditspolicy['reply'];

$modnewthreads = (!$allowdirectpost || $allowdirectpost == 1) && ($forum['modnewposts'] || !empty($censormod)) ? 1 : 0;
$modnewreplies = (!$allowdirectpost || $allowdirectpost == 2) && ($forum['modnewposts'] == 2 || !empty($censormod)) ? 1 : 0;

$subject = wapconvert($subject);
$subject = ($subject != '') ? dhtmlspecialchars(censor(trim($subject))) : '';

$message = wapconvert($message);
$message = ($message != '') ? censor(trim($message)) : '';

if($do == 'newthread') {

	$discuz_action = 195;

	if(!$discuz_uid && !((!$forum['postperm'] && $allowpost) || ($forum['postperm'] && forumperm($forum['postperm'])))) {
		wapmsg('post_newthread_nopermission');
	} elseif(empty($forum['allowpost'])) {
		if(!$forum['postperm'] && !$allowpost) {
			wapmsg('post_newthread_nopermission');
		} elseif($forum['postperm'] && !forumperm($forum['postperm'])) {
			wapmsg('post_newthread_nopermission');
		}
	} elseif($forum['allowpost'] == -1) {
		wapmsg('post_newthread_nopermission');
	}

	if(empty($subject) || empty($message)) {

		$typeselect = isset($forum['threadtypes']['required']) ? typeselect() : '';
		echo "<p>".($typeselect ? "$lang[type]$typeselect<br />\n" : '').
			"$lang[subject]<input type=\"text\" name=\"subject\" value=\"\" maxlength=\"80\" format=\"M*m\" /><br />\n".
			"$lang[message]<input type=\"text\" name=\"message\" value=\"\" format=\"M*m\" /><br />\n".
			"<anchor title=\"$lang[submit]\">$lang[submit]".
			"<go method=\"post\" href=\"index.php?action=post&amp;do=newthread&amp;fid=$fid&amp;sid=$sid\">\n".
			"<postfield name=\"subject\" value=\"$(subject)\" />\n".
			"<postfield name=\"message\" value=\"$(message)\" />\n".
			"<postfield name=\"formhash\" value=\"".formhash()."\" />\n".
			($typeselect ? "<postfield name=\"typeid\" value=\"$(typeid)\" />\n" : '').
			"</go></anchor>\n<br /><br />".
			"<a href=\"index.php?action=forum&amp;fid=$fid\">$lang[return_forum]</a></p>\n";

	} else {

		if($post_invalid = checkpost()) {
			wapmsg($post_invalid);
		}

		if($formhash != formhash()) {
			wapmsg('wap_submit_invalid');
		}

		if(checkflood()) {
			wapmsg('post_flood_ctrl');
		}

		$typeid = isset($forum['threadtypes']['types'][$typeid]) ? $typeid : 0;
		if(empty($typeid) && !empty($forum['threadtypes']['required'])) {
			wapmsg('post_type_isnull');
		}

		$displayorder = $pinvisible = $modnewthreads ? -2 : 0;
		$db->query("INSERT INTO {$tablepre}threads (fid, readperm, iconid, typeid, author, authorid, subject, dateline, lastpost, lastposter, displayorder, digest, special, attachment, moderated)
			VALUES ('$fid', '0', '0', '$typeid', '$discuz_user', '$discuz_uid', '$subject', '$timestamp', '$timestamp', '$discuz_user', '$displayorder', '0', '0', '0', '0')");
		$tid = $db->insert_id();

		$db->query("INSERT INTO {$tablepre}posts (fid, tid, first, author, authorid, subject, dateline, message, useip, invisible, usesig, htmlon, bbcodeoff, smileyoff, parseurloff, attachment)
			VALUES ('$fid', '$tid', '1', '$discuz_user', '$discuz_uid', '$subject', '$timestamp', '$message', '$onlineip', '$pinvisible', '0', '0', '0', '0', '0', '0')");
		$pid = $db->insert_id();

		if($modnewthreads) {
			wapmsg('post_mod_succeed', array('title' => 'post_mod_forward', 'link' => "index.php?action=forum&amp;tid=$fid"));
		} else {
			updatepostcredits('+', $discuz_uid, $postcredits);

			$lastpost = "$tid\t$subject\t$timestamp\t$discuz_user";
			$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost', threads=threads+1, posts=posts+1, todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
			if($forum['type'] == 'sub') {
				$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost' WHERE fid='$forum[fup]'", 'UNBUFFERED');
			}

			wapmsg('post_newthread_succeed', array('title' => 'post_newthread_forward', 'link' => "index.php?action=thread&amp;tid=$tid"));

		}
	}

} elseif($do == 'reply') {

	$discuz_action = 196;

	$thread = $db->fetch_first("SELECT * FROM {$tablepre}threads WHERE tid='$tid'");
	if(!$thread) {
		wapmsg('thread_nonexistence');
	}

	if(empty($forum['allowreply']) && ((!$forum['replyperm'] && !$allowreply) || ($forum['replyperm'] && !forumperm($forum['replyperm'])))) {
		wapmsg('post_newreply_nopermission');
	}

	if($thread['closed'] && !$forum['ismoderator']) {
		wapmsg('post_thread_closed');
	}
	if($post_autoclose = checkautoclose()) {
		wapmsg($post_autoclose);
	}

	if(empty($message)) {

		echo "<p>$lang[message]<input type=\"text\" name=\"message\" value=\"\" format=\"M*m\" /><br />\n".
			"<anchor title=\"$lang[submit]\">$lang[submit]".
			"<go method=\"post\" href=\"index.php?action=post&amp;do=reply&amp;fid=$fid&amp;tid=$tid&amp;sid=$sid\">\n".
			"<postfield name=\"subject\" value=\"$(subject)\" />\n".
			"<postfield name=\"message\" value=\"$(message)\" />\n".
			"<postfield name=\"formhash\" value=\"".formhash()."\" />\n".
			"</go></anchor><br /><br />\n".
			"<a href=\"index.php?action=thread&amp;tid=$tid\">$lang[return_thread]</a><br />\n".
			"<a href=\"index.php?action=forum&amp;fid=$fid\">$lang[return_forum]</a></p>\n";

	} else {

		if($message == '') {
			wapmsg('post_sm_isnull');
		}
		if($post_invalid = checkpost()) {
			wapmsg($post_invalid);
		}

		if($formhash != formhash()) {
			wapmsg('wap_submit_invalid');
		}

		if(checkflood()) {
			wapmsg('post_flood_ctrl');
		}

		$pinvisible = $modnewreplies ? -2 : 0;
		$db->query("INSERT INTO {$tablepre}posts (fid, tid, first, author, authorid, dateline, message, useip, invisible, usesig, htmlon, bbcodeoff, smileyoff, parseurloff, attachment)
				VALUES ('$fid', '$tid', '0', '$discuz_user', '$discuz_uid', '$timestamp', '$message', '$onlineip', '$pinvisible', '1', '0', '0', '0', '0', '0')");
		$pid = $db->insert_id();

		if($modnewreplies) {
			wapmsg('post_mod_succeed', array('title' => 'post_mod_forward', 'link' => "index.php?action=forum&amp;fid=$fid"));
		} else {
			$db->query("UPDATE {$tablepre}threads SET lastposter='$discuz_user', lastpost='$timestamp', replies=replies+1 WHERE tid='$tid' AND fid='$fid'", 'UNBUFFERED');

			updatepostcredits('+', $discuz_uid, $replycredits);

			$lastpost = "$thread[tid]\t".addslashes($thread['subject'])."\t$timestamp\t$discuz_user";
			$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost', posts=posts+1, todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
			if($forum['type'] == 'sub') {
				$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost' WHERE fid='$forum[fup]'", 'UNBUFFERED');
			}

			wapmsg('post_newreply_succeed', array('title' => 'post_newreply_forward', 'link' => "index.php?action=thread&amp;tid=$tid&amp;page=".(@ceil(($thread['replies'] + 2) / $wapppp))));
		}

	}

}



?>