<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: attach.inc.php 20422 2009-09-27 03:19:30Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!submitcheck('deletesubmit')) {

	require_once DISCUZ_ROOT.'./include/forum.func.php';

	$anchor = in_array($anchor, array('search', 'admin')) ? $anchor : 'search';

	shownav('topic', 'nav_attaches');
	showsubmenusteps('nav_attaches', array(
		array('search', !$searchsubmit),
		array('admin', $searchsubmit),
	));
	showtips('attach_tips', 'attach_tips', $searchsubmit);
	showtagheader('div', 'search', !$searchsubmit);
	showformheader('attach');
	showtableheader();
	showsetting('attach_nomatched', 'nomatched', 0, 'radio');
	showsetting('attach_forum', '', '', '<select name="inforum"><option value="all">&nbsp;&nbsp;>'.lang('all').'</option><option value="">&nbsp;</option>'.forumselect(FALSE, 0, 0, TRUE).'</select>');
	showsetting('attach_sizerange', array('sizeless', 'sizemore'), array('', ''), 'range');
	showsetting('attach_dlcountrange', array('dlcountless', 'dlcountmore'), array('', ''), 'range');
	showsetting('attach_daysold', 'daysold', '', 'text');
	showsetting('filename', 'filename', '', 'text');
	showsetting('attach_keyword', 'keywords', '', 'text');
	showsetting('attach_author', 'author', '', 'text');
	showsubmit('searchsubmit', 'search');
	showtablefooter();
	showformfooter();
	showtagfooter('div');

	if(submitcheck('searchsubmit')) {

		require_once DISCUZ_ROOT.'./include/attachment.func.php';

		$sql = "a.pid=p.pid";
		$ppp = 100;

		if($inforum != 'all') {
			$inforum = intval($inforum);
		}

		$sql .= is_numeric($inforum) ? " AND p.fid='$inforum'" : '';
		$sql .= $daysold ? " AND p.dateline<='".($timestamp - (86400 * $daysold))."'" : '';
		$sql .= $author ? " AND p.author='$author'" : '';
		$sql .= $filename ? " AND a.filename LIKE '%$filename%'" : '';

		if($keywords) {
			$sqlkeywords = $or = '';
			foreach(explode(',', str_replace(' ', '', $keywords)) as $keyword) {
				$sqlkeywords .= " $or af.description LIKE '%$keyword%'";
				$or = 'OR';
			}
			$sql .= " AND ($sqlkeywords)";
		}

		$sql .= $sizeless ? " AND a.filesize<'$sizeless'" : '';
		$sql .= $sizemore ? " AND a.filesize>'$sizemore' " : '';
		$sql .= $dlcountless ? " AND a.downloads<'$dlcountless'" : '';
		$sql .= $dlcountmore ? " AND a.downloads>'$dlcountmore'" : '';

		$attachments = '';
		$page = max(1, intval($page));
		$query = $db->query("SELECT a.*, af.description, p.fid, p.author, t.tid, t.tid, t.subject, f.name AS fname
			FROM {$tablepre}attachments a LEFT JOIN {$tablepre}attachmentfields af ON a.aid=af.aid, {$tablepre}posts p, {$tablepre}threads t, {$tablepre}forums f
			WHERE t.tid=a.tid AND f.fid=p.fid AND t.displayorder>='0' AND p.invisible='0' AND $sql LIMIT ".(($page - 1) * $ppp).','.$ppp);
		while($attachment = $db->fetch_array($query)) {
			if(!$attachment['remote']) {
				$matched = file_exists($attachdir.'/'.$attachment['attachment']) ? '' : lang('attach_lost');
				$attachment['url'] = $attachurl;
			} else {
				@set_time_limit(0);
				if(@fclose(@fopen($ftp['attachurl'].'/'.$attachment['attachment'], 'r'))) {
					$matched = '';
				} else {
					$matched = lang('attach_lost');
				}
				$attachment['url'] = $ftp['attachurl'];
			}
			$attachsize = sizecount($attachment['filesize']);
			if(!$nomatched || ($nomatched && $matched)) {
				$attachments .= showtablerow('', array('class="td25"', 'title="'.$attachment['description'].'" class="td21"'), array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$attachment[aid]\" />",
					$attachment['remote'] ? "<span class=\"diffcolor3\">$attachment[filename]" : $attachment['filename'],
					"<a href=\"$attachment[url]/$attachment[attachment]\" class=\"smalltxt\" target=\"_blank\">".cutstr($attachment['attachment'], 30)."</a>",
					$attachment['author'],
					"<a href=\"viewthread.php?tid=$attachment[tid]\" target=\"_blank\">".cutstr($attachment['subject'], 20)."</a>",
					$attachsize,
					$attachment['downloads'],
					$matched ? "<em class=\"error\">$matched<em>" : "<a href=\"attachment.php?aid=".aidencode($attachment['aid'])."&noupdate=yes\" target=\"_blank\" class=\"act nomargin\">$lang[download]</a>"
				), TRUE);
			}
		}
		$attachmentcount = $db->result_first("SELECT count(*) FROM {$tablepre}attachments a LEFT JOIN {$tablepre}attachmentfields af ON a.aid=af.aid, {$tablepre}posts p, {$tablepre}threads t, {$tablepre}forums f
			WHERE t.tid=a.tid AND f.fid=p.fid AND t.displayorder>='0' AND p.invisible='0' AND $sql");
		$multipage = multi($attachmentcount, $ppp, $page, "$BASESCRIPT?action=attachments");
		$multipage = preg_replace("/href=\"$BASESCRIPT\?action=attachments&amp;page=(\d+)\"/", "href=\"javascript:page(\\1)\"", $multipage);
		$multipage = str_replace("window.location=$BASESCRIPT.'?action=attachments&amp;page='+this.value", "page(this.value)", $multipage);

		echo <<<EOT
<script type="text/JavaScript">
	function page(number) {
		$('attachmentforum').page.value=number;
		$('attachmentforum').searchsubmit.click();
	}
</script>
EOT;
		showtagheader('div', 'admin', $searchsubmit);
		showformheader('attach', '', 'attachmentforum');
		showhiddenfields(array(
			'page' => $page,
			'nomatched' => $nomatched,
			'inforum' => $inforum,
			'sizeless' => $sizeless,
			'sizemore' => $sizemore,
			'dlcountless' => $dlcountless,
			'dlcountmore' => $dlcountmore,
			'daysold' => $daysold,
			'filename' => $filename,
			'keywords' => $keywords,
			'author' => $author,
		));
		echo '<input type="submit" name="searchsubmit" value="'.lang('submit').'" class="btn" style="display: none" />';
		showformfooter();

		showformheader('attach&frame=no', 'target="attachmentframe"');
		showtableheader();
		showsubtitle(array('', 'filename', 'attach_path', 'author', 'attach_thread', 'size', 'attach_downloadnums', ''));
		echo $attachments;
		showsubmit('deletesubmit', 'submit', 'del', '<a href="###" onclick="$(\'admin\').style.display=\'none\';$(\'search\').style.display=\'\';" class="act lightlink normal">'.lang('research').'</a>', $multipage);
		showtablefooter();
		showformfooter();
		echo '<iframe name="attachmentframe" style="display:none"></iframe>';
		showtagfooter('div');

	}

} else {

	if($ids = implodeids($delete)) {

		$tids = $pids = 0;
		$query = $db->query("SELECT tid, pid, attachment, thumb, remote FROM {$tablepre}attachments WHERE aid IN ($ids)");
		while($attach = $db->fetch_array($query)) {
			dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
			$tids .= ','.$attach['tid'];
			$pids .= ','.$attach['pid'];
		}
		$db->query("DELETE FROM {$tablepre}attachments WHERE aid IN ($ids)");
		$db->query("DELETE FROM {$tablepre}attachmentfields WHERE aid IN ($ids)");

		$attachtids = 0;
		$query = $db->query("SELECT tid FROM {$tablepre}attachments WHERE tid IN ($tids) GROUP BY tid ORDER BY pid DESC");
		while($attach = $db->fetch_array($query)) {
			$attachtids .= ','.$attach['tid'];
		}
		$db->query("UPDATE {$tablepre}threads SET attachment='0' WHERE tid IN ($tids)".($attachtids ? " AND tid NOT IN ($attachtids)" : NULL));

		$attachpids = 0;
		$query = $db->query("SELECT pid FROM {$tablepre}attachments WHERE pid IN ($pids) GROUP BY pid ORDER BY pid DESC");
		while($attach = $db->fetch_array($query)) {
			$attachpids .= ','.$attach['pid'];
		}
		$db->query("UPDATE {$tablepre}posts SET attachment='0' WHERE pid IN ($pids)".($attachpids ? " AND pid NOT IN ($attachpids)" : NULL));

		eval("\$cpmsg = \"".lang('attach_edit_succeed')."\";");

	} else {

		eval("\$cpmsg = \"".lang('attach_edit_invalid')."\";");

	}

	echo "<script type=\"text/JavaScript\">alert('$cpmsg');parent.\$('attachmentforum').searchsubmit.click();</script>";
}

?>