<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: my.php 21055 2009-11-10 00:33:11Z monkey $
*/

define('NOROBOT', TRUE);
define('CURSCRIPT', 'my');
require_once './include/common.inc.php';

require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
require_once DISCUZ_ROOT.'./include/forum.func.php';
if($my_status && $from) {
	$funcstatinfo[] = 'manyou2dz,20090727';
}
$discuz_action = 8;
if(!$discuz_uid) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

$page = max(1, intval($page));
$start_limit = ($page - 1) * $tpp;

$threadlist = $postlist = array();
$tids = $comma = $threadadd = $postadd = $forumname =$uidadd =  $extrafid = $extra = $multipage = '';

if($srchfid = empty($srchfid) ? 0 : intval($srchfid)) {
	$threadadd = "AND t.fid='$srchfid'";
	$postadd = "AND p.fid='$srchfid'";
	$forumname = $_DCACHE['forums'][$srchfid]['name'];
	$extrafid = '&amp;srchfid='.$srchfid;
}
$forumselect = forumselect(FALSE, 0, $srchfid);

$item = isset($item) ? trim($item) : '';

$uid = !empty($uid) && $uid != $discuz_uid && $allowviewuserthread ? $uid : 0;
if($uid) {
	if(!$allowviewpro) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	}
	$member = $db->fetch_first("SELECT username FROM {$tablepre}members WHERE uid='$uid'");
	if(!$member) {
		header("HTTP/1.0 404 Not Found");
		showmessage('member_nonexistence');
	}
	$uidadd = '&amp;uid='.$uid;
}

