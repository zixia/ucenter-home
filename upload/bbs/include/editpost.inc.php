<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: editpost.inc.php 21308 2009-11-26 01:08:59Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$discuz_action = 13;

$orig = $db->fetch_first("SELECT m.adminid, p.first, p.authorid, p.author, p.dateline, p.anonymous, p.invisible, p.htmlon FROM {$tablepre}posts p
	LEFT JOIN {$tablepre}members m ON m.uid=p.authorid
	WHERE pid='$pid' AND tid='$tid' AND fid='$fid'");

if($magicstatus) {
	$magicid = $db->result_first("SELECT magicid FROM {$tablepre}threadsmod WHERE tid='$tid' AND magicid='10'");
	$allowanonymous = $allowanonymous || $magicid ? 1 : $allowanonymous;
}

$isfirstpost = $orig['first'] ? 1 : 0;
$isorigauthor = $discuz_uid && $discuz_uid == $orig['authorid'];
$isanonymous = $isanonymous && $allowanonymous ? 1 : 0;
$audit = $orig['invisible'] == -2 || $thread['displayorder'] == -2 ? $audit : 0;

if(empty($orig)) {
	showmessage('undefined_action');
} elseif((!$forum['ismoderator'] || !$alloweditpost || (in_array($orig['adminid'], array(1, 2, 3)) && $adminid > $orig['adminid'])) && !($forum['alloweditpost'] && $isorigauthor)) {
	showmessage('post_edit_nopermission', NULL, 'HALTED');
} elseif($isorigauthor && !$forum['ismoderator']) {
	if($edittimelimit && $timestamp - $orig['dateline'] > $edittimelimit * 60) {
		showmessage('post_edit_timelimit', NULL, 'HALTED');
	}
}

$thread['pricedisplay'] = $thread['price'] == -1 ? 0 : $thread['price'];

if($tagstatus && $isfirstpost) {
	$query = $db->query("SELECT tagname FROM {$tablepre}threadtags WHERE tid='$tid'");
	$threadtagary = array();
	while($tagname = $db->fetch_array($query)) {
		$threadtagary[] = $tagname['tagname'];
	}
	$threadtags = dhtmlspecialchars(implode(' ',$threadtagary));
}

if($special == 5) {
	$debate = array_merge($thread, daddslashes($db->fetch_first("SELECT * FROM {$tablepre}debates WHERE tid='$tid'"), 1));
	$firststand = $db->result_first("SELECT stand FROM {$tablepre}debateposts WHERE tid='$tid' AND uid='$discuz_uid' AND stand<>'0' ORDER BY dateline LIMIT 1");

	if(!$isfirstpost && $debate['endtime'] && $debate['endtime'] < $timestamp && !$forum['ismoderator']) {
		showmessage('debate_end');
	}
	if($isfirstpost && $debate['umpirepoint'] && !$forum['ismoderator']) {
		showmessage('debate_umpire_comment_invalid');
	}
}

$rushreply = getstatus($thread['status'], 3);

$savepostposition = getstatus($thread['status'], 1);

if(!submitcheck('editsubmit')) {

	$hiddenreplies = getstatus($thread['status'], 2);


	$icons = '';
	if(!$special && is_array($_DCACHE['icons']) && $isfirstpost) {
		$key = 1;
		foreach($_DCACHE['icons'] as $id => $icon) {
			$icons .= ' <input class="radio" type="radio" name="iconid" value="'.$id.'" '.($thread['iconid'] == $id ? 'checked="checked"' : '').' /><img src="images/icons/'.$icon.'" alt="" />';
			$icons .= !(++$key % 10) ? '<br />' : '';
		}
	}

	$postinfo = $db->fetch_first("SELECT * FROM {$tablepre}posts WHERE pid='$pid' AND tid='$tid' AND fid='$fid'");

	$usesigcheck = $postinfo['usesig'] ? 'checked="checked"' : '';
	$urloffcheck = $postinfo['parseurloff'] ? 'checked="checked"' : '';
	$smileyoffcheck = $postinfo['smileyoff'] == 1 ? 'checked="checked"' : '';
	$codeoffcheck = $postinfo['bbcodeoff'] == 1 ? 'checked="checked"' : '';
	$tagoffcheck = $postinfo['htmlon'] & 2 ? 'checked="checked"' : '';
	$htmloncheck = $postinfo['htmlon'] & 1 ? 'checked="checked"' : '';
	$showthreadsorts = ($thread['sortid'] || !empty($sortid)) && $isfirstpost;
	$sortid = empty($sortid) ? $thread['sortid'] : $sortid;

	$poll = $temppoll = '';
	if($isfirstpost) {
		if($special == 127) {
			$sppos = strrpos($postinfo['message'], chr(0).chr(0).chr(0));
			$specialextra = substr($postinfo['message'], $sppos + 3);
			if($specialextra && array_key_exists($specialextra, $threadplugins) && in_array($specialextra, unserialize($forum['threadplugin'])) && in_array($specialextra, $allowthreadplugin)) {
				$postinfo['message'] = substr($postinfo['message'], 0, $sppos);
			} else {
				$special = 0;
				$specialextra = '';
			}
		}
		$thread['freecharge'] = $maxchargespan && $timestamp - $thread['dateline'] >= $maxchargespan * 3600 ? 1 : 0;
		$freechargehours = !$thread['freecharge'] ? $maxchargespan - intval(($timestamp - $thread['dateline']) / 3600) : 0;
		if($thread['special'] == 1 && ($alloweditpoll || $thread['authorid'] == $discuz_uid)) {
			$query = $db->query("SELECT polloptionid, displayorder, polloption, multiple, visible, maxchoices, expiration, overt FROM {$tablepre}polloptions AS polloptions LEFT JOIN {$tablepre}polls AS polls ON polloptions.tid=polls.tid WHERE polls.tid ='$tid' ORDER BY displayorder");
			while($temppoll = $db->fetch_array($query)) {
				$poll['multiple'] = $temppoll['multiple'];
				$poll['visible'] = $temppoll['visible'];
				$poll['maxchoices'] = $temppoll['maxchoices'];
				$poll['expiration'] = $temppoll['expiration'];
				$poll['overt'] = $temppoll['overt'];
				$poll['polloptionid'][] = $temppoll['polloptionid'];
				$poll['displayorder'][] = $temppoll['displayorder'];
				$poll['polloption'][] = stripslashes($temppoll['polloption']);
			}
			$maxpolloptions = $maxpolloptions - $db->num_rows($query);
		} elseif($thread['special'] == 3) {
			$rewardprice = abs($thread['price']);
		} elseif($thread['special'] == 4) {
			$activitytypelist = $activitytype ? explode("\n", trim($activitytype)) : '';
			$activity = $db->fetch_first("SELECT * FROM {$tablepre}activities WHERE tid='$tid'");
			$activity['starttimefrom'] = gmdate("Y-m-d H:i", $activity['starttimefrom'] + $timeoffset * 3600);
			$activity['starttimeto'] = $activity['starttimeto'] ? gmdate("Y-m-d H:i", $activity['starttimeto'] + $timeoffset * 3600) : '';
			$activity['expiration'] = $activity['expiration'] ? gmdate("Y-m-d H:i", $activity['expiration'] + $timeoffset * 3600) : '';
		} elseif($thread['special'] == 5 ) {
			$debate['endtime'] = $debate['endtime'] ? gmdate("Y-m-d H:i", $debate['endtime'] + $timeoffset * 3600) : '';
		}
	}

	if($thread['special'] == 2 && ($thread['authorid'] == $discuz_uid && $allowposttrade || $allowedittrade)) {
		$query = $db->query("SELECT * FROM {$tablepre}trades WHERE pid='$pid'");
		$tradetypeselect = '';
		if($db->num_rows($query)) {
			$trade = $db->fetch_array($query);
			$trade['expiration'] = $trade['expiration'] ? date('Y-m-d', $trade['expiration']) : '';
			$trade['costprice'] = $trade['costprice'] > 0 ? $trade['costprice'] : '';
			$trade['message'] = dhtmlspecialchars($trade['message']);
			$tradetypeid = $trade['typeid'];
			$forum['tradetypes'] = $forum['tradetypes'] == '' ? -1 : unserialize($forum['tradetypes']);
			if((!$tradetypeid || !isset($tradetypes[$tradetypeid]) && !empty($forum['tradetypes']))) {
				$tradetypeselect = '<select name="tradetypeid" onchange="ajaxget(\'post.php?action=threadsorts&tradetype=yes&sortid=\'+this.options[this.selectedIndex].value+\'&sid='.$sid.'\', \'threadtypes\', \'threadtypeswait\')"><option value="0">&nbsp;</option>';
				foreach($tradetypes as $typeid => $name) {
					if($forum['tradetypes'] == -1 || @in_array($typeid, $forum['tradetypes'])) {
						$tradetypeselect .= '<option value="'.$typeid.'">'.strip_tags($name).'</option>';
					}
				}
				$tradetypeselect .= '</select><span id="threadtypeswait"></span>';
			} else {
				$tradetypeselect = '<select disabled><option>'.$tradetypes[$trade['typeid']].'</option></select>';
			}
			$expiration_7days = date('Y-m-d', $timestamp + 86400 * 7);
			$expiration_14days = date('Y-m-d', $timestamp + 86400 * 14);
			$expiration_month = date('Y-m-d', mktime(0, 0, 0, date('m')+1, date('d'), date('Y')));
			$expiration_3months = date('Y-m-d', mktime(0, 0, 0, date('m')+3, date('d'), date('Y')));
			$expiration_halfyear = date('Y-m-d', mktime(0, 0, 0, date('m')+6, date('d'), date('Y')));
			$expiration_year = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d'), date('Y')+1));
		} else {
			$tradetypeid = $special = 0;
			$trade = array();
		}
	}

	if($isfirstpost && $specialextra) {
		@include_once DISCUZ_ROOT.'./plugins/'.$threadplugins[$specialextra]['module'].'.class.php';
		$classname = 'threadplugin_'.$specialextra;
		if(method_exists($classname, 'editpost')) {
			$threadpluginclass = new $classname;
			$threadplughtml = $threadpluginclass->editpost($fid, $tid);
		}
	}

	$postinfo['subject'] = str_replace('"', '&quot;', $postinfo['subject']);
	$postinfo['message'] = dhtmlspecialchars($postinfo['message']);
	include_once language('misc');
	$postinfo['message'] = preg_replace($language['post_edit_regexp'], '', $postinfo['message']);

	if($special == 5) {
		$standselected = array($firststand => 'selected="selected"');
	}

	if($allowpostattach) {
		$attachlist = getattach();
		$attachs = $attachlist['attachs'];
		$imgattachs = $attachlist['imgattachs'];
		unset($attachlist);
		$attachfind = $attachreplace = array();
		if($attachs['used']) {
			foreach($attachs['used'] as $attach) {
				if($attach['isimage']) {
					$attachfind[] = "/\[attach\]$attach[aid]\[\/attach\]/i";
					$attachreplace[] = '[attachimg]'.$attach['aid'].'[/attachimg]';
				}
			}
		}
		if($imgattachs['used']) {
			foreach($imgattachs['used'] as $attach) {
				$attachfind[] = "/\[attach\]$attach[aid]\[\/attach\]/i";
				$attachreplace[] = '[attachimg]'.$attach['aid'].'[/attachimg]';
			}
		}
		$attachfind && $postinfo['message'] = preg_replace($attachfind, $attachreplace, $postinfo['message']);
	}
	if($special == 2 && $trade['aid'] && !empty($imgattachs['used']) && is_array($imgattachs['used'])) {
		foreach($imgattachs['used'] as $k => $tradeattach) {
			if($tradeattach['aid'] == $trade['aid']) {
				unset($imgattachs['used'][$k]);
				break;
			}
		}
	}

	include template('post');

} else {

	$redirecturl = "viewthread.php?tid=$tid&page=$page&extra=$extra".($vid && $isfirstpost ? "&vid=$vid" : '')."#pid$pid";

	if(empty($delete)) {

		if($post_invalid = checkpost($isfirstpost && $special)) {
			showmessage($post_invalid);
		}

		if(!$isorigauthor && !$allowanonymous) {
			if($orig['anonymous'] && !$isanonymous) {
				$isanonymous = 0;
				$authoradd = ', author=\''.addslashes($orig['author']).'\'';
				$anonymousadd = ', anonymous=\'0\'';
			} else {
				$isanonymous = $orig['anonymous'];
				$authoradd = $anonymousadd = '';
			}
		} else {
			$authoradd = ', author=\''.($isanonymous ? '' : addslashes($orig['author'])).'\'';
			$anonymousadd = ", anonymous='$isanonymous'";
		}

		if($isfirstpost) {

			if($subject == '') {
				showmessage('post_sm_isnull');
			}

			if(!$sortid && !$thread['special'] && $message == '') {
				showmessage('post_sm_isnull');
			}

			$typeid = isset($forum['threadtypes']['types'][$typeid]) ? $typeid : 0;
			$sortid = isset($forum['threadsorts']['types'][$sortid]) ? $sortid : 0;
			$iconid = isset($_DCACHE['icons'][$iconid]) ? $iconid : 0;

			if(!$typeid && $forum['threadtypes']['required'] && !$thread['special']) {
				showmessage('post_type_isnull');
			}

			$readperm = $allowsetreadperm ? intval($readperm) : ($isorigauthor ? 0 : 'readperm');
			if($thread['special'] == 3) {
				$price = $thread['price'];
			}
			$price = intval($price);
			$price = $thread['price'] < 0 && !$thread['special']
				?($isorigauthor || !$price ? -1 : $price)
				:($maxprice ? ($price <= $maxprice ? ($price > 0 ? $price : 0) : $maxprice) : ($isorigauthor ? $price : $thread['price']));

			if($price > 0 && floor($price * (1 - $creditstax)) == 0) {
				showmessage('post_net_price_iszero');
			}

			$polladd = '';
			if($thread['special'] == 1 && ($alloweditpoll || $isorigauthor) && !empty($polls)) {
				$pollarray = '';
				$pollarray['options'] = $polloption;
				if($pollarray['options']) {
					if(count($pollarray['options']) > $maxpolloptions) {
						showmessage('post_poll_option_toomany');
					}
					foreach($pollarray['options'] as $key => $value) {
						if(!trim($value)) {
							$db->query("DELETE FROM {$tablepre}polloptions WHERE polloptionid='$key' AND tid='$tid'");
							unset($pollarray['options'][$key]);
						}
					}
					$polladd = ', special=\'1\'';
					foreach($displayorder as $key => $value) {
						if(preg_match("/^-?\d*$/", $value)) {
							$pollarray['displayorder'][$key] = $value;
						}
					}
					$pollarray['multiple'] = !empty($multiplepoll);
					$pollarray['visible'] = empty($visibilitypoll);
					$pollarray['expiration'] = $expiration;
					$pollarray['overt'] = !empty($overt);
					foreach($polloptionid as $key => $value) {
						if(!preg_match("/^\d*$/", $value)) {
							showmessage('submit_invalid');
						}
					}
					$maxchoices = !empty($multiplepoll) ? (!$maxchoices || $maxchoices >= count($pollarray['options']) ? count($pollarray['options']) : $maxchoices) : '';
					if(preg_match("/^\d*$/", $maxchoices)) {
						if(!$pollarray['multiple']) {
							$pollarray['maxchoices'] = 1;
						} elseif(empty($maxchoices)) {
							$pollarray['maxchoices'] = 0;
						} else {
							$pollarray['maxchoices'] = $maxchoices;
						}
					}
					$expiration = intval($expiration);
					if($close) {
						$pollarray['expiration'] = $timestamp;
					} elseif($expiration) {
						if(empty($pollarray['expiration'])) {
							$pollarray['expiration'] = 0;
						} else {
							$pollarray['expiration'] = $timestamp + 86400 * $expiration;
						}
					}
					$optid = '';
					$query = $db->query("SELECT polloptionid FROM {$tablepre}polloptions WHERE tid='$tid'");
					while($tempoptid = $db->fetch_array($query)) {
						$optid[] = $tempoptid['polloptionid'];
					}

					foreach($pollarray['options'] as $key => $value) {
						$value = dhtmlspecialchars(trim($value));
						if(in_array($polloptionid[$key], $optid)) {
							if($alloweditpoll) {
								$db->query("UPDATE {$tablepre}polloptions SET displayorder='".$pollarray['displayorder'][$key]."', polloption='$value' WHERE polloptionid='$polloptionid[$key]' AND tid='$tid'");
							} else {
								$db->query("UPDATE {$tablepre}polloptions SET displayorder='".$pollarray['displayorder'][$key]."' WHERE polloptionid='$polloptionid[$key]' AND tid='$tid'");
							}
						} else {
							$db->query("INSERT INTO {$tablepre}polloptions (tid, displayorder, polloption) VALUES ('$tid', '".$pollarray['displayorder'][$key]."', '$value')");
						}
					}
					$db->query("UPDATE {$tablepre}polls SET multiple='$pollarray[multiple]', visible='$pollarray[visible]', maxchoices='$pollarray[maxchoices]', expiration='$pollarray[expiration]', overt='$pollarray[overt]' WHERE tid='$tid'", 'UNBUFFERED');
				} else {
					$polladd = ', special=\'0\'';
					$db->query("DELETE FROM {$tablepre}polls WHERE tid='$tid'");
					$db->query("DELETE FROM {$tablepre}polloptions WHERE tid='$tid'");
				}

			} elseif($thread['special'] == 3 && ($allowpostreward || $isorigauthor)) {

				if($thread['price'] > 0 && $thread['price'] != $rewardprice) {
					$rewardprice = intval($rewardprice);
					if($rewardprice <= 0){
						showmessage("reward_credits_invalid");
					}
					$addprice = ceil(($rewardprice - $thread['price']) + ($rewardprice - $thread['price']) * $creditstax);
					if(!$forum['ismoderator']) {
						if($rewardprice < $thread['price']) {
							showmessage("reward_credits_fall");
						} elseif($rewardprice < $minrewardprice || ($maxrewardprice > 0 && $rewardprice > $maxrewardprice)) {
							showmessage("reward_credits_between");
						} elseif($addprice > $_DSESSION["extcredits$creditstransextra[2]"]) {
							showmessage('reward_credits_shortage');
						}
					}
					$realprice = ceil($thread['price'] + $thread['price'] * $creditstax) + $addprice;

					$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[2]=extcredits$creditstransextra[2]-$addprice WHERE uid='$thread[authorid]'");
					$db->query("UPDATE {$tablepre}rewardlog SET netamount='$realprice' WHERE tid='$tid' AND authorid='$thread[authorid]'");
				}

				if(!$forum['ismoderator']) {

					if($thread['replies'] > 1) {
						$subject = addslashes($thread['subject']);
					}

					if($thread['price'] < 0) {
						$rewardprice = abs($thread['price']);
					}
				}

				$price = $thread['price'] > 0 ? $rewardprice : -$rewardprice;

			} elseif($thread['special'] == 4 && $allowpostactivity) {

				$activitytime = intval($activitytime);
				if(empty($starttimefrom[$activitytime])) {
					showmessage('activity_fromtime_please');
				} elseif(strtotime($starttimefrom[$activitytime]) === -1 || @strtotime($starttimefrom[$activitytime]) === FALSE) {
					showmessage('activity_fromtime_error');
				} elseif($activitytime && ((@strtotime($starttimefrom) > @strtotime($starttimeto) || !$starttimeto))) {
					showmessage('activity_fromtime_error');
				} elseif(!trim($activityclass)) {
					showmessage('activity_sort_please');
				} elseif(!trim($activityplace)) {
					showmessage('activity_address_please');
				} elseif(trim($activityexpiration) && (@strtotime($activityexpiration) === -1 || @strtotime($activityexpiration) === FALSE)) {
					showmessage('activity_totime_error');
				}

				$activity = array();
				$activity['class'] = dhtmlspecialchars(trim($activityclass));
				$activity['starttimefrom'] = @strtotime($starttimefrom[$activitytime]);
				$activity['starttimeto'] = $activitytime ? @strtotime($starttimeto) : 0;
				$activity['place'] = dhtmlspecialchars(trim($activityplace));
				$activity['cost'] = intval($cost);
				$activity['gender'] = intval($gender);
				$activity['number'] = intval($activitynumber);
				if($activityexpiration) {
					$activity['expiration'] = @strtotime($activityexpiration);
				} else {
					$activity['expiration'] = 0;
				}

				$db->query("UPDATE {$tablepre}activities SET cost='$activity[cost]', starttimefrom='$activity[starttimefrom]', starttimeto='$activity[starttimeto]', place='$activity[place]', class='$activity[class]', gender='$activity[gender]', number='$activity[number]', expiration='$activity[expiration]' WHERE tid='$tid'", 'UNBUFFERED');

			} elseif($thread['special'] == 5 && $allowpostdebate) {

				if(empty($affirmpoint) || empty($negapoint)) {
					showmessage('debate_position_nofound');
				} elseif(!empty($endtime) && (!($endtime = @strtotime($endtime)) || $endtime < $timestamp)) {
					showmessage('debate_endtime_invalid');
				} elseif(!empty($umpire)) {
					if(!$db->result_first("SELECT COUNT(*) FROM {$tablepre}members WHERE username='$umpire'")) {
						$umpire = dhtmlspecialchars($umpire);
						showmessage('debate_umpire_invalid');
					}
				}
				$affirmpoint = dhtmlspecialchars($affirmpoint);
				$negapoint = dhtmlspecialchars($negapoint);
				$db->query("UPDATE {$tablepre}debates SET affirmpoint='$affirmpoint', negapoint='$negapoint', endtime='$endtime', umpire='$umpire' WHERE tid='$tid' AND uid='$discuz_uid'");

			} elseif($specialextra) {

				@include_once DISCUZ_ROOT.'./plugins/'.$threadplugins[$specialextra]['module'].'.class.php';
				$classname = 'threadplugin_'.$specialextra;
				if(method_exists($classname, 'editpost_submit')) {
					$threadpluginclass = new $classname;
					$threadpluginclass->editpost_submit($fid, $tid);
				}

			}

			$optiondata = array();
			if($forum['threadsorts']['types'][$sortid] && $checkoption) {
				$optiondata = threadsort_validator($typeoption);
			}

			if($forum['threadsorts']['types'][$sortid] && $optiondata && is_array($optiondata)) {
				$sql = $separator = '';
				foreach($optiondata as $optionid => $value) {
					if($_DTYPE[$optionid]['type'] == 'image') {
						$oldvalue = $db->result_first("SELECT value FROM {$tablepre}typeoptionvars WHERE tid='$tid' AND optionid='$optionid'");
						if($oldvalue != $value) {
							if(preg_match("/^\[aid=(\d+)\]$/", $oldvalue, $r)) {
								$attach = $db->fetch_first("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE aid='$r[1]'");
								$db->query("DELETE FROM {$tablepre}attachments WHERE aid='$r[1]'");
								dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
							}
						}
					}
					if(($_DTYPE[$optionid]['search'] || in_array($_DTYPE[$optionid]['type'], array('radio', 'select', 'number'))) && $value) {
						$sql .= $separator.$_DTYPE[$optionid]['identifier']."='$value'";
						$separator = ' ,';
					}
					$db->query("UPDATE {$tablepre}typeoptionvars SET value='$value', sortid='$sortid' WHERE tid='$tid' AND optionid='$optionid'");
				}

				if($sql) {
					$db->query("UPDATE {$tablepre}optionvalue$sortid SET $sql WHERE tid='$tid' AND fid='$fid'");
				}
			}

			$thread['status'] = setstatus(4, $ordertype, $thread['status']);

			$thread['status'] = setstatus(2, $hiddenreplies, $thread['status']);

			$db->query("UPDATE {$tablepre}threads SET iconid='$iconid', typeid='$typeid', sortid='$sortid', subject='$subject', readperm='$readperm', price='$price' $authoradd $polladd ".($auditstatuson && $audit == 1 ? ",displayorder='0', moderated='1'" : '').", status='$thread[status]' WHERE tid='$tid'", 'UNBUFFERED');

			if($tagstatus) {
				$tags = str_replace(array(chr(0xa3).chr(0xac), chr(0xa1).chr(0x41), chr(0xef).chr(0xbc).chr(0x8c)), ',', censor($tags));
				if(strexists($tags, ',')) {
					$tagarray = array_unique(explode(',', $tags));
				} else {
					$tags = str_replace(array(chr(0xa1).chr(0xa1), chr(0xa1).chr(0x40), chr(0xe3).chr(0x80).chr(0x80)), ' ', $tags);
					$tagarray = array_unique(explode(' ', $tags));
				}
				$threadtagsnew = array();
				$tagcount = 0;
				foreach($tagarray as $tagname) {
					$tagname = trim($tagname);
					if(preg_match('/^([\x7f-\xff_-]|\w|\s){3,20}$/', $tagname)) {
						$threadtagsnew[] = $tagname;
						if(!in_array($tagname, $threadtagary)) {
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
								$db->query("INSERT {$tablepre}threadtags (tagname, tid) VALUES ('$tagname', '$tid')", 'UNBUFFERED');
							}
						}
					}
					$tagcount++;
					if($tagcount > 4) {
						unset($tagarray);
						break;
					}
				}
				foreach($threadtagary as $tagname) {
					if(!in_array($tagname, $threadtagsnew)) {
						if($db->result_first("SELECT count(*) FROM {$tablepre}threadtags WHERE tagname='$tagname' AND tid!='$tid'")) {
							$db->query("UPDATE {$tablepre}tags SET total=total-1 WHERE tagname='$tagname'", 'UNBUFFERED');
						} else {
							$db->query("DELETE FROM {$tablepre}tags WHERE tagname='$tagname'", 'UNBUFFERED');
						}
						$db->query("DELETE FROM {$tablepre}threadtags WHERE tagname='$tagname' AND tid='$tid'", 'UNBUFFERED');
					}
				}
			}

		} else {

			if($subject == '' && $message == '') {
				showmessage('post_sm_isnull');
			}

		}

		if($editedby && ($timestamp - $orig['dateline']) > 60 && $adminid != 1) {
			include_once language('misc');

			$editor = $isanonymous && $isorigauthor ? $language['anonymous'] : $discuz_user;
			$edittime = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $timestamp + $timeoffset * 3600);
			eval("\$message = \"$language[post_edit]\".\$message;");
		}

		$bbcodeoff = checkbbcodes($message, !empty($bbcodeoff));
		$smileyoff = checksmilies($message, !empty($smileyoff));
		$tagoff = $isfirstpost ? !empty($tagoff) : 0;
		$htmlon = bindec(($tagstatus && $tagoff ? 1 : 0).($allowhtml && !empty($htmlon) ? 1 : 0));

		$uattachment = ($allowpostattach && $uattachments = attach_upload('attachupdate', 1)) ? 1 : 0;
		if($uattachment) {
			$query = $db->query("SELECT aid, tid, pid, uid, attachment, thumb, remote FROM {$tablepre}attachments WHERE pid='$pid'");
			while($attach = $db->fetch_array($query)) {
				$paid = 'paid'.$attach['aid'];
				$attachfileadd = '';
				if($uattachment && isset($uattachments[$paid])) {
					dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
					$attachfileadd = 'dateline=\''.$timestamp.'\',
							filename=\''.$uattachments[$paid]['name'].'\',
							filetype=\''.$uattachments[$paid]['type'].'\',
							filesize=\''.$uattachments[$paid]['size'].'\',
							attachment=\''.$uattachments[$paid]['attachment'].'\',
							thumb=\''.$uattachments[$paid]['thumb'].'\',
							isimage=\'-'.$uattachments[$paid]['isimage'].'\',
							remote=\''.$uattachments[$paid]['remote'].'\',
							width=\''.$uattachments[$paid]['width'].'\'';
					unset($uattachments[$paid]);
				}

				if($attachfileadd) $db->query("UPDATE {$tablepre}attachments SET $attachfileadd WHERE aid='$attach[aid]'");
			}
		}

		$allowpostattach && ($attachnew || $attachdel || $special == 2 && $tradeaid || $isfirstpost && $sortid) && updateattach();

		if($uattachment || $attachdel) {
			$tattachment = $db->result_first("SELECT count(*) FROM {$tablepre}posts p, {$tablepre}attachments a WHERE a.tid='$tid' AND a.isimage IN ('1', '-1') AND a.pid=p.pid AND p.invisible='0' LIMIT 1") ? 2 :
				($db->result_first("SELECT count(*) FROM {$tablepre}posts p, {$tablepre}attachments a WHERE a.tid='$tid' AND a.pid=p.pid AND p.invisible='0' LIMIT 1") ? 1 : 0);

			$db->query("UPDATE {$tablepre}threads SET attachment='$tattachment' WHERE tid='$tid'");
		}

		if($special == 2 && $allowposttrade) {

			$oldtypeid = $db->result_first("SELECT typeid FROM {$tablepre}trades WHERE pid='$pid'");
			$oldtypeid = isset($tradetypes[$oldtypeid]) ? $oldtypeid : 0;
			$tradetypeid = !$tradetypeid ? $oldtypeid : $tradetypeid;
			$optiondata = array();
			threadsort_checkoption($oldtypeid, 1);
			$optiondata = array();
			if($tradetypes && $typeoption && is_array($typeoption) && $checkoption) {
				$optiondata = threadsort_validator($typeoption);
			}

			if($tradetypes && $optiondata && is_array($optiondata)) {
				foreach($optiondata as $optionid => $value) {
					if($oldtypeid) {
						$db->query("UPDATE {$tablepre}tradeoptionvars SET value='$value' WHERE pid='$pid' AND optionid='$optionid'");
					} else {
						$db->query("INSERT INTO {$tablepre}tradeoptionvars (sortid, pid, optionid, value)
							VALUES ('$tradetypeid', '$pid', '$optionid', '$value')");
					}
				}
			}

			if(!$oldtypeid) {
				$db->query("UPDATE {$tablepre}trades SET typeid='$tradetypeid' WHERE pid='$pid'");
			}

			if($trade = $db->fetch_first("SELECT * FROM {$tablepre}trades WHERE tid='$tid' AND pid='$pid'")) {
				$seller = dhtmlspecialchars(trim($seller));
				$item_name = dhtmlspecialchars(trim($item_name));
				$item_price = floatval($item_price);
				$item_credit = intval($item_credit);
				$item_locus = dhtmlspecialchars(trim($item_locus));
				$item_number = intval($item_number);
				$item_quality = intval($item_quality);
				$item_transport = intval($item_transport);
				$postage_mail = intval($postage_mail);
				$postage_express = intval(trim($postage_express));
				$postage_ems = intval($postage_ems);
				$item_type = intval($item_type);
				$item_costprice = floatval($item_costprice);

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

				if($trade['aid'] != $tradeaid) {
					$attach = $db->fetch_first("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE aid='$trade[aid]'");
					$db->query("DELETE FROM {$tablepre}attachments WHERE aid='$trade[aid]'");
					dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
				}

				$expiration = $item_expiration ? @strtotime($item_expiration) : 0;
				$closed = $expiration > 0 && @strtotime($item_expiration) < $timestamp ? 1 : $closed;

				switch($transport) {
					case 'seller':$item_transport = 1;break;
					case 'buyer':$item_transport = 2;break;
					case 'virtual':$item_transport = 3;break;
					case 'logistics':$item_transport = 4;break;
				}
				if(!$item_price || $item_price <= 0) {
					$item_price = $postage_mail = $postage_express = $postage_ems = '';
				}

				$db->query("UPDATE {$tablepre}trades SET aid='$tradeaid', account='$seller', subject='$item_name', price='$item_price', amount='$item_number', quality='$item_quality', locus='$item_locus',
					transport='$item_transport', ordinaryfee='$postage_mail', expressfee='$postage_express', emsfee='$postage_ems', itemtype='$item_type', expiration='$expiration', closed='$closed',
					costprice='$item_costprice', credit='$item_credit', costcredit='$item_costcredit' WHERE tid='$tid' AND pid='$pid'", 'UNBUFFERED');

				if(!empty($infloat)) {
					$viewpid = $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$tid' AND first='1' LIMIT 1");
					$redirecturl = "viewthread.php?tid=$tid&viewpid=$viewpid#pid$viewpid";
				} else {
					$redirecturl = "viewthread.php?do=tradeinfo&tid=$tid&pid=$pid";
				}
			}

		}

		$feed = array();
		if($special == 127) {

			$message .= chr(0).chr(0).chr(0).$specialextra;

		}

		if($auditstatuson && $audit == 1) {
			updatepostcredits('+', $orig['authorid'], ($isfirstpost ? $postcredits : $replycredits));
			updatemodworks('MOD', 1);
			updatemodlog($tid, 'MOD');
		}

		$displayorder = $pinvisible = 0;
		if($isfirstpost) {
			$displayorder = $modnewthreads ? -2 : $thread['displayorder'];
			$pinvisible = $modnewthreads ? -2 : 0;
		} else {
			$pinvisible = $modnewreplies ? -2 : 0;
		}

		$message = preg_replace('/\[attachimg\](\d+)\[\/attachimg\]/is', '[attach]\1[/attach]', $message);
		$db->query("UPDATE {$tablepre}posts SET message='$message', usesig='$usesig', htmlon='$htmlon', bbcodeoff='$bbcodeoff', parseurloff='$parseurloff',
			smileyoff='$smileyoff', subject='$subject' ".($db->result_first("SELECT aid FROM {$tablepre}attachments WHERE pid='$pid' LIMIT 1") ? ", attachment='1'" : '')." $anonymousadd ".($auditstatuson && $audit == 1 ? ",invisible='0'" : ", invisible='$pinvisible'")." WHERE pid='$pid'");

		$forum['lastpost'] = explode("\t", $forum['lastpost']);

		if($orig['dateline'] == $forum['lastpost'][2] && ($orig['author'] == $forum['lastpost'][3] || ($forum['lastpost'][3] == '' && $orig['anonymous']))) {
			$lastpost = "$tid\t".($isfirstpost ? $subject : addslashes($thread['subject']))."\t$orig[dateline]\t".($isanonymous ? '' : addslashes($orig['author']));
			$db->query("UPDATE {$tablepre}forums SET lastpost='$lastpost' WHERE fid='$fid'", 'UNBUFFERED');
		}

		if(!$auditstatuson || $audit != 1) {
			if($isfirstpost && $modnewthreads) {
				$db->query("UPDATE {$tablepre}threads SET displayorder='-2' WHERE tid='$tid'");
			} elseif(!$isfirstpost && $modnewreplies) {
				$db->query("UPDATE {$tablepre}threads SET replies=replies-'1' WHERE tid='$tid'");
			}
		}

		if($thread['lastpost'] == $orig['dateline'] && ((!$orig['anonymous'] && $thread['lastposter'] == $orig['author']) || ($orig['anonymous'] && $thread['lastposter'] == '')) && $orig['anonymous'] != $isanonymous) {
			$db->query("UPDATE {$tablepre}threads SET lastposter='".($isanonymous ? '' : addslashes($orig['author']))."' WHERE tid='$tid'", 'UNBUFFERED');
		}

		$attentionon = empty($attention_add) ? 0 : 1;
		$attentionoff = empty($attention_remove) ? 0 : 1;
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
				write_statlog('', 'item=attention&action=editpost_'.$stataction, '', '', 'my.php');
			}
		}

		if(!$isorigauthor) {
			updatemodworks('EDT', 1);
			require_once DISCUZ_ROOT.'./include/misc.func.php';
			modlog($thread, 'EDT');
		}

		if($thread['special'] == 3 && $isfirstpost && $thread['price'] > 0) {
			$pricediff = $rewardprice - $thread['price'];
			$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[2]=extcredits$creditstransextra[2]-$pricediff WHERE uid='$orig[authorid]'", 'UNBUFFERED');
		}

	} else {

		if($isfirstpost && $thread['replies'] > 0) {
			showmessage(($thread['special'] == 3 ? 'post_edit_reward_already_reply' : 'post_edit_thread_already_reply'), NULL, 'HALTED');
		}

		if($thread['special'] == 3) {
			if($thread['price'] < 0 && ($thread['dateline'] + 1 == $orig['dateline'])) {
				showmessage('post_edit_reward_nopermission', NULL, 'HALTED');
			}
		}

		if($rushreply) {
			showmessage('post_edit_delete_rushreply_nopermission', NULL, 'HALTED');
		}

		updatepostcredits('-', $orig['authorid'], ($isfirstpost ? $postcredits : $replycredits));

		if($thread['special'] == 3 && $isfirstpost) {
			$db->query("UPDATE {$tablepre}members SET extcredits$creditstransextra[2]=extcredits$creditstransextra[2]+$thread[price] WHERE uid='$orig[authorid]'", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}rewardlog WHERE tid='$tid'", 'UNBUFFERED');
		}

		$thread_attachment = $post_attachment = 0;
		$query = $db->query("SELECT pid, attachment, thumb, remote FROM {$tablepre}attachments WHERE tid='$tid'");
		while($attach = $db->fetch_array($query)) {
			if($attach['pid'] == $pid) {
				$post_attachment ++;
				dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
			} else {
				$thread_attachment = 1;
			}
		}

		if($post_attachment) {
			$db->query("DELETE FROM {$tablepre}attachments WHERE pid='$pid'", 'UNBUFFEREED');
			$db->query("DELETE FROM {$tablepre}attachmentfields WHERE pid='$pid'", 'UNBUFFERED');
			updatecredits($orig['authorid'], $postattachcredits, -($post_attachment));
		}

		$db->query("DELETE FROM {$tablepre}posts WHERE pid='$pid'");
		if($thread['special'] == 2) {
			$db->query("DELETE FROM {$tablepre}trades WHERE pid='$pid'");
		}

		if($isfirstpost) {
			$forumadd = 'threads=threads-\'1\', posts=posts-\'1\'';
			$tablearray = array('threadsmod','relatedthreads','threads','debates','debateposts','polloptions','polls','typeoptionvars');
			foreach ($tablearray as $table) {
				$db->query("DELETE FROM {$tablepre}$table WHERE tid='$tid'", 'UNBUFFERED');
			}
			if($globalstick && in_array($thread['displayorder'], array(2, 3))) {
				require_once DISCUZ_ROOT.'./include/cache.func.php';
				updatecache('globalstick');
			}
		} else {
			$savepostposition && $db->query("DELETE FROM {$tablepre}postposition WHERE pid='$pid'");
			$forumadd = 'posts=posts-\'1\'';
			$query = $db->query("SELECT author, dateline, anonymous FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' ORDER BY dateline DESC LIMIT 1");
			$lastpost = $db->fetch_array($query);
			$lastpost['author'] = !$lastpost['anonymous'] ? addslashes($lastpost['author']) : '';
			$db->query("UPDATE {$tablepre}threads SET replies=replies-'1', attachment='$thread_attachment', lastposter='$lastpost[author]', lastpost='$lastpost[dateline]' WHERE tid='$tid'", 'UNBUFFERED');
		}

		$forum['lastpost'] = explode("\t", $forum['lastpost']);
		if($orig['dateline'] == $forum['lastpost'][2] && ($orig['author'] == $forum['lastpost'][3] || ($forum['lastpost'][3] == '' && $orig['anonymous']))) {
			$lastthread = daddslashes($db->fetch_first("SELECT tid, subject, lastpost, lastposter FROM {$tablepre}threads
				WHERE fid='$fid' AND displayorder>='0' ORDER BY lastpost DESC LIMIT 1"), 1);
			$forumadd .= ", lastpost='$lastthread[tid]\t$lastthread[subject]\t$lastthread[lastpost]\t$lastthread[lastposter]'";
		}

		$db->query("UPDATE {$tablepre}forums SET $forumadd WHERE fid='$fid'", 'UNBUFFERED');

	}

	if($specialextra) {

		@include_once DISCUZ_ROOT.'./plugins/'.$threadplugins[$specialextra]['module'].'.class.php';
		$classname = 'threadplugin_'.$specialextra;
		if(method_exists($classname, 'editpost_submit_end')) {
			$threadpluginclass = new $classname;
			$threadpluginclass->editpost_submit_end($fid, $tid);
		}

	}

	// debug: update thread caches ?
	if($forum['threadcaches']) {
		if($isfirstpost || $page == 1 || $thread['replies'] < $_DCACHE['pospperpage'] || !empty($delete)) {
			$forum['threadcaches'] && deletethreadcaches($tid);
		} else {
			if($db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$tid' AND pid<'$pid'") < $_DCACHE['settings']['postperpage']) {
				$forum['threadcaches'] && deletethreadcaches($tid);
			}
		}
	}

	if($auditstatuson) {
		if($audit == 1) {
			showmessage('auditstatuson_succeed', $redirecturl);
		} else {
			showmessage('audit_edit_succeed');
		}
	} else {
		if(!empty($delete) && $isfirstpost) {
			showmessage('post_edit_delete_succeed', "forumdisplay.php?fid=$fid");
		} elseif(!empty($delete)) {
			showmessage('post_edit_delete_succeed', "viewthread.php?tid=$tid&page=$page&extra=$extra".($vid && $isfirstpost ? "&vid=$vid" : ''));
		} else {
			if($isfirstpost && $modnewthreads) {
				showmessage('edit_newthread_mod_succeed', "forumdisplay.php?fid=$fid");
			} elseif(!$isfirstpost && $modnewreplies) {
				showmessage('edit_reply_mod_succeed', "forumdisplay.php?fid=$fid");
			} else {
				showmessage('post_edit_succeed', $redirecturl);
			}
		}
	}

}

?>