<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: extvar.inc.php 21158 2009-11-18 00:52:28Z monkey $
*/

$attachdir = './attachments';
$attachurl = 'attachments';

$cron_pushthread_week = rand(1, 7);
$cron_pushthread_hour = rand(1, 8);

$extcredits = Array
(
	1 => Array
	(
		'title' => $lang['init_credits_karma'],
		'showinthread' => '',
		'available' => 1
	),
	2 => Array
	(
		'title' => $lang['init_credits_money'],
		'showinthread' => '',
		'available' => 1
	)
);

$extrasql = <<<EOT
UPDATE cdb_forumlinks SET name='$lang[init_link]', description='$lang[init_link_note]' WHERE id='1';

UPDATE cdb_forums SET name='$lang[init_default_forum]' WHERE fid='2';

UPDATE cdb_onlinelist SET title='$lang[init_group_1]' WHERE groupid='1';
UPDATE cdb_onlinelist SET title='$lang[init_group_2]' WHERE groupid='2';
UPDATE cdb_onlinelist SET title='$lang[init_group_3]' WHERE groupid='3';
UPDATE cdb_onlinelist SET title='$lang[init_group_0]' WHERE groupid='0';

UPDATE cdb_ranks SET ranktitle='$lang[init_rank_1]' WHERE rankid='1';
UPDATE cdb_ranks SET ranktitle='$lang[init_rank_2]' WHERE rankid='2';
UPDATE cdb_ranks SET ranktitle='$lang[init_rank_3]' WHERE rankid='3';
UPDATE cdb_ranks SET ranktitle='$lang[init_rank_4]' WHERE rankid='4';
UPDATE cdb_ranks SET ranktitle='$lang[init_rank_5]' WHERE rankid='5';

UPDATE cdb_usergroups SET grouptitle='$lang[init_group_1]' WHERE groupid='1';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_2]' WHERE groupid='2';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_3]' WHERE groupid='3';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_4]' WHERE groupid='4';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_5]' WHERE groupid='5';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_6]' WHERE groupid='6';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_7]' WHERE groupid='7';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_8]' WHERE groupid='8';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_9]' WHERE groupid='9';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_10]' WHERE groupid='10';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_11]' WHERE groupid='11';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_12]' WHERE groupid='12';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_13]' WHERE groupid='13';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_14]' WHERE groupid='14';
UPDATE cdb_usergroups SET grouptitle='$lang[init_group_15]' WHERE groupid='15';

UPDATE cdb_crons SET name='$lang[init_cron_1]' WHERE cronid='1';
UPDATE cdb_crons SET name='$lang[init_cron_2]' WHERE cronid='2';
UPDATE cdb_crons SET name='$lang[init_cron_3]' WHERE cronid='3';
UPDATE cdb_crons SET name='$lang[init_cron_4]' WHERE cronid='4';
UPDATE cdb_crons SET name='$lang[init_cron_5]' WHERE cronid='5';
UPDATE cdb_crons SET name='$lang[init_cron_6]' WHERE cronid='6';
UPDATE cdb_crons SET name='$lang[init_cron_7]' WHERE cronid='7';
UPDATE cdb_crons SET name='$lang[init_cron_8]' WHERE cronid='8';
UPDATE cdb_crons SET name='$lang[init_cron_9]' WHERE cronid='9';
UPDATE cdb_crons SET name='$lang[init_cron_10]' WHERE cronid='10';
UPDATE cdb_crons SET name='$lang[init_cron_11]', weekday='$cron_pushthread_week', hour='$cron_pushthread_week' WHERE cronid='11';

UPDATE cdb_settings SET value='$lang[init_dataformat]' WHERE variable='dateformat';
UPDATE cdb_settings SET value='$lang[init_modreasons]' WHERE variable='modreasons';
UPDATE cdb_settings SET value='$lang[init_threadsticky]' WHERE variable='threadsticky';
UPDATE cdb_settings SET value='$lang[init_qihoo_searchboxtxt]' WHERE variable='qihoo_searchboxtxt';

UPDATE cdb_styles SET name='$lang[init_default_style]' WHERE styleid='1';

UPDATE cdb_templates SET name='$lang[init_default_template]', copyright='$lang[init_default_template_copyright]' WHERE templateid='1';

EOT;

