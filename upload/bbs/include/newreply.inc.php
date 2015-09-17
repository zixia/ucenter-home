<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: newreply.inc.php 21053 2009-11-09 10:29:02Z wangjinbo $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$discuz_action = 12;

if($special == 5) {
	$debate = array_merge($thread, $db->fetch_first("SELECT * FROM {$tablepre}debates WHERE tid='$tid'"));
	$standquery = $db->query("SELECT stand FROM {$tablepre}debateposts WHERE tid='$tid' AND uid='$discuz_uid' AND stand<>'0' ORDER BY dateline LIMIT 1");
	$firststand = $db->result_first("SELECT stand FROM {$tablepre}debateposts WHERE tid='$tid' AND uid='$discuz_uid' AND stand<>'0' ORDER BY dateline LIMIT 1");

	if($debate['endtime'] && $debate['endtime'] < $timestamp) {
		showmessage('debate_end');
	}
}

if(!$discuz_uid && !((!$forum['replyperm'] && $allowreply) || ($forum['replyperm'] && forumperm($forum['replyperm'])))) {
	showmessage('replyperm_login_nopermission', NULL, 'NOPERM');
} elseif(empty($forum['allowreply'])) {
	if(!$forum['replyperm'] && !$allowreply) {
		showmessage('replyperm_none_nopermission', NULL, 'NOPERM');
	} elseif($forum['replyperm'] && !forumperm($forum['replyperm'])) {
		showmessagenoperm('replyperm', $forum['fid']);
	}
} elseif($forum['allowreply'] == -1) {
	showmessage('post_forum_newreply_nopermission', NULL, 'HALTED');
}

if(empty($thread)) {
	showmessage('thread_nonexistence');
} elseif($thread['price'] > 0 && $thread['special'] == 0 && !$discuz_uid) {
	showmessage('group_nopermission', NULL, 'NOPERM');
}

checklowerlimit($replycredits);

if($special == 127) {
	$postinfo = $db->fetch_first("SELECT message FROM {$tablepre}posts WHERE tid='$tid' AND first='1'");
	$sppos = strrpos($postinfo['message'], chr(0).chr(0).chr(0));
	$specialextra = substr($postinfo['message'], $sppos + 3);
	if(!array_key_exists($specialextra, $threadplugins) || !in_array($specialextra, unserialize($forum['threadplugin'])) || !in_array($specialextra, $allowthreadplugin)) {
		$special = 0;
		$specialextra = '';
	}
}

