<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: viewthread_trade.inc.php 19254 2009-08-20 05:33:53Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(empty($do) || $do == 'tradeinfo') {

	if($do == 'tradeinfo') {
		$tradelistadd = "AND pid = '$pid'";
	} else {
		$tradelistadd = '';
		!$tradenum && $allowpostreply = FALSE;
	}

	$query = $db->query("SELECT * FROM {$tablepre}trades WHERE tid='$tid' $tradelistadd ORDER BY displayorder");
	$trades = $tradesstick = array();$tradelist = 0;
	if(empty($do)) {
		$sellerid = 0;
		$listcount = $db->num_rows($query);
		$tradelist = $tradenum - $listcount;
	}

	$tradesaids = $tradesaids = array();
	while($trade = $db->fetch_array($query)) {
		if($trade['expiration']) {
			$trade['expiration'] = ($trade['expiration'] - $timestamp) / 86400;
			if($trade['expiration'] > 0) {
				$trade['expirationhour'] = floor(($trade['expiration'] - floor($trade['expiration'])) * 24);
				$trade['expiration'] = floor($trade['expiration']);
			} else {
				$trade['expiration'] = -1;
			}
		}
		$tradesaids[] = $trade['aid'];
		$tradespids[] = $trade['pid'];
		if($trade['displayorder'] < 0) {
			$trades[$trade['pid']] = $trade;
		} else {
			$tradesstick[$trade['pid']] = $trade;
		}
	}
	$trades = $tradesstick + $trades;
	$tradespids = implodeids($tradespids);
	unset($trade);

	if($tradespids) {
		$query = $db->query("SELECT a.*,af.description FROM {$tablepre}attachments a LEFT JOIN {$tablepre}attachmentfields af ON a.aid=af.aid WHERE a.pid IN ($tradespids)");
		while($attach = $db->fetch_array($query)) {
			if($attach['isimage'] && is_array($tradesaids) && in_array($attach['aid'], $tradesaids)) {
				$trades[$attach['pid']]['attachurl'] = ($attach['remote'] ? $ftp['attachurl'] : $attachurl).'/'.$attach['attachment'];
				$trades[$attach['pid']]['thumb'] = $trades[$attach['pid']]['attachurl'].($attach['thumb'] ? '.thumb.jpg' : '');
			}
		}
	}

	if($do == 'tradeinfo') {
		$subjectpos = strrpos($navigation, '&raquo; ');
		$subject = substr($navigation, $subjectpos + 8);
		$navigation = substr($navigation, 0, $subjectpos).'&raquo; <a href="viewthread.php?tid='.$tid.'">'.$subject.'</a>';
		$trade = $trades[$pid];
		unset($trades);

		$post = $db->fetch_first("SELECT p.*, m.uid, m.username, m.groupid, m.adminid, m.regdate, m.lastactivity, m.posts, m.digestposts, m.oltime,
			m.pageviews, m.credits, m.extcredits1, m.extcredits2, m.extcredits3, m.extcredits4, m.extcredits5, m.extcredits6,
			m.extcredits7, m.extcredits8, m.email, m.gender, m.showemail, m.invisible, mf.nickname, mf.site,
			mf.icq, mf.qq, mf.yahoo, mf.msn, mf.taobao, mf.alipay, mf.location, mf.medals,
			mf.avatarheight, mf.customstatus, mf.spacename, mf.buyercredit, mf.sellercredit $fieldsadd
			FROM {$tablepre}posts p
			LEFT JOIN {$tablepre}members m ON m.uid=p.authorid
			LEFT JOIN {$tablepre}memberfields mf ON mf.uid=m.uid
			WHERE pid='$pid'");

		$postlist[$post['pid']] = viewthread_procpost($post);

		if($attachpids) {
			require_once DISCUZ_ROOT.'./include/attachment.func.php';
			parseattach($attachpids, $attachtags, $postlist, $showimages, array($trade['aid']));
		}

		$post = $postlist[$pid];

		$post['buyerrank'] = 0;
		if($post['buyercredit']){
			foreach($ec_credit['rank'] AS $level => $credit) {
				if($post['buyercredit'] <= $credit) {
					$post['buyerrank'] = $level;
					break;
				}
			}
		}
		$post['sellerrank'] = 0;
		if($post['sellercredit']){
			foreach($ec_credit['rank'] AS $level => $credit) {
				if($post['sellercredit'] <= $credit) {
					$post['sellerrank'] = $level;
					break;
				}
			}
		}

		$navtitle = $trade['subject'].' - ';

		$tradetypeid = $trade['typeid'];

		$typetemplate = '';
		$optiondata = $optionlist = array();
		if($tradetypeid && isset($tradetypes[$tradetypeid])) {
			if(@include_once DISCUZ_ROOT.'./forumdata/cache/threadsort_'.$tradetypeid.'.php') {
				$query = $db->query("SELECT optionid, value FROM {$tablepre}tradeoptionvars WHERE pid='$pid'");
				while($option = $db->fetch_array($query)) {
					$optiondata[$option['optionid']] = $option['value'];
				}

				foreach($_DTYPE as $optionid => $option) {
					$optionlist[$option['identifier']]['title'] = $_DTYPE[$optionid]['title'];
					if($_DTYPE[$optionid]['type'] == 'checkbox') {
						$optionlist[$option['identifier']]['value'] = '';
						foreach(explode("\t", $optiondata[$optionid]) as $choiceid) {
							$optionlist[$option['identifier']]['value'] .= $_DTYPE[$optionid]['choices'][$choiceid].'&nbsp;';
						}
					} elseif(in_array($_DTYPE[$optionid]['type'], array('radio', 'select'))) {
						$optionlist[$option['identifier']]['value'] = $_DTYPE[$optionid]['choices'][$optiondata[$optionid]];
					} elseif($_DTYPE[$optionid]['type'] == 'image') {
						$maxwidth = $_DTYPE[$optionid]['maxwidth'] ? 'width="'.$_DTYPE[$optionid]['maxwidth'].'"' : '';
						$maxheight = $_DTYPE[$optionid]['maxheight'] ? 'height="'.$_DTYPE[$optionid]['maxheight'].'"' : '';
						$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? "<a href=\"$optiondata[$optionid]\" target=\"_blank\"><img src=\"$optiondata[$optionid]\"  $maxwidth $maxheight border=\"0\"></a>" : '';
					} elseif($_DTYPE[$optionid]['type'] == 'url') {
						$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? "<a href=\"$optiondata[$optionid]\" target=\"_blank\">$optiondata[$optionid]</a>" : '';
					} else {
						$optionlist[$option['identifier']]['value'] = $optiondata[$optionid];
					}
				}

				$typetemplate = $_DTYPETEMPLATE ? preg_replace(array("/\[(.+?)value\]/ies", "/{(.+?)}/ies"), array("showoption('\\1', 'value')", "showoption('\\1', 'title')"), $_DTYPETEMPLATE) : '';
			}

			$post['subject'] = '['.$tradetypes[$tradetypeid].'] '.$post['subject'];
		}

		include template('trade_info');
		exit;

	}
}

?>