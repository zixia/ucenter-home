<?

/*
	[Discuz!] (C)2001-2009 Comsenz Inc. & (C)2009 DPS LuciferSheng
	This is NOT a freeware, use is subject to license terms

	$Id: postawards.inc.php 21306 2009-11-26 00:56:50Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$tid){
	showmessage("dps_postawards:wrong_request");
}


if(!@include_once DISCUZ_ROOT.'./forumdata/cache/cache_postawards_setting.php'){
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	$query = $db->query("SELECT data FROM {$tablepre}caches WHERE cachename='postawards'");
	$data = $db->fetch_array($query);
	writetocache('postawards_setting', '', $data['data']);
}

$allow = $PACACHE['userright'][$groupid];

if(!$allow['postawards'] || !$adminid){
	showmessage('group_nopermission', NULL, 'NOPERM');
}

if(!$allow['systemcredit']){
	$selfcreditmode = TRUE;
}

if(!$allow['ratemode']){
	$ratemode = FALSE;
}

$replycount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$tid'");

if(!$replycount) {
	showmessage('dps_postawards:msg_no_reply');
}

if($awardsubmit) {

	if(!$awardpost) {
		showmessage('dps_postawards:msg_awardpost_empty');
	}

	if(!empty($allow['ratelowlimit']) && $credit < $allow['ratelowlimit']) {
		showmessage('dps_postawards:msg_credit_over_limit');
	}


	if(!empty($allow['ratehighlimit']) && $credit > $allow['ratehighlimit']) {
		showmessage('dps_postawards:msg_credit_over_limit');
	}

	$posts = explode(",", $awardpost);
	for($i=0; $i<count($posts); $i++) {
		if(strpos($posts[$i],"-")) {
			$posts2 = explode("-", $posts[$i]);
			if(intval($posts2[0]) && intval($posts2[1])){
				for($j=$posts2[0]; $j<=$posts2[1]; $j++){
					$awardlist[] = $j-1;
				}
			}
		}
		if(strstr($posts[$i],'?')) {
			preg_match_all('/\?/',$posts[$i],$qcount);
 			$qcounts = count($qcount[0]);
			for($j=0;$j<10;$j++){
				if($qcounts == 1) {
					 $awardlist[] = preg_replace('/\?/',$j,$posts[$i],1)-1;
				} else {
					 $b[] = preg_replace('/\?/',$j,$posts[$i],1);
				}
				for($k=0;$k<10;$k++) {
					if($qcounts == 2) {
						$awardlist[] = preg_replace('/\?/',$k,$b[$j],1)-1;
					} else {
						$c[] = preg_replace('/\?/',$k,$b[$j],1);
					}
					for($l=0;$l<10;$l++) {
						if($qcounts == 3) {
							$awardlist[] = preg_replace('/\?/',$l,$c[$k],1)-1;
						} else {
							$d[] = preg_replace('/\?/',$l,$c[$k],1);
						}
					}
				}
			}
		} else {
			if(intval($posts[$i])) $awardlist[] = $posts[$i]-1;
		}
	}
	$allpost = range(0, $replycount);
	$awardlist = array_intersect ($awardlist, $allpost);
	$awardlist = array_unique($awardlist);
	sort($awardlist);

	if(!$awardlist) {
		showmessage('dps_postawards:msg_awardpost_error');
	}
	$awardlistmax = max($awardlist) + 1;

	if($awardlistmax > $replycount) {
		showmessage('dps_postawards:msg_awardpost_overrange');
	}

	$credit = intval($credit);
	$credittype = intval($credittype);
	if($credittype < 1 || $credittype > 8) {
		showmessage('dps_postawards:msg_credittype_error');
	}

	if(!$credittype || !$credit) {
		showmessage('dps_postawards:msg_parameter_empty');
	}

	if($rate_msg) {
		require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
		$rate_msg = censor(trim($rate_msg));
		$rate_msg = cutstr(dhtmlspecialchars($rate_msg), 40);
	}

	if($ratemode && !$rate_msg) {
		showmessage('dps_postawards:msg_ratereason_empty');
	}

	require_once DISCUZ_ROOT.'./include/forum.func.php';
	$query = $db->query("SELECT status, authorid FROM {$tablepre}threads WHERE tid='$tid'");
	$thread = $db->fetch_array($query);
	$rushreply = getstatus($thread['status'], 3);

	if($rushreply) {
		for($i=0;$i<count($awardlist);$i++) {
			$position[$i] = $awardlist[$i]+1;
		}
		$position72 = implodeids($position);

		$query = $db->query("SELECT * FROM {$tablepre}postposition WHERE tid='$tid' AND position IN($position72)");
		while($result = $db->fetch_array($query)) {
			$awardpid[] = $result['pid'];
		}

		$pidlist72 = implodeids($awardpid);
		$query = $db->query("SELECT authorid FROM {$tablepre}posts WHERE tid='$tid' AND pid IN($pidlist72)");
		while($result = $db->fetch_array($query)) {
			$awarduid[] = $result['authorid'];
		}
	} else {
		$query = $db->query("SELECT pid, authorid FROM {$tablepre}posts WHERE tid='$tid' ORDER BY dateline LIMIT 0, $awardlistmax");
		while($result = $db->fetch_array($query)) {
			$awarduid[] = $result['authorid'];
			$awardpid[] = $result['pid'];
		}
	}

	$awardplist = array_intersect_key($awardpid,array_flip($awardlist));
	$awardulist = array_unique(array_intersect_key($awarduid,array_flip($awardlist)));
	$awardulist = array_diff($awardulist, array(0 => $discuz_uid));
	$pidcount = count($awardplist);

	$uidlist = implode("','", $awardulist);
	$extcredit = "extcredits".$credittype;

	//check credit limit
	if(!empty($allow['ratealllimit']) && abs($credit * count($awardulist)) > $allow['ratealllimit']) {
		showmessage('dps_postawards:msg_credit_all_limit');
	}

	// if use user's credit, updatecredits
	if($selfcreditmode) {
		$usercredit = $db->result_first("SELECT $extcredit FROM {$tablepre}members WHERE uid='$discuz_uid'");
		if($usercredit < $credit * count($awardulist)) {
			showmessage('dps_postawards:msg_over_user_credit');
		}
		$updatecredits[$credittype]= -1 * abs($credit) * count($awardulist);
		updatecredits($discuz_uid, $updatecredits);
	}

	updatecredits($uidlist, array($credittype => $credit));

	if($ratemode){
		if(!$raterange) {
			showmessage('group_nopermission', NULL, 'NOPERM');
		} elseif ($modratelimit && $adminid == 3 && !$forum['ismoderator']) {
			showmessage('thread_rate_moderator_invalid', NULL, 'HALTED');
		}
		foreach($raterange as $id => $rating) {
			$maxratetoday[$id] = $rating['mrpd'];
		}

		$query = $db->query("SELECT extcredits, SUM(ABS(score)) AS todayrate FROM {$tablepre}ratelog
			WHERE uid='$discuz_uid' AND dateline>=$timestamp-86400
			GROUP BY extcredits");
		while($rate = $db->fetch_array($query)) {
			$maxratetoday[$rate['extcredits']] = $raterange[$rate['extcredits']]['mrpd'] - $rate['todayrate'];
		}
		if($pidcount * $credit > $maxratetoday[$credittype]){
			showmessage('thread_rate_range_invalid');
		}

		$plist = implodeids($awardplist);

		$del = array();
		$query = $db->query("SELECT pid, authorid, status, dateline, tid, anonymous FROM {$tablepre}posts WHERE pid IN($plist) AND invisible='0' AND authorid<>'0'");
		while($post = $db->fetch_array($query)){
			if(!$post || $post['tid'] != $tid || !$post['authorid']) {
				showmessage('undefined_action', NULL, 'HALTED');
			} elseif(!$forum['ismoderator'] && $karmaratelimit && $timestamp - $post['dateline'] > $karmaratelimit * 3600) {
				showmessage('thread_rate_timelimit', NULL, 'HALTED');
			} elseif($post['authorid'] == $discuz_uid || $post['anonymous'] || $post['status'] & 1) {
				$del[] = $post['pid'];
			}
			$p[] = $post;
		}

		$alist = array_diff($awardplist, $del);
		$plist = implodeids($alist);
		$ratetimes = ceil($credit / 5);
		$db->query("UPDATE {$tablepre}posts SET rate=rate+($credit), ratetimes=ratetimes+$ratetimes WHERE pid IN($plist)");
		foreach($alist as $id => $aquery) {
			$db->query("INSERT INTO {$tablepre}ratelog (pid, uid, username, extcredits, dateline, score, reason)
				VALUES ('$aquery', '$discuz_uid', '$discuz_user', '$credittype', '$timestamp', '$credit', '$rate_msg')", 'UNBUFFERED');
		}
	}

	if($sendmsg){
		$thread = $db->fetch_first("SELECT tid, subject FROM {$tablepre}posts WHERE tid='$tid' AND first='1'");
		$awardmsg = "$credit ".$extcredits[$credittype]['title'];
		eval("\$message = addslashes(\"".$scriptlang['dps_postawards']['pm_message']."\");");
		foreach(array_unique($awardulist) as $user){
			sendnotice($user, $message, 'systempm', 0, array(), 0);
		}
	}
	showmessage('dps_postawards:success', dreferer());
}

$creditselect = '<select name="credittype">';
foreach($extcredits as $id => $credit) {
	$creditselect .= '<option value="'.$id.'">'.$credit['title'].'</option>';
}
$creditselect .= '</select>';

include template('dps_postawards:postawards');

?>