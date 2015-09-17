<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: viewthread.php 21339 2010-01-06 08:25:41Z zhaoxiongfei $
*/

if(!defined('CURSCRIPT')) {
	define('CURSCRIPT', 'viewthread');
}

define('SQL_ADD_THREAD', ' t.dateline, t.special, t.lastpost AS lastthreadpost, ');
require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/forum.func.php';

$page = max($page, 1);
if($cachethreadlife && $forum['threadcaches'] && !$discuz_uid && $page == 1 && !$forum['special']) {
	viewthread_loadcache();
}

require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

$discuz_action = 3;
$sdb = loadmultiserver('viewthread');

$thread = $sdb->fetch_first("SELECT * FROM {$tablepre}threads t WHERE tid='$tid'".($auditstatuson ? '' : " AND displayorder>='0'"));

if(!$thread) {
	header("HTTP/1.0 404 Not Found");
	showmessage('thread_nonexistence');
}

$oldtopics = isset($_DCOOKIE['oldtopics']) ? $_DCOOKIE['oldtopics'] : 'D';
if(strpos($oldtopics, 'D'.$tid.'D') === FALSE) {
	$oldtopics = 'D'.$tid.$oldtopics;
	if(strlen($oldtopics) > 3072) {
		$oldtopics = preg_replace("((D\d+)+D).*$", "\\1", substr($oldtopics, 0, 3072));
	}
	dsetcookie('oldtopics', $oldtopics, 3600);
}

if($lastvisit < $thread['lastpost'] && (!isset($_DCOOKIE['fid'.$fid]) || $thread['lastpost'] > $_DCOOKIE['fid'.$fid])) {
	dsetcookie('fid'.$fid, $thread['lastpost'], 3600);
}

$thisgid = 0;

$thread['subjectenc'] 	= rawurlencode($thread['subject']);
$fromuid = $creditspolicy['promotion_visit'] && $discuz_uid ? '&amp;fromuid='.$discuz_uid : '';
$feeduid = $thread['authorid'] ? $thread['authorid'] : 0;
$feedpostnum = $thread['replies'] > $ppp ? $ppp : ($thread['replies'] ? $thread['replies'] : 1);
$threadshare = $thread['subject'] ? addslashes($thread['subject']) : '';

$upnavlink = 'forumdisplay.php?fid='.$fid.($extra ? '&amp;'.preg_replace("/^(&amp;)*/", '', $extra) : '');
$navigation = ' &raquo; <a href="'.$upnavlink.'">'.(strip_tags($forum['name']) ? strip_tags($forum['name']) : $forum['name']).'</a> &raquo; '.$thread['subject'];

$navtitle = $thread['subject'].' - '.strip_tags($forum['name']);
if($forum['type'] == 'sub') {
	$fup = $sdb->fetch_first("SELECT fid, name FROM {$tablepre}forums WHERE fid='$forum[fup]'");
	$navigation = '&raquo; <a href="forumdisplay.php?fid='.$fup['fid'].'">'.(strip_tags($fup['name']) ? strip_tags($fup['name']) : $fup['name']).'</a> '.$navigation;
	$navtitle = $navtitle.' - '.strip_tags($fup['name']);
}
$navtitle .= ' - ';

$forum['typemodels'] = $forum['typemodels'] ? unserialize($forum['typemodels']) : array();
$threadsort = isset($forum['threadsorts']['types'][$thread['sortid']]) ? 1 : 0;
$typetemplate = $tagscript = '';
$optiondata = $optionlist = $skipaids = $skipaidlist = array();
if($thread['sortid'] && $threadsort) {
	if($forum['threadsorts']['types'][$thread['sortid']]) {
		if(@include_once DISCUZ_ROOT.'./forumdata/cache/threadsort_'.$thread['sortid'].'.php') {
			$query = $sdb->query("SELECT optionid, value FROM {$tablepre}typeoptionvars WHERE tid='$tid'");
			while($option = $sdb->fetch_array($query)) {
				$optiondata[$option['optionid']] = $option['value'];
			}

			$searchtitle = $searchvalue = $searchunit = array();
			foreach($_DTYPE as $optionid => $option) {
				$optionlist[$option['identifier']]['title'] = $_DTYPE[$optionid]['title'];
				$optionlist[$option['identifier']]['unit'] = $_DTYPE[$optionid]['unit'];
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
					if(preg_match("/^\[aid=(\d+)\]$/", $optiondata[$optionid], $r)) {
						$skipaids[] = $r[1];
						$optiondata[$optionid] = 'attachment.php?aid='.aidencode($r[1]);
					}
					$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? "<a href=\"$optiondata[$optionid]\" target=\"_blank\"><img src=\"$optiondata[$optionid]\" onload=\"thumbImg(this)\" $maxwidth $maxheight border=\"0\"></a>" : '';
				} elseif($_DTYPE[$optionid]['type'] == 'url') {
					$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? "<a href=\"$optiondata[$optionid]\" target=\"_blank\">$optiondata[$optionid]</a>" : '';
				} elseif($_DTYPE[$optionid]['type'] == 'textarea') {
					$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? nl2br($optiondata[$optionid]) : '';
				} else {
					$optionlist[$option['identifier']]['value'] = $optiondata[$optionid];
				}
			}

			if($_DTYPETEMPLATE) {
				foreach($_DTYPE as $option) {
					$searchtitle[] = '/{('.$option['identifier'].')}/e';
					$searchvalue[] = '/\[('.$option['identifier'].')value\]/e';
					$searchunit[] = '/\[('.$option['identifier'].')unit\]/e';
				}
				$typetemplate = preg_replace($searchtitle, "showoption('\\1', 'title')", $_DTYPETEMPLATE);
				$typetemplate = preg_replace($searchvalue, "showoption('\\1', 'value')", $typetemplate);
				$typetemplate = preg_replace($searchunit, "showoption('\\1', 'unit')", $typetemplate);
			}
		}
	}
}