if(!submitcheck('replysubmit', 0, $seccodecheck, $secqaacheck)) {

	if($thread['special'] == 2 && ((!isset($addtrade) || $thread['authorid'] != $discuz_uid) && !$tradenum = $db->result_first("SELECT count(*) FROM {$tablepre}trades WHERE tid='$tid'"))) {
		showmessage('trade_newreply_nopermission', NULL, 'HALTED');
	}

	include_once language('misc');
	$noticeauthor = $noticetrimstr = '';
	if(isset($repquote)) {

		$thaquote = $db->fetch_first("SELECT tid, fid, author, authorid, first, message, useip, dateline, anonymous, status FROM {$tablepre}posts WHERE pid='$repquote' AND invisible='0'");
		if($thaquote['tid'] != $tid) {
			showmessage('undefined_action', NULL, 'HALTED');
		}

		if(getstatus($thread['status'], 2) && $thaquote['authorid'] != $discuz_uid && $discuz_uid != $thread['authorid'] && $thaquote['first'] != 1 && !$forum['ismoderator']) {
			showmessage('undefined_action', NULL, 'HALTED');
		}

		if(!($thread['price'] && !$thread['special'] && $thaquote['first'])) {
			$quotefid = $thaquote['fid'];
			$message = $thaquote['message'];

			if($bannedmessages && $thaquote['authorid']) {
				$author = $db->fetch_first("SELECT groupid FROM {$tablepre}members WHERE uid='$thaquote[authorid]'");
				if(!$author['groupid'] || $author['groupid'] == 4 || $author['groupid'] == 5) {
					$message = $language['post_banned'];
				} elseif($thaquote['status'] & 1) {
					$message = $language['post_single_banned'];
				}
			}

			$time = gmdate("$dateformat $timeformat", $thaquote['dateline'] + ($timeoffset * 3600));
			$message = messagecutstr($message, 100);

			$thaquote['useip'] = substr($thaquote['useip'], 0, strrpos($thaquote['useip'], '.')).'.x';
			if($thaquote['author'] && $thaquote['anonymous']) {
			    $thaquote['author'] = 'Anonymous';
			} elseif(!$thaquote['author']) {
			    $thaquote['author'] = 'Guest from '.$thaquote['useip'];
			} else {
			    $thaquote['author'] = $thaquote['author'];
			}

			eval("\$language['post_reply_quote'] = \"$language[post_reply_quote]\";");
			$noticeauthormsg = htmlspecialchars($message);
			$message = "[quote]$message\n[size=2][color=#999999]$language[post_reply_quote][/color] [url={$boardurl}redirect.php?goto=findpost&pid=$repquote&ptid=$tid][img]{$boardurl}images/common/back.gif[/img][/url][/size][/quote]\n\n\n    ";
			$noticeauthor = htmlspecialchars('q|'.$thaquote['authorid'].'|'.$thaquote['author']);
			$noticetrimstr = htmlspecialchars($message);
		}

	} elseif(isset($reppost)) {

		$thapost = $db->fetch_first("SELECT tid, author, authorid, useip, dateline, anonymous, status, message FROM {$tablepre}posts WHERE pid='$reppost' AND invisible='0'");
		if($thapost['tid'] != $tid) {
			showmessage('undefined_action', NULL, 'HALTED');
		}

		$thapost['useip'] = substr($thapost['useip'], 0, strrpos($thapost['useip'], '.')).'.x';
		if($thapost['author'] && $thapost['anonymous']) {
		    $thapost['author'] = '[i]Anonymous[/i]';
		} elseif(!$thapost['author']) {
		    $thapost['author'] = '[i]Guest[/i] from '.$thapost['useip'];
		} else {
		    $thapost['author'] = '[i]'.$thapost['author'].'[/i]';
		}

		$thapost['number'] = $db->result_first("SELECT count(*) FROM {$tablepre}posts WHERE tid='$thapost[tid]' AND dateline<='$thapost[dateline]'");
		$message = "[b]$language[post_reply] [url={$boardurl}redirect.php?goto=findpost&pid=$reppost&ptid=$thapost[tid]]$thapost[number]#[/url] $thapost[author] $lang[post_thread][/b]\n\n\n    ";
		$noticeauthormsg = htmlspecialchars(messagecutstr($thapost['message'], 100));
		$noticeauthor = htmlspecialchars('r|'.$thapost['authorid'].'|'.$thapost['author']);
		$noticetrimstr = htmlspecialchars($message);

	}

	if(isset($addtrade) && $thread['special'] == 2 && $allowposttrade && $thread['authorid'] == $discuz_uid) {
		$expiration_7days = date('Y-m-d', $timestamp + 86400 * 7);
		$expiration_14days = date('Y-m-d', $timestamp + 86400 * 14);
		$trade['expiration'] = $expiration_month = date('Y-m-d', mktime(0, 0, 0, date('m')+1, date('d'), date('Y')));
		$expiration_3months = date('Y-m-d', mktime(0, 0, 0, date('m')+3, date('d'), date('Y')));
		$expiration_halfyear = date('Y-m-d', mktime(0, 0, 0, date('m')+6, date('d'), date('Y')));
		$expiration_year = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d'), date('Y')+1));
	}

	if($thread['replies'] <= $ppp) {
		$postlist = array();
		$query = $db->query("SELECT p.* ".($bannedmessages ? ', m.groupid ' : '').
			"FROM {$tablepre}posts p ".($bannedmessages ? "LEFT JOIN {$tablepre}members m ON p.authorid=m.uid " : '').
			"WHERE p.tid='$tid' AND p.invisible='0' ".($thread['price'] > 0 && $thread['special'] == 0 ? 'AND p.first = 0' : '')." ORDER BY p.dateline DESC");
		while($post = $db->fetch_array($query)) {

			$post['dateline'] = dgmdate("$dateformat $timeformat", $post['dateline'] + $timeoffset * 3600);

			if($bannedmessages && ($post['authorid'] && (!$post['groupid'] || $post['groupid'] == 4 || $post['groupid'] == 5))) {
				$post['message'] = $language['post_banned'];
			} elseif($post['status'] & 1) {
				$post['message'] = $language['post_single_banned'];
			} else {
				$post['message'] = preg_replace("/\[hide=?\d*\](.+?)\[\/hide\]/is", "[b]$language[post_hidden][/b]", $post['message']);
				$post['message'] = discuzcode($post['message'], $post['smileyoff'], $post['bbcodeoff'], $post['htmlon'] & 1, $forum['allowsmilies'], $forum['allowbbcode'], $forum['allowimgcode'], $forum['allowhtml'], $forum['jammer']);
			}

			$postlist[] = $post;
		}
	}

	if($special == 2 && isset($addtrade) && $thread['authorid'] == $discuz_uid) {
		$tradetypeselect = '';
		$forum['tradetypes'] = $forum['tradetypes'] == '' ? -1 : unserialize($forum['tradetypes']);
		if($tradetypes && !empty($forum['tradetypes'])) {
			$tradetypeselect = '<select name="tradetypeid" onchange="ajaxget(\'post.php?action=threadsorts&tradetype=yes&sortid=\'+this.options[this.selectedIndex].value+\'&sid='.$sid.'\', \'threadtypes\', \'threadtypeswait\')"><option value="0">&nbsp;</option>';
			foreach($tradetypes as $typeid => $name) {
				if($forum['tradetypes'] == -1 || @in_array($typeid, $forum['tradetypes'])) {
					$tradetypeselect .= '<option value="'.$typeid.'">'.strip_tags($name).'</option>';
				}
			}
			$tradetypeselect .= '</select><span id="threadtypeswait"></span>';
		}
	}

	if($allowpostattach) {
		$attachlist = getattach();
		$attachs = $attachlist['attachs'];
		$imgattachs = $attachlist['imgattachs'];
		unset($attachlist);
	}

	$infloat ? include template('post_infloat') : include template('post');

} else {

	require_once DISCUZ_ROOT.'./include/forum.func.php';

	if($subject == '' && $message == '' && $thread['special'] != 2) {
		showmessage('post_sm_isnull');
	} elseif($thread['closed'] && !$forum['ismoderator']) {
		showmessage('post_thread_closed');
	} elseif($post_autoclose = checkautoclose()) {
		showmessage($post_autoclose);
	} elseif($post_invalid = checkpost($special == 2 && $allowposttrade)) {
		showmessage($post_invalid);
	} elseif(checkflood()) {
		showmessage('post_flood_ctrl');
	}
	if(!empty($trade) && $thread['special'] == 2 && $allowposttrade) {

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

		threadsort_checkoption(1, 1);

		$optiondata = array();
		if($tradetypes && $typeoption && $checkoption) {
			$optiondata = threadsort_validator($typeoption);
		}

	}

	$attentionon = empty($attention_add) ? 0 : 1;
	$attentionoff = empty($attention_remove) ? 0 : 1;

	if($thread['lastposter'] != $discuz_userss) {
		$userreplies = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$tid' AND first='0' AND authorid='$discuz_uid'");
		$thread['heats'] += round($heatthread['reply'] * pow(0.8, $userreplies));
		$heatbefore = $thread['heats'];
		$db->query("UPDATE {$tablepre}threads SET heats='$thread[heats]' WHERE tid='$tid'", 'UNBUFFERED');
	}

	$bbcodeoff = checkbbcodes($message, !empty($bbcodeoff));
	$smileyoff = checksmilies($message, !empty($smileyoff));
	$parseurloff = !empty($parseurloff);
	$htmlon = $allowhtml && !empty($htmlon) ? 1 : 0;
	$usesig = !empty($usesig) ? 1 : 0;

	$isanonymous = $allowanonymous && !empty($isanonymous)? 1 : 0;
	$author = empty($isanonymous) ? $discuz_user : '';

	$pinvisible = $modnewreplies ? -2 : 0;
	$message = preg_replace('/\[attachimg\](\d+)\[\/attachimg\]/is', '[attach]\1[/attach]', $message);

	$db->query("INSERT INTO {$tablepre}posts (fid, tid, first, author, authorid, subject, dateline, message, useip, invisible, anonymous, usesig, htmlon, bbcodeoff, smileyoff, parseurloff, attachment)
			VALUES ('$fid', '$tid', '0', '$discuz_user', '$discuz_uid', '$subject', '$timestamp', '$message', '$onlineip', '$pinvisible', '$isanonymous', '$usesig', '$htmlon', '$bbcodeoff', '$smileyoff', '$parseurloff', '0')");
	$pid = $db->insert_id();
	$cacheposition = getstatus($thread['status'], 1);
	if($pid && $cacheposition) {
		savepostposition($tid, $pid);
	}

	$nauthorid = 0;
	if(!empty($noticeauthor) && !$isanonymous) {
		list($ac, $nauthorid, $nauthor) = explode('|', $noticeauthor);
		if($nauthorid != $discuz_uid) {
			$postmsg = messagecutstr(str_replace($noticetrimstr, '', $message), 100);
			if($ac == 'q') {
				sendnotice($nauthorid, 'repquote_noticeauthor', 'threads');
			} elseif($ac == 'r') {
				sendnotice($nauthorid, 'reppost_noticeauthor', 'threads');
			}
		}
	}

	$uidarray = array();
	$query = $db->query("SELECT uid FROM {$tablepre}favoritethreads WHERE tid='$tid'");
	while($favthread = $db->fetch_array($query)) {
		if($favthread['uid'] !== $discuz_uid && (!$nauthorid || $nauthorid != $favthread['uid'])) {
			$uidarray[] = $favthread['uid'];
		}
	}
	if($discuz_uid && !empty($uidarray)) {
		sendnotice(implode(',', $uidarray), 'favoritethreads_notice', 'threads', $tid, array('user' => (!$isanonymous ? $discuz_userss : '<i>Anonymous</i>'), 'maxusers' => 5));
		$db->query("UPDATE {$tablepre}favoritethreads SET newreplies=newreplies+1, dateline='$timestamp' WHERE uid IN (".implodeids($uidarray).") AND tid='$tid'", 'UNBUFFERED');
	}
	if($discuz_uid) {
		$stataction = '';
		if($attentionon) {
			$stataction = 'attentionon';
			$db->query("REPLACE INTO {$tablepre}favoritethreads (tid, uid, dateline) VALUES ('$tid', '$discuz_uid', '$timestamp')", 'UNBUFFERED');
		}
		if($attentionoff) {
			$stataction = 'attentionoff';
			$db->query("DELETE FROM {$tablepre}favoritethreads WHERE tid='$tid' AND uid='$discuz_uid'", 'UNBUFFERED');
		}
		if($stataction) {
			write_statlog('', 'item=attention&action=newreply_'.$stataction, '', '', 'my.php');
		}
	}

	if($special == 3 && $thread['authorid'] != $discuz_uid && $thread['price'] > 0) {

		$rewardlog = $db->fetch_first("SELECT * FROM {$tablepre}rewardlog WHERE tid='$tid' AND answererid='$discuz_uid'");
		if(!$rewardlog) {
			$db->query("INSERT INTO {$tablepre}rewardlog (tid, answererid, dateline) VALUES ('$tid', '$discuz_uid', '$timestamp')");
		}

	} elseif($special == 5) {

		$stand = $firststand ? $firststand : intval($stand);

		if(!$db->num_rows($standquery)) {
			if($stand == 1) {
				$db->query("UPDATE {$tablepre}debates SET affirmdebaters=affirmdebaters+1 WHERE tid='$tid'");
			} elseif($stand == 2) {
				$db->query("UPDATE {$tablepre}debates SET negadebaters=negadebaters+1 WHERE tid='$tid'");
			}
		} else {
			$stand = $firststand;
		}
		if($stand == 1) {
			$db->query("UPDATE {$tablepre}debates SET affirmreplies=affirmreplies+1 WHERE tid='$tid'");
		} elseif($stand == 2) {
			$db->query("UPDATE {$tablepre}debates SET negareplies=negareplies+1 WHERE tid='$tid'");
		}
		$db->query("INSERT INTO {$tablepre}debateposts (tid, pid, uid, dateline, stand, voters, voterids) VALUES ('$tid', '$pid', '$discuz_uid', '$timestamp', '$stand', '0', '')");
	}

	$allowpostattach && ($attachnew || $attachdel || $special == 2 && $tradeaid) && updateattach();

	$replymessage = 'post_reply_succeed';

	if($special == 2 && $allowposttrade && $thread['authorid'] == $discuz_uid && !empty($trade) && !empty($item_name)) {

		if($tradetypes && $optiondata) {
			foreach($optiondata as $optionid => $value) {
				$db->query("INSERT INTO {$tablepre}tradeoptionvars (sortid, pid, optionid, value)
					VALUES ('$tradetypeid', '$pid', '$optionid', '$value')");
			}
		}

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

		$replymessage = 'trade_add_succeed';

	}

	if($specialextra) {

		@include_once DISCUZ_ROOT.'./plugins/'.$threadplugins[$specialextra]['module'].'.class.php';
		$classname = 'threadplugin_'.$specialextra;
		if(method_exists($classname, 'newreply_submit_end')) {
			$threadpluginclass = new $classname;
			$threadpluginclass->newreply_submit_end($fid, $tid);
		}

	}

	$forum['threadcaches'] && deletethreadcaches($tid);

	if($modnewreplies) {
		$db->query("UPDATE {$tablepre}forums SET todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
		showmessage('post_reply_mod_succeed', "forumdisplay.php?fid=$fid");
	} else {

		$db->query("UPDATE {$tablepre}threads SET lastposter='$author', lastpost='$timestamp', replies=replies+1 WHERE tid='$tid'", 'UNBUFFERED');

		updatepostcredits('+', $discuz_uid, $replycredits);

		$lastpost = "$thread[tid]\t".addslashes($thread['subject'])."\t$timestamp\t$author";
		$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost', posts=posts+1, todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
		if($forum['type'] == 'sub') {
			$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost' WHERE fid='$forum[fup]'", 'UNBUFFERED');
		}

		$feed = array();
		if($addfeed && $forum['allowfeed'] && $thread['authorid'] != $discuz_uid && !$isanonymous) {
			if($special == 2 && !empty($trade) && !empty($item_name) && !empty($item_price)) {
				$feed['icon'] = 'goods';
				$feed['title_template'] = 'feed_thread_goods_title';
				$feed['body_template'] = 'feed_thread_goods_message';
				$feed['body_data'] = array(
					'itemname'=> "<a href=\"{$boardurl}viewthread.php?do=tradeinfo&tid=$tid&pid=$pid\">$item_name</a>",
					'itemprice'=> $item_price
				);
			} elseif($special == 3) {
				$feed['icon'] = 'reward';
				$feed['title_template'] = 'feed_reply_reward_title';
				$feed['title_data'] = array(
					'subject' => "<a href=\"{$boardurl}viewthread.php?tid=$tid\">$thread[subject]</a>",
					'author' => "<a href=\"space.php?uid=$thread[authorid]\">$thread[author]</a>"
				);
			} elseif($special == 5) {
				$feed['icon'] = 'debate';
				$feed['title_template'] = 'feed_thread_debatevote_title';
				$feed['title_data'] = array(
					'subject' => "<a href=\"{$boardurl}viewthread.php?tid=$tid\">$thread[subject]</a>",
					'author' => "<a href=\"space.php?uid=$thread[authorid]\">$thread[author]</a>"
				);
			} else {
				$feed['icon'] = 'post';
				$feed['title_template'] = 'feed_reply_title';
				$feed['title_data'] = array(
					'subject' => "<a href=\"{$boardurl}viewthread.php?tid=$tid\">$thread[subject]</a>",
					'author' => "<a href=\"space.php?uid=$thread[authorid]\">$thread[author]</a>"
				);
			}
			postfeed($feed);
		}

		if(is_array($dzfeed_limit['thread_replies']) && in_array(($thread['replies'] + 1), $dzfeed_limit['thread_replies'])) {
			$arg = $data = array();
			$arg['type'] = 'thread_replies';
			$arg['fid'] = $thread['fid'];
			$arg['typeid'] = $thread['typeid'];
			$arg['sortid'] = $thread['sortid'];
			$arg['uid'] = $thread['authorid'];
			$arg['username'] = addslashes($thread['author']);
			$data['title']['actor'] = $thread['authorid'] ? "<a href=\"space.php?uid={$thread[authorid]}\" target=\"_blank\">{$thread[author]}</a>" : $thread['author'];
			$data['title']['forum'] = "<a href=\"forumdisplay.php?fid={$thread[fid]}\" target=\"_blank\">".$forum['name'].'</a>';
			$data['title']['count'] = $thread['replies'] + 1;
			$data['title']['subject'] = "<a href=\"viewthread.php?tid={$thread[tid]}\" target=\"_blank\">{$thread[subject]}</a>";
			add_feed($arg, $data);
		}

		if(is_array($dzfeed_limit['user_posts']) && in_array(($posts + 1), $dzfeed_limit['user_posts'])) {
			$arg = $data = array();
			$arg['type'] = 'user_posts';
			$arg['uid'] = $discuz_uid;
			$arg['username'] = $discuz_userss;
			$data['title']['actor'] = "<a href=\"space.php?uid={$discuz_uid}\" target=\"_blank\">{$discuz_user}</a>";
			$data['title']['count'] = $posts + 1;
			add_feed($arg, $data);
		}
		
		$page = getstatus($thread['status'], 4) ? 1 : @ceil(($thread['special'] ? $thread['replies'] + 1 : $thread['replies'] + 2) / $ppp);
		showmessage($replymessage, "viewthread.php?tid=$tid&pid=$pid&page=$page&extra=$extra#pid$pid");
	}

}

?>