<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: trade.php 21135 2009-11-17 02:30:08Z monkey $
*/

define('NOROBOT', TRUE);
define('CURSCRIPT', 'trade');

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/trade.func.php';

if(!$discuz_uid) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

$page = max(1, intval($page));

if(!empty($orderid)) {

	include_once language('misc');

	$tradelog = daddslashes($db->fetch_first("SELECT * FROM {$tablepre}tradelog WHERE orderid='$orderid'"), 1);
	if(empty($tradelog) || $discuz_uid != $tradelog['sellerid'] && $discuz_uid != $tradelog['buyerid']) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	$trade_message = '';
	$currentcredit = $creditstrans ? $_DSESSION['extcredits'.$creditstrans] : 0;
	$discountprice = $tradelog['baseprice'] * $tradelog['number'];

	if(!empty($pay) && !$tradelog['offline'] && $tradelog['status'] == 0 && $tradelog['buyerid'] == $discuz_uid) {
		if($creditstransextra[5] != -1 && $tradelog['credit']) {
			if($tradelog['credit'] > $GLOBALS['extcredits'.$creditstransextra[5]]) {
				showmessage('trade_credit_lack');
			}
			updatecredits($tradelog['buyerid'], array($creditstransextra[5] => -$tradelog['credit']));
		}
		$trade = $db->fetch_first("SELECT * FROM {$tablepre}trades WHERE tid='$tradelog[tid]' AND pid='$tradelog[pid]'");

		if($discuz_uid && $currentcredit < $discountcredit && $tradelog['discount']) {
			showmessage('trade_credits_no_enough');
		}
		$pay = array();
		$pay['commision'] = 0;
		$transport = $tradelog['transport'];
		$transportfee = 0;
		trade_setprice(array('fee' => $fee, 'trade' => $trade, 'transport' => $transport), $price, $pay, $transportfee);
		$payurl = trade_payurl($pay, $trade, $tradelog);
		showmessage('trade_directtopay', $payurl);
	}

	if(submitcheck('offlinesubmit') && in_array($offlinestatus, trade_offline($tradelog, 0))) {

		require_once DISCUZ_ROOT.'./uc_client/client.php';
		$ucresult = uc_user_login($discuz_user, $password);
		list($tmp['uid']) = daddslashes($ucresult);

		if($tmp['uid'] <= 0) {
			showmessage('trade_password_error', 'trade.php?orderid='.$orderid);
		}
		if($offlinestatus == STATUS_SELLER_SEND) {
			if($creditstransextra[5] != -1 && $tradelog['credit']) {
				if($tradelog['credit'] > $GLOBALS['extcredits'.$creditstransextra[5]]) {
					showmessage('trade_credit_lack');
				}
				updatecredits($tradelog['buyerid'], array($creditstransextra[5] => -$tradelog['credit']));
			}
			$trade = $db->fetch_first("SELECT amount FROM {$tablepre}trades WHERE tid='$tradelog[tid]' AND pid='$tradelog[pid]'");
			$user = $tradelog['buyer'];
			$userid = $tradelog['buyerid'];
			$itemsubject = $tradelog['subject'];
			sendnotice($tradelog['sellerid'], 'trade_seller_send', 'threads');
		} elseif($offlinestatus == STATUS_WAIT_BUYER) {
			$user = $tradelog['seller'];
			$userid = $tradelog['buyerid'];
			$itemsubject = $tradelog['subject'];
			sendnotice($tradelog['buyerid'], 'trade_buyer_confirm', 'threads');
		} elseif($offlinestatus == STATUS_TRADE_SUCCESS) {
			if($creditstransextra[5] != -1 && $tradelog['basecredit']) {
				$netcredit = round($tradelog['number'] * $tradelog['basecredit'] * (1 - $creditstax));
				updatecredits($tradelog['sellerid'], array($creditstransextra[5] => $netcredit));
			} else {
				$netcredit = 0;
			}
			$db->query("UPDATE {$tablepre}trades SET lastbuyer='$tradelog[buyer]', lastupdate='$timestamp', totalitems=totalitems+'$tradelog[number]', tradesum=tradesum+'$tradelog[price]', credittradesum=credittradesum+'$netcredit' WHERE tid='$tradelog[tid]' AND pid='$tradelog[pid]'", 'UNBUFFERED');
			$itemsubject = $tradelog['subject'];
			sendnotice($tradelog['sellerid'], 'trade_success', 'threads');
			sendnotice($tradelog['buyerid'], 'trade_success', 'threads');
		} elseif($offlinestatus == STATUS_REFUND_CLOSE) {
			$db->query("UPDATE {$tablepre}trades SET amount=amount+'$tradelog[number]' WHERE tid='$tradelog[tid]' AND pid='$tradelog[pid]'", 'UNBUFFERED');
			$itemsubject = $tradelog['subject'];
			sendnotice($tradelog['sellerid'], 'trade_fefund_success', 'threads');
			sendnotice($tradelog['buyerid'], 'trade_fefund_success', 'threads');
			if($creditstransextra[5] != -1 && $tradelog['basecredit']) {
				updatecredits($tradelog['buyerid'], array($creditstransextra[5] => $tradelog['number'] * $tradelog['basecredit']));
			}
		}

		$message = trim($message);
		if($message) {
			$message = daddslashes(stripslashes($tradelog['message'])."\t\t\t".$discuz_uid."\t".$discuz_userss."\t".$timestamp."\t".nl2br(strip_tags(substr($message, 0, 200))), 1);
		} else {
			$message = daddslashes($tradelog['message'], 1);
		}

		$db->query("UPDATE {$tablepre}tradelog SET status='$offlinestatus', lastupdate='$timestamp', message='$message' WHERE orderid='$orderid'");
		showmessage('trade_orderstatus_updated', 'trade.php?orderid='.$orderid);
	}

	if(submitcheck('tradesubmit')) {

		if($tradelog['status'] == 0) {

			$update = array();
			$oldbasecredit = $tradelog['basecredit'];
			$oldnumber = $tradelog['number'];
			if($tradelog['sellerid'] == $discuz_uid) {
				$tradelog['baseprice'] = floatval($newprice);
				$tradelog['basecredit'] = intval($newcredit);
				if(!$tradelog['baseprice'] < 0 || $tradelog['basecredit'] < 0) {
					showmessage('trade_pricecredit_error');
				}
				$tradelog['transportfee'] = intval($newfee);
				$newnumber = $tradelog['number'];
				$update = array(
					"baseprice='$tradelog[baseprice]'",
					"basecredit='$tradelog[basecredit]'",
					"transportfee='$tradelog[transportfee]'"
				);
			}
			if($tradelog['buyerid'] == $discuz_uid) {

				$newnumber = intval($newnumber);
				if($newnumber <= 0) {
					showmessage('trade_input_no');
				}
				$trade = $db->fetch_first("SELECT amount FROM {$tablepre}trades WHERE tid='$tradelog[tid]' AND pid='$tradelog[pid]'");
				if($newnumber > $trade['amount'] + $tradelog['number']) {
					showmessage('trade_lack');
				}
				$amount = $trade['amount'] + $tradelog['number'] - $newnumber;
				$db->query("UPDATE {$tablepre}trades SET amount='$amount' WHERE tid='$tradelog[tid]' AND pid='$tradelog[pid]'", 'UNBUFFERED');
				$tradelog['number'] = $newnumber;

				$update = array(
					"number='$tradelog[number]'",
					"discount=0",
					"buyername='".dhtmlspecialchars($newbuyername)."'",
					"buyercontact='".dhtmlspecialchars($newbuyercontact)."'",
					"buyerzip='".dhtmlspecialchars($newbuyerzip)."'",
					"buyerphone='".dhtmlspecialchars($newbuyerphone)."'",
					"buyermobile='".dhtmlspecialchars($newbuyermobile)."'",
					"buyermsg='".dhtmlspecialchars($newbuyermsg)."'",
				);

			}
			if($update) {
				if($tradelog['discount']) {
					$tradelog['baseprice'] = $tradelog['baseprice'] - $tax;
					$price = $tradelog['baseprice'] * $tradelog['number'];
				} else {
					$price = $tradelog['baseprice'] * $tradelog['number'];
				}
				if($creditstransextra[5] != -1 && ($oldnumber != $newnumber || $oldbasecredit != $tradelog['basecredit'])) {
					$tradelog['credit'] = $newnumber * $tradelog['basecredit'];
					$update[] = "credit='$tradelog[credit]'";
				}

				$update[] = "price='".($price + ($tradelog['transport'] == 2 ? $tradelog['transportfee'] : 0))."'";
				$db->query("UPDATE {$tablepre}tradelog SET ".implode(',', $update)." WHERE orderid='$orderid'");
				$tradelog = $db->fetch_first("SELECT * FROM {$tablepre}tradelog WHERE orderid='$orderid'");
			}
		}

	}

	$tradelog['lastupdate'] = dgmdate("$dateformat $timeformat", $tradelog['lastupdate'] + $timeoffset * 3600);
	$tradelog['statusview'] = trade_getstatus($tradelog['status']);

	$messagelist = array();
	if($tradelog['offline']) {
		$offlinenext = trade_offline($tradelog);
		$message = explode("\t\t\t", stripslashes($tradelog['message']));
		foreach($message as $row) {
			$row = explode("\t", $row);
			$row[2] = dgmdate("$dateformat $timeformat", $row[2] + $timeoffset * 3600);
			$row[0] && $messagelist[] = $row;
		}
	} else {
		$loginurl = trade_getorderurl($tradelog['tradeno']);
	}
	$tradelog['buyer'] = stripslashes($tradelog['buyer']);
	$tradelog['seller'] = stripslashes($tradelog['seller']);

	include template('trade_view');

} else {

	if(empty($pid)) {
		$pid = $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$tid' AND first='1' LIMIT 1");
	}
	if($db->result_first("SELECT closed FROM {$tablepre}threads WHERE tid='$tid'")) {
		showmessage('trade_closed', 'viewthread.php?tid='.$tid.'&page='.$page);
	}
	$trade = $db->fetch_first("SELECT * FROM {$tablepre}trades WHERE tid='$tid' AND pid='$pid'");
	if(empty($trade)) {
		showmessage('undefined_action', NULL, 'HALTED');
	}
	$fromcode = false;

	if($trade['closed']) {
		showmessage('trade_closed', 'viewthread.php?tid='.$tid.'&page='.$page);
	}

	if($trade['price'] <= 0 && $trade['credit'] <= 0) {
		showmessage('trade_invalid', 'viewthread.php?tid='.$tid.'&page='.$page);
	}
	if($trade['credit'] > 0 && $creditstransextra[5] == -1) {
		showmessage('trade_credit_invalid', 'viewthread.php?tid='.$tid.'&page='.$page);
	}

	if($action != 'trade' && !submitcheck('tradesubmit')) {
		$lastbuyerinfo = dhtmlspecialchars($db->fetch_first("SELECT buyername,buyercontact,buyerzip,buyerphone,buyermobile FROM {$tablepre}tradelog WHERE buyerid='$discuz_uid' AND status!=0 AND buyername!='' ORDER BY lastupdate DESC LIMIT 1"));
		$extra = rawurlencode($extra);
		include template('trade');
	} else {

		if($trade['sellerid'] == $discuz_uid) {
			showmessage('trade_by_myself');
		} elseif($number <= 0) {
			showmessage('trade_input_no');
		} elseif(!$fromcode && $number > $trade['amount']) {
			showmessage('trade_lack');
		}

		$pay['number'] = $number;
		$pay['price'] = $trade['price'];
		$credit = 0;
		if($creditstransextra[5] != -1 && $trade['credit']) {
			$credit = $number * $trade['credit'];
		}

		$price = $pay['price'] * $pay['number'];
		$buyercredits = 0;
		$pay['commision'] = 0;

		$orderid = $pay['orderid'] = gmdate('YmdHis', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600).random(18);
		$transportfee = 0;
		trade_setprice(array('fee' => $fee, 'trade' => $trade, 'transport' => $transport), $price, $pay, $transportfee);

		$buyerid = $discuz_uid ? $discuz_uid : 0;
		$discuz_user = $discuz_user ? $discuz_user : $guestuser;
		$trade = daddslashes($trade, 1);
		$buyermsg = dhtmlspecialchars($buyermsg);
		$buyerzip = dhtmlspecialchars($buyerzip);
		$buyerphone = dhtmlspecialchars($buyerphone);
		$buyermobile = dhtmlspecialchars($buyermobile);
		$buyername = dhtmlspecialchars($buyername);
		$buyercontact = dhtmlspecialchars($buyercontact);

		$offline = !empty($offline) ? 1 : 0;
		$db->query("INSERT INTO {$tablepre}tradelog
			(tid, pid, orderid, subject, price, quality, itemtype, number, tax, locus, sellerid, seller, selleraccount, buyerid, buyer, buyercontact, buyercredits, buyermsg, lastupdate, offline, buyerzip, buyerphone, buyermobile, buyername, transport, transportfee, baseprice, discount, credit, basecredit) VALUES
			('$trade[tid]', '$trade[pid]', '$orderid', '$trade[subject]', '$price', '$trade[quality]', '$trade[itemtype]', '$number', '$tax',
			 '$trade[locus]', '$trade[sellerid]', '$trade[seller]', '$trade[account]', '$discuz_uid', '$discuz_user', '$buyercontact', 0, '$buyermsg', '$timestamp', '$offline', '$buyerzip', '$buyerphone', '$buyermobile', '$buyername', '$transport', '$transportfee', '$trade[price]', 0, '$credit', '$trade[credit]')");

		$db->query("UPDATE {$tablepre}trades SET amount=amount-'$number' WHERE tid='$trade[tid]' AND pid='$trade[pid]'", 'UNBUFFERED');
		showmessage('trade_order_created', 'trade.php?orderid='.$orderid);
	}

}

?>