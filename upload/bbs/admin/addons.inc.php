<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: addons.inc.php 20401 2009-09-25 09:26:17Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

define('ADDONS_SERVER', 'http://addons.discuz.com');

cpheader();

if(!$operation) {

	shownav('addons');
	showsubmenu('addons', array(
		array('addons_list', 'addons', 1),
		array('addons_add', 'addons&operation=add', 0)
	));
	showtips('addons_tips');

	$query = $db->query("SELECT * FROM {$tablepre}addons ORDER BY `system` DESC,`key` ASC");
	while($addon = $db->fetch_array($query)) {
		showproviderinfo($addon, 0);
	}
	echo '<input class="btn" type="button" onclick="location.href=\''.$BASESCRIPT.'?action=addons&operation=add\'" value="'.$lang['addons_more'].'" />';

} elseif($operation == 'list') {

	require_once DISCUZ_ROOT.'./discuz_version.php';
	$baseparm = 'version='.rawurlencode(DISCUZ_VERSION).'&release='.rawurlencode(DISCUZ_RELEASE).'&charset='.rawurlencode($charset).'&boardurl='.rawurlencode($boardurl);
	$addon = dstrip_tags($db->fetch_first("SELECT * FROM {$tablepre}addons WHERE `key`='$provider'"));
	if(!$addon) {
		cpmsg('addons_provider_nonexistence', '', 'error');
	}
	$providerapi = trim(dfopen(ADDONS_SERVER, 0, $baseparm.'&key='.rawurlencode($provider)));
	if(!$providerapi) {
		cpmsg('addons_provider_disabled', '', 'error');
	}

	$extra = !empty($category) ? '&category='.rawurlencode($category) : '';
	$data = dfopen($providerapi, 0, $baseparm.$extra);
	require_once DISCUZ_ROOT.'./include/xml.class.php';
	if(strtoupper($charset) != 'UTF-8') {
		require_once DISCUZ_ROOT.'include/chinese.class.php';
		$c = new Chinese('utf8', $charset, TRUE);
		$data = $c->Convert($data);
	}
	$data = xml2array($data);
	if(!is_array($data) || !$data || $data['Key'] != $provider) {
		cpmsg('addons_provider_apiinvalid', $BASESCRIPT.'?action=addons', 'error');
	}
	checkinfoupdate();

	$data = dstrip_tags($data);
	shownav('addons', $data['Title']);
	showsubmenu($data['Title']);

	showproviderinfo($addon, 1);

	showtableheader('', 'noborder');
	echo '<tr><td valign="top" width="150" style="padding-top:0"><ul class="menu">';
	foreach($data['Category'] as $categoryid => $Category) {
		echo '<li class="a"><a'.($category == $categoryid ? ' class="tabon"' : '').' href="'.$BASESCRIPT.'?action=addons&operation=list&provider='.$provider.'&category='.$categoryid.'">'.$Category.'</a></li>';
	}
	echo '</ul></td><td valign="top" style="padding-top:0">';
	if($data['Searchlink'] != '') {
		echo '<form method="post" action="'.$data['Searchlink'].'" target="_blank">'.
			'<input type="hidden" name="version" value="'.DISCUZ_VERSION.'" />'.
			'<input type="hidden" name="release" value="'.DISCUZ_RELEASE.'" />'.
			'<input type="hidden" name="charset" value="'.$charset.'" />'.
			'<input type="hidden" name="boardurl" value="'.$boardurl.'" />'.
			'<input name="keyword" /><input name="submit" class="btn" style="margin: -4px 0 0 2px" type="submit" value="'.$lang['addons_search'].'" />'.
			'</form>';
	}
	$count = 0;
	showtableheader('', 'fixpadding', 'style="margin-top:0"');
	if(is_array($data['Data'])) foreach($data['Data'] as $row) {
		$count++;
		$Charset = explode(',', $row['Charset']);
		foreach($Charset as $k => $v) {
			if(preg_match('/^SC\_GBK$/i', $v)) {
				$Charset[$k] = '&#31616;&#20307;&#20013;&#25991;';
				if(strtoupper($charset) == 'GBK') {
					$Charset[$k] = '<b>'.$Charset[$k].'</b>';
				}
			} elseif(preg_match('/^SC\_UTF8$/i', $v)) {
				$Charset[$k] = '&#31616;&#20307;&#20013;&#25991;&#85;&#84;&#70;&#56;';
				if(strtoupper($charset) == 'UTF-8') {
					$Charset[$k] = '<b>'.$Charset[$k].'</b>';
				}
			} elseif(preg_match('/^TC\_BIG5$/i', $v)) {
				$Charset[$k] = '&#32321;&#39636;&#20013;&#25991;';
				if(strtoupper($charset) == 'BIG5') {
					$Charset[$k] = '<b>'.$Charset[$k].'</b>';
				}
			} elseif(preg_match('/^TC\_UTF8$/i', $v)) {
				$Charset[$k] = '&#32321;&#39636;&#20013;&#25991;&#85;&#84;&#70;&#56;';
				if(strtoupper($charset) == 'UTF-8') {
					$Charset[$k] = '<b>'.$Charset[$k].'</b>';
				}
			}
		}
		echo '<tr><th colspan="3" class="partition">'.($row['Time'] != '' ? '<div class="right">'.$row['Time'].'</div>' : '').'<a href="'.$row['Url'].'" target="_blank">'.($row['Greenplugin'] ? '<img class="vmiddle" title="'.$lang['addons_greenplugin'].'" src="images/admincp/greenplugin.gif" /> ' : '').$row['Name'].($row['Version'] != '' ? ' '.$row['Version'] : '').'</a></th></tr>'.
			'<tr><td width="110">'.($row['Thumb'] != '' ? '<a href="'.$row['Url'].'" target="_blank"><img onerror="this.src=\'images/common/none.gif\'" src="'.$row['Thumb'].'" width="100" /></a>' : '').'</td>'.
			'<td width="90%" class="lineheight" valign="top">'.($row['Charset'] != '' ? $lang['addons_charset'].implode(', ', $Charset).'<br />' : '').($row['Description'] != '' ? nl2br($row['Description']) : '').'</td></tr>';
		if($count == 20) {
			break;
		}
	}
	showtablefooter();
	if($data['Morelink'] != '') {
		showtableheader('', 'fixpadding');
		echo '<tr><td class="partition"><a href="'.$data['Morelink'].'" target="_blank">'.$lang['addons_more'].'</a></td></tr>';
		showtablefooter();
	}
	echo '</td></tr>';
	showtablefooter();

} elseif($operation == 'remove') {

	$addon = $db->fetch_first("SELECT * FROM {$tablepre}addons WHERE `key`='$provider'");
	if(!$addon) {
		cpmsg('addons_provider_nonexistence', '', 'error');
	}
	$db->query("DELETE FROM {$tablepre}addons WHERE `key`='$provider' AND system='0'");
	cpmsg('addons_provider_removesucceed', $BASESCRIPT.'?action=addons', 'succeed');

} elseif($operation == 'add') {

	if(empty($providerkey)) {

		$extra = !empty($category) ? '&category='.rawurlencode($category) : '';
		$data = dfopen(ADDONS_SERVER.'/list.xml');
		require_once DISCUZ_ROOT.'./include/xml.class.php';
		if(strtoupper($charset) != 'UTF-8') {
			require_once DISCUZ_ROOT.'include/chinese.class.php';
			$c = new Chinese('utf8', $charset, TRUE);
			$data = $c->Convert($data);
		}
		$data = xml2array($data);

		shownav('addons');
		showsubmenu('addons', array(
			array('addons_list', 'addons', 0),
			array('addons_add', 'addons&operation=add', 1)
		));
		showtips('addons_add_tips');

		showtableheader();
		if(is_array($data) && $data) {
			$data = dstrip_tags($data);
			showsubtitle(array('addons_recommend', ''));
			echo '<tr><td>';
			foreach($data as $row) {
				echo '<div class="hover" style="float:left;width:20%"><div style="text-align:center"><a href="'.$BASESCRIPT.'?action=addons&operation=add&providerkey='.$row['key'].'">'.
				($row['logo'] ? '<img width="100" height="50" src="'.$row['logo'].'" />' : '<img width="100" height="50" src="images/common/none.gif" />').
				'</a><br /><a href="'.$BASESCRIPT.'?action=addons&operation=add&providerkey='.$row['key'].'">'.$row['sitename'].'</a></div></div>';
			}
			echo '</td></tr>';

		} else {
			echo '<tr><td>'.$lang['addons_provider_listinvalid'].'</td></tr>';
		}
		showtablefooter();

		showformheader('addons&operation=add');
		showtableheader('addons_add_input');
		showsetting('addons_provider_key', 'providerkey', '', 'text');
		showsubmit('newsubmit');
		showtablefooter();
		showformfooter();

	} else {
		if(!$providerkey) {
			cpmsg('addons_provider_nonexistence', '', 'error');
		}
		$addon = $db->fetch_first("SELECT * FROM {$tablepre}addons WHERE `key`='$providerkey'");
		if($addon) {
			cpmsg('addons_provider_exists', $BASESCRIPT.'?action=addons&operation=list&provider='.rawurlencode($providerkey), 'succeed');
		}
		require_once DISCUZ_ROOT.'./discuz_version.php';
		$baseparm = 'version='.rawurlencode(DISCUZ_VERSION).'&release='.rawurlencode(DISCUZ_RELEASE).'&charset='.rawurlencode($charset);
		$providerapi = trim(dfopen(ADDONS_SERVER, 0, $baseparm.'&key='.rawurlencode($providerkey)));
		if(!$providerapi) {
			cpmsg('addons_provider_disabled', '', 'error');
		}
		$db->query("INSERT INTO {$tablepre}addons (`key`) VALUES ('$providerkey')");
		cpmsg('addons_provider_addsucceed', $BASESCRIPT.'?action=addons&operation=list&provider='.rawurlencode($providerkey), 'succeed');
	}

}