$thread['subject'] = ($forum['threadsorts']['types'][$thread['sortid']] ? ($forum['threadsorts']['listable'] ? '<a href="forumdisplay.php?fid='.$fid.'&amp;filter=sort&amp;sortid='.$thread['sortid'].'">['.$forum['threadsorts']['types'][$thread['sortid']].']</a>' : '['.$forum['threadsorts']['types'][$thread['sortid']].']').' ' : '').
			($forum['threadtypes']['types'][$thread['typeid']] ? ($forum['threadtypes']['listable'] ? '<a href="forumdisplay.php?fid='.$fid.'&amp;filter=type&amp;typeid='.$thread['typeid'].'">['.$forum['threadtypes']['types'][$thread['typeid']].']</a>' : '['.$forum['threadtypes']['types'][$thread['typeid']].']').' ' : '').
			$thread['subject'];

if(empty($forum['allowview'])) {

	if(!$forum['viewperm'] && !$readaccess) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	} elseif($forum['viewperm'] && !forumperm($forum['viewperm'])) {
		$navtitle = '';
		showmessagenoperm('viewperm', $fid);
	}

} elseif($forum['allowview'] == -1) {
	$navtitle = '';
	showmessage('forum_access_view_disallow');
}

if($forum['formulaperm'] && $adminid != 1) {
	formulaperm($forum['formulaperm']);
}

if($forum['password'] && $forum['password'] != $_DCOOKIE['fidpw'.$fid]) {
	dheader("Location: {$boardurl}forumdisplay.php?fid=$fid&amp;sid=$sid");
}

if($thread['readperm'] && $thread['readperm'] > $readaccess && !$forum['ismoderator'] && $thread['authorid'] != $discuz_uid) {
	showmessage('thread_nopermission', NULL, 'NOPERM');
}

$usemagic = array();
if($magicstatus) {
	require_once DISCUZ_ROOT.'./forumdata/cache/cache_magics.php';
	foreach($_DCACHE['magics'] as $id => $magic) {
		if($magic['available'] == 1) {
			if($magic['type'] == 1) {
				$usemagic['thread'][$id]['name'] = $magic['name'];
			} elseif($magic['type'] == 2) {
				$usemagic['user'][$id]['name'] = $magic['name'];
				$usemagic['user'][$id]['pic'] = strtolower($magic['identifier']).'_s.gif';
			}
		}
	}
}

$hiddenreplies = getstatus($thread['status'], 2);

$rushreply = getstatus($thread['status'], 3);

$savepostposition = getstatus($thread['status'], 1);

$threadpay = FALSE;
if($thread['price'] > 0 && $thread['special'] == 0) {
	if($maxchargespan && $timestamp - $thread['dateline'] >= $maxchargespan * 3600) {
		$db->query("UPDATE {$tablepre}threads SET price='0' WHERE tid='$tid'");
		$thread['price'] = 0;
	} else {
		$exemptvalue = $forum['ismoderator'] ? 128 : 16;
		if(!($exempt & $exemptvalue) && $thread['authorid'] != $discuz_uid) {
			$query = $sdb->query("SELECT tid FROM {$tablepre}paymentlog WHERE tid='$tid' AND uid='$discuz_uid'");
			if(!$sdb->num_rows($query)) {
				require_once DISCUZ_ROOT.'./include/threadpay.inc.php';
				$threadpay = TRUE;
			}
		}
	}
}

$raterange = $modratelimit && $adminid == 3 && !$forum['ismoderator'] ? array() : $raterange;
$extra = rawurlencode($extra);

$allowgetattach = !empty($forum['allowgetattach']) || ($allowgetattach && !$forum['getattachperm']) || forumperm($forum['getattachperm']);
$attachcredits = '';
$getattachcredits = $forum['getattachcredits'] ? $forum['getattachcredits'] : $creditspolicy['getattach'];
if($getattachcredits) {
	$exemptvalue = $ismoderator ? 32 : 4;
	if(!($exempt & $exemptvalue)) {
		foreach($getattachcredits as $creditid => $v) {
			if($v) {
				$attachcredits .= $extcredits[$creditid]['title'].' '.$v.' '.$extcredits[$creditid]['unit'].'&nbsp;';
			}
		}
	}
}
$seccodecheck = ($seccodestatus & 4) && (!$seccodedata['minposts'] || $posts < $seccodedata['minposts']);
$secqaacheck = $secqaa['status'][2] && (!$secqaa['minposts'] || $posts < $secqaa['minposts']);

$postlist = $attachtags = $attachlist = $threadstamp = array();
$aimgcount = 0;
$attachpids = -1;

