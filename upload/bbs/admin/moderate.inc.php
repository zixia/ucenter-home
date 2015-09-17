<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: moderate.inc.php 20913 2009-10-29 08:51:23Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

cpheader();

if($operation == 'members') {

	$do = empty($do) ? 'mod' : $do;
	if($do == 'mod') {

		if(!submitcheck('modsubmit')) {

			$query = $db->query("SELECT status, COUNT(*) AS count FROM {$tablepre}validating GROUP BY status");
			while($num = $db->fetch_array($query)) {
				$count[$num['status']] = $num['count'];
			}

			$sendemail = isset($sendemail) ? $sendemail : 1;
			$checksendemail = $sendemail ? 'checked' : '';

			$page = max(1, intval($page));
			$start_limit = ($page - 1) * $memberperpage;

			$query = $db->query("SELECT COUNT(*) FROM {$tablepre}validating WHERE status='0'");
			$multipage = multi($db->result($query, 0), $memberperpage, $page, $BASESCRIPT.'?action=moderate&operation=members&sendemail=$sendemail');

			$vuids = '0';
			$members = '';
			$query = $db->query("SELECT m.uid, m.username, m.groupid, m.email, m.regdate, m.regip, v.message, v.submittimes, v.submitdate, v.moddate, v.admin, v.remark
				FROM {$tablepre}validating v, {$tablepre}members m
				WHERE v.status='0' AND m.uid=v.uid ORDER BY v.submitdate DESC LIMIT $start_limit, $memberperpage");
			while($member = $db->fetch_array($query)) {
				if($member['groupid'] != 8) {
					$vuids .= ','.$member['uid'];
					continue;
				}
				$member['regdate'] = gmdate("$dateformat $timeformat", $member['regdate'] + $timeoffset * 3600);
				$member['submitdate'] = gmdate("$dateformat $timeformat", $member['submitdate'] + $timeoffset * 3600);
				$member['moddate'] = $member['moddate'] ? gmdate("$dateformat $timeformat", $member['moddate'] + $timeoffset * 3600) : $lang['none'];
				$member['admin'] = $member['admin'] ? "<a href=\"space.php?username=".rawurlencode($member['admin'])."\" target=\"_blank\">$member[admin]</a>" : $lang['none'];
				$members .= "<tr class=\"smalltxt\"><td><input class=\"radio\" type=\"radio\" name=\"mod[$member[uid]]\" value=\"invalidate\"> $lang[invalidate]<br /><input class=\"radio\" type=\"radio\" name=\"mod[$member[uid]]\" value=\"validate\" checked> $lang[validate]<br />\n".
					"<input class=\"radio\" type=\"radio\" name=\"mod[$member[uid]]\" value=\"delete\"> $lang[delete]<br /><input class=\"radio\" type=\"radio\" name=\"mod[$member[uid]]\" value=\"ignore\"> $lang[ignore]</td><td><b><a href=\"space.php?uid=$member[uid]\" target=\"_blank\">$member[username]</a></b>\n".
					"<br />$lang[members_edit_regdate] $member[regdate]<br />$lang[members_edit_regip] $member[regip]<br />Email: $member[email]</td>\n".
					"<td align=\"center\"><textarea rows=\"4\" name=\"remark[$member[uid]]\" style=\"width: 95%; word-break: break-all\">$member[message]</textarea></td>\n".
					"<td>$lang[moderate_members_submit_times]: $member[submittimes]<br />$lang[moderate_members_submit_time]: $member[submitdate]<br />$lang[moderate_members_admin]: $member[admin]<br />\n".
					"$lang[moderate_members_mod_time]: $member[moddate]</td><td><textarea rows=\"4\" name=\"remark[$member[uid]]\" style=\"width: 95%; word-break: break-all\">$member[remark]</textarea></td></tr>\n";
			}

			if($vuids) {
				$db->query("DELETE FROM {$tablepre}validating WHERE uid IN ($vuids)", 'UNBUFFERED');
			}

			shownav('user', 'nav_modmembers');
			showsubmenu('nav_moderate_users', array(
				array('nav_moderate_users_mod', 'moderate&operation=members&do=mod', 1),
				array('clean', 'moderate&operation=members&do=del', 0)
			));
			showtips('moderate_members_tips');
			showformheader('moderate&operation=members&do=mod');
			showtableheader('moderate_members', 'fixpadding');
			showsubtitle(array('operation', 'members_edit_info', 'moderate_members_message', 'moderate_members_info', 'moderate_members_remark'));
			echo $members;
			showsubmit('modsubmit', 'submit', '', '<a href="#all" onclick="checkAll(\'option\', $(\'cpform\'), \'invalidate\')">'.lang('moderate_all_invalidate').'</a> &nbsp;<a href="#all" onclick="checkAll(\'option\', $(\'cpform\'), \'validate\')">'.lang('moderate_all_validate').'</a> &nbsp;<a href="#all" onclick="checkAll(\'option\', $(\'cpform\'), \'delete\')">'.lang('moderate_all_delete').'</a> &nbsp;<a href="#all" onclick="checkAll(\'option\', $(\'cpform\'), \'ignore\')">'.lang('moderate_all_ignore').'</a> &nbsp;<input class="checkbox" type="checkbox" name="sendemail" id="sendemail" value="1" '.$checksendemail.' /><label for="sendemail"> '.lang('moderate_members_email').'</label>', $multipage);
			showtablefooter();
			showformfooter();

		} else {

			$moderation = array('invalidate' => array(), 'validate' => array(), 'delete' => array(), 'ignore' => array());

			$uids = 0;
			if(is_array($mod)) {
				foreach($mod as $uid => $act) {
					$uid = intval($uid);
					$uids .= ','.$uid;
					$moderation[$act][] = $uid;
				}
			}

			$members = array();
			$uidarray = array(0);
			$query = $db->query("SELECT v.*, m.uid, m.username, m.email, m.regdate FROM {$tablepre}validating v, {$tablepre}members m
				WHERE v.uid IN ($uids) AND m.uid=v.uid AND m.groupid='8'");
			while($member = $db->fetch_array($query)) {
				$members[$member['uid']] = $member;
				$uidarray[] = $member['uid'];
			}

			$uids = implode(',', $uidarray);
			$numdeleted = $numinvalidated = $numvalidated = 0;

			if(!empty($moderation['delete']) && is_array($moderation['delete'])) {
				$deleteuids = '\''.implode('\',\'', $moderation['delete']).'\'';
				$db->query("DELETE FROM {$tablepre}members WHERE uid IN ($deleteuids) AND uid IN ($uids)");
				$numdeleted = $db->affected_rows();

				$db->query("DELETE FROM {$tablepre}memberfields WHERE uid IN ($deleteuids) AND uid IN ($uids)");
				$db->query("DELETE FROM {$tablepre}validating WHERE uid IN ($deleteuids) AND uid IN ($uids)");
			} else {
				$moderation['delete'] = array();
			}

			if(!empty($moderation['validate']) && is_array($moderation['validate'])) {
				$newgroupid = $db->result_first("SELECT groupid FROM {$tablepre}usergroups WHERE creditshigher<=0 AND 0<creditslower LIMIT 1");
				$validateuids = '\''.implode('\',\'', $moderation['validate']).'\'';
				$db->query("UPDATE {$tablepre}members SET adminid='0', groupid='$newgroupid' WHERE uid IN ($validateuids) AND uid IN ($uids)");
				$numvalidated = $db->affected_rows();

				$db->query("DELETE FROM {$tablepre}validating WHERE uid IN ($validateuids) AND uid IN ($uids)");
			} else {
				$moderation['validate'] = array();
			}

			if(!empty($moderation['invalidate']) && is_array($moderation['invalidate'])) {
				foreach($moderation['invalidate'] as $uid) {
					$numinvalidated++;
					$db->query("UPDATE {$tablepre}validating SET moddate='$timestamp', admin='$discuz_user', status='1', remark='".dhtmlspecialchars($remark[$uid])."' WHERE uid='$uid' AND uid IN ($uids)");
				}
			} else {
				$moderation['invalidate'] = array();
			}

			if($sendemail) {
				foreach(array('delete', 'validate', 'invalidate') as $o) {
					foreach($moderation[$o] as $uid) {
						if(isset($members[$uid])) {
							$member = $members[$uid];
							$member['regdate'] = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $member['regdate'] + $_DCACHE['settings']['timeoffset'] * 3600);
							$member['submitdate'] = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $member['submitdate'] + $_DCACHE['settings']['timeoffset'] * 3600);
							$member['moddate'] = gmdate($_DCACHE['settings']['dateformat'].' '.$_DCACHE['settings']['timeformat'], $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);
							$member['operation'] = $o;
							$member['remark'] = $remark[$uid] ? dhtmlspecialchars($remark[$uid]) : $lang['none'];

							sendmail("$member[username] <$member[email]>", 'moderate_member_subject', 'moderate_member_message');
						}
					}
				}
			}

			cpmsg('moderate_members_succeed', "$BASESCRIPT?action=moderate&operation=members&page=$page", 'succeed');

		}


	} elseif($do == 'del') {


		if(!submitcheck('prunesubmit', 1)) {

			shownav('user', 'nav_modmembers');
			showsubmenu('nav_moderate_users', array(
				array('nav_moderate_users_mod', 'moderate&operation=members&do=mod', 0),
				array('clean', 'moderate&operation=members&do=del', 1)
			));
			showtips('moderate_members_tips');
			showformheader('moderate&operation=members&do=del');
			showtableheader('moderate_members_prune');
			showsetting('moderate_members_prune_submitmore', 'submitmore', '5', 'text');
			showsetting('moderate_members_prune_regbefore', 'regbefore', '30', 'text');
			showsetting('moderate_members_prune_modbefore', 'modbefore', '15', 'text');
			showsetting('moderate_members_prune_regip', 'regip', '', 'text');
			showsubmit('prunesubmit');
			showtablefooter();
			showformfooter();

		} else {

			$sql = '1';
			$sql .= $submitmore ? " AND v.submittimes>'$submitmore'" : '';
			$sql .= $regbefore ? " AND m.regdate<'".($timestamp - $regbefore * 86400)."'" : '';
			$sql .= $modbefore ? " AND v.moddate<'".($timestamp - $modbefore * 86400)."'" : '';
			$sql .= $regip ? " AND m.regip LIKE '$regip%'" : '';

			$query = $db->query("SELECT v.uid FROM {$tablepre}validating v, {$tablepre}members m
				WHERE $sql AND m.uid=v.uid AND m.groupid='8'");

			if(!$membernum = $db->num_rows($query)) {
				cpmsg('members_search_noresults', '', 'error');
			} elseif(!$confirmed) {
				cpmsg('members_delete_confirm', "$BASESCRIPT?action=moderate&operation=members&do=del&submitmore=".rawurlencode($submitmore)."&regbefore=".rawurlencode($regbefore)."&regip=".rawurlencode($regip)."&prunesubmit=yes", 'form');
			} else {
				$uids = 0;
				while($member = $db->fetch_array($query)) {
					$uids .= ','.$member['uid'];
				}

				$db->query("DELETE FROM {$tablepre}members WHERE uid IN ($uids)");
				$numdeleted = $db->affected_rows();

				$db->query("DELETE FROM {$tablepre}memberfields WHERE uid IN ($uids)");
				$db->query("DELETE FROM {$tablepre}validating WHERE uid IN ($uids)");

				cpmsg('members_delete_succeed', '', 'succeed');
			}

		}
	}

} else {

	require_once DISCUZ_ROOT.'./include/forum.func.php';
	require_once DISCUZ_ROOT.'./include/post.func.php';

	$modfid = !empty($modfid) ? intval($modfid) : 0;

	$fids = 0;
	$recyclebins = $forumlist = array();
	if($adminid == 3) {
		$query = $db->query("SELECT m.fid, f.name, f.recyclebin FROM {$tablepre}moderators m LEFT JOIN {$tablepre}forums f ON f.fid=m.fid  WHERE m.uid='$discuz_uid'");
		while($forum = $db->fetch_array($query)) {
			$fids .= ','.$forum['fid'];
			$recyclebins[$forum['fid']] = $forum['recyclebin'];
			$forumlist[$forum['fid']] = strip_tags($forum['name']);
		}

		if(empty($forumlist)) {
			cpmsg('moderate_posts_no_access_all', '', 'error');
		} elseif($modfid && empty($forumlist[$modfid])) {
			cpmsg('moderate_posts_no_access_this', '', 'error');
		}

	} else {
		$query = $db->query("SELECT fid, name, recyclebin FROM {$tablepre}forums WHERE status='1' AND type<>'group'");
		while($forum = $db->fetch_array($query)) {
			$recyclebins[$forum['fid']] = $forum['recyclebin'];
			$forumlist[$forum['fid']] = $forum['name'];
		}
	}

	if($modfid) {
		$fidadd = array('fids' => "fid='$modfid'", 'and' => ' AND ', 't' => 't.', 'p' => 'p.');
	} else {
		$fidadd = $fids ? array('fids' => "fid IN ($fids)", 'and' => ' AND ', 't' => 't.', 'p' => 'p.') : array();
	}

	if(isset($filter) && $filter == 'ignore') {
		$displayorder = -3;
		$filteroptions = '<option value="normal">'.$lang['moderate_none'].'</option><option value="ignore" selected>'.$lang['moderate_ignore'].'</option>';
	} else {
		$displayorder = -2;
		$filter = 'normal';
		$filteroptions = '<option value="normal" selected>'.$lang['moderate_none'].'</option><option value="ignore">'.$lang['moderate_ignore'].'</option>';
	}

	$forumoptions = '<option value="all"'.(empty($modfid) ? ' selected' : '').'>'.$lang['moderate_all_fields'].'</option>';
	foreach($forumlist as $fid => $forumname) {
		$selected = $modfid == $fid ? ' selected' : '';
		$forumoptions .= '<option value="'.$fid.'" '.$selected.'>'.$forumname.'</option>'."\n";
	}

	require_once DISCUZ_ROOT.'./include/misc.func.php';
	$modreasonoptions = '<option value="">'.$lang['none'].'</option><option value="">--------</option>'.modreasonselect(1);

	echo <<<EOT
<style type="text/css">
	.mod_validate td{ background: #FFFFFF !important; }
	.mod_delete td{	background: #FFEBE7 !important; }
	.mod_ignore td{	background: #EEEEEE !important; }
</style>
<script type="text/JavaScript">
	function mod_setbg(tid, value) {
		$('mod_' + tid + '_row1').className = 'mod_' + value;
		$('mod_' + tid + '_row2').className = 'mod_' + value;
		$('mod_' + tid + '_row3').className = 'mod_' + value;
	}
	function mod_setbg_all(value) {
		checkAll('option', $('cpform'), value);
		var trs = $('cpform').getElementsByTagName('TR');
		for(var i in trs) {
			if(trs[i].id && trs[i].id.substr(0, 4) == 'mod_') {
				trs[i].className = 'mod_' + value;
			}
		}
	}
	function attachimg() {}
</script>
EOT;

}

if($operation == 'threads') {

	if(!submitcheck('modsubmit')) {

		require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

		$tpp = 10;
		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $tpp;

		$modcount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE $fidadd[fids]$fidadd[and] displayorder='$displayorder'");
		$multipage = multi($modcount, $tpp, $page, "$BASESCRIPT?action=moderate&operation=threads&filter=$filter&modfid=$modfid");

		shownav('topic', $lang['moderate_threads']);
		showsubmenu('nav_moderate_posts', array(
			array('nav_moderate_threads', 'moderate&operation=threads', 1),
			array('nav_moderate_replies', 'moderate&operation=replies', 0)
		));
		showformheader("moderate&operation=threads&page=$page");
		showhiddenfields(array('ignore' => $ignore, 'filter' => $filter, 'modfid' => $modfid));
		showtableheader("$lang[select]: <select style=\"margin: 0px;\" onchange=\"if(this.options[this.selectedIndex].value != '') {window.location='$BASESCRIPT?action=moderate&operation=threads&modfid=$modfid&filter='+this.options[this.selectedIndex].value;}\">$filteroptions</select>
		<select style=\"margin: 0px;\" onchange=\"if(this.options[this.selectedIndex].value != '') {window.location='$BASESCRIPT?action=moderate&operation=threads&filter=$filter&modfid='+this.options[this.selectedIndex].value;}\">$forumoptions</select>");

		$query = $db->query("SELECT f.name AS forumname, f.allowsmilies, f.allowhtml, f.allowbbcode, f.allowimgcode,
				t.tid, t.fid, t.sortid, t.author, t.authorid, t.subject, t.dateline, t.attachment,
				p.pid, p.message, p.useip, p.attachment, p.htmlon, p.smileyoff, p.bbcodeoff
				FROM {$tablepre}threads t
				LEFT JOIN {$tablepre}posts p ON p.tid=t.tid AND p.first=1
				LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
				WHERE $fidadd[t]$fidadd[fids]$fidadd[and] t.displayorder='$displayorder'
				ORDER BY t.dateline DESC LIMIT $start_limit, $tpp");

		while($thread = $db->fetch_array($query)) {
			$threadsortinfo = '';
			if($thread['authorid'] && $thread['author']) {
				$thread['author'] = "<a href=\"space.php?uid=$thread[authorid]\" target=\"_blank\">$thread[author]</a>";
			} elseif($thread['authorid'] && !$thread['author']) {
				$thread['author'] = "<a href=\"space.php?uid=$thread[authorid]\" target=\"_blank\">$lang[anonymous]</a>";
			} else {
				$thread['author'] = $lang['guest'];
			}

			$thread['dateline'] = gmdate("$dateformat $timeformat", $thread['dateline'] + $timeoffset * 3600);
			$thread['message'] = discuzcode($thread['message'], $thread['smileyoff'], $thread['bbcodeoff'], sprintf('%00b', $thread['htmlon']), $thread['allowsmilies'], $thread['allowbbcode'], $thread['allowimgcode'], $thread['allowhtml']);

			$thread['modthreadkey'] = modthreadkey($thread['tid']);

			if($thread['attachment']) {
				require_once DISCUZ_ROOT.'./include/attachment.func.php';

				$queryattach = $db->query("SELECT aid, filename, filetype, filesize, attachment, isimage, remote FROM {$tablepre}attachments WHERE tid='$thread[tid]'");
				while($attach = $db->fetch_array($queryattach)) {
					$attachurl = $attach['remote'] ? $ftp['attachurl'] : $attachurl;
					$attach['url'] = $attach['isimage']
							? " $attach[filename] (".sizecount($attach['filesize']).")<br /><br /><img src=\"$attachurl/$attach[attachment]\" onload=\"if(this.width > 400) {this.resized=true; this.width=400;}\">"
							 : "<a href=\"$attachurl/$attach[attachment]\" target=\"_blank\">$attach[filename]</a> (".sizecount($attach['filesize']).")";
					$thread['message'] .= "<br /><br />$lang[attachment]: ".attachtype(fileext($thread['filename'])."\t".$attach['filetype']).$attach['url'];
				}
			}

			$optiondata = $optionlist = array();
			if($thread['sortid']) {
				if(@include DISCUZ_ROOT.'./forumdata/cache/threadsort_'.$thread['sortid'].'.php') {
					$sortquery = $db->query("SELECT optionid, value FROM {$tablepre}typeoptionvars WHERE tid='$thread[tid]'");
					while($option = $db->fetch_array($sortquery)) {
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
						} elseif($_DTYPE[$optionid]['type'] == 'textarea') {
							$optionlist[$option['identifier']]['value'] = $optiondata[$optionid] ? nl2br($optiondata[$optionid]) : '';
						} else {
							$optionlist[$option['identifier']]['value'] = $optiondata[$optionid];
						}
					}
				}

				foreach($optionlist as $option) {
					$threadsortinfo .= $option['title'].' '.$option['value']."<br />";
				}
			}

			showtablerow("id=\"mod_$thread[tid]_row1\"", array('rowspan="3" class="rowform threadopt" style="width:80px;"', 'class="threadtitle"'), array(
				"<ul class=\"nofloat\"><li><input class=\"radio\" type=\"radio\" name=\"mod[$thread[tid]]\" id=\"mod_$thread[tid]_1\" value=\"validate\" checked=\"checked\" onclick=\"mod_setbg($thread[tid], 'validate');\"><label for=\"mod_$thread[tid]_1\">$lang[validate]</label></li><li><input class=\"radio\" type=\"radio\" name=\"mod[$thread[tid]]\" id=\"mod_$thread[tid]_2\" value=\"delete\" onclick=\"mod_setbg($thread[tid], 'delete');\"><label for=\"mod_$thread[tid]_2\">$lang[delete]</label></li><li><input class=\"radio\" type=\"radio\" name=\"mod[$thread[tid]]\" id=\"mod_$thread[tid]_3\" value=\"ignore\" onclick=\"mod_setbg($thread[tid], 'ignore');\"><label for=\"mod_$thread[tid]_3\">$lang[ignore]</label></li></ul>",
				"<h3><a href=\"forumdisplay.php?fid=$thread[fid]\" target=\"_blank\">$thread[forumname]</a> &raquo; <a href=\"viewthread.php?tid=$thread[tid]&modthreadkey=$thread[modthreadkey]\" target=\"_blank\">$thread[subject]</a></h3><p><span class=\"bold\">$lang[author]:</span> $thread[author] ($thread[useip]) &nbsp;&nbsp; <span class=\"bold\">$lang[time]:</span> $thread[dateline]</p>"
			));
			showtablerow("id=\"mod_$thread[tid]_row2\"", 'colspan="2" style="padding: 10px; line-height: 180%;"', '<div style="overflow: auto; overflow-x: hidden; max-height:120px; height:auto !important; height:120px; word-break: break-all;">'.$thread['message'].'<br /><br />'.$threadsortinfo.'</div>');
			showtablerow("id=\"mod_$thread[tid]_row3\"", 'class="threadopt threadtitle" colspan="2"', "<a href=\"post.php?action=edit&fid=$thread[fid]&tid=$thread[tid]&pid=$thread[pid]&page=1&modthreadkey=$thread[modthreadkey]\" target=\"_blank\">".$lang['moderate_edit_thread']."</a> &nbsp;&nbsp;|&nbsp;&nbsp; ".$lang['moderate_reasonpm']."&nbsp; <input type=\"text\" class=\"txt\" name=\"pm_$thread[tid]\" id=\"pm_$thread[tid]\" style=\"margin: 0px;\"> &nbsp; <select style=\"margin: 0px;\" onchange=\"$('pm_$thread[tid]').value=this.value\">$modreasonoptions</select>");
		}

		showsubmit('modsubmit', 'submit', '', '<a href="#all" onclick="mod_setbg_all(\'validate\')">'.lang('moderate_all_validate').'</a> &nbsp;<a href="#all" onclick="mod_setbg_all(\'delete\')">'.lang('moderate_all_delete').'</a> &nbsp;<a href="#all" onclick="mod_setbg_all(\'ignore\')">'.lang('moderate_all_ignore').'</a>', $multipage);
		showtablefooter();
		showformfooter();

	} else {

		$validates = $ignores = $recycles = $deletes = 0;
		$validatedthreads = $pmlist = array();
		$moderation = array('validate' => array(), 'delete' => array(), 'ignore' => array());

		if(is_array($mod)) {
			foreach($mod as $tid => $act) {
				$moderation[$act][] = intval($tid);
			}
		}

		if($moderation['ignore']) {
			$ignoretids = '\''.implode('\',\'', $moderation['ignore']).'\'';
			$db->query("UPDATE {$tablepre}threads SET displayorder='-3' WHERE tid IN ($ignoretids) AND displayorder='-2'");
			$ignores = $db->affected_rows();
		}

		if($moderation['delete']) {
			$deletetids = '0';
			$recyclebintids = '0';
			$query = $db->query("SELECT tid, fid, authorid, subject FROM {$tablepre}threads WHERE tid IN ('".implode('\',\'', $moderation['delete'])."') AND displayorder='$displayorder' $fidadd[and]$fidadd[fids]");
			while($thread = $db->fetch_array($query)) {
				if($recyclebins[$thread['fid']]) {
					$recyclebintids .= ','.$thread['tid'];
				} else {
					$deletetids .= ','.$thread['tid'];
				}
				$pm = 'pm_'.$thread['tid'];
				if(isset($$pm) && $$pm <> '' && $thread['authorid']) {
					$pmlist[] = array(
						'action' => 'modthreads_delete',
						'authorid' => $thread['authorid'],
						'thread' =>  $thread['subject'],
						'reason' => dhtmlspecialchars($$pm)
					);
				}
			}

			if($recyclebintids) {
				$db->query("UPDATE {$tablepre}threads SET displayorder='-1', moderated='1' WHERE tid IN ($recyclebintids)");
				$recycles = $db->affected_rows();
				updatemodworks('MOD', $recycles);

				$db->query("UPDATE {$tablepre}posts SET invisible='-1' WHERE tid IN ($recyclebintids)");
				updatemodlog($recyclebintids, 'DEL');
			}

			$query = $db->query("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE tid IN ($deletetids)");
			while($attach = $db->fetch_array($query)) {
				dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
			}

			$db->query("DELETE FROM {$tablepre}threads WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$deletes = $db->affected_rows();
			$db->query("DELETE FROM {$tablepre}posts WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}polloptions WHERE tid IN ($deletetids)");
			$db->query("DELETE FROM {$tablepre}polls WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}trades WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}attachments WHERE tid IN ($deletetids)", 'UNBUFFERED');
			$db->query("DELETE FROM {$tablepre}attachmentfields WHERE tid IN ($deletetids)", 'UNBUFFERED');
		}

		if($moderation['validate']) {

			$forums = array();
			$validatetids = '\''.implode('\',\'', $moderation['validate']).'\'';

			$tids = $comma = $comma2 = '';
			$authoridarray = $moderatedthread = array();
			$query = $db->query("SELECT t.fid, t.tid, t.authorid, t.subject, t.author, t.dateline, ff.postcredits FROM {$tablepre}threads t
				LEFT JOIN {$tablepre}forumfields ff USING(fid)
				WHERE t.tid IN ($validatetids) AND t.displayorder='$displayorder' $fidadd[and]$fidadd[t]$fidadd[fids]");
			while($thread = $db->fetch_array($query)) {
				$tids .= $comma.$thread['tid'];
				$comma = ',';
				if($thread['postcredits']) {
					updatepostcredits('+', $thread['authorid'], unserialize($thread['postcredits']));
				} else {
					$authoridarray[] = $thread['authorid'];
				}
				$forums[] = $thread['fid'];
				$validatedthreads[] = $thread;

				$pm = 'pm_'.$thread['tid'];
				if(isset($$pm) && $$pm <> '' && $thread['authorid']) {
					$pmlist[] = array(
							'action' => 'modthreads_validate',
							'authorid' => $thread['authorid'],
							'tid' => $thread['tid'],
							'thread' => $thread['subject'],
							'reason' => dhtmlspecialchars($$pm)
							);
				}
			}

			if($tids) {

				if($authoridarray) {
					updatepostcredits('+', $authoridarray, $creditspolicy['post']);
				}

				$db->query("UPDATE {$tablepre}posts SET invisible='0' WHERE tid IN ($tids)");
				$db->query("UPDATE {$tablepre}threads SET displayorder='0', moderated='1' WHERE tid IN ($tids)");
				$validates = $db->affected_rows();

				foreach(array_unique($forums) as $fid) {
					updateforumcount($fid);
				}

				updatemodworks('MOD', $validates);
				updatemodlog($tids, 'MOD');

			}
		}

		if($pmlist) {
			foreach($pmlist as $pm) {
				$reason = $pm['reason'];
				$threadsubject = $pm['thread'];
				$tid = intval($pm['tid']);
				sendnotice($pm['authorid'], $pm['action'], 'systempm');
			}
		}

		if($validates) {
			eval("\$lang[moderate_validate_list] = \"".$lang['moderate_validate_list']."\";");
			showsubmenu('nav_moderate_posts', array(
				array('nav_moderate_threads', 'moderate&operation=threads', 0),
				array('nav_moderate_replies', 'moderate&operation=replies', 0)
			));
			echo '<form id="topicadmin" name="topicadmin" method="post" action="topicadmin.php" target="_blank">';
			showhiddenfields(array('action'=> '', 'fid'=> '', 'tid'=> ''));
			showtableheader();
			showtablerow('', 'class="lineheight" colspan="5"', lang('moderate_validate_list'));
			showsubtitle(array('Tid', 'subject', 'author', 'dateline', 'admin'));

			if(!empty($validatedthreads)) {
				foreach($validatedthreads as $thread) {
					showtablerow('', '', array(
						$thread['tid'],
						'<a href="viewthread.php?tid='.$thread['tid'].'&modthreadkey='.modthreadkey($thread['tid']).'" target="_blank">'.$thread['subject'].'</a>',
						'<a href="space.php?uid='.$thread['authorid'].'" target="_blank">'.$thread['author'].'</a>',
						gmdate("$dateformat $timeformat", $thread['dateline'] + 3600 * $timeoffset),
						'<select name="action2" id="action2" onchange="if(this.options[this.selectedIndex].value != \'\') {$(\'topicadmin\').action.value= this.options[this.selectedIndex].value; $(\'topicadmin\').tid.value='.$thread['tid'].'; $(\'topicadmin\').fid.value='.$thread['fid'].'; $(\'topicadmin\').submit();}">
						<option value="" selected>'.$lang['select'].'</option>
						<option value="delete">'.$lang['moderate_delthread'].'</option>
						<option value="close">'.$lang['moderate_close'].'</option>
						<option value="move">'.$lang['moderate_move'].'</option>
						<option value="copy">'.$lang['moderate_copy'].'</option>
						<option value="highlight">'.$lang['moderate_highlight'].'</option>
						<option value="digest">'.$lang['moderate_digest'].'</option>
						<option value="stick">'.$lang['moderate_stick'].'</option>
						<option value="merge">'.$lang['moderate_merge'].'</option>
						<option value="bump">'.$lang['moderate_bump'].'</option>
						<option value="repair">'.$lang['moderate_repair'].'</option>
						</select>'
					));
				}
			}

			showtablefooter();
			showformfooter();
		} else {
			cpmsg('moderate_threads_succeed', $BASESCRIPT.'?action=moderate&operation=threads', 'succeed');
		}

	}

} elseif($operation == 'replies') {

	if(!submitcheck('modsubmit')) {

		require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
		$ppp = 10;
		$page = max(1, intval($page));
		$start_limit = ($page - 1) * $ppp;

		$modcount = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE invisible='$displayorder' AND first='0' $fidadd[and]$fidadd[fids]");
		$multipage = multi($modcount, $ppp, $page, "$BASESCRIPT?action=moderate&operation=replies&filter=$filter&modfid=$modfid");

		shownav('topic', $lang['moderate_replies']);
		showsubmenu('nav_moderate_posts', array(
			array('nav_moderate_threads', 'moderate&operation=threads', 0),
			array('nav_moderate_replies', 'moderate&operation=replies', 1)
		));

		showformheader("moderate&operation=replies&page=$page");
		showhiddenfields(array('filter' => $filter, 'modfid' => $modfid));
		showtableheader("$lang[select]: <select style=\"margin: 0px;\" onchange=\"if(this.options[this.selectedIndex].value != '') {window.location='$BASESCRIPT?action=moderate&operation=replies&modfid=$modfid&filter='+this.options[this.selectedIndex].value+'&amp;sid=5ScwCd';}\">$filteroptions</select> <select style=\"margin: 0px;\" onchange=\"if(this.options[this.selectedIndex].value != '') {window.location='$BASESCRIPT?action=moderate&operation=replies&filter=$filter&modfid='+this.options[this.selectedIndex].value+'&amp;sid=5ScwCd';}\">$forumoptions</select>");

		$query = $db->query("SELECT f.name AS forumname, f.allowsmilies, f.allowhtml, f.allowbbcode, f.allowimgcode,
			p.pid, p.fid, p.tid, p.author, p.authorid, p.subject, p.dateline, p.message, p.useip, p.attachment,
			p.htmlon, p.smileyoff, p.bbcodeoff, t.subject AS tsubject
			FROM {$tablepre}posts p
			LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
			LEFT JOIN {$tablepre}forums f ON f.fid=p.fid
			WHERE p.invisible='$displayorder' AND p.first='0' $fidadd[and]$fidadd[p]$fidadd[fids]
			ORDER BY p.dateline DESC LIMIT $start_limit, $ppp");

		while($post = $db->fetch_array($query)) {
			$post['dateline'] = gmdate("$dateformat $timeformat", $post['dateline'] + $timeoffset * 3600);
			$post['subject'] = $post['subject'] ? '<b>'.$post['subject'].'</b>' : '<i>'.$lang['nosubject'].'</i>';
			$post['message'] = discuzcode($post['message'], $post['smileyoff'], $post['bbcodeoff'], sprintf('%00b', $post['htmlon']), $post['allowsmilies'], $post['allowbbcode'], $post['allowimgcode'], $post['allowhtml']);
			$post['modthreadkey'] = modthreadkey($post['tid']);

			if($post['attachment']) {
				require_once DISCUZ_ROOT.'./include/attachment.func.php';

				$queryattach = $db->query("SELECT aid, filename, filetype, filesize, attachment, isimage, remote FROM {$tablepre}attachments WHERE pid='$post[pid]'");
				while($attach = $db->fetch_array($queryattach)) {
					$attachurl = $attach['remote'] ? $ftp['attachurl'] : $attachurl;
					$attach['url'] = $attach['isimage']
					 		? " $attach[filename] (".sizecount($attach['filesize']).")<br /><br /><img src=\"$attachurl/$attach[attachment]\" onload=\"if(this.width > 400) {this.resized=true; this.width=400;}\">"
						 	 : "<a href=\"$attachurl/$attach[attachment]\" target=\"_blank\">$attach[filename]</a> (".sizecount($attach['filesize']).")";
					$post['message'] .= "<br /><br />$lang[attachment]: ".attachtype(fileext($attach['filename'])."\t".$attach['filetype']).$attach['url'];
				}
			}

			showtablerow("id=\"mod_$post[pid]_row1\"", array('rowspan="3" class="rowform threadopt" style="width:80px;"', 'class="threadtitle"'), array(
				"<ul class=\"nofloat\"><li><input class=\"radio\" type=\"radio\" name=\"mod[$post[pid]]\" id=\"mod_$post[pid]_1\" value=\"validate\" checked=\"checked\" onclick=\"mod_setbg($post[pid], 'validate');\"><label for=\"mod_$post[pid]_1\">$lang[validate]</label></li><li><input class=\"radio\" type=\"radio\" name=\"mod[$post[pid]]\" id=\"mod_$post[pid]_2\" value=\"delete\" onclick=\"mod_setbg($post[pid], 'delete');\"><label for=\"mod_$post[pid]_2\">$lang[delete]</label></li><li><input class=\"radio\" type=\"radio\" name=\"mod[$post[pid]]\" id=\"mod_$post[pid]_3\" value=\"ignore\" onclick=\"mod_setbg($post[pid], 'ignore');\"><label for=\"mod_$post[pid]_3\">$lang[ignore]</label></li></ul>",
				"<h3><a href=\"forumdisplay.php?fid=$post[fid]\" target=\"_blank\">$post[forumname]</a> &raquo; <a href=\"viewthread.php?tid=$post[tid]&modthreadkey=$post[modthreadkey]\" target=\"_blank\">$post[tsubject]</a> &raquo; <b>$post[subject]</b></h3><p><span class=\"bold\">$lang[author]:</span> $post[author] ($post[useip]) &nbsp;&nbsp; <span class=\"bold\">$lang[time]:</span> $post[dateline]</p>"
			));
			showtablerow("id=\"mod_$post[pid]_row2\"", 'colspan="2" style="padding: 10px; line-height: 180%;"', '<div style="overflow: auto; overflow-x: hidden; max-height:120px; height:auto !important; height:100px; word-break: break-all;">'.$post[message].'</div>');
			showtablerow("id=\"mod_$post[pid]_row3\"", 'class="threadopt threadtitle" colspan="2"', "<a href=\"post.php?action=edit&fid=$post[fid]&tid=$post[tid]&pid=$post[pid]&page=1&modthreadkey=$post[modthreadkey]\" target=\"_blank\">".$lang['moderate_edit_post']."</a> &nbsp;&nbsp;|&nbsp;&nbsp; ".$lang['moderate_reasonpm']."&nbsp; <input type=\"text\" class=\"txt\" name=\"pm_$post[pid]\" id=\"pm_$post[pid]\" style=\"margin: 0px;\"> &nbsp; <select style=\"margin: 0px;\" onchange=\"$('pm_$post[pid]').value=this.value\">$modreasonoptions</select>");

		}

		showsubmit('modsubmit', 'submit', '', '<a href="#all" onclick="mod_setbg_all(\'validate\')">'.lang('moderate_all_validate').'</a> &nbsp;<a href="#all" onclick="mod_setbg_all(\'delete\')">'.lang('moderate_all_delete').'</a> &nbsp;<a href="#all" onclick="mod_setbg_all(\'ignore\')">'.lang('moderate_all_ignore').'</a>', $multipage);
		showtablefooter();
		showformfooter();

	} else {

		$moderation = array('validate' => array(), 'delete' => array(), 'ignore' => array());
		$pmlist = array();
		$validates = $ignores = $deletes = 0;

		if(is_array($mod)) {
			foreach($mod as $pid => $act) {
				$moderation[$act][] = intval($pid);
			}
		}

		if($ignorepids = implodeids($moderation['ignore'])) {
			$db->query("UPDATE {$tablepre}posts SET invisible='-3' WHERE pid IN ($ignorepids) AND invisible='-2' AND first='0' $fidadd[and]$fidadd[fids]");
			$ignores = $db->affected_rows();
		}

		if($deletepids = implodeids($moderation['delete'])) {
			$query = $db->query("SELECT pid, authorid, tid, message FROM {$tablepre}posts WHERE pid IN ($deletepids) AND invisible='$displayorder' AND first='0' $fidadd[and]$fidadd[fids]", 'UNBUFFERED');
			$pids = '0';
			while($post = $db->fetch_array($query)) {
				$pids .= ','.$post['pid'];
				$pm = 'pm_'.$post['pid'];
				if(isset($$pm) && $$pm <> '' && $post['authorid']) {
					$pmlist[] = array(
						'action' => 'modreplies_delete',
						'authorid' => $post['authorid'],
						'tid' => $post['tid'],
						'post' =>  dhtmlspecialchars(cutstr($post['message'], 30)),
						'reason' => dhtmlspecialchars($$pm)
					);
				}
			}

			if($pids) {
				$query = $db->query("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE pid IN ($deletepids)");
				while($attach = $db->fetch_array($query)) {
					dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
				}
				$db->query("DELETE FROM {$tablepre}attachments WHERE pid IN ($pids)", 'UNBUFFERED');
				$db->query("DELETE FROM {$tablepre}attachmentfields WHERE pid IN ($pids)", 'UNBUFFERED');
				$db->query("DELETE FROM {$tablepre}posts WHERE pid IN ($pids)", 'UNBUFFERED');
				$deletes = $db->affected_rows();
				$db->query("DELETE FROM {$tablepre}trades WHERE pid IN ($pids)", 'UNBUFFERED');
			}
			updatemodworks('DLP', count($moderation['delete']));
		}

		if($validatepids = implodeids($moderation['validate'])) {
			$forums = $threads = $lastpost = $attachments = $pidarray = $authoridarray = array();
			$query = $db->query("SELECT t.lastpost, p.pid, p.fid, p.tid, p.authorid, p.author, p.dateline, p.attachment, p.message, p.anonymous, ff.replycredits
				FROM {$tablepre}posts p
				LEFT JOIN {$tablepre}forumfields ff ON ff.fid=p.fid
				LEFT JOIN {$tablepre}threads t ON t.tid=p.tid
				WHERE p.pid IN ($validatepids) AND p.invisible='$displayorder' AND first='0' $fidadd[and]$fidadd[p]$fidadd[fids]");

			while($post = $db->fetch_array($query)) {
				$pidarray[] = $post['pid'];
				if($post['replycredits']) {
					updatepostcredits('+', $post['authorid'], unserialize($post['replycredits']));
				} else {
					$authoridarray[] = $post['authorid'];
				}

				$forums[] = $post['fid'];

				$threads[$post['tid']]['posts']++;
				$threads[$post['tid']]['lastpostadd'] = $post['dateline'] > $post['lastpost'] && $post['dateline'] > $lastpost[$post['tid']] ?
					", lastpost='$post[dateline]', lastposter='".($post['anonymous'] && $post['dateline'] != $post['lastpost'] ? '' : addslashes($post[author]))."'" : '';
				$threads[$post['tid']]['attachadd'] = $threads[$post['tid']]['attachadd'] || $post['attachment'] ? ', attachment=\'1\'' : '';

				$pm = 'pm_'.$post['pid'];
				if(isset($$pm) && $$pm <> '' && $post['authorid']) {
					$pmlist[] = array(
						'action' => 'modreplies_validate',
						'authorid' => $post['authorid'],
						'tid' => $post['tid'],
						'post' =>  dhtmlspecialchars(cutstr($post['message'], 30)),
						'reason' => dhtmlspecialchars($$pm)
					);
				}
			}

			if($authoridarray) {
				updatepostcredits('+', $authoridarray, $creditspolicy['reply']);
			}

			foreach($threads as $tid => $thread) {
				$db->query("UPDATE {$tablepre}threads SET replies=replies+$thread[posts] $thread[lastpostadd] $thread[attachadd] WHERE tid='$tid'", 'UNBUFFERED');
			}

			foreach(array_unique($forums) as $fid) {
				updateforumcount($fid);
			}

			if(!empty($pidarray)) {
				$db->query("UPDATE {$tablepre}posts SET invisible='0' WHERE pid IN (0,".implode(',', $pidarray).")");
				$validates = $db->affected_rows();
				updatemodworks('MOD', $validates);
			} else {
				updatemodworks('MOD', 1);
			}
		}

		if($pmlist) {
			foreach($pmlist as $pm) {
				$reason = $pm['reason'];
				$post = $pm['post'];
				$tid = intval($pm['tid']);
				sendnotice($pm['authorid'], $pm['action'], 'systempm');
			}
		}

		cpmsg('moderate_replies_succeed', "$BASESCRIPT?action=moderate&operation=replies&page=$page&filter=$filter&modfid=$modfid", 'succeed');

	}

}

?>