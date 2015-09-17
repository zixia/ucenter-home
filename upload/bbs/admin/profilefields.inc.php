<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: profilefields.inc.php 16698 2008-11-14 07:58:56Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!$operation) {

	if(!submitcheck('fieldsubmit')) {

		$query = $db->query("SELECT * FROM {$tablepre}profilefields");
		while($field = $db->fetch_array($query)) {
			$profilefields .= showtablerow('', array('class="td25"', 'class="td28"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[{$field[fieldid]}]\" value=\"$field[fieldid]\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"displayordernew[{$field[fieldid]}]\" value=\"$field[displayorder]\">",
				"<input type=\"text\" class=\"txt\" size=\"18\" name=\"titlenew[{$field[fieldid]}]\" value=\"$field[title]\">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[{$field[fieldid]}]\" value=\"1\" ".($field['available'] ? 'checked' : '').">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"invisiblenew[{$field[fieldid]}]\" value=\"1\" ".($field['invisible'] ? 'checked' : '').">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"unchangeablenew[{$field[fieldid]}]\" value=\"1\" ".($field['unchangeable'] ? 'checked' : '').">",
				"<a href=\"$BASESCRIPT?action=profilefields&operation=edit&id=$field[fieldid]\" class=\"act\">$lang[detail]</a>"
			), TRUE);
		}
		shownav('user', 'profilefields');
		showsubmenu('profilefields');

		echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[
			[1,'', 'td25'],
			[1,'', 'td28'],
			[6,'<input type="text" class="txt" name="newtitle[]" size="18">']
		]
	];
</script>
EOT;
		showformheader('profilefields');
		showtableheader();
		showsubtitle(array('', 'display_order', 'profilefields_title', 'available', 'profilefields_invisible', 'profilefields_unchangeable', ''));
		echo $profilefields;
		echo '<tr><td></td><td colspan="7"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['profilefields_add'].'</a></div></td></tr>';
		showsubmit('fieldsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if(is_array($titlenew)) {
			foreach($titlenew as $id => $val) {
				$db->query("UPDATE {$tablepre}profilefields SET title='$titlenew[$id]', available='$availablenew[$id]', invisible='$invisiblenew[$id]', displayorder='$displayordernew[$id]', unchangeable='$unchangeablenew[$id]' WHERE fieldid='$id'");
			}
		}

		if(is_array($delete)) {
			$ids = implode('\',\'', $delete);
			$dropfields = implode(',DROP field_', $delete);
			$db->query("DELETE FROM {$tablepre}profilefields WHERE fieldid IN ('$ids')");
			$db->query("ALTER TABLE {$tablepre}memberfields DROP field_$dropfields");
		}

		if(is_array($newtitle)) {
			foreach($newtitle as $value) {
				if($value = trim($value)) {
					$db->query("INSERT INTO {$tablepre}profilefields (available, invisible, title, size)
							VALUES ('1', '0', '$value', '50')");
					$fieldid = $db->insert_id();
					$db->query("ALTER TABLE {$tablepre}memberfields ADD field_$fieldid varchar(50) NOT NULL", 'SILENT');
				}
			}
		}

		updatecache(array('fields_required', 'fields_optional', 'custominfo'));
		cpmsg('fields_edit_succeed', $BASESCRIPT.'?action=profilefields', 'succeed');
	}

} elseif($operation == 'edit') {

	$field = $db->fetch_first("SELECT * FROM {$tablepre}profilefields WHERE fieldid='$id'");
	if(!$field) {
		cpmsg('undefined_action', '', 'error');
	}

	if(!submitcheck('editsubmit')) {

		showsubmenu("$lang[profilefields_edit] - $field[title]");
		showformheader("profilefields&operation=edit&id=$id");
		showtableheader();
		showsetting('profilefields_edit_title', 'titlenew', $field['title'], 'text');
		showsetting('profilefields_edit_desc', 'descriptionnew', $field['description'], 'text');
		showsetting('profilefields_edit_size', 'sizenew', $field['size'], 'text');
		showsetting('profilefields_edit_invisible', 'invisiblenew', $field['invisible'], 'radio');
		showsetting('profilefields_edit_required', 'requirednew', $field['required'], 'radio');
		showsetting('profilefields_edit_unchangeable', 'unchangeablenew', $field['unchangeable'], 'radio');
		showsetting('profilefields_edit_selective', 'selectivenew', $field['selective'], 'radio');
		showsetting('profilefields_edit_choices', 'choicesnew', $field['choices'], 'textarea');
		showsubmit('editsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$titlenew = trim($titlenew);
		$sizenew = $sizenew <= 255 ? $sizenew : 255;
		if(!$titlenew || !$sizenew) {
			cpmsg('fields_edit_invalid', '', 'error');
		}

		if($sizenew != $field['size']) {
			$db->query("ALTER TABLE {$tablepre}memberfields CHANGE field_$id field_$id varchar($sizenew) NOT NULL");
		}

		$db->query("UPDATE {$tablepre}profilefields SET title='$titlenew', description='$descriptionnew', size='$sizenew', invisible='$invisiblenew', required='$requirednew', unchangeable='$unchangeablenew', selective='$selectivenew', choices='$choicesnew' WHERE fieldid='$id'");

		updatecache(array('fields_required', 'fields_optional', 'custominfo'));
		cpmsg('fields_edit_succeed', $BASESCRIPT.'?action=profilefields', 'succeed');
	}

}

?>