if(empty($action) && $tid) {

	$thisgid = $forum['type'] == 'forum' ? $forum['fup'] : $_DCACHE['forums'][$forum['fup']]['fup'];
	$lastmod = $thread['moderated'] ? viewthread_lastmod() : array();
	if(!$threadstamp && getstatus($thread['status'], 5)) {
		$query = $db->query("SELECT action, stamp FROM {$tablepre}threadsmod WHERE tid='$tid' ORDER BY dateline DESC");
		while($stamp = $db->fetch_array($query)) {
			if($stamp['action'] == 'SPA') {
				$threadstamp = $_DCACHE['stamps'][$stamp['stamp']];
				break;
			} elseif($stamp['action'] == 'SPD') {
				break;
			}
		}
	}

	$showsettings = str_pad(decbin($showsettings), 3, '0', STR_PAD_LEFT);

	$customshow = $discuz_uid ? str_pad(base_convert($customshow, 10, 3), 4, '0', STR_PAD_LEFT) : '2222';

	$disableddateconvert = $customshow{0};
	$showsignatures = $customshow{1} == 2 ? $showsettings{0} : $customshow{1};
	$showavatars = $customshow{2} == 2 ? $showsettings{1} : $customshow{2};
	$showimages = $customshow{3} == 2 ? $showsettings{2} : $customshow{3};

	$highlightstatus = isset($highlight) && str_replace('+', '', $highlight) ? 1 : 0;

	$usesigcheck = $discuz_uid && $sigstatus ? 1 : 0;
	$allowpostreply = ($forum['allowreply'] != -1) && ((!$thread['closed'] && !checkautoclose()) || $forum['ismoderator']) && ((!$forum['replyperm'] && $allowreply) || ($forum['replyperm'] && forumperm($forum['replyperm'])) || $forum['allowreply']);
	$allowpost = $forum['allowpost'] != -1 && ((!$forum['postperm'] && $allowpost) || ($forum['postperm'] && forumperm($forum['postperm'])) || $forum['allowpost']);
	$addfeedcheck = $customaddfeed & 4 ? 1 : 0;

	if($allowpost) {
		$allowpostpoll = $allowpostpoll && ($forum['allowpostspecial'] & 1);
		$allowposttrade = $allowposttrade && ($forum['allowpostspecial'] & 2);
		$allowpostreward = $allowpostreward && ($forum['allowpostspecial'] & 4) && isset($extcredits[$creditstrans]);
		$allowpostactivity = $allowpostactivity && ($forum['allowpostspecial'] & 8);
		$allowpostdebate = $allowpostdebate && ($forum['allowpostspecial'] & 16);
	} else {
		$allowpostpoll = $allowposttrade = $allowpostreward = $allowpostactivity = $allowpostdebate = FALSE;
	}

	$forum['threadplugin'] = $allowpost && $threadplugins ? unserialize($forum['threadplugin']) : array();

	$visitedforums = $visitedforums ? visitedforums() : '';
	$forummenu = '';

	if($forumjump) {
		$forummenu = forumselect(FALSE, 1);
	}


	$relatedthreadlist = array();
	$relatedthreadupdate = $tagupdate = FALSE;
	$relatedkeywords = $tradekeywords = $metakeywords = $firstpid = '';
	$randnum = $qihoo['relate']['webnum'] ? rand(1, 1000) : '';
	$statsdata = !empty($statsdata) ? dhtmlspecialchars($statsdata) : '';
	if($qihoo['relate']['bbsnum']) {
		$site = site();
		$query = $db->query("SELECT type, expiration, keywords, relatedthreads FROM {$tablepre}relatedthreads WHERE tid='$tid'");
		if($db->num_rows($query)) {
			while($related = $db->fetch_array($query)) {
				if($related['expiration'] <= $timestamp) {
					$relatedthreadupdate = TRUE;
					$qihoo_up = 1;
				} elseif($qihoo['relate']['bbsnum'] && $related['type'] == 'general') {
					$relatedthreadlist = unserialize($related['relatedthreads']);
					if($related['keywords']) {
						$keywords = str_replace("\t", ' ', $related['keywords']);
						$searchkeywords = rawurlencode($keywords);
						$statskeywords = urlencode($keywords);
						$statsurl = urlencode($boardurl.'viewthread.php?tid='.$tid);
						$prefix = '';
						foreach(explode("\t", $related['keywords']) as $keyword) {
							$relatedkeywords .= $keyword ? $prefix.'<a href="http://www.qihoo.com/wenda.php?kw='.rawurlencode($keyword).'&amp;do=search&amp;noq=q" target="_blank">'.$keyword.'</a>' : '';
							$prefix = ', ';
							$metakeywords .= $keyword ? $keyword.',' : '';
						}
					}
				} elseif($related['type'] == 'trade') {
					$tradekeywords = explode("\t", $related['keywords']);
					$tradekeywords = $tradekeywords[array_rand($tradekeywords)];
				}
			}
		} else {
			$relatedthreadupdate = TRUE;
			$qihoo_up = 0;
		}
		$relatedthreadupdate && $verifykey = md5($authkey.$tid.$thread['subjectenc'].$charset.$site);
	}
	$relatedthreads = array();
	if(!empty($relatedthreadlist)) {
		if(!isset($_COOKIE['discuz_collapse']) || strpos($_COOKIE['discuz_collapse'], 'relatedthreads') === FALSE) {
			$relatedthreads['img'] = 'collapsed_no.gif';
			$relatedthreads['style'] = '';
		} else {
			$relatedthreads['img'] = 'collapsed_yes.gif';
			$relatedthreads['style'] = 'display: none';
		}
	}

	if(!isset($_COOKIE['discuz_collapse']) || strpos($_COOKIE['discuz_collapse'], 'modarea_c') === FALSE) {
		$collapseimg['modarea_c'] = 'collapsed_no';
		$collapse['modarea_c'] = '';
	} else {
		$collapseimg['modarea_c'] = 'collapsed_yes';
		$collapse['modarea_c'] = 'display: none';
	}

	$threadtag = array();
	$tagstatus = $tagstatus && $forum['allowtag'] ? ($tagstatus == 2 ? 2 : $forum['allowtag']) : 0;
	if($tagstatus) {
		$query = $sdb->query("SELECT tagname FROM {$tablepre}threadtags WHERE tid='$tid'");
		$thread['tags'] = $prefix = '';
		while($tags = $sdb->fetch_array($query)) {
			$metakeywords .= $tags['tagname'].',';
			$thread['tags'] .= $prefix.'<a href="tag.php?name='.rawurlencode($tags['tagname']).'" target="_blank">'.$tags['tagname'].'</a>';
			$prefix = ', ';
		}
		if($tagstatus == 2 && !$thread['tags'] || $relatedthreadupdate) {
			$tagupdate = TRUE;
		}
		$relatedthreadupdate && $thread['tagsenc'] = rawurlencode(strip_tags($thread['tags']));
	}

	viewthread_updateviews();

	@extract($_DCACHE['custominfo']);

	$infosidestatus['posts'] = $infosidestatus[1] && isset($infosidestatus['f'.$fid]['posts']) ? $infosidestatus['f'.$fid]['posts'] : $infosidestatus['posts'];
	$infoside = $infosidestatus[1] && $thread['replies'] > $infosidestatus['posts'];

	$specialadd1 = $specialadd2 = $specialextra = '';
	if($thread['special'] == 2) {
		$specialadd1 = "LEFT JOIN {$tablepre}trades tr ON p.pid=tr.pid";
		$specialadd2 = "AND tr.tid IS null";
	} elseif($thread['special'] == 5) {
		if(isset($stand) && $stand >= 0 && $stand < 3) {
			$specialadd1 .= "LEFT JOIN {$tablepre}debateposts dp ON p.pid=dp.pid";
			if($stand) {
				$specialadd2 .= "AND (dp.stand='$stand' OR p.first='1')";
			} else {
				$specialadd2 .= "AND (dp.stand='0' OR dp.stand IS NULL OR p.first='1')";
			}
			$specialextra = "&amp;stand=$stand";
		} else {
			$specialadd1 = "LEFT JOIN {$tablepre}debateposts dp ON p.pid=dp.pid";
		}
		$fieldsadd .= ", dp.stand, dp.voters";
	}

	$onlyauthoradd = $threadplughtml = '';
	if(empty($viewpid)) {
		$ordertype = !isset($_GET['ordertype']) && getstatus($thread['status'], 4) ? 1 : intval($ordertype);
		$authorid = intval($authorid);
		if($authorid) {
			$thread['replies'] = $sdb->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' AND authorid='$authorid'") - 1;
			if($thread['replies'] < 0) {
				showmessage('undefined_action');
			}
			$onlyauthoradd = "AND p.authorid='$authorid'";
		} elseif($thread['special'] == 5) {
			if(isset($stand) && $stand >= 0 && $stand < 3) {
				if($stand) {
					$thread['replies'] = $sdb->result_first("SELECT COUNT(*) FROM {$tablepre}debateposts WHERE tid='$tid' AND stand='$stand'");
				} else {
					$thread['replies'] = $sdb->result_first("SELECT COUNT(*) FROM {$tablepre}posts p LEFT JOIN {$tablepre}debateposts dp ON p.pid=dp.pid WHERE p.tid='$tid' AND (dp.stand='0' OR dp.stand IS NULL)");
				}
				$thread['replies'] = $sdb->result_first("SELECT COUNT(*) FROM {$tablepre}debateposts WHERE tid='$tid' AND stand='$stand'");
			} else {
				$thread['replies'] = $sdb->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0'") - 1;
			}
		} elseif($thread['special'] == 2) {
			$tradenum = $db->result_first("SELECT count(*) FROM {$tablepre}trades WHERE tid='$tid'");
			$thread['replies'] -= $tradenum;
		}

		$ppp = $forum['threadcaches'] && !$discuz_uid ? $_DCACHE['settings']['postperpage'] : $ppp;
		$totalpage = ceil(($thread['replies'] + 1) / $ppp);
		$page > $totalpage && $page = $totalpage;
		$pagebydesc = $page > 50 && $page > ($totalpage / 2) ? TRUE : FALSE;

		if($pagebydesc) {
			$firstpagesize = ($thread['replies'] + 1) % $ppp;
			$ppp3 = $ppp2 = $page == $totalpage && $firstpagesize ? $firstpagesize : $ppp;
			$realpage = $totalpage - $page + 1;
			$start_limit = max(0, ($realpage - 2) * $ppp + $firstpagesize);
			$numpost = ($page - 1) * $ppp;
			if($ordertype != 1) {
				$pageadd =  "ORDER BY dateline DESC LIMIT $start_limit, $ppp2";
			} else {
				$numpost = $thread['replies'] + 2 - $numpost + ($page > 1 ? 1 : 0);
				$pageadd = "ORDER BY first ASC,dateline ASC LIMIT $start_limit, $ppp2";
			}
		} else {
			$start_limit = $numpost = ($page - 1) * $ppp;
			if($start_limit > $thread['replies']) {
				$start_limit = $numpost = 0;
				$page = 1;
			}
			if($ordertype != 1) {
				$pageadd = "ORDER BY dateline LIMIT $start_limit, $ppp";
			} else {
				$numpost = $thread['replies'] + 2 - $numpost + ($page > 1 ? 1 : 0);
				$pageadd = "ORDER BY first DESC,dateline DESC LIMIT $start_limit, $ppp";
			}
		}

		$multipage = multi($thread['replies'] + 1, $ppp, $page, "viewthread.php?tid=$tid&amp;extra=$extra".($ordertype && $ordertype != getstatus($thread['status'], 4) ? "&amp;ordertype=$ordertype" : '').(isset($highlight) ? "&amp;highlight=".rawurlencode($highlight) : '').(!empty($authorid) ? "&amp;authorid=$authorid" : '').$specialextra);
	} else {
		$viewpid = intval($viewpid);
		$pageadd = "AND p.pid='$viewpid'";
	}

	$newpostanchor = $postcount = $ratelogpids = 0;

	$onlineauthors = array();


	$query = "SELECT p.*, m.uid, m.username, m.groupid, m.adminid, m.regdate, m.lastactivity, m.posts, m.threads, m.digestposts, m.oltime,
		m.pageviews, m.credits, m.extcredits1, m.extcredits2, m.extcredits3, m.extcredits4, m.extcredits5, m.extcredits6,
		m.extcredits7, m.extcredits8, m.email, m.gender, m.showemail, m.invisible, mf.nickname, mf.site,
		mf.icq, mf.qq, mf.yahoo, mf.msn, mf.taobao, mf.alipay, mf.location, mf.medals,
		mf.sightml AS signature, mf.customstatus, mf.spacename $fieldsadd
		FROM {$tablepre}posts p
		LEFT JOIN {$tablepre}members m ON m.uid=p.authorid
		LEFT JOIN {$tablepre}memberfields mf ON mf.uid=m.uid
		$specialadd1 ";

	$cachepids = $positionlist = array();
	if($savepostposition && empty($onlyauthoradd) && empty($specialadd2) && empty($viewpid) && $ordertype != 1) {
		$start = ($page - 1) * $ppp + 1;
		$end = $start + $ppp;
		$q2 = $db->query("SELECT pid, position FROM {$tablepre}postposition WHERE tid='$tid' AND position>='$start' AND position < $end ORDER BY position");
		while ($post = $db->fetch_array($q2)) {
			$cachepids[] = $post['pid'];
			$positionlist[$post['pid']] = $post['position'];
		}
		$cachepids = implodeids($cachepids);
		$pagebydesc = false;
	}

	$query .= $savepostposition && $cachepids ? "WHERE p.pid in($cachepids) ORDER BY pid ".($ordertype !=1 ? 'DESC' : 'ASC') : ("WHERE p.tid='$tid'".($auditstatuson ? '' : " AND p.invisible='0'")." $specialadd2 $onlyauthoradd $pageadd");

/*
	"WHERE p.tid='$tid'".($auditstatuson ? '' : " AND p.invisible='0'")." $specialadd2 $onlyauthoradd $pageadd");
	$query = $sdb->query("SELECT p.*, m.uid, m.username, m.groupid, m.adminid, m.regdate, m.lastactivity, m.posts, m.threads, m.digestposts, m.oltime,
		m.pageviews, m.credits, m.extcredits1, m.extcredits2, m.extcredits3, m.extcredits4, m.extcredits5, m.extcredits6,
		m.extcredits7, m.extcredits8, m.email, m.gender, m.showemail, m.invisible, mf.nickname, mf.site,
		mf.icq, mf.qq, mf.yahoo, mf.msn, mf.taobao, mf.alipay, mf.location, mf.medals,
		mf.sightml AS signature, mf.customstatus, mf.spacename $fieldsadd
		FROM {$tablepre}posts p
		LEFT JOIN {$tablepre}members m ON m.uid=p.authorid
		LEFT JOIN {$tablepre}memberfields mf ON mf.uid=m.uid
		$specialadd1
		WHERE p.tid='$tid'".($auditstatuson ? '' : " AND p.invisible='0'")." $specialadd2 $onlyauthoradd $pageadd");
*/
	$query = $sdb->query($query);
	while($post = $sdb->fetch_array($query)) {
		if(($onlyauthoradd && $post['anonymous'] == 0) || !$onlyauthoradd) {
			$postlist[$post['pid']] = viewthread_procpost($post);
		}
	}

	if($savepostposition && $positionlist) {
		foreach ($positionlist as $pid => $position)
		$postlist[$pid]['number'] = $position;
	}

	if($thread['special'] > 0 && (empty($viewpid) || $viewpid == $firstpid)) {
		$thread['starttime'] = gmdate("$dateformat $timeformat", $thread['dateline'] + $timeoffset * 3600);
		$thread['remaintime'] = '';
		switch($thread['special']) {
			case 1: include_once DISCUZ_ROOT.'./include/viewthread_poll.inc.php'; break;
			case 2: include_once DISCUZ_ROOT.'./include/viewthread_trade.inc.php'; break;
			case 3: include_once DISCUZ_ROOT.'./include/viewthread_reward.inc.php'; break;
			case 4: include_once DISCUZ_ROOT.'./include/viewthread_activity.inc.php'; break;
			case 5: include_once DISCUZ_ROOT.'./include/viewthread_debate.inc.php'; break;
			case 127:
				$sppos = strrpos($postlist[$firstpid]['message'], chr(0).chr(0).chr(0));
				$specialextra = substr($postlist[$firstpid]['message'], $sppos + 3);
				if($specialextra) {
					if(array_key_exists($specialextra, $threadplugins)) {
						@include_once DISCUZ_ROOT.'./plugins/'.$threadplugins[$specialextra]['module'].'.class.php';
						$classname = 'threadplugin_'.$specialextra;
						if(method_exists($classname, 'viewthread')) {
							$threadpluginclass = new $classname;
							$threadplughtml = $threadpluginclass->viewthread($tid);
						}
					}
					$postlist[$firstpid]['message'] = substr($postlist[$firstpid]['message'], 0, $sppos);
				}
				break;
		}
	}

	if(empty($authorid) && empty($postlist)) {
		$replies = intval($db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0'")) - 1;
		if($thread['replies'] != $replies && $replies > 0) {
			$db->query("UPDATE {$tablepre}threads SET replies='$replies' WHERE tid='$tid'");
			dheader("Location: redirect.php?tid=$tid&goto=lastpost");
		}
	}

	if($pagebydesc) {
		$postlist = array_reverse($postlist, TRUE);
	}

	if($vtonlinestatus == 2 && $onlineauthors) {
		$query = $db->query("SELECT uid FROM {$tablepre}sessions WHERE uid IN(".(implode(',', $onlineauthors)).") AND invisible=0");
		$onlineauthors = array();
		while($author = $db->fetch_array($query)) {
			$onlineauthors[$author['uid']] = 1;
		}
	} else {
		$onlineauthors = array();
	}
	$ratelogs = array();
	if($ratelogpids) {
		$query = $db->query("SELECT * FROM {$tablepre}ratelog WHERE pid IN ($ratelogpids) ORDER BY dateline DESC");
		while($ratelog = $db->fetch_array($query)) {
			if(count($postlist[$ratelog['pid']]['ratelog']) < $ratelogrecord) {
				$ratelogs[$ratelog['pid']][$ratelog['uid']]['username'] = $ratelog['username'];
				$ratelogs[$ratelog['pid']][$ratelog['uid']]['score'][$ratelog['extcredits']] = $ratelog['score'];
				$ratelogs[$ratelog['pid']][$ratelog['uid']]['reason'] = dhtmlspecialchars($ratelog['reason']);
				$postlist[$ratelog['pid']]['ratelog'][$ratelog['uid']] = $ratelogs[$ratelog['pid']][$ratelog['uid']];
			}

			if(!$postlist[$ratelog['pid']]['totalrate'] || !in_array($ratelog['uid'], $postlist[$ratelog['pid']]['totalrate'])) {
				$postlist[$ratelog['pid']]['totalrate'][] = $ratelog['uid'];
			}
		}
	}

	if($attachpids != '-1') {
		require_once DISCUZ_ROOT.'./include/attachment.func.php';
		parseattach($attachpids, $attachtags, $postlist, $showimages, $skipaids);
	}

	if(empty($postlist)) {
		showmessage('undefined_action', NULL, 'HALTED');
	} else {
		$seodescription = current($postlist);
		$seodescription = !$thread['price'] ? str_replace(array("\r", "\n"), '', cutstr(htmlspecialchars(strip_tags($seodescription['message'])), 150)) : '';
	}

	viewthread_parsetags();

	if($prompts['newbietask'] && $newbietaskid && substr($newbietasks[$newbietaskid]['scriptname'], 0, 4) == 'post') {
		require_once DISCUZ_ROOT.'./include/task.func.php';
		task_newbie_complete();
	}

	if(empty($viewpid)) {
		include template('viewthread');
	} else {
		$admode = 0;
		$post = $postlist[$viewpid];
		$post['number'] = $sdb->result_first("SELECT count(*) FROM {$tablepre}posts WHERE tid='$post[tid]' AND dateline<='$post[dbdateline]'");
		include template('header_ajax');
		include template('viewthread_node');
		include template('footer_ajax');
	}

} elseif($action == 'printable' && $tid) {

	require_once DISCUZ_ROOT.'./include/printable.inc.php';

}

function viewthread_updateviews() {
	global $delayviewcount, $timestamp, $tablepre, $tid, $db, $adminid, $thread, $do;

	if($delayviewcount == 1 || $delayviewcount == 3) {
		$logfile = './forumdata/cache/cache_threadviews.log';
		if(substr($timestamp, -2) == '00') {
			require_once DISCUZ_ROOT.'./include/misc.func.php';
			updateviews('threads', 'tid', 'views', $logfile);
		}
		if(@$fp = fopen(DISCUZ_ROOT.$logfile, 'a')) {
			fwrite($fp, "$tid\n");
			fclose($fp);
		} elseif($adminid == 1) {
			showmessage('view_log_invalid');
		}
	} else {

		$db->query("UPDATE LOW_PRIORITY {$tablepre}threads SET views=views+1 WHERE tid='$tid'", 'UNBUFFERED');

		if(is_array($dzfeed_limit['thread_views']) && in_array(($thread['views'] + 1), $dzfeed_limit['thread_views'])) {
			$arg = $data = array();
			$arg['type'] = 'thread_views';
			$arg['fid'] = $thread['fid'];
			$arg['typeid'] = $thread['typeid'];
			$arg['sortid'] = $thread['sortid'];
			$arg['uid'] = $thread['authorid'];
			$arg['username'] = addslashes($thread['author']);
			$data['title']['actor'] = $thread['authorid'] ? "<a href=\"space.php?uid={$thread[authorid]}\" target=\"_blank\">{$thread[author]}</a>" : $thread['author'];
			$data['title']['forum'] = "<a href=\"forumdisplay.php?fid={$thread[fid]}\" target=\"_blank\">".$GLOBALS['forum']['name'].'</a>';
			$data['title']['count'] = $thread['replies'] + 1;
			$data['title']['subject'] = "<a href=\"viewthread.php?tid={$thread[tid]}\" target=\"_blank\">{$thread[subject]}</a>";
			add_feed($arg, $data);
		}

	}
}

function viewthread_procpost($post, $special = 0) {

	global $_DCACHE, $newpostanchor, $numpost, $thisbg, $postcount, $ratelogpids, $onlineauthors, $lastvisit, $thread,
		$attachpids, $attachtags, $forum, $dateformat, $timeformat, $timeoffset, $userstatusby, $allowgetattach,
		$ratelogrecord, $showimages, $forum, $discuz_uid, $showavatars, $pagebydesc, $ppp, $ppp2, $ppp3,
		$firstpid, $threadpay, $sigviewcond, $ordertype;

	if(!$newpostanchor && $post['dateline'] > $lastvisit) {
		$post['newpostanchor'] = '<a name="newpost"></a>';
		$newpostanchor = 1;
	} else {
		$post['newpostanchor'] = '';
	}

	$post['lastpostanchor'] = ($ordertype != 1 && $numpost == $thread['replies']) || ($ordertype == 1 && $numpost == $thread['replies'] + 2) ? '<a name="lastpost"></a>' : '';

	if($pagebydesc) {
		if($ordertype != 1) {
			$post['number'] = $numpost + $ppp2--;
		} else {
			$post['number'] = $post['first'] == 1 ? 1 : $numpost - $ppp2--;
		}
		$post['count'] = $ppp == $ppp3 ? $ppp - $postcount - 1 : $ppp3 - $postcount - 1;
	} else {
		if($ordertype != 1) {
			$post['number'] = ++$numpost;
		} else {
			$post['number'] = $post['first'] == 1 ? 1 : --$numpost;
		}
		$post['count'] = $postcount;
	}

	$postcount++;

	$post['dbdateline'] = $post['dateline'];
	$post['dateline'] = dgmdate("$dateformat $timeformat", $post['dateline'] + $timeoffset * 3600);
	$post['groupid'] = $_DCACHE['usergroups'][$post['groupid']] ? $post['groupid'] : 7;

	if($post['username']) {
		$onlineauthors[] = $post['authorid'];
		$post['usernameenc'] = rawurlencode($post['username']);
		!$special && $post['groupid'] = getgroupid($post['authorid'], $_DCACHE['usergroups'][$post['groupid']], $post);
		$post['readaccess'] = $_DCACHE['usergroups'][$post['groupid']]['readaccess'];
		if($_DCACHE['usergroups'][$post['groupid']]['userstatusby'] == 1) {
			$post['authortitle'] = $_DCACHE['usergroups'][$post['groupid']]['grouptitle'];
			$post['stars'] = $_DCACHE['usergroups'][$post['groupid']]['stars'];
		} elseif($_DCACHE['usergroups'][$post['groupid']]['userstatusby'] == 2) {
			foreach($_DCACHE['ranks'] as $rank) {
				if($post['posts'] > $rank['postshigher']) {
					$post['authortitle'] = $rank['ranktitle'];
					$post['stars'] = $rank['stars'];
					break;
				}
			}
		}

		$post['taobaoas'] = addslashes($post['taobao']);
		$post['authoras'] = !$post['anonymous'] ? ' '.addslashes($post['author']) : '';
		$post['regdate'] = gmdate($dateformat, $post['regdate'] + $timeoffset * 3600);
		$post['lastdate'] = gmdate($dateformat, $post['lastactivity'] + $timeoffset * 3600);

		if($post['medals']) {
			@include_once DISCUZ_ROOT.'./forumdata/cache/cache_medals.php';
			foreach($post['medals'] = explode("\t", $post['medals']) as $key => $medalid) {
				list($medalid, $medalexpiration) = explode("|", $medalid);
				if(isset($_DCACHE['medals'][$medalid]) && (!$medalexpiration || $medalexpiration > $timestamp)) {
					$post['medals'][$key] = $_DCACHE['medals'][$medalid];
				} else {
					unset($post['medals'][$key]);
				}
			}
		}
		if($showavatars) {
			$post['avatar'] = discuz_uc_avatar($post['authorid']);
			if($_DCACHE['usergroups'][$post['groupid']]['groupavatar']) {
				$post['avatar'] .= '<br /><img src="'.$_DCACHE['usergroups'][$post['groupid']]['groupavatar'].'" border="0" alt="" />';
			}
		} else {
			$post['avatar'] = '';
		}

		$post['banned'] = $post['status'] & 1;
		$post['warned'] = ($post['status'] & 2) >> 1;
		$post['msn'] = explode("\t", $post['msn']);

	} else {
		if(!$post['authorid']) {
			$post['useip'] = substr($post['useip'], 0, strrpos($post['useip'], '.')).'.x';
		}
	}
	$post['attachments'] = array();
	if($post['attachment']) {
		if($allowgetattach && !$threadpay) {
			$attachpids .= ",$post[pid]";
			$post['attachment'] = 0;
			if(preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $post['message'], $matchaids)) {
				$attachtags[$post['pid']] = $matchaids[1];
			}
		} else {
			$post['message'] = preg_replace("/\[attach\](\d+)\[\/attach\]/i", '', $post['message']);
		}
	}

	$ratelogpids .= ($ratelogrecord && $post['rate']) ? ','.$post['pid'] : '';
	$forum['allowbbcode'] = $forum['allowbbcode'] ? ($_DCACHE['usergroups'][$post['groupid']]['allowcusbbcode'] ? 2 : 1) : 0;
	$post['signature'] = $post['usesig'] ? ($sigviewcond ? (strlen($post['message']) > $sigviewcond ? $post['signature'] : '') : $post['signature']) : '';
	$post['message'] = discuzcode($post['message'], $post['smileyoff'], $post['bbcodeoff'], $post['htmlon'] & 1, $forum['allowsmilies'], $forum['allowbbcode'], ($forum['allowimgcode'] && $showimages ? 1 : 0), $forum['allowhtml'], ($forum['jammer'] && $post['authorid'] != $discuz_uid ? 1 : 0), 0, $post['authorid'], $forum['allowmediacode'], $post['pid']);
	$post['first'] && $firstpid = $post['pid'];
	$firstpid = intval($firstpid);
	return $post;
}