if(empty($item) || $item == 'threads') {

	if($uid) {
		$threadadd .= " AND fid IN ($allowviewuserthread)";
		$srchuid = $uid;
		$filter = '';
	} else {
		$srchuid = $discuz_uid;
	}
	if($filter == 'recyclebin') {
		$threadadd .= " AND displayorder='-1'";
	} elseif($filter == 'aduit') {
		$threadadd .= " AND displayorder='-2'";
	} elseif($filter == 'close') {
		$threadadd .= " AND closed='1'";
	} elseif($filter == 'common') {
		$threadadd .= " AND displayorder>='0' AND closed='0'";
	}
	$fidadd = $srchfid ? "&amp;srchfid=$srchfid" : '';

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads t WHERE authorid='$srchuid' $threadadd");
	$multipage = multi($num, $tpp, $page, 'my.php?item=threads'.$uidadd.$fidadd.$extrafid);

	$query = $db->query("SELECT * FROM {$tablepre}threads t WHERE authorid='$srchuid' $threadadd ORDER BY dateline DESC LIMIT $start_limit, $tpp");
	require_once DISCUZ_ROOT.'./include/misc.func.php';
	while($thread = $db->fetch_array($query)) {
		$threadlist[] = procthread($thread);
	}

} elseif($item == 'posts') {

	if($uid) {
		$threadadd .= " AND t.fid IN ($allowviewuserthread)";
		$srchuid = $uid;
		$filter = '';
	} else {
		$srchuid = $discuz_uid;
	}
	if($filter == 'recyclebin') {
		$postadd .= " AND p.invisible='-1'";
	} elseif($filter == 'aduit') {
		$postadd .= " AND p.invisible='-2'";
	} elseif($filter == 'close') {
		$threadadd .= " AND t.closed='1'";
	} elseif($filter == 'common') {
		$postadd .= " AND p.invisible='0'";
		$threadadd .= " AND t.displayorder>='0' AND t.closed='0'";
	}
	$fidadd = $srchfid ? "&amp;srchfid=$srchfid" : '';

	require_once DISCUZ_ROOT.'./include/post.func.php';

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts p
		INNER JOIN {$tablepre}threads t ON t.tid=p.tid $threadadd
		WHERE p.authorid='$srchuid' $postadd");
	$multipage = multi($num, $tpp, $page, 'my.php?item=posts'.$uidadd.$fidadd.$extrafid);

	$query = $db->query("SELECT p.authorid, p.tid, p.pid, p.fid, p.invisible, p.dateline, p.message, t.status FROM {$tablepre}posts p
		INNER JOIN {$tablepre}threads t ON t.tid=p.tid $threadadd
		WHERE p.authorid='$srchuid' $postadd ORDER BY p.dateline DESC LIMIT $start_limit, $tpp");
	$tids = $threads = array();
	while($post = $db->fetch_array($query)) {
		$hiddenreplies = $srchuid == $discuz_uid ? 0 : getstatus($post['status'], 2);
		$post['dateline'] = dgmdate("$dateformat $timeformat", $post['dateline'] + $timeoffset * 3600);
		$post['forumname'] = $_DCACHE['forums'][$post['fid']]['name'];
		$post['message'] = !$hiddenreplies ? messagecutstr($post['message'], 100) : '';
		$postlist[] = $post;
		$tids[$post['tid']] = $post['tid'];
	}

	if($tids) {
		require_once DISCUZ_ROOT.'./include/misc.func.php';
		$query = $db->query("SELECT * FROM {$tablepre}threads WHERE tid IN (".implodeids($tids).")");
		while($thread = $db->fetch_array($query)) {
			$threads[$thread['tid']] = procthread($thread);
		}
	}

} elseif($item == 'favorites') {

	if($fid && empty($forum['allowview'])) {
		if(!$forum['viewperm'] && !$readaccess) {
			showmessage('group_nopermission', NULL, 'NOPERM');
		} elseif($forum['viewperm'] && !forumperm($forum['viewperm'])) {
			showmessage('forum_nopermission', NULL, 'NOPERM');
		}
	}

	$ftid = $type == 'thread' || $tid ? 'tid' : 'fid';
	$type = $type == 'thread' || $tid ? 'thread' : 'forum';
	$extra .= $srchfid ? '&amp;type='.$type : '';
	$action = empty($action) ? '' : $action;

	if($action == 'remove' && ($tid || $fid)) {
		if($tid) {
			$db->query("DELETE FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND tid='$tid'", 'UNBUFFERED');
			showmessage('favorite_remove_thread_succeed', dreferer());
		} else {
			$db->query("DELETE FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND fid='$fid'", 'UNBUFFERED');
			showmessage('favorite_remove_forum_succeed', dreferer());
		}
	} elseif(($fid || $tid) && !submitcheck('favsubmit')) {

		if($db->result_first("SELECT $ftid FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND $ftid='${$ftid}' LIMIT 1")) {
			if($tid) {
				showmessage('favorite_thread_exists');
			} else {
				showmessage('favorite_forum_exists');
			}

		} else {
			$db->query("INSERT INTO {$tablepre}favorites (uid, $ftid) VALUES ('$discuz_uid', '${$ftid}')");
			if($tid) {
				showmessage('favorite_add_thread_succeed', dreferer());
			} else {
				showmessage('favorite_add_forum_succeed', dreferer());
			}
		}

	} elseif(!$fid && !$tid) {

		if(!submitcheck('favsubmit')) {

			$favlist = array();
			if($type == 'forum') {
				$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}favorites fav, {$tablepre}forums f
					WHERE fav.uid = '$discuz_uid' AND fav.fid=f.fid");
				$multipage = multi($num, $tpp, $page, "my.php?item=favorites&amp;type=forum$extrafid");

				$query = $db->query("SELECT f.fid, f.name, f.threads, f.posts, f.todayposts, f.lastpost
					FROM {$tablepre}favorites fav, {$tablepre}forums f
					WHERE fav.fid=f.fid AND fav.uid='$discuz_uid' ORDER BY f.lastpost DESC LIMIT $start_limit, $tpp");

				while($fav = $db->fetch_array($query)) {
					$fav['lastposterenc'] = rawurlencode($fav['lastposter']);
					$fav['lastpost'] = dgmdate("$dateformat $timeformat", $fav['lastpost'] + $timeoffset * 3600);
					$favlist[] = $fav;
				}
			} else {
				$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}favorites fav, {$tablepre}threads t
					WHERE fav.uid = '$discuz_uid' AND fav.tid=t.tid AND t.displayorder>='0' $threadadd");
				$multipage = multi($num, $tpp, $page, "my.php?item=favorites&amp;type=thread$extrafid");

				$query = $db->query("SELECT t.tid, t.fid, t.subject, t.replies, t.lastpost, t.lastposter, f.name
					FROM {$tablepre}favorites fav, {$tablepre}threads t, {$tablepre}forums f
					WHERE fav.tid=t.tid AND t.displayorder>='0' AND fav.uid='$discuz_uid' AND t.fid=f.fid $threadadd
					ORDER BY t.lastpost DESC LIMIT $start_limit, $tpp");

				while($fav = $db->fetch_array($query)) {
					$fav['lastposterenc'] = rawurlencode($fav['lastposter']);
					$fav['lastpost'] = dgmdate("$dateformat $timeformat", $fav['lastpost'] + $timeoffset * 3600);
					$favlist[] = $fav;
				}
			}

		} else {

			if($ids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}favorites WHERE uid='$discuz_uid' AND $ftid IN ($ids)", 'UNBUFFERED');
				write_statlog('', 'item=favorites&action=deletefav', '', '', 'my.php');
			}
			showmessage('favorite_update_succeed', dreferer());
		}

	}

} elseif($item == 'selltrades' || $item == 'buytrades') {

	require_once DISCUZ_ROOT.'./include/trade.func.php';
	include_once language('misc');

	$sqlfield = $item == 'selltrades' ? 'sellerid' : 'buyerid';
	$sqlfilter = '';
	switch($filter) {
		case 'attention': $typestatus = $item; break;
		case 'eccredit'	: $typestatus = 'eccredittrades';
				  $sqlfilter .= $item == 'selltrades' ? 'AND (tl.ratestatus=0 OR tl.ratestatus=1) ' : 'AND (tl.ratestatus=0 OR tl.ratestatus=2) ';
				  break;
		case 'all'	: $typestatus = ''; break;
		case 'success'	: $typestatus = 'successtrades'; break;
		case 'closed'	: $typestatus = 'closedtrades'; break;
		case 'refund'	: $typestatus = 'refundtrades'; break;
		case 'unstart'	: $typestatus = 'unstarttrades'; break;
		default		: $typestatus = 'tradingtrades'; $filter = '';
	}

	$sqlfilter .= $typestatus ? 'AND tl.status IN (\''.trade_typestatus($typestatus).'\')' : '';

	if(!empty($srchkey)) {
		$sqlkey = 'AND tl.subject like \'%'.str_replace('*', '%', addcslashes($srchkey, '%_')).'%\'';
		$extrasrchkey = '&srchkey='.rawurlencode($srchkey);
		$srchkey = dhtmlspecialchars($srchkey);
	} else {
		$sqlkey = $extrasrchkey = $srchkey = '';
	}

	$pid = intval($pid);
	$sqltid = $tid ? 'AND tl.tid=\''.$tid.'\''.($pid ? ' AND tl.pid=\''.$pid.'\'' : '') : '';
	$extra .= $srchfid ? '&amp;filter='.$filter : '';
	$extratid = $tid ? "&amp;tid=$tid".($pid ? "&amp;pid=$pid" : '') : '';

	$num = $db->result_first("SELECT COUNT(*)
			FROM {$tablepre}tradelog tl, {$tablepre}threads t
			WHERE tl.tid=t.tid AND tl.$sqlfield='$discuz_uid'
			$threadadd $sqltid $sqlkey $sqlfilter");

	$multipage = multi($num, $tpp, $page, "my.php?item=$item$extratid$extrafid".($filter ? "&amp;filter=$filter" : '').$extrafid.$extrasrchkey);

	$query = $db->query("SELECT tl.*, tr.aid, t.subject AS threadsubject
			FROM {$tablepre}tradelog tl, {$tablepre}threads t, {$tablepre}trades tr
			WHERE tl.tid=t.tid AND tr.pid=tl.pid AND tr.tid=tl.tid AND tl.$sqlfield='$discuz_uid'
			$threadadd $sqltid $sqlkey $sqlfilter
			ORDER BY tl.lastupdate DESC LIMIT $start_limit, $tpp");

	$tradeloglist = array();
	while($tradelog = $db->fetch_array($query)) {
		$tradelog['lastupdate'] = dgmdate("$dateformat $timeformat", $tradelog['lastupdate'] + $timeoffset * 3600);
		$tradelog['attend'] = trade_typestatus($item, $tradelog['status']);
		$tradelog['status'] = trade_getstatus($tradelog['status']);
		$tradeloglist[] = $tradelog;
	}
} elseif($item	== 'tradestats') {

	$extrasrchkey = $extratid = '';

	require_once DISCUZ_ROOT.'./include/trade.func.php';

	$buystats = $db->fetch_first("SELECT COUNT(*) AS totalitems, SUM(price) AS tradesum FROM {$tablepre}tradelog WHERE buyerid='$discuz_uid' AND status IN ('".trade_typestatus('successtrades')."')");

	$sellstats = $db->fetch_first("SELECT COUNT(*) AS totalitems, SUM(price) AS tradesum FROM {$tablepre}tradelog WHERE sellerid='$discuz_uid' AND status IN ('".trade_typestatus('successtrades')."')");

	$query = $db->query("SELECT status FROM {$tablepre}tradelog WHERE buyerid='$discuz_uid' AND status IN ('".trade_typestatus('buytrades')."')");
	$buyerattend = $db->num_rows($query);
	$attendstatus = array();
	while($status = $db->fetch_array($query)) {
		@$attendstatus[$status['status']]++;
	}

	$query = $db->query("SELECT status FROM {$tablepre}tradelog WHERE sellerid='$discuz_uid' AND status IN ('".trade_typestatus('selltrades')."')");
	$sellerattend = $db->num_rows($query);
	while($status = $db->fetch_array($query)) {
		@$attendstatus[$status['status']]++;
	}

	$goodsbuyer = $db->result_first("SELECT COUNT(*) FROM {$tablepre}tradelog WHERE buyerid='$discuz_uid' AND status IN ('".trade_typestatus('tradingtrades')."')");
	$goodsseller = $db->result_first("SELECT COUNT(*) FROM {$tablepre}trades WHERE sellerid='$discuz_uid' AND closed='0'");
	$eccreditbuyer = $db->result_first("SELECT COUNT(*) FROM {$tablepre}tradelog WHERE status IN ('".trade_typestatus('eccredittrades')."') AND buyerid='$discuz_uid' AND (ratestatus=0 OR ratestatus=2)");
	$eccreditseller = $db->result_first("SELECT COUNT(*) FROM {$tablepre}tradelog WHERE status IN ('".trade_typestatus('eccredittrades')."') AND sellerid='$discuz_uid' AND (ratestatus=0 OR ratestatus=1)");

} elseif($item == 'tradethreads') {

	if(!empty($srchkey)) {
		$sqlkey = 'AND subject like \'%'.str_replace('*', '%', addcslashes($srchkey, '%_')).'%\'';
		$extrasrchkey = '&srchkey='.rawurlencode($srchkey);
		$srchkey = dhtmlspecialchars($srchkey);
	} else {
		$sqlkey = $extrasrchkey = $srchkey = '';
	}

	$sqltid = $tid ? 'AND tid ='.$tid : '';
	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}trades WHERE sellerid='$discuz_uid' $sqltid $sqlkey");
	$extratid = $tid ? "&amp;tid=$tid" : '';
	$multipage = multi($num, $tpp, $page, "my.php?item=tradethreads$extratid$extrafid$extrasrchkey");

	$tradelist = array();
	$query = $db->query("SELECT * FROM {$tablepre}trades WHERE sellerid='$discuz_uid' $sqltid $sqlkey ORDER BY tradesum DESC, totalitems DESC LIMIT $start_limit, $tpp");
	while($tradethread = $db->fetch_array($query)) {
		$tradethread['lastupdate'] = dgmdate("$dateformat $timeformat", $tradethread['lastupdate'] + $timeoffset * 3600);
		$tradethread['lastbuyer'] = rawurlencode($tradethread['lastbuyer']);
		if($tradethread['expiration']) {
			$tradethread['expiration'] = ($tradethread['expiration'] - $timestamp) / 86400;
			if($tradethread['expiration'] > 0) {
				$tradethread['expirationhour'] = floor(($tradethread['expiration'] - floor($tradethread['expiration'])) * 24);
				$tradethread['expiration'] = floor($tradethread['expiration']);
			} else {
				$tradethread['expiration'] = -1;
			}
		}
		$tradelist[] = $tradethread;
	}
} elseif($item == 'reward') {

	$rewardloglist = Array();

	if($type == 'stats') {

		$questions = $db->fetch_first("SELECT COUNT(*) AS total, SUM(ABS(netamount)) AS totalprice FROM {$tablepre}rewardlog WHERE authorid='$discuz_uid'");
		$questions['total'] = $questions['total'] ? $questions['total'] : 0;
		$questions['totalprice'] = $questions['totalprice'] ? $questions['totalprice'] : 0;
		$questions['solved'] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}rewardlog WHERE authorid='$discuz_uid' and answererid>0");
		$questions['percent'] = number_format(($questions['total'] != 0) ? round($questions['solved'] / $questions['total'] * 100, 2) : 0, 2, '.', '');
		$answers['total'] = $db->result_first("SELECT COUNT(*) FROM {$tablepre}rewardlog WHERE answererid='$discuz_uid'");
		$answeradopted = $db->fetch_first("SELECT COUNT(*) AS tids, SUM(ABS(t.price)) AS totalprice
			FROM {$tablepre}rewardlog r
			LEFT JOIN {$tablepre}threads t USING(tid)
			WHERE r.authorid>0 and r.answererid='$discuz_uid'");
		$answers['adopted'] = $answeradopted['tids'];
		$answers['totalprice'] = $answeradopted['totalprice'] ? $answeradopted['totalprice'] : 0;
		$answers['percent'] = number_format(($answers['total'] != 0) ? round($answers['adopted'] / $answers['total'] * 100, 2) : 0, 2, '.', '');

	} elseif($type == 'answer') {

		$extra .= $srchfid ? '&amp;type=answer' : '';
		$filter = isset($filter) && in_array($filter, array('adopted', 'unadopted')) ? $filter : '';
		$sqlfilter = empty($filter) ? '' : ($filter == 'adopted' ? 'AND r.authorid>0' : 'AND r.authorid=0');

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;
		$multipage = multi($db->result_first("SELECT COUNT(*) FROM {$tablepre}rewardlog r WHERE answererid='$discuz_uid'"), $tpp, $page, "my.php?item=reward&amp;type=answer$extrafid");

		require_once DISCUZ_ROOT.'./include/misc.func.php';
		$query = $db->query("SELECT r.*, t.*, m.uid, m.username, f.fid, f.name
			FROM {$tablepre}rewardlog r
			LEFT JOIN {$tablepre}threads t ON t.tid=r.tid
			LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
			LEFT JOIN {$tablepre}members m ON m.uid=t.authorid
			WHERE r.answererid='$discuz_uid' $threadadd $sqlfilter
			ORDER BY r.dateline DESC
			LIMIT $start_limit, $tpp");
		while($rewardlog = $db->fetch_array($query)) {
			$rewardlog['price'] = abs($rewardlog['price']);
			$rewardloglist[] = procthread($rewardlog);
		}

	} elseif($type == 'question') {

		$extra .= $srchfid ? '&amp;type=question' : '';
		$filter = in_array($filter, array('solved', 'unsolved')) ? $filter : '';
		$sqlfilter = empty($filter) ? '' : ($filter == 'solved' ? 'AND r.answererid>0' : 'AND r.answererid=0');

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;
		$multipage = multi($db->result_first("SELECT COUNT(*) FROM {$tablepre}rewardlog r WHERE authorid='$discuz_uid' $sqlfilter"), $tpp, $page, "my.php?item=reward&amp;type=question&amp;filter=$filter$extrafid");

		require_once DISCUZ_ROOT.'./include/misc.func.php';
		$query = $db->query("SELECT r.*, t.*, m.uid, m.username, f.fid, f.name
			FROM {$tablepre}rewardlog r
			LEFT JOIN {$tablepre}threads t ON t.tid=r.tid
			LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
			LEFT JOIN {$tablepre}members m ON m.uid=r.answererid
			WHERE r.authorid='$discuz_uid' $threadadd $sqlfilter
			ORDER BY r.dateline DESC
			LIMIT $start_limit, $tpp");
		while($rewardlog = $db->fetch_array($query)) {
			$rewardlog['price'] = abs($rewardlog['price']);
			$rewardloglist[] = procthread($rewardlog);
		}

	} else {
		showmessage('undefined_action');
	}

} elseif($item == 'activities') {

	$ended = isset($ended) && in_array($ended, array('yes', 'no')) ? $ended : '';
	$sign = $ascadd = '';
	$activity = array();

	if($type == 'orig') {

		if($ended == 'yes') {
			$sign = " AND starttimefrom<'$timestamp'";
			$ascadd = 'DESC';
		} elseif($ended == 'no') {
			$sign = " AND starttimefrom>='$timestamp'";
		}

		$extra .= $srchfid ? '&amp;type=orig' : '';

		$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}activities a LEFT JOIN {$tablepre}threads t USING(tid) WHERE a.uid='$discuz_uid' AND t.special='4' $threadadd $sign");
		$multipage = multi($num, $tpp, $page, "my.php?item=activities&amp;type=orig&amp;ended=$ended$extrafid");

		require_once DISCUZ_ROOT.'./include/misc.func.php';
		$query = $db->query("SELECT a.*, t.* FROM {$tablepre}activities a LEFT JOIN {$tablepre}threads t USING(tid) WHERE a.uid='$discuz_uid' AND t.special='4' $threadadd $sign ORDER BY starttimefrom $ascadd LIMIT $start_limit, $tpp");
		while($tempact = $db->fetch_array($query)) {
			$tempact['starttimefrom'] = dgmdate("$dateformat $timeformat", $tempact['starttimefrom'] + $timeoffset * 3600);
			$activity[] = procthread($tempact);
		}
	} else {

		if($ended == 'yes') {
			$sign = " AND verified='1'";
		} elseif($ended == 'no') {
			$sign = " AND verified='0'";
		}

		$extra .= $srchfid ? '&amp;type='.$type : '';

		$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}activityapplies aa LEFT JOIN {$tablepre}activities a USING(tid) LEFT JOIN {$tablepre}threads t USING(tid) WHERE a.uid='$discuz_uid' $threadadd$sign");
		$multipage = multi($num, $tpp, $page, "my.php?item=activities&amp;type=apply&amp;ended=$ended$extrafid");

		require_once DISCUZ_ROOT.'./include/misc.func.php';
		$query = $db->query("SELECT aa.verified, aa.tid, starttimefrom, a.place, a.cost, t.* FROM {$tablepre}activityapplies aa LEFT JOIN {$tablepre}activities a USING(tid) LEFT JOIN {$tablepre}threads t USING(tid) WHERE aa.uid='$discuz_uid' $threadadd$sign ORDER BY starttimefrom $ascadd LIMIT $start_limit, $tpp");
		while($tempact = $db->fetch_array($query)) {
			$tempact['starttimefrom'] = dgmdate("$dateformat $timeformat", $tempact['starttimefrom'] + $timeoffset * 3600);
			$activity[] = procthread($tempact);
		}
	}

} elseif($item == 'polls'){

	$polllist = array();

	if($srchfid = intval($srchfid)) {
		$threadadd = "AND fid='$srchfid'";
	}

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads t
		WHERE t.authorid='$discuz_uid' AND t.special='1' $threadadd");

	$multipage = multi($num, $tpp, $page, "my.php?item=polls&amp;type=poll$extrafid");

	require_once DISCUZ_ROOT.'./include/misc.func.php';
	$query = $db->query("SELECT t.*
		FROM {$tablepre}threads t
		WHERE t.authorid='$discuz_uid' AND t.special='1' $threadadd
		ORDER BY t.dateline DESC LIMIT $start_limit, $tpp");
	while($poll = $db->fetch_array($query)) {
		$poll['lastposterenc'] = rawurlencode($poll['lastposter']);
		$poll['forumname'] = $_DCACHE['forums'][$poll['fid']]['name'];
		$polllist[] = procthread($poll);
	}

} elseif($item == 'promotion' && ($creditspolicy['promotion_visit'] || $creditspolicy['promotion_register'])) {

	$promotion_visit = $promotion_register = $space = '';
	foreach(array('promotion_visit', 'promotion_register') as $val) {
		if(!empty($creditspolicy[$val]) && is_array($creditspolicy[$val])) {
			foreach($creditspolicy[$val] as $id => $policy) {
				$$val .= $space.$extcredits[$id]['title'].' +'.$policy;
				$space = '&nbsp;';
			}
		}
	}

} elseif($item == 'debate') {

	$debatelist = array();

	if($filter == 'recyclebin') {
		$threadadd .= " AND t.displayorder='-1'";
	} elseif($filter == 'aduit') {
		$threadadd .= " AND t.displayorder='-2'";
	} elseif($filter == 'close') {
		$threadadd .= " AND t.closed='1'";
	} elseif($filter == 'common') {
		$threadadd .= " AND t.displayorder>='0' AND t.closed='0'";
	}

	if($type == 'orig') {

		$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads t
			WHERE t.authorid='$discuz_uid' AND t.special='5' $threadadd");
		$multipage = multi($num, $tpp, $page, "my.php?item=debate&amp;type=orig$extrafid");

		require_once DISCUZ_ROOT.'./include/misc.func.php';
		$query = $db->query("SELECT t.*
			FROM {$tablepre}threads t
			WHERE t.authorid='$discuz_uid' AND t.special='5' $threadadd
			ORDER BY t.dateline DESC LIMIT $start_limit, $tpp");
		while($debate = $db->fetch_array($query)) {
			$debate['lastposterenc'] = rawurlencode($debate['lastposter']);
			$debate['forumname'] = $_DCACHE['forums'][$debate['fid']]['name'];
			$debatelist[] = procthread($debate);
		}

	} elseif($type == 'reply') {

		require_once DISCUZ_ROOT.'./include/post.func.php';
		$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts p, {$tablepre}threads t
			WHERE p.authorid='$discuz_uid' AND p.tid=t.tid AND t.special='5' $threadadd");
		$multipage = multi($num, $tpp, $page, "my.php?item=debate&amp;type=reply$extrafid");

		require_once DISCUZ_ROOT.'./include/misc.func.php';
		$query = $db->query("SELECT p.pid, p.message, t.dateline, t.*
			FROM {$tablepre}posts p, {$tablepre}threads t
			WHERE p.authorid='$discuz_uid' AND p.first='0' AND p.tid=t.tid AND t.special='5' $threadadd
			ORDER BY p.dateline DESC LIMIT $start_limit, $tpp");
		while($debate = $db->fetch_array($query)) {
			$debate['message'] = messagecutstr($debate['message'], 100);
			$debate['forumname'] = $_DCACHE['forums'][$debate['fid']]['name'];
			$debatelist[] = procthread($debate);
		}

	}

} elseif($item == 'buddylist') {

	include_once DISCUZ_ROOT.'./uc_client/client.php';

	$buddynum = 999;
	$extratype = empty($type) ? '' : '&type=fans';

	if(!submitcheck('buddysubmit', 1)) {

		$buddylist = array();
		$friendtype = empty($type) ? 3 : 1;
		$buddynum = uc_friend_totalnum($discuz_uid, $friendtype);
		$buddies = $buddynum ? uc_friend_ls($discuz_uid, $page, $tpp, $buddynum, $friendtype) : array();
		$multipage = multi($buddynum, $tpp, $page, "my.php?item=buddylist$extratype");

		if($buddies) {
			foreach($buddies as $key => $buddy) {
				$buddylist[$buddy['friendid']] = $buddy;
			}
			unset($buddies);
			$query = $db->query("SELECT m.uid, m.gender, mf.msn, s.uid AS online FROM {$tablepre}members m
				LEFT JOIN {$tablepre}sessions s ON s.uid=m.uid
				LEFT JOIN {$tablepre}memberfields mf ON mf.uid=m.uid
				WHERE m.uid IN (".implodeids(array_keys($buddylist)).")");
			while($member = $db->fetch_array($query)) {
				if(isset($buddylist[$member['uid']])) {
					$buddylist[$member['uid']]['avatar'] = discuz_uc_avatar($member['uid'], 'small');
					$buddylist[$member['uid']]['gender'] = $member['gender'];
					$buddylist[$member['uid']]['online'] = $member['online'];
					$buddylist[$member['uid']]['msn'] = explode("\t", $member['msn']);
				} else {
					unset($buddylist[$member['uid']]);
				}
			}
		}

	} else {

		$buddyarray = uc_friend_ls($discuz_uid, 1, $buddynum, $buddynum);

		if($action == 'edit') {

			if($comment = cutstr(dhtmlspecialchars($comment), 255)) {
				$friendid = intval($friendid);
				uc_friend_delete($discuz_uid, array($friendid));
				uc_friend_add($discuz_uid, $friendid, $comment);
			}

		} elseif($action == 'delete') {

			$friendid = intval($friendid);
			uc_friend_delete($discuz_uid, array($friendid));
			manyoulog('friend', $discuz_uid, 'delete', $friendid);

		} else {

			$buddyarraynew = array();
			if($buddyarray) {
				foreach($buddyarray as $buddy) {
					$buddyarraynew[$buddy['friendid']] = $buddy;
				}
			}
			$buddyarray = $buddyarraynew;unset($buddyarraynew);

			if(($newbuddy && $newbuddy != $discuz_userss) || ($newbuddyid && $newbuddyid != $discuz_uid)) {
				$newbuddyid && $newbuddy = $db->result_first("SELECT username FROM {$tablepre}members WHERE uid='$newbuddyid'", 0);

				if($buddyid = uc_get_user($newbuddy)) {
					if(isset($buddyarray[$buddyid[0]])) {
						showmessage('buddy_add_invalid');
					}
					if(uc_friend_add($discuz_uid, $buddyid[0], cutstr(dhtmlspecialchars($newdescription), 255))) {
						if($my_status) {
							$manyoufriend = FALSE;
							$buddyarray = uc_friend_ls($discuz_uid, 1, $buddynum, $buddynum, 3);
							foreach($buddyarray as $buddy) {
								if($buddyid[0] == $buddy['friendid']) {
									$manyoufriend = TRUE;
									break;
								}
							}
							if($manyoufriend) {
								manyoulog('friend', $discuz_uid, 'add', $buddyid[0]);
								manyoulog('friend', $buddyid[0], 'add', $discuz_uid);
							}
						}
						if($ucappopen['UCHOME']) {
							sendnotice($buddyid[0], 'buddy_new_uch', 'friend');
						} else {
							sendnotice($buddyid[0], 'buddy_new', 'friend');
						}
						if($prompts['newbietask'] && $newbietaskid && $newbietasks[$newbietaskid]['scriptname'] == 'addbuddy') {
							require_once DISCUZ_ROOT.'./include/task.func.php';
							task_newbie_complete();
						}
					} else {
						showmessage('buddy_add_ignore');
					}
				} else {
					showmessage('username_nonexistence');
				}
			}
		}

		showmessage('buddy_update_succeed', 'my.php?item=buddylist'.$extratype);

	}

} elseif($item == 'attention') {
	if(!submitcheck('attentionsubmit')) {

		$type = !$type ? 'thread' : $type;

		if($type == 'forum') {
			if($action == 'add') {
				if($db->result_first("SELECT COUNT(*) FROM {$tablepre}favoriteforums WHERE fid='$fid' AND uid='$discuz_uid'")) {
					showmessage('favoriteforums_exists', dreferer());
				}
				$timestamp = time();
				$db->query("REPLACE INTO {$tablepre}favoriteforums (fid, uid, dateline) VALUES ('$fid', '$discuz_uid', '$timestamp')");
				showmessage('favoriteforums_add_succeed', dreferer());
			} elseif($action == 'remove') {
				$db->query("DELETE FROM {$tablepre}favoriteforums WHERE fid='$fid' AND uid='$discuz_uid'");
				showmessage('favoriteforums_remove_succeed', dreferer());
			} elseif($action == 'detail') {
				$theforum = $db->fetch_first("SELECT * FROM {$tablepre}forums WHERE fid='$fid'");
				$newthreads = array();
				$num_dateline = $db->fetch_first("SELECT newthreads, dateline FROM {$tablepre}favoriteforums WHERE fid='$fid' AND uid='$discuz_uid'");
				$num = $num_dateline['newthreads'];
				$dateline = $num_dateline['dateline'];
				$multipage = multi($num, $tpp, $page, 'my.php?item=attention&action=detail&type=forum');

				$query = $db->query("SELECT tid, fid, author, authorid, subject, dateline, lastpost, lastposter, views, replies FROM {$tablepre}threads WHERE fid='$fid' AND authorid<>'$discuz_uid' AND dateline>$dateline ORDER BY dateline LIMIT $start_limit, $tpp");
				$newthreads = array();
				while($newthread = $db->fetch_array($query)) {
					$newthread['forumname'] = $_DCACHE['forums'][$newthread['fid']]['name'];
					$newthread['dateline'] = dgmdate("$dateformat $timeformat", $newthread['dateline'] + $timeoffset * 3600);
					$newthread['lastpost'] = dgmdate("$dateformat $timeformat", $newthread['lastpost'] + $timeoffset * 3600);
					$newthreads[] = $newthread;
				}

				$db->query("UPDATE {$tablepre}favoriteforums SET newthreads=0, dateline='$timestamp' WHERE fid='$fid' AND uid='$discuz_uid'");
			} elseif($action == 'open') {
				$db->query("UPDATE {$tablepre}favoriteforums SET dateline='$timestamp', newthreads=0 WHERE fid='$fid' AND uid='$discuz_uid'");
				dheader("Location: forumdisplay.php?fid=$fid");
			} else {
				$sqladd = '';
				if($filter == 'new') {
					$sqladd .= "AND ff.newthreads>0";
				}
				$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}favoriteforums ff WHERE ff.uid='$discuz_uid' $sqladd");
				$filteradd = $filter ? "&filter=$filter" : '';
				$multipage = multi($num, $tpp, $page, 'my.php?item=attention&type=forum'.$fiteradd);

				$query = $db->query("SELECT ff.*, f.fid f_fid, f.name, f.threads, f.posts, f.lastpost, f.todayposts FROM {$tablepre}favoriteforums ff LEFT JOIN {$tablepre}forums f ON ff.fid=f.fid WHERE ff.uid='$discuz_uid' $sqladd ORDER BY f.displayorder LIMIT $start_limit, $tpp");
				$attentionlist = array();
				while($attention = $db->fetch_array($query)) {
					if(!$attention['f_fid']) {
						$db->query("DELETE FROM {$tablepre}favoriteforums WHERE fid='$attention[fid]' AND uid='$discuz_uid'");
						continue;
					}
					$lastpost = array();
					list($lastpost['tid'], $lastpost['subject'], $lastpost['dateline'], $lastpost['author']) = explode("\t", $attention['lastpost']);
					$lastpost['dateline'] = dgmdate("$dateformat $timeformat", $lastpost['dateline'] + $timeoffset * 3600);
					$attention['lastpost'] = $lastpost;
					$attentionlist[] = $attention;
				}
			}
		} else {
			if($tid && $action == 'add') {
				if($db->result_first("SELECT COUNT(*) FROM {$tablepre}favoritethreads WHERE tid='$tid' AND uid='$discuz_uid'")) {
					showmessage('favoritethreads_exists', dreferer());
				}
				$timestamp = time();
				$attention_exists = $db->result_first("SELECT COUNT(*) FROM {$tablepre}favoritethreads WHERE tid='$tid' AND uid='$discuz_uid'");
				$db->query("REPLACE INTO {$tablepre}favoritethreads (tid, uid, dateline) VALUES ('$tid', '$discuz_uid', '$timestamp')", 'UNBUFFERED');
				showmessage('favoritethreads_add_succeed', dreferer());
			} elseif($action == 'remove') {
				$db->query("DELETE FROM {$tablepre}favoritethreads WHERE tid='$tid' AND uid='$discuz_uid'");
				showmessage('favoritethreads_remove_succeed', dreferer());
			}
			$sqladd = '';
			if($filter == 'new') {
				$sqladd .= " AND ft.newreplies>0";
			}
			$filteradd = $filter ? "&filter=$filter" : '';
			$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}favoritethreads ft WHERE ft.uid='$discuz_uid' $sqladd");
			$multipage = multi($num, $tpp, $page, 'my.php?item=attention'.$filteradd);
			$query = $db->query("SELECT t.tid AS t_tid, t.fid, t.subject, t.replies, t.lastpost, t.lastposter, ft.* FROM {$tablepre}favoritethreads ft LEFT JOIN {$tablepre}threads t ON ft.tid=t.tid WHERE ft.uid='$discuz_uid' $sqladd ORDER BY t.lastpost DESC LIMIT $start_limit, $tpp");
			$attentionlist = array();
			while($attention = $db->fetch_array($query)) {
				if(!$attention['t_tid']) {
					$db->query("DELETE FROM {$tablepre}favoritethreads WHERE tid='$attention[tid]' AND uid='$discuz_uid'", 'UNBUFFERED');
					continue;
				}
				$attention['lastpost'] = dgmdate("$dateformat $timeformat", $attention['lastpost'] + $timeoffset * 3600);
				$attention['forumname'] = $_DCACHE['forums'][$attention['fid']]['name'];
				$attentionlist[] = $attention;
			}
		}
	} else {
		if($type == 'forum') {
			if($deleteids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}favoriteforums WHERE fid IN ($deleteids) AND uid='$discuz_uid'", 'UNBUFFERED');
				showmessage('favoriteforums_update_succeed', dreferer());
			}
		} else {
			if($deleteids = implodeids($delete)) {
				$db->query("DELETE FROM {$tablepre}favoritethreads WHERE tid IN ($deleteids) AND uid='$discuz_uid'", 'UNBUFFERED');
			}
			showmessage('favoritethreads_update_succeed', dreferer());
		}
	}

} else {

	showmessage('undefined_action', NULL, 'HALTED');

}

if(!$uid) {
	include template('my');
} else {
	include template('viewpro_data');
}

?>