function upg_newbietask() {
	global $db, $tablepre, $newbietask;

	if($db->result($db->query("SELECT count(*) FROM `{$tablepre}tasks` WHERE newbietask=1"), 0)) {
		return;
	}
	foreach($newbietask as $k => $sqlarray) {
		$db->query("INSERT INTO `{$tablepre}tasks` (`newbietask`, `available`, `name`, `description`, `icon`, `applicants`, `achievers`, `tasklimits`, `applyperm`, `scriptname`, `starttime`, `endtime`, `period`, `reward`, `prize`, `bonus`, `displayorder`, `version`) VALUES ($sqlarray[task]);");
		$currentid = $db->insert_id();
		foreach($sqlarray['vars'] as $taskvars) {
			$db->query("INSERT INTO `{$tablepre}taskvars` (`taskid`, `sort`, `name`, `description`, `variable`, `type`, `value`, `extra`) VALUES ($currentid, $taskvars);");
		}
	}
}

function upg_comsenz_stats() {
	global $db, $tablepre;
	static $is_run = false;
	if($is_run) return;
	if(getgpc('addfounder_contact','P')) {
		$email = strip_tags(getgpc('email', 'P'));
		$msn = strip_tags(getgpc('msn', 'P'));
		$qq = strip_tags(getgpc('qq', 'P'));
		if(!preg_match("/^[\d]+$/", $qq)) $qq = '';
		if(strlen($email) < 6 || !preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email)) $email = '';
		if(strlen($msn) < 6 || !preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $msn)) $msn = '';

		$contact = serialize(array('qq' => $qq, 'msn' => $msn, 'email' => $email));
		$db->query("REPLACE {$tablepre}settings (variable, value) VALUES ('founder_contact', '$contact')");
		$is_run = ture;
		echo '<script type="text/javascript">document.getElementById("laststep").disabled=false;document.getElementById("laststep").value = \''.lang('install_succeed').'\';</script><iframe src="../" style="display:none"></iframe>'."\r\n";
		show_header();
		echo '</div><div class="main" style="margin-top: -123px;"><ul style="line-height: 200%; margin-left: 30px;">';
		echo '<li><a href="../">'.lang('install_succeed').'</a><br>';
		echo '<script>setTimeout(function(){window.location=\'../\'}, 2000);</script>'.lang('auto_redirect').'</li>';
		echo '</ul></div>';
		show_footer();
	} else {

		show_header();
		$contact = array();
		$contact = unserialize($db->result($db->query("SELECT value FROM {$tablepre}settings WHERE variable='founder_contact'"),0));
		$founder_contact = lang('founder_contact');
		$founder_contact = str_replace(array("\n","\t"), array('<br>','&nbsp;&nbsp;&nbsp;&nbsp;'), $founder_contact);
			echo '</div><div class="main" style="margin-top: -123px;">';
			echo $founder_contact;
			echo '<form action="'.$url_forward.'" method="post" id="postform">';
			echo	"<br><table width=\"360\" cellspacing=\"1\" border=\"0\" align=\"center\">".
		 		"<tr height=\"30\"><td align=\"right\" >QQ:</td><td>&nbsp;&nbsp;<input  class=\"txt\" type=\"text\" value=\"$contact[qq]\" name=\"qq\" ></td></tr>
		 		<tr height=\"30\"><td align=\"right\">MSN:</td><td>&nbsp;&nbsp;<input  class=\"txt\" type=\"text\" value=\"$contact[msn]\" name=\"msn\" ></td></tr>
		 		<tr height=\"30\"><td align=\"right\">E-mail:</td><td>&nbsp;&nbsp;<input  class=\"txt\" type=\"text\" value=\"$contact[email]\" name=\"email\" ></td></tr>
		 		<tr align=\"center\" height=\"30\"><td colspan=\"2\"><input type=\"submit\" style=\"padding: 2px;\" name=\"addfounder_contact\" value=\"".lang('install_submit')."\"></td></tr></table>";
			echo '</form>';
			getstatinfo();
			echo '<p style="text-align:right"><input type="button" style="padding: 2px;" onclick="window.location=\'index.php?method=ext_info&skip=1\'" value="'.lang('skip_current').'" /></center></p>';
			echo '</div>';
		show_footer();
	}
}


function getstatinfo() {
	if($siteid && $key) {
		return;
	}
	$version = '7.2';
	$onlineip = '';
	if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
		$onlineip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
		$onlineip = getenv('REMOTE_ADDR');
	} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
		$onlineip = $_SERVER['REMOTE_ADDR'];
	}
	$funcurl = 'http://stat'.'.disc'.'uz.co'.'m/stat_ins.php';
	$PHP_SELF = htmlspecialchars($_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
	$url = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].preg_replace("/\/+(api|archiver|wap)?\/*$/i", '', substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'))));
	$url = substr($url, 0, -8);
	$hash = md5("$url\$version{$onlineip}");
	$q = "url=$url&version=$version&ip=$onlineip&time=".time()."&hash=$hash";
	$q=rawurlencode(base64_encode($q));
	dfopen($funcurl."?action=newinstall&q=$q");
}
?>