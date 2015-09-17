<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: runwizard.inc.php 19960 2009-09-15 23:18:37Z wangjinbo $
*/


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}


$step = in_array($step, array(1, 2, 3, 4)) ? $step : 1;
$runwizardhistory = array();
$runwizardfile = DISCUZ_ROOT.'./forumdata/logs/runwizardlog.php';
if($fp = @fopen($runwizardfile, 'r')) {
	fseek($fp, 13);
	$runwizardhistory = @unserialize(fread($fp, 99999));
	fclose($fp);
}

cpheader();

shownav('tools', 'nav_runwizard');
showsubmenusteps('nav_runwizard', array(
	array('runwizard_step_1', $step == 1),
	array('runwizard_step_2', $step == 2),
	array('runwizard_step_3', $step == 3),
	array('runwizard_step_4', $step == 4)
));
showtips('runwizard_tips');

if($step == 1) {
	$sizecheckedid = isset($runwizardhistory['step1']['size']) ?  $runwizardhistory['step1']['size'] : 1;
	$safecheckedid = isset($runwizardhistory['step1']['safe']) ?  $runwizardhistory['step1']['safe'] : 0;
	$funccheckedid = isset($runwizardhistory['step1']['func']) ?  $runwizardhistory['step1']['func'] : 1;

	showformheader('runwizard&step=2');
	showtableheader();
	showsetting('runwizard_forum_scope', array('size', array(
		array(0, $lang['runwizard_forum_scope_small']),
		array(1, $lang['runwizard_forum_scope_midding']),
		array(2, $lang['runwizard_forum_scope_big'])
	)), $sizecheckedid, 'mradio');
	showsetting('runwizard_security', array('safe', array(
		array(2, $lang['runwizard_security_high']),
		array(1, $lang['runwizard_security_midding']),
		array(0, $lang['runwizard_security_low'])
	)), $safecheckedid, 'mradio');
	showsetting('runwizard_hobby', array('func', array(
		array(0, $lang['runwizard_hobby_concision']),
		array(1, $lang['runwizard_hobby_commonly']),
		array(2, $lang['runwizard_hobby_abundance'])
	)), $funccheckedid, 'mradio');
	showsubmit('step1submit', 'nextstep');
	showtablefooter();
	showformfooter();

	$db->query("DELETE FROM {$tablepre}settings WHERE variable='runwizard'");
	updatecache('settings');

} elseif($step == 2) {
	if(submitcheck('step1submit')) {
		$runwizardhistory['step1']['size'] = $size;
		$runwizardhistory['step1']['safe'] = $safe;
		$runwizardhistory['step1']['func'] = $func;
		saverunwizardhistory();
	}
	$settings = &$_DCACHE['settings'];
	$settings['bbname']   = empty($runwizard) && $runwizardhistory['step2']['bbname'] ? $runwizardhistory['step2']['bbname'] : $settings['bbname'];
	$settings['sitename'] = empty($runwizard) && $runwizardhistory['step2']['sitename'] ? $runwizardhistory['step2']['sitename'] : $settings['sitename'];
	$settings['siteurl']  = empty($runwizard) && $runwizardhistory['step2']['siteurl'] ? $runwizardhistory['step2']['siteurl'] : $settings['siteurl'];

	showformheader('runwizard&step=3');
	showtableheader();
	showsetting('settings_basic_bbname', 'settingsnew[bbname]', $settings['bbname'], 'text');
	showsetting('settings_basic_sitename', 'settingsnew[sitename]', $settings['sitename'], 'text');
	showsetting('settings_basic_siteurl', 'settingsnew[siteurl]', $settings['siteurl'], 'text');
	showsubmit('step2submit', 'nextstep', '<input type="button" class="btn" name="step2submit" value="'.lang('laststep').'" onclick="history.back();">');
	showtablefooter();
	showformfooter();

} elseif($step == 3) {

	if(submitcheck('step2submit')) {
		$runwizardhistory['step2']['bbname']   = $settingsnew['bbname'];
		$runwizardhistory['step2']['sitename'] = $settingsnew['sitename'];
		$runwizardhistory['step2']['siteurl']  = $settingsnew['siteurl'];
		saverunwizardhistory();
	}

	showformheader('runwizard&step=4');
	showtableheader('', 'fixpadding');
	for($i = 1; $i < 4; $i++) {
		showtablerow('', '', '<div class="parentboard"><input type="text" name="newcat['.$i.']" value="'.lang('runwizard_cat').' '.$i.'" class="txt" /></div>');
		for($j = 1; $j < 4; $j++) {
			showtablerow('', '', '<div class="'.($j == 3 ? 'lastboard' : 'board').'"><input type="text" name="newforum['.$i.'][]" value="'.lang('runwizard_forum').' '.$i.'.'.$j.'" class="txt" /></div>');
		}
	}
	showsubmit('step3submit', 'nextstep', '<input type="button" class="btn" name="step2submit" value="'.$lang['laststep'].'" onclick="history.back();">');
	showtablefooter();
	showformfooter();

} elseif($step == 4) {

	if(submitcheck('step3submit')) {
		foreach($newcat as $k=>$catename) {
			if(!$catename) {
				unset($newcat[$k]);
				unset($newforum[$k]);
			} else {
				foreach($newforum[$k] as $k2=>$forumname) {
					if(!$forumname) {
						unset($newforum[$k][$k2]);
					}
				}
			}
		}

		$runwizardhistory['step3']['cates']   = $newcat ? $newcat : array();
		$runwizardhistory['step3']['forums']   = $newforum ? $newforum : array();

		saverunwizardhistory();
	}

	showtableheader('', 'nobottom fixpadding');
	echo '<tr><td>';

	if($confirm != 'yes') {

?>

<br /><?=$lang['runwizard_forum_initialization']?>
<ul class="tpllist">
<li><h4><?=$lang['runwizard_forum_scope']?></b> <?=$lang['runwizard_size_'.$runwizardhistory['step1']['size']]?><h4></li>
<li><h4><?=$lang['runwizard_security']?></b> <?=$lang['runwizard_safe_'.$runwizardhistory['step1']['safe']]?><h4></li>
<li><h4><?=$lang['runwizard_hobby']?></b> <?=$lang['runwizard_func_'.$runwizardhistory['step1']['func']]?><h4></li>
<li><h4><?=$lang['settings_bbname']?></b> <?=$runwizardhistory['step2']['bbname']?><h4></li>
<li><h4><?=$lang['settings_sitename']?></b> <?=$runwizardhistory['step2']['sitename']?><h4></li>
<li><h4><?=$lang['settings_siteurl']?></b> <?=$runwizardhistory['step2']['siteurl']?><h4></li>
<li><h4><?=$lang['runwizard_forum_add']?><h4>
<?

		if($runwizardhistory['step3']['cates']) {
			echo '<ul class="tpllist2">';
			foreach($runwizardhistory['step3']['cates'] as $id=>$catename) {
				echo '<li><h5>'.$catename.'</h5><ul class="tpllist3">';
				foreach($runwizardhistory['step3']['forums'][$id] as $forumname) {
					echo '<li>'.$forumname.'</li>';
				}
				echo '</ul></li>';
			}
			echo '</ul>';
		}  else {
			echo $lang['none'];
		}

		echo '</li></ul></td></tr>';
		showtablefooter();
		showformheader('runwizard&step=4&confirm=yes');
		showtableheader('', 'notop');
		showsubmit('step4submit', 'submit', '<input type="button" class="btn" " value="'.$lang['laststep'].'" onclick="history.back();">');
		showtablefooter();
		showformfooter();

	} else {

		$sizesettings = array(
			'attachsave' => array('1', '3', '4'),
			'delayviewcount' => array('0', '0', '3'),
			'fullmytopics' => array('1', '0', '0'),
			'maxonlines' => array('500', '5000', '50000'),
			'pvfrequence' => array('30', '60', '100'),
			'qihoo_status' => array('0', '0', '1'),
			'searchctrl' => array('10', '30', '60'),
			'hottopic' => array('10', '20', '50'),
			'losslessdel' => array('365', '200', '100'),
			'maxmodworksmonths' => array('5', '3', '1'),
			'maxsearchresults' => array('200', '500', '1000'),
			'statscachelife' => array('90', '180', '360'),
			'moddisplay' => array('flat', 'flat', 'selectbox'),
			'topicperpage' => array('30', '20', '15'),
			'postperpage' => array('20', '15', '10'),
			'maxpolloptions' => array('10', '10', '15'),
			'maxpostsize' => array('10000', '10000', '20000'),
			'myrecorddays' => array('100', '60', '30'),
			'maxfavorites' => array('500', '200', '100'),

		);
		$safesettings = array(
			'attachrefcheck' => array('', '1', '1'),
			'bannedmessages' => array('', '1', '1'),
			'doublee' => array('1', '0', '0'),
			'dupkarmarate' => array('1', '0', '0'),
			'hideprivate' => array('0', '1', '1'),
			'memliststatus' => array('1', '1', '0'),
			'seccodestatus' => array('0', '1', '1'),
			'bbrules' => array('0', '1', '1'),
			'edittimelimit' => array('0', '20', '10'),
			'floodctrl' => array('0', '10', '30'),
			'karmaratelimit' => array('0', '1', '4'),
			'newbiespan' => array('', '1', '4'),
			'showemail' => array('0', '1', '1'),
			'maxchargespan' => array('0', '1', '2'),
			'regctrl' => array('0', '12', '48'),
			'regfloodctrl' => array('0', '100', '50'),
			'regstatus' => array('1', '1', '1'),
			'regverify' => array('0', '1', '2'),
		);
		$funcsettings = array(
			'archiverstatus' => array('0', '1', '1'),
			'bdaystatus' => array('0', '0', '1'),
			'fastpost' => array('0', '1', '1'),
			'editedby' => array('0', '1', '1'),
			'forumjump' => array('0', '1', '1'),
			'gzipcompress' => array('', '', '1'),
			'newsletter' => array('', '', '1'),
			'modworkstatus' => array('0', '0', '1'),
			'reportpost' => array('0', '1', '1'),
			'rewritestatus' => array('0', '0', '0'),
			'rssstatus' => array('0', '1', '1'),
			'wapstatus' => array('0', '1', '1'),
			'maxbdays' => array('0', '100', '500'),
			'statstatus' => array('0', '0', '1'),
			'stylejump' => array('0', '0', '1'),
			'subforumsindex' => array('0', '0', '1'),
			'transsidstatus' => array('0', '0', '1'),
			'visitedforums' => array('0', '10', '20'),
			'vtonlinestatus' => array('0', '1', '1'),
			'welcomemsg' => array('0', '0', '1'),
			'jsstatus' => array('0', '0', '1'),
			'watermarkstatus' => array('0', '0', '1'),
			'whosonlinestatus' => array('0', '1', '1'),
			'debug' => array('0', '1', '1'),
			'regadvance' => array('0', '0', '1'),
			'jsmenustatus' => array('0', '1', '15'),
			'showsettings' => array('0', '7', '7'),
			'editoroptions' => array('0', '1', '1'),
		);

		$safeforums = array(
			'modnewposts' => array('0', '0', '1'),
			'recyclebin' => array('0', '1', '1'),
			'jammer' => array('0', '0', '1'),
		);
		$funcforums = array(
			'allowsmilies' => array('0', '1', '1'),
			'allowbbcode' => array('0', '1', '1'),
			'allowimgcode' => array('0', '1', '1'),
			'allowanonymous' => array('0', '0', '1'),
			'allowpostspecial' => array('', '1', '127'),
			'disablewatermark' => array('1', '0', '0'),
			'threadcaches' => array('0', '0', '1'),
			'allowshare' => array('0', '1', '1'),
			);
		$sizeforums = array(
			'threadcaches' => array('0', '0', '1'),
		);

		$sqladd = $comma = '';

		foreach($sizesettings as $fieldname=>$val) {
			$db->query("REPLACE INTO {$tablepre}settings (variable, value)
				VALUES ('$fieldname', '{$val[$runwizardhistory[step1][size]]}')");
		}
		foreach($sizeforums as $fieldname=>$val) {
			$sqladd .= $comma."$fieldname='".$val[$runwizardhistory['step1']['size']]."'";
			$comma = ',';
		}

		foreach($safesettings as $fieldname=>$val) {
			$db->query("REPLACE INTO {$tablepre}settings (variable, value)
				VALUES ('$fieldname', '{$val[$runwizardhistory[step1][safe]]}')");
		}
		foreach($safeforums as $fieldname=>$val) {
			$sqladd .= $comma."$fieldname='".$val[$runwizardhistory['step1']['safe']]."'";
		}

		foreach($funcsettings as $fieldname=>$val) {
			$db->query("REPLACE INTO {$tablepre}settings (variable, value)
				VALUES ('$fieldname', '{$val[$runwizardhistory[step1][func]]}')");
		}
		foreach($funcforums as $fieldname=>$val) {
			$sqladd .= $comma."$fieldname='".$val[$runwizardhistory['step1']['func']]."'";
		}

		$db->query("UPDATE {$tablepre}forums SET $sqladd");

		$maxonlines = $sizesettings['maxonlines'][$runwizardhistory['step1']['size']];
		$db->query("ALTER TABLE {$tablepre}sessions MAX_ROWS=$maxonlines");

		$db->query("REPLACE INTO {$tablepre}settings (variable, value)
			VALUES ('bbname', '{$runwizardhistory[step2][bbname]}')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value)
			VALUES ('sitename', '{$runwizardhistory[step2][sitename]}')");
		$db->query("REPLACE INTO {$tablepre}settings (variable, value)
			VALUES ('siteurl', '{$runwizardhistory[step2][siteurl]}')");

		updatecache('settings');

		foreach($runwizardhistory['step3']['cates'] as $id=>$catename) {
			$db->query("INSERT INTO {$tablepre}forums (type, name, status)
				VALUES ('group', '$catename', '1')");
			$fup = $fid = $db->insert_id();
			$db->query("INSERT INTO {$tablepre}forumfields (fid)
				VALUES ('$fid')");
			foreach($runwizardhistory['step3']['forums'][$id] as $forumname) {
				$db->query("INSERT INTO {$tablepre}forums (fup, type, name, status, allowsmilies, allowbbcode, allowimgcode, allowshare, allowpostspecial)
					VALUES ('$fup', 'forum', '$forumname', '1', '1', '1', '1', '1', '15')");
				$fid = $db->insert_id();
				$db->query("INSERT INTO {$tablepre}forumfields (fid)
					VALUES ('$fid')");
			}
		}

		updatecache('forums');

		$runwizardhistory['step3']['cates'] = array();
		$runwizardhistory['step3']['forums'] = array();
		saverunwizardhistory();

?>

<ul class="tpllist"><li>
<h4><?=$lang['runwizard_succeed']?></h4>
<ul class="tpllist2">
<li><a href="<?=$BASESCRIPT?>?action=settings&operation=basic"><?=$lang['runwizard_particular']?></a></li>
<li><a href="<?=$BASESCRIPT?>?action=forums"><?=$lang['forums_admin_add_forum']?></a></li>
<li><a href="<?=$BASESCRIPT?>?action=tools&operation=fileperms"><?=$lang['nav_fileperms']?></a></li>
</ul>
</li>
<li>
<h4><?=$lang['runwizard_database_backup']?></h4>
<ul class="tpllist2">
<li><a href="<?=$BASESCRIPT?>?action=db&operation=export"><?=$lang['db_export']?></a></li>
<li><a href="<?=$BASESCRIPT?>?action=db&operation=import"><?=$lang['db_import']?></a></li>
</ul>
</li>
</ul>
<?}?>
</td></tr></table>
<?

}

function saverunwizardhistory() {
	global $runwizardfile, $runwizardhistory;
	$fp = fopen($runwizardfile, 'w');
	$s = '<?php exit;?>';
	$s .= serialize($runwizardhistory);
	fwrite($fp, $s);
	fclose($fp);
}

?>