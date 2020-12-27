<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: post.php 21337 2010-01-06 08:09:58Z tiger $
*/

define('CURSCRIPT', 'post');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/post.func.php';

$_DTYPE = $checkoption = $optionlist = array();
if($sortid) {
	threadsort_checkoption();
}

if(empty($action)) {

	showmessage('undefined_action', NULL, 'HALTED');

}elseif($action == 'threadsorts') {
	threadsort_optiondata();
	$template = intval($operate) ? 'search_sortoption' : 'post_sortoption';
	include template($template);
	exit;

} elseif(($forum['simple'] & 1) || $forum['redirect']) {
	showmessage('forum_disablepost');
}

require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

if($action == 'reply') {
	$addfeedcheck = $customaddfeed & 4 ? 'checked="checked"': '';
} elseif(!empty($special) && $action != 'reply') {
	$addfeedcheck = $customaddfeed & 2 ? 'checked="checked"': '';
} else {
	$addfeedcheck = $customaddfeed & 1 ? 'checked="checked"': '';
}


$navigation = $navtitle = $thread = '';
if(!empty($cedit)) {
	unset($inajax, $infloat, $ajaxtarget, $handlekey);
}

if($action == 'edit' || $action == 'reply') {

	if($thread = $db->fetch_first("SELECT * FROM {$tablepre}threads WHERE tid='$tid'".($auditstatuson ? '' : " AND displayorder>='0'"))) {

		$navigation = "&raquo; <a href=\"viewthread.php?tid=$tid\">$thread[subject]</a>";
		$navtitle = $thread['subject'].' - ';
		if($thread['readperm'] && $thread['readperm'] > $readaccess && !$forum['ismoderator'] && $thread['authorid'] != $discuz_uid) {
			showmessage('thread_nopermission', NULL, 'NOPERM');
		}

		$fid = $thread['fid'];
		$special = $thread['special'];

	} else {
		showmessage('thread_nonexistence');
	}

	if($action == 'reply' && ($thread['closed'] == 1) && !$forum['ismoderator']) {
		showmessage('post_thread_closed');
	}

}

$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fid".($extra ? '&'.preg_replace("/^(&)*/", '', $extra) : '')."\">$forum[name]</a> $navigation";
$navtitle = $navtitle.strip_tags($forum['name']).' - ';

if($forum['type'] == 'sub') {
	$fup = $db->fetch_first("SELECT name, fid FROM {$tablepre}forums WHERE fid='$forum[fup]'");
	$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fup[fid]\">$fup[name]</a> $navigation";
	$navtitle = $navtitle.strip_tags($fup['name']).' - ';
}

periodscheck('postbanperiods');

if($forum['password'] && $forum['password'] != $_DCOOKIE['fidpw'.$fid]) {
	showmessage('forum_passwd', "forumdisplay.php?fid=$fid");
}

if(empty($forum['allowview'])) {
	if(!$forum['viewperm'] && !$readaccess) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	} elseif($forum['viewperm'] && !forumperm($forum['viewperm'])) {
		showmessagenoperm('viewperm', $fid);
	}
} elseif($forum['allowview'] == -1) {
	showmessage('forum_access_view_disallow');
}

if($adminid != 1) {
	formulaperm($forum['formulaperm']);
}

if(!$adminid && $newbiespan && (!$lastpost || $timestamp - $lastpost < $newbiespan * 3600)) {
	if($timestamp - ($db->result_first("SELECT regdate FROM {$tablepre}members WHERE uid='$discuz_uid'")) < $newbiespan * 3600) {
		showmessage('post_newbie_span');
	}
}

$special = $special > 0 && $special < 7 || $special == 127 ? intval($special) : 0;

