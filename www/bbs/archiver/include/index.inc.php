<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: index.inc.php 20759 2009-10-19 02:07:04Z monkey $
*/


if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

showheader();

$forums = $subforums = array();
$categories = array(0 => array('fid' => 0, 'name' => $_DCACHE['settings']['bbname']));

foreach($_DCACHE['forums'] as $forum) {
	if(forumperm($forum['viewperm']) && $forum['status']) {
		if($forum['type'] == 'group') {
			$categories[] = $forum;
		} else {
			$forum['type'] == 'sub' ? $subforums[$forum['fup']][] = $forum : $forums[$forum['fup']][] = $forum;
		}
	}
}

foreach($categories as $category) {
	if(isset($forums[$category['fid']])) {
		echo "<h3>$category[name]</h3>\n<ul>\n";
		foreach($forums[$category['fid']] as $forum) {
			echo "<li><a href=\"archiver/{$qm}fid-{$forum[fid]}.html\">$forum[name]</a>\n";
			if(isset($subforums[$forum['fid']])) {
				echo "<ul>\n";
				foreach($subforums[$forum['fid']] as $subforum) {
					echo "<li><a href=\"archiver/{$qm}fid-$subforum[fid].html\">$subforum[name]</a></li>\n";
				}
				echo "</ul></li>\n";
			}
		}
		echo "</li></ul>\n";
	}
}

?>