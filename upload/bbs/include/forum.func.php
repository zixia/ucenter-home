<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forum.func.php 20900 2009-10-29 02:49:38Z tiger $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function checkautoclose() {
	global $timestamp, $forum, $thread;

	if(!$forum['ismoderator'] && $forum['autoclose']) {
		$closedby = $forum['autoclose'] > 0 ? 'dateline' : 'lastpost';
		$forum['autoclose'] = abs($forum['autoclose']);
		if($timestamp - $thread[$closedby] > $forum['autoclose'] * 86400) {
			return 'post_thread_closed_by_'.$closedby;
		}
	}
	return FALSE;
}

function forum(&$forum) {
	global $_DCOOKIE, $timestamp, $timeformat, $dateformat, $discuz_uid, $groupid, $lastvisit, $moddisplay, $timeoffset, $hideprivate, $onlinehold;

	if(!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || !empty($forum['allowview']) || (isset($forum['users']) && strstr($forum['users'], "\t$discuz_uid\t"))) {
		$forum['permission'] = 2;
	} elseif(!$hideprivate) {
		$forum['permission'] = 1;
	} else {
		return FALSE;
	}

	if($forum['icon']) {
		if(strstr($forum['icon'], ',')) {
			$flash = explode(",", $forum['icon']);
			$forum['icon'] = "<a href=\"forumdisplay.php?fid=$forum[fid]\"><embed style=\"float:left; margin-right: 10px\" src=\"".trim($flash[0])."\" width=\"".trim($flash[1])."\" height=\"".trim($flash[2])."\" type=\"application/x-shockwave-flash\" align=\"left\"></embed></a>";
		} else {
			$forum['icon'] = "<a href=\"forumdisplay.php?fid=$forum[fid]\"><img style=\"float:left; margin-right: 10px\" src=\"$forum[icon]\" align=\"left\" alt=\"\" border=\"0\" /></a>";
		}
	}

	$lastpost = array('tid' => 0, 'dateline' => 0, 'subject' => '', 'author' => '');
	list($lastpost['tid'], $lastpost['subject'], $lastpost['dateline'], $lastpost['author']) = is_array($forum['lastpost']) ? $forum['lastpost'] : explode("\t", $forum['lastpost']);
	$forum['folder'] = (isset($_DCOOKIE['fid'.$forum['fid']]) && $_DCOOKIE['fid'.$forum['fid']] > $lastvisit ? $_DCOOKIE['fid'.$forum['fid']] : $lastvisit) < $lastpost['dateline'] ? ' class="new"' : '';

	if($lastpost['tid']) {
		$lastpost['dateline'] = dgmdate("$dateformat $timeformat", $lastpost['dateline'] + $timeoffset * 3600);
		$lastpost['authorusername'] = $lastpost['author'];
		if($lastpost['author']) {
			$lastpost['author'] = '<a href="space.php?username='.rawurlencode($lastpost['author']).'">'.$lastpost['author'].'</a>';
		}
		$forum['lastpost'] = $lastpost;
	} else {
		$forum['lastpost'] = $lastpost['authorusername'] = '';
	}

	$forum['moderators'] = moddisplay($forum['moderators'], $moddisplay, !empty($forum['inheritedmod']));

	if(isset($forum['subforums'])) {
		$forum['subforums'] = implode(', ', $forum['subforums']);
	}

	return TRUE;
}