$allowpostattach = $forum['allowpostattach'] != -1 && ($forum['allowpostattach'] == 1 || (!$forum['postattachperm'] && $allowpostattach) || ($forum['postattachperm'] && forumperm($forum['postattachperm'])));
$attachextensions = $forum['attachextensions'] ? $forum['attachextensions'] : $attachextensions;
if($attachextensions) {
	$imgexts = explode(',', str_replace(' ', '', $attachextensions));
	$imgexts = array_intersect(array('jpg','jpeg','gif','png','bmp'), $imgexts);
	$imgexts = implode(', ', $imgexts);
} else {
	$imgexts = 'jpg, jpeg, gif, png, bmp';
}
$allowuploadnum = TRUE;
if($allowpostattach) {
	if($maxattachnum) {
		$allowuploadnum = $maxattachnum - $db->result_first("SELECT count(*) FROM {$tablepre}attachments WHERE uid='$discuz_uid' AND dateline>'$timestamp'-86400");
		$allowuploadnum = $allowuploadnum < 0 ? 0 : $allowuploadnum;
	}
	if($maxsizeperday) {
		$allowuploadsize = $maxsizeperday - intval($db->result_first("SELECT SUM(filesize) FROM {$tablepre}attachments WHERE uid='$discuz_uid' AND dateline>'$timestamp'-86400"));
		$allowuploadsize = $allowuploadsize < 0 ? 0 : $allowuploadsize;
		$allowuploadsize = $allowuploadsize / 1048576 >= 1 ? round(($allowuploadsize / 1048576), 1).'MB' : round(($allowuploadsize / 1024)).'KB';
	}
}

$allowpostimg = $allowpostattach && $imgexts;
$enctype = $allowpostattach ? 'enctype="multipart/form-data"' : '';
$maxattachsize_mb = $maxattachsize / 1048576 >= 1 ? round(($maxattachsize / 1048576), 1).'MB' : round(($maxattachsize / 1024)).'KB';

$postcredits = $forum['postcredits'] ? $forum['postcredits'] : $creditspolicy['post'];
$replycredits = $forum['replycredits'] ? $forum['replycredits'] : $creditspolicy['reply'];
$digestcredits = $forum['digestcredits'] ? $forum['digestcredits'] : $creditspolicy['digest'];
$postattachcredits = $forum['postattachcredits'] ? $forum['postattachcredits'] : $creditspolicy['postattach'];

$maxprice = isset($extcredits[$creditstrans]) ? $maxprice : 0;

$extra = rawurlencode($extra);
$notifycheck = empty($emailnotify) ? '' : 'checked="checked"';
$stickcheck = empty($sticktopic) ? '' : 'checked="checked"';
$digestcheck = empty($addtodigest) ? '' : 'checked="checked"';

$subject = isset($subject) ? dhtmlspecialchars(censor(trim($subject))) : '';
$subject = !empty($subject) ? str_replace("\t", ' ', $subject) : $subject;
$message = isset($message) ? censor(trim($message)) : '';
$polloptions = isset($polloptions) ? censor(trim($polloptions)) : '';
$readperm = isset($readperm) ? intval($readperm) : 0;
$price = isset($price) ? intval($price) : 0;
$tagstatus = $tagstatus && $forum['allowtag'] ? ($tagstatus == 2 ? 2 : $forum['allowtag']) : 0;

if(empty($bbcodeoff) && !$allowhidecode && !empty($message) && preg_match("/\[hide=?\d*\].+?\[\/hide\]/is", preg_replace("/(\[code\](.+?)\[\/code\])/is", ' ', $message))) {
	showmessage('post_hide_nopermission');
}

if(periodscheck('postmodperiods', 0)) {
	$modnewthreads = $modnewreplies = 1;
} else {
	$censormod = censormod($subject."\t".$message);
	$modnewthreads = (!$allowdirectpost || $allowdirectpost == 1) && $forum['modnewposts'] || $censormod ? 1 : 0;
	$modnewreplies = (!$allowdirectpost || $allowdirectpost == 2) && $forum['modnewposts'] == 2 || $censormod ? 1 : 0;
}

if($allowposturl < 3 && $message) {
	$urllist = get_url_list($message);
	if(is_array($urllist[1])) foreach($urllist[1] as $key => $val) {
		if(!$val = trim($val)) continue;
		if(!iswhitelist($val)) {
			if($allowposturl == 0) {
				showmessage('post_url_nopermission');
			} elseif($allowposturl == 1) {
				$modnewthreads = $modnewreplies = 1;
				break;
			} elseif($allowposturl == 2) {
				$message = str_replace('[url]'.$urllist[0][$key].'[/url]', $urllist[0][$key], $message);
				$message = preg_replace("@\[url={$urllist[0][$key]}\](.*?)\[/url\]@i", '\\1', $message);
			}
		}
	}
}

$urloffcheck = $usesigcheck = $smileyoffcheck = $codeoffcheck = $htmloncheck = $emailcheck = '';
if($discuz_uid) {
	if($db->result_first("SELECT COUNT(*) FROM {$tablepre}favoritethreads WHERE tid='$tid' AND uid='$discuz_uid'")) {
		$has_attention = true;
	}
}

