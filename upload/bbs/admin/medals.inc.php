<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: medals.inc.php 21268 2009-11-24 06:15:58Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!$operation) {

	if(!submitcheck('medalsubmit')) {
		shownav('extended', 'nav_medals', 'admin');
		showsubmenu('nav_medals', array(
			array('admin', 'medals', 1),
			array('nav_medals_confer', 'members&operation=confermedal', 0),
			array('nav_medals_mod', 'medals&operation=mod', 0)
		));
		showtips('medals_tips');
		showformheader('medals');
		showtableheader();
		showtablerow('', array('class="td25"', 'class="td28"', 'class="td25"', 'class="td25"', '', '', '', 'class="td23"', 'class="td25"'), array(
			'',
			lang('display_order'),
			'',
			lang('available'),
			lang('name'),
			lang('description'),
			lang('medals_image'),
			lang('medals_type'),
			'',
		));

?>
<script type="text/JavaScript">
	var rowtypedata = [
		[
			[1,'', 'td25'],
			[1,'<input type="text" class="txt" name="newdisplayorder[]" size="3">', 'td28'],
			[1,'', 'td25'],
			[1,'', 'td25'],
			[1,'<input type="text" class="txt" name="newname[]" size="10">'],
			[1,'<input type="text" class="txt" name="newdescription[]" size="30">'],
			[1,'<input type="text" class="txt" name="newimage[]" size="20">'],
			[1,'', 'td23'],
			[1,'', 'td25']
		]
	];
</script>
<?
		$query = $db->query("SELECT * FROM {$tablepre}medals ORDER BY displayorder");
		while($medal = $db->fetch_array($query)) {
			$checkavailable = $medal['available'] ? 'checked' : '';
			$medal['type'] = $medal['type'] == 1 ? lang('medals_register') : lang('medals_adminadd');
			showtablerow('', array('class="td25"', 'class="td28"', 'class="td25"', 'class="td25"', '', '', '', 'class="td23"', 'class="td25"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$medal[medalid]\">",
				"<input type=\"text\" class=\"txt\" size=\"3\" name=\"displayorder[$medal[medalid]]\" value=\"$medal[displayorder]\">",
				"<img src=\"images/common/$medal[image]\">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"available[$medal[medalid]]\" value=\"1\" $checkavailable>",
				"<input type=\"text\" class=\"txt\" size=\"10\" name=\"name[$medal[medalid]]\" value=\"$medal[name]\">",
				"<input type=\"text\" class=\"txt\" size=\"30\" name=\"description[$medal[medalid]]\" value=\"$medal[description]\">",
				"<input type=\"text\" class=\"txt\" size=\"20\" name=\"image[$medal[medalid]]\" value=\"$medal[image]\">",
				$medal[type],
				"<a href=\"$BASESCRIPT?action=medals&operation=edit&medalid=$medal[medalid]\" class=\"act\">$lang[detail]</a>"
			));
		}

		echo '<tr><td></td><td colspan="8"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['medals_addnew'].'</a></div></td></tr>';
		showsubmit('medalsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if(is_array($delete)) {
			$ids = $comma = '';
			foreach($delete as $id) {
				$ids .= "$comma'$id'";
				$comma = ',';
			}
			$db->query("DELETE FROM {$tablepre}medals WHERE medalid IN ($ids)");
		}

		if(is_array($name)) {
			foreach($name as $id => $val) {
				$db->query("UPDATE {$tablepre}medals SET name=".($name[$id] ? '\''.dhtmlspecialchars($name[$id]).'\'' : 'name').", available='$available[$id]', description=".($description[$id] ? '\''.dhtmlspecialchars($description[$id]).'\'' : 'name').", displayorder='".intval($displayorder[$id])."', image=".($image[$id] ? '\''.$image[$id].'\'' : 'image')." WHERE medalid='$id'");
			}
		}

		if(is_array($newname)) {
			foreach($newname as $key => $value) {
				if($value != '' && $newimage[$key] != '') {
					$db->query("INSERT INTO {$tablepre}medals (name, available, image, displayorder, description) VALUES ('".dhtmlspecialchars($value)."', '$newavailable[$key]', '$newimage[$key]', '".intval($newdisplayorder[$key])."', '".dhtmlspecialchars($newdescription[$key])."')");
				}
			}
		}

		updatecache('settings');
		updatecache('medals');
		cpmsg('medals_succeed', $BASESCRIPT.'?action=medals', 'succeed');
	}

} elseif($operation == 'mod') {

	if(submitcheck('delmedalsubmit')) {
		if (is_array($delete)) {
			$ids = $comma = '';
			foreach($delete as $id) {
				$ids .= "$comma'$id'";
				$comma = ',';
			}
			$query = $db->query("UPDATE {$tablepre}medallog SET type='3' WHERE id IN ($ids)");
			cpmsg('medals_invalidate_succeed', $BASESCRIPT.'?action=medals&operation=mod', 'succeed');
		} else {
			cpmsg('medals_please_input', $BASESCRIPT.'?action=medals&operation=mod', 'error');
		}
	} elseif(submitcheck('modmedalsubmit')) {

		if(is_array($delete)) {
			$ids = $comma = '';
			foreach($delete as $id) {
				$ids .= "$comma'$id'";
				$comma = ',';
			}

			$query = $db->query("SELECT me.id, me.uid, me.medalid, me.dateline, me.expiration, mf.medals
					FROM {$tablepre}medallog me
					LEFT JOIN {$tablepre}memberfields mf USING (uid)
					WHERE id IN ($ids)");

			@include_once DISCUZ_ROOT.'./forumdata/cache/cache_medals.php';
			while($modmedal = $db->fetch_array($query)) {
				$modmedal['medals'] = empty($medalsnew[$modmedal['uid']]) ? $modmedal['medals'] : $medalsnew[$modmedal['uid']];

				foreach($modmedal['medals'] = explode("\t", $modmedal['medals']) as $key => $modmedalid) {
					list($medalid, $medalexpiration) = explode("|", $modmedalid);
					if(isset($_DCACHE['medals'][$medalid]) && (!$medalexpiration || $medalexpiration > $timestamp)) {
						$medalsnew[$modmedal['uid']][$key] = $modmedalid;
					}
				}
				$medalstatus = empty($modmedal['expiration']) ? 0 : 1;
				$modmedal['expiration'] = $modmedal['expiration'] ? ($timestamp + $modmedal['expiration'] - $modmedal['dateline']) : '';
				$medalsnew[$modmedal['uid']][] = $modmedal['medalid'].(empty($modmedal['expiration']) ? '' : '|'.$modmedal['expiration']);
				$db->query("UPDATE {$tablepre}medallog SET type=1, status='$medalstatus', expiration='$modmedal[expiration]' WHERE id='$modmedal[id]'");
			}

			foreach ($medalsnew as $key => $medalnew) {
				$medalnew = implode("\t", $medalnew);
				$db->query("UPDATE {$tablepre}memberfields SET medals='$medalnew' WHERE uid='$key'");
			}
			cpmsg('medals_validate_succeed', $BASESCRIPT.'?action=medals&operation=mod', 'succeed');
		} else {
			cpmsg('medals_please_input', $BASESCRIPT.'?action=medals&operation=mod', 'error');
		}
	} else {

		$medals = '';
		$query = $db->query("SELECT mel.*, m.username, me.name FROM {$tablepre}medallog mel
				LEFT JOIN {$tablepre}medals me ON me.medalid = mel.medalid
				LEFT JOIN {$tablepre}members m ON m.uid = mel.uid
				WHERE mel.type=2 ORDER BY dateline");
		while($medal = $db->fetch_array($query)) {
			$medal['dateline'] =  gmdate('Y-m-d H:i', $medal['dateline'] + $timeoffset * 3600);
			$medal['expiration'] =  empty($medal['expiration']) ? $lang['medals_forever'] : gmdate('Y-m-d H:i', $medal['expiration'] + $timeoffset * 3600);
			$medals .= showtablerow('', '', array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$medal[id]\">",
				"<a href=\"space.php?username=".rawurlencode($medal['username'])."\" target=\"_blank\">$medal[username]</a>",
				$medal['name'],
				$medal['dateline'],
				$medal['expiration']
			), TRUE);
		}

		shownav('extended', 'nav_medals', 'nav_medals_mod');
		showsubmenu('nav_medals', array(
			array('admin', 'medals', 0),
			array('nav_medals_confer', 'members&operation=confermedal', 0),
			array('nav_medals_mod', 'medals&operation=mod', 1)
		));
		showformheader('medals&operation=mod');
		showtableheader('medals_mod');
		showtablerow('', '', array(
			'',
			lang('medals_user'),
			lang('medals_name'),
			lang('medals_date'),
			lang('medals_expr'),
		));
		echo $medals;
		showsubmit('modmedalsubmit', 'medals_modpass', 'select_all', '<input type="submit" class="btn" value="'.lang('medals_modnopass').'" name="delmedalsubmit"> ');
		showtablefooter();
		showformfooter();
	}

} elseif($operation == 'edit') {

	$medalid = intval($medalid);

	if(!submitcheck('medaleditsubmit')) {

		$medal = $db->fetch_first("SELECT * FROM {$tablepre}medals WHERE medalid='$medalid'");

		$medal['permission'] = unserialize($medal['permission']);$medal['permission'] = $medal['permission'][0];

		$checkmedaltype = array($medal['type'] => 'checked');

		shownav('extended', 'nav_medals', 'admin');
		showsubmenu('nav_medals', array(
			array('admin', 'medals', 1),
			array('nav_medals_confer', 'members&operation=confermedal', 0),
			array('nav_medals_mod', 'medals&operation=mod', 0)
		));
		showformheader("medals&operation=edit&medalid=$medalid");
		showtableheader(lang('medals_edit').' - '.$medal['name'], 'nobottom');
		showsetting('medals_name1', 'namenew', $medal['name'], 'text');
		showsetting('medals_img', '', '', '<input type="text" class="txt" size="30" name="imagenew" value="'.$medal['image'].'" ><img src="images/common/'.$medal['image'].'">');
		showsetting('medals_type1', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">
			<li'.($checkmedaltype[0] ? ' class="checked"' : '').'><input name="typenew" type="radio" class="radio" value="0" '.$checkmedaltype[0].'>&nbsp;'.$lang['medals_adminadd'].'</li>
			<li'.($checkmedaltype[1] ? ' class="checked"' : '').'><input name="typenew" type="radio" class="radio" value="1" '.$checkmedaltype[1].'>&nbsp;'.$lang['medals_register'].'</li></ul>'
		);
		showsetting('medals_expr1', 'expirationnew', $medal['expiration'], 'text');
		showsetting('medals_memo', 'descriptionnew', $medal['description'], 'text');
		showtablefooter();

		showtableheader('medals_perm', 'notop');

			$formulareplace .= '\'<u>'.$lang['settings_credits_formula_digestposts'].'</u>\',\'<u>'.$lang['settings_credits_formula_posts'].'</u>\',\'<u>'.$lang['settings_credits_formula_oltime'].'</u>\',\'<u>'.$lang['settings_credits_formula_pageviews'].'</u>\'';

?>
<script type="text/JavaScript">

	function isUndefined(variable) {
		return typeof variable == 'undefined' ? true : false;
	}

	function insertunit(text, textend) {
		$('formulapermnew').focus();
		textend = isUndefined(textend) ? '' : textend;
		if(!isUndefined($('formulapermnew').selectionStart)) {
			var opn = $('formulapermnew').selectionStart + 0;
			if(textend != '') {
				text = text + $('formulapermnew').value.substring($('formulapermnew').selectionStart, $('formulapermnew').selectionEnd) + textend;
			}
			$('formulapermnew').value = $('formulapermnew').value.substr(0, $('formulapermnew').selectionStart) + text + $('formulapermnew').value.substr($('formulapermnew').selectionEnd);
		} else if(document.selection && document.selection.createRange) {
			var sel = document.selection.createRange();
			if(textend != '') {
				text = text + sel.text + textend;
			}
			sel.text = text.replace(/\r?\n/g, '\r\n');
			sel.moveStart('character', -strlen(text));
		} else {
			$('formulapermnew').value += text;
		}
		formulaexp();
	}

	var formulafind = new Array('digestposts', 'posts', 'threads', 'oltime', 'pageviews');
	var formulareplace = new Array(<?=$formulareplace?>);
	function formulaexp() {
		var result = $('formulapermnew').value;
<?

		$extcreditsbtn = '';
		for($i = 1; $i <= 8; $i++) {
			$extcredittitle = $extcredits[$i]['title'] ? $extcredits[$i]['title'] : $lang['settings_credits_formula_extcredits'].$i;
			echo 'result = result.replace(/extcredits'.$i.'/g, \'<u>'.$extcredittitle.'</u>\');';
			$extcreditsbtn .= '<a href="###" onclick="insertunit(\'extcredits'.$i.'\')">'.$extcredittitle.'</a> &nbsp;';
		}

		$profilefields = '';
		$query = $db->query("SELECT * FROM {$tablepre}profilefields WHERE available='1' AND unchangeable='1'");
		while($profilefield = $db->fetch_array($query)) {
			echo 'result = result.replace(/field_'.$profilefield['fieldid'].'/g, \'<u>'.str_replace("'", "\'", $profilefield['title']).'</u>\');';
			$profilefields .= '<a href="###" onclick="insertunit(\' field_'.$profilefield['fieldid'].' \')">&nbsp;'.$profilefield['title'].'&nbsp;</a>&nbsp;';
		}

		echo 'result = result.replace(/regdate/g, \'<u>'.$lang['forums_edit_perm_formula_regdate'].'</u>\');';
		echo 'result = result.replace(/regday/g, \'<u>'.$lang['forums_edit_perm_formula_regday'].'</u>\');';
		echo 'result = result.replace(/regip/g, \'<u>'.$lang['forums_edit_perm_formula_regip'].'</u>\');';
		echo 'result = result.replace(/lastip/g, \'<u>'.$lang['forums_edit_perm_formula_lastip'].'</u>\');';
		echo 'result = result.replace(/buyercredit/g, \'<u>'.$lang['forums_edit_perm_formula_buyercredit'].'</u>\');';
		echo 'result = result.replace(/sellercredit/g, \'<u>'.$lang['forums_edit_perm_formula_sellercredit'].'</u>\');';
		echo 'result = result.replace(/digestposts/g, \'<u>'.$lang['settings_credits_formula_digestposts'].'</u>\');';
		echo 'result = result.replace(/posts/g, \'<u>'.$lang['settings_credits_formula_posts'].'</u>\');';
		echo 'result = result.replace(/threads/g, \'<u>'.$lang['settings_credits_formula_threads'].'</u>\');';
		echo 'result = result.replace(/oltime/g, \'<u>'.$lang['settings_credits_formula_oltime'].'</u>\');';
		echo 'result = result.replace(/pageviews/g, \'<u>'.$lang['settings_credits_formula_pageviews'].'</u>\');';
		echo 'result = result.replace(/and/g, \'&nbsp;&nbsp;<b>'.$lang['forums_edit_perm_formula_and'].'</b>&nbsp;&nbsp;\');';
		echo 'result = result.replace(/or/g, \'&nbsp;&nbsp;<b>'.$lang['forums_edit_perm_formula_or'].'</b>&nbsp;&nbsp;\');';
		echo 'result = result.replace(/>=/g, \'&ge;\');';
		echo 'result = result.replace(/<=/g, \'&le;\');';
		echo 'result = result.replace(/==/g, \'=\');';

?>
		$('formulapermexp').innerHTML = result;
	}
</script>
<tr><td colspan="2"><div class="extcredits">
<?php echo $extcreditsbtn?>
<a href="###" onclick="insertunit(' regdate ')">&nbsp;<?php echo lang('forums_edit_perm_formula_regdate')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' regday ')">&nbsp;<?php echo lang('forums_edit_perm_formula_regday')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' regip ')">&nbsp;<?php echo lang('forums_edit_perm_formula_regip')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' lastip ')">&nbsp;<?php echo lang('forums_edit_perm_formula_lastip')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' buyercredit ')">&nbsp;<?php echo lang('forums_edit_perm_formula_buyercredit')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' sellercredit ')">&nbsp;<?php echo lang('forums_edit_perm_formula_sellercredit')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' digestposts ')"><?php echo lang('forums_edit_perm_formula_digestposts')?></a>&nbsp;
<a href="###" onclick="insertunit(' posts ')"><?php echo lang('forums_edit_perm_formula_posts')?></a>&nbsp;
<a href="###" onclick="insertunit(' threads ')"><?php echo lang('forums_edit_perm_formula_threads')?></a>&nbsp;
<a href="###" onclick="insertunit(' oltime ')"><?php echo lang('forums_edit_perm_formula_oltime')?></a>&nbsp;
<a href="###" onclick="insertunit(' pageviews ')"><?php echo lang('forums_edit_perm_formula_pageviews')?></a><?php echo $profilefields;?><br />
<a href="###" onclick="insertunit(' + ')">&nbsp;+&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' - ')">&nbsp;-&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' * ')">&nbsp;*&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' / ')">&nbsp;/&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' > ')">&nbsp;>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' >= ')">&nbsp;>=&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' < ')">&nbsp;<&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' <= ')">&nbsp;<=&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' == ')">&nbsp;=&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' != ')">&nbsp;!=&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' (', ') ')">&nbsp;(&nbsp;)&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' and ')">&nbsp;<?php echo lang('forums_edit_perm_formula_and')?>&nbsp;</a>&nbsp;
<a href="###" onclick="insertunit(' or ')">&nbsp;<?php echo lang('forums_edit_perm_formula_or')?>&nbsp;</a>&nbsp;<br />

