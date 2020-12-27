<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: uchome.php 20555 2009-10-09 03:00:53Z liuqiang $
*/

@chdir('../');

require './include/common.inc.php';

if(!$ucappopen['UCHOME']) {
	showmessage('uchome_not_installed');
}

if($action == 'getalbums' && $discuz_uid) {

	$albums = @unserialize(dfopen("$uchomeurl/api/discuz.php?ac=album&uid=$discuz_uid"));

	include_once language('misc');
	if($albums && is_array($albums)) {
		$str = '<select name="uch_albums" onchange="if(this.value) {var tmp=this.value.split(\'_\');ajaxget(\'api/uchome.php?action=getphotoes&photonum=\'+tmp[1]+\'&aid=\'+tmp[0], \'uch_photoes\');}"><option value="">'.$language['uch_selectalbum'].'</option>';
		foreach($albums as $album) {
			$str .= "<option value=\"$album[albumid]_$album[picnum]\">$album[albumname]</option>";
		}
		$str .= '</select>';
		showmessage($str);
	} else {
		showmessage("$language[uch_noalbum]<a href=\"$uchomeurl/cp.php?ac=upload\" target=\"_blank\">$language[click_here]</a>$language[uch_createalbum]");
	}


} elseif($action == 'getphotoes' && $discuz_uid && $aid) {

	$page = max(1, intval($page));
	$perpage = 8;
	$start = ($page - 1) * $perpage;
	$aid = intval($aid);
	$photonum = intval($photonum);
	$photoes = @unserialize(dfopen("$uchomeurl/api/discuz.php?ac=album&uid=$discuz_uid&start=$start&count=$photonum&perpage=$perpage&aid=$aid"));

	if($photoes && is_array($photoes)) {
		$i = 0;
		$str = '<table cellspacing="2" cellpadding="2" class="imglist"><tr>';
		foreach($photoes as $photo) {
			if($i++ == $perpage) {
				break;
			}
			$picurl = substr(strtolower($photo['bigpic']), 0, 7) == 'http://' ? '' : $uchomeurl.'/';
			$str .= '<td valign="bottom" width="25%"><a href="javascript:;"><img src="'.$picurl.$photo['pic'].'" title="'.$photo['filename'].'" onclick="wysiwyg ? insertText(\'<img src='.$picurl.$photo[bigpic].' border=0 /> \', false) : insertText(\'[img]'.$picurl.$photo[bigpic].'[/img]\');" onload="thumbImg(this, 1)" _width="110" _height="110"></a></td>'.
			($i % 4 == 0 && isset($photoes[$i]) ? '</tr><tr>' : '');
		}
		$str .= '</tr></table>'.multi($photonum, $perpage, $start / $perpage + 1, "api/uchome.php?action=getphotoes&aid=$aid&photonum=$photonum");
		showmessage($str);
	} else {
		showmessage('NOPHOTO');
	}

}