<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: discuzcode.func.php 21257 2009-11-23 08:21:38Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include template('discuzcode');

$discuzcodes = array(
	'pcodecount' => -1,
	'codecount' => 0,
	'codehtml' => '',
	'smiliesreplaced' => 0,
	'seoarray' => array(
		0 => '',
		1 => $_SERVER['HTTP_HOST'],
		2 => $bbname,
		3 => $seotitle,
		4 => $seokeywords,
		5 => $seodescription
	)
);
$authorreplyexist = '';

if(!isset($_DCACHE['bbcodes']) || !is_array($_DCACHE['bbcodes']) || !is_array($_DCACHE['smilies'])) {
	@include DISCUZ_ROOT.'./forumdata/cache/cache_bbcodes.php';
}

mt_srand((double)microtime() * 1000000);

function attachtag($pid, $aid, &$postlist) {
	return attachinpost($postlist[$pid]['attachments'][$aid]);
}

function censor($message) {
	global $_DCACHE, $posts;
	require_once(DISCUZ_ROOT.'/forumdata/cache/cache_censor.php');
	if($_DCACHE['censor']['banned']) {
		$bbcodes = 'b|i|u|color|size|font|align|list|indent|url|email|hide|quote|code|free|table|tr|td|img|swf|attach|payto|float'.($_DCACHE['bbcodes_display'] ? '|'.implode('|', array_keys($_DCACHE['bbcodes_display'])) : '');
		if(preg_match($_DCACHE['censor']['banned'], @preg_replace(array("/\[($bbcodes)=?.*\]/iU", "/\[\/($bbcodes)\]/i"), '', $message))) {
			showmessage('word_banned');
		}
	}
	return empty($_DCACHE['censor']['filter']) ? $message :
		@preg_replace($_DCACHE['censor']['filter']['find'], $_DCACHE['censor']['filter']['replace'], $message);
}

function censormod($message) {
	global $_DCACHE, $posts;
	require_once(DISCUZ_ROOT.'/forumdata/cache/cache_censor.php');
	if($_DCACHE['censor']['mod']) {
		if(preg_match($_DCACHE['censor']['mod'], $message)) {
			return TRUE;
		}
	}
	return FALSE;
}

function creditshide($creditsrequire, $message, $pid) {
	global $hideattach;

	if($GLOBALS['credits'] >= $creditsrequire || $GLOBALS['forum']['ismoderator']) {
		return tpl_hide_credits($creditsrequire, str_replace('\\"', '"', $message));
	} else {
		return tpl_hide_credits_hidden($creditsrequire);
	}
}

function codedisp($code) {
	global $discuzcodes;
	$discuzcodes['pcodecount']++;
	$code = dhtmlspecialchars(str_replace('\\"', '"', preg_replace("/^[\n\r]*(.+?)[\n\r]*$/is", "\\1", $code)));
	$code = str_replace("\n", "<li>", $code);
	$discuzcodes['codehtml'][$discuzcodes['pcodecount']] = tpl_codedisp($discuzcodes, $code);
	$discuzcodes['codecount']++;
	return "[\tDISCUZ_CODE_$discuzcodes[pcodecount]\t]";
}

function karmaimg($rate, $ratetimes) {
	$karmaimg = '';
	if($rate && $ratetimes) {
		$image = $rate > 0 ? 'agree.gif' : 'disagree.gif';
		for($i = 0; $i < ceil(abs($rate) / $ratetimes); $i++) {
			$karmaimg .= '<img src="'.IMGDIR.'/'.$image.'" border="0" alt="" />';
		}
	}
	return $karmaimg;
}

