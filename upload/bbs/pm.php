<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: pm.php 20686 2009-10-14 09:29:20Z liuqiang $
*/

define('CURSCRIPT', 'pm');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';

$discuz_action = 101;
if(!$discuz_uid) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

include_once DISCUZ_ROOT.'./uc_client/client.php';

if(isset($checknewpm)) {
	@dheader("Expires: 0");
	@dheader("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
	@dheader("Pragma: no-cache");
	$ucnewpm = uc_pm_checknew($discuz_uid, 2);
	$s = '';
	if($prompts['pm']['new'] != $ucnewpm['newpm']) {
		$s .= updateprompt('pm', $discuz_uid, $ucnewpm['newpm']);
	}
	if($announcepm != $ucnewpm['announcepm']) {
		$_DCACHE['settings']['announcepm'] = $ucnewpm['announcepm'];
		require_once DISCUZ_ROOT.'./include/cache.func.php';
		updatesettings();
		$s .= updateprompt('announcepm', $discuz_uid, $ucnewpm['announcepm'], 0);
	}
	dsetcookie('checkpm', 1, 30, 0);
	include_once template('pm_checknew');
	exit;
}

$page = max($page, 1);
$action = !empty($action) ? $action : (isset($uid) || !empty($pmid) ? 'view' : '');

if(!$action) {

	$pmstatus = uc_pm_checknew($discuz_uid, 4);
	$filter = !empty($filter) && in_array($filter, array('newpm', 'privatepm', 'announcepm')) ? $filter : ($pmstatus['newpm'] ? 'newpm' : 'privatepm');
	$ucdata = uc_pm_list($discuz_uid, $page, $ppp, !isset($search) ? 'inbox' : 'searchbox', !isset($search) ? $filter : $srchtxt, 200);
	if(!empty($search) && $srchtxt !== '') {
		$filter = '';
		$srchtxtinput = htmlspecialchars(stripslashes($srchtxt));
		$srchtxtenc = rawurlencode($srchtxt);
	} else {
		$multipage = multi($ucdata['count'], $ppp, $page, 'pm.php?filter='.$filter);
	}
	$_COOKIE['checkpm'] && setcookie('checkpm', '', -86400 * 365);

	$pmlist = array();
	$today = $timestamp - ($timestamp + $timeoffset * 3600) % 86400;
	foreach($ucdata['data'] as $pm) {
		$pm['msgfromurl'] = $pm['fromappid'] && $ucapp[$pm['fromappid']]['viewprourl'] ? sprintf($ucapp[$pm['fromappid']]['viewprourl'], $pm['msgfromid']) : 'space.php?uid='.$pm['msgfromid'];
		$pm['daterange'] = 5;
		if($pm['dateline'] >= $today) {
			$pm['daterange'] = 1;
		} elseif($pm['dateline'] >= $today - 86400) {
			$pm['daterange'] = 2;
		} elseif($pm['dateline'] >= $today - 172800) {
			$pm['daterange'] = 3;
		}
		$pm['date'] = gmdate($dateformat, $pm['dateline'] + $timeoffset * 3600);
		$pm['time'] = gmdate($timeformat, $pm['dateline'] + $timeoffset * 3600);
		$pmlist[] = $pm;
	}

	if($prompts['pm']['new']) {
		updateprompt('pm', $discuz_uid, 0);
	}

} elseif($action == 'view') {

	$daterange = empty($daterange) ? 1 : $daterange;
	if(isset($uid)) {
		$ucdata = uc_pm_view($discuz_uid, '', $uid, $daterange);
		$msgfromurl = $pm['fromappid'] && $ucapp[$pm['fromappid']]['viewprourl'] ? sprintf($ucapp[$pm['fromappid']]['viewprourl'], $uid) : 'space.php?uid='.$uid;
		list(,$user) = uc_get_user($uid, 1);
	} elseif(!empty($pmid)) {
		$ucdata = uc_pm_view($discuz_uid, $pmid, 0, $daterange);
		$msgfromurl = '';
	}

	$pmlist = array();
	$pmdate = '';
	foreach($ucdata as $pm) {
		$dateline = $pm['dateline'] + $timeoffset * 3600;
		$pm['date'] = gmdate($dateformat, $dateline);
		if($pmdate != $pm['date']) {
			$lastdaterange = $pm['daterange'] = $pm['date'];
		} else {
			$pm['daterange'] = '';
		}
		$pmdate = $pm['date'];
		$pm['dateline'] = gmdate("$dateformat $timeformat", $dateline);
		$pm['new'] && $pmunread++;
		$pmlist[] = $pm;
	}

	if(!empty($export)) {
		ob_end_clean();
		dheader('Content-Encoding: none');
		dheader('Content-Type: '.(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') ? 'application/octetstream' : 'application/octet-stream'));
		dheader('Content-Disposition: attachment; filename="PM_'.$discuz_userss.'_'.$user.'_'.gmdate('ymd_Hi', $timestamp + $timeoffset * 3600).'.htm"');
		dheader('Pragma: no-cache');
		dheader('Expires: 0');
		include template('pm_archive_html');
		exit;
	}

} elseif($action == 'new') {

	if(!$allowsendpm) {
		showmessage('pm_send_disable');
	}

	$username = $subject = $message = '';
	if(!empty($uid)) {
		$username = htmlspecialchars($db->result_first("SELECT username FROM {$tablepre}members WHERE uid='$uid'", 0));
	}
	if(!empty($pmid)) {
		include_once language('misc');
		$ucdata = uc_pm_view($discuz_uid, $pmid);
		$subject = 'Fw: '.$ucdata[0]['subject'];
		$message = ($ucdata[0]['msgfromid'] ? $language['pm_from'].': '.$ucdata[0]['msgfrom'] : $lang['pm_system'])."\n".
			$language['pm_to'].': '.$discuz_userss."\n".
			$language['pm_date'].': '.gmdate("$dateformat $timeformat", $ucdata[0]['dateline'] + $timeoffset * 3600)."\n\n".
			'[quote]'.trim(preg_replace("/(\[quote])(.*)(\[\/quote])/siU", '', strip_tags($ucdata[0]['message']))).'[/quote]'."\n";
	}

	if($operation == 'trade' && ($tradepid = intval($pid))) {
		include_once language('misc');
		$tradepid = intval($tradepid);
		$trade = $db->fetch_first("SELECT * FROM {$tablepre}trades WHERE pid='$tradepid'");
		if($trade) {
			$message = '[url='.$boardurl.'viewthread.php?do=tradeinfo&tid='.$trade['tid'].'&pid='.$tradepid.']'.$trade['subject']."[/url]\n";
			$message .= $language['post_trade_price'].': '.($trade['price'] > 0 ? $trade['price'].' '.$language['post_trade_yuan'].' ' : '').($extcredits[$creditstransextra[5]]['title'] != -1 && $trade['credit'] > 0 ? $extcredits[$creditstransextra[5]]['title'].' '.$trade['credit'].' '.$extcredits[$creditstransextra[5]]['unit'] : '')."\n";
			$message .= $language['post_trade_transport_type'].': ';
			if($trade['transport'] == 1) {
				$message .= $language['post_trade_transport_seller'];
			} elseif($trade['transport'] == 2) {
				$message .= $language['post_trade_transport_buyer'];
			} elseif($trade['transport'] == 3) {
				$message .= $language['post_trade_transport_virtual'];
			} elseif($trade['transport'] == 4) {
				$message .= $language['post_trade_transport_physical'];
			}
			if($trade['transport'] == 1 or $trade['transport'] == 2 or $trade['transport'] == 4) {
				if(!empty($trade['ordinaryfee'])) {
					$message .= ', '.$language['post_trade_transport_mail'].' '.$trade['ordinaryfee'].' '.$language['payment_unit'];
				}
				if(!empty($trade['expressfee'])) {
					$message .= ', '.$language['post_trade_transport_express'].' '.$trade['expressfee'].' '.$language['payment_unit'];
				}
				if(!empty($trade['emsfee'])) {
					$message .= ', EMS '.$trade['emsfee'].' '.$language['payment_unit'];
				}
			}
			$message .= "\n".$language['post_trade_locus'].': '.$trade['locus']."\n\n";
			$message .= $language['post_trade_pm_buynum'].": \n";
			$message .= $language['post_trade_pm_wishprice'].": \n";
			$message .= $language['post_trade_pm_reason'].": \n";
			$message = htmlspecialchars($message);
		}
	} elseif($operation == 'report' && ($reportid = intval($reportid))) {
		include_once language('misc');
		$reportlog = $db->fetch_first("SELECT r.*, p.tid, p.author, p.first, p.authorid, t.subject, t.displayorder FROM {$tablepre}reportlog r
			LEFT JOIN {$tablepre}posts p ON p.pid=r.pid
			LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
			WHERE r.id='$reportid'");
		if($reportlog && $reportlog['tid'] && $reportlog['displayorder'] >= 0) {
			$plang = $reportlog['first'] ? 'thread' : 'post';
			$message = '[quote]'.(empty($isreporter) ? '[url='.$boardurl.'space.php?uid='.$reportlog['uid'].']'.$reportlog['username'].'[/url]' : $language['reportpost_your']).
				($reportlog['type'] ? $language['reportpost_recommend'] : $language['reportpost_delate']).
				(!empty($isreporter) ? '[url='.$boardurl.'space.php?uid='.$reportlog['authorid'].']'.$reportlog['author'].'[/url]'.$language['reportpost_'.$plang] : $language['reportpost_your'.$plang]).
				'[url='.$boardurl.'redirect.php?goto=findpost&pid='.$reportlog['pid'].'&ptid='.$reportlog['tid'].']'.$reportlog['subject']."[/url]: \n".$reportlog['reason'].
				"[/quote]\n";
			$message = htmlspecialchars($message);
		}
	} elseif($operation == 'share') {
		include_once language('misc');

		$thread = $db->fetch_first("SELECT * FROM {$tablepre}threads WHERE tid='$tid' AND displayorder>='0'");
		$fromuid = $creditspolicy['promotion_visit'] ? '&amp;fromuid='.$discuz_uid : '';
		$threadurl = "{$boardurl}viewthread.php?tid=$tid$fromuid";

		eval("\$message = \"".$language['share_message']."\";");
	}

	$buddynum = uc_friend_totalnum($discuz_uid, 3);
	$buddyarray = uc_friend_ls($discuz_uid, 1, $buddynum, $buddynum, 3);
	$uids = array();
	foreach($buddyarray as $buddy) {
		$uids[] = $buddy['friendid'];
	}
	$buddyarray = array();
	if($uids) {
		$query = $db->query("SELECT uid, username FROM {$tablepre}members WHERE uid IN (".implodeids($uids).")");
		while($buddy = $db->fetch_array($query)) {
			$buddyarray[] = $buddy;
		}
	}

	include template('pm_send');
	exit;

} elseif($action == 'send' && submitcheck('pmsubmit')) {

	if(!$allowsendpm) {
		showmessage('pm_send_disable');
	}

	if(!$adminid && $newbiespan && (!$lastpost || $timestamp - $lastpost < $newbiespan * 3600)) {
		$query = $db->query("SELECT regdate FROM {$tablepre}members WHERE uid='$discuz_uid'");
		if($timestamp - ($db->result($query, 0)) < $newbiespan * 3600) {
			showmessage('pm_newbie_span');
		}
	}

	!($exempt & 1) && checklowerlimit($creditspolicy['sendpm'], -1);

	if(!empty($uid)) {
		$msgto = intval($uid);
	} else {
		if(!empty($msgtos)) {
			$buddynum = uc_friend_totalnum($discuz_uid, 3);
			$buddyarray = uc_friend_ls($discuz_uid, 1, $buddynum, $buddynum, 3);
			$uids = array();
			foreach($buddyarray as $buddy) {
				$uids[] = $buddy['friendid'];
			}
			$msgto = $p = '';
			foreach($msgtos as $uid) {
				$msgto .= in_array($uid, $uids) ? $p.$uid : '';
				$p = ',';
			}
			if(!$msgto) {
				showmessage('pm_send_nonexistence');
			}
		} else {
			if(!($uid = $db->result_first("SELECT uid FROM {$tablepre}members WHERE username='$msgto'"))) {
				showmessage('pm_send_nonexistence');
			}
			$msgto = $uid;
		}
	}
	if($discuz_uid == $msgto) {
		showmessage('pm_send_self_ignore');
	}
	if(trim($message) === '') {
		showmessage('pm_send_empty');
	}

	include_once './forumdata/cache/cache_bbcodes.php';
	foreach($_DCACHE['smilies']['replacearray'] AS $key => $smiley) {
		$_DCACHE['smilies']['replacearray'][$key] = '[img]'.$boardurl.'images/smilies/'.$_DCACHE['smileytypes'][$_DCACHE['smilies']['typearray'][$key]]['directory'].'/'.$smiley.'[/img]';
	}
	$message = preg_replace($_DCACHE['smilies']['searcharray'], $_DCACHE['smilies']['replacearray'], $message);

	$pmid = uc_pm_send($discuz_uid, $msgto, '', $message, 1, 0, 0);
	if($pmid > 0) {
		!($exempt & 1) && updatecredits($discuz_uid, $creditspolicy['sendpm'], -1);
		if(empty($sendnew)) {
			$pm = uc_pm_viewnode($discuz_uid, 0, $pmid);
			$dateline = $pm['dateline'] + $timeoffset * 3600;
			$pm['daterange'] = gmdate($dateformat, $dateline);
			$pm['daterange'] = $pm['daterange'] != $lastdaterange ? $pm['daterange'] : '';
			$pm['dateline'] = gmdate("$dateformat $timeformat", $dateline);
			include template('header_ajax');
			include template('pm_node');
			include template('footer_ajax');
			exit;
		} else {
			if($prompts['newbietask'] && $newbietaskid && $newbietasks[$newbietaskid]['scriptname'] == 'sendpm') {
				require_once DISCUZ_ROOT.'./include/task.func.php';
				task_newbie_complete();
			}
			showmessage('pm_send_succeed', '', 1);
			exit;
		}
	} elseif($pmid == -1) {
		showmessage('pm_send_limit1day_error');
	} elseif($pmid == -2) {
		showmessage('pm_send_floodctrl_error');
	} elseif($pmid == -3) {
		showmessage('pm_send_batnotfriend_error');
	} elseif($pmid == -4) {
		showmessage('pm_send_pmsendregdays_error');
	} else {
		showmessage('pm_send_invalid');
	}

} elseif($action == 'del') {

	$uid = !empty($uid) ? (!is_array($uid) ? array($uid) : $uid) : array();
	$pmid = !empty($pmid) ? (!is_array($pmid) ? array($pmid) : $pmid) : array();
	if($uid || $pmid) {
		if(!$readopt) {
			if($uid) {
				uc_pm_deleteuser($discuz_uid, $uid);
			}
			if($pmid) {
				uc_pm_delete($discuz_uid, 'inbox', $pmid);
			}
			showmessage('pm_delete_succeed', "pm.php?filter=$filter&page=$page");
		} else {
			uc_pm_readstatus($discuz_uid, $uid, $pmid, $readopt == 1 ? 0 : 1);
			showmessage($readopt == 1 ? 'pm_mark_read_succeed' : 'pm_mark_unread_succeed', "pm.php?filter=$filter&page=$page");
		}
	} else {
		showmessage('pm_nonexistence', "pm.php?filter=$filter&page=$page");
	}

} elseif($action == 'addblack') {

	if($formhash != FORMHASH) {
		showmessage('undefined_action', NULL, 'HALTED');
	}
	uc_pm_blackls_add($discuz_uid, $user);
	if($user != '{ALL}') {
		showmessage('pm_addblack_succeed', 'pm.php?action=viewblack');
	} else {
		showmessage('pm_addblackall_succeed', 'pm.php?action=viewblack');
	}

} elseif($action == 'delblack') {

	if($formhash != FORMHASH) {
		showmessage('undefined_action', NULL, 'HALTED');
	}
	uc_pm_blackls_delete($discuz_uid, $user);
	showmessage('pm_delblack_succeed', 'pm.php?action=viewblack');

} elseif($action == 'viewblack') {

	$blackls = explode(',', uc_pm_blackls_get($discuz_uid));
	$blackall = in_array('{ALL}', $blackls);

}

include template('pm');

?>