function forumselect($groupselectable = FALSE, $tableformat = 0, $selectedfid = 0, $showhide = FALSE) {
	global $_DCACHE, $discuz_uid, $groupid, $fid, $gid, $indexname, $db, $tablepre;

	if(!isset($_DCACHE['forums'])) {
		require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
	}
	$forumcache = &$_DCACHE['forums'];

	$forumlist = $tableformat ? '<dl><dd><ul>' : '<optgroup label="&nbsp;">';
	foreach($forumcache as $forum) {
		if((!$forum['status'] || $forum['status'] == 2) && !$showhide) {
			continue;
		}
		if($forum['type'] == 'group') {
			if($tableformat) {
				$forumlist .= '</ul></dd></dl><dl><dt><a href="'.$indexname.'?gid='.$forum['fid'].'">'.$forum['name'].'</a></dt><dd><ul>';
			} else {
				$forumlist .= $groupselectable ? '<option value="'.$forum['fid'].'" class="bold">--'.$forum['name'].'</option>' : '</optgroup><optgroup label="--'.$forum['name'].'">';
			}
			$visible[$forum['fid']] = true;
		} elseif($forum['type'] == 'forum' && isset($visible[$forum['fup']]) && (!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || strstr($forum['users'], "\t$discuz_uid\t"))) {
			if($tableformat) {
				$forumlist .= '<li'.($fid == $forum['fid'] ? ' class="current"' : '').'><a href="forumdisplay.php?fid='.$forum['fid'].'">'.$forum['name'].'</a></li>';
			} else {
				$forumlist .= '<option value="'.$forum['fid'].'"'.($selectedfid && $selectedfid == $forum['fid'] ? ' selected' : '').'>'.$forum['name'].'</option>';
			}
			$visible[$forum['fid']] = true;
		} elseif($forum['type'] == 'sub' && isset($visible[$forum['fup']]) && (!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || strstr($forum['users'], "\t$discuz_uid\t"))) {
			if($tableformat) {
				$forumlist .=  '<li class="sub'.($fid == $forum['fid'] ? ' current' : '').'"><a href="forumdisplay.php?fid='.$forum['fid'].'">'.$forum['name'].'</a></li>';
			} else {
				$forumlist .= '<option value="'.$forum['fid'].'"'.($selectedfid && $selectedfid == $forum['fid'] ? ' selected' : '').'>&nbsp; &nbsp; &nbsp; '.$forum['name'].'</option>';
			}
		}
	}
	$forumlist .= $tableformat ? '</ul></dd></dl>' : '</optgroup>';
	$forumlist = str_replace($tableformat ? '<dl><dd><ul></ul></dd></dl>' : '<optgroup label="&nbsp;"></optgroup>', '', $forumlist);

	return $forumlist;
}

function visitedforums() {
	global $_DCACHE, $_DCOOKIE, $forum, $sid;

	$count = 0;
	$visitedforums = '';
	$fidarray = array($forum['fid']);
	foreach(explode('D', $_DCOOKIE['visitedfid']) as $fid) {
		if(isset($_DCACHE['forums'][$fid]) && !in_array($fid, $fidarray)) {
			$fidarray[] = $fid;
			if($fid != $forum['fid']) {
				$visitedforums .= '<li><a href="forumdisplay.php?fid='.$fid.'&amp;sid='.$sid.'">'.$_DCACHE['forums'][$fid]['name'].'</a></li>';
				if(++$count >= $GLOBALS['visitedforums']) {
					break;
				}

			}
		}
	}
	if(($visitedfid = implode('D', $fidarray)) != $_DCOOKIE['visitedfid']) {
		dsetcookie('visitedfid', $visitedfid, 2592000);
	}
	return $visitedforums;
}

function moddisplay($moderators, $type, $inherit = 0) {
	if($type == 'selectbox') {
		if($moderators) {
			$modlist = '';
			foreach(explode("\t", $moderators) as $moderator) {
				$modlist .= '<li><a href="space.php?username='.rawurlencode($moderator).'">'.($inherit ? '<strong>'.$moderator.'</strong>' : $moderator).'</a></li>';
			}
		} else {
			$modlist = '';
		}

		return $modlist;
	} else {
		if($moderators) {
			$modlist = $comma = '';
			foreach(explode("\t", $moderators) as $moderator) {
				$modlist .= $comma.'<a class="notabs" href="space.php?username='.rawurlencode($moderator).'">'.($inherit ? '<strong>'.$moderator.'</strong>' : $moderator).'</a>';
				$comma = ', ';
			}
		} else {
			$modlist = '';
		}
		return $modlist;
	}
}

