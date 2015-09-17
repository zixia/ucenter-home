<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: checktools.inc.php 20755 2009-10-19 01:36:56Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!isfounder()) cpmsg('noaccess_isfounder', '', 'error');

if($operation == 'filecheck') {

	$step = max(1, intval($step));
	shownav('tools', 'nav_filecheck');
	showsubmenusteps('nav_filecheck', array(
		array('nav_filecheck_confirm', $step == 1),
		array('nav_filecheck_verify', $step == 2),
		array('nav_filecheck_completed', $step == 3)
	));
	if($step == 1) {
		cpmsg($lang[filecheck_tips_step1], $BASESCRIPT.'?action=checktools&operation=filecheck&step=2', 'button', '', FALSE);
	} elseif($step == 2) {
		cpmsg(lang('filecheck_verifying'), "$BASESCRIPT?action=checktools&operation=filecheck&step=3", 'loading', '', FALSE);
	} elseif($step == 3) {

		if(!$discuzfiles = @file('admin/discuzfiles.md5')) {
			cpmsg('filecheck_nofound_md5file', '', 'error');
		}

		$md5data = array();
		$cachelist = checkcachefiles('forumdata/cache/');
		checkfiles('./', '\.php', 0, 'config.inc.php');
		checkfiles('include/', '\.php|\.htm|\.js');
		checkfiles('templates/default/', '\.php|\.htm');
		checkfiles('wap/', '\.php');
		checkfiles('archiver/', '\.php');
		checkfiles('api/', '\.php');
		checkfiles('plugins/', '\.php');
		checkfiles('admin/', '\.php');
		checkfiles('modcp/', '\.php');
		checkfiles('uc_client/', '\.php', 0);
		checkfiles('uc_client/control/', '\.php');
		checkfiles('uc_client/model/', '\.php');
		checkfiles('uc_client/lib/', '\.php');

		foreach($discuzfiles as $line) {
			$file = trim(substr($line, 34));
			$md5datanew[$file] = substr($line, 0, 32);
			if($md5datanew[$file] != $md5data[$file]) {
				$modifylist[$file] = $md5data[$file];
			}
			$md5datanew[$file] = $md5data[$file];
		}

		$weekbefore = $timestamp - 604800;
		$addlist = @array_merge(@array_diff_assoc($md5data, $md5datanew), $cachelist[2]);
		$dellist = @array_diff_assoc($md5datanew, $md5data);
		$modifylist = @array_merge(@array_diff_assoc($modifylist, $dellist), $cachelist[1]);
		$showlist = @array_merge($md5data, $md5datanew, $cachelist[0]);
		$doubt = 0;
		$dirlist = $dirlog = array();
		foreach($showlist as $file => $md5) {
			$dir = dirname($file);
			if(@array_key_exists($file, $modifylist)) {
				$fileststus = 'modify';
			} elseif(@array_key_exists($file, $dellist)) {
				$fileststus = 'del';
			} elseif(@array_key_exists($file, $addlist)) {
				$fileststus = 'add';
			} else {
				$filemtime = @filemtime($file);
				if($filemtime > $weekbefore) {
					$fileststus = 'doubt';
					$doubt++;
				} else {
					$fileststus = '';
				}
			}
			if(file_exists($file)) {
				$filemtime = @filemtime($file);
				$fileststus && $dirlist[$fileststus][$dir][basename($file)] = array(number_format(filesize($file)).' Bytes', date("$dateformat $timeformat", $filemtime));
			} else {
				$fileststus && $dirlist[$fileststus][$dir][basename($file)] = array('', '');
			}
		}
		$result = $resultjs = '';
		$dirnum = 0;
		foreach($dirlist as $status => $filelist) {
			$dirnum++;
			$class = $status == 'modify' ? 'edited' : ($status == 'del' ? 'del' : 'unknown');
			$result .= '<tbody id="status_'.$status.'" style="display:'.($status != 'modify' ? 'none' : '').'">';
			foreach($filelist as $dir => $files) {
				$result .= '<tr><td colspan="4"><div class="ofolder">'.$dir.'</div><div class="lightfont filenum left">';
				foreach($files as $filename => $file) {
					$result .= '<tr><td><em class="files bold">'.$filename.'</em></td><td style="text-align: right">'.$file[0].'&nbsp;&nbsp;</td><td>'.$file[1].'</td><td><em class="'.$class.'">&nbsp;</em></td></tr>';
				}
			}
			$result .= '</tbody>';
			$resultjs .= '$(\'status_'.$status.'\').style.display=\'none\';';
		}

		$modifiedfiles = count($modifylist);
		$deletedfiles = count($dellist);
		$unknownfiles = count($addlist);
		$doubt = intval($doubt);

		$result .= '<script>function showresult(o) {'.$resultjs.'$(\'status_\' + o).style.display=\'\';}</script>';
		showtips('filecheck_tips');
		showtableheader('filecheck_completed');
		showtablerow('', 'colspan="4"', "<div class=\"lightfont filenum left\">".
			"<em class=\"edited\">$lang[filecheck_modify]: $modifiedfiles</em> ".($modifiedfiles > 0 ? "<a href=\"###\" onclick=\"showresult('modify')\">[$lang[view]]</a> " : '').
			"&nbsp;&nbsp;&nbsp;&nbsp;<em class=\"del\">$lang[filecheck_delete]: $deletedfiles</em> ".($deletedfiles > 0 ? "<a href=\"###\" onclick=\"showresult('del')\">[$lang[view]]</a> " : '').
			"&nbsp;&nbsp;&nbsp;&nbsp;<em class=\"unknown\">$lang[filecheck_unknown]: $unknownfiles</em> ".($unknownfiles > 0 ? "<a href=\"###\" onclick=\"showresult('add')\">[$lang[view]]</a> " : '').
			($doubt > 0 ? "&nbsp;&nbsp;&nbsp;&nbsp;<em class=\"unknown\">$lang[filecheck_doubt]: $doubt</em> <a href=\"###\" onclick=\"showresult('doubt')\">[$lang[view]]</a> " : '').
			"</div>");
		showsubtitle(array('filename', '', 'lastmodified', ''));
		echo $result;
		showtablefooter();

	}

} elseif($operation == 'ftpcheck') {

	$alertmsg = '';
	$testdir = substr(md5('Discuz!' + $timestamp), 12, 8);
	$testfile = 'discuztest.txt';
	$attach_dir = $attachdir;
	if($attachsave) {
		$attach_dir .= '/'.$testdir;
		if(!@mkdir($attach_dir, 0777)) {
			$alertmsg = lang('settings_attach_local_mderr');
		}
	}
	if(!$alertmsg) {
		if(!@fclose(fopen($attach_dir.'/'.$testfile, 'w'))) {
			$alertmsg = lang('settings_attach_local_uperr');
		} else {
			@unlink($attach_dir.'/'.$testfile);
		}
		$attachsave && @rmdir($attach_dir);
	}

	if(!$alertmsg) {
		require_once './include/ftp.func.php';
		if(!empty($settingsnew['ftp']['password'])) {
			$settings['ftp'] = unserialize($db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='ftp'"));
			$settings['ftp']['password'] = authcode($settings['ftp']['password'], 'DECODE', md5($authkey));
			$pwlen = strlen($settingsnew['ftp']['password']);
			if($settingsnew['ftp']['password']{0} == $settings['ftp']['password']{0} && $settingsnew['ftp']['password']{$pwlen - 1} == $settings['ftp']['password']{strlen($settings['ftp']['password']) - 1} && substr($settingsnew['ftp']['password'], 1, $pwlen - 2) == '********') {
				$settingsnew['ftp']['password'] = $settings['ftp']['password'];
			}
		}
		$ftp['pasv'] = intval($settingsnew['ftp']['pasv']);
		$ftp_conn_id = dftp_connect($settingsnew['ftp']['host'], $settingsnew['ftp']['username'], $settingsnew['ftp']['password'], $settingsnew['ftp']['attachdir'], $settingsnew['ftp']['port'], $settingsnew['ftp']['ssl'], 1);
		switch($ftp_conn_id) {
			case '-1':
				$alertmsg = $lang['settings_attach_remote_conerr'];
				break;
			case '-2':
				$alertmsg = $lang['settings_attach_remote_logerr'];
				break;
			case '-3':
				$alertmsg = $lang['settings_attach_remote_pwderr'];
				break;
			case '-4':
				$alertmsg = $lang['settings_attach_remote_ftpoff'];
				break;
			default:
				$alertmsg = '';
		}
	}

	if(!$alertmsg) {
		if(!dftp_mkdir($ftp_conn_id, $testdir)) {
			$alertmsg = $lang['settings_attach_remote_mderr'];
		} else {
			if(!(function_exists('ftp_chmod') && dftp_chmod($ftp_conn_id, 0777, $testdir)) && !dftp_site($ftp_conn_id, "'CHMOD 0777 $testdir'") && !@ftp_exec($ftp_conn_id, "SITE CHMOD 0777 $testdir")) {
				$alertmsg = $lang['settings_attach_remote_chmoderr'].'\n';
			}
			$testfile = $testdir.'/'.$testfile;
			if(!dftp_put($ftp_conn_id, $testfile, DISCUZ_ROOT.'./robots.txt', FTP_BINARY)) {
				$alertmsg .= $lang['settings_attach_remote_uperr'];
				dftp_delete($ftp_conn_id, $testfile);
				dftp_delete($ftp_conn_id, $testfile.'.uploading');
				dftp_delete($ftp_conn_id, $testfile.'.abort');
				dftp_rmdir($ftp_conn_id, $testdir);
			} else {
				if(!@readfile($settingsnew['ftp']['attachurl'].'/'.$testfile)) {
					$alertmsg .= $lang['settings_attach_remote_geterr'];
					dftp_delete($ftp_conn_id, $testfile);
					dftp_rmdir($ftp_conn_id, $testdir);
				} else {
					if(!dftp_delete($ftp_conn_id, $testfile)) {
						$alertmsg .= $lang['settings_attach_remote_delerr'];
					} else {
						dftp_rmdir($ftp_conn_id, $testdir);
						$alertmsg = $lang['settings_attach_remote_ok'];
					}
				}
			}
		}
	}
	echo '<script language="javascript">alert(\''.str_replace('\'', '\\\'', $alertmsg).'\');parent.$(\'cpform\').action=\''.$BASESCRIPT.'?action=settings&edit=yes\';parent.$(\'cpform\').target=\'_self\'</script>';

} elseif($operation == 'mailcheck') {

	$oldmail = unserialize($mail);
	$passwordmask = $oldmail['auth_password'] ? $oldmail['auth_password']{0}.'********'.substr($oldmail['auth_password'], -2) : '';
	$settingsnew['mail']['auth_password'] = $settingsnew['mail']['auth_password'] == $passwordmask ? $oldmail['auth_password'] : $settingsnew['mail']['auth_password'];
	$mail = serialize($settingsnew['mail']);
	$test_tos = explode(',', $test_to);
	$date = date('Y-m-d H:i:s');
	$alertmsg = '';

	$title = $lang['settings_mailcheck_title_'.$settingsnew['mail']['mailsend']];
	$message = $lang['settings_mailcheck_message_'.$settingsnew['mail']['mailsend']].' '.$test_from.$lang['settings_mailcheck_date'].' '.$date;

	$bbname = $lang['settings_mail_check_method_1'];
	sendmail($test_tos[0], $title.' @ '.$date, "$bbname\n\n\n$message", $test_from);
	$bbname = $lang['settings_mail_check_method_2'];
	sendmail($test_to, $title.' @ '.$date, "$bbname\n\n\n$message", $test_from);

	if(!$alertmsg) {
		$alertmsg = $lang['settings_mail_check_success_1']."$title @ $date".$lang['settings_mail_check_success_2'];
	} else {
		$alertmsg = $lang['settings_mail_check_error'].$alertmsg;
	}

	echo '<script language="javascript">alert(\''.str_replace(array('\'', "\n", "\r"), array('\\\'', '\n', ''), $alertmsg).'\');parent.$(\'cpform\').action=\''.$BASESCRIPT.'?action=settings&edit=yes\';parent.$(\'cpform\').target=\'_self\'</script>';

} elseif($operation == 'imagepreview') {

	if(!empty($previewthumb)) {
		$thumbstatus = $settingsnew['thumbstatus'];
		if(!$thumbstatus) {
			cpmsg('thumbpreview_error', '', 'error');
		}
		$imagelib = $settingsnew['imagelib'];
		$imageimpath = $settingsnew['imageimpath'];
		$thumbwidth = $settingsnew['thumbwidth'];
		$thumbheight = $settingsnew['thumbheight'];
		$thumbquality = $settingsnew['thumbquality'];

		require_once DISCUZ_ROOT.'./include/image.class.php';
		@unlink(DISCUZ_ROOT.'./forumdata/watermark_temp.jpg');
		$image = new Image('images/admincp/watermarkpreview.jpg', 'images/admincp/watermarkpreview.jpg');
		$image->Thumb($thumbwidth, $thumbheight, 1);
		if(file_exists(DISCUZ_ROOT.'./forumdata/watermark_temp.jpg')) {
			showsubmenu('imagepreview_thumb');
			$sizesource = filesize('images/admincp/watermarkpreview.jpg');
			$sizetarget = filesize(DISCUZ_ROOT.'./forumdata/watermark_temp.jpg');
			echo '<img src="forumdata/watermark_temp.jpg?'.random(5).'"><br /><br />'.
				$lang['imagepreview_imagesize_source'].' '.number_format($sizesource).' Bytes &nbsp;&nbsp;'.
				$lang['imagepreview_imagesize_target'].' '.number_format($sizetarget).' Bytes ('.
				(sprintf("%2.1f", $sizetarget / $sizesource * 100)).'%)';
		} else {
			cpmsg('thumbpreview_createerror', '', 'error');
		}
	} else {
		$watermarkstatus = $settingsnew['watermarkstatus'];
		if(!$watermarkstatus) {
			cpmsg('watermarkpreview_error', '', 'error');
		}
		$imagelib = $settingsnew['imagelib'];
		$imageimpath = $settingsnew['imageimpath'];
		$watermarktype = $settingsnew['watermarktype'];
		$watermarktrans = $settingsnew['watermarktrans'];
		$watermarkquality = $settingsnew['watermarkquality'];
		$watermarkminwidth = $settingsnew['watermarkminwidth'];
		$watermarkminheight = $settingsnew['watermarkminheight'];
		$settingsnew['watermarktext']['size'] = intval($settingsnew['watermarktext']['size']);
		$settingsnew['watermarktext']['angle'] = intval($settingsnew['watermarktext']['angle']);
		$settingsnew['watermarktext']['shadowx'] = intval($settingsnew['watermarktext']['shadowx']);
		$settingsnew['watermarktext']['shadowy'] = intval($settingsnew['watermarktext']['shadowy']);
		$settingsnew['watermarktext']['translatex'] = intval($settingsnew['watermarktext']['translatex']);
		$settingsnew['watermarktext']['translatey'] = intval($settingsnew['watermarktext']['translatey']);
		$settingsnew['watermarktext']['skewx'] = intval($settingsnew['watermarktext']['skewx']);
		$settingsnew['watermarktext']['skewy'] = intval($settingsnew['watermarktext']['skewy']);
		$settingsnew['watermarktext']['fontpath'] = str_replace(array('\\', '/'), '', $settingsnew['watermarktext']['fontpath']);
		$settingsnew['watermarktext']['color'] = preg_replace('/#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/e', "hexdec('\\1').','.hexdec('\\2').','.hexdec('\\3')", $settingsnew['watermarktext']['color']);
		$settingsnew['watermarktext']['shadowcolor'] = preg_replace('/#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/e', "hexdec('\\1').','.hexdec('\\2').','.hexdec('\\3')", $settingsnew['watermarktext']['shadowcolor']);

		if($watermarktype == 2) {
			if($settingsnew['watermarktext']['fontpath']) {
				$fontpath = $settingsnew['watermarktext']['fontpath'];
				$fontpathnew = 'ch/'.$fontpath;
				$settingsnew['watermarktext']['fontpath'] = file_exists('images/fonts/'.$fontpathnew) ? $fontpathnew : '';
				if(!$settingsnew['watermarktext']['fontpath']) {
					$fontpathnew = 'en/'.$fontpath;
					$settingsnew['watermarktext']['fontpath'] = file_exists('images/fonts/'.$fontpathnew) ? $fontpathnew : '';
				}
				if(!$settingsnew['watermarktext']['fontpath']) {
					cpmsg('watermarkpreview_fontpath_error', '', 'error');
				}
				$settingsnew['watermarktext']['fontpath'] = 'images/fonts/'.$settingsnew['watermarktext']['fontpath'];
			}

			if($settingsnew['watermarktext']['text'] && strtoupper($charset) != 'UTF-8') {
				include DISCUZ_ROOT.'include/chinese.class.php';
				$c = new Chinese($charset, 'utf8');
				$settingsnew['watermarktext']['text'] = $c->Convert($settingsnew['watermarktext']['text']);
			}
			$settingsnew['watermarktext']['text'] = bin2hex($settingsnew['watermarktext']['text']);
			$watermarktext = $settingsnew['watermarktext'];
		}

		require_once DISCUZ_ROOT.'./include/image.class.php';
		@unlink(DISCUZ_ROOT.'./forumdata/watermark_temp.jpg');
		$image = new Image('images/admincp/watermarkpreview.jpg', 'images/admincp/watermarkpreview.jpg');
		$image->Watermark(1);
		if(file_exists(DISCUZ_ROOT.'./forumdata/watermark_temp.jpg')) {
			showsubmenu('imagepreview_watermark');
			$sizesource = filesize('images/admincp/watermarkpreview.jpg');
			$sizetarget = filesize(DISCUZ_ROOT.'./forumdata/watermark_temp.jpg');
			echo '<img src="forumdata/watermark_temp.jpg?'.random(5).'"><br /><br />'.
				$lang['imagepreview_imagesize_source'].' '.number_format($sizesource).' Bytes &nbsp;&nbsp;'.
				$lang['imagepreview_imagesize_target'].' '.number_format($sizetarget).' Bytes ('.
				(sprintf("%2.1f", $sizetarget / $sizesource * 100)).'%)';
		} else {
			cpmsg('watermarkpreview_createerror', '', 'error');
		}
	}

}

function checkfiles($currentdir, $ext = '', $sub = 1, $skip = '') {
	global $md5data;
	$dir = @opendir(DISCUZ_ROOT.$currentdir);
	$exts = '/('.$ext.')$/i';
	$skips = explode(',', $skip);

	while($entry = @readdir($dir)) {
		$file = $currentdir.$entry;
		if($entry != '.' && $entry != '..' && (preg_match($exts, $entry) || $sub && is_dir($file)) && !in_array($entry, $skips)) {
			if($sub && is_dir($file)) {
				checkfiles($file.'/', $ext, $sub, $skip);
			} else {
				$md5data[$file] = md5_file($file);
			}
		}
	}
}

function checkcachefiles($currentdir) {
	global $authkey;
	$dir = opendir($currentdir);
	$exts = '/\.php$/i';
	$showlist = $modifylist = $addlist = array();
	while($entry = readdir($dir)) {
		$file = $currentdir.$entry;
		if($entry != '.' && $entry != '..' && preg_match($exts, $entry)) {
			$fp = fopen($file, "rb");
			$cachedata = fread($fp, filesize($file));
			fclose($fp);

			if(preg_match("/^<\?php\n\/\/Discuz! cache file, DO NOT modify me!\n\/\/Created: [\w\s,:]+\n\/\/Identify: (\w{32})\n\n(.+?)\?>$/s", $cachedata, $match)) {
				$showlist[$file] = $md5 = $match[1];
				$cachedata = $match[2];

				if(md5($entry.$cachedata.$authkey) != $md5) {
					$modifylist[$file] = $md5;
				}
			} else {
				$showlist[$file] = $addlist[$file] = '';
			}
		}

	}

	return array($showlist, $modifylist, $addlist);
}

function checkmailerror($type, $error) {
	global $alertmsg;
	$alertmsg .= !$alertmsg ? $error : '';
}

?>