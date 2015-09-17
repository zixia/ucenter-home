<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: prune.inc.php 21266 2009-11-24 05:35:06Z liulanbo $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

$page = max(1, intval($page));

require_once DISCUZ_ROOT.'./include/misc.func.php';
require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';

if(!submitcheck('prunesubmit')) {

	require_once DISCUZ_ROOT.'./include/forum.func.php';

	if($adminid == 1 || $adminid == 2) {
		$forumselect = '<select name="forums"><option value="">&nbsp;&nbsp;> '.$lang['select'].'</option>'.
			'<option value="">&nbsp;</option>'.forumselect(FALSE, 0, 0, TRUE).'</select>';

		if($forums) {
			$forumselect = preg_replace("/(\<option value=\"$forums\")(\>)/", "\\1 selected=\"selected\" \\2", $forumselect);
		}
	} else {
		$forumselect = $comma = '';
		$query = $db->query("SELECT f.name FROM {$tablepre}moderators m, {$tablepre}forums f WHERE m.uid='$discuz_uid' AND m.fid=f.fid");
		while($forum = $db->fetch_array($query)) {
			$forumselect .= $comma.$forum['name'];
			$comma = ', ';
		}
		$forumselect = $forumselect ? $forumselect : $lang['none'];
	}

	$starttime = !preg_match("/^(0|\d{4}\-\d{1,2}\-\d{1,2})$/", $starttime) ? gmdate('Y-n-j', $timestamp + $timeoffset * 3600 - 86400 * 7) : $starttime;
	$endtime = $adminid == 3 || !preg_match("/^(0|\d{4}\-\d{1,2}\-\d{1,2})$/", $endtime) ? gmdate('Y-n-j', $timestamp + $timeoffset * 3600) : $endtime;

	shownav('topic', 'nav_prune');
	showsubmenusteps('nav_prune', array(
		array('prune_search', !$searchsubmit),
		array('nav_prune', $searchsubmit)
	));
	showtips('prune_tips');
	echo <<<EOT
<script type="text/javascript" src="include/js/calendar.js"></script>
<script type="text/JavaScript">
function page(number) {
	$('pruneforum').page.value=number;
	$('pruneforum').searchsubmit.click();
}
</script>
EOT;
	showtagheader('div', 'searchposts', !$searchsubmit);
	showformheader("prune", '', 'pruneforum');
	showhiddenfields(array('page' => $page));
	showtableheader();
	showsetting('prune_search_detail', 'detail', $detail, 'radio');
	showsetting('prune_search_forum', '', '', $forumselect);
	showsetting('prune_search_time', array('starttime', 'endtime'), array($starttime, $endtime), 'daterange');
	showsetting('prune_search_user', 'users', $users, 'text');
	showsetting('prune_search_ip', 'useip', $useip, 'text');
	showsetting('prune_search_keyword', 'keywords', $keywords, 'text');
	showsetting('prune_search_lengthlimit', 'lengthlimit', $lengthlimit, 'text');
	showsubmit('searchsubmit');
	showtablefooter();
	showformfooter();
	showtagfooter('div');

} else {

	$tidsdelete = $pidsdelete = '0';
	$pids = authcode($pids, 'DECODE');
	$pidsadd = $pids ? 'pid IN ('.$pids.')' : 'pid IN ('.implodeids($pidarray).')';

	$query = $db->query("SELECT fid, tid, pid, first, authorid FROM {$tablepre}posts WHERE $pidsadd");
	while($post = $db->fetch_array($query)) {
		$prune['forums'][] = $post['fid'];
		$prune['thread'][$post['tid']]++;

		$pidsdelete .= ",$post[pid]";
		$tidsdelete .= $post['first'] ? ",$post[tid]" : '';
	}

	if($pidsdelete) {
		require_once DISCUZ_ROOT.'./include/post.func.php';

		$query = $db->query("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE pid IN ($pidsdelete) OR tid IN ($tidsdelete)");
		while($attach = $db->fetch_array($query)) {
			dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
		}

		if(!$donotupdatemember) {
			$postsarray = $tuidarray = $ruidarray = array();
			$query1 = $db->query("SELECT fid, pid, first, authorid FROM {$tablepre}posts WHERE pid IN ($pidsdelete)");
			$query2 = $db->query("SELECT fid, pid, first, authorid FROM {$tablepre}posts WHERE tid IN ($tidsdelete)");
			while(($post = $db->fetch_array($query1)) || ($post = $db->fetch_array($query2))) {
				$forumpostsarray[$post['fid']][$post['pid']] = $post;
			}
			foreach($forumpostsarray as $fid => $postsarray) {
				$query = $db->query("SELECT postcredits, replycredits FROM {$tablepre}forumfields WHERE fid='$fid'");
				if($forum = $db->fetch_array($query)) {
					$forum['postcredits'] = !empty($forum['postcredits']) ? unserialize($forum['postcredits']) : array();
					$forum['replycredits'] = !empty($forum['replycredits']) ? unserialize($forum['replycredits']) : array();
				}
				$postcredits = $forum['postcredits'] ? $forum['postcredits'] : $creditspolicy['post'];
				$replycredits = $forum['replycredits'] ? $forum['replycredits'] : $creditspolicy['reply'];
				$tuidarray = $ruidarray = array();
				foreach($postsarray as $post) {
					if($post['first']) {
						$tuidarray[] = $post['authorid'];
					} else {
						$ruidarray[] = $post['authorid'];
					}
				}
				if($tuidarray) {
					updatepostcredits('-', $tuidarray, $postcredits);
				}
				if($ruidarray) {
					updatepostcredits('-', $ruidarray, $replycredits);
				}				
			}
		}

		$db->query("DELETE FROM {$tablepre}attachments WHERE pid IN ($pidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}attachmentfields WHERE pid IN ($pidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}attachments WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}attachmentfields WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}threadsmod WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}threads WHERE tid IN ($tidsdelete)");
		$deletedthreads = $db->affected_rows();
		$db->query("DELETE FROM {$tablepre}posts WHERE pid IN ($pidsdelete)");
		$deletedposts = $db->affected_rows();
		$db->query("DELETE FROM {$tablepre}posts WHERE tid IN ($tidsdelete)");
		$deletedposts += $db->affected_rows();
		$db->query("DELETE FROM {$tablepre}polloptions WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}polls WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}rewardlog WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}trades WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}rewardlog WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}activities WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}activityapplies WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}typeoptionvars WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}postposition WHERE tid IN ($tidsdelete)", 'UNBUFFERED');

		if(count($prune['thread']) < 50) {
			foreach($prune['thread'] as $tid => $decrease) {
				updatethreadcount($tid);
			}
		} else {
			$repliesarray = array();
			foreach($prune['thread'] as $tid => $decrease) {
				$repliesarray[$decrease][] = $tid;
			}
			foreach($repliesarray as $decrease => $tidarray) {
				$db->query("UPDATE {$tablepre}threads SET replies=replies-$decrease WHERE tid IN (".implode(',', $tidarray).")");
			}
		}

		if($globalstick) {
			updatecache('globalstick');
		}

		foreach(array_unique($prune['forums']) as $fid) {
			updateforumcount($fid);
		}

	}

	$deletedthreads = intval($deletedthreads);
	$deletedposts = intval($deletedposts);
	updatemodworks('DLP', $deletedposts);
	eval("\$cpmsg = \"".lang('prune_succeed')."\";");

?>
<script type="text/JavaScript">alert('<?=$cpmsg?>');parent.$('pruneforum').searchsubmit.click();</script>
<?

}