function getcacheinfo($tid) {
	global $timestamp, $cachethreadlife, $cachethreaddir;
	$tid = intval($tid);
	$cachethreaddir2 = DISCUZ_ROOT.'./'.$cachethreaddir;
	$cache = array('filemtime' => 0, 'filename' => '');
	$tidmd5 = substr(md5($tid), 3);
	$fulldir = $cachethreaddir2.'/'.$tidmd5[0].'/'.$tidmd5[1].'/'.$tidmd5[2].'/';
	$cache['filename'] = $fulldir.$tid.'.htm';
	if(file_exists($cache['filename'])) {
		$cache['filemtime'] = filemtime($cache['filename']);
	} else {
		if(!is_dir($fulldir)) {
			for($i=0; $i<3; $i++) {
				$cachethreaddir2 .= '/'.$tidmd5{$i};
				if(!is_dir($cachethreaddir2)) {
					@mkdir($cachethreaddir2, 0777);
					@touch($cachethreaddir2.'/index.htm');
				}
			}
		}
	}
	return $cache;
}

function recommendupdate($fid, &$modrecommend, $force = '', $position = 0) {
	global $db, $tablepre, $timestamp, $_DCACHE;

	$recommendlist = $recommendimagelist = $modedtids = array();
	$num = $modrecommend['num'] ? intval($modrecommend['num']) : 10;
	$imagenum = $modrecommend['imagenum'] = $modrecommend['imagenum'] ? intval($modrecommend['imagenum']) : 5;
	$imgw = $modrecommend['imagewidth'] = $modrecommend['imagewidth'] ? intval($modrecommend['imagewidth']) : 200;
	$imgh = $modrecommend['imageheight'] = $modrecommend['imageheight'] ? intval($modrecommend['imageheight']) : 150;

	if($modrecommend['sort'] && ($timestamp - $modrecommend['updatetime'] > $modrecommend['cachelife'] || $force)) {
		$query = $db->query("SELECT tid, moderatorid, aid FROM {$tablepre}forumrecommend WHERE fid='$fid'");
		while($row = $db->fetch_array($query)) {
			if($row['aid'] && $modrecommend['sort'] == 2 || $modrecommend['sort'] == 1) {
				@unlink(DISCUZ_ROOT.'./forumdata/imagecaches/'.intval($row['aid']).'_'.$imgw.'_'.$imgh.'.jpg');
			}
			if($modrecommend['sort'] == 2 && $row['moderatorid']) {
				$modedtids[] = $row['tid'];
			}
		}
		$db->query("DELETE FROM {$tablepre}forumrecommend WHERE fid='$fid'".($modrecommend['sort'] == 2 ? " AND moderatorid='0'" : ''));
		$orderby = 'dateline';
		$conditions = $modrecommend['dateline'] ? 'AND dateline>'.($timestamp - $modrecommend['dateline'] * 3600) : '';
		switch($modrecommend['orderby']) {
			case '':
			case '1':$orderby = 'lastpost';break;
			case '2':$orderby = 'views';break;
			case '3':$orderby = 'replies';break;
			case '4':$orderby = 'digest';break;
			case '5':$orderby = 'recommends';$conditions .= " AND recommends>'0'";break;
			case '6':$orderby = 'heats';break;
		}

		$add = $comma = $i = '';
		$addthread = $addimg = $recommendlist = $recommendimagelist = $tids = array();
		$query = $db->query("SELECT fid, tid, author, authorid, subject, highlight FROM {$tablepre}threads WHERE fid='$fid' AND displayorder>='0' $conditions ORDER BY $orderby DESC LIMIT 0, $num");
		while($thread = $db->fetch_array($query)) {
			$recommendlist[$thread['tid']] = $thread;
			$tids[] = $thread['tid'];
			if(!$modedtids || !in_array($thread['tid'], $modedtids)) {
				$addthread[$thread['tid']] = "'$thread[fid]', '$thread[tid]', '1', '$i', '".addslashes($thread['subject'])."', '".addslashes($thread['author'])."', '$thread[authorid]', '0', '0', '$thread[highlight]'";
				$i++;
			}
		}
		if($tids) {
			$query = $db->query("SELECT p.fid, p.tid, a.aid FROM {$tablepre}posts p
				INNER JOIN {$tablepre}attachments a ON a.pid=p.pid AND a.isimage IN ('1', '-1') AND a.width>='$imgw'
				WHERE p.tid IN (".implodeids($tids).") AND p.first='1'");
			while($attachment = $db->fetch_array($query)) {
				if(isset($recommendimagelist[$attachment['tid']])) {
					continue;
				}
				$key = authcode($attachment['aid']."\t".$imgw."\t".$imgh, 'ENCODE', $_DCACHE['settings']['authkey']);
				$recommendlist[$attachment['tid']]['filename'] = 'image.php?aid='.$attachment['aid'].'&size='.$imgw.'x'.$imgh.'&key='.rawurlencode($key);
				$recommendimagelist[$attachment['tid']] = $recommendlist[$attachment['tid']];
				$addimg[$attachment['tid']] = ",'$attachment[aid]', '".addslashes($recommendlist[$attachment['tid']]['filename'])."', '1'";
				if(count($recommendimagelist) == $imagenum) {
					break;
				}
			}
		}
		foreach($addthread as $tid => $row) {
			$add .= $comma.'('.$row.(!isset($addimg[$tid]) ? ",'0','','0'" : $addimg[$tid]).')';
			$comma = ', ';
		}
		unset($recommendimagelist);

		if($add) {
			$db->query("REPLACE INTO {$tablepre}forumrecommend (fid, tid, position, displayorder, subject, author, authorid, moderatorid, expiration, highlight, aid, filename, typeid) VALUES $add");
			$modrecommend['updatetime'] = $timestamp;
			$modrecommendnew = addslashes(serialize($modrecommend));
			$db->query("UPDATE {$tablepre}forumfields SET modrecommend='$modrecommendnew' WHERE fid='$fid'");
		}
	}

	$recommendlists = $recommendlist =  array();
	$position = $position ? "AND position IN ('0','$position')" : '';
	$query = $db->query("SELECT * FROM {$tablepre}forumrecommend WHERE fid='$fid' $position ORDER BY displayorder");
	while($recommend = $db->fetch_array($query)) {
		if(($recommend['expiration'] && $recommend['expiration'] > $timestamp) || !$recommend['expiration']) {
			$recommendlist[] = $recommend;
			if($recommend['typeid'] && count($recommendimagelist) <= $imagenum) {
				$recommendimagelist[] = $recommend;
			}
		}
		if(count($recommendlist) == $num) {
			break;
		}
	}

	if($recommendlist) {
		$colorarray = array('', '#EE1B2E', '#EE5023', '#996600', '#3C9D40', '#2897C5', '#2B65B7', '#8F2A90', '#EC1282');
		foreach($recommendlist as $thread) {
			if($thread['highlight']) {
				$string = sprintf('%02d', $thread['highlight']);
				$stylestr = sprintf('%03b', $string[0]);

				$thread['highlight'] = ' style="';
				$thread['highlight'] .= $stylestr[0] ? 'font-weight: bold;' : '';
				$thread['highlight'] .= $stylestr[1] ? 'font-style: italic;' : '';
				$thread['highlight'] .= $stylestr[2] ? 'text-decoration: underline;' : '';
				$thread['highlight'] .= $string[1] ? 'color: '.$colorarray[$string[1]] : '';
				$thread['highlight'] .= '"';
			} else {
				$thread['highlight'] = '';
			}
			$recommendlists[$thread['tid']]['author'] = $thread['author'];
			$recommendlists[$thread['tid']]['authorid'] = $thread['authorid'];
			$recommendlists[$thread['tid']]['subject'] = $modrecommend['maxlength'] ? cutstr($thread['subject'], $modrecommend['maxlength']) : $thread['subject'];
			$recommendlists[$thread['tid']]['subjectstyles'] = $thread['highlight'];
		}
	}

	if($recommendimagelist && $recommendlist) {
		$recommendlists['images'] = $recommendimagelist;
	}

	return $recommendlists;
}

function showstars($num) {
	global $starthreshold;
	$alt = 'alt="Rank: '.$num.'"';
	if(empty($starthreshold)) {
		for($i = 0; $i < $num; $i++) {
			echo '<img src="'.IMGDIR.'/star_level1.gif" '.$alt.' />';
		}
	} else {
		for($i = 3; $i > 0; $i--) {
			$numlevel = intval($num / pow($starthreshold, ($i - 1)));
			$num = ($num % pow($starthreshold, ($i - 1)));
			for($j = 0; $j < $numlevel; $j++) {
				echo '<img src="'.IMGDIR.'/star_level'.$i.'.gif" '.$alt.' />';
			}
		}
	}
}

?>