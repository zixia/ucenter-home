<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forum.inc.php 18819 2009-07-23 10:38:43Z liuqiang $
*/


if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$forum = $sdb->fetch_first("SELECT * FROM {$tablepre}forums f
	LEFT JOIN {$tablepre}forumfields ff USING (fid)
	WHERE f.fid='$fid' AND f.status='1' AND f.type<>'group' AND ff.password=''");

if($forum['redirect']) {
	header("Location: $forum[redirect]");
	exit();
}

$page = max(1, intval($page));

$navtitle = ($forum['type'] == 'sub' ? ' - '.strip_tags($_DCACHE['forums'][$forum['fup']]['name']) : '').
	strip_tags($forum['name']).'('.$lang['page'].' '.$page.') - ';

if(!$forum || !forumperm($forum['viewperm']) || !forumformulaperm($forum['formulaperm'])) {
	showheader();
	echo $lang['forum_nonexistence'];

} else {

	$headernav .= ' &raquo; ';
	$headernav .= $forum['type'] == 'sub' ? "<a href=\"archiver/{$qm}fid-$forum[fup].html\">{$_DCACHE[forums][$forum[fup]][name]}</a> &raquo; ": '';
	$headernav .= '<a href="archiver/'.$qm.'fid-'.$fid.'.html">'.$forum['name'].'</a>';
	showheader();

	$fullversion = array('title' => $forum['name'], 'link' => "forumdisplay.php?fid=$fid");

	$tpp = $_DCACHE['settings']['topicperpage'] * 2;
	$start = ($page - 1) * $tpp;

	echo "<ul type=\"1\" start=\"".($start + 1)."\">\n";

	$query = $sdb->query("SELECT * FROM {$tablepre}threads WHERE fid='$fid' AND displayorder>='0' ORDER BY displayorder DESC, lastpost DESC LIMIT $start, $tpp");
	while($thread = $sdb->fetch_array($query)) {
		echo "<li><a href=\"archiver/{$qm}tid-$thread[tid].html\">$thread[subject]</a> ($thread[replies] $lang[replies])</li>\n";
	}

	echo "</ul>\n";
	echo '<div class="page">'.multi($forum['threads'], $page, $tpp, "{$qm}fid-$fid").'</div>';

}

?>
