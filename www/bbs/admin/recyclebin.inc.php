<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: recyclebin.inc.php 20917 2009-10-29 09:19:37Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

require_once DISCUZ_ROOT.'./include/post.func.php';
require_once DISCUZ_ROOT.'./include/discuzcode.func.php';

cpheader();

if(!$operation) {

	shownav('topic', 'nav_recyclebin');
	showsubmenu('nav_recyclebin', array(
		array('recyclebin_list', 'recyclebin', 1),
		array('search', 'recyclebin&operation=search', 0),
		array('clean', 'recyclebin&operation=clean', 0)
	));


	if(!submitcheck('delsubmit') && !submitcheck('undelsubmit')) {

		$lpp = empty($lpp) ? 10 : $lpp;
		$page = max(1, intval($page));
		$start = ($page - 1) * $lpp;
		$start_limit = ($page - 1) * $lpp;

		showformheader('recyclebin');
		showtableheader('recyclebin_list');
		showsubtitle(array('', 'thread', 'recyclebin_list_thread', 'recyclebin_list_author', 'recyclebin_list_status', 'recyclebin_list_lastpost', 'recyclebin_list_operation'));
		$query = $db->query("SELECT f.name AS forumname,t.tid, t.fid, t.authorid, t.author, t.subject, t.views, t.replies, t.dateline, t.lastpost, t.lastposter,
					tm.uid AS moduid, tm.username AS modusername, tm.dateline AS moddateline, tm.action AS modaction
					FROM {$tablepre}threads t
					LEFT JOIN {$tablepre}threadsmod tm ON tm.tid=t.tid
					LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
					WHERE t.displayorder='-1' $sql
					GROUP BY t.tid ORDER BY t.dateline DESC LIMIT $start_limit, $lpp");
		while($thread = $db->fetch_array($query)) {
			$thread['modthreadkey'] = modthreadkey($thread['tid']);
			showtablerow('', array('class="td25"', '', '', 'class="td28"', 'class="td28"'), array(
				"<input type=\"checkbox\" class=\"checkbox\" name=\"threadlist[]\" value=\"$thread[tid]\">",
				'<a href="viewthread.php?tid='.$thread['tid'].'&modthreadkey='.$thread['modthreadkey'].'" target="_blank">'.$thread['subject'].'</a>',
				'<a href="forumdisplay.php?fid='.$thread['fid'].'" target="_blank">'.$thread['forumname'].'</a>',
				'<a href="space.php?uid='.$thread['authorid'].'" target="_blank">'.$thread['author'].'</a><br /><em style="font-size:9px;color:#999999;">'.gmdate("$dateformat", $thread['dateline'] + $timeoffset * 3600).'</em>',
				$thread['replies'].' / '.$thread['views'],
				$thread['lastposter'].'<br /><em style="font-size:9px;color:#999999;">'.gmdate("$dateformat", $thread['lastpost'] + $timeoffset * 3600).'</em>',
				$thread['modusername'].'<br /><em style="font-size:9px;color:#999999;">'.gmdate("$dateformat", $thread['moddateline'] + $timeoffset * 3600).'</em>'
			));
		}

		$threadcount = $db->result_first("SELECT count(*) FROM {$tablepre}threads t WHERE t.displayorder='-1'");
		$multipage = multi($threadcount, $lpp, $page, "$BASESCRIPT?action=recyclebin&lpp=$lpp", 0, 3);

		showsubmit('', '', '', '<input type="checkbox" name="chkall" id="chkall" class="checkbox" onclick="checkAll(\'prefix\', this.form, \'threadlist\')" /><label for="chkall">'.lang('select_all').'</label>&nbsp;&nbsp;<input type="submit" class="btn" name="delsubmit" value="'.lang('recyclebin_delete').'" />&nbsp;<input type="submit" class="btn" name="undelsubmit" value="'.lang('recyclebin_undelete').'" />', $multipage);
		showtablefooter();
		showformfooter();
	} else {

		if(empty($threadlist)) {
			cpmsg('recyclebin_none_selected', $BASESCRIPT.'?action=recyclebin', 'error');
		}

		$threadsundel = $threadsdel = 0;
		if(submitcheck('undelsubmit')) {
			$threadsundel = undeletethreads($threadlist);
		} elseif(submitcheck('delsubmit')) {
			$threadsdel = deletethreads($threadlist);
		}

		cpmsg('recyclebin_succeed', $BASESCRIPT.'?action=recyclebin', 'succeed');

	}

} elseif($operation == 'search') {

	if(!submitcheck('rbsubmit')) {

		require_once DISCUZ_ROOT.'./include/forum.func.php';

		$forumselect = '<select name="inforum"><option value="">&nbsp;&nbsp;> '.$lang['select'].'</option>'.
			'<option value="">&nbsp;</option>'.forumselect(FALSE, 0, 0, TRUE).'</select>';

		if($inforum) {
			$forumselect = preg_replace("/(\<option value=\"$inforum\")(\>)/", "\\1 selected=\"selected\" \\2", $forumselect);
		}

		shownav('topic', 'nav_recyclebin');
		showsubmenu('nav_recyclebin', array(
			array('recyclebin_list', 'recyclebin', 0),
			array('search', 'recyclebin', 1),
			array('clean', 'recyclebin&operation=clean', 0)
		));
		echo <<<EOT
<script type="text/javascript" src="include/js/calendar.js"></script>
<script type="text/JavaScript">
function page(number) {
	$('rbsearchform').page.value=number;
	$('rbsearchform').searchsubmit.click();
}
</script>
EOT;
		showtagheader('div', 'threadsearch', !$searchsubmit);
		showformheader('recyclebin&operation=search', '', 'rbsearchform');
		showhiddenfields(array('page' => $page));
		showtableheader('recyclebin_search');
		showsetting('recyclebin_search_forum', '', '', $forumselect);
		showsetting('recyclebin_search_author', 'authors', $authors, 'text');
		showsetting('recyclebin_search_keyword', 'keywords', $keywords, 'text');
		showsetting('recyclebin_search_admin', 'admins', $admins, 'text');
		showsetting('recyclebin_search_post_time', array('pstarttime', 'pendtime'), array($pstarttime, $pendtime), 'daterange');
		showsetting('recyclebin_search_mod_time', array('mstarttime', 'mendtime'), array($mstarttime, $mendtime), 'daterange');
		showsubmit('searchsubmit');
		showtablefooter();
		showformfooter();
		showtagfooter('div');

		if(submitcheck('searchsubmit')) {

			$sql = '';
			$sql .= $inforum		? " AND t.fid='$inforum'" : '';
			$sql .= $authors != ''		? " AND t.author IN ('".str_replace(',', '\',\'', str_replace(' ', '', $authors))."')" : '';
			$sql .= $admins != ''		? " AND tm.username IN ('".str_replace(',', '\',\'', str_replace(' ', '', $admins))."')" : '';
			$sql .= $pstarttime != ''	? " AND t.dateline>='".strtotime($pstarttime)."'" : '';
			$sql .= $pendtime != ''		? " AND t.dateline<'".strtotime($pendtime)."'" : '';
			$sql .= $mstarttime != ''	? " AND tm.dateline>='".strtotime($mstarttime)."'" : '';
			$sql .= $mendtime != ''		? " AND tm.dateline<'".strtotime($mendtime)."'" : '';

			if(trim($keywords)) {
				$sqlkeywords = $or = '';
				foreach(explode(',', str_replace(' ', '', $keywords)) as $keyword) {
					$sqlkeywords .= " $or t.subject LIKE '%$keyword%'";
					$or = 'OR';
				}
				$sql .= " AND ($sqlkeywords)";
			}

			$threadcount = $db->result_first("SELECT count(*)
				FROM {$tablepre}threads t
				LEFT JOIN {$tablepre}threadsmod tm ON tm.tid=t.tid
				WHERE t.displayorder='-1' $sql");

			$pagetmp = $page;
			do{
				$query = $db->query("SELECT f.name AS forumname, f.allowsmilies, f.allowhtml, f.allowbbcode, f.allowimgcode,
					t.tid, t.fid, t.authorid, t.author, t.subject, t.views, t.replies, t.dateline,
					p.message, p.useip, p.attachment, p.htmlon, p.smileyoff, p.bbcodeoff,
					tm.uid AS moduid, tm.username AS modusername, tm.dateline AS moddateline, tm.action AS modaction
					FROM {$tablepre}threads t
					LEFT JOIN {$tablepre}posts p ON p.tid=t.tid AND p.first='1'
					LEFT JOIN {$tablepre}threadsmod tm ON tm.tid=t.tid
					LEFT JOIN {$tablepre}forums f ON f.fid=t.fid
					WHERE t.displayorder='-1' $sql
					GROUP BY t.tid ORDER BY t.dateline DESC LIMIT ".(($pagetmp - 1) * $ppp).",$ppp");
				$pagetmp--;
			} while(!$db->num_rows($query) && $pagetmp);
			$multi = multi($threadcount, $ppp, $page, "$BASESCRIPT?action=recyclebin");
			$multi = preg_replace("/href=\"$BASESCRIPT\?action=recyclebin&amp;page=(\d+)\"/", "href=\"javascript:page(\\1)\"", $multi);
			$multi = str_replace("window.location=$BASESCRIPT.'?action=recyclebin&amp;page='+this.value", "page(this.value)", $multi);

			echo '<script type="text/JavaScript">var replyreload;function attachimg() {}</script>';
			showtagheader('div', 'threadlist', $searchsubmit);
			showformheader('recyclebin&operation=search&frame=no', 'target="rbframe"', 'rbform');
			showtableheader(lang('recyclebin_result').' '.$threadcount.' <a href="#" onclick="$(\'threadlist\').style.display=\'none\';$(\'threadsearch\').style.display=\'\';" class="act lightlink normal">'.lang('research').'</a>', 'fixpadding');

			while($thread = $db->fetch_array($query)) {
				$thread['message'] = discuzcode($thread['message'], $thread['smileyoff'], $thread['bbcodeoff'], sprintf('%00b', $thread['htmlon']), $thread['allowsmilies'], $thread['allowbbcode'], $thread['allowimgcode'], $thread['allowhtml']);
				$thread['moddateline'] = gmdate("$dateformat $timeformat", $thread['moddateline'] + $timeoffset * 3600);
				$thread['dateline'] = gmdate("$dateformat $timeformat", $thread['dateline'] + $timeoffset * 3600);

				if($thread['attachment']) {
					require_once DISCUZ_ROOT.'./include/attachment.func.php';
					$queryattach = $db->query("SELECT aid, filename, filetype, filesize FROM {$tablepre}attachments WHERE tid='$thread[tid]'");
					while($attach = $db->fetch_array($queryattach)) {
						$thread['message'] .= "<br /><br />$lang[attachment]: ".attachtype(fileext($thread['filename'])."\t".$attach['filetype'])." $attach[filename] (".sizecount($attach['filesize']).")";
					}
				}

				showtablerow("id=\"mod_$thread[tid]_row1\"", array('rowspan="3" class="rowform threadopt" style="width:80px;"', 'class="threadtitle"'), array(
					"<ul class=\"nofloat\"><li><input class=\"radio\" type=\"radio\" name=\"mod[$thread[tid]]\" id=\"mod_$thread[tid]_1\" value=\"delete\" checked=\"checked\" /><label for=\"mod_$thread[tid]_1\">$lang[delete]</label></li><li><input class=\"radio\" type=\"radio\" name=\"mod[$thread[tid]]\" id=\"mod_$thread[tid]_2\" value=\"undelete\" /><label for=\"mod_$thread[tid]_2\">$lang[undelete]</label></li><li><input class=\"radio\" type=\"radio\" name=\"mod[$thread[tid]]\" id=\"mod_$thread[tid]_3\" value=\"ignore\" /><label for=\"mod_$thread[tid]_3\">$lang[ignore]</label></li></ul>",
					"<h3><a href=\"forumdisplay.php?fid=$thread[fid]\" target=\"_blank\">$thread[forumname]</a> &raquo; $thread[subject]</h3><p><span class=\"bold\">$lang[author]:</span> <a href=\"space.php?uid=$thread[authorid]\" target=\"_blank\">$thread[author]</a> &nbsp;&nbsp; <span class=\"bold\">$lang[time]:</span> $thread[dateline] &nbsp;&nbsp; $lang[threads_replies]: $thread[replies] $lang[threads_views]: $thread[views]</p>"
				));
				showtablerow("id=\"mod_$thread[tid]_row2\"", 'colspan="2" style="padding: 10px; line-height: 180%;"', '<div style="overflow: auto; overflow-x: hidden; max-height:120px; height:auto !important; height:120px; word-break: break-all;">'.$thread['message'].'</div>');
				showtablerow("id=\"mod_$thread[tid]_row3\"", 'class="threadopt threadtitle" colspan="2"', "$lang[operator]: <a href=\"space.php?uid=$thread[moduid]\" target=\"_blank\">$thread[modusername]</a> &nbsp;&nbsp; $lang[recyclebin_delete_time]: $thread[moddateline]");
			}

			showsubmit('rbsubmit', 'submit', '', '<a href="#rb" onclick="checkAll(\'option\', $(\'rbform\'), \'delete\')">'.lang('recyclebin_all_delete').'</a> &nbsp;<a href="#rb" onclick="checkAll(\'option\', $(\'rbform\'), \'undelete\')">'.lang('recyclebin_all_undelete').'</a> &nbsp;<a href="#rb" onclick="checkAll(\'option\', $(\'rbform\'), \'ignore\')">'.lang('recyclebin_all_ignore').'</a> &nbsp;', $multi);
			showtablefooter();
			showformfooter();
			echo '<iframe name="rbframe" style="display:none"></iframe>';
			showtagfooter('div');

		}

	} else {

		$moderation = array('delete' => array(), 'undelete' => array(), 'ignore' => array());

		if(is_array($mod)) {
			foreach($mod as $tid => $action) {
				$moderation[$action][] = intval($tid);
			}
		}

		$threadsdel = deletethreads($moderation['delete']);
		$threadsundel = undeletethreads($moderation['undelete']);

		//cpmsg('recyclebin_succeed', $BASESCRIPT.'?action=recyclebin&operation=', 'succeed');
		eval("\$cpmsg = \"".lang('recyclebin_succeed')."\";");

?>
<script type="text/JavaScript">alert('<?=$cpmsg?>');parent.$('rbsearchform').searchsubmit.click();</script>
<?

	}

} elseif($operation == 'clean') {

	if(!submitcheck('rbsubmit')) {

		shownav('topic', 'nav_recyclebin');
		showsubmenu('nav_recyclebin', array(
			array('recyclebin_list', 'recyclebin', 0),
			array('search', 'recyclebin', 0),
			array('clean', 'recyclebin&operation=clean', 1)
		));
		showformheader('recyclebin&operation=clean');
		showtableheader('recyclebin_clean');
		showsetting('recyclebin_clean_days', 'days', '30', 'text');
		showsubmit('rbsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$deletetids = array();
		$query = $db->query("SELECT tm.tid FROM {$tablepre}threadsmod tm, {$tablepre}threads t
			WHERE tm.dateline<$timestamp-'$days'*86400 AND tm.action='DEL' AND t.tid=tm.tid AND t.displayorder='-1'");
		while($thread = $db->fetch_array($query)) {
			$deletetids[] = $thread['tid'];
		}
		$threadsdel = deletethreads($deletetids);
		$threadsundel = 0;
		cpmsg('recyclebin_succeed', $BASESCRIPT.'?action=recyclebin&operation=clean', 'succeed');

	}
}

?>