function showoption($var, $type) {
	global $optionlist;
	if($optionlist[$var][$type]) {
		return $optionlist[$var][$type];
	} else {
		return '';
	}
}

function viewthread_loadcache() {
	global $tid, $forum, $timestamp, $cachethreadlife, $_DCACHE, $gzipcompress, $debug, $styleid;
	$forum['livedays'] = ceil(($timestamp - $forum['dateline']) / 86400);
	$forum['lastpostdays'] = ceil(($timestamp - $forum['lastthreadpost']) / 86400);
	$threadcachemark = 100 - (
		$forum['displayorder'] * 15 +
		$forum['digest'] * 10 +
		min($forum['views'] / max($forum['livedays'], 10) * 2, 50) +
		max(-10, (15 - $forum['lastpostdays'])) +
		min($forum['replies'] / $_DCACHE['settings']['postperpage'] * 1.5, 15));
	if($threadcachemark < $forum['threadcaches']) {

		$threadcache = getcacheinfo($tid);

		if($timestamp - $threadcache['filemtime'] > $cachethreadlife) {
			@unlink($threadcache['filename']);
			define('CACHE_FILE', $threadcache['filename']);
			$styleid = $_DCACHE['settings']['styleid'];
			@include DISCUZ_ROOT.'./forumdata/cache/style_'.$styleid.'.php';
		} else {
			readfile($threadcache['filename']);

			viewthread_updateviews();
			$debug && debuginfo();
			$debug ? die('<script type="text/javascript">document.getElementById("debuginfo").innerHTML = " '.($debug ? 'Updated at '.gmdate("H:i:s", $threadcache['filemtime'] + 3600 * 8).', Processed in '.$debuginfo['time'].' second(s), '.$debuginfo['queries'].' Queries'.($gzipcompress ? ', Gzip enabled' : '') : '').'";</script>') : die();
		}
	}
}

