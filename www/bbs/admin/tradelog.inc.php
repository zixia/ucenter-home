<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: tradelog.inc.php 18655 2009-07-08 10:28:06Z wangjinbo $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

require_once DISCUZ_ROOT.'./include/trade.func.php';
include_once language('misc');

cpheader();
if(!isfounder()) cpmsg('noaccess_isfounder', '', 'error');

$page = max(1, intval($page));
$start_limit = ($page - 1) * $tpp;

$filter = !isset($filter) ? -1 : $filter;
$sqlfilter = $filter >= 0 ? "WHERE status='$filter'" : '';

$count = $db->fetch_first("SELECT sum(price) AS pricesum, sum(tax) AS taxsum FROM {$tablepre}tradelog status $sqlfilter");

$num = $db->result_first("SELECT COUNT(*) FROM {$tablepre}tradelog $sqlfilter");
$multipage = multi($num, $tpp, $page, "$BASESCRIPT?action=tradelog&filter=$filter");

$query = $db->query("SELECT * FROM {$tablepre}tradelog $sqlfilter ORDER BY lastupdate DESC LIMIT $start_limit, $tpp");

shownav('extended', 'nav_ec');
showsubmenu('nav_ec', array(
	array('nav_ec_config', 'settings&operation=ec', 0),
	array('nav_ec_alipay', 'ec&operation=alipay', 0),
	array('nav_ec_tenpay', 'ec&operation=tenpay', 0),
	array('nav_ec_credit', 'ec&operation=credit', 0),
	array('nav_ec_orders', 'ec&operation=orders', 0),
	array('nav_ec_tradelog', 'tradelog', 1)
));
showtableheader();
showsubtitle(array('tradelog_trade_no', 'tradelog_trade_name', 'tradelog_buyer', 'tradelog_seller', 'tradelog_money', 'tradelog_fee', 'tradelog_order_status'));

while($tradelog = $db->fetch_array($query)) {
	$tradelog['status'] = trade_getstatus($tradelog['status']);
	$tradelog['lastupdate'] = gmdate("$dateformat $timeformat", $tradelog['lastupdate'] + $timeoffset * 3600);
	$tradelog['tradeno'] = $tradelog['offline'] ? $lang['tradelog_offline'] : $tradelog['tradeno'];
	showtablerow('', '', array(
		$tradelog['tradeno'],
		'<a target="_blank" href="viewthread.php?do=tradeinfo&tid='.$tradelog['tid'].'&pid='.$tradelog['pid'].'">'.$tradelog['subject'].'</a>',
		'<a target="_blank" href="space.php?uid='.$tradelog['buyerid'].'">'.$tradelog['buyer'].'</a>',
		'<a target="_blank" href="space.php?uid='.$tradelog['sellerid'].'">'.$tradelog['seller'].'</a>',
		$tradelog['price'],
		$tradelog['tax'],
		'<a target="_blank" href="trade.php?orderid='.$tradelog['orderid'].'">'.$tradelog['status'].'<br />'.$tradelog['lastupdate']
	));
}

$statusselect = $lang['tradelog_order_status'].': <select onchange="location.href=\''.$BASESCRIPT.'?action=tradelog&filter=\' + this.value"><option value="-1">'.$lang['tradelog_all_order'].'</option>';
$statuss = trade_getstatus(0, -1);
foreach($statuss as $key => $value) {
	$statusselect .= "<option value=\"$key\" ".($filter == $key ? 'selected' : '').">$value</option>";
}
$statusselect .= '</select>';

showsubmit('', '', "$lang[tradelog_order_count] $num".($count['pricesum'] ? ", $lang[tradelog_trade_total] $count[pricesum] $lang[rmb_yuan], $lang[tradelog_fee_total] $count[taxsum] $lang[rmb_yuan]" : ''), '', $multipage.$statusselect);
showtablefooter();

?>