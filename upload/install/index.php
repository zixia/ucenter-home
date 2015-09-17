<?php
/*
	(C) 2007-2008 Comsenz Inc.
	$Id: index.php 10948 2009-01-09 05:10:46Z zhaolei $
*/

error_reporting(0);

define('DBCHARSET', 'utf8');
define('CHARSET', 'utf8');
define('UC_API',true);

include_once('./install.func.php');
include_once('./install.lang.php');
include_once('./db.class.php');

$step = empty($_GET['step'])? 1 : intval($_GET['step']);

$dbcharset = DBCHARSET;

$dirname = substr(dirname(__FILE__), 0, -7);
$ucenter = 'ucenter';
$bbs = 'bbs';
$uchome = 'home';

define('UC_ROOT', $dirname.'./'.$ucenter.'/');
define('S_ROOT', $dirname.'./'.$uchome.'/');
define('DISCUZ_ROOT', $dirname.'./'.$bbs.'/');

$uri = $_SERVER['REQUEST_URI']?$_SERVER['REQUEST_URI']:($_SERVER['PHP_SELF']?$_SERVER['PHP_SELF']:$_SERVER['SCRIPT_NAME']);
$uri = substr($uri, 0, strrpos($uri, '/'));
$uri = substr($uri,0,-7);
$theurl = 'http://'.$_SERVER['HTTP_HOST'].$uri;

if(submitchecki('dbtest')) {

    $dbhost = trim($_POST['dbhost']);
    $dbuser = trim($_POST['dbuser']);
    $dbpw = trim($_POST['dbpw']);
    $dbname = trim($_POST['dbname']);
	$link = mysql_connect($dbhost, $dbuser, $dbpw);
	if(empty($link)) {
		echo 1;
		exit;
	}
	if(!mysql_select_db($dbname, $link)) {
		$dberror = '';
		mysql_query("CREATE DATABASE `$dbname`");
		$dberror = mysql_error();
		if(!empty($dberror)) {
			echo 2;
			exit;
		}
	}

	if($query = mysql_query("SHOW TABLES FROM $dbname")) {
		while($row = mysql_fetch_row($query)) {
			if(preg_match("/^uc_/", $row[0])) {
				echo 3;
				exit;
			}
		}
	}

	echo 0;
	mysql_close($link);
	exit;
}

install_header();

$setuppass = true;
$langstep = $lang['step_'.$step];

echo <<<END
	<div class="header">
		<h1>$lang[install_wizard]</h1>
		<div class="setup step$step">
			<h2>$langstep</h2>
		</div>

	</div>
	<div class="main">
END;

