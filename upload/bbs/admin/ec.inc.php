<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: ec.inc.php 20461 2009-09-28 04:43:28Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if($operation == 'alipay') {

	$settings = array();
	$query = $db->query("SELECT variable, value FROM {$tablepre}settings WHERE variable IN ('ec_account', 'ec_contract')");
	while($setting = $db->fetch_array($query)) {
		$settings[$setting['variable']] = $setting['value'];
	}

	if(!empty($checktype)) {
		require_once DISCUZ_ROOT.'./api/trade/alipay.api.php';
		if($checktype == 'credit') {
			dheader('location: '.credit_payurl(1, $orderid));
		} elseif($checktype == 'virtualgoods') {
			$pay = array(
				'logistics_type' => 'VIRTUAL'
			);
			$trade = array(
				'subject' => $lang['ec_alipay_check_virtualgoodssubject'],
				'itemtype' => 1,
				'account' => $settings['ec_account'],
			);
			$tradelog = array(
				'orderid' => 'TEST'.gmdate('YmdHis', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600).random(18),
				'baseprice' => 1,
				'number' => 1,
				'transportfee' => 0,
			);
			dheader('location: '.trade_payurl($pay, $trade, $tradelog));
		} elseif($checktype == 'goods') {
			$pay = array(
				'logistics_type' => 'EMS',
				'transport' => 'SELLER_PAY',
			);
			$trade = array(
				'subject' => $lang['ec_alipay_check_goodssubject'],
				'itemtype' => 1,
				'account' => $settings['ec_account'],
			);
			$tradelog = array(
				'orderid' => 'TEST'.gmdate('YmdHis', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600).random(18),
				'baseprice' => 1,
				'number' => 1,
				'transportfee' => 0,
			);
			dheader('location: '.trade_payurl($pay, $trade, $tradelog));
		}
		exit;
	}

	list($ec_contract, $ec_securitycode, $ec_partner, $ec_creditdirectpay) = explode("\t", authcode($settings['ec_contract'], 'DECODE', $_DCACHE['settings']['authkey']));
	$ec_securitycodemask = $ec_securitycode ? $ec_securitycode{0}.'********'.substr($ec_securitycode, -4) : '';

	if(!submitcheck('alipaysubmit')) {


		if($from == 'creditwizard') {
			shownav('tools', 'nav_creditwizard');
			showsubmenu('nav_creditwizard', array(
				array('creditwizard_step_menu_1', 'creditwizard&step=1', 0),
				array('creditwizard_step_menu_2', 'creditwizard&step=2', 0),
				array('creditwizard_step_menu_3', 'creditwizard&step=3', 0),
				array('creditwizard_step_menu_4', 'settings&operation=ec&from=creditwizard', 0),
				array('ec_alipay', 'ec&operation=alipay&from=creditwizard', 1),
				array('ec_tenpay', 'ec&operation=tenpay&from=creditwizard', 0),
			));
		} else {
			shownav('extended', 'nav_ec');
			showsubmenu('nav_ec', array(
				array('nav_ec_config', 'settings&operation=ec', 0),
				array('nav_ec_alipay', 'ec&operation=alipay', 1),
				array('nav_ec_tenpay', 'ec&operation=tenpay', 0),
				array('nav_ec_credit', 'ec&operation=credit', 0),
				array('nav_ec_orders', 'ec&operation=orders', 0),
				array('nav_ec_tradelog', 'tradelog', 0)
			));
		}

		showtips('ec_alipay_tips');
		showformheader('ec&operation=alipay');

		showtableheader('','nobottom');
		showtitle('ec_alipay');
		showsetting('ec_alipay_account', 'settingsnew[ec_account]', $settings['ec_account'], 'text');
		showsetting('ec_alipay_check', '', '',
			'<a href="'.$BASESCRIPT.'?action=ec&operation=alipay&checktype=credit" target="_blank">'.$lang['ec_alipay_checklink_credit'].'</a><br />'.
			'<a href="'.$BASESCRIPT.'?action=ec&operation=alipay&checktype=virtualgoods" target="_blank">'.$lang['ec_alipay_checklink_virtualgoods'].'</a><br />'.
			'<a href="'.$BASESCRIPT.'?action=ec&operation=alipay&checktype=goods" target="_blank">'.$lang['ec_alipay_checklink_goods'].'</a><br />'
		);
		showtitle('ec_contract');
		showsetting('ec_alipay_contract', 'settingsnew[ec_contract]', $ec_contract, 'radio', 0, 1);
		showsetting('ec_alipay_securitycode', 'settingsnew[ec_securitycode]', $ec_securitycodemask, 'text');
		showsetting('ec_alipay_partner', 'settingsnew[ec_partner]', $ec_partner, 'text');
		showsetting('ec_alipay_creditdirectpay', 'settingsnew[ec_creditdirectpay]', $ec_creditdirectpay, 'radio');
		showtablefooter();

		showtableheader('', 'notop');
		showsubmit('alipaysubmit');
		showtablefooter();
		showformfooter();

	} else {

		$settingsnew['ec_account'] = trim($settingsnew['ec_account']);
		$settingsnew['ec_securitycode'] = trim($settingsnew['ec_securitycode']);
		$db->query("UPDATE {$tablepre}settings SET value='$settingsnew[ec_account]' WHERE variable='ec_account'");
		$ec_securitycodemasknew = $settingsnew['ec_securitycode'] ? $settingsnew['ec_securitycode']{0}.'********'.substr($settingsnew['ec_securitycode'], -4) : '';
		$settingsnew['ec_securitycode'] = $ec_securitycodemasknew == $ec_securitycodemask ? $ec_securitycode : $settingsnew['ec_securitycode'];
		$ec_contract = addslashes(authcode($settingsnew['ec_contract']."\t".$settingsnew['ec_securitycode']."\t".$settingsnew['ec_partner']."\t".$settingsnew['ec_creditdirectpay'], 'ENCODE', $_DCACHE['settings']['authkey']));
		$db->query("UPDATE {$tablepre}settings SET value='$ec_contract' WHERE variable='ec_contract'");
		updatecache('settings');

		cpmsg('alipay_succeed', $BASESCRIPT.'?action=ec&operation=alipay', 'succeed');

	}

} elseif($operation == 'tenpay') {

	$settings = array();
	$query = $db->query("SELECT variable, value FROM {$tablepre}settings WHERE variable IN ('ec_tenpay_account', 'ec_tenpay_bargainor', 'ec_tenpay_key')");
	while($setting = $db->fetch_array($query)) {
		$settings[$setting['variable']] = $setting['value'];
	}

	if(!empty($checktype)) {
		require_once DISCUZ_ROOT.'./api/trade/tenpay.api.php';
		if($checktype == 'credit') {
			dheader('location: '.credit_payurl(1, $orderid));
		} elseif($checktype == 'virtualgoods') {
			$pay = array(
				'logistics_type' => 'VIRTUAL'
			);
			$trade = array(
				'subject' => $lang['ec_tenpay_check_virtualgoodssubject'],
				'itemtype' => 1,
				'account' => $settings['ec_account'],
			);
			$tradelog = array(
				'orderid' => 'TEST'.gmdate('YmdHis', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600).random(18),
				'baseprice' => 1,
				'number' => 1,
				'transportfee' => 0,
			);
			dheader('location: '.trade_payurl($pay, $trade, $tradelog));
		} elseif($checktype == 'goods') {
			$pay = array(
				'logistics_type' => 'EMS',
				'transport' => 'SELLER_PAY',
			);
			$trade = array(
				'subject' => $lang['ec_tenpay_check_goodssubject'],
				'itemtype' => 1,
				'account' => $settings['ec_account'],
			);
			$tradelog = array(
				'orderid' => 'TEST'.gmdate('YmdHis', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600).random(18),
				'baseprice' => 1,
				'number' => 1,
				'transportfee' => 0,
			);
			dheader('location: '.trade_payurl($pay, $trade, $tradelog));
		}
		exit;
	}

	if(!submitcheck('tenpaysubmit')) {

		if($from == 'creditwizard') {
			shownav('tools', 'nav_creditwizard');
			showsubmenu('nav_creditwizard', array(
				array('creditwizard_step_menu_1', 'creditwizard&step=1', 0),
				array('creditwizard_step_menu_2', 'creditwizard&step=2', 0),
				array('creditwizard_step_menu_3', 'creditwizard&step=3', 0),
				array('creditwizard_step_menu_4', 'settings&operation=ec&from=creditwizard', 0),
				array('ec_alipay', 'ec&operation=alipay&from=creditwizard', 0),
				array('ec_tenpay', 'ec&operation=tenpay&from=creditwizard', 1),
			));
		} else {
			shownav('extended', 'nav_ec');
			showsubmenu('nav_ec', array(
				array('nav_ec_config', 'settings&operation=ec', 0),
				array('nav_ec_alipay', 'ec&operation=alipay', 0),
				array('nav_ec_tenpay', 'ec&operation=tenpay', 1),
				array('nav_ec_credit', 'ec&operation=credit', 0),
				array('nav_ec_orders', 'ec&operation=orders', 0),
				array('nav_ec_tradelog', 'tradelog', 0)
			));
		}

		showtips('ec_tenpay_tips');
		showformheader('ec&operation=tenpay');

		showtableheader('','nobottom');
		showtitle('ec_tenpay');
		showsetting('ec_tenpay_bargainor', 'settingsnew[ec_tenpay_bargainor]', $settings['ec_tenpay_bargainor'], 'text');

		$tenpay_securitycodemask = $settings['ec_tenpay_key'] ? $settings['ec_tenpay_key']{0}.'********'.substr($settings['ec_tenpay_key'], -4) : '';
		showsetting('ec_tenpay_key', 'settingsnew[ec_tenpay_key]', $tenpay_securitycodemask, 'text');
		showsetting('ec_tenpay_check', '', '',
			'<a href="'.$BASESCRIPT.'?action=ec&operation=tenpay&checktype=credit" target="_blank">'.$lang['ec_alipay_checklink_credit'].'</a><br />');
		showtablefooter();

		showtableheader('', 'notop');
		showsubmit('tenpaysubmit');
		showtablefooter();
		showformfooter();

	} else {
		$settingsnew['ec_tenpay_bargainor'] = trim($settingsnew['ec_tenpay_bargainor']);
		$settingsnew['ec_tenpay_key'] = trim($settingsnew['ec_tenpay_key']);
		$tenpay_securitycodemask = $settings['ec_tenpay_key'] ? $settings['ec_tenpay_key']{0}.'********'.substr($settings['ec_tenpay_key'], -4) : '';
		$settingsnew['ec_tenpay_key'] = $tenpay_securitycodemask == $settingsnew['ec_tenpay_key'] ? $settings['ec_tenpay_key'] : $settingsnew['ec_tenpay_key'];
		if(!empty($settingsnew['ec_tenpay_bargainor']) && !preg_match('/^\d{10}$/', $settingsnew['ec_tenpay_bargainor'])) {
			cpmsg('tenpay_bargainor_invalid', $BASESCRIPT.'?action=ec&operation=tenpay', 'error');
		}
		if(!empty($settingsnew['ec_tenpay_key']) && !preg_match('/^[a-zA-Z0-9]{32}$/', $settingsnew['ec_tenpay_key'])) {
			cpmsg('tenpay_key_invalid', $BASESCRIPT.'?action=ec&operation=tenpay', 'error');
		}
		$db->query("UPDATE {$tablepre}settings SET value='$settingsnew[ec_tenpay_bargainor]' WHERE variable='ec_tenpay_bargainor'");
		$db->query("UPDATE {$tablepre}settings SET value='$settingsnew[ec_tenpay_key]' WHERE variable='ec_tenpay_key'");
		updatecache('settings');

		cpmsg('tenpay_succeed', $BASESCRIPT.'?action=ec&operation=tenpay', 'succeed');

	}

} elseif($operation == 'orders') {

	$orderurl = array(
		'alipay' => 'https://www.alipay.com/trade/query_trade_detail.htm?trade_no=',
		'tenpay' => 'https://www.tenpay.com/med/tradeDetail.shtml?trans_id=',
	);

	if(!$creditstrans || !$ec_ratio) {
		cpmsg('orders_disabled', '', 'error');
	}

	if(!submitcheck('ordersubmit')) {

		echo '<script type="text/javascript" src="include/js/calendar.js"></script>';
		shownav('extended', 'nav_ec');
		showsubmenu('nav_ec', array(
			array('nav_ec_config', 'settings&operation=ec', 0),
			array('nav_ec_alipay', 'ec&operation=alipay', 0),
			array('nav_ec_tenpay', 'ec&operation=tenpay', 0),
			array('nav_ec_credit', 'ec&operation=credit', 0),
			array('nav_ec_orders', 'ec&operation=orders', 1),
			array('nav_ec_tradelog', 'tradelog', 0)
		));
		showtips('ec_orders_tips');
		showtagheader('div', 'ordersearch', !submitcheck('searchsubmit', 1));
		showformheader('ec&operation=orders');
		showtableheader('ec_orders_search');
		showsetting('ec_orders_search_status', array('orderstatus', array(
			array('', $lang['ec_orders_search_status_all']),
			array(1, $lang['ec_orders_search_status_pending']),
			array(2, $lang['ec_orders_search_status_auto_finished']),
			array(3, $lang['ec_orders_search_status_manual_finished'])
		)), intval($orderstatus), 'select');
		showsetting('ec_orders_search_id', 'orderid', $orderid, 'text');
		showsetting('ec_orders_search_users', 'users', $users, 'text');
		showsetting('ec_orders_search_buyer', 'buyer', $buyer, 'text');
		showsetting('ec_orders_search_admin', 'admin', $admin, 'text');
		showsetting('ec_orders_search_submit_date', array('sstarttime', 'sendtime'), array($sstarttime, $sendtime), 'daterange');
		showsetting('ec_orders_search_confirm_date', array('cstarttime', 'cendtime'), array($cstarttime, $cendtime), 'daterange');
		showsubmit('searchsubmit');
		showtablefooter();
		showformfooter();
		showtagfooter('div');

		if(submitcheck('searchsubmit', 1)) {

			$page = max(1, intval($page));
			$start_limit = ($page - 1) * $tpp;

			$sql = '';
			$sql .= $orderstatus != ''	? " AND o.status='$orderstatus'" : '';
			$sql .= $orderid != ''		? " AND o.orderid='$orderid'" : '';
			$sql .= $users != ''		? " AND m.username IN ('".str_replace(',', '\',\'', str_replace(' ', '', $users))."')" : '';
			$sql .= $buyer != ''		? " AND o.buyer='$buyer'" : '';
			$sql .= $admin != ''		? " AND o.admin='$admin'" : '';
			$sql .= $sstarttime != ''	? " AND o.submitdate>='".strtotime($sstarttime)."'" : '';
			$sql .= $sendtime != ''		? " AND o.submitdate<'".strtotime($sendtime)."'" : '';
			$sql .= $cstarttime != ''	? " AND o.confirmdate>='".strtotime($cstarttime)."'" : '';
			$sql .= $cendtime != ''		? " AND o.confirmdate<'".strtotime($cendtime)."'" : '';

			$ordercount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}orders o, {$tablepre}members m WHERE m.uid=o.uid $sql");
			$multipage = multi($ordercount, $tpp, $page, "$BASESCRIPT?action=ec&operation=orders&searchsubmit=yes&orderstatus=$orderstatus&orderid=$orderid&users=$users&buyer=$buyer&admin=$admin&sstarttime=$sstarttime&sendtime=$sendtime&cstarttime=$cstarttime&cendtime=$cendtime");

			showtagheader('div', 'orderlist', TRUE);
			showformheader('ec&operation=orders');
			showtableheader('result');
			showsubtitle(array('', 'ec_orders_id', 'ec_orders_status', 'ec_orders_buyer', 'ec_orders_amount', 'ec_orders_price', 'ec_orders_submitdate', 'ec_orders_confirmdate'));

			$query = $db->query("SELECT o.*, m.username
				FROM {$tablepre}orders o, {$tablepre}members m
				WHERE m.uid=o.uid $sql ORDER BY o.submitdate DESC
				LIMIT $start_limit, $tpp");

			while($order = $db->fetch_array($query)) {
				switch($order['status']) {
					case 1: $order['orderstatus'] = $lang['ec_orders_search_status_pending']; break;
					case 2: $order['orderstatus'] = '<b>'.$lang['ec_orders_search_status_auto_finished'].'</b>'; break;
					case 3: $order['orderstatus'] = '<b>'.$lang['ec_orders_search_status_manual_finished'].'</b><br />(<a href="space.php?username='.rawurlencode($order['admin']).'" target="_blank">'.$order['admin'].'</a>)'; break;
				}
				$order['submitdate'] = gmdate("$dateformat $timeformat", $order['submitdate'] + $timeoffset * 3600);
				$order['confirmdate'] = $order['confirmdate'] ? gmdate("$dateformat $timeformat", $order['confirmdate'] + $timeoffset * 3600) : 'N/A';

				list($orderid, $apitype) = explode("\t", $order['buyer']);
				$apitype = $apitype ? $apitype : 'alipay';
				$orderid = '<a href="'.$orderurl[$apitype].$orderid.'" target="_blank">'.$orderid.'</a>';
				showtablerow('', '', array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"validate[]\" value=\"$order[orderid]\" ".($order['status'] != 1 ? 'disabled' : '').">",
					"$order[orderid]<br />$orderid",
					$order[orderstatus],
					"<a href=\"space.php?uid=$order[uid]\" target=\"_blank\">$order[username]</a>",
					"{$extcredits[$creditstrans]['title']} $order[amount] {$extcredits[$creditstrans]['unit']}",
					"$lang[rmb] $order[price] $lang[rmb_yuan]",
					$order[submitdate],
					$order[confirmdate]
				));
			}

			showsubmit('ordersubmit', 'submit', '<input type="checkbox" name="chkall" id="chkall" class="checkbox" onclick="checkAll(\'prefix\', this.form, \'validate\')" /><label for="chkall">'.lang('ec_orders_validate').'</label>', '<a href="#" onclick="$(\'orderlist\').style.display=\'none\';$(\'ordersearch\').style.display=\'\';">'.lang('research').'</a>', $multipage);
			showtablefooter();
			showformfooter();
			showtagfooter('div');
		}

	} else {

		$numvalidate = 0;
		if($validate) {
			$orderids = $comma = '';
			$confirmdate = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);

			$query = $db->query("SELECT * FROM {$tablepre}orders WHERE orderid IN ('".implode('\',\'', $validate)."') AND status='1'");
			while($order = $db->fetch_array($query)) {
				$db->query("UPDATE {$tablepre}members SET extcredits$creditstrans=extcredits$creditstrans+'$order[amount]' WHERE uid='$order[uid]'");
				$orderids .= "$comma'$order[orderid]'";
				$comma = ',';

				$submitdate = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $order['submitdate'] + $_DCACHE['settings']['timeoffset'] * 3600);
				sendnotice($order['uid'], 'addfunds', 'systempm');
			}
			if($numvalidate = $db->num_rows($query)) {
				$db->query("UPDATE {$tablepre}orders SET status='3', admin='$discuz_user', confirmdate='$timestamp' WHERE orderid IN ($orderids)");
			}
		}

		cpmsg('orders_validate_succeed', "$BASESCRIPT?action=ec&operation=orders&searchsubmit=yes&orderstatus=$orderstatus&orderid=$orderid&users=$users&buyer=$buyer&admin=$admin&sstarttime=$sstarttime&sendtime=$sendtime&cstarttime=$cstarttime&cendtime=$cendtime", 'succeed');

	}

} elseif($operation == 'credit') {

	$defaultrank = array(
		1 => 4,
		2 => 11,
		3 => 41,
		4 => 91,
		5 => 151,
		6 => 251,
		7 => 501,
		8 => 1001,
		9 => 2001,
		10 => 5001,
		11 => 10001,
		12 => 20001,
		13 => 50001,
		14 => 100001,
		15 => 200001
	);

	if(!submitcheck('creditsubmit')) {

		$ec_credit = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='ec_credit'");
		$ec_credit = $ec_credit ? unserialize($ec_credit) : array(
			'maxcreditspermonth' => '6',
			'rank' => $defaultrank
		);

		shownav('extended', 'nav_ec');
		showsubmenu('nav_ec', array(
			array('nav_ec_config', 'settings&operation=ec', 0),
			array('nav_ec_alipay', 'ec&operation=alipay', 0),
			array('nav_ec_tenpay', 'ec&operation=tenpay', 0),
			array('nav_ec_credit', 'ec&operation=ec_credit', 1),
			array('nav_ec_orders', 'ec&operation=orders', 0),
			array('nav_ec_tradelog', 'tradelog', 0)
		));

		showtips('ec_credit_tips');
		showformheader('ec&operation=credit');
		showtableheader('ec_credit', 'nobottom');
		showsetting('ec_credit_maxcreditspermonth', 'ec_creditnew[maxcreditspermonth]', $ec_credit['maxcreditspermonth'], 'text');
		showtablefooter('</tbody>');

		showtableheader('ec_credit_rank', 'notop fixpadding');
		showsubtitle(array('ec_credit_rank', 'ec_credit_between', 'ec_credit_sellericon', 'ec_credit_buyericon'));

		foreach($ec_credit['rank'] as $rank => $mincredits) {
			showtablerow('', '', array(
				$rank,
				'<input type="text" class="txt" size="6" name="ec_creditnew[rank]['.$rank.']" value="'.$mincredits.'" /> ~ '.$ec_credit[rank][$rank + 1],
				"<img src=\"images/rank/seller/$rank.gif\" border=\"0\">",
				"<img src=\"images/rank/buyer/$rank.gif\" border=\"0\">"
			));
		}
		showsubmit('creditsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$ec_creditnew['maxcreditspermonth'] = intval($ec_creditnew['maxcreditspermonth']);

		if(is_array($ec_creditnew['rank'])) {
			foreach($ec_creditnew['rank'] as $rank => $mincredits) {
				$mincredits = intval($mincredits);
				if($rank == 1 && $mincredits <= 0) {
					cpmsg('ecommerce_invalidcredit', '', 'error');
				} elseif($rank > 1 && $mincredits <= $ec_creditnew['rank'][$rank - 1]) {
					cpmsg('ecommerce_must_larger', '', 'error');
				}
				$ec_creditnew['rank'][$rank] = $mincredits;
			}
		} else {
			$ec_creditnew['rank'] = $defaultrank;
		}

		$db->query("UPDATE {$tablepre}settings SET value='".serialize($ec_creditnew)."' WHERE variable='ec_credit'");
		updatecache('settings');

		cpmsg('ec_credit_succeed', $BASESCRIPT.'?action=ec&operation=credit', 'succeed');

	}
}

?>