function discuzcode($message, $smileyoff, $bbcodeoff, $htmlon = 0, $allowsmilies = 1, $allowbbcode = 1, $allowimgcode = 1, $allowhtml = 0, $jammer = 0, $parsetype = '0', $authorid = '0', $allowmediacode = '0', $pid = 0) {
	global $discuzcodes, $credits, $tid, $discuz_uid, $highlight, $maxsmilies, $db, $tablepre, $hideattach, $allowattachurl;

	if($parsetype != 1 && !$bbcodeoff && $allowbbcode && (strpos($message, '[/code]') || strpos($message, '[/CODE]')) !== FALSE) {
		$message = preg_replace("/\s?\[code\](.+?)\[\/code\]\s?/ies", "codedisp('\\1')", $message);
	}

	$msglower = strtolower($message);

	//$htmlon = $htmlon && $allowhtml ? 1 : 0;

	if(!$htmlon) {
		$message = $jammer ? preg_replace("/\r\n|\n|\r/e", "jammer()", dhtmlspecialchars($message)) : dhtmlspecialchars($message);
	}

	if(!$smileyoff && $allowsmilies && !empty($GLOBALS['_DCACHE']['smilies']) && is_array($GLOBALS['_DCACHE']['smilies'])) {
		if(!$discuzcodes['smiliesreplaced']) {
			foreach($GLOBALS['_DCACHE']['smilies']['replacearray'] AS $key => $smiley) {
				$GLOBALS['_DCACHE']['smilies']['replacearray'][$key] = '<img src="images/smilies/'.$GLOBALS['_DCACHE']['smileytypes'][$GLOBALS['_DCACHE']['smilies']['typearray'][$key]]['directory'].'/'.$smiley.'" smilieid="'.$key.'" border="0" alt="" />';
			}
			$discuzcodes['smiliesreplaced'] = 1;
		}
		$message = preg_replace($GLOBALS['_DCACHE']['smilies']['searcharray'], $GLOBALS['_DCACHE']['smilies']['replacearray'], $message, $maxsmilies);
	}

	if($allowattachurl && strpos($msglower, 'attach://') !== FALSE) {
		$message = preg_replace("/attach:\/\/(\d+)\.?(\w*)/ie", "parseattachurl('\\1', '\\2')", $message);
	}

	if(!$bbcodeoff && $allowbbcode) {
		if(strpos($msglower, '[/url]') !== FALSE) {
			$message = preg_replace("/\[url(=((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/|www\.|mailto:)([^\s\[\"']+?))?\](.+?)\[\/url\]/ies", "parseurl('\\1', '\\5')", $message);
		}
		if(strpos($msglower, '[/email]') !== FALSE) {
			$message = preg_replace("/\[email(=([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+))?\](.+?)\[\/email\]/ies", "parseemail('\\1', '\\4')", $message);
		}
		$message = str_replace(array(
			'[/color]', '[/size]', '[/font]', '[/align]', '[b]', '[/b]', '[s]', '[/s]', '[hr]', '[/p]',
			'[i=s]', '[i]', '[/i]', '[u]', '[/u]', '[list]', '[list=1]', '[list=a]',
			'[list=A]', '[*]', '[/list]', '[indent]', '[/indent]', '[/float]'
		), array(
			'</font>', '</font>', '</font>', '</p>', '<strong>', '</strong>', '<strike>', '</strike>', '<hr class="solidline" />', '</p>', '<i class="pstatus">', '<i>',
			'</i>', '<u>', '</u>', '<ul>', '<ul type="1" class="litype_1">', '<ul type="a" class="litype_2">',
			'<ul type="A" class="litype_3">', '<li>', '</ul>', '<blockquote>', '</blockquote>', '</span>'
		), preg_replace(array(
			"/\[color=([#\w]+?)\]/i",
			"/\[size=(\d+?)\]/i",
			"/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i",
			"/\[font=([^\[\<]+?)\]/i",
			"/\[align=(left|center|right)\]/i",
			"/\[p=(\d{1,2}), (\d{1,2}), (left|center|right)\]/i",
			"/\[float=(left|right)\]/i"

		), array(
			"<font color=\"\\1\">",
			"<font size=\"\\1\">",
			"<font style=\"font-size: \\1\">",
			"<font face=\"\\1 \">",
			"<p align=\"\\1\">",
			"<p style=\"line-height: \\1px; text-indent: \\2em; text-align: \\3;\">",
			"<span style=\"float: \\1;\">"
		), $message));
		$nest = 0;
		while(strpos($msglower, '[table') !== FALSE && strpos($msglower, '[/table]') !== FALSE){
			$message = preg_replace("/\[table(?:=(\d{1,4}%?)(?:,([\(\)%,#\w ]+))?)?\]\s*(.+?)\s*\[\/table\]/ies", "parsetable('\\1', '\\2', '\\3')", $message);
			if(++$nest > 4) break;
		}

		if($parsetype != 1) {
			if(strpos($msglower, '[/quote]') !== FALSE) {
				$message = preg_replace("/\s?\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s?/is", tpl_quote(), $message);
			}
			if(strpos($msglower, '[/free]') !== FALSE) {
				$message = preg_replace("/\s*\[free\][\n\r]*(.+?)[\n\r]*\[\/free\]\s*/is", tpl_free(), $message);
			}
		}
		if(strpos($msglower, '[/media]') !== FALSE) {
			$message = preg_replace("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/ies", $allowmediacode ? "parsemedia('\\1', '\\2')" : "bbcodeurl('\\2', '<a href=\"%s\" target=\"_blank\">%s</a>')", $message);
		}
		if($allowmediacode && strpos($msglower, '[/audio]') !== FALSE) {
			$message = preg_replace("/\[audio\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/ies", "parseaudio('\\1')", $message);
		}
		if($allowmediacode && strpos($msglower, '[/flash]') !== FALSE) {
			$message = preg_replace("/\[flash\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/is", "<script type=\"text/javascript\" reload=\"1\">document.write(AC_FL_RunContent('width', '550', 'height', '400', 'allowNetworking', 'internal', 'allowScriptAccess', 'never', 'src', '\\1', 'quality', 'high', 'bgcolor', '#ffffff', 'wmode', 'transparent', 'allowfullscreen', 'true'));</script>", $message);
		}
		if($parsetype != 1 && $allowbbcode == 2 && $GLOBALS['_DCACHE']['bbcodes']) {
			$message = preg_replace($GLOBALS['_DCACHE']['bbcodes']['searcharray'], $GLOBALS['_DCACHE']['bbcodes']['replacearray'], $message);
		}
		if($parsetype != 1 && strpos($msglower, '[/hide]') !== FALSE) {
			if(strpos($msglower, '[hide]') !== FALSE) {
				if($GLOBALS['authorreplyexist'] === '') {
					$GLOBALS['authorreplyexist'] = !$GLOBALS['forum']['ismoderator'] ? $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$tid' AND ".($discuz_uid ? "authorid='$discuz_uid'" : "authorid=0 AND useip='$GLOBALS[onlineip]'")." LIMIT 1") : TRUE;
				}
				if($GLOBALS['authorreplyexist']) {
					$message = preg_replace("/\[hide\]\s*(.+?)\s*\[\/hide\]/is", tpl_hide_reply(), $message);
				} else {
					$message = preg_replace("/\[hide\](.+?)\[\/hide\]/is", tpl_hide_reply_hidden(), $message);
					$message .= '<script type="text/javascript">replyreload += \',\' + '.$pid.';</script>';
				}
			}
			if(strpos($msglower, '[hide=') !== FALSE) {
				$message = preg_replace("/\[hide=(\d+)\]\s*(.+?)\s*\[\/hide\]/ies", "creditshide(\\1,'\\2', $pid)", $message);
			}
		}
	}

	if(!$bbcodeoff) {
		if($parsetype != 1 && strpos($msglower, '[swf]') !== FALSE) {
			$message = preg_replace("/\[swf\]\s*([^\[\<\r\n]+?)\s*\[\/swf\]/ies", "bbcodeurl('\\1', ' <img src=\"images/attachicons/flash.gif\" align=\"absmiddle\" alt=\"\" /> <a href=\"%s\" target=\"_blank\">Flash: %s</a> ')", $message);
		}
		if(strpos($msglower, '[/img]') !== FALSE) {
			$message = preg_replace(array(
				"/\[img\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/ies",
				"/\[img=(\d{1,4})[x|\,](\d{1,4})\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/ies"
			), $allowimgcode ? array(
				"bbcodeurl('\\1', '<img src=\"%s\" onload=\"thumbImg(this)\" alt=\"\" />')",
				"parseimg('\\1', '\\2', '\\3')"
			) : array(
				"bbcodeurl('\\1', '<a href=\"%s\" target=\"_blank\">%s</a>')",
				"bbcodeurl('\\3', '<a href=\"%s\" target=\"_blank\">%s</a>')"
			), $message);
		}
	}

	for($i = 0; $i <= $discuzcodes['pcodecount']; $i++) {
		$message = str_replace("[\tDISCUZ_CODE_$i\t]", $discuzcodes['codehtml'][$i], $message);
	}

	if($highlight) {
		$highlightarray = explode('+', $highlight);
		$sppos = strrpos($message, chr(0).chr(0).chr(0));
		if($sppos !== FALSE) {
			$specialextra = substr($postlist[$firstpid]['message'], $sppos + 3);
			$message = substr($message, 0, $sppos);
		}
		$message = preg_replace(array("/(^|>)([^<]+)(?=<|$)/sUe", "/<highlight>(.*)<\/highlight>/siU"), array("highlight('\\2', \$highlightarray, '\\1')", "<strong><font color=\"#FF0000\">\\1</font></strong>"), $message);
		if($sppos !== FALSE) {
			$message = $message.chr(0).chr(0).chr(0).$specialextra;
		}
	}

	unset($msglower);

	return $htmlon ? $message : nl2br(str_replace(array("\t", '   ', '  '), array('&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'), $message));
}