if(submitcheck('searchsubmit')) {

	$pids = $postcount = '0';
	$sql = $error = '';

	$keywords = trim($keywords);
	$users = trim($users);
	if(($starttime == '0' && $endtime == '0') || ($keywords == '' && $useip == '' && $users == '')) {
		$error = 'prune_condition_invalid';
	}

	if($adminid == 1 || $adminid == 2) {
		if($forums) {
			$sql .= " AND p.fid='$forums'";
		}
	} else {
		$forums = '0';
		$query = $db->query("SELECT fid FROM {$tablepre}moderators WHERE uid='$discuz_uid'");
		while($forum = $db->fetch_array($query)) {
			$forums .= ','.$forum['fid'];
		}
		$sql .= " AND p.fid IN ($forums)";
	}

	if($users != '') {
		$uids = '-1';
		$query = $db->query("SELECT uid FROM {$tablepre}members WHERE username IN ('".str_replace(',', '\',\'', str_replace(' ', '', $users))."')");
		while($member = $db->fetch_array($query)) {
			$uids .= ",$member[uid]";
		}
		$sql .= " AND p.authorid IN ($uids)";
	}
	if($useip != '') {
		$sql .= " AND p.useip LIKE '".str_replace('*', '%', $useip)."'";
	}
	if($keywords != '') {
		$sqlkeywords = '';
		$or = '';
		$keywords = explode(',', str_replace(' ', '', $keywords));
		for($i = 0; $i < count($keywords); $i++) {
			if(preg_match("/\{(\d+)\}/", $keywords[$i])) {
				$keywords[$i] = preg_replace("/\\\{(\d+)\\\}/", ".{0,\\1}", preg_quote($keywords[$i], '/'));
				$sqlkeywords .= " $or p.subject REGEXP '".$keywords[$i]."' OR p.message REGEXP '".$keywords[$i]."'";
			} else {
				$sqlkeywords .= " $or p.subject LIKE '%".$keywords[$i]."%' OR p.message LIKE '%".$keywords[$i]."%'";
			}
			$or = 'OR';
		}
		$sql .= " AND ($sqlkeywords)";
	}

	if($lengthlimit != '') {
		$lengthlimit = intval($lengthlimit);
		$sql .= " AND LENGTH(p.message) < $lengthlimit";
	}

	if($starttime != '0') {
		$starttime = strtotime($starttime);
		$sql .= " AND p.dateline>'$starttime'";
	}
	if($adminid == 1 && $endtime != gmdate('Y-n-j', $timestamp + $timeoffset * 3600)) {
		if($endtime != '0') {
			$endtime = strtotime($endtime);
			$sql .= " AND p.dateline<'$endtime'";
		}
	} else {
		$endtime = $timestamp;
	}
	if(($adminid == 2 && $endtime - $starttime > 86400 * 16) || ($adminid == 3 && $endtime - $starttime > 86400 * 8)) {
		$error = 'prune_mod_range_illegal';
	}

	if(!$error) {
		if($detail) {
			$pagetmp = $page;
			do{
				$query = $db->query("SELECT p.fid, p.tid, p.pid, p.author, p.authorid, p.dateline, t.subject, p.message FROM {$tablepre}posts p LEFT JOIN {$tablepre}threads t USING(tid) WHERE t.digest>=0 $sql LIMIT ".(($pagetmp - 1) * $ppp).",$ppp");
				$pagetmp--;
			} while(!$db->num_rows($query) && $pagetmp);
			$posts = '';
			while($post = $db->fetch_array($query)) {
				$post['dateline'] = gmdate("$dateformat $timeformat", $post['dateline'] + $timeoffset * 3600);
				$post['subject'] = cutstr($post['subject'], 30);
				$post['message'] = dhtmlspecialchars(cutstr($post['message'], 50));
				$posts .= showtablerow('', '', array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"pidarray[]\" value=\"$post[pid]\" checked />",
					"<a href=\"redirect.php?goto=findpost&pid=$post[pid]&ptid=$post[tid]\" target=\"_blank\">$post[subject]</a>",
					$post['message'],
					"<a href=\"forumdisplay.php?fid=$post[fid]\" target=\"_blank\">{$_DCACHE[forums][$post[fid]][name]}</a>",
					"<a href=\"space.php?uid=$post[authorid]\" target=\"_blank\">$post[author]</a>",
					$post['dateline']
				), TRUE);
			}
			$postcount = $db->result_first("SELECT count(*) FROM {$tablepre}posts p LEFT JOIN {$tablepre}threads t USING(tid) WHERE t.digest>=0 $sql");
			$multi = multi($postcount, $ppp, $page, "$BASESCRIPT?action=prune");
			$multi = preg_replace("/href=\"$BASESCRIPT\?action=prune&amp;page=(\d+)\"/", "href=\"javascript:page(\\1)\"", $multi);
			$multi = str_replace("window.location=$BASESCRIPT.'?action=prune&amp;page='+this.value", "page(this.value)", $multi);
		} else {
			$postcount = 0;
			$query = $db->query("SELECT pid FROM {$tablepre}posts p LEFT JOIN {$tablepre}threads t USING(tid) WHERE t.digest>=0 $sql");
			while($post = $db->fetch_array($query)) {
				$pids .= ','.$post['pid'];
				$postcount++;
			}
			$multi = '';
		}

		if(!$postcount) {
			$error = 'prune_post_nonexistence';
		}
	}

	showtagheader('div', 'postlist', $searchsubmit);
	showformheader('prune&frame=no', 'target="pruneframe"');
	showhiddenfields(array('pids' => authcode($pids, 'ENCODE')));
	showtableheader(lang('prune_result').' '.$postcount.' <a href="###" onclick="$(\'searchposts\').style.display=\'\';$(\'postlist\').style.display=\'none\';" class="act lightlink normal">'.lang('research').'</a>', 'fixpadding');

	if($error) {
		echo "<tr><td class=\"lineheight\">$lang[$error]</td></tr>";
	} else {
		if($detail) {
			showsubtitle(array('', 'subject', 'message', 'forum', 'author', 'time'));
			echo $posts;
		}
	}

	showsubmit('prunesubmit', 'submit', $detail ? 'del' : '', '<input class="checkbox" type="checkbox" name="donotupdatemember" id="donotupdatemember" value="1" checked="checked" /><label for="donotupdatemember"> '.lang('prune_no_update_member').'</label>', $multi);
	showtablefooter();
	showformfooter();
	echo '<iframe name="pruneframe" style="display:none"></iframe>';
	showtagfooter('div');

}

?>