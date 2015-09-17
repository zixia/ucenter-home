<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: redirect.php 21051 2009-11-09 08:13:08Z monkey $
*/

define('CURSCRIPT', 'viewthread');

require_once './include/common.inc.php';

if(empty($goto) && !empty($ptid)) {
	$ptid = intval($ptid);
	$postno = intval($postno);
	if($ordertype != 1) {
		$postno = $postno > 0 ? $postno - 1 : 0;
		$pid = $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$ptid' AND invisible='0' ORDER BY dateline LIMIT $postno, 1");
	} else {
		$postno = $postno > 1 ? $postno - 1 : 0;
		if($postno) {
			$pid = $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$ptid' AND invisible='0' ORDER BY dateline LIMIT $postno, 1");
		} else {
			$pid = $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$ptid' AND first='1' LIMIT 1");
		}
	}
	$goto = 'findpost';
}

if($goto == 'findpost') {
	$pid = intval($pid);
	$ptid = intval($ptid);
	if($post = $db->fetch_first("SELECT p.tid, p.dateline, t.status, t.special, t.replies FROM {$tablepre}posts p LEFT JOIN {$tablepre}threads t USING(tid) WHERE p.pid='$pid'")) {
		$ordertype = !isset($_GET['ordertype']) && getstatus($post['status'], 4) ? 1 : intval($ordertype);
		$sqladd = $post['special'] ? "AND first=0" : '';
		$curpostnum = $db->result_first("SELECT count(*) FROM {$tablepre}posts WHERE tid='$post[tid]' AND dateline<='$post[dateline]' $sqladd");
		if($ordertype != 1) {
			$page = ceil($curpostnum / $ppp);
		} else {
			if($curpostnum > 1) {
				$page = ceil(($post['replies'] - $curpostnum + 3) / $ppp);
			} else {
				$page = 1;
			}
		}
		if(!empty($special) && $special == 'trade') {
			dheader("Location: viewthread.php?do=tradeinfo&tid=$post[tid]&pid=$pid");
		} else {
			$extra = '';
			if($discuz_uid && empty($postno)) {
				if($db->result_first("SELECT count(*) FROM {$tablepre}favoritethreads WHERE tid='$post[tid]' AND uid='$discuz_uid'")) {
					$db->query("UPDATE {$tablepre}favoritethreads SET newreplies=0 WHERE tid='$post[tid]' AND uid='$discuz_uid'", 'UNBUFFERED');
					$db->query("DELETE FROM {$tablepre}promptmsgs WHERE uid='$discuz_uid' AND typeid='".$prompts['threads']['id']."' AND extraid='$post[tid]'", 'UNBUFFERED');
					$extra = '&fav=yes';
				}
			}
			dheader("Location: viewthread.php?tid=$post[tid]&rpid=$pid$extra&ordertype=$ordertype&page=$page".(isset($_GET['modthreadkey']) && ($modthreadkey=modthreadkey($post['tid'])) ? "&modthreadkey=$modthreadkey": '')."#pid$pid");
		}
	} else {
		$ptid = !empty($ptid) ? intval($ptid) : 0;
		if($ptid) {
			dheader("location: viewthread.php?tid=$ptid");
		}
		showmessage('post_check', NULL, 'HALTED');
	}
}

$tid = $forum['closed'] < 2 ? $tid : $forum['closed'];

if(empty($tid)) {
	showmessage('thread_nonexistence');
}

if(isset($fid) && empty($forum)) {
	showmessage('forum_nonexistence', NULL, 'HALTED');
}

@include DISCUZ_ROOT.'./forumdata/cache/cache_viewthread.php';

if($goto == 'lastpost') {

	if($tid) {
		$query = $db->query("SELECT tid, replies, special, status FROM {$tablepre}threads WHERE tid='$tid' AND displayorder>='0'");
	} else {
		$query = $db->query("SELECT tid, replies, special, status FROM {$tablepre}threads WHERE fid='$fid' AND displayorder>='0' ORDER BY lastpost DESC LIMIT 1");
	}
	if(!$thread = $db->fetch_array($query)) {
		showmessage('thread_nonexistence');
	}
	if(!getstatus($thread['status'], 4)) {
		$page = ceil(($thread['special'] ? $thread['replies'] : $thread['replies'] + 1) / $ppp);
	} else {
		$page = 1;
	}

	$tid = $thread['tid'];

	require_once DISCUZ_ROOT.'./viewthread.php';
	exit();

} elseif($goto == 'nextnewset') {

	if($fid && $tid) {
		$this_lastpost = $db->result_first("SELECT lastpost FROM {$tablepre}threads WHERE tid='$tid' AND displayorder>='0'");
		if($next = $db->fetch_first("SELECT tid FROM {$tablepre}threads WHERE fid='$fid' AND displayorder>='0' AND lastpost>'$this_lastpost' ORDER BY lastpost ASC LIMIT 1")) {
			$tid = $next['tid'];
			require_once DISCUZ_ROOT.'./viewthread.php';
			exit();
		} else {
			showmessage('redirect_nextnewset_nonexistence');
		}
	} else {
		showmessage('undefined_action', NULL, 'HALTED');
	}

} elseif($goto == 'nextoldset') {

	if($fid && $tid) {
		$this_lastpost = $db->result_first("SELECT lastpost FROM {$tablepre}threads WHERE tid='$tid' AND displayorder>='0'");
		if($last = $db->fetch_first("SELECT tid FROM {$tablepre}threads WHERE fid='$fid' AND displayorder>='0' AND lastpost<'$this_lastpost' ORDER BY lastpost DESC LIMIT 1")) {
			$tid = $last['tid'];
			require_once DISCUZ_ROOT.'./viewthread.php';
			exit();
		} else {
			showmessage('redirect_nextoldset_nonexistence');
		}
	} else {
		showmessage('undefined_action', NULL, 'HALTED');
	}

} else {
	showmessage('undefined_action', NULL, 'HALTED');
}

?>