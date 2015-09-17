<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: newtrade.inc.php 21053 2009-11-09 10:29:02Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$discuz_action = 11;

if(empty($forum['fid']) || $forum['type'] == 'group') {
	showmessage('forum_nonexistence');
}

if($special != 2 || !submitcheck('topicsubmit', 0, $seccodecheck, $secqaacheck)) {
	showmessage('undefined_action', NULL, 'HALTED');
}

if(!$allowposttrade) {
	showmessage('group_nopermission', NULL, 'NOPERM');
}

if(!$discuz_uid && !((!$forum['postperm'] && $allowpost) || ($forum['postperm'] && forumperm($forum['postperm'])))) {
	showmessage('group_nopermission', NULL, 'NOPERM');
} elseif(empty($forum['allowpost'])) {
	if(!$forum['postperm'] && !$allowpost) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	} elseif($forum['postperm'] && !forumperm($forum['postperm'])) {
		showmessage('post_forum_newthread_nopermission', NULL, 'HALTED');
	}
} elseif($forum['allowpost'] == -1) {
	showmessage('post_forum_newthread_nopermission', NULL, 'HALTED');
}

checklowerlimit($postcredits);

if($post_invalid = checkpost(1)) {
	showmessage($post_invalid);
}

if(checkflood()) {
	showmessage('post_flood_ctrl');
}

$item_price = floatval($item_price);
$item_credit = intval($item_credit);
if(!trim($item_name)) {
	showmessage('trade_please_name');
} elseif($maxtradeprice && $item_price > 0 && ($mintradeprice > $item_price || $maxtradeprice < $item_price)) {
	showmessage('trade_price_between');
} elseif($maxtradeprice && $item_credit > 0 && ($mintradeprice > $item_credit || $maxtradeprice < $item_credit)) {
	showmessage('trade_credit_between');
} elseif(!$maxtradeprice && $item_price > 0 && $mintradeprice > $item_price) {
	showmessage('trade_price_more_than');
} elseif(!$maxtradeprice && $item_credit > 0 && $mintradeprice > $item_credit) {
	showmessage('trade_credit_more_than');
} elseif($item_price <= 0 && $item_credit <= 0) {
	showmessage('trade_pricecredit_need');
} elseif($item_number < 1) {
	showmessage('tread_please_number');
}

if(!empty($_FILES['tradeattach']['tmp_name'][0])) {
	$_FILES['attach'] = array_merge_recursive((array)$_FILES['attach'], $_FILES['tradeattach']);
}

if($allowpostattach && is_array($_FILES['attach'])) {
	foreach($_FILES['attach']['name'] as $attachname) {
		if($attachname != '') {
			checklowerlimit($postattachcredits);
			break;
		}
	}
}

$typeid = isset($typeid) ? $typeid : 0;
$tradetypeid = isset($tradetypeid) ? $tradetypeid : 0;
$iconid = !empty($iconid) && isset($_DCACHE['icons'][$iconid]) ? $iconid : 0;
$displayorder = $modnewthreads ? -2 : (($forum['ismoderator'] && !empty($sticktopic)) ? 1 : 0);
$digest = ($forum['ismoderator'] && !empty($addtodigest)) ? 1 : 0;
$readperm = $allowsetreadperm ? $readperm : 0;
$isanonymous = $isanonymous && $allowanonymous ? 1 : 0;

$author = !$isanonymous ? $discuz_user : '';

$moderated = $digest || $displayorder > 0 ? 1 : 0;

$attachment = ($allowpostattach && $attachments = attach_upload()) ? 1 : 0;

