<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: qihoo.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

$query = $db->query("SELECT value FROM {$tablepre}settings WHERE variable='qihoo'");
$qihoo = ($qihoo = $db->result($query, 0)) ? unserialize($qihoo) : array();

$operation = !empty($operation) ? $operation : 'config';

if($operation == 'config') {

	if(!submitcheck('qihoosubmit')) {

		$checks = array();
		$checkstatus = array($qihoo['status'] => 'checked');
		$checklocation = array($qihoo['location'] => 'checked');
		$qihoo['searchbox'] = sprintf('%03b', $qihoo['searchbox']);
		for($i = 1; $i <= 3; $i++) {
			$checks[$i] = $qihoo['searchbox'][3 - $i] ? 'checked' : '';
		}

		shownav('extended', 'nav_qihoo');
		showsubmenu('nav_qihoo', array(
			array('nav_qihoo_config', 'qihoo&operation=config', 1),
			array('nav_qihoo_topics', 'qihoo&operation=topics', 0),
			array('nav_qihoo_relatedthreads', 'qihoo&operation=relatedthreads', 0)
		));

		showtips('qihoo_tips');
		showformheader('qihoo&operation=config');
		showtableheader();
		showtitle('qihoo');
		showsetting('qihoo_status', '', '', '<ul class="nofloat" onmouseover="altStyle(this);"><li'.($checkstatus[1] ? ' class="checked"' : '').'><input class="radio" type="radio" name="qihoonew[status]" value="1" '.$checkstatus[1].'> '.$lang['qihoo_status_enable'].'</li><li'.($checkstatus[2] ? ' class="checked"' : '').'><input class="radio" type="radio" name="qihoonew[status]" value="2" '.$checkstatus[2].'> '.$lang['qihoo_status_enable_default'].'</li><li'.($checkstatus[0] ? ' class="checked"' : '').'><input class="radio" type="radio" name="qihoonew[status]" value="0" '.$checkstatus[0].'> '.$lang['qihoo_status_disable'].'</li></ul>');
		showsetting('qihoo_searchbox', '', '', '<ul class="nofloat" onmouseover="altStyle(this);"><li'.($checks[1] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="qihoonew[searchbox][1]" value="1" '.$checks[1].'> '.$lang['qihoo_searchbox_index'].'</li><li'.($checks[2] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="qihoonew[searchbox][2]" value="1" '.$checks[2].'> '.$lang['qihoo_searchbox_forumdisplay'].'</li></ul>');
		showsetting('qihoo_summary', 'qihoonew[summary]', $qihoo['summary'], 'radio');
		showsetting('qihoo_jammer_allow', 'qihoonew[jammer]', $qihoo['jammer'], 'radio');
		showsetting('qihoo_maxtopics', 'qihoonew[maxtopics]', $qihoo['maxtopics'], 'text');
		showsetting('qihoo_keywords', 'qihoonew[keywords]', $qihoo['keywords'], 'textarea');
		showsetting('qihoo_adminemail', 'qihoonew[adminemail]', $qihoo['adminemail'], 'text');
		showsubmit('qihoosubmit');
		showtablefooter();
		showformfooter();

	} else {

		$qihoonew['searchbox'] = bindec(intval($qihoonew['searchbox'][3]).intval($qihoonew['searchbox'][2]).intval($qihoonew['searchbox'][1]));
		$qihoonew['validity'] = $qihoonew['validity'] < 1 ? 1 : intval($qihoonew['validity']);

		if($qihoonew['status'] && $qihoonew['adminemail']) {
			if(!isemail($qihoonew['adminemail'])) {
				cpmsg('qihoo_adminemail_invalid', '', 'error');
			}
			if($qihoonew['adminemail'] != $qihoo['adminemail']) {
				dfopen('http://search.qihoo.com/corp/discuz.html?site='.site().'&key='.md5(site().'qihoo_discuz'.gmdate("Ymd", $timestamp)).'&email='.$qihoonew['adminemail']);
			}
		}

		foreach((array)$qihoonew as $key => $value) {
			$qihoo[$key] = in_array($key, array('keywords', 'adminemail')) ? $value : intval($value);
		}

		$db->query("UPDATE {$tablepre}settings SET value='".addslashes(serialize($qihoo))."' WHERE variable='qihoo'");
		updatecache('settings');
		cpmsg('qihoo_succeed', $BASESCRIPT.'?action=qihoo&operation=config', 'succeed');

	}

} elseif($operation == 'relatedthreads') {

	if(!submitcheck('qihoosubmit')) {

		$checktype = array();
		$settings = is_array($qihoo['relatedthreads']) ? $qihoo['relatedthreads'] : array();

		foreach((array)$settings['type'] as $type) {
			$checktype[$type] = 'checked';
		}

		shownav('extended', 'nav_qihoo');
		showsubmenu('nav_qihoo', array(
			array('nav_qihoo_config', 'qihoo&operation=config', 0),
			array('nav_qihoo_topics', 'qihoo&operation=topics', 0),
			array('nav_qihoo_relatedthreads', 'qihoo&operation=relatedthreads', 1)
		));

		showtips('qihoo_tips');
		showformheader('qihoo&operation=relatedthreads');
		showtableheader();
		showsetting('qihoo_relatedthreads', 'settingsnew[bbsnum]', $settings['bbsnum'], 'text');
		showsetting('qihoo_relatedthreads_web', 'settingsnew[webnum]', $settings['webnum'], 'radio');
		showsetting('qihoo_relatedthreads_type', '', '', '<ul class="nofloat" onmouseover="altStyle(this);">'.
			'<li'.($checktype['news'] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="settingsnew[type][news]" value="news" '.$checktype['news'].'> '.$lang['qihoo_relatedthreads_type_news'].'</li>'.
			'<li'.($checktype['bbs'] ? ' class="checked"' : '').'><input class="checkbox" type="checkbox" name="settingsnew[type][bbs]" value="bbs" '.$checktype['bbs'].'> '.$lang['qihoo_relatedthreads_type_bbs']).'</li></ul>';
		showsetting('qihoo_relatedthreads_banurl', 'settingsnew[banurl]', $settings['banurl'], 'textarea');
		showsetting('qihoo_relatedthreads_position', array('settingsnew[position]', array(
			array(0, $lang['qihoo_relatedthreads_position_mode_top']),
			array(1, $lang['qihoo_relatedthreads_position_mode_under'])
		)), $settings['position'], 'mradio');
		//showsetting('qihoo_relatedthreads_validity', 'settingsnew[validity]', $settings['validity'], 'text');
		showsubmit('qihoosubmit');
		showtablefooter();
		showformfooter();

	} else {

		$qihoo['relatedthreads'] = array();
		foreach((array)$settingsnew as $key => $value) {
			$qihoo['relatedthreads'][$key] = in_array($key, array('bbsnum', 'webnum', 'position', 'order', 'validity')) ? intval($value) : $value;
		}
		$db->query("UPDATE {$tablepre}settings SET value='".addslashes(serialize($qihoo))."' WHERE variable='qihoo'");
		updatecache('settings');
		cpmsg('qihoo_succeed', $BASESCRIPT.'?action=qihoo&operation=relatedthreads', 'succeed');
	}

} elseif($operation == 'topics') {

	if(!submitcheck('topicsubmit')) {
		showsubmenu('nav_qihoo', array(
			array('nav_qihoo_config', 'qihoo&operation=config', 0),
			array('nav_qihoo_topics', 'qihoo&operation=topics', 1),
			array('nav_qihoo_relatedthreads', 'qihoo&operation=relatedthreads', 0)
		));
		showtips('qihoo_topics_tips');
		showformheader('qihoo&operation=topics');
		showtableheader('qihoo_topics_list');
		showsubtitle(array('', 'qihoo_topics_name', 'qihoo_topics_keywords', 'qihoo_topics_length', 'qihoo_topics_type', 'qihoo_topics_orderby', ''));

		foreach((is_array($qihoo['topics']) ? $qihoo['topics'] : array()) as $key => $value) {
			$checkstype = array($value['stype'] => 'selected="selected"');
			$checkrelate = array($value['relate'] => 'selected="selected"');
			showtablerow('', array('class="td25"', '', 'class="td29"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[$key]\" value=\"".$value['topic']."\">",
				"<input type=\"text\" class=\"txt\" size=\"20\" name=\"qihoo_topics[$key][topic]\" id=\"qihoo_topics[$key][topic]\" value=\"$value[topic]\">",
				"<input type=\"text\" class=\"txt\" size=\"30\" name=\"qihoo_topics[$key][keyword]\" id=\"qihoo_topics[$key][keyword]\" value=\"$value[keyword]\">",
				"<input type=\"text\" class=\"txt\" size=\"10\" name=\"qihoo_topics[$key][length]\" id=\"qihoo_topics[$key][length]\" value=\"$value[length]\">",
				"<select name=\"qihoo_topics[$key][stype]\" id=\"qihoo_topics[$key][stype]\"><option value=\"0\" $checkstype[0]>$lang[qihoo_topics_type_fulltext]</option><option value=\"title\" $checkstype[title]>$lang[qihoo_topics_type_title]</option></select>",
				"<select name=\"qihoo_topics[$key][relate]\" id=\"qihoo_topics[$key][relate]\"><option value=\"score\" $checkrelate[score]>$lang[qihoo_topics_orderby_relation]</option><option value=\"pdate\" $checkrelate[pdate]>$lang[qihoo_topics_orderby_dateline]</option><option value=\"rdate\" $checkrelate[rdate]>$lang[qihoo_topics_orderby_lastpost]</option></select>",
				"<a href=\"###\" onClick=\"window.open('topic.php?topic='+$('qihoo_topics[$key][topic]').value+'&keyword='+$('qihoo_topics[$key][keyword]').value+'&stype='+$('qihoo_topics[$key][stype]').value+'&length='+$('qihoo_topics[$key][length]').value+'&relate='+$('qihoo_topics[$key][relate]').value+'');\" class=\"act\">".lang('preview')."</a>"
			));
		}

		showtablerow('', array('class="td25"', '', 'class="td29"'), array(
			lang('add_new'),
			'<input type="text" class="txt" size="20" name="newtopic" id="newtopic">',
			'<input type="text" class="txt" size="30" name="newkeyword" id="newkeyword">',
			'<input type="text" class="txt" size="10" name="newlength" id="newlength" value="0">',
			'<select name="newstype" id="newstype"><option value="0" selected>'.lang('qihoo_topics_type_fulltext').'</option><option value="1">'.lang('qihoo_topics_type_title').'</option></select>',
			'<select name="newrelate" id="newrelate"><option value="score">'.lang('qihoo_topics_orderby_relation').'</option><option value="pdate">'.lang('qihoo_topics_orderby_dateline').'</option><option value="rdate">'.lang('qihoo_topics_orderby_lastpost').'</option></select>',
			'<a href="###" onClick="window.open(\'topic.php?topic=\'+$(\'newtopic\').value+\'&keyword=\'+$(\'newkeyword\').value+\'&stype=\'+$(\'newstype\').value+\'&length=\'+$(\'newlength\').value+\'&relate=\'+$(\'newrelate\').value);" class="act">'.lang('preview').'</a>'
		));
		showsubmit('topicsubmit', 'submit', 'del');
		showtablefooter();
		showformfooter();

	} else {

		$qihoo['topics'] = array();
		foreach((array)$qihoo_topics as $key => $value) {
			if(isset($delete[$key])) {
				unset($qihoo['topics'][$key]);
			} else {
				$qihoo['topics'][$key] = array(
					'topic'		=> dhtmlspecialchars(stripslashes($value['topic'])),
					'keyword'	=> $value['keyword'] = trim($value['keyword']) ? dhtmlspecialchars(stripslashes($value['keyword'])) : $value['topic'],
					'length'	=> intval($value['length']),
					'stype'		=> $value['stype'],
					'relate'	=> $value['relate']
				);
			}
		}

		if($newtopic) {
			$qihoo['topics'][] = array(
				'topic'		=> dhtmlspecialchars(stripslashes($newtopic)),
				'keyword'	=> $newkeyword = trim($newkeyword) ? dhtmlspecialchars(stripslashes($newkeyword)) : $newtopic,
				'length'	=> intval($newlength),
				'stype'		=> $newstype > 1 ? 1 : intval($newstype),
				'relate'	=> $newrelate
			);
		}

		$db->query("UPDATE {$tablepre}settings SET value='".addslashes(serialize($qihoo))."' WHERE variable='qihoo'");
		updatecache('settings');
		cpmsg('qihoo_topics_succeed', $BASESCRIPT.'?action=qihoo&operation=topics', 'succeed');

	}

}

?>