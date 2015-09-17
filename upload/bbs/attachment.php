<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: attachment.php 21262 2009-11-24 02:05:34Z liulanbo $
*/

define('CURSCRIPT', 'attachment');
define('NOROBOT', TRUE);
if(empty($_GET['k'])) {
	$encodemethod = 1;
	@list($_GET['aid'], $_GET['k'], $_GET['t'], $_GET['sid']) = explode('|', base64_decode($_GET['aid']));
} else {
	$encodemethod = 0;
}
require_once './include/common.inc.php';

if($attachexpire) {
	$k = $_GET['k'];
	$t = $_GET['t'];
	$authk = md5($aid.md5($authkey).$t);
	$authk = $encodemethod ? substr($authk, 0, 8) : $authk;
	if(empty($k) || empty($t) || $k != $authk || $timestamp - $t > $attachexpire * 3600) {
		$aid = intval($aid);
		if($attach = $db->fetch_first("SELECT pid, tid, isimage FROM {$tablepre}attachments WHERE aid='$aid'")) {
			if($attach['isimage']) {
				dheader('location: '.$boardurl.'images/common/none.gif');
			} else {
				$aidencode = aidencode($aid);
				showmessage('attachment_expired');
			}
		} else {
			showmessage('attachment_nonexistence');
		}
	}
}

$discuz_action = 14;

// read local file's function: 1=fread 2=readfile 3=fpassthru 4=fpassthru+multiple
$readmod = 2;

$refererhost = parse_url($_SERVER['HTTP_REFERER']);
$serverhost = $_SERVER['HTTP_HOST'];
if(($pos = strpos($serverhost, ':')) !== FALSE) {
	$serverhost = substr($serverhost, 0, $pos);
}

if($attachrefcheck && $_SERVER['HTTP_REFERER'] && !($refererhost['host'] == $serverhost)) {
	//dheader("Location: {$boardurl}images/common/invalidreferer.gif");
	showmessage('attachment_referer_invalid', NULL, 'HALTED');
}

periodscheck('attachbanperiods');

$attachexists = FALSE;
if(!empty($aid) && is_numeric($aid)) {
	if(($attach = $db->fetch_first("SELECT a.*, p.invisible FROM {$tablepre}attachments a LEFT JOIN {$tablepre}posts p ON a.pid=p.pid WHERE aid='$aid'")) && $attach['invisible'] == 0) {
		$thread = $db->fetch_first("SELECT tid, fid, price, special FROM {$tablepre}threads WHERE tid='$attach[tid]' AND displayorder>='0'");
		$thread && $attachexists = TRUE;
	}
}
!$attachexists && showmessage('attachment_nonexistence');

