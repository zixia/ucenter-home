<?php
	/*
	[UCenter Home] (C) 2007-2008 Comsenz Inc.
	$Id: install.func.php 10767 2008-12-19 00:54:38Z zhaolei $
*/

function install_header() {
	global $lang;
	$charset = CHARSET;
   	$uri = $_SERVER['REQUEST_URI']?$_SERVER['REQUEST_URI']:($_SERVER['PHP_SELF']?$_SERVER['PHP_SELF']:$_SERVER['SCRIPT_NAME']);
	$siteurl = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))).'://'.$_SERVER['HTTP_HOST'].preg_replace("/\/*install$/i", '', substr($uri, 0, strrpos($uri, '/install')));
	echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=$charset" />
<title>$lang[install_wizard]</title>
<link rel="stylesheet" href="css/setup.css" type="text/css" media="all" />
<meta content="Comsenz Inc." name="Copyright" />
<script src="ajax.js" type="text/javascript" language="javascript" charset="utf-8"></script>

<script type="text/javascript">

var siteUrl = '$siteurl';
function checkpass() {

	var pass = $('ucfounderpw').value;
	var pass1 = $('ucfounderpw1').value;
	var upass = $('userpw').value;
	var upass1 = $('userpw1').value;
	if(pass == '' || pass != pass1) {
		alert('$lang[pass_uc_check]');
		return false;
	}
	if(upass == '' || upass != upass1) {
		alert('$lang[pass_admin_check]');
		return false;
	}

	initdat();
	return false;
}
</script>
</head>
<body>
<div class="container">
END;
}
function install_footer() {

	echo <<<END
				<div class="footer">&copy;2001-2008 <a href="http://www.comsenz.com/" target="_blank">Comsenz</a> Inc.</div>
			</div>
		</div>
	</body>
	</html>
END;
}
function checkfdperm($path, $isfile=0) {
	if($isfile) {
		$file = $path;
		$mod = 'a';
	} else {
		$file = $path.'./install_tmptest.data';
		$mod = 'w';
	}
	if(!@$fp = fopen($file, $mod)) {
		return false;
	}
	if(!$isfile) {
		//是否可以删除
		fwrite($fp, ' ');
		fclose($fp);
		if(!@unlink($file)) {
			return false;
		}
		//检测是否可以创建子目录
		if(is_dir($path.'./install_tmpdir')) {
			if(!@rmdir($path.'./install_tmpdir')) {
				return false;
			}
		}
		if(!@mkdir($path.'./install_tmpdir')) {
			return false;
		}
		//是否可以删除
		if(!@rmdir($path.'./install_tmpdir')) {
			return false;
		}
	} else {
		fclose($fp);
	}
	return true;
}

function getgpc($k, $var='G') {
	switch($var) {
		case 'G': $var = &$_GET; break;
		case 'P': $var = &$_POST; break;
		case 'C': $var = &$_COOKIE; break;
		case 'R': $var = &$_REQUEST; break;
	}
	return isset($var[$k]) ? $var[$k] : '';
}

function show_msg($message, $next=0) {
	global $theurl, $lang;

	$nextstr = '';
	$backstr = '';

	if(empty($next)) {
		$backstr = '<a href=\"javascript:history.go(-1);\">'.$lang['message_return'].'</a>';
	}

	print<<<END
	<table>
	<tr><td>$message</td></tr>
	<tr><td>$backstr $nextstr</td></tr>
	</table>
END;
	exit();
}

function submitchecki($var) {
	if(!empty($_POST[$var]) && $_SERVER['REQUEST_METHOD'] == 'POST') {
		return true;
	} else {
		return false;
	}
}

//产生form防伪码
function formhash() {
	global $_SGLOBAL, $_SCONFIG;

	$mtime = explode(' ', microtime());
	$_SGLOBAL['timestamp'] = $mtime[1];

	if(empty($_SGLOBAL['formhash'])) {
		$hashadd = defined('IN_ADMINCP') ? 'Only For UCenter Home AdminCP' : '';
		$_SGLOBAL['formhash'] = substr(md5(substr($_SGLOBAL['timestamp'], 0, -7).'|'.$_SGLOBAL['supe_uid'].'|'.md5($_SCONFIG['sitekey']).'|'.$hashadd), 8, 8);
	}

	return $_SGLOBAL['formhash'];
}
?>