$db->query("INSERT INTO {$tablepre}threads (fid, readperm, price, iconid, typeid, author, authorid, subject, dateline, lastpost, lastposter, displayorder, digest, special, attachment, moderated, replies)
	VALUES ('$fid', '$readperm', '$price', '$iconid', '$typeid', '$author', '$discuz_uid', '$subject', '$timestamp', '$timestamp', '$author', '$displayorder', '$digest', '$special', '$attachment', '$moderated', '1')");
$tid = $db->insert_id();

if($moderated) {
	updatemodlog($tid, ($displayorder > 0 ? 'STK' : 'DIG'));
	updatemodworks(($displayorder > 0 ? 'STK' : 'DIG'), 1);
}

$bbcodeoff = checkbbcodes($message, !empty($bbcodeoff));
$smileyoff = checksmilies($message, !empty($smileyoff));
$parseurloff = !empty($parseurloff);
$htmlon = bindec(($tagstatus && !empty($tagoff) ? 1 : 0).($allowhtml && !empty($htmlon) ? 1 : 0));
$attentionon = empty($attention_add) ? 0 : 1;

$pinvisible = $modnewthreads ? -2 : 0;
$db->query("INSERT INTO {$tablepre}posts (fid, tid, first, author, authorid, subject, dateline, message, useip, invisible, anonymous, usesig, htmlon, bbcodeoff, smileyoff, parseurloff, attachment)
	VALUES ('$fid', '$tid', '1', '$discuz_user', '$discuz_uid', '$subject', '$timestamp', '', '$onlineip', '$pinvisible', '$isanonymous', '$usesig', '$htmlon', '$bbcodeoff', '$smileyoff', '$parseurloff', '0')");

if($tagstatus && $tags != '') {
	$tagarray = array_unique(explode(' ', censor($tags)));
	$tagcount = 0;
	foreach($tagarray as $tagname) {
		$tagname = trim($tagname);
		if(preg_match('/^([\x7f-\xff_-]|\w){3,20}$/', $tagname)) {
			$query = $db->query("SELECT closed FROM {$tablepre}tags WHERE tagname='$tagname'");
			if($db->num_rows($query)) {
				if(!$tagstatus = $db->result($query, 0)) {
					$db->query("UPDATE {$tablepre}tags SET total=total+1 WHERE tagname='$tagname'", 'UNBUFFERED');
				}
			} else {
				$db->query("INSERT INTO {$tablepre}tags (tagname, closed, total)
					VALUES ('$tagname', 0, 1)", 'UNBUFFERED');
				$tagstatus = 0;
			}
			if(!$tagstatus) {
				$db->query("INSERT {$tablepre}threadtags (tagname, tid) VALUES ('$tagname', $tid)", 'UNBUFFERED');
			}
			$tagcount++;
			if($tagcount > 4) {
				unset($tagarray);
				break;
			}
		}
	}
}

$pinvisible = $modnewreplies ? -2 : 0;
$db->query("INSERT INTO {$tablepre}posts (fid, tid, first, author, authorid, subject, dateline, message, useip, invisible, anonymous, usesig, htmlon, bbcodeoff, smileyoff, parseurloff, attachment)
		VALUES ('$fid', '$tid', '0', '$discuz_user', '$discuz_uid', '$subject', '$timestamp', '$message', '$onlineip', '$pinvisible', '$isanonymous', '$usesig', '$htmlon', '$bbcodeoff', '$smileyoff', '$parseurloff', '$attachment')");
$pid = $db->insert_id();

threadsort_checkoption(1, 1);

$optiondata = array();
if($tradetypes && $typeoption && $checkoption) {
	$optiondata = threadsort_validator($typeoption);
}

if($tradetypes && $optiondata) {
	foreach($optiondata as $optionid => $value) {
		$db->query("INSERT INTO {$tablepre}tradeoptionvars (sortid, pid, optionid, value)
			VALUES ('$tradetypeid', '$pid', '$optionid', '$value')");
	}
}

if($discuz_uid) {
	$stataction = '';
	if($attentionon) {
	$stataction = 'attentionon';
		$db->query("REPLACE INTO {$tablepre}favoritethreads (tid, uid, dateline) VALUES ('$tid', '$discuz_uid', '$timestamp')", 'UNBUFFERED');
	}
	if($stataction) {
		write_statlog('', 'item=attention&action=newtrade_'.$stataction, '', '', 'my.php');		
	}
	$db->query("UPDATE {$tablepre}favoriteforums SET newthreads=newthreads+1 WHERE fid='$fid' AND uid<>'$discuz_uid'", 'UNBUFFERED');
}

$allowpostattach && ($attachnew || $attachdel || $tradeaid) && updateattach();

require_once DISCUZ_ROOT.'./include/trade.func.php';
trade_create(array(
	'tid' => $tid,
	'pid' => $pid,
	'aid' => $tradeaid,
	'typeid' => $tradetypeid,
	'item_expiration' => $item_expiration,
	'thread' => $thread,
	'discuz_uid' => $discuz_uid,
	'author' => $author,
	'seller' => $seller,
	'item_name' => $item_name,
	'item_price' => $item_price,
	'item_number' => $item_number,
	'item_quality' => $item_quality,
	'item_locus' => $item_locus,
	'transport' => $transport,
	'postage_mail' => $postage_mail,
	'postage_express' => $postage_express,
	'postage_ems' => $postage_ems,
	'item_type' => $item_type,
	'item_costprice' => $item_costprice,
	'item_credit' => $item_credit,
	'item_costcredit' => $item_costcredit
));

if($modnewthreads) {

	$db->query("UPDATE {$tablepre}forums SET todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
	showmessage('post_newthread_mod_succeed', "forumdisplay.php?fid=$fid");

} else {
	$feed = array();
	if($addfeed && $forum['allowfeed']) {
		$feed['icon'] = 'goods';
		$feed['title_template'] = 'feed_thread_goods_title';
		$feed['body_template'] = 'feed_thread_goods_message';
		$feed['body_data'] = array(
			'itemname'=> "<a href=\"{$boardurl}viewthread.php?do=tradeinfo&tid=$tid&pid=$pid\">$item_name</a>",
			'itemprice'=> $item_price
		);

		if(in_array($attachments[1]['type'], array('image/gif', 'image/jpeg', 'image/png'))) {
			$attachurl = preg_match("/^((https?|ftps?):\/\/|www\.)/i", $attachurl) ? $attachurl : $boardurl.$attachurl;
			$imgurl = $attachurl.'/'.$attachments[1]['attachment'].($attachments[1]['thumb'] && $attachments[1]['type'] != 'image/gif' ? '.thumb.jpg' : '');
			$feed['images'][] = $attachments[1]['attachment'] ? array('url' => $imgurl, 'link' => "{$boardurl}viewthread.php?tid=$tid") : array();
		}

		postfeed($feed);
	}
	if($digest) {
		foreach($digestcredits as $id => $addcredits) {
			$postcredits[$id] = (isset($postcredits[$id]) ? $postcredits[$id] : 0) + $addcredits;
		}
	}
	updatepostcredits('+', $discuz_uid, $postcredits);
	$db->query("UPDATE {$tablepre}members SET threads=threads+1 WHERE uid='$discuz_uid'");

	if(is_array($dzfeed_limit['user_threads']) && in_array(($threads + 1), $dzfeed_limit['user_threads'])) {
		$arg = $data = array();
		$arg['type'] = 'user_threads';
		$arg['uid'] = $discuz_uid;
		$arg['username'] = $discuz_userss;
		$data['title']['actor'] = "<a href=\"space.php?uid={$discuz_uid}\" target=\"_blank\">{$discuz_user}</a>";
		$data['title']['count'] = $threads + 1;
		add_feed($arg, $data);
	}

	$lastpost = "$tid\t$subject\t$timestamp\t$author";
	$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost', threads=threads+1, posts=posts+2, todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
	if($forum['type'] == 'sub') {
		$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost' WHERE fid='$forum[fup]'", 'UNBUFFERED');
	}

	showmessage('post_newthread_succeed', "viewthread.php?tid=$tid&extra=$extra");

}

?>