</div><div id="formulapermexp" class="marginbot diffcolor2"><?=$formulapermexp?></div>
<textarea name="formulapermnew" id="formulapermnew" style="width: 80%" rows="3" onkeyup="formulaexp()"><?=dhtmlspecialchars($medal['permission'])?></textarea>
<br /><span class="smalltxt"><?=$lang['medals_permformula']?></span>
<br /><?=$lang['creditwizard_current_formula_notice']?>
<script type="text/JavaScript">formulaexp()</script>
</td></tr>
<?
			showsubmit('medaleditsubmit');
			showtablefooter();
			showformfooter();

	} else {
		if($formulapermnew && !preg_match("/^(\{|\}|\+|\-|\*|\/|\.|>|<|=|!|\d|\s|\(|\)|extcredits[1-8]|regdate|regday|regip|lastip|buyercredit|sellercredit|field\_\d+|digestposts|posts|threads|pageviews|oltime|and|or)+$/", $formulapermnew) ||
			!is_null(@eval(preg_replace(
				array("/(regdate|regday|regip|lastip|buyercredit|sellercredit|field\_\d+|digestposts|posts|threads|pageviews|oltime|extcredits[1-8])/", "/\{([\d\.\-]+?)\}/"),
				array("\$\\1", "'\\1'"), $formulapermnew).';'))) {
			cpmsg('forums_formulaperm_error', '', 'error');
		}

		$formulapermary[0] = $formulapermnew;
		$formulapermary[1] = preg_replace(
			array("/(digestposts|posts|threads|pageviews|oltime|extcredits[1-8])/", "/(regdate|regday|regip|lastip|buyercredit|sellercredit|field\_\d+)/"),
			array("\$_DSESSION['\\1']", "\$memberformula['\\1']"),
			$formulapermnew);
		$formulapermnew = addslashes(serialize($formulapermary));

		$db->query("UPDATE {$tablepre}medals SET name=".($namenew ? '\''.dhtmlspecialchars($namenew).'\'' : 'name').", type='$typenew', description='".dhtmlspecialchars($descriptionnew)."', expiration='".intval($expirationnew)."', permission='$formulapermnew', image='$imagenew' WHERE medalid='$medalid'");

		updatecache('medals');
		cpmsg('medals_succeed', $BASESCRIPT.'?action=medals&do=editmedals', 'succeed');
	}

}

?>