function parseurl($url, $text) {
	if(!$url && preg_match("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/|www\.)[^\[\"']+/i", trim($text), $matches)) {
		$url = $matches[0];
		$length = 65;
		if(strlen($url) > $length) {
			$text = substr($url, 0, intval($length * 0.5)).' ... '.substr($url, - intval($length * 0.3));
		}
		return '<a href="'.(substr(strtolower($url), 0, 4) == 'www.' ? 'http://'.$url : $url).'" target="_blank">'.$text.'</a>';
	} else {
		$url = substr($url, 1);
		if(substr(strtolower($url), 0, 4) == 'www.') {
			$url = 'http://'.$url;
		}
		return '<a href="'.$url.'" target="_blank">'.$text.'</a>';
	}
}

function parseattachurl($aid, $ext) {
	$GLOBALS['skipaidlist'][] = $aid;
	return $GLOBALS['boardurl'].'attachment.php?aid='.aidencode($aid).($ext ? '&request=yes&_f=.'.$ext : '');
}

function parseemail($email, $text) {
	if(!$email && preg_match("/\s*([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+)\s*/i", $text, $matches)) {
		$email = trim($matches[0]);
		return '<a href="mailto:'.$email.'">'.$email.'</a>';
	} else {
		return '<a href="mailto:'.substr($email, 1).'">'.$text.'</a>';
	}
}