$seccodecheck = ($seccodestatus & 4) && (!$seccodedata['minposts'] || $posts < $seccodedata['minposts']);
$secqaacheck = $secqaa['status'][2] && (!$secqaa['minposts'] || $posts < $secqaa['minposts']);

$allowpostpoll = $allowpost && $allowpostpoll && ($forum['allowpostspecial'] & 1);
$allowposttrade = $allowpost && $allowposttrade && ($forum['allowpostspecial'] & 2);
$allowpostreward = $allowpost && $allowpostreward && ($forum['allowpostspecial'] & 4) && isset($extcredits[$creditstrans]);
$allowpostactivity = $allowpost && $allowpostactivity && ($forum['allowpostspecial'] & 8);
$allowpostdebate = $allowpost && $allowpostdebate && ($forum['allowpostspecial'] & 16);
$usesigcheck = $discuz_uid && $sigstatus ? 'checked="checked"' : '';
$ordertypecheck = getstatus($thread['status'], 4) ? 'checked="checked"' : '';

if($specialextra && $allowpost && $threadplugins && (!array_key_exists($specialextra, $threadplugins) || !@in_array($specialextra, unserialize($forum['threadplugin'])) || !@in_array($specialextra, $allowthreadplugin))) {
	$specialextra = '';
}

$allowanonymous = $forum['allowanonymous'] || $allowanonymous ? 1 : 0;

if($action == 'newthread' && $forum['allowspecialonly'] && !$special) {
	if($allowpostpoll) {
		$special = 1;
	} elseif($allowposttrade) {
		$special = 2;
	} elseif($allowpostreward) {
		$special = 3;
	} elseif($allowpostactivity) {
		$special = 4;
	} elseif($allowpostdebate) {
		$special = 5;
	} elseif($allowpost && $threadplugins && $allowthreadplugin && ($forum['threadplugin'] = unserialize($forum['threadplugin']))) {
		$threadpluginary = array_intersect($allowthreadplugin, $forum['threadplugin']);
		$specialextra = $threadpluginary[0] ? $threadpluginary[0] : '';
	}

	if(!$special && !$specialextra) {
		showmessage('undefined_action', NULL, 'HALTED');
	}
}

$editorid = 'e';
$editoroptions = str_pad(decbin($editoroptions), 2, 0, STR_PAD_LEFT);
$editormode = $editormode == 2 ? $editoroptions{0} : $editormode;
$allowswitcheditor = $editoroptions{1};
if($specialextra) {
	$special = 127;
	if(@in_array($specialextra, $pluginlangs)) {
		@include_once DISCUZ_ROOT.'./forumdata/cache/cache_scriptlang.php';
	}
}

if($action == 'newthread') {
	$policykey = 'post';
} elseif($action == 'reply') {
	$policykey = 'reply';
} else {
	$policykey = '';
}
if($policykey) {
	$postcredits = $forum[$policykey.'credits'] ? $forum[$policykey.'credits'] : $creditspolicy[$policykey];
}

$posturl = "action=$action&fid=$fid".
	(!empty($tid) ? "&tid=$tid" : '').
	(!empty($pid) ? "&pid=$pid" : '').
	(!empty($special) ? "&special=$special" : '').
	(!empty($sortid) ? "&sortid=$sortid" : '').
	(!empty($typeid) ? "&sortid=$typeid" : '').
	(!empty($firstpid) ? "&firstpid=$firstpid" : '').
	(!empty($addtrade) ? "&addtrade=$addtrade" : '');

if($action == 'newthread') {
	($forum['allowpost'] == -1) && showmessage('forum_access_disallow');
	require_once DISCUZ_ROOT.'./include/newthread.inc.php';
} elseif($action == 'reply') {
	($forum['allowreply'] == -1) && showmessage('forum_access_disallow');
	require_once DISCUZ_ROOT.'./include/newreply.inc.php';
} elseif($action == 'edit') {
	($forum['allowpost'] == -1) && showmessage('forum_access_disallow');
	require_once DISCUZ_ROOT.'./include/editpost.inc.php';
} elseif($action == 'newtrade') {
	($forum['allowpost'] == -1) && showmessage('forum_access_disallow');
	require_once DISCUZ_ROOT.'./include/newtrade.inc.php';
}

?>