function viewthread_lastmod() {
	global $db, $tablepre, $dateformat, $timeformat, $threadstamp, $timeoffset, $tid, $_DCACHE;
	if($lastmod = $db->fetch_first("SELECT uid AS moduid, username AS modusername, dateline AS moddateline, action AS modaction, magicid, stamp
		FROM {$tablepre}threadsmod
		WHERE tid='$tid' ORDER BY dateline DESC LIMIT 1")) {
		include language('modactions');
		$lastmod['modusername'] = $lastmod['modusername'] ? $lastmod['modusername'] : 'System';
		$lastmod['moddateline'] = dgmdate("$dateformat $timeformat", $lastmod['moddateline'] + $timeoffset * 3600);
		$threadstamp = $lastmod['modaction'] != 'SPA' ? array() : $_DCACHE['stamps'][$lastmod['stamp']];
		$lastmod['modaction'] = ($modactioncode[$lastmod['modaction']] ? $modactioncode[$lastmod['modaction']] : '').($lastmod['modaction'] != 'SPA' ? '' : ' '.$_DCACHE['stamps'][$lastmod['stamp']]['text']);
		if($lastmod['magicid']) {
			require_once DISCUZ_ROOT.'./forumdata/cache/cache_magics.php';
			$lastmod['magicname'] = $_DCACHE['magics'][$lastmod['magicid']]['name'];
		}
	} else {
		$db->query("UPDATE {$tablepre}threads SET moderated='0' WHERE tid='$tid'", 'UNBUFFERED');
	}
	return $lastmod;
}

function viewthread_parsetags() {
	global $tagstatus, $_DCACHE, $firstpid, $postlist, $forum, $tagscript;
	if($firstpid && $tagstatus && $forum['allowtag'] && !($postlist[$firstpid]['htmlon'] & 2) && !empty($_DCACHE['tags'])) {
		$tagscript = '<script type="text/javascript">var tagarray = '.$GLOBALS['_DCACHE']['tags'][0].';var tagencarray = '.$GLOBALS['_DCACHE']['tags'][1].';parsetag('.$firstpid.');</script>';
	}
}

function remaintime($time) {
	$days = intval($time / 86400);
	$time -= $days * 86400;
	$hours = intval($time / 3600);
	$time -= $hours * 3600;
	$minutes = intval($time / 60);
	$time -= $minutes * 60;
	$seconds = $time;
	return array((int)$days, (int)$hours, (int)$minutes, (int)$seconds);
}

?>