function parsetable($width, $bgcolor, $message) {
	if(!preg_match("/^\[tr(?:=([\(\)%,#\w]+))?\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/", $message) && !preg_match("/^<tr[^>]*?>\s*<td[^>]*?>/", $message)) {
		return str_replace('\\"', '"', preg_replace("/\[tr(?:=([\(\)%,#\w]+))?\]|\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]|\[\/td\]|\[\/tr\]/", '', $message));
	}
	if(substr($width, -1) == '%') {
		$width = substr($width, 0, -1) <= 98 ? intval($width).'%' : '98%';
	} else {
		$width = intval($width);
		$width = $width ? ($width <= 560 ? $width.'px' : '98%') : '';
	}
	return '<table cellspacing="0" class="t_table" '.
		($width == '' ? NULL : 'style="width:'.$width.'"').
		($bgcolor ? ' bgcolor="'.$bgcolor.'">' : '>').
		str_replace('\\"', '"', preg_replace(array(
				"/\[tr(?:=([\(\)%,#\w]+))?\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/ie",
				"/\[\/td\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/ie",
				"/\[\/td\]\s*\[\/tr\]\s*/i"
			), array(
				"parsetrtd('\\1', '\\2', '\\3', '\\4')",
				"parsetrtd('td', '\\1', '\\2', '\\3')",
				'</td></tr>'
			), $message)
		).'</table>';
}

function parsetrtd($bgcolor, $colspan, $rowspan, $width) {
	return ($bgcolor == 'td' ? '</td>' : '<tr'.($bgcolor ? ' bgcolor="'.$bgcolor.'"' : '').'>').'<td'.($colspan > 1 ? ' colspan="'.$colspan.'"' : '').($rowspan > 1 ? ' rowspan="'.$rowspan.'"' : '').($width ? ' width="'.$width.'"' : '').'>';
}

