<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: faq.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();
if(!isfounder()) cpmsg('noaccess_isfounder', '', 'error');
$operation = $operation ? $operation : 'list';

if($operation == 'list') {

	if(!submitcheck('faqsubmit')) {

		shownav('extended', 'faq');
		showsubmenu('faq');
		showformheader('faq&operation=list');
		showtableheader();
		echo '<tr><th class="td25"></th><th>'.$lang['display_order'].'</th><th style="width:350px">'.$lang['faq_thread'].'</th><th class="td24">'.$lang['faq_sortup'].'</th><th></th></tr>';

		$faqparent = $faqsub = array();
		$faqlists = $faqselect = '';
		$query = $db->query("SELECT * FROM {$tablepre}faqs ORDER BY displayorder");
		while($faq = $db->fetch_array($query)) {
			if(empty($faq['fpid'])) {
				$faqparent[$faq['id']] = $faq;
				$faqselect .= "<option value=\"$faq[id]\">$faq[title]</option>";
			} else {
				$faqsub[$faq['fpid']][] = $faq;
			}
		}

		foreach($faqparent as $parent) {
			$disabled = !empty($faqsub[$parent['id']]) ? 'disabled' : '';
			showtablerow('', array('', 'class="td23 td28"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$parent[id]\" $disabled>",
				"<input type=\"text\" class=\"txt\" size=\"3\" name=\"displayorder[$parent[id]]\" value=\"$parent[displayorder]\">",
				"<div class=\"parentnode\"><input type=\"text\" class=\"txt\" size=\"30\" name=\"title[$parent[id]]\" value=\"".dhtmlspecialchars($parent['title'])."\"></div>",
				$lang[none],
				"<a href=\"$BASESCRIPT?action=faq&operation=detail&id=$parent[id]\" class=\"act\">".$lang['detail']."</a>"
			));
			if(!empty($faqsub[$parent['id']])) {
				foreach($faqsub[$parent['id']] as $sub) {
					showtablerow('', array('', 'class="td23 td28"'), array(
						"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$sub[id]\">",
						"<input type=\"text\" class=\"txt\" size=\"3\" name=\"displayorder[$sub[id]]\" value=\"$sub[displayorder]\">",
						"<div class=\"node\"><input type=\"text\" class=\"txt\" size=\"30\" name=\"title[$sub[id]]\" value=\"".dhtmlspecialchars($sub['title'])."\"></div>",
						$faqparent[$sub['fpid']][title],
						"<a href=\"$BASESCRIPT?action=faq&operation=detail&id=$sub[id]\" class=\"act\">".$lang['detail']."</a>"
					));
				}
			}
			echo '<tr><td></td><td></td><td colspan="3"><div class="lastnode"><a href="###" onclick="addrow(this, 1, '.$parent['id'].')" class="addtr">'.lang('faq_additem').'</a></div></td></tr>';
		}
		echo '<tr><td></td><td></td><td colspan="3"><div><a href="###" onclick="addrow(this, 0, 0)" class="addtr">'.lang('faq_addcat').'</a></div></td></tr>';

		echo <<<EOT
<script type="text/JavaScript">
var rowtypedata = [
	[[1,''], [1,'<input name="newdisplayorder[]" value="" size="3" type="text" class="txt">', 'td25'], [1, '<input name="newtitle[]" value="" size="30" type="text" class="txt">'], [2, '<input type="hidden" name="newfpid[]" value="0" />']],
	[[1,''], [1,'<input name="newdisplayorder[]" value="" size="3" type="text" class="txt">', 'td25'], [1, '<div class=\"node\"><input name="newtitle[]" value="" size="30" type="text" class="txt"></div>'], [2, '<input type="hidden" name="newfpid[]" value="{1}" />']]
];
</script>
EOT;

		showsubmit('faqsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		if($ids = implodeids($delete)) {
			$db->query("DELETE FROM	{$tablepre}faqs WHERE id IN ($ids)");
		}

		if(is_array($title)) {
			foreach($title as $id => $val) {
				$db->query("UPDATE {$tablepre}faqs SET displayorder='$displayorder[$id]', title='$title[$id]' WHERE id='$id'");
			}
		}

		if(is_array($newtitle)) {
			foreach($newtitle as $k => $v) {
				$v = trim($v);
				if($v) {
					$db->query("INSERT INTO	{$tablepre}faqs (fpid, displayorder, title)
						VALUES ('".intval($newfpid[$k])."', '".intval($newdisplayorder[$k])."', '$v')");
				}
			}
		}

		cpmsg('faq_list_update', $BASESCRIPT.'?action=faq&operation=list', 'succeed');

	}

} elseif($operation == 'detail') {

	if(!submitcheck('detailsubmit')) {

		$faq = $db->fetch_first("SELECT * FROM {$tablepre}faqs WHERE id='$id'");
		if(!$faq) {
			cpmsg('undefined_action', '', 'error');
		}

		$query = $db->query("SELECT * FROM {$tablepre}faqs WHERE fpid='0' ORDER BY displayorder, fpid ");
		while($parent = $db->fetch_array($query)) {
			$faqselect .= "<option value=\"$parent[id]\" ".($faq['fpid'] == $parent['id'] ? 'selected' : '').">$parent[title]</option>";
		}

		shownav('extended', 'faq');
		showsubmenu('faq');
		showformheader("faq&operation=detail&id=$id");
		showtableheader();
		showtitle('faq_edit');
		showsetting('faq_title', 'titlenew', $faq['title'], 'text');
		if(!empty($faq['fpid'])) {
			showsetting('faq_sortup', '', '', '<select name="fpidnew"><option value=\"\">'.$lang['none'].'</option>'.$faqselect.'</select>');
			showsetting('faq_identifier', 'identifiernew', $faq['identifier'], 'text');
			showsetting('faq_keywords', 'keywordnew', $faq['keyword'], 'text');
			showsetting('faq_content', 'messagenew', $faq['message'], 'textarea');
		}
		showsubmit('detailsubmit');
		showtablefooter();
		showformfooter();

	} else {

		if(!$titlenew) {
			cpmsg('faq_no_title', '', 'error');
		}

		if(!empty($identifiernew)) {
			$query = $db->query("SELECT id FROM {$tablepre}faqs WHERE identifier='$identifiernew' AND id!='$id'");
			if($db->num_rows($query)) {
				cpmsg('faq_identifier_invalid', '', 'error');
			}
		}

		if(strlen($keywordnew) > 50) {
			cpmsg('faq_keyword_toolong', '', 'error');
		}

		$fpidnew = $fpidnew ? intval($fpidnew) : 0;
		$titlenew = trim($titlenew);
		$messagenew = trim($messagenew);
		$identifiernew = trim($identifiernew);
		$keywordnew = trim($keywordnew);

		$db->query("UPDATE {$tablepre}faqs SET fpid='$fpidnew', identifier='$identifiernew', keyword='$keywordnew', title='$titlenew', message='$messagenew' WHERE id='$id'");

		updatecache('faqs');
		cpmsg('faq_list_update', $BASESCRIPT.'?action=faq&operation=list', 'succeed');

	}

}

?>