if($step == 1) {
	if(file_exists('install.lock')) {
		echo '<h4>'.$lang['all_install'].'<h4>';
		install_footer();
		exit;
	} elseif(file_exists(DISCUZ_ROOT.'./forumdata/install.lock')) {
		echo '<h4>'.$lang['discuz_install'].'<h4>';
		install_footer();
		exit;
	} elseif(file_exists(UC_ROOT.'./data/install.lock')) {
		echo '<h4>'.$lang['ucenter_install'].'<h4>';
		install_footer();
		exit;
	} elseif(file_exists(S_ROOT.'./data/install.lock')) {
		echo '<h4>'.$lang['ucenterhome_install'].'<h4>';
		install_footer();
		exit;
	}elseif (!ini_get('short_open_tag')) {
		echo '<h4>'.$lang['short_open_tag_invalid'].'<h4>';
		install_footer();
		exit;
	}

	$phpos = PHP_OS;
	$result = array();
	if(function_exists('mysql_connect')) {
		$result['mysql'] = '<td class="w pdleft1">'.$lang['supportted'].'</td>';
	} else {
		$result['mysql'] = '<td class="nw pdleft1">'.$lang['unsupportted'].'</td>';
		$pass = FALSE;
	}
	if(PHP_VERSION > '4.3.0') {
		$result['phpversion'] = '<td class="w pdleft1">'.PHP_VERSION.'</td>';
	} else {
		$result['phpversion'] = '<td class="nw pdleft1">'.PHP_VERSION.'</td>';
		$pass = FALSE;
	}
	if(@ini_get(file_uploads)) {
		$max_size = @ini_get(upload_max_filesize);
		$result['upload'] = '<td class="w pdleft1">'.$lang['max_size'].$max_size.'</td>';
	} else {
		$result['upload'] = '<td class="nw pdleft1">'.$lang['unsupportted'].'</td>';
		$pass = FALSE;
	}
	$curr_disk_space = intval(diskfreespace('.') / (1024 * 1024)).'M';
	if($curr_disk_space > 10) {
		$result['diskfree'] = '<td class="w pdleft1">'.$curr_disk_space.'</td>';
	} else {
		$result['diskfree'] = '<td class="nw pdleft1">'.$curr_disk_space.'</td>';
		$pass = FALSE;
	}
	echo <<<END
	<div class="main">
		<h2 class="title">$lang[env_check]</h2>
		<table class="tb" style="margin:20px 0 20px 55px;">
			<tr>
				<th>$lang[project]</th>
				<th class="padleft">$lang[ucenter_required]</th>
				<th class="padleft">$lang[ucenter_best]</th>
				<th class="padleft">$lang[curr_server]</th>
			</tr>
			<tr>
				<td>$lang[os]</td>
				<td class="padleft">$lang[unlimit]</td>
				<td class="padleft">UNIX/Linux/FreeBSD</td>
				<td class="padleft">$phpos</td>
			</tr>
			<tr>
				<td>PHP $lang[version]</td>
				<td class="padleft">4.3.0+</td>
				<td class="padleft">5.0.0+</td>
				$result[phpversion]
			</tr>
			<tr>
				<td>$lang[attach_upload]</td>
				<td class="padleft">$lang[allow]</td>
				<td class="padleft">$lang[allow]</td>
				$result[upload]
			</tr>
			<tr>
				<td>MySQL $lang[supportted]</td>
				<td class="padleft">MySQL4.0+</td>
				<td class="padleft">MySQL5.0+</td>
				$result[mysql]
			</tr>
			<tr>
				<td>$lang[disk_free]</td>
				<td class="padleft">30M+</td>
				<td class="padleft">$lang[unlimit]</td>
				$result[diskfree]
			</tr>
		</table>
END;
	echo <<<END
	<h2 class="title">$lang[func_depend]</h2>
		<table class="tb" style="margin:20px 0 20px 55px;width:90%;">
			<tr>
				<th>$lang[func_name]</th>
				<th class="padleft">$lang[check_result]</th>
				<th class="padleft">$lang[suggestion]</th>
			</tr>
END;
	$functions = array('mysql_connect'=>FALSE, 'fsockopen'=>FALSE, 'gethostbyname'=>FALSE, 'file_get_contents'=>FALSE, 'xml_parser_create'=>FALSE);
	$advices = array(
		'mysql_connect' => $lang['advice_mysql'],
		'fsockopen' => $lang['advice_fopen'],
		'file_get_contents' => $lang['advice_file_get_contents'],
		'xml_parser_create' => $lang['advice_xml']
	);
	foreach($functions as $name=>$status) {
		$status = function_exists($name);
		echo '<tr><td>'.$name.'()</td>'.($status ? '<td class="w pdleft1">'.$lang['supportted'].'</td>' : '<td class="nw pdleft1">'.$lang['unsupportted'].'</td>').($status ? '<td class="padleft">'.$lang['none'].'</td>' : '<td><font color="red">'.$advices[$name].'</font></td>').'</tr>';
	}
	echo '</table>';
	//UCenter 的安装检测
	$dirs = array('/data/', '/data/cache/', '/data/view/', '/data/avatar/', '/data/logs/', '/data/backup/', '/data/tmp/');
	echo <<<END
		<h2 class="title">$lang[priv_check]</h2>
		<table class="tb" style="margin:20px 0 20px 55px;width:90%;">
			<tr>
				<th width="61%">$lang[step1_file]</th>
				<th class="padleft">$lang[step1_need_status]</th>
				<th class="padleft">$lang[step1_status]</th>
			</tr>
END;

	if(!checkfdperm(UC_ROOT.'/data/config.inc.php',1)) {
		$setuppass = false;
		$checkok = false;
	} else {
		$checkok = true;
	}
	echo '<tr><td>./'.$ucenter.'/data/config.inc.php</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';

	foreach($dirs as $dir) {
		if(!checkfdperm(UC_ROOT.$dir)) {
			$setuppass = false;
			$checkok = false;
		} else {
			$checkok = true;
		}
		echo '<tr><td>./'.$ucenter.$dir.'</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';
	}

	//UCenter_Home的信息检查

	$checkok = false;
	if(!checkfdperm(S_ROOT.'./config.php', 1)) {
		$setuppass = false;
		$checkok = false;
	} else {
		$checkok = true;
	}
	echo '<tr><td>./'.$uchome.'/config.php</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';
	if(!checkfdperm(S_ROOT.'./attachment/')) {
		$setuppass = false;
		$checkok = false;
	} else {
		$checkok = true;
	}
	echo '<tr><td>./'.$uchome.'/attachment/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';
	if(!checkfdperm(S_ROOT.'./data/')) {
		$setuppass = false;
		$checkok = false;
	} else {
		$checkok = true;
	}
	echo '<tr><td>./'.$uchome.'/data/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';
	if(!checkfdperm(S_ROOT.'./uc_client/data/')) {
		$setuppass = false;
		$checkok = false;
	} else {
		$checkok = true;
	}
	echo '<tr><td>./'.$uchome.'/uc_client/data/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';

	//DISCUZ 文件权限检测
	$checkok = false;
	if(checkfdperm(DISCUZ_ROOT.'./config.inc.php',1)) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}

	echo '<tr><td>./'.$bbs.'/config.inc.php</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';
	if(checkfdperm(DISCUZ_ROOT.'./attachments/')) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}
	echo '<tr><td>./'.$bbs.'/attachments/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';
	if(checkfdperm(DISCUZ_ROOT.'./templates/')) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}
	echo '<tr><td>./'.$bbs.'/templates/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';

	if(checkfdperm(DISCUZ_ROOT.'./forumdata/')) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}

	echo '<tr><td>./'.$bbs.'/forumdata/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';

	if(checkfdperm(DISCUZ_ROOT.'./forumdata/cache/')) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}

	echo '<tr><td>./'.$bbs.'/forumdata/cache/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';

	if(checkfdperm(DISCUZ_ROOT.'./forumdata/templates/')) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}

	echo '<tr><td>./'.$bbs.'/forumdata/templates/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';

	if(checkfdperm(DISCUZ_ROOT.'./forumdata/threadcaches/')) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}

	echo '<tr><td>./'.$bbs.'/forumdata/threadcaches/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';

	if(checkfdperm(DISCUZ_ROOT.'./forumdata/logs/')) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}

	echo '<tr><td>./'.$bbs.'/forumdata/logs/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';
	if(checkfdperm(DISCUZ_ROOT.'./uc_client/data/cache/')) {
		$checkok = true;
	} else {
		$setuppass = false;
		$checkok = false;
	}

	echo '<tr><td>./'.$bbs.'/uc_client/data/cache/</td><td class="w pdleft1">'.$lang['writeable'].'</td><td'.($checkok ? ' class="w pdleft1">'.$lang['writeable'] : ' class="nw pdleft1">'.$lang['unwriteable']).'</td></tr>';
	echo '</table>';
	echo '<form action="index.php?step=2" method="post">';
	if($setuppass) {
		$nextstep = ' <input type="button" onclick="history.back();" value="'.$lang['old_step'].'"><input type="submit" value="'.$lang['new_step'].'">';
	} else {
		$nextstep = ' <input type="button" disabled="disabled" value="'.$lang['step1_unwriteable'].'">';
	}
	echo '<div class="btnbox marginbot"> '.$nextstep.'</div>';
	echo '</form>';
} elseif($step == 2) {

	if(file_exists('install.lock')) {
		echo '<h4>'.$lang['all_install'].'<h4>';
		install_footer();
		exit;
	} elseif(file_exists(DISCUZ_ROOT.'./forumdata/install.lock')) {
		echo '<h4>'.$lang['discuz_install'].'<h4>';
		install_footer();
		exit;
	} elseif(file_exists(UC_ROOT.'./data/install.lock')) {
		echo '<h4>'.$lang['ucenter_install'].'<h4>';
		install_footer();
		exit;
	} elseif(file_exists(S_ROOT.'./data/install.lock')) {
		echo '<h4>'.$lang['ucenterhome_install'].'<h4>';
		install_footer();
		exit;
	}

	$formhash = formhash();
	echo <<<END
		<div id="status">
		</div>
		<div id="app" style="margin:auto; text-align:left; width:300px; height:400px; display:none;">
			<div id="status">
			</div>
			<div id="ucenter" >
			</div>
			<div id="home">
			</div>
			<div id="bbs">
			</div>
			<form action="index.php?step=3" method="post">
			<div id="indexselect" style="display:none;">
				<div><input type="radio" checked="true" value="index" name="index"/>$lang[setting_index]</div>
				<div><input type="radio" value="discuz" name="index"/>$lang[index_discuz]</div>
				<div><input type="radio" value="uchome" name="index"/>$lang[index_home]</div>
                <br />
				<div><input type="submit" value="$lang[step_3]"  /></div>
			</div>
			</form>

		</div>
		<form method="post" action="index.php" id="setupinfoform" name="setupinfo" >
		<input type="hidden" name="dbsubmit" value="1"  />
		<h2 class="title">$lang[step_2]</h2>
		<table class="tb2">
			<tr>
				<th>$lang[step2_dbhost]:</th>
				<td><input name="dbhost" type="text" class="txt" value="localhost" /></td>
			</tr>
			<tr>
				<th>$lang[step2_dbname]</th>
				<td><input name="dbname" type="text" class="txt" value="" /></td>
			</tr>
			<tr>
				<th>$lang[step2_dbuser]:</th>
				<td><input name="dbuser" type="text" class="txt" value="" /></td>
			</tr>
			<tr>
				<th>$lang[step2_dbpw]:</th>
				<td><input name="dbpw" type="password" class="txt" value="" /></td>
			</tr>
		</table>
		<h2 class="title">$lang[uc_info]</h2>
		<table class="tb2">
			<tr>
				<th >$lang[step3_desc]:</th>
				<td><input name="ucfounderpw" id="ucfounderpw"  type="password" class="txt" value="" /></td>
			</tr>
			<tr>
				<th >$lang[check_password]:</th>
				<td><input name="ucfounderpw1" id="ucfounderpw1" type="password" class="txt" value="" /></td>
			</tr>
		</table>
		<h2 class="title">$lang[uchome_bbs_admin]</h2>
		<table class="tb2">
			<tr>
				<th>$lang[step3_username]:</th>
				<td ><input name="username" type="text" class="txt" value="admin" /></td>
			</tr>
			<tr>
				<th >$lang[step3_password]:</th>
				<td><input name="userpw" id="userpw" type="password" class="txt" value="" /></td>
			</tr>
			<tr>
				<th >$lang[check_password]:</th>
				<td><input name="userpw1" id="userpw1" type="password" class="txt" value="" /></td>
			</tr>
			<tr>
				<th class="tbopt">$lang[admin_email]:</th>
				<td><input type="text" name="adminemail" value="admin@admin.com" size="35" class="txt"></td>
			</tr>
		</table>
		<div class="btnbox marginbot">
		<input type="hidden" name="formhash" value="$formhash" />
		<input type="hidden" name="dbcharset" value="$dbcharset" />
		<input type="button" name="dbsubmit" value="$lang[setup]" onclick="checkpass();" /></div>
		</form>
END;

} elseif($step == 3) {
	$index = getgpc('index', 'P');
	if($index == 'discuz') {
		@touch(DISCUZ_ROOT.'./forumdata/index.lock');
		@unlink(S_ROOT.'./data/index.lock');
	}elseif($index == 'uchome') {
		@touch(S_ROOT.'./data/index.lock');
		@unlink(DISCUZ_ROOT.'./forumdata/index.lock');
	}
	touch('./install.lock');

	$setupinfo = "$lang[ucenter_dir]:\n".$theurl.$ucenter."\n\n$lang[step3_desc]:".$_POST['ucf'];
	$setupinfo .= "\n\n\n$lang[discuz_dir]:\n".$theurl.$bbs;
	$setupinfo .= "\n\n$lang[admin_url]:\n".$theurl.$bbs.'/admincp.php'."\n\n$lang[step3_username]:".$_POST['admin'].' '.$lang['step3_password'].':'.$_POST['pass'];
	$setupinfo .= "\n\n\n$lang[ucenterhome_dir]:\n".$theurl.$uchome;
	$setupinfo .= "\n\n$lang[admin_url]:\n".$theurl.$uchome.'/admincp.php'."\n\n$lang[step3_username]:".$_POST['admin'].' '.$lang['step3_password'].':'.$_POST['pass'];
	echo '<h3>'.$lang['save_setup_info'].'</h3>';
	echo '<textarea cols="104"  rows="14" id="info" >'.$setupinfo.'</textarea>';
	echo ' <div class="btnbox marginbot"><input type="button" onclick="setCopy($(\'info\').value);" value="'.$lang['copy'].'"><input type="button" onclick="window.open(\''.$theurl.'\',\'_blank\');" value="'.$lang['go_home'].'"></div>';
}
install_footer();
?>