function parseaudio($url, $width = 400, $autostart = 0) {
	$ext = strtolower(substr(strrchr($url, '.'), 1, 5));
	switch($ext) {
		case 'mp3':
		case 'wma':
		case 'mid':
		case 'wav':
			return '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="'.$width.'" height="64"><param name="invokeURLs" value="0"><param name="autostart" value="'.$autostart.'" /><param name="url" value="'.$url.'" /><embed src="'.$url.'" autostart="'.$autostart.'" type="application/x-mplayer2" width="'.$width.'" height="64"></embed></object>';
		case 'ra':
		case 'rm':
		case 'ram':
			$mediaid = 'media_'.random(3);
			return '<object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="'.$width.'" height="32"><param name="autostart" value="'.$autostart.'" /><param name="src" value="'.$url.'" /><param name="controls" value="controlpanel" /><param name="console" value="'.$mediaid.'_" /><embed src="'.$url.'" type="audio/x-pn-realaudio-plugin" controls="ControlPanel" console="'.$mediaid.'_" width="'.$width.'" height="32"></embed></object>';
	}
}

function parsemedia($params, $url) {
	$params = explode(',', $params);
	$width = intval($params[1]) > 800 ? 800 : intval($params[1]);
	$height = intval($params[2]) > 600 ? 600 : intval($params[2]);
	$autostart = !empty($params[3]) ? 1 : 0;
	if($flv = parseflv($url, $width, $height)) {
		return $flv;
	}
	if(in_array(count($params), array(3, 4))) {
		$type = $params[0];
		$url = str_replace(array('<', '>'), '', str_replace('\\"', '\"', $url));
		switch($type) {
			case 'mp3':
			case 'wma':
			case 'ra':
			case 'ram':
			case 'wav':
			case 'mid':
				return parseaudio($url, $width, $autostart);
			case 'rm':
			case 'rmvb':
			case 'rtsp':
				$mediaid = 'media_'.random(3);
				return '<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" width="'.$width.'" height="'.$height.'"><param name="autostart" value="'.$autostart.'" /><param name="src" value="'.$url.'" /><param name="controls" value="imagewindow" /><param name="console" value="'.$mediaid.'_" /><embed src="'.$url.'" type="audio/x-pn-realaudio-plugin" controls="imagewindow" console="'.$mediaid.'_" width="'.$width.'" height="'.$height.'"></embed></object><br /><object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="'.$width.'" height="32"><param name="src" value="'.$url.'" /><param name="controls" value="controlpanel" /><param name="console" value="'.$mediaid.'_" /><embed src="'.$url.'" type="audio/x-pn-realaudio-plugin" controls="controlpanel" console="'.$mediaid.'_" width="'.$width.'" height="32"'.($autostart ? ' autostart="true"' : '').'></embed></object>';
			case 'flv':
				return '<script type="text/javascript" reload="1">document.write(AC_FL_RunContent(\'width\', \''.$width.'\', \'height\', \''.$height.'\', \'allowNetworking\', \'internal\', \'allowScriptAccess\', \'never\', \'src\', \'images/common/flvplayer.swf\', \'flashvars\', \'file='.rawurlencode($url).'\', \'quality\', \'high\', \'wmode\', \'transparent\', \'allowfullscreen\', \'true\'));</script>';
			case 'swf':
				return '<script type="text/javascript" reload="1">document.write(AC_FL_RunContent(\'width\', \''.$width.'\', \'height\', \''.$height.'\', \'allowNetworking\', \'internal\', \'allowScriptAccess\', \'never\', \'src\', \''.$url.'\', \'quality\', \'high\', \'bgcolor\', \'#ffffff\', \'wmode\', \'transparent\', \'allowfullscreen\', \'true\'));</script>';
			case 'asf':
			case 'asx':
			case 'wmv':
			case 'mms':
			case 'avi':
			case 'mpg':
			case 'mpeg':
				return '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="'.$width.'" height="'.$height.'"><param name="invokeURLs" value="0"><param name="autostart" value="'.$autostart.'" /><param name="url" value="'.$url.'" /><embed src="'.$url.'" autostart="'.$autostart.'" type="application/x-mplayer2" width="'.$width.'" height="'.$height.'"></embed></object>';
			case 'mov':
				return '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="'.$width.'" height="'.$height.'"><param name="autostart" value="'.($autostart ? '' : 'false').'" /><param name="src" value="'.$url.'" /><embed src="'.$url.'" autostart="'.($autostart ? 'true' : 'false').'" type="video/quicktime" controller="true" width="'.$width.'" height="'.$height.'"></embed></object>';
			default:
				return '<a href="'.$url.'" target="_blank">'.$url.'</a>';
		}
	}
	return;
}

