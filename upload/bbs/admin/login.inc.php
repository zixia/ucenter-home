<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: login.inc.php 20196 2009-09-21 13:29:23Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

if($inajax) {
	ajaxshowheader();
	ajaxshowfooter();
	exit;
}

include language('admincp.menu');
echo <<<EOT

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>$menulang[admincp_title]</title>
<meta http-equiv="Content-Type" content="text/html;charset=$charset" />
<link rel="stylesheet" href="images/admincp/admincp.css" type="text/css" media="all" />
<meta content="Comsenz Inc." name="Copyright" />
</head>
<body>
<script language="JavaScript">
	if(self.parent.frames.length != 0) {
		self.parent.location=document.location;
	}
</script>
<table class="logintb">
<tr>
	<td class="login">
		<h1>Discuz! Administrator's Control Panel</h1>
		<p>$lang[login_tips]</p>
	</td>

	<td>
EOT;

if($cpaccess == 0 || (!$discuz_secques && $admincp['forcesecques'])) {
	echo '<p class="logintips">'.$lang[$cpaccess == 0 ? 'noaccess' : 'login_nosecques'].'</p>';
} elseif($cpaccess == 1) {
	$extra = '?'.(isset($action) && empty($frames) ? 'frames=yes&' : '').$_SERVER['QUERY_STRING'];
	echo <<<EOT
		<form method="post" name="login" id="loginform" action="$BASESCRIPT$extra">
		<input type="hidden" name="sid" value="$sid">
		<input type="hidden" name="frames" value="yes">
		<p class="logintitle">$lang[username]: </p>
		<p class="loginform">$discuz_userss</p>
		<p class="logintitle">$lang[password]:</p>
		<p class="loginform"><input name="admin_password" tabindex="1" type="password" class="txt" autocomplete="off" /></p>
		<p class="logintitle">$lang[security_question]:</p>
		<p class="loginform">
			<select id="questionid" name="admin_questionid" tabindex="2">
				<option value="0">$lang[security_question_0]</option>
				<option value="1">$lang[security_question_1]</option>
				<option value="2">$lang[security_question_2]</option>
				<option value="3">$lang[security_question_3]</option>
				<option value="4">$lang[security_question_4]</option>
				<option value="5">$lang[security_question_5]</option>
				<option value="6">$lang[security_question_6]</option>
				<option value="7">$lang[security_question_7]</option>
			</select>
		</p>
		<p class="logintitle">$lang[security_answer]:</p>
		<p class="loginform"><input name="admin_answer" tabindex="3" type="text" class="txt" autocomplete="off" /></p>
		<p class="loginnofloat"><input name="submit" value="$lang[submit]"  tabindex="3" type="submit" class="btn" /></p>
		</form>
		<script type="text/JavaScript">document.getElementById('loginform').admin_password.focus();</script>
EOT;

}

echo <<<EOT

	</td>
</tr>

<tr>
	<td colspan="2" class="footer">
		<div class="copyright">
			<p>Powered by <a href="http://www.discuz.net/" target="_blank">Discuz!</a> $version </p>
			<p>&copy; 2001-2009, <a href="http://www.comsenz.com/" target="_blank">Comsenz</a> Inc.</p>
		</div>
	</td>
</tr>
</table>
</body>
</html>

EOT;

?>