<?php
/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: trade.func.php 18283 2009-04-11 11:41:49Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$apitype = empty($apitype) || !preg_match('/^[a-z0-9]+$/i', $apitype) ? 'alipay' : $apitype;

require_once DISCUZ_ROOT.'./api/trade/'.$apitype.'.api.php';

function trade_offline($tradelog, $returndlang = 1) {
	global $discuz_uid, $language, $trade_message;
	$tmp = $return = array();
	if($discuz_uid == $tradelog['buyerid']) {
		$data = array(
			0 => array(4,8),
			1 => array(4,8),
			5 => array(7,10),
			11 => array(10,7),
			12 => array(13)
		);
		$tmp = $data[$tradelog['status']];
	} elseif($discuz_uid == $tradelog['sellerid']) {
		$data = array(
			4 => array(5),
			10 => array(12,11),
			13 => array(17)
		);
		$tmp = $data[$tradelog['status']];
	}
	if($returndlang) {
		for($i = 0, $count = count($tmp);$i < $count;$i++) {
			$return[$tmp[$i]] = $language['trade_offline_'.$tmp[$i]];
			$trade_message .= isset($language['trade_message_'.$tmp[$i]]) ? $language['trade_message_'.$tmp[$i]].'<br />' : '';
		}
		return $return;
	} else {
		return $tmp;
	}
}

function trade_create($trade) {
	global $tablepre, $db, $allowposttrade, $mintradeprice, $maxtradeprice, $timestamp;

	extract($trade);
	$special = 2;

	$expiration = $item_expiration ? strtotime($item_expiration) : 0;
	$closed = $expiration > 0 && strtotime($item_expiration) < $timestamp ? 1 : $closed;
	$item_price = floatval($item_price);

	switch($transport) {
		case 'seller'	: $item_transport = 1; break;
		case 'buyer'	: $item_transport = 2; break;
		case 'virtual'	: $item_transport = 3; break;
		case 'logistics': $item_transport = 4; break;
	}

	$seller = dhtmlspecialchars($seller);
	$item_name = dhtmlspecialchars($item_name);
	$item_locus = dhtmlspecialchars($item_locus);
	$item_number = intval($item_number);
	$item_quality = intval($item_quality);
	$item_transport = intval($item_transport);
	$postage_mail = intval($postage_mail);
	$postage_express = intval($postage_express);
	$postage_ems = intval($postage_ems);
	$item_type = intval($item_type);
	$typeid = intval($typeid);
	$item_costprice = floatval($item_costprice);
	if(!$item_price || $item_price <= 0) {
		$item_price = $postage_mail = $postage_express = $postage_ems = '';
	}

	if(empty($pid)) {
		$pid = $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$tid' AND first='1' LIMIT 1");
	}
	$db->query("INSERT INTO {$tablepre}trades (tid, pid, typeid, sellerid, seller, account, subject, price, amount, quality, locus, transport, ordinaryfee, expressfee, emsfee, itemtype, dateline, expiration, lastupdate, totalitems, tradesum, closed, costprice, aid, credit, costcredit)
		VALUES ('$tid', '$pid', '$typeid', '$discuz_uid', '$author', '$seller', '$item_name', '$item_price', '$item_number', '$item_quality', '$item_locus', '$item_transport', '$postage_mail', '$postage_express', '$postage_ems', '$item_type', '$timestamp', '$expiration', '$timestamp', '0', '0', '$closed', '$item_costprice', '$aid', '$item_credit', '$item_costcredit')");
}

?>