function bbcodeurl($url, $tags) {
	if(!preg_match("/<.+?>/s", $url)) {
		if(!in_array(strtolower(substr($url, 0, 6)), array('http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://'))) {
			$url = 'http://'.$url;
		}
		return str_replace(array('submit', 'logging.php'), array('', ''), sprintf($tags, $url, addslashes($url)));
	} else {
		return '&nbsp;'.$url;
	}
}

function jammer() {
	$randomstr = '';
	for($i = 0; $i < mt_rand(5, 15); $i++) {
		$randomstr .= chr(mt_rand(32, 59)).' '.chr(mt_rand(63, 126));
	}
	$seo = !$GLOBALS['tagstatus'] ? $GLOBALS['discuzcodes']['seoarray'][mt_rand(0, 5)] : '';
	return mt_rand(0, 1) ? '<font style="font-size:0px;color:'.WRAPBG.'">'.$seo.$randomstr.'</font>'."\r\n" :
		"\r\n".'<span style="display:none">'.$randomstr.$seo.'</span>';
}

function highlight($text, $words, $prepend) {
	$text = str_replace('\"', '"', $text);
	foreach($words AS $key => $replaceword) {
		$text = str_replace($replaceword, '<highlight>'.$replaceword.'</highlight>', $text);
	}
	return "$prepend$text";
}

function parseflv($url, $width, $height) {
	$lowerurl = strtolower($url);
	$flv = '';
	if($lowerurl != str_replace(array('player.youku.com/player.php/sid/','tudou.com/v/','player.ku6.com/refer/'), '', $lowerurl)) {
		$flv = $url;
	} elseif(strpos($lowerurl, 'v.youku.com/v_show/') !== FALSE) {
		if(preg_match("/http:\/\/v.youku.com\/v_show\/id_([^\/]+)(.html|)/i", $url, $matches)) {
			$flv = 'http://player.youku.com/player.php/sid/'.$matches[1].'/v.swf';
		}
	} elseif(strpos($lowerurl, 'tudou.com/programs/view/') !== FALSE) {
		if(preg_match("/http:\/\/(www.)?tudou.com\/programs\/view\/([^\/]+)/i", $url, $matches)) {
			$flv = 'http://www.tudou.com/v/'.$matches[2];
		}
	} elseif(strpos($lowerurl, 'v.ku6.com/show/') !== FALSE) {
		if(preg_match("/http:\/\/v.ku6.com\/show\/([^\/]+).html/i", $url, $matches)) {
			$flv = 'http://player.ku6.com/refer/'.$matches[1].'/v.swf';
		}
	} elseif(strpos($lowerurl, 'v.ku6.com/special/show_') !== FALSE) {
		if(preg_match("/http:\/\/v.ku6.com\/special\/show_\d+\/([^\/]+).html/i", $url, $matches)) {
			$flv = 'http://player.ku6.com/refer/'.$matches[1].'/v.swf';
		}
	}
	if($flv) {
		return '<script type="text/javascript" reload="1">document.write(AC_FL_RunContent(\'width\', \''.$width.'\', \'height\', \''.$height.'\', \'allowNetworking\', \'internal\', \'allowScriptAccess\', \'never\', \'src\', \''.$flv.'\', \'quality\', \'high\', \'bgcolor\', \'#ffffff\', \'wmode\', \'transparent\', \'allowfullscreen\', \'true\'));</script>';
	} else {
		return FALSE;
	}
}

function parseimg($width, $height, $src) {
	return bbcodeurl($src, '<img'.($width > 0 ? " width=\"$width\"" : '').($height > 0 ? " height=\"$height\"" : '')." src=\"$src\" border=\"0\" alt=\"\" />");
}

?>