function showproviderinfo($addon, $simple) {
	$contact = $addon['contact'];
	$contact = preg_replace("/(((https?){1}:\/\/|www\.).+?)(\s|$)/ies", "parsetaga('\\1', '\\4', 0)", $contact);
	$contact = preg_replace("/(([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+))(\s|$)/ies", "parsetaga('\\1', '\\4', 1)", $contact);
	if($simple) {
		echo '<div class="colorbox">';
	}
	showtableheader('', $simple ? 'noborder' : '');
	echo (!$simple ? '<tr><th colspan="3" class="partition"><a href="'.$BASESCRIPT.'?action=addons&operation=list&provider='.$addon['key'].'">'.$addon['title'].'</a></th></tr>' : '').
		'<tr><td width="110"><a href="'.$BASESCRIPT.'?action=addons&operation=list&provider='.$addon['key'].'"><img onerror="this.src=\'images/common/none.gif\'" src="'.$addon['logo'].'" width="100" height="50" /></a></td>'.
		'<td valign="top">'.nl2br($addon['description']).'<br />'.
		lang('addons_provider').'<a href="'.$addon['siteurl'].'" target="_blank">'.$addon['sitename'].'</a><br />'.
		lang('addons_contact').$contact.'</td>'.
		(!$simple ? '<td align="right" width="50">'.(!$addon['system'] ? '<a href="'.$BASESCRIPT.'?action=addons&operation=remove&provider='.$addon['key'].'" onclick="return confirm(\''.lang('addons_delete_confirm').'\')">'.lang('delete').'</a>' : '').'&nbsp;</td>' : '').'</tr>';
	showtablefooter();
	if($simple) {
		echo '</div>';
	}
}