$forum = $db->fetch_first("SELECT f.fid, f.viewperm, f.getattachperm, f.getattachcredits, a.allowgetattach FROM {$tablepre}forumfields f
	LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid
	WHERE f.fid='$thread[fid]'");

$_GET['fid'] = $forum['fid'];

$allowgetattach = !empty($forum['allowgetattach']) || ($allowgetattach && !$forum['getattachperm']) || forumperm($forum['getattachperm']);
if($allowgetattach && ($attach['readperm'] && $attach['readperm'] > $readaccess) && $adminid <= 0 && !($discuz_uid && $discuz_uid == $attach['uid'])) {
	showmessage('attachment_forum_nopermission', NULL, 'NOPERM');
}

$ispaid = FALSE;
if(!$thread['special'] && $thread['price'] > 0 && (!$discuz_uid || ($discuz_uid && $discuz_uid != $attach['uid'] && $adminid <=0))) {
	$exemptattachpay = $discuz_uid && ($exempt & 8) ? 1 : 0;
	$ispaid = $discuz_uid ? $db->result_first("SELECT uid FROM {$tablepre}paymentlog WHERE uid='$discuz_uid' AND tid='$attach[tid]'") : FALSE;
	!$ispaid && !$exemptattachpay && showmessage('attachment_payto', 'viewthread.php?tid='.$attach['tid']);
}

$ismoderator = in_array($adminid, array(1, 2)) ? 1 : ($adminid == 3 ? $db->result_first("SELECT uid FROM {$tablepre}moderators m INNER JOIN {$tablepre}threads t ON t.tid='$attach[tid]' AND t.fid=m.fid WHERE m.uid='$discuz_uid'") : 0);
$exemptvalue = $ismoderator ? 64 : 8;
if($attach['price'] && (!$discuz_uid || ($discuz_uid != $attach['uid'] && !($exempt & $exemptvalue)))) {
	$payrequired = $discuz_uid ? !$db->result_first("SELECT uid FROM {$tablepre}attachpaymentlog WHERE uid='$discuz_uid' AND aid='$attach[aid]'") : 1;
	$payrequired && showmessage('attachement_payto_attach', 'misc.php?action=attachpay&aid='.$attach['aid']);
}

$isimage = $attach['isimage'];
$ftp['hideurl'] = $ftp['hideurl'] || ($isimage && !empty($noupdate) && $attachimgpost && strtolower(substr($ftp['attachurl'], 0, 3)) == 'ftp');

if(empty($nothumb) && $attach['isimage'] && $attach['thumb']) {
	$db->close(); ob_end_clean();
	dheader('Content-Disposition: inline; filename='.$attach['filename'].'.thumb.jpg');
	dheader('Content-Type: image/pjpeg');
	if($attach['remote']) {
		$ftp['hideurl'] ? getremotefile($attach['attachment'].'.thumb.jpg') : dheader('location:'.$ftp['attachurl'].'/'.$attach['attachment'].'.thumb.jpg');
	} else {
		getlocalfile($attachdir.'/'.$attach['attachment'].'.thumb.jpg');
	}
	exit();
}

$filename = $attachdir.'/'.$attach['attachment'];
if(!$attach['remote'] && !is_readable($filename)) {
	showmessage('attachment_nonexistence');
}

if(!$ispaid && !$forum['allowgetattach']) {
	if(!$forum['getattachperm'] && !$allowgetattach) {
		showmessage('getattachperm_none_nopermission', NULL, 'NOPERM');
	} elseif(($forum['getattachperm'] && !forumperm($forum['getattachperm'])) || ($forum['viewperm'] && !forumperm($forum['viewperm']))) {
		showmessagenoperm('getattachperm', $forum['fid']);
	}
}

$range = 0;
if($readmod == 4 && !empty($_SERVER['HTTP_RANGE'])) {
	list($range) = explode('-',(str_replace('bytes=', '', $_SERVER['HTTP_RANGE'])));
}

$exemptvalue = $ismoderator ? 32 : 4;
if(!$isimage && !($exempt & $exemptvalue)) {
	$getattachcredits = $forum['getattachcredits'] ? unserialize($forum['getattachcredits']) : $creditspolicy['getattach'];
	$redirectcredit = FALSE;
	foreach($getattachcredits as $creditid => $v) {
		if($v) {
			$redirectcredit = TRUE;
			break;
		}
	}
	if($redirectcredit) {
		$k = $_GET['ck'];
		$t = $_GET['t'];
		if(empty($k) || empty($t) || $k != substr(md5($aid.$t.md5($authkey)), 0, 8) || $timestamp - $t > 3600) {
			dheader('location: misc.php?action=attachcredit&aid='.$attach['aid'].'&formhash='.FORMHASH);
			exit();
		}
	}
}

if(empty($noupdate)) {
	if($delayviewcount == 2 || $delayviewcount == 3) {
		$logfile = './forumdata/cache/cache_attachviews.log';
		if(substr($timestamp, -1) == '0') {
			require_once DISCUZ_ROOT.'./include/misc.func.php';
			updateviews('attachments', 'aid', 'downloads', $logfile);
		}

		if(@$fp = fopen(DISCUZ_ROOT.$logfile, 'a')) {
			fwrite($fp, "$aid\n");
			fclose($fp);
		} elseif($adminid == 1) {
			showmessage('view_log_invalid');
		}
	} else {
		$db->query("UPDATE {$tablepre}attachments SET downloads=downloads+'1' WHERE aid='$aid'", 'UNBUFFERED');
	}
}

$db->close(); ob_end_clean();

//dheader('Cache-control: max-age=31536000');
//dheader('Expires: '.gmdate('D, d M Y H:i:s', $timestamp + 31536000).' GMT');

if($attach['remote'] && !$ftp['hideurl']) {
	dheader('location:'.$ftp['attachurl'].'/'.$attach['attachment']);
}

$filesize = !$attach['remote'] ? filesize($filename) : $attach['filesize'];
$attach['filename'] = '"'.(strtolower($charset) == 'utf-8' && strexists($_SERVER['HTTP_USER_AGENT'], 'MSIE') ? urlencode($attach['filename']) : $attach['filename']).'"';

dheader('Date: '.gmdate('D, d M Y H:i:s', $attach['dateline']).' GMT');
dheader('Last-Modified: '.gmdate('D, d M Y H:i:s', $attach['dateline']).' GMT');
dheader('Content-Encoding: none');

if($isimage && !empty($noupdate) || !empty($request)) {
	dheader('Content-Disposition: inline; filename='.$attach['filename']);
} else {
	dheader('Content-Disposition: attachment; filename='.$attach['filename']);
}

dheader('Content-Type: '.$attach['filetype']);
dheader('Content-Length: '.$filesize);

if($readmod == 4) {
	dheader('Accept-Ranges: bytes');
	if(!empty($_SERVER['HTTP_RANGE'])) {
		$rangesize = ($filesize - $range) > 0 ?  ($filesize - $range) : 0;
		dheader('Content-Length: '.$rangesize);
		dheader('HTTP/1.1 206 Partial Content');
		dheader('Content-Range: bytes='.$range.'-'.($filesize-1).'/'.($filesize));
	}
}

$attach['remote'] ? getremotefile($attach['attachment']) : getlocalfile($filename, $readmod, $range);

function getremotefile($file) {
	global $authkey, $ftp, $attachdir;
	@set_time_limit(0);
	if(!@readfile($ftp['attachurl'].'/'.$file)) {
		require_once DISCUZ_ROOT.'./include/ftp.func.php';
		if(!($ftp['connid'] = dftp_connect($ftp['host'], $ftp['username'], authcode($ftp['password'], 'DECODE', md5($authkey)), $ftp['attachdir'], $ftp['port'], $ftp['ssl']))) {
			return FALSE;
		}
		$tmpfile = @tempnam($attachdir, '');
		if(dftp_get($ftp['connid'], $tmpfile, $file, FTP_BINARY)) {
			@readfile($tmpfile);
			@unlink($tmpfile);
		} else {
			@unlink($tmpfile);
			return FALSE;
		}
	}
	return TRUE;
}

function getlocalfile($filename, $readmod = 2, $range = 0) {
	if($readmod == 1 || $readmod == 3 || $readmod == 4) {
		if($fp = @fopen($filename, 'rb')) {
			@fseek($fp, $range);
			if(function_exists('fpassthru') && ($readmod == 3 || $readmod == 4)) {
				@fpassthru($fp);
			} else {
				echo @fread($fp, filesize($filename));
			}
		}
		@fclose($fp);
	} else {
		@readfile($filename);
	}
	@flush(); @ob_flush();
}

?>