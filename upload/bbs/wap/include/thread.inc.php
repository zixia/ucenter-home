<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: thread.inc.php 21035 2009-11-09 02:07:45Z zhaoxiongfei $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$discuz_action = 193;

$breaked = 0;
$threadposts = '';
$start = isset($start) ? intval($start) : 0;
$offset = isset($offset) ? intval($offset) : 0;
$do = !empty($do) ? $do : '';

$thread = $sdb->fetch_first("SELECT * FROM {$tablepre}threads WHERE tid='$tid' AND displayorder>='0'");
if(!$thread) {
	wapmsg('thread_nonexistence');
}

$hiddenreplies = getstatus($thread['status'], 2);

if(($thread['readperm'] && $thread['readperm'] > $readaccess && !$forum['ismoderator'] && $thread['authorid'] != $discuz_uid) || (empty($forum['allowview']) && ((!$forum['viewperm'] && !$readaccess) || ($forum['viewperm'] && !forumperm($forum['viewperm'])))) || $forum['password'] || $forum['redirect']) {
	wapmsg('thread_nopermission');
} elseif($thread['price'] > 0) {
	if($maxchargespan && $timestamp - $thread['dateline'] >= $maxchargespan * 3600) {
		$db->query("UPDATE {$tablepre}threads SET price='0' WHERE tid='$tid'");
		$thread['price'] = 0;
	} elseif(!$discuz_uid || (!$forum['ismoderator'] && $thread['authorid'] != $discuz_uid && !$db->num_rows($db->query("SELECT tid FROM {$tablepre}paymentlog WHERE tid='$tid' AND uid='$discuz_uid'")))) {
			wapmsg('thread_nopermission');
	}
}

