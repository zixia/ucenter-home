<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: faq.php 20957 2009-11-04 01:44:48Z monkey $
*/

define('CURSCRIPT', 'faq');
require_once './include/common.inc.php';

$discuz_action = 51;
$keyword = isset($keyword) ? dhtmlspecialchars($keyword) : '';

$faqparent = $faqsub = array();
$query = $db->query("SELECT id, fpid, title FROM {$tablepre}faqs ORDER BY displayorder");
while($faq = $db->fetch_array($query)) {
	if(empty($faq['fpid'])) {
		$faqparent[$faq['id']] = $faq;
	} else {
		$faqsub[$faq['fpid']][] = $faq;
	}
}

if($action == 'faq') {

	$id = intval($id);
	if($ffaq = $db->fetch_first("SELECT title FROM {$tablepre}faqs WHERE fpid='$id'")) {

		$navigation = "&raquo; $ffaq[title]";
		$faqlist = array();
		$messageid = empty($messageid) ? 0 : $messageid;
		$query = $db->query("SELECT id,title,message FROM {$tablepre}faqs WHERE fpid='$id' ORDER BY displayorder");
		while($faq = $db->fetch_array($query)) {
			if(!$messageid) {
				$messageid = $faq['id'];
			}
			$faqlist[] = $faq;
		}

	} else {
		showmessage("faq_content_empty", 'faq.php');
	}

} elseif($action == 'search') {

	if(submitcheck('searchsubmit')) {
		$keyword = isset($keyword) ? trim($keyword) : '';
		if($keyword) {
			$sqlsrch = '';
			$searchtype = in_array($searchtype, array('all', 'title', 'message')) ? $searchtype : 'all';
			switch($searchtype) {
				case 'all':
					$sqlsrch = "WHERE title LIKE '%$keyword%' OR message LIKE '%$keyword%'";
					break;
				case 'title':
					$sqlsrch = "WHERE title LIKE '%$keyword%'";
					break;
				case 'message':
					$sqlsrch = "WHERE message LIKE '%$keyword%'";
					break;
			}

			$keyword = stripslashes($keyword);
			$faqlist = array();
			$query = $db->query("SELECT fpid, title, message FROM {$tablepre}faqs $sqlsrch ORDER BY displayorder");
			while($faq = $db->fetch_array($query)) {
				if(!empty($faq['fpid'])) {
					$faq['title'] = preg_replace("/(?<=[\s\"\]>()]|[\x7f-\xff]|^)(".preg_quote($keyword, '/').")(([.,:;-?!()\s\"<\[]|[\x7f-\xff]|$))/siU", "<u><b><font color=\"#FF0000\">\\1</font></b></u>\\2", stripslashes($faq['title']));
					$faq['message'] = preg_replace("/(?<=[\s\"\]>()]|[\x7f-\xff]|^)(".preg_quote($keyword, '/').")(([.,:;-?!()\s\"<\[]|[\x7f-\xff]|$))/siU", "<u><b><font color=\"#FF0000\">\\1</font></b></u>\\2", stripslashes($faq['message']));
					$faqlist[] = $faq;
				}
			}
		} else {
			showmessage('faq_keywords_empty', 'faq.php');
		}
	}

} elseif($action == 'credits') {

	if(empty($extcredits)) {
		showmessage('credits_disabled');
	}

	require_once DISCUZ_ROOT.'./include/forum.func.php';
	$forumlist = forumselect(FALSE, 0, $fid);

	$extgroups = array();
	if($forum) {
		$query = $db->query("SELECT * FROM {$tablepre}usergroups ORDER BY (creditshigher<>'0' || creditslower<>'0') DESC, creditslower");
		while($group = $db->fetch_array($query)) {
			$extgroups[$group['groupid']] = $group;
		}
		$perms = array('viewperm', 'postperm', 'replyperm', 'getattachperm', 'postattachperm');
		foreach($perms as $perm) {
			if($forum[$perm]) {
				$groupids = explode("\t", $forum[$perm]);
				foreach($groupids as $id) {
					if($id) {
						$extgroups[$id]['perm'][$perm] = 1;
					}
				}
			} else {
				foreach($extgroups as $id => $data) {
					if($id == 7 && ($perm == 'viewperm' || $perm == 'getattachperm') || $id != 7) {
						$extgroups[$id]['perm'][$perm] = 1;
					}
				}
			}
		}
	}

	$policyarray = array();
	foreach($creditspolicy as $operation => $policy) {
		!$forum && $policyarray[$operation] = $policy;
		if(in_array($operation, array('post', 'reply', 'digest', 'postattach', 'getattach'))) {
			if($forum) {
				$policyarray[$operation] = $forum[$operation.'credits'] ? $forum[$operation.'credits'] : $creditspolicy[$operation];
			}
		}
	}

	$creditsarray = array();
	for($i = 1; $i <= 8; $i++) {
		if(isset($extcredits[$i])) {
			foreach($policyarray as $operation => $policy) {
				$addcredits = in_array($operation, array('getattach', 'forum_getattach', 'sendpm', 'search')) ? -$policy[$i] : $policy[$i];
				if($operation != 'lowerlimit') {
					$creditsarray[$operation][$i] = empty($policy[$i]) ? 0 : (is_numeric($policy[$i]) ? '<b>'.($addcredits > 0 ? '+'.$addcredits : $addcredits).'</b> '.$extcredits[$i]['unit'] : $policy[$i]);
				} else {
					$creditsarray[$operation][$i] = '<b>'.intval($addcredits).'</b> '.$extcredits[$i]['unit'];
				}
			}
		}
	}

	if(!$forum) {
		$query = $db->query("SELECT * FROM {$tablepre}usergroups WHERE type='member' ORDER BY type");
		while($group = $db->fetch_array($query)) {
			$extgroups[] = $group;
		}
	}

	include template('credits');
	exit;

} elseif($action == 'grouppermission') {

	require_once './include/forum.func.php';
	require_once language('misc');
	$permlang = $language;
	unset($language);

	$searchgroupid = isset($searchgroupid) ? intval($searchgroupid) : $groupid;
	$groups = $grouplist = array();
	$query = $db->query("SELECT groupid, type, grouptitle, radminid FROM {$tablepre}usergroups ORDER BY (creditshigher<>'0' || creditslower<>'0'), creditslower");
	$cgdata = $nextgid = '';
	while($group = $db->fetch_array($query)) {
		$group['type'] = $group['type'] == 'special' && $group['radminid'] ? 'specialadmin' : $group['type'];
		$groups[$group['type']][] = array($group['groupid'], $group['grouptitle']);
		$grouplist[$group['type']] .= '<option value="'.$group['groupid'].'"'.($searchgroupid == $group['groupid'] ? ' selected="selected"' : '').'>'.$group['grouptitle'].($groupid == $group['groupid'] ? ' &larr;' : '').'</option>';
		if($group['groupid'] == $searchgroupid) {
			$cgdata = array($group['type'], count($groups[$group['type']]) - 1, $group['groupid']);
		}
	}
	if($cgdata[0] == 'member') {
		$nextgid = $groups[$cgdata[0]][$cgdata[1] + 1][0];
		if($cgdata[1] > 0) {
			$gids[1] = $groups[$cgdata[0]][$cgdata[1] - 1];
		}
		$gids[2] = $groups[$cgdata[0]][$cgdata[1]];
		if($cgdata[1] < count($groups[$cgdata[0]]) - 1) {
			$gids[3] = $groups[$cgdata[0]][$cgdata[1] + 1];
			if(count($gids) == 2) {
				$gids[4] = $groups[$cgdata[0]][$cgdata[1] + 2];
			}
		} elseif(count($gids) == 2) {
			$gids[0] = $groups[$cgdata[0]][$cgdata[1] - 2];
		}
	} else {
		$gids[1] = $groups[$cgdata[0]][$cgdata[1]];
	}
	ksort($gids);
	$groupids = array();
	foreach($gids as $row) {
		$groupids[] = $row[0];
	}

	$query = $db->query("SELECT * FROM {$tablepre}usergroups u LEFT JOIN {$tablepre}admingroups a ON u.groupid=a.admingid WHERE u.groupid IN (".implodeids($groupids).")");
	$groups = array();
	while($group = $db->fetch_array($query)) {
		$group['maxattachsize'] = $group['maxattachsize'] / 1024;
		$group['maxsizeperday'] = $group['maxsizeperday'] / 1024;
		$group['maxbiosize'] = $group['maxbiosize'] ? $group['maxbiosize'] : 200;
		if($searchgroupid == $group['groupid']) {
			$currenti = $group['groupid'];
		}
		$groups[$group['groupid']] = $group;
	}
	$newgroups = $groupbperms = $grouppperms = $groupaperms = array();
	foreach($groupids as $row) {
		$newgroups[$row] = $groups[$row];
	}
	$groups = $newgroups;unset($newgroups);
	$group = $groups[$currenti];
	$bperms = array('allowvisit','readaccess','allowviewpro','allowinvisible','allowsearch','allownickname','allowcstatus');
	$pperms = array('allowpost','allowreply','allowpostpoll','allowvote','allowpostreward','allowpostactivity','allowpostdebate','allowposttrade','maxsigsize','allowsigbbcode','allowsigimgcode','maxbiosize','allowbiobbcode','allowbioimgcode','allowrecommend');
	$aperms = array('allowgetattach', 'allowpostattach', 'allowsetattachperm', 'maxattachsize', 'maxsizeperday', 'attachextensions');
	foreach(array('bperms', 'pperms', 'aperms') as $xperms) {
		foreach($$xperms as $xperm) {
			$prevv = '';
			if(empty($view)) {
				$issame = TRUE;
				if($cgdata[0] == 'member') {
					$kn = 0;
					foreach($groups as $k => $row) {
						if($kn > 0) {
							if($prevv != $row[$xperm]) {
								$issame = FALSE;
							}
						}
						$prevv = $row[$xperm];
						$kn++;
					}
				} else {
					$issame = FALSE;
				}
			} else {
				$issame = FALSE;
			}
			if(!$issame) {
				${'group'.$xperms}[] = $xperm;
			}
		}
	}
	$viewextra = !empty($view) ? '&amp;view=all' : '';

	include_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
	$perms = array('viewperm', 'postperm', 'replyperm', 'getattachperm', 'postattachperm');
	$query = $db->query("SELECT fid, viewperm, postperm, replyperm, getattachperm, postattachperm FROM {$tablepre}forumfields");
	while($forum = $db->fetch_array($query)) {
		foreach($perms as $perm) {
			if($forum[$perm]) {
				$groupids = explode("\t", $forum[$perm]);
				if(in_array($searchgroupid, $groupids)) {
					$forumperm[$forum['fid']][$perm] = 1;
				}
			} elseif($searchgroupid == 7 && ($perm == 'viewperm' || $perm == 'getattachperm') || $searchgroupid != 7) {
				$forumperm[$forum['fid']][$perm] = 1;
			}
		}
	}

	foreach($_DCACHE['forums'] as $fid => $tmpforum) {
		switch($tmpforum['type']) {
			case 'group':
				if(!$tmpforum['status']) {
					unset($_DCACHE['forums'][$fid]);
				}
				break;
			case 'forum':
				$fup = $tmpforum['fup'];
				if(!$tmpforum['status'] || !$_DCACHE['forums'][$fup]['status']) {
					unset($_DCACHE['forums'][$fid]);
				}
				break;
			case 'sub':
				$fup = $tmpforum['fup'];
				$fupup = $_DCACHE['forums'][$fup]['fup'];
				if(!$tmpforum['status'] || !$_DCACHE['forums'][$fup]['status'] || !$_DCACHE['forums'][$fupup]['status']) {
					unset($_DCACHE['forums'][$fid]);
				}
				break;
		}
	}
	include template('my_grouppermission');
	exit;

} elseif($action == 'plugin' && !empty($id)) {

	list($identifier, $module) = explode(':', $id);
	if(!is_array($plugins['faq']) || !array_key_exists($id, $plugins['faq'])) {
		showmessage('undefined_action');
	}
	$directory = $plugins['faq'][$id]['directory'];
	if(empty($identifier) || !preg_match("/^[a-z]+[a-z0-9_]*\/$/i", $directory) || !preg_match("/^[a-z0-9_\-]+$/i", $module)) {
		showmessage('undefined_action');
	}
	if(@!file_exists(DISCUZ_ROOT.($modfile = './plugins/'.$directory.$module.'.inc.php'))) {
		showmessage('plugin_module_nonexistence');
	}

	if(@in_array($identifier, $pluginlangs)) {
		@include_once DISCUZ_ROOT.'./forumdata/cache/cache_scriptlang.php';
	}

	$navigation = '&raquo; '.$plugins['faq'][$id]['name'];
	$faqtpl = $identifier.':'.$module;

	include DISCUZ_ROOT.$modfile;

}

include template('faq');

?>