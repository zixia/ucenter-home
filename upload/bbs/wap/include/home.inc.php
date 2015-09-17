<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: home.inc.php 19072 2009-08-12 05:01:33Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$newthreads = round(($timestamp - $lastvisit + 600) / 1000) * 1000;
$onlinemem = $onlineguest = $forumnum = 0;

echo "<p>$bbname<br />\n";

if($discuz_uid) {
	echo (!empty($allowsearch) ? "<br /><a href=\"index.php?action=search&amp;srchfrom=$newthreads&amp;do=submit\">$lang[home_newthreads]</a><br /><a href=\"index.php?action=search\">$lang[search]</a><br />" : '').
		"<a href=\"index.php?action=my&amp;do=fav\">$lang[my_favorites]</a><br />".
		"<a href=\"index.php?action=my\">$lang[my]</a><br />";
}

echo 	"<br />$lang[home_forums]<br />";

$sql = !empty($accessmasks) ?
			"SELECT f.fid, f.name, f.fup, f.type, ff.viewperm, a.allowview FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid
				LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid
				WHERE f.status='1' AND f.type='forum' ORDER BY f.displayorder"
			: "SELECT f.fid, f.name, f.fup, f.type, ff.viewperm FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff USING(fid)
				WHERE f.status='1' AND f.type='forum' ORDER BY f.type, f.displayorder";

$query = $sdb->query($sql);
$forumlist = array();
while($forum = $sdb->fetch_array($query)) {
	$forumlist[] = $forum;
}

foreach($forumlist as $forum) {
	if(forum($forum) && (!$forum['viewperm'] || (strexists("\t".trim($forum['viewperm'])."\t", "\t".trim($groupid)."\t") && $forum['viewperm']))) {
		echo "<a href=\"index.php?action=forum&amp;fid=$forum[fid]\">".strip_tags($forum['name'])."</a><br/>";
	}
	if($forumnum ++ >= 10) {
		break;
	}
}

echo ($forumnum > 10 ? "<a href=\"index.php?action=forum\">$lang[more]</a><br /><br />" : '').
	"$lang[home_tools]<br />".
	"<a href=\"index.php?action=stats\">$lang[stats]</a><br />".
	"<a href=\"index.php?action=goto\">$lang[goto]</a>".
	(!empty($allowsearch) ? "<br /><br /><input type=\"text\" name=\"srchtxt\" value=\"\" size=\"8\" emptyok=\"true\" /> ".
	"<anchor title=\"submit\">$lang[search]\n".
	"<go method=\"post\" href=\"index.php?action=search&amp;do=submit\" />\n".
	"<postfield name=\"srchtxt\" value=\"$(srchtxt)\" /></anchor>" : '');

$query = $db->query("SELECT uid, COUNT(*) AS count FROM {$tablepre}sessions GROUP BY uid='0'");
while($online = $db->fetch_array($query)) {
	$online['uid'] ? $onlinemem = $online['count'] : $onlineguest = $online['count'];
}

echo "<br /><br />$lang[home_online]".($onlinemem + $onlineguest)."({$onlinemem} $lang[home_members])</p>\n";

?>