function checkinfoupdate() {
	global $db, $tablepre, $data, $addon, $provider;
	$update = array();
	if($data['Title'] != $addon['title']) {
		$update[] = "title='".addslashes($data['Title'])."'";
	}
	if($data['Sitename'] != $addon['sitename']) {
		$update[] = "sitename='".addslashes($data['Sitename'])."'";
	}
	if($data['Siteurl'] != $addon['siteurl']) {
		$update[] = "siteurl='".addslashes($data['Siteurl'])."'";
	}
	if($data['Description'] != $addon['description']) {
		$update[] = "description='".addslashes($data['Description'])."'";
	}
	if($data['Contact'] != $addon['contact']) {
		$update[] = "contact='".addslashes($data['Contact'])."'";
	}
	if($data['Logo'] != $addon['logo']) {
		$update[] = "logo='".addslashes($data['Logo'])."'";
	}
	if($update) {
		$db->query("UPDATE {$tablepre}addons SET ".implode(',', $update)." WHERE `key`='$provider'");
	}
	$addon = $db->fetch_first("SELECT * FROM {$tablepre}addons WHERE `key`='$provider'");
}

function parsetaga($href, $s, $mailto) {
	return '<a href="'.($mailto ? 'mailto:' : '').$href.'" target="_blank">'.$href.'</a>'.$s;
}

function dstrip_tags($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dstrip_tags($val);
		}
	} else {
		$string = strip_tags($string);
	}
	return $string;
}

?>