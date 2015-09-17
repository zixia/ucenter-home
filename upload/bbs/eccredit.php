<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: eccredit.php 20206 2009-09-22 02:37:49Z monkey $
*/

define('NOROBOT', TRUE);
define('CURSCRIPT', 'eccredit');
require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/ec_credit.func.php';

if(empty($action)) {

	$uid = intval($uid);
	$allowviewpro = $discuz_uid && $uid == $discuz_uid ? 1 : $allowviewpro;

	if(!$allowviewpro) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	}

	include_once DISCUZ_ROOT.'./forumdata/cache/cache_usergroups.php';

	$discuz_action = 62;

	$member = $db->fetch_first("SELECT m.uid, mf.customstatus, m.username, m.groupid, mf.taobao, mf.alipay, mf.avatar, mf.avatarwidth, mf.avatarheight, mf.buyercredit, mf.sellercredit, m.regdate FROM {$tablepre}members m LEFT JOIN {$tablepre}memberfields mf USING(uid) WHERE m.uid='$uid'");
	if(!$member) {
		showmessage('member_nonexistence', NULL, 'NOPERM');
	}

	$member['avatar'] = '<div class="avatar">'.discuz_uc_avatar($member['uid']);
	if($_DCACHE['usergroups'][$member['groupid']]['groupavatar']) {
		$member['avatar'] .= '<br /><img src="'.$_DCACHE['usergroups'][$member['groupid']]['groupavatar'].'" border="0" alt="" />';
	}
	$member['avatar'] .= '</div>';

	$member['taobaoas'] = str_replace("'", '', addslashes($member['taobao']));
	$member['regdate'] = gmdate($dateformat, $member['regdate'] + $timeoffset * 3600);
	$member['usernameenc'] = rawurlencode($member['username']);
	$member['buyerrank'] = 0;
	if($member['buyercredit']){
		foreach($ec_credit['rank'] AS $level => $credit) {
			if($member['buyercredit'] <= $credit) {
				$member['buyerrank'] = $level;
				break;
			}
		}
	}
	$member['sellerrank'] = 0;
	if($member['sellercredit']){
		foreach($ec_credit['rank'] AS $level => $credit) {
			if($member['sellercredit'] <= $credit) {
				$member['sellerrank'] = $level;
				break;
			}
		}
	}

	$query = $db->query("SELECT variable, value, expiration FROM {$tablepre}spacecaches WHERE uid='$uid' AND variable IN ('buyercredit', 'sellercredit')");
	$caches = array();
	while($cache = $db->fetch_array($query)) {
		$caches[$cache['variable']] = unserialize($cache['value']);
		$caches[$cache['variable']]['expiration'] = $cache['expiration'];
	}

	foreach(array('buyercredit', 'sellercredit') AS $type) {
		if(!isset($caches[$type]) || $timestamp > $caches[$type]['expiration']) {
			$caches[$type] = updatecreditcache($uid, $type, 1);
		}
	}

	@$buyerpercent = $caches['buyercredit']['all']['total'] ? sprintf('%0.2f', $caches['buyercredit']['all']['good'] * 100 / $caches['buyercredit']['all']['total']) : 0;
	@$sellerpercent = $caches['sellercredit']['all']['total'] ? sprintf('%0.2f', $caches['sellercredit']['all']['good'] * 100 / $caches['sellercredit']['all']['total']) : '';

	include template('ec_credit');

} elseif($action == 'list') {

	$from = !empty($from) && in_array($from, array('buyer', 'seller', 'myself')) ? $from : '';
	$uid = !empty($uid) ? intval($uid) : '';

	$sql = $from == 'myself' ? "raterid='$uid'" : "rateeid='$uid'";
	$sql .= $from == 'buyer' ? ' AND type=0' : ($from == 'seller' ? ' AND type=1' : '');

	$filter = !empty($filter) ? $filter : '';
	switch($filter) {
		case 'thisweek':
			$sql .= " AND dateline>=$timestamp - 604800";
			break;
		case 'thismonth':
			$sql .= " AND dateline>=$timestamp - 2592000";
			break;
		case 'halfyear':
			$sql .= " AND dateline>=$timestamp - 15552000";
			break;
		case 'before':
			$sql .= " AND dateline<$timestamp - 15552000";
			break;
		default:
			$filter = '';
	}

	$level = !empty($level) ? $level : '';
	switch($level) {
		case 'good':
			$sql .= ' AND score=1';
			break;
		case 'soso':
			$sql .= ' AND score=0';
			break;
		case 'bad':
			$sql .= ' AND score=-1';
			break;
		default:
			$level = '';
	}

	$page = max(1, intval($page));
	$start_limit = ($page - 1) * 10;

	$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}tradecomments WHERE $sql");
	$multipage = multi($num, 10, $page, "eccredit.php?action=list&uid=$uid".($from ? "&from=$from" : NULL).($filter ? "&filter=$filter" : NULL).($level ? "&level=$level" : NULL));

	$comments = array();
	$query = $db->query("SELECT tc.*, tl.subject, tl.price, tl.credit FROM {$tablepre}tradecomments tc LEFT JOIN {$tablepre}tradelog tl ON tl.orderid=tc.orderid WHERE $sql ORDER BY dateline DESC LIMIT $start_limit, 10");

	while($comment = $db->fetch_array($query)) {
		$comment['expiration'] = dgmdate("$dateformat $timeformat", $comment['dateline'] + $timeoffset * 3600 + 30 * 86400);
		$comment['dbdateline'] = $comment['dateline'];
		$comment['dateline'] = dgmdate("$dateformat $timeformat", $comment['dateline'] + $timeoffset * 3600);
		$comment['baseprice'] = sprintf('%0.2f', $comment['baseprice']);
		$comments[] = $comment;
	}

	include template('ec_list');

} elseif($action == 'rate' && $orderid && isset($type)) {

	require_once DISCUZ_ROOT.'./include/trade.func.php';

	$type = intval($type);
	if(!$type) {
		$raterid = 'buyerid';
		$ratee = 'seller';
		$rateeid = 'sellerid';
	} else {
		$raterid = 'sellerid';
		$ratee = 'buyer';
		$rateeid = 'buyerid';
	}
	$order = $db->fetch_first("SELECT * FROM {$tablepre}tradelog WHERE orderid='$orderid' AND $raterid='$discuz_uid'");
	if(!$order) {
		showmessage('eccredit_order_notfound');
	} elseif($order['ratestatus'] == 3 || ($type == 0 && $order['ratestatus'] == 1) || ($type == 1 && $order['ratestatus'] == 2)) {
		showmessage('eccredit_rate_repeat');
	} elseif(!trade_typestatus('successtrades', $order['status']) && !trade_typestatus('refundsuccess', $order['status'])) {
		showmessage('eccredit_nofound');
	}

	$uid = $discuz_uid == $order['buyerid'] ? $order['sellerid'] : $order['buyerid'];

	if(!submitcheck('ratesubmit')) {

		include template('ec_rate');

	} else {

		$score = intval($score);
		$message = cutstr(dhtmlspecialchars($message), 200);
		$level = $score == 1 ? 'good' : ($score == 0 ? 'soso' : 'bad');
		$pid = intval($order['pid']);
		$order = daddslashes($order, 1);

		$db->query("INSERT INTO {$tablepre}tradecomments (pid, orderid, type, raterid, rater, ratee, rateeid, score, message, dateline) VALUES ('$pid', '$orderid', '$type', '$discuz_uid', '$discuz_user', '$order[$ratee]', '$order[$rateeid]', '$score', '$message', '$timestamp')");

		if(!$order['offline'] || $order['credit']) {
			if($db->result_first("SELECT COUNT(score) FROM {$tablepre}tradecomments WHERE raterid='$discuz_uid' AND type='$type'") < $ec_credit['maxcreditspermonth']) {
				updateusercredit($uid, $type ? 'sellercredit' : 'buyercredit', $level);
			}
		}

		if($type == 0) {
			$ratestatus = $order['ratestatus'] == 2 ? 3 : 1;
		} else {
			$ratestatus = $order['ratestatus'] == 1 ? 3 : 2;
		}

		$db->query("UPDATE {$tablepre}tradelog SET ratestatus='$ratestatus' WHERE orderid='$order[orderid]'");

		if($ratestatus != 3) {
			sendnotice($order[$rateeid], 'eccredit', 'threads');
		}

		showmessage('eccredit_succeed');

	}

} elseif($action == 'explain' && $id) {

	$id = intval($id);
	if(!submitcheck('explainsubmit', 1)) {
		include template('ec_explain');
	} else {
		$comment = $db->fetch_first("SELECT explanation, dateline FROM {$tablepre}tradecomments WHERE id='$id' AND rateeid='$discuz_uid'");
		if(!$comment) {
			showmessage('eccredit_nofound');
		} elseif($comment['explanation']) {
			showmessage('eccredit_reexplanation_repeat');
		} elseif($comment['dateline'] < $timestamp - 30 * 86400) {
			showmessage('eccredit_reexplanation_closed');
		}

		$explanation = cutstr(dhtmlspecialchars($explanation), 200);

		$db->query("UPDATE {$tablepre}tradecomments SET explanation='$explanation' WHERE id='$id'");

		include_once language('misc');
		showmessage("<script type=\"text/javascript\">\$('ecce_$id').innerHTML = '<font class=\"lighttxt\">$language[eccredit_explain]: ".addslashes($explanation)."</font>';hideMenu();</script>");
	}

}

?>