if(empty($do)) {

	echo "<p>$lang[subject]$thread[subject]<br />".
		"$lang[author]<a href=\"index.php?action=my&amp;uid=$thread[authorid]\">$thread[author]</a><br />".
		"$lang[dateline]".gmdate("$wapdateformat $timeformat", $thread['dateline'] + $timeoffset * 3600)."<br /><br />";

	$page = max(1, intval($page));
	$start_limit = $number = ($page - 1) * $wapppp;
	if($page < 2) {
		$end_limit = $wapppp + 1;
	} else {
		$start_limit = $start_limit + 1;
		$end_limit = $wapppp;
	}

	$query = $sdb->query("SELECT p.*, m.groupid
		FROM {$tablepre}posts p
		LEFT JOIN {$tablepre}members m ON p.authorid=m.uid
		WHERE p.tid='$tid' AND p.invisible='0'
		ORDER BY p.dateline LIMIT $start_limit, $end_limit");
	while($post = $sdb->fetch_array($query)) {
		$needhiddenreply = ($hiddenreplies && $discuz_uid != $post['authorid'] && $discuz_uid != $thread['authorid'] && !$post['first'] && !$forum['ismoderator']);
		if($post['status'] & 1) {
			$post['message'] = $lang['thread_banned'];
		}
		if(in_array($post['groupid'], array(4, 5, 6))) {
			$post['message'] = $lang['thread_banned'];
		}
		if($needhiddenreply) {
			$post['needhiddenreply'] = $needhiddenreply;
			$post['message'] = $lang['message_ishidden_hiddenreplies'];
		}
		$post['message'] = wapcode($post['message']);
		if($post['first']) {
			if($offset > 0) {
				$str = $post['message'];
				for($i = $offset; $i > $offset - $wapmps + 2; $i --) {
					if(ord($str[$i-1]) > 127) {
						$i --;
					}
				}
				$offset_last = $i;
				$post['message'] = substr($post['message'], $offset);
			} else {
				$offset = 0;
			}
			if(strlen($post['message']) > $wapmps) {
				$post['message'] = wapcutstr($post['message'], $wapmps);
				$offset_next = $offset + $wapmps;
				$breaked = 1;
			} else {
				$breaked = 0;
			}
			if($adminid != 1 && $post['status'] & 1) {
				$post['message'] = $lang['message_single_banned'];
			} else {
				if($post['status'] & 1) {
					$post['message'] = '<p>' . $lang['admin_message_single_banned'] . '</p>' . $post['message'];
				}
			}

			$post['author'] = !$post['anonymous'] ? $post['author'] : $lang['anonymous'];
			$threadposts .= nl2br(trim($post['message']));
		} else {
			$postlist[] = $post;
		}
	}

	echo $threadposts.(!$breaked ? '' : "<br /><a href=\"index.php?action=thread&amp;tid=$tid&amp;offset=$offset_next\">$lang[next_page]</a> ").
		(!$offset ? '' : "<a href=\"index.php?action=thread&amp;tid=$tid&amp;offset=$offset_last\">$lang[last_page]</a>")."<br />\n".
		"<br /><a href=\"index.php?action=post&amp;do=reply&amp;fid=$forum[fid]&amp;tid=$thread[tid]\">$lang[post_reply]</a>|<a href=\"index.php?action=post&amp;do=newthread&amp;fid=$forum[fid]\">$lang[post_new]</a>\n";

	if(!empty($postlist)) {
		echo "<br /><br />$lang[thread_replylist] ($thread[replies])<br />";
		foreach($postlist as $post) {
			$waptlength = 30;
			if(isset($post['needhiddenreply']) && $post['needhiddenreply']) {
				echo "#".++$number." ".wapcutstr(trim($post['message']), $waptlength);
			} else {
				echo "<a href=\"index.php?action=thread&amp;do=reply&amp;tid=$post[tid]&amp;pid=$post[pid]\">#".++$number." ".wapcutstr(trim($post['message']), $waptlength)."</a>";
			}
			echo "<br />[".(!$post['anonymous'] ? $post['author'].' ' : $lang['anonymous'].' ').gmdate("$wapdateformat $timeformat", $post['dateline'] + $timeoffset * 3600)."]<br />";
		}
		echo wapmulti($thread['replies'], $wapppp, $page, "index.php?action=thread&amp;tid=$thread[tid]");
	}

} elseif($do == 'reply') {

	$post = $db->fetch_first("SELECT * FROM {$tablepre}posts WHERE pid='$pid' AND invisible='0'");

	if($post['status'] & 1) {
		$post['message'] = $lang['thread_banned'];
	}

	$needhiddenreply = ($hiddenreplies && $discuz_uid != $post['authorid'] && $discuz_uid != $thread['authorid'] && !$post['first'] && !$forum['ismoderator']);
	if($needhiddenreply) {
		wapmsg('message_ishidden_hiddenreplies');
	}

	if($offset > 0) {
		$post['message'] = '..'.substr($post['message'], $offset - 4);
	}

	if(strlen($threadposts) + strlen($post['message']) - $wapmps > 0) {
		$length = $wapmps - strlen($threadposts);
		$post['message'] = wapcutstr($post['message'], $length);
		$offset += $length;
		$breaked = 1;
	}
	$post['author'] = !$post['anonymous'] ? $post['author'] : $lang['anonymous'];
	$post['message'] = wapcode($post['message']);

	echo "<p>$lang[thread_reply]<a href=\"index.php?action=thread&amp;tid=$thread[tid]\">$thread[subject]</a><br />";

	echo $lang['author'].(!$post['anonymous'] ? "<a href=\"index.php?action=my&amp;uid=$post[authorid]\">$post[author]</a>" : $lang['anonymous'])."<br />\n".
		"<br />".nl2br(trim($post['message']))."\n";

	if(!$breaked) {
		$start++;
		$offset = 0;
	}

}

echo "<br />".
	(!empty($allowreply) ? "<br /><input type=\"text\" name=\"message\" value=\"\" size=\"6\" emptyok=\"true\"/>\n".
	"<anchor title=\"$lang[submit]\">$lang[thread_quickreply]".
	"<go method=\"post\" href=\"index.php?action=post&amp;do=reply&amp;fid=$forum[fid]&amp;tid=$thread[tid]&amp;sid=$sid\">\n".
	"<postfield name=\"message\" value=\"$(message)\"/>\n".
	"<postfield name=\"formhash\" value=\"".formhash()."\" />\n".
	"</go></anchor><br />\n".
	"<a href=\"index.php?action=my&amp;do=fav&amp;favid=$thread[tid]&amp;type=thread\">$lang[my_addfav]</a><br />\n" : '').
	"<br />&lt;&lt;<a href=\"index.php?action=goto&amp;do=next&amp;tid=$thread[tid]&amp;fid=$thread[fid]\">$lang[next_thread]</a>".
	"<br />&gt;&gt;<a href=\"index.php?action=goto&amp;do=last&amp;tid=$thread[tid]&amp;fid=$thread[fid]\">$lang[last_thread]</a><br />".
	"<a href=\"index.php?action=forum&amp;fid=$forum[fid]\">$lang[return_forum]</a></p>";

?>