<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: printable.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$thisbg = '#FFFFFF';

$query = $db->query("SELECT p.*, m.username, m.groupid FROM {$tablepre}posts p
		LEFT JOIN {$tablepre}members m ON m.uid=p.authorid
		WHERE p.tid='$tid' AND p.invisible='0' ORDER BY p.dateline LIMIT 100");

while($post = $db->fetch_array($query)) {

	$post['dateline'] = dgmdate("$dateformat $timeformat", $post['dateline'] + ($timeoffset * 3600));
	$post['message'] = discuzcode($post['message'], $post['smileyoff'], $post['bbcodeoff'], sprintf('%00b', $post['htmlon']), $forum['allowsmilies'], $forum['allowbbcode'], $forum['allowimgcode'], $forum['allowhtml'], ($forum['jammer'] && $post['authorid'] != $discuz_uid ? 1 : 0));

	if($post['attachment']) {
		$attachment = 1;
	}
	$post['attachments'] = array();
	if($post['attachment'] && $allowgetattach) {
		$attachpids .= ",$post[pid]";
		$post['attachment'] = 0;
		if(preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $post['message'], $matchaids)) {
			$attachtags[$post['pid']] = $matchaids[1];
		}
	}

	$postlist[$post['pid']] = $post;
}

if($attachpids) {
	require_once DISCUZ_ROOT.'./include/attachment.func.php';
	parseattach($attachpids, $attachtags, $postlist);
}

include template('viewthread_printable');

?>