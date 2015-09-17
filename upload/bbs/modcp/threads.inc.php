<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: threads.inc.php 21059 2009-11-10 01:28:17Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_MODCP')) {
	exit('Access Denied');
}

$op = !in_array($op , array('threads', 'posts')) ? 'threads' : $op;
$do = !empty($do) ? dhtmlspecialchars($do) : '';

$modtpl = $op ==  'posts' ? 'modcp_posts' : 'modcp_threads';


if($op == 'threads') {

	if($fid && $forum['ismoderator']) {

		$result = array();
		foreach (array('threadoption', 'viewsless', 'viewsmore', 'repliesless', 'repliesmore', 'noreplydays') as $key) {
			$$key = isset($$key) && is_numeric($$key) ? intval($$key) : '';
			$result[$key] = $$key;
		}

		foreach (array('starttime', 'endtime', 'keywords', 'users') as $key) {
			$result[$key] = isset($$key) ? dhtmlspecialchars($$key) : '';
		}

		$threadoptionselect = array($threadoption => 'selected');

		if($do == 'search' &&  submitcheck('submit')) {

			$sql = '';

			if($threadoption > 0 && $threadoption < 255) {
				$sql .= " AND special='$threadoption'";
			} elseif($threadoption == 999) {
				$sql .= " AND digest in(1,2,3)";
			} elseif($threadoption == 888) {
				$sql .= " AND displayorder IN(1,2,3)";
			}

			$sql .= $viewsless !== ''? " AND views<='$viewsless'" : '';
			$sql .= $viewsmore !== ''? " AND views>='$viewsmore'" : '';
			$sql .= $repliesless !== ''? " AND replies<='$repliesless'" : '';
			$sql .= $repliesmore !== ''? " AND replies>='$repliesmore'" : '';
			$sql .= $noreplydays !== ''? " AND lastpost<='$timestamp'-'$noreplydays'*86400" : '';
			$sql .= $starttime != '' ? " AND dateline>='".strtotime($starttime)."'" : '';
			$sql .= $endtime != '' ? " AND dateline<='".strtotime($endtime)."'" : '';

			if(trim($keywords)) {
				$sqlkeywords = '';
				$or = '';
				$keywords = explode(',', str_replace(' ', '', $keywords));
				for($i = 0; $i < count($keywords); $i++) {
					$sqlkeywords .= " $or subject LIKE '%".$keywords[$i]."%'";
					$or = 'OR';
				}
				$sql .= " AND ($sqlkeywords)";

				$keywords = implode(', ', $keywords);
			}

			if(trim($users)) {
				$sql .= " AND author IN ('".str_replace(',', '\',\'', str_replace(' ', '', trim($users)))."')";
			}

			if($sql) {

				$query = $db->query("SELECT tid FROM {$tablepre}threads WHERE fid='$fid' AND displayorder>=0 $sql ORDER BY displayorder DESC, lastpost DESC LIMIT 1000");
				$tids = $comma = '';
				$count = 0;
				while ($tid = $db->fetch_array($query)) {
					$tids .= $comma.$tid['tid'];
					$comma = ',';
					$count ++;
				}

				$result['tids'] = $tids;
				$result['count'] = $count;
				$result['fid'] = $fid;

				$modsession->set('srchresult', $result, true);

				$db->free_result($query);
				unset($result, $tids);
				$do = 'list';
				$page = 1;

			} else {
				$do = '';
			}
		}

		$page = max(1, intval($page));
		$total = 0;
		$query = $multipage = '';

		if(empty($do)) {

			$total = $db->result_first("SELECT count(*) FROM {$tablepre}threads WHERE fid='$fid' AND displayorder>=0");
			$tpage = ceil($total / $tpp);
			$page = min($tpage, $page);
			$multipage = multi($total, $tpp, $page, "$cpscript?action=$action&amp;op=$op&amp;fid=$fid&amp;do=$do");
			if($total) {
				$start = ($page - 1) * $tpp;
				$query = $db->query("SELECT * FROM {$tablepre}threads WHERE fid='$fid' AND displayorder>=0 ORDER BY displayorder DESC, lastpost DESC LIMIT $start, $tpp");
			}

		} else {

			$result = $modsession->get('srchresult');
			$threadoptionselect = array($result['threadoption'] => 'selected');

			if($result['fid'] == $fid) {
				$total = $result['count'];
				$tpage = ceil($total / $tpp);
				$page = min($tpage, $page);
				$multipage = multi($total, $tpp, $page, "$cpscript?action=$action&amp;op=$op&amp;fid=$fid&amp;do=$do");
				if($total) {
					$start = ($page - 1) * $tpp;
					$query = $db->query("SELECT * FROM {$tablepre}threads WHERE tid in($result[tids]) ORDER BY lastpost DESC LIMIT $start, $tpp");
				}
			}
		}

		$postlist = array();
		if($query) {
			require_once DISCUZ_ROOT.'./include/misc.func.php';
			while ($thread = $db->fetch_array($query)) {
				$postlist[] = procthread($thread);
			}
		}

	}

	return;
}


