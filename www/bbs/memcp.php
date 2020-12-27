<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: memcp.php 21344 2010-01-06 09:39:22Z zhaoxiongfei $
*/
define('CURSCRIPT', 'memcp');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';

$discuz_action = 7;

if(!$discuz_uid) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

$action = !empty($action) ? $action : '';
$operation = !empty($operation) ? $operation : '';

$maxbiosize = $maxbiosize ? $maxbiosize : 200;

if($regverify == 2 && $groupid == 8 && ($action != 'profile' || $action == 'validating')) {
	$validating = array();
	if($regverify == 2 && $groupid == 8) {
		if($validating = $db->fetch_first("SELECT * FROM {$tablepre}validating WHERE uid='$discuz_uid'")) {
			$validating['moddate'] = $validating['moddate'] ? dgmdate("$dateformat $timeformat", $validating['moddate'] + $timeoffset * 3600) : 0;
			$validating['adminenc'] = rawurlencode($validating['admin']);
		}
	}
	include template('memcp_validating');
	dexit();
}

if(!$action || $action == 'profile') {

	$typeid = empty($typeid) || !in_array($typeid, array(1, 2, 3, 4, 5, 6)) ? 2 : $typeid;
	require_once DISCUZ_ROOT.'./forumdata/cache/cache_profilefields.php';

	$member = $db->fetch_first("SELECT * FROM {$tablepre}members m
		LEFT JOIN {$tablepre}memberfields mf ON mf.uid=m.uid
		WHERE m.uid='$discuz_uid'");

	$seccodecheck = ($seccodestatus & 8) && (!$seccodedata['minposts'] || $posts < $seccodedata['minposts']);
	$secqaacheck = false;
	$member['msn']= explode("\t", $member['msn']);

	if(!submitcheck('editsubmit', 0, $seccodecheck)) {

		if($typeid == 1) {

			if($seccodecheck) {
				$seccode = random(6, 1) + $seccode{0} * 1000000;
			}

		} elseif($typeid == 2) {

			require_once DISCUZ_ROOT.'./include/editor.func.php';

			$gendercheck = array($member['gender'] => 'selected="selected"');
			$member['bio'] = preg_replace("/<imme>(.+)<\/imme>/is", '[imme]', $member['bio']);
			$member['sightml'] = preg_replace("/<imme>(.+)<\/imme>/is", '[imme]', $member['sightml']);
			$member['bio'] = html2bbcode($member['bio']);
			$member['signature'] = html2bbcode($member['sightml']);

		} elseif($typeid == 3) {

			require_once DISCUZ_ROOT.'/uc_client/client.php';
			$uc_avatarflash = uc_avatar($discuz_uid, '', 0);

		} elseif($typeid == 5) {

			$invisiblechecked = $member['invisible'] ? 'checked="checked"' : '';
			$emailchecked = $member['showemail'] ? 'checked="checked"' : '';
			$newschecked = $member['newsletter'] ? 'checked="checked"' : '';
			$tppchecked = array($member['tpp'] => 'checked="checked"');
			$pppchecked = array($member['ppp'] => 'checked="checked"');
			$toselect = array(strval((float)$member['timeoffset']) => 'selected="selected"');
			$pscheck = array(intval($member['pmsound']) => 'checked="checked"');
			$emcheck = array($member['editormode'] => 'checked="checked"');
			$tfcheck = array($member['timeformat'] => 'checked="checked"');
			$dfcheck = array($member['dateformat'] => 'checked="checked"');

			$styleselect = '';
			$query = $db->query("SELECT styleid, name FROM {$tablepre}styles WHERE available='1'");
			while($style = $db->fetch_array($query)) {
				$styleselect .= "<option value=\"$style[styleid]\" ".
					($style['styleid'] == $member['styleid'] ? 'selected="selected"' : NULL).
					">$style[name]</option>\n";
			}

			$customshow = str_pad(base_convert($member['customshow'], 10, 3), 4, '0', STR_PAD_LEFT);
			$dateconvertchecked = array($customshow{0} => 'checked="checked"');
			$sschecked = array($customshow{1} => 'checked="checked"');
			$sachecked = array($customshow{2} => 'checked="checked"');
			$sichecked = array($customshow{3} => 'checked="checked"');

			$creditnoticechecked = array(empty($_COOKIE['discuz_creditnoticedisable']) => 'checked="checked"');

			$dateformatlist = array();
			if(!empty($userdateformat) && ($count = count($userdateformat))) {
				for($num =1; $num <= $count; $num ++) {
					$dateformatlist[$num] = str_replace(array('n', 'j', 'y', 'Y'), array('mm', 'dd', 'yy', 'yyyy'), $userdateformat[$num-1]);
				}
			}

			$feedchecks = array();
			$customaddfeed = intval($member['customaddfeed']);
			if($customaddfeed > 0) {
				$customaddfeed = sprintf('%03b', $customaddfeed);
				for($i = 1; $i <= 3; $i++) {
					$feedchecks[$i] = $customaddfeed[3 - $i] ? 'checked="checked"' : '';
				}
			}

			$defaultcheck = $customaddfeed == 0 ? 'checked="checked"' : '';
			$customcheck = $feedchecks || $customaddfeed < 0 ? 'checked="checked"' : '';
			$showfeedcheck = $customaddfeed == 0 ? 'none' : '';

		}

		include template('memcp_profile');

	} else {

		require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

		$membersql = $memberfieldsql = $authstradd1 = $authstradd2 = $newpasswdadd = '';
		if($typeid == 1) {

			$emailnew = dhtmlspecialchars($emailnew);
			if($questionidnew === '') {
				$secquesnew = $discuz_secques;
				$questionidnew = $answernew = '';
			} else {
				$secquesnew = $questionidnew > 0 ? random(8) : '';
			}

			if(($adminid == 1 || $adminid == 2 || $adminid == 3) && !$secquesnew && $admincp['forcesecques']) {
				showmessage('profile_admin_security_invalid');
			}

			if(!empty($newpassword) && $newpassword != $newpassword2) {
				showmessage('profile_passwd_notmatch');
			}

			require_once DISCUZ_ROOT.'./uc_client/client.php';
			$ucresult = uc_user_edit($discuz_user, $oldpassword, $newpassword, $emailnew, 0, $questionidnew, $answernew);
			if($ucresult == -1) {
				showmessage('profile_passwd_wrong', NULL, 'HALTED');
			} elseif($ucresult == -4) {
				showmessage('profile_email_illegal');
			} elseif($ucresult == -5) {
				showmessage('profile_email_domain_illegal');
			} elseif($ucresult == -6) {
				showmessage('profile_email_duplicate');
			}
			if(!empty($newpassword)) {
				$newpasswdadd = ", password='".md5(random(10))."'";
			}

			if($regverify == 1 && $adminid == 0 && $emailnew != $email && (($grouptype == 'member' && $adminid == 0) || $groupid == 8)) {
				$idstring = random(6);
				$groupid = 8;

				require_once DISCUZ_ROOT.'./forumdata/cache/usergroup_8.php';

				$authstradd1 = ", groupid='8'";
				$authstradd2 = "authstr='$timestamp\t2\t$idstring'";
				sendmail("$discuz_userss <$emailnew>", 'email_verify_subject', 'email_verify_message');
			}

			$membersql = "secques='$secquesnew', email='$emailnew' $newpasswdadd $authstradd1";
			$memberfieldsql = $authstradd2;

		} elseif($typeid == 2) {

			$censorexp = '/^('.str_replace(array('\\*', "\r\n", ' '), array('.*', '|', ''), preg_quote(($censoruser = trim($censoruser)), '/')).')$/i';
			if($censoruser && (@preg_match($censorexp, $nicknamenew) || @preg_match($censorexp, $cstatusnew))) {
				showmessage('profile_nickname_cstatus_illegal');
			}

			if($msnnew && !isemail($msnnew)) {
				showmessage('profile_alipay_msn');
			}

			$sitenew = !preg_match("/^http:\/\/$/i", $sitenew) ? (dhtmlspecialchars(trim(preg_match("/^https?:\/\/.+/i", $sitenew) ? $sitenew : ($sitenew ? 'http://'.$sitenew : '')))) : '';

			$icqnew = preg_match ("/^([0-9]+)$/", $icqnew) && strlen($icqnew) >= 5 && strlen($icqnew) <= 12 ? $icqnew : '';
			$qqnew = preg_match ("/^([0-9]+)$/", $qqnew) && strlen($qqnew) >= 5 && strlen($qqnew) <= 12 ? $qqnew : '';
			$bdaynew = datecheck($bdaynew) ? $bdaynew : '0000-00-00';
			$yahoonew = dhtmlspecialchars($yahoonew);
			$msnnew = dhtmlspecialchars($msnnew);
			$msnnew = "$msnnew\t{$member[msn][1]}";
			$taobaonew = dhtmlspecialchars($taobaonew);
			$alipaynew = dhtmlspecialchars($alipaynew);
			$nicknamenew = $allownickname ? cutstr(censor(dhtmlspecialchars($nicknamenew)), 30) : '';
			$cstatusadd = $allowcstatus ? ', customstatus=\''.cutstr(censor(dhtmlspecialchars($cstatusnew)), 30).'\'' : '';
			$gendernew = empty($gendernew) ? 0 : intval($gendernew);
			$locationnew = cutstr(censor(dhtmlspecialchars($locationnew)), 30);

			if($maxsigsize) {
				if(strlen($signaturenew) > $maxsigsize) {
					showmessage('profile_sig_toolong');
				}
			} else {
				$signaturenew = '';
			}

			$signaturenew = censor($signaturenew);
			$sigstatusnew = $signaturenew ? 1 : 0;
			$bionew = censor(dhtmlspecialchars($bionew));

			$sightmlnew = discuzcode(stripslashes($signaturenew), 1, 0, 0, 0, $allowsigbbcode, $allowsigimgcode, 0, 0, 1);
			$biohtmlnew = discuzcode(stripslashes($bionew), 1, 0, 0, 0, $allowbiobbcode, $allowbioimgcode, 0, 0, 1);
			if($member['msn'][1]) {
				if(strpos(strtolower($sightmlnew), '[imme]') !== FALSE) {
					$sightmlnew = str_replace('[imme]', "<imme><a target='_blank' href='http://settings.messenger.live.com/Conversation/IMMe.aspx?invitee=".$member['msn'][1]."@apps.messenger.live.com&mkt=zh-cn' title='MSN'><img style='vertical-align:middle' src='http://messenger.services.live.com/users/".$member['msn'][1]."@apps.messenger.live.com/presenceimage?mkt=zh-cn' width='16' height='16' /></a></imme>", $sightmlnew);
				}
				if(strpos(strtolower($biohtmlnew), '[imme]') !== FALSE) {
					$biohtmlnew = str_replace('[imme]', "<imme><a target='_blank' href='http://settings.messenger.live.com/Conversation/IMMe.aspx?invitee=".$member['msn'][1]."@apps.messenger.live.com&mkt=zh-cn' title='MSN'><img style='vertical-align:middle' src='http://messenger.services.live.com/users/".$member['msn'][1]."@apps.messenger.live.com/presenceimage?mkt=zh-cn' width='16' height='16' /></a></imme>", $biohtmlnew);
				}
			}
			$sightmlnew = addslashes($sightmlnew);
			$biohtmlnew = addslashes($biohtmlnew);

			$membersql = "gender='$gendernew', bday='$bdaynew', sigstatus='$sigstatusnew'";
			$memberfieldsql = "nickname='$nicknamenew', site='$sitenew', location='$locationnew', icq='$icqnew', qq='$qqnew', yahoo='$yahoonew', msn='$msnnew', taobao='$taobaonew', alipay='$alipaynew', bio='$biohtmlnew', sightml='$sightmlnew' $cstatusadd";

			if($_DCACHE['fields_required'] || $_DCACHE['fields_optional']) {
				$fieldadd = array();
				foreach(array_merge($_DCACHE['fields_required'], $_DCACHE['fields_optional']) as $field) {
					$field_key = 'field_'.$field['fieldid'];
					$field_val = trim(${'field_'.$field['fieldid'].'new'});
					if($field['required'] && $field_val == '' && !($field['unchangeable'] && $member[$field_key])) {
						showmessage('profile_required_info_invalid');
					} elseif($field['selective'] && $field_val != '' && !isset($field['choices'][$field_val])) {
						showmessage('undefined_action', NULL, 'HALTED');
					} elseif(!$field['unchangeable'] || !$member[$field_key]) {
						$fieldadd[] = "$field_key='".dhtmlspecialchars($field_val)."'";
					}
				}

				if(!empty($fieldadd)) {
					$memberfieldsql .= ', '.implode(', ', $fieldadd);
				}
			}

		} else {

			$tppnew = in_array($tppnew, array(10, 20, 30)) ? $tppnew : 0;
			$pppnew = in_array($pppnew, array(5, 10, 15)) ? $pppnew : 0;
			$editormodenew = in_array($editormodenew, array(0, 1, 2)) ? $editormodenew : 2;
			$ssnew = in_array($ssnew, array(0, 1)) ? $ssnew : 2;
			$sanew = in_array($sanew, array(0, 1)) ? $sanew : 2;
			$sinew = in_array($sinew, array(0, 1)) ? $sinew : 2;
			$dateconvertnew = $dateconvertnew ? 1 : 0;
			$customshownew = base_convert($dateconvertnew.$ssnew.$sanew.$sinew, 3, 10);
			$dateformatnew = ($dateformatnew = intval($dateformatnew)) && !empty($userdateformat[$dateformatnew -1]) ? $dateformatnew : 0;
			$invisiblenew = $allowinvisible && $invisiblenew ? 1 : 0;
			$showemailnew = empty($showemailnew) ? 0 : 1;
			$styleid = empty($styleidnew) ? $styleid : $styleidnew;

			if($customaddfeednew) {
				$customaddfeednew = $addfeed[1] || $addfeed[2] ||$addfeed[3] ? bindec(intval($addfeed[3]).intval($addfeed[2]).intval($addfeed[1])) : '-1';
			}
			if($creditnoticenew) {
				dsetcookie('discuz_creditnoticedisable', '', -31536000, '');
			}
			$disablepromptnewary = array();
			foreach($prompts as $promptkey => $promptdata) {
				if($promptkey == 'newbietask') {
					continue;
				}
				if(empty($disablepromptnew[$promptkey])) {
					$disablepromptnewary[] = $promptkey;
				}
			}
			dsetcookie('disableprompt', implode('|', $disablepromptnewary), 31536000);

			$membersql = "styleid='$styleidnew', showemail='$showemailnew', timeoffset='$timeoffsetnew', tpp='$tppnew', ppp='$pppnew', editormode='$editormodenew', customshow='$customshownew', newsletter='$newsletternew', invisible='$invisiblenew', timeformat='$timeformatnew', dateformat='$dateformatnew', pmsound='$pmsoundnew', customaddfeed='$customaddfeednew'";

		}

		if($membersql) {
			$db->query("UPDATE {$tablepre}members SET $membersql WHERE uid='$discuz_uid'");
		}

		$query = $db->query("SELECT uid FROM {$tablepre}memberfields WHERE uid='$discuz_uid'");
		if(!$db->num_rows($query)) {
			$db->query("REPLACE INTO {$tablepre}memberfields (uid) VALUES ('$discuz_uid')");
		}

		if($memberfieldsql) {
			$db->query("UPDATE {$tablepre}memberfields SET $memberfieldsql WHERE uid='$discuz_uid'");
		}

		if($prompts['newbietask'] && $newbietaskid && $newbietasks[$newbietaskid]['scriptname'] == 'modifyprofile') {
			require_once DISCUZ_ROOT.'./include/task.func.php';
			task_newbie_complete();
			$msgforward = unserialize($_DCACHE['settings']['msgforward']);
			$msgforward['refreshtime'] = 9999999999;
			$_DCACHE['settings']['msgforward'] = serialize($msgforward);
		}

		manyoulog('user', $discuz_uid, 'update');

		if($type == 1 && !empty($authstradd1) && !empty($authstradd2)) {
			showmessage('profile_email_verify');
		} else {
			showmessage('profile_succeed', 'memcp.php?action=profile&typeid='.$typeid);
		}
	}

} elseif($action == 'credits') {

	$taxpercent = sprintf('%1.2f', $creditstax * 100).'%';

	if($creditspolicy['promotion_visit'] || $creditspolicy['promotion_register']) {
		$promotion_visit = $promotion_register = $space = '';
		foreach(array('promotion_visit', 'promotion_register') as $val) {
			if(!empty($creditspolicy[$val]) && is_array($creditspolicy[$val])) {
				foreach($creditspolicy[$val] as $id => $policy) {
					$$val .= $space.$extcredits[$id]['title'].' +'.$policy;
					$space = '&nbsp;';
				}
			}
		}
	}

	if(submitcheck('transfersubmit')) {
		if($transferstatus) {
			if(!submitcheck('confirm')) {

				$to = $db->result_first("SELECT username FROM {$tablepre}members WHERE username='$to'");
				include template('memcp_credits_action');

			} else {

				$amount = intval($amount);

				require_once DISCUZ_ROOT.'./uc_client/client.php';
				$ucresult = uc_user_login($discuz_user, $password);
				list($tmp['uid']) = daddslashes($ucresult);

				if($tmp['uid'] <= 0) {
					showmessage('credits_password_invalid');
				} elseif($amount <= 0) {
					showmessage('credits_transaction_amount_invalid');
				} elseif(${'extcredits'.$creditstrans} - $amount < ($minbalance = $transfermincredits)) {
					showmessage('credits_balance_insufficient');
				} elseif(!($netamount = floor($amount * (1 - $creditstax)))) {
					showmessage('credits_net_amount_iszero');
				}

				$member = $db->fetch_first("SELECT uid, username FROM {$tablepre}members WHERE username='$to'");
				if(!$member) {
					showmessage('credits_transfer_send_nonexistence');
				} elseif($member['uid'] == $discuz_uid) {
					showmessage('credits_transfer_self');
				}

				$creditsarray[$creditstrans] = -$amount;
				updatecredits($discuz_uid, $creditsarray);
				$db->query("UPDATE {$tablepre}members SET extcredits$creditstrans=extcredits$creditstrans+'$netamount' WHERE uid='$member[uid]'");
				$db->query("INSERT INTO {$tablepre}creditslog (uid, fromto, sendcredits, receivecredits, send, receive, dateline, operation)
					VALUES ('$discuz_uid', '".addslashes($member['username'])."', '$creditstrans', '$creditstrans', '$amount', '0', '$timestamp', 'TFR'),
					('$member[uid]', '$discuz_user', '$creditstrans', '$creditstrans', '0', '$netamount', '$timestamp', 'RCV')");

				if(!empty($transfermessage)) {
					$transfermessage = stripslashes($transfermessage);
					$transfertime = gmdate($GLOBALS['_DCACHE']['settings']['dateformat'].' '.$GLOBALS['_DCACHE']['settings']['timeformat'], $timestamp + $timeoffset * 3600);
					sendnotice($member['uid'], 'transfer', 'systempm');
				}

				showmessage('credits_transaction_succeed', '', 1);
			}
		} else {
			showmessage('action_closed', NULL, 'HALTED');
		}

	} elseif(submitcheck('exchangesubmit')) {

		if(($exchangestatus || $outextcredits) && $outextcredits[$tocredits] || $extcredits[$fromcredits]['ratio'] && $extcredits[$tocredits]['ratio']) {
			if(!submitcheck('confirm')) {

				$outexange = strexists($tocredits, '|');
				if($outexange) {
					$netamount = floor($exchangeamount * $outextcredits[$tocredits]['ratiosrc'][${'fromcredits_'.$outi}] / $outextcredits[$tocredits]['ratiodesc'][${'fromcredits_'.$outi}]);
					$fromcredits = ${'fromcredits_'.$outi};
				} else {
					if($extcredits[$tocredits]['ratio'] < $extcredits[$fromcredits]['ratio']) {
						$netamount = ceil($exchangeamount * $extcredits[$tocredits]['ratio'] / $extcredits[$fromcredits]['ratio'] * (1 + $creditstax));
					} else {
						$netamount = floor($exchangeamount * $extcredits[$tocredits]['ratio'] / $extcredits[$fromcredits]['ratio'] * (1 + $creditstax));
					}
				}

				include template('memcp_credits_action');

			} else {

				if(!$outexange && !$extcredits[$tocredits]['ratio']) {
					showmessage('credits_exchange_invalid');
				}

				$amount = intval($amount);
				if($outexange) {
					$netamount = floor($amount * $outextcredits[$tocredits]['ratiosrc'][$fromcredits] / $outextcredits[$tocredits]['ratiodesc'][$fromcredits]);
				} else {
					if($extcredits[$tocredits]['ratio'] < $extcredits[$fromcredits]['ratio']) {
						$netamount = ceil($amount * $extcredits[$tocredits]['ratio'] / $extcredits[$fromcredits]['ratio'] * (1 + $creditstax));
					} else {
						$netamount = floor($amount * $extcredits[$tocredits]['ratio'] / $extcredits[$fromcredits]['ratio'] * (1 + $creditstax));
					}
				}

				require_once DISCUZ_ROOT.'./uc_client/client.php';
				$ucresult = uc_user_login($discuz_user, $password);
				list($tmp['uid']) = daddslashes($ucresult);

				if($tmp['uid'] <= 0) {
					showmessage('credits_password_invalid');
				} elseif($fromcredits == $tocredits) {
					showmessage('credits_exchange_invalid');
				} elseif($amount <= 0) {
					showmessage('credits_transaction_amount_invalid');
				} elseif(${'extcredits'.$fromcredits} - $netamount < ($minbalance = $exchangemincredits)) {
					showmessage('credits_balance_insufficient');
				} elseif(!$outexange && !$netamount) {
					showmessage('credits_net_amount_iszero');
				}
				if(!$outexange && !$extcredits[$fromcredits]['allowexchangeout']) {
					showmessage('extcredits_disallowexchangeout');
				}
				if(!$outexange && !$extcredits[$tocredits]['allowexchangein']) {
					showmessage('extcredits_disallowexchangein');
				}

				if(!$outexange) {
					$creditsarray[$fromcredits] = -$netamount;
					$creditsarray[$tocredits] = $amount;
					updatecredits($discuz_uid, $creditsarray);
				} else {
					if(!array_key_exists($fromcredits, $outextcredits[$tocredits]['creditsrc'])) {
						showmessage('extcredits_dataerror', NULL, 'HALTED');
					}
					list($toappid, $tocredits) = explode('|', $tocredits);
					$ucresult = uc_credit_exchange_request($discuz_uid, $fromcredits, $tocredits, $toappid, $amount);
					if(!$ucresult) {
						showmessage('extcredits_dataerror', NULL, 'HALTED');
					}
					$creditsarray[$fromcredits] = -$netamount;
					updatecredits($discuz_uid, $creditsarray);
					$netamount = $amount;
					$amount = $tocredits = 0;
				}

				$db->query("INSERT INTO {$tablepre}creditslog (uid, fromto, sendcredits, receivecredits, send, receive, dateline, operation)
					VALUES ('$discuz_uid', '$discuz_user', '$fromcredits', '$tocredits', '$netamount', '$amount', '$timestamp', 'EXC')");

				showmessage('credits_transaction_succeed', '', 1);
			}
		} else {
			showmessage('action_closed', NULL, 'HALTED');
		}

	} elseif(submitcheck('addfundssubmit')) {

		if($ec_ratio) {
			if(!submitcheck('confirm')) {

				$price = round(($addfundamount / $ec_ratio * 100) / 100, 2);
				include template('memcp_credits_action');

			} else {

				include language('misc');
				$amount = intval($amount);
				if(!$amount || ($ec_mincredits && $amount < $ec_mincredits) || ($ec_maxcredits && $amount > $ec_maxcredits)) {
					showmessage('credits_addfunds_amount_invalid');
				}

				if($db->result_first("SELECT COUNT(*) FROM {$tablepre}orders WHERE uid='$discuz_uid' AND submitdate>='$timestamp'-180 LIMIT 1")) {
					showmessage('credits_addfunds_ctrl');
				}

				if($ec_maxcreditspermonth) {
					$query = $db->query("SELECT SUM(amount) FROM {$tablepre}orders WHERE uid='$discuz_uid' AND submitdate>='$timestamp'-2592000 AND status IN (2, 3)");
					if(($db->result($query, 0)) + $amount > $ec_maxcreditspermonth) {
						showmessage('credits_addfunds_toomuch');
					}
				}

				$price = round(($amount / $ec_ratio * 100) / 100, 2);
				$orderid = '';

				//$apitype = 'tenpay';
				require_once DISCUZ_ROOT.'./include/trade.func.php';
				$requesturl = credit_payurl($price, $orderid);

				$query = $db->query("SELECT orderid FROM {$tablepre}orders WHERE orderid='$orderid'");
				if($db->num_rows($query)) {
					showmessage('credits_addfunds_order_invalid');
				}

				$db->query("INSERT INTO {$tablepre}orders (orderid, status, uid, amount, price, submitdate)
					VALUES ('$orderid', '1', '$discuz_uid', '$amount', '$price', '$timestamp')");

				showmessage('credits_addfunds_succeed', $requesturl);

			}
		} else {
			showmessage('action_closed', NULL, 'HALTED');
		}

	} else {

		$extcredits_exchange = array();

		if(!empty($extcredits)) {
			foreach($extcredits as $key => $value) {
				if($value['allowexchangein'] || $value['allowexchangeout']) {
					$extcredits_exchange['extcredits'.$key] = array('title' => $value['title'], 'unit' => $value['unit']);
				}
			}
		}

		include template('memcp_credits');

	}

} elseif($action == 'creditslog') {

	if($operation == 'paymentlog') {

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}paymentlog WHERE uid='$discuz_uid'");
		$multipage = multi($db->result($query, 0), $tpp, $page, "memcp.php?action=creditslog&amp;operation=paymentlog");
		$totalamount = $db->result($query, 1);

		$loglist = array();
		$query = $db->query("SELECT p.*, f.fid, f.name, t.subject, t.author, t.dateline AS tdateline FROM {$tablepre}paymentlog p
			LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
			LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
			WHERE p.uid='$discuz_uid' ORDER BY p.dateline DESC
			LIMIT $start_limit, $tpp");
		while($log = $db->fetch_array($query)) {
			$log['authorenc'] = rawurlencode($log['authorenc']);
			$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
			$log['tdateline'] = dgmdate("$dateformat $timeformat", $log['tdateline'] + $timeoffset * 3600);
			$loglist[] = $log;
		}

		include template('memcp_credits_log');

	} elseif($operation == 'incomelog') {

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}paymentlog WHERE authorid='$discuz_uid'");
		$multipage = multi($db->result($query, 0), $tpp, $page, "memcp.php?action=creditslog&amp;operation=incomelog");

		$loglist = array();
		$query = $db->query("SELECT p.*, m.username, f.fid, f.name, t.subject, t.dateline AS tdateline FROM {$tablepre}paymentlog p
			LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
			LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
			LEFT JOIN {$tablepre}members m ON m.uid=p.uid
			WHERE p.authorid='$discuz_uid' ORDER BY p.dateline DESC
			LIMIT $start_limit, $tpp");
		while($log = $db->fetch_array($query)) {
			$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
			$log['tdateline'] = dgmdate("$dateformat $timeformat", $log['tdateline'] + $timeoffset * 3600);
			$loglist[] = $log;
		}

		include template('memcp_credits_log');

	} elseif($operation == 'attachpaymentlog') {

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}attachpaymentlog WHERE uid='$discuz_uid'");
		$multipage = multi($db->result($query, 0), $tpp, $page, "memcp.php?action=creditslog&amp;operation=attachpaymentlog");

		$loglist = array();
		$query = $db->query("SELECT ap.*, a.filename, a.pid, a.dateline as adateline, t.subject, t.tid, m.username FROM {$tablepre}attachpaymentlog ap
			LEFT JOIN {$tablepre}attachments a ON a.aid=ap.aid
			LEFT JOIN {$tablepre}threads t ON t.tid=a.tid
			LEFT JOIN {$tablepre}members m ON m.uid=ap.authorid
			WHERE ap.uid='$discuz_uid' ORDER BY ap.dateline DESC
			LIMIT $start_limit, $tpp");
		while($log = $db->fetch_array($query)) {
			$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
			$log['adateline'] = dgmdate("$dateformat $timeformat", $log['adateline'] + $timeoffset * 3600);
			$loglist[] = $log;
		}

		include template('memcp_credits_log');


	} elseif($operation == 'attachincomelog') {

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}attachpaymentlog WHERE authorid='$discuz_uid'");
		$multipage = multi($db->result($query, 0), $tpp, $page, "memcp.php?action=creditslog&amp;operation=attachincomelog");
		$totalamount = $db->result($query, 1);

		$loglist = array();
		$query = $db->query("SELECT ap.*, a.filename, a.pid, a.dateline as adateline, t.subject, t.tid, m.username FROM {$tablepre}attachpaymentlog ap
			LEFT JOIN {$tablepre}attachments a ON a.aid=ap.aid
			LEFT JOIN {$tablepre}threads t ON t.tid=a.tid
			LEFT JOIN {$tablepre}members m ON m.uid=ap.uid
			WHERE ap.authorid='$discuz_uid' ORDER BY ap.dateline DESC
			LIMIT $start_limit, $tpp");
		while($log = $db->fetch_array($query)) {
			$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
			$log['adateline'] = dgmdate("$dateformat $timeformat", $log['adateline'] + $timeoffset * 3600);
			$loglist[] = $log;
		}

		include template('memcp_credits_log');

	} elseif($operation == 'rewardpaylog') {

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}rewardlog WHERE authorid='$discuz_uid'");
		$multipage = multi($db->result($query, 0), $tpp, $page, "memcp.php?action=creditslog&amp;operation=incomelog");

		$loglist = array();
		$query = $db->query("SELECT
			r.*, m.uid, m.username
			, f.fid, f.name, t.subject, t.price
			FROM
			{$tablepre}rewardlog r
			LEFT JOIN {$tablepre}threads t ON t.tid=r.tid
			LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
			LEFT JOIN {$tablepre}members m ON m.uid=r.answererid
			WHERE r.authorid='$discuz_uid' ORDER BY r.dateline DESC
			LIMIT $start_limit, $tpp");
		while($log = $db->fetch_array($query)) {
			$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
			$log['price'] = abs($log['price']);
			$loglist[] = $log;
		}

		include template('memcp_credits_log');

	} elseif($operation == 'rewardincomelog') {

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}rewardlog WHERE answererid='$discuz_uid'");
		$multipage = multi($db->result($query, 0), $tpp, $page, "memcp.php?action=creditslog&amp;operation=incomelog");

		$loglist = array();
		$query = $db->query("SELECT r.*, m.uid, m.username, f.fid, f.name, t.subject, t.price FROM {$tablepre}rewardlog r
			LEFT JOIN {$tablepre}threads t ON t.tid=r.tid
			LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
			LEFT JOIN {$tablepre}members m ON m.uid=r.authorid
			WHERE r.answererid='$discuz_uid' and r.authorid>0 ORDER BY r.dateline DESC
			LIMIT $start_limit, $tpp");
		while($log = $db->fetch_array($query)) {
			$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
			$log['price'] = abs($log['price']);
			$loglist[] = $log;
		}

		include template('memcp_credits_log');

	} else {

		$operation = 'creditslog';

		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}creditslog WHERE uid='$discuz_uid'");
		$multipage = multi($db->result($query, 0), $tpp, $page, "memcp.php?action=creditslog&amp;operation=creditslog");

		$loglist = array();
		$query = $db->query("SELECT * FROM {$tablepre}creditslog WHERE uid='$discuz_uid' ORDER BY dateline DESC LIMIT $start_limit, $tpp");
		while($log = $db->fetch_array($query)) {
			$log['fromtoenc'] = rawurlencode($log['fromto']);
			$log['dateline'] = dgmdate("$dateformat $timeformat", $log['dateline'] + $timeoffset * 3600);
			$loglist[] = $log;
		}

		include template('memcp_credits_log');

	}

} elseif($action == 'usergroups') {

	require_once DISCUZ_ROOT.'./include/forum.func.php';

//	if(!$allowmultigroups) {
//		showmessage('group_nopermission', NULL, 'NOPERM');
//	}

	$switchmaingroup = $grouppublic || $grouptype == 'member' ? 1 : 0;

	$extgroupidarray = $extgroupids ? explode("\t", $extgroupids) : array();
	foreach($extgroupidarray as $key => $val) {
		$val = intval($val);
		if($val <  1) {
			unset($extgroupidarray[$key]);
		} else {
			$extgroupidarray[$key] = intval($val);
		}
	}
	if(implode("\t", $extgroupidarray) != $extgroupids) {
		$extgroupids = implode("\t", $extgroupidarray);
		$groupexpiryadd= empty($extgroupids) ? ", groupexpiry=''" : '';
		$db->query("UPDATE {$tablepre}members SET extgroupids='$extgroupids' $groupexpiryadd WHERE uid='$discuz_uid'");
	}

	if(empty($type)) {

		$groupterms = unserialize($db->result_first("SELECT groupterms FROM {$tablepre}memberfields WHERE uid='$discuz_uid'"));

		$maingroup = $extgroups = $publicgroups = $grouplist = array();

		$query = $db->query("SELECT * FROM {$tablepre}usergroups WHERE (type='special' AND system<>'private' AND radminid='0') OR (type='member' AND '$credits'>=creditshigher AND '$credits'<creditslower) OR groupid IN ('$groupid'".($extgroupids ? ', '.str_replace("\t", ',', $extgroupids) : '').") ORDER BY type, system");
		while($group = $db->fetch_array($query)) {

			if(!$group['allowmultigroups']) {
				$group['grouptitle'] = '<u>'.$group['grouptitle'].'</u>';
			}

			$group['mainselected'] = $group['groupid'] == $groupid ? 'checked="checked"' : '';
			$group['maindisabled'] = $switchmaingroup && (($group['system'] != 'private' && ($group['system'] == "0\t0" || $group['groupid'] == $groupid || in_array($group['groupid'], $extgroupidarray))) || $group['type'] == 'member') ? '' : 'disabled';
			$group['allowsetmain'] = $switchmaingroup && (($group['system'] != 'private' && ($group['system'] == "0\t0" || $group['groupid'] == $groupid || in_array($group['groupid'], $extgroupidarray))) || $group['type'] == 'member') ? true : false;
			$group['dailyprice'] = $group['minspan'] = 0;

			if($group['system'] != 'private') {
				list($group['dailyprice'], $group['minspan']) = explode("\t", $group['system']);
			}

			if($group['groupid'] == $groupid && !empty($groupterms['main'])) {
				$group['expiry'] = gmdate($dateformat, $groupterms['main']['time'] + $timeoffset * 3600);
			} elseif(isset($groupterms['ext'][$group['groupid']])) {
				$group['expiry'] = gmdate($dateformat, $groupterms['ext'][$group['groupid']] + $timeoffset * 3600);
			} else {
				$group['expiry'] = '-';
			}

			if($group['groupid'] == $groupid) {
				$maingroup = $group;
				$group['owned'] = true;
			} elseif(in_array($group['groupid'], $extgroupidarray)) {
				$extgroups[] = $group;
				$group['owned'] = true;
			} else {
				$group['owned'] = false;
			}

			if($group['system'] != 'private') {
				$publicgroups[$group['groupid']] = $group;
			}

		}

		$query = $db->query("SELECT * FROM {$tablepre}usergroups WHERE type!='system' ORDER BY type, creditshigher");
		while($group = $db->fetch_array($query)) {
			$group['type'] = $group['type'] == 'special' && $group['radminid'] ? 'specialadmin' : $group['type'];
			$grouplist[$group['type']][] = $group;
		}

		include template('memcp_usergroups');

	} else {

		if($type == 'main' && $switchmaingroup) {


			$edit = intval($edit);
			$group = $db->fetch_first("SELECT groupid, type, system, grouptitle, allowmultigroups FROM {$tablepre}usergroups WHERE groupid='$edit' AND (".($extgroupids ? 'groupid IN ('.str_replace("\t", ',', $extgroupids).') OR ' : '')."(type='special' AND system='0\t0' AND radminid='0') OR (type='member' AND '$credits'>=creditshigher AND '$credits'<creditslower))");
			if(!$group) {
				showmessage('undefined_action', NULL, 'HALTED');
			}

			if(!submitcheck('groupsubmit')) {
				include template('memcp_usergroups_misc');
				dexit();
			}

			$extgroupidsnew = $groupid;
			foreach(explode("\t", $extgroupids) as $extgroupid) {
				if($extgroupid && $extgroupid != $edit) {
					$extgroupidsnew .= "\t".$extgroupid;
				}
			}
			$adminidnew = in_array($adminid, array(1, 2, 3)) ? $adminid : ($group['type'] == 'special' ? -1 : 0);

			$db->query("UPDATE {$tablepre}members SET groupid='$edit', adminid='$adminidnew', extgroupids='$extgroupidsnew' WHERE uid='$discuz_uid'");
			showmessage('usergroups_update_succeed', 'memcp.php?action=usergroups');


		} elseif($type == 'extended') {


			$group = $db->fetch_first("SELECT groupid, type, system, grouptitle FROM {$tablepre}usergroups WHERE groupid='$edit' AND (".($extgroupids ? 'groupid IN ('.str_replace("\t", ',', $extgroupids).') OR ' : '')."(type='special' AND system<>'private' AND radminid='0'))");
			if(!$group) {
				showmessage('undefined_action', NULL, 'HALTED');
			}

			$join = !in_array($group['groupid'], explode("\t", $extgroupids));
			$group['dailyprice'] = $group['minspan'] = 0;

			if($group['system'] != 'private') {
				list($group['dailyprice'], $group['minspan']) = explode("\t", $group['system']);
				if($group['dailyprice'] > -1 && $group['minspan'] == 0) {
					 $group['minspan'] = 1;
				}
			}

			if(!isset($extcredits[$creditstrans])) {
				showmessage('credits_transaction_disabled');
			}

			if(!submitcheck('groupsubmit')) {

				$usermoney = ${'extcredits'.$creditstrans};
				$usermaxdays = $group['dailyprice'] > 0 ? round($usermoney / $group['dailyprice']) : 0;
				$group['minamount'] = $group['dailyprice'] * $group['minspan'];

				include template('memcp_usergroups_misc');

			} else {

				$groupterms = unserialize($db->result_first("SELECT groupterms FROM {$tablepre}memberfields WHERE uid='$discuz_uid'"));

				if($join) {

					$extgroupidsarray = array();
					foreach(array_unique(array_merge(explode("\t", $extgroupids), array($edit))) as $extgroupid) {
						if($extgroupid) {
							$extgroupidsarray[] = $extgroupid;
						}
					}
					$extgroupidsnew = implode("\t", $extgroupidsarray);

					if($group['dailyprice']) {
						if(($days = intval($days)) < $group['minspan']) {
							showmessage('usergroups_span_invalid');
						}

						if(${'extcredits'.$creditstrans} - ($amount = $days * $group['dailyprice']) < ($minbalance = 0)) {
							showmessage('credits_balance_insufficient');
						}

						$groupexpirynew = $timestamp + $days * 86400;
						$groupterms['ext'][$edit] = $groupexpirynew;

						$groupexpirynew = groupexpiry($groupterms);

						$db->query("UPDATE {$tablepre}members SET groupexpiry='$groupexpirynew', extgroupids='$extgroupidsnew', extcredits$creditstrans=extcredits$creditstrans-'$amount' WHERE uid='$discuz_uid'");
						$db->query("UPDATE {$tablepre}memberfields SET groupterms='".addslashes(serialize($groupterms))."' WHERE uid='$discuz_uid'");
						$db->query("INSERT INTO {$tablepre}creditslog (uid, fromto, sendcredits, receivecredits, send, receive, dateline, operation)
							VALUES ('$discuz_uid', '$discuz_user', '$creditstrans', '0', '$amount', '0', '$timestamp', 'UGP')");
					} else {
						$db->query("UPDATE {$tablepre}members SET extgroupids='$extgroupidsnew' WHERE uid='$discuz_uid'");
					}

					showmessage('usergroups_join_succeed', 'memcp.php?action=usergroups');

				} else {

					if($edit != $groupid) {
						if(isset($groupterms['ext'][$edit])) {
							unset($groupterms['ext'][$edit]);
						}
						$groupexpirynew = groupexpiry($groupterms);
						$db->query("UPDATE {$tablepre}memberfields SET groupterms='".addslashes(serialize($groupterms))."' WHERE uid='$discuz_uid'");
					} else {
						$groupexpirynew = 'groupexpiry';
					}

					$extgroupidsarray = array();
					foreach(explode("\t", $extgroupids) as $extgroupid) {
						if($extgroupid && $extgroupid != $edit) {
							$extgroupidsarray[] = $extgroupid;
						}
					}
					$extgroupidsnew = implode("\t", array_unique($extgroupidsarray));
					$db->query("UPDATE {$tablepre}members SET groupexpiry='$groupexpirynew', extgroupids='$extgroupidsnew' WHERE uid='$discuz_uid'");

					showmessage('usergroups_exit_succeed', 'memcp.php?action=usergroups');

				}

			}

		} else {

			showmessage('usergroups_nonexistence');

		}

	}

}

?>