<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forum.inc.php 19046 2009-08-10 10:04:18Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$discuz_action = 192;

$page = max(1, intval($page));
$start_limit = $number = ($page - 1) * $waptpp;

if(!empty($fid)) {

	require_once DISCUZ_ROOT.'./include/forum.func.php';

	if(empty($forum)) {
		wapmsg('forum_nonexistence');
	}

	if(($forum['viewperm'] && !forumperm($forum['viewperm']) && !$forum['allowview']) || $forum['redirect'] || $forum['password']) {
		wapmsg('forum_nopermission');
	} elseif($forum['formulaperm']) {
		formulaperm($forum['formulaperm'], 0, TRUE);
	}

	echo 	"<p>".strip_tags($forum['name'])."<br />".
		"<a href=\"index.php?action=post&amp;do=newthread&amp;fid=$forum[fid]\">$lang[post_new]</a> ".
		"<a href=\"index.php?action=forum&amp;do=digest&amp;fid=$forum[fid]\">$lang[digest]</a><br /><br />".
		"$lang[forum_list] <a href=\"index.php?action=forum&amp;fid=$forum[fid]\">$lang[reload]</a><br />";

	$do = !empty($do) ? 'digest' : '';
	$filteradd = $do == 'digest' ? 'AND digest>\'0\'' : '';
	$threadcount = $sdb->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE fid='$fid' $filteradd AND displayorder>='0'");

	$thread['prefix'] = '';
	$query = $sdb->query("SELECT * FROM {$tablepre}threads
		WHERE fid='$fid' $filteradd AND displayorder>='0'
		ORDER BY displayorder DESC, lastpost DESC LIMIT $start_limit, $waptpp");
	while($thread = $sdb->fetch_array($query)) {
		$thread['prefix'] .= $thread['displayorder'] > 0 ? $lang['forum_thread_sticky'] : '';
		$thread['prefix'] .= $thread['digest'] ? $lang['forum_thread_digest'] : '';
		echo "<a href=\"index.php?action=thread&amp;tid=$thread[tid]\">#".++$number." ".cutstr($thread['subject'], 30)."</a>$thread[prefix]<br />\n".
			"<small>[$thread[author] $lang[replies]$thread[replies] $lang[views]$thread[views]]</small><br />\n";
	}

	echo wapmulti($threadcount, $waptpp, $page, "index.php?action=forum&amp;fid=$forum[fid]&amp;sid=$sid");

	if($do != 'digest') {
		$subforums = '';
		foreach($_DCACHE['forums'] as $subforum) {
			if($subforum['type'] == 'sub' && $subforum['fup'] == $fid && (!$forum['viewperm'] || (strexists("\t".trim($forum['viewperm'])."\t", "\t".trim($groupid)."\t")))) {
				$subforums .= "<a href=\"index.php?action=forum&amp;fid=$subforum[fid]\">".strip_tags($subforum['name'])."</a><br />";
			}
		}
		if(!empty($subforums)) {
			echo "<br /><br />$lang[forum_sublist]<br />".$subforums;
		}
	}

	echo (!empty($allowsearch) ? "<br /><br /><a href=\"index.php?action=post&amp;do=newthread&amp;fid=$forum[fid]\">$lang[post_new]</a><br />".
		"<a href=\"index.php?action=my&amp;do=fav&amp;favid=$forum[fid]&amp;type=forum\">$lang[my_addfav]</a><br />".
		"<input type=\"text\" name=\"srchtxt\" value=\"\" size=\"6\" format=\"M*m\" emptyok=\"true\"/>".
		"<anchor title=\"submit\">$lang[search]\n".
		"<go method=\"post\" href=\"index.php?action=search&amp;srchfid=$forum[fid]&amp;do=submit&amp;sid=$sid\">\n".
		"<postfield name=\"srchtxt\" value=\"$(srchtxt)\" />\n".
		"</go></anchor><br />" : '').
		"</p>";

} else {

	echo "<p>$lang[home_forums]<br />";

	$forumcount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}forums WHERE status='1' AND type='forum'");

	$sql = !empty($accessmasks) ?
			"SELECT f.fid, f.name, ff.viewperm, a.allowview FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid
				LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid
				WHERE f.status='1' AND f.type='forum' ORDER BY f.displayorder LIMIT $start_limit, $waptpp"
			: "SELECT f.fid, f.name, ff.viewperm FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff USING(fid)
				WHERE f.status='1' AND f.type='forum' ORDER BY f.displayorder LIMIT $start_limit, $waptpp";

	$query = $sdb->query($sql);
	while($forum = $sdb->fetch_array($query)) {
		if(forum($forum) && (!$forum['viewperm'] || (strexists("\t".trim($forum['viewperm'])."\t", "\t".trim($groupid)."\t") && $forum['viewperm']))) {
			echo "<a href=\"index.php?action=forum&amp;fid=$forum[fid]\">".strip_tags($forum['name'])."</a><br/>";
		}
	}

	echo wapmulti($forumcount, $waptpp, $page, "index.php?action=forum&amp;sid=$sid");
	echo "</p>";

}

?>