if($op == 'posts') {

	$error = 0;

	$result = array();
	$result['threadoption'] = intval($threadoption);

	$starttime = !preg_match("/^(0|\d{4}\-\d{1,2}\-\d{1,2})$/", $starttime) ? gmdate('Y-n-j', $timestamp + $timeoffset * 3600 - 86400 * ($adminid == 2 ? 13 : ($adminid == 3 ? 6 : 60))) : $starttime;
	$endtime = $adminid == 3 || !preg_match("/^(0|\d{4}\-\d{1,2}\-\d{1,2})$/", $endtime) ? gmdate('Y-n-j', $timestamp + $timeoffset * 3600) : $endtime;

	foreach (array('starttime', 'endtime', 'keywords', 'users', 'useip') as $key) {
		$$key = isset($$key) ? trim($$key) : '';
		$result[$key] = dhtmlspecialchars($$key);
	}

	$threadoptionselect = array($threadoption => 'selected');

	$fidadd = '';
	if($fid && $modforums['list'][$fid]) {
		$fidadd = "AND fid='$fid'";
	} else {
		if($adminid == 1 && $adminid == $groupid) {
			$fidadd = '';
		} elseif(!$modforums['fids']) {
			$fidadd = 'AND 0 ';
		} else {
			$fidadd = "AND fid in($modforums[fids])";
		}
	}

	if($do == 'delete' && submitcheck('deletesubmit')) {

		if(!$allowmassprune) {
			$error = 4;
			return;
		}

		$tidsdelete = $pidsdelete = '0';
		$prune = array();

		if($pids = implodeids($delete)) {
			$tidsdelete = $pidsdelete = '0';
			$query = $db->query("SELECT fid, tid, pid, first, authorid FROM {$tablepre}posts WHERE pid IN ($pids) $fidadd");
			while($post = $db->fetch_array($query)) {
				$prune['forums'][] = $post['fid'];
				$prune['thread'][$post['tid']]++;

				$pidsdelete .= ",$post[pid]";
				$tidsdelete .= $post['first'] ? ",$post[tid]" : '';
			}
		}

		if($pidsdelete) {
			require_once DISCUZ_ROOT.'./include/post.func.php';

			$query = $db->query("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE pid IN ($pidsdelete) OR tid IN ($tidsdelete)");
			while($attach = $db->fetch_array($query)) {
				dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
			}

			if(!$nocredit) {
				$postsarray = $tuidarray = $ruidarray = array();
				$query1 = $db->query("SELECT pid, first, authorid FROM {$tablepre}posts WHERE pid IN ($pidsdelete)");
				$query2 = $db->query("SELECT pid, first, authorid FROM {$tablepre}posts WHERE tid IN ($tidsdelete)");
				while(($post = $db->fetch_array($query1)) || ($post = $db->fetch_array($query2))) {
					$postsarray[$post['pid']] = $post;
				}
				foreach($postsarray as $post) {
					if($post['first']) {
						$tuidarray[] = $post['authorid'];
					} else {
						$ruidarray[] = $post['authorid'];
					}
				}
				if($tuidarray) {
					updatepostcredits('-', $tuidarray, $creditspolicy['post']);
				}
				if($ruidarray) {
					updatepostcredits('-', $ruidarray, $creditspolicy['reply']);
				}
			}

			$db->query("DELETE FROM {$tablepre}attachments WHERE pid IN ($pidsdelete)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}attachmentfields WHERE pid IN ($pidsdelete)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}attachments WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}attachmentfields WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}threadsmod WHERE tid IN ($tidsdelete)", 'UNBUFFERED');
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

			if(count($prunt['thread']) < 50) {
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

			foreach(array_unique($prune['forums']) as $id) {
				updateforumcount($id);
			}

		}


		$do = 'list';
	}

	if($do == 'search' && submitcheck('searchsubmit', 1)) {

		if(($starttime == '0' && $endtime == '0') || ($keywords == '' && $useip == '' && $users == '')) {
			$error = 1;
			return ;
		}

		$sql = '';

		if($threadoption == 1) {
			$sql .= " AND first='1'";
		} elseif($threadoption == 2) {
			$sql .= " AND first='0'";
		}

		if($starttime != '0') {
			$starttime = strtotime($starttime);
			$sql .= " AND dateline>'$starttime'";
		}

		if($adminid == 1 && $endtime != gmdate('Y-n-j', $timestamp + $timeoffset * 3600)) {
			if($endtime != '0') {
				$endtime = strtotime($endtime);
				$sql .= " AND dateline<'$endtime'";
			}
		} else {
			$endtime = $timestamp;
		}

		if(($adminid == 2 && $endtime - $starttime > 86400 * 14) || ($adminid == 3 && $endtime - $starttime > 86400 * 7)) {
			$error = '2';
			return;
		}

		if($users != '') {
			$uids = $comma = '';
			$query = $db->query("SELECT uid FROM {$tablepre}members WHERE username IN ('".str_replace(',', '\',\'', str_replace(' ', '', $users))."')");
			while($member = $db->fetch_array($query)) {
				$uids .= $comma.$member[uid]; $comma = ',';
			}
			if($uids) {
				$sql .= " AND authorid IN ($uids)";
			}
		}

		if(trim($keywords)) {
			$sqlkeywords = '';
			$or = '';
			$keywords = explode(',', str_replace(' ', '', $keywords));
			for($i = 0; $i < count($keywords); $i++) {
				if(strlen($keywords[$i]) > 3) {
					$sqlkeywords .= " $or message LIKE '%".$keywords[$i]."%'";
					$or = 'OR';
				} else {
					$error = 3;
					return ;
				}
			}
			$sql .= " AND ($sqlkeywords)";
		}

		$useip = trim($useip);
		if($useip != '') {
			$sql .= " AND useip LIKE '".str_replace('*', '%', $useip)."'";
		}

		if($sql) {

			$query = $db->query("SELECT pid FROM {$tablepre}posts WHERE 1 $fidadd $sql ORDER BY dateline DESC LIMIT 1000");
			$pids = $comma = '';
			$count = 0;
			while ($pid = $db->fetch_array($query)) {
				$pids .= $comma.$pid['pid'];
				$comma = ',';
				$count ++;
			}

			$result['pids'] = $pids;
			$result['count'] = $count;
			$result['fid'] = $fid;

			$modsession->set('srchresult_p'.$fid, $result, true);

			$db->free_result($query);
			unset($result, $pids);
			$do = 'list';
			$page = 1;

		} else {
			$do = '';
		}
	}

	$page = max(1, intval($page));
	$total = 0;
	$query = $multipage = '';

	if($do == 'list') {

		$result = $modsession->get('srchresult_p'.$fid);
		$threadoptionselect = array($result['threadoption'] => 'selected');

		if($result['fid'] == $fid) {
			$total = $result['count'];
			$tpage = ceil($total / $tpp);
			$page = min($tpage, $page);
			$multipage = multi($total, $tpp, $page, "$cpscript?action=$action&amp;op=$op&amp;fid=$fid&amp;do=$do");
			if($total && $result[pids]) {
				$start = ($page - 1) * $tpp;
				$query = $db->query("SELECT p.*, t.subject as tsubject FROM {$tablepre}posts p LEFT JOIN {$tablepre}threads t USING(tid) WHERE pid in($result[pids]) ORDER BY dateline DESC LIMIT $start, $tpp");
			}
		}
	}

	$postlist = array();

	if($query) {
		require_once DISCUZ_ROOT.'./include/post.func.php';
		while ($post = $db->fetch_array($query)) {
			$post['dateline'] = gmdate("$dateformat $timeformat", $post['dateline'] + $timeoffset * 3600);
			$post['message'] = messagecutstr($post['message'], 200);
			$post['forum'] = $modforums['list'][$post[fid]];
			$post['modthreadkey'] = modthreadkey($post['tid']);
			$postlist[] = $post;
		}
	}

}

?>