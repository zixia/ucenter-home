<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: global.func.php 21342 2010-01-06 08:52:53Z zhaoxiongfei $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

	$ckey_length = 4;
	$key = md5($key ? $key : $GLOBALS['discuz_auth_key']);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}

function aidencode($aid) {
	static $sidauth = '';
	$sidauth = $sidauth != '' ? $sidauth : authcode($GLOBALS['sid'], 'ENCODE', $GLOBALS['authkey']);
	return rawurlencode(base64_encode($aid.'|'.substr(md5($aid.md5($GLOBALS['authkey']).$GLOBALS['timestamp']), 0, 8).'|'.$GLOBALS['timestamp'].'|'.$sidauth));
}

function clearcookies() {
	global $discuz_uid, $discuz_user, $discuz_pw, $discuz_secques, $adminid, $credits;
	foreach(array('sid', 'auth', 'visitedfid', 'onlinedetail', 'loginuser', 'activationauth', 'indextype') as $k) {
		dsetcookie($k);
	}
	$discuz_uid = $adminid = $credits = 0;
	$discuz_user = $discuz_pw = $discuz_secques = '';
}

function checklowerlimit($creditsarray, $coef = 1) {
	if(is_array($creditsarray)) {
		global $extcredits, $id;
		foreach($creditsarray as $id => $addcredits) {
			$addcredits = $addcredits * $coef;
			if($addcredits < 0 && ($GLOBALS['extcredits'.$id] < $extcredits[$id]['lowerlimit'] || (($GLOBALS['extcredits'.$id] + $addcredits) < $extcredits[$id]['lowerlimit']))) {
				showmessage('credits_policy_lowerlimit');
			}
		}
	}
}


function checkmd5($md5, $verified, $salt = '') {
	if(md5($md5.$salt) == $verified) {
		$result = !empty($salt) ? 1 : 2;
	} elseif(empty($salt)) {
		$result = $md5 == $verified ? 3 : ((strlen($verified) == 16 && substr($md5, 8, 16) == $verified) ? 4 : 0);
	} else {
		$result = 0;
	}
	return $result;
}

function checktplrefresh($maintpl, $subtpl, $timecompare, $templateid, $tpldir) {
	global $tplrefresh;
	if(empty($timecompare) || $tplrefresh == 1 || ($tplrefresh > 1 && !($GLOBALS['timestamp'] % $tplrefresh))) {
		if(empty($timecompare) || @filemtime($subtpl) > $timecompare) {
			require_once DISCUZ_ROOT.'./include/template.func.php';
			parse_template($maintpl, $templateid, $tpldir);
			return TRUE;
		}
	}
	return FALSE;
}

function cutstr($string, $length, $dot = ' ...') {
	global $charset;

	if(strlen($string) <= $length) {
		return $string;
	}

	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);

	$strcut = '';
	if(strtolower($charset) == 'utf-8') {

		$n = $tn = $noc = 0;
		while($n < strlen($string)) {

			$t = ord($string[$n]);
			if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1; $n++; $noc++;
			} elseif(194 <= $t && $t <= 223) {
				$tn = 2; $n += 2; $noc += 2;
			} elseif(224 <= $t && $t <= 239) {
				$tn = 3; $n += 3; $noc += 2;
			} elseif(240 <= $t && $t <= 247) {
				$tn = 4; $n += 4; $noc += 2;
			} elseif(248 <= $t && $t <= 251) {
				$tn = 5; $n += 5; $noc += 2;
			} elseif($t == 252 || $t == 253) {
				$tn = 6; $n += 6; $noc += 2;
			} else {
				$n++;
			}

			if($noc >= $length) {
				break;
			}

		}
		if($noc > $length) {
			$n -= $tn;
		}

		$strcut = substr($string, 0, $n);

	} else {
		for($i = 0; $i < $length; $i++) {
			$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
		}
	}

	$strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

	return $strcut.$dot;
}

function daddslashes($string, $force = 0) {
	!defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
	if(!MAGIC_QUOTES_GPC || $force) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = daddslashes($val, $force);
			}
		} else {
			$string = addslashes($string);
		}
	}
	return $string;
}

function datecheck($ymd, $sep='-') {
	if(!empty($ymd)) {
		list($year, $month, $day) = explode($sep, $ymd);
		return checkdate($month, $day, $year);
	} else {
		return FALSE;
	}
}

function debuginfo() {
	if($GLOBALS['debug']) {
		global $db, $discuz_starttime, $debuginfo;
		$mtime = explode(' ', microtime());
		$debuginfo = array('time' => number_format(($mtime[1] + $mtime[0] - $discuz_starttime), 6), 'queries' => $db->querynum);
		return TRUE;
	} else {
		return FALSE;
	}
}

function dexit($message = '') {
	echo $message;
	output();
	exit();
}

function dfopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE) {
	$return = '';
	$matches = parse_url($url);
	$host = $matches['host'];
	$path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
	$port = !empty($matches['port']) ? $matches['port'] : 80;

	if($post) {
		$out = "POST $path HTTP/1.0\r\n";
		$out .= "Accept: */*\r\n";
		//$out .= "Referer: $boardurl\r\n";
		$out .= "Accept-Language: zh-cn\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
		$out .= "Host: $host\r\n";
		$out .= 'Content-Length: '.strlen($post)."\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Cache-Control: no-cache\r\n";
		$out .= "Cookie: $cookie\r\n\r\n";
		$out .= $post;
	} else {
		$out = "GET $path HTTP/1.0\r\n";
		$out .= "Accept: */*\r\n";
		//$out .= "Referer: $boardurl\r\n";
		$out .= "Accept-Language: zh-cn\r\n";
		$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Cookie: $cookie\r\n\r\n";
	}
	$fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
	if(!$fp) {
		return '';
	} else {
		stream_set_blocking($fp, $block);
		stream_set_timeout($fp, $timeout);
		@fwrite($fp, $out);
		$status = stream_get_meta_data($fp);
		if(!$status['timed_out']) {
			while (!feof($fp)) {
				if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
					break;
				}
			}

			$stop = false;
			while(!feof($fp) && !$stop) {
				$data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
				$return .= $data;
				if($limit) {
					$limit -= strlen($data);
					$stop = $limit <= 0;
				}
			}
		}
		@fclose($fp);
		return $return;
	}
}

function dhtmlspecialchars($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dhtmlspecialchars($val);
		}
	} else {
		$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1',
		//$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1',
		str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
	}
	return $string;
}

function dheader($string, $replace = true, $http_response_code = 0) {
	$string = str_replace(array("\r", "\n"), array('', ''), $string);
	if(empty($http_response_code) || PHP_VERSION < '4.3' ) {
		@header($string, $replace);
	} else {
		@header($string, $replace, $http_response_code);
	}
	if(preg_match('/^\s*location:/is', $string)) {
		exit();
	}
}

function dreferer($default = '') {
	global $referer, $indexname;

	$default = empty($default) ? $indexname : '';
	if(empty($referer) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
		$referer = preg_replace("/([\?&])((sid\=[a-z0-9]{6})(&|$))/i", '\\1', $GLOBALS['_SERVER']['HTTP_REFERER']);
		$referer = substr($referer, -1) == '?' ? substr($referer, 0, -1) : $referer;
	} else {
		$referer = dhtmlspecialchars($referer);
	}

	if(strpos($referer, 'logging.php')) {
		$referer = $default;
	}
	return $referer;
}

function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false) {
	global $cookiepre, $cookiedomain, $cookiepath, $timestamp, $_SERVER;
	$var = ($prefix ? $cookiepre : '').$var;
	if($value == '' || $life < 0) {
		$value = '';
		$life = -1;
	}
	$life = $life > 0 ? $timestamp + $life : ($life < 0 ? $timestamp - 31536000 : 0);
	$path = $httponly && PHP_VERSION < '5.2.0' ? "$cookiepath; HttpOnly" : $cookiepath;
	$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
	if(PHP_VERSION < '5.2.0') {
		setcookie($var, $value, $life, $path, $cookiedomain, $secure);
	} else {
		setcookie($var, $value, $life, $path, $cookiedomain, $secure, $httponly);
	}
}

function dunlink($filename, $havethumb = 0, $remote = 0) {
	global $authkey, $ftp, $attachdir;
	if($remote) {
		require_once DISCUZ_ROOT.'./include/ftp.func.php';
		if(!$ftp['connid']) {
			if(!($ftp['connid'] = dftp_connect($ftp['host'], $ftp['username'], authcode($ftp['password'], 'DECODE', md5($authkey)), $ftp['attachdir'], $ftp['port'], $ftp['ssl']))) {
				return;
			}
		}
		dftp_delete($ftp['connid'], $filename);
		$havethumb && dftp_delete($ftp['connid'], $filename.'.thumb.jpg');
	} else {
		@unlink($attachdir.'/'.$filename);
		$havethumb && @unlink($attachdir.'/'.$filename.'.thumb.jpg');
	}
}

function dgmdate($format, $timestamp, $convert = 1) {
	$s = gmdate($format, $timestamp);
	if($GLOBALS['dateconvert'] && $convert) {
		if($GLOBALS['discuz_uid']) {
			if(!isset($GLOBALS['disableddateconvert'])) {
				$customshow = str_pad(base_convert($GLOBALS['customshow'], 10, 3), 4, '0', STR_PAD_LEFT);
				$GLOBALS['disableddateconvert'] = $customshow{0};
			}
			if($GLOBALS['disableddateconvert']) {
				return $s;
			}
		}
		if(!isset($GLOBALS['todaytimestamp'])) {
			$GLOBALS['todaytimestamp'] = $GLOBALS['timestamp'] - ($GLOBALS['timestamp'] + $GLOBALS['timeoffset'] * 3600) % 86400 + $GLOBALS['timeoffset'] * 3600;
		}
		$lang = $GLOBALS['dlang']['date'];
		$time = $GLOBALS['timestamp'] + $GLOBALS['timeoffset'] * 3600 - $timestamp;
		if($timestamp >= $GLOBALS['todaytimestamp']) {
			if($time > 3600) {
				return '<span title="'.$s.'">'.intval($time / 3600).'&nbsp;'.$lang[4].$lang[0].'</span>';
			} elseif($time > 1800) {
				return '<span title="'.$s.'">'.$lang[5].$lang[4].$lang[0].'</span>';
			} elseif($time > 60) {
				return '<span title="'.$s.'">'.intval($time / 60).'&nbsp;'.$lang[6].$lang[0].'</span>';
			} elseif($time > 0) {
				return '<span title="'.$s.'">'.$time.'&nbsp;'.$lang[7].$lang[0].'</span>';
			} elseif($time == 0) {
				return '<span title="'.$s.'">'.$lang[8].'</span>';
			} else {
				return $s;
			}
		} elseif(($days = intval(($GLOBALS['todaytimestamp'] - $timestamp) / 86400)) >= 0 && $days < 7) {
			if($days == 0) {
				return '<span title="'.$s.'">'.$lang[2].'&nbsp;'.gmdate($GLOBALS['timeformat'], $timestamp).'</span>';
			} elseif($days == 1) {
				return '<span title="'.$s.'">'.$lang[3].'&nbsp;'.gmdate($GLOBALS['timeformat'], $timestamp).'</span>';
			} else {
				return '<span title="'.$s.'">'.($days + 1).'&nbsp;'.$lang[1].$lang[0].'&nbsp;'.gmdate($GLOBALS['timeformat'], $timestamp).'</span>';
			}
		} else {
			return $s;
		}
	} else {
		return $s;
	}
}

function errorlog($type, $message, $halt = 1) {
	global $timestamp, $discuz_userss, $onlineip, $_SERVER;
	$user = empty($discuz_userss) ? '' : $discuz_userss.'<br />';
	$user .= $onlineip.'|'.$_SERVER['REMOTE_ADDR'];
	writelog('errorlog', dhtmlspecialchars("$timestamp\t$type\t$user\t".str_replace(array("\r", "\n"), array(' ', ' '), trim($message))));
	if($halt) {
		exit();
	}
}

function fileext($filename) {
	return trim(substr(strrchr($filename, '.'), 1, 10));
}

function formhash($specialadd = '') {
	global $discuz_user, $discuz_uid, $discuz_pw, $timestamp, $discuz_auth_key;
	$hashadd = defined('IN_ADMINCP') ? 'Only For Discuz! Admin Control Panel' : '';
	return substr(md5(substr($timestamp, 0, -7).$discuz_user.$discuz_uid.$discuz_pw.$discuz_auth_key.$hashadd.$specialadd), 8, 8);
}

function forumperm($permstr) {
	global $groupid, $extgroupids;

	$groupidarray = array($groupid);
	foreach(explode("\t", $extgroupids) as $extgroupid) {
		if($extgroupid = intval(trim($extgroupid))) {
			$groupidarray[] = $extgroupid;
		}
	}
	return preg_match("/(^|\t)(".implode('|', $groupidarray).")(\t|$)/", $permstr);
}

function formulaperm($formula, $type = 0, $wap = FALSE) {
	global $db, $tablepre, $_DSESSION, $extcredits, $formulamessage, $usermsg, $forum, $language, $medalstatus, $discuz_uid, $timestamp;

	$formula = unserialize($formula);
	$medalperm = $formula['medal'];
	$permusers = $formula['users'];
	$permmessage = $formula['message'];
	if(!$type && $medalstatus && $medalperm) {
		$exists = 1;
		$formulamessage = '';
		$medalpermc = $medalperm;
		if($discuz_uid) {
			$medals = explode("\t", $db->result_first("SELECT medals FROM {$tablepre}memberfields WHERE uid='$discuz_uid'"));
			foreach($medalperm as $k => $medal) {
				foreach($medals as $r) {
					list($medalid) = explode("|", $r);
					if($medalid == $medal) {
						$exists = 0;
						unset($medalpermc[$k]);
					}
				}
			}
		} else {
			$exists = 0;
		}
		if($medalpermc) {
			if(!$wap) {
				@include DISCUZ_ROOT.'./forumdata/cache/cache_medals.php';
				foreach($medalpermc as $medal) {
					if($_DCACHE['medals'][$medal]) {
						$formulamessage .= '<img src="images/common/'.$_DCACHE['medals'][$medal]['image'].'" />'.$_DCACHE['medals'][$medal]['name'].'&nbsp; ';
					}
				}
				showmessage('forum_permforum_nomedal', NULL, 'NOPERM');
			} else {
				wapmsg('forum_nopermission');
			}
		}
	}
	$formula = $formula[1];
	if(!$type && ($_DSESSION['adminid'] == 1 || $forum['ismoderator'])) {
		return FALSE;
	}
	if(!$type && $permusers) {
		$permusers = str_replace(array("\r\n", "\r"), array("\n", "\n"), $permusers);
		$permusers = explode("\n", trim($permusers));
		if(!in_array($GLOBALS['discuz_user'], $permusers)) {
			showmessage('forum_permforum_disallow', NULL, 'NOPERM');
		}
	}
	if(!$formula) {
		return FALSE;
	}
	if(strexists($formula, '$memberformula[')) {
		preg_match_all("/\\\$memberformula\['(\w+?)'\]/", $formula, $a);
		$fields = $profilefields = array();
		$mfadd = '';
		foreach($a[1] as $field) {
			switch($field) {
				case 'regdate':
					$formula = preg_replace("/\{(\d{4})\-(\d{1,2})\-(\d{1,2})\}/e", "'\\1-'.sprintf('%02d', '\\2').'-'.sprintf('%02d', '\\3')", $formula);
				case 'regday':
					$fields[] = 'm.regdate';break;
				case 'regip':
				case 'lastip':
					$formula = preg_replace("/\{([\d\.]+?)\}/", "'\\1'", $formula);
					$fields[] = 'm.'.$field;break;
				case substr($field, 0, 6) == 'field_':
					$profilefields[] = $field;
				case 'buyercredit':
				case 'sellercredit':
					$mfadd = "LEFT JOIN {$tablepre}memberfields mf ON m.uid=mf.uid";
					$fields[] = 'mf.'.$field;break;
			}
		}
		$memberformula = array();
		if($discuz_uid) {
			$memberformula = $db->fetch_first("SELECT ".implode(',', $fields)." FROM {$tablepre}members m $mfadd WHERE m.uid='$discuz_uid'");
			if(in_array('regday', $a[1])) {
				$memberformula['regday'] = intval(($timestamp - $memberformula['regdate']) / 86400);
			}
			if(in_array('regdate', $a[1])) {
				$memberformula['regdate'] = date('Y-m-d', $memberformula['regdate']);
			}
			$memberformula['lastip'] = $memberformula['lastip'] ? $memberformula['lastip'] : $GLOBALS['onlineip'];
		} else {
			if(isset($memberformula['regip'])) {
				$memberformula['regip'] = $GLOBALS['onlineip'];
			}
			if(isset($memberformula['lastip'])) {
				$memberformula['lastip'] = $GLOBALS['onlineip'];
			}
		}
	}
	@eval("\$formulaperm = ($formula) ? TRUE : FALSE;");
	if(!$formulaperm || $type == 2) {
		if(!$permmessage) {
			include_once language('misc');
			$search = array('$memberformula[\'regdate\']', '$memberformula[\'regday\']', '$memberformula[\'regip\']', '$memberformula[\'lastip\']', '$memberformula[\'buyercredit\']', '$memberformula[\'sellercredit\']', '$_DSESSION[\'digestposts\']', '$_DSESSION[\'posts\']', '$_DSESSION[\'threads\']', '$_DSESSION[\'oltime\']', '$_DSESSION[\'pageviews\']');
			$replace = array($language['formulaperm_regdate'], $language['formulaperm_regday'], $language['formulaperm_regip'], $language['formulaperm_lastip'], $language['formulaperm_buyercredit'], $language['formulaperm_sellercredit'], $language['formulaperm_digestposts'], $language['formulaperm_posts'], $language['formulaperm_threads'], $language['formulaperm_oltime'], $language['formulaperm_pageviews']);
			for($i = 1; $i <= 8; $i++) {
				$search[] = '$_DSESSION[\'extcredits'.$i.'\']';
				$replace[] = $extcredits[$i]['title'] ? $extcredits[$i]['title'] : $language['formulaperm_extcredits'].$i;
			}
			if($profilefields) {
				@include DISCUZ_ROOT.'./forumdata/cache/cache_profilefields.php';
				foreach($profilefields as $profilefield) {
					$search[] = '$memberformula[\''.$profilefield.'\']';
					$replace[] = !empty($_DCACHE['fields_optional'][$profilefield]) ? $_DCACHE['fields_optional'][$profilefield]['title'] : $_DCACHE['fields_required'][$profilefield]['title'];
				}
			}
			$i = 0;$usermsg = '';
			foreach($search as $s) {
				if(!in_array($s, array('$memberformula[\'regdate\']', '$memberformula[\'regip\']', '$memberformula[\'lastip\']'))) {
					$usermsg .= strexists($formula, $s) ? '<br />&nbsp;&nbsp;&nbsp;'.$replace[$i].': '.(@eval('return intval('.$s.');')) : '';
				} elseif($s == '$memberformula[\'regdate\']') {
					$usermsg .= strexists($formula, $s) ? '<br />&nbsp;&nbsp;&nbsp;'.$replace[$i].': '.(@eval('return '.$s.';')) : '';
				}
				$i++;
			}
			$search = array_merge($search, array('and', 'or', '>=', '<=', '=='));
			$replace = array_merge($replace, array('&nbsp;&nbsp;<b>'.$language['formulaperm_and'].'</b>&nbsp;&nbsp;', '&nbsp;&nbsp;<b>'.$language['formulaperm_or'].'</b>&nbsp;&nbsp;', '&ge;', '&le;', '='));
			$formulamessage = str_replace($search, $replace, $formula);
		} else {
			$formulamessage = nl2br(htmlspecialchars($permmessage));
		}

		if($type == 1 || $type == 2) {
			return $formulamessage;
		} elseif(!$wap) {
			if(!$permmessage) {
				showmessage('forum_permforum_nopermission', NULL, 'NOPERM');
			} else {
				showmessage('forum_permforum_nopermission_custommsg', NULL, 'NOPERM');
			}
		} else {
			wapmsg('forum_nopermission');
		}
	}
	return TRUE;
}

function getgroupid($uid, $group, &$member) {
	global $creditsformula, $db, $tablepre, $dzfeed_limit;

	if(!empty($creditsformula)) {
		$updatearray = array();
		eval("\$credits = round($creditsformula);");

		if($credits != $member['credits']) {

			$send_feed = false;
			if(is_array($dzfeed_limit['user_credit'])) foreach($dzfeed_limit['user_credit'] as $val) {
				if($member['credits'] < $val && $credits > $val) {
					$send_feed = true;
					$count = $val;
				}
			}
			if($send_feed) {
				$arg = $data = array();
				$arg['type'] = 'user_credit';
				$arg['uid'] = $uid;
				$arg['username'] = addslashes($member['username'] ? $member['username'] : $member['discuz_user']);
				$data['title']['actor'] = "<a href=\"space.php?uid={$arg[uid]}\" target=\"_blank\">".($member['username'] ? $member['username'] : $member['discuz_user'])."</a>";
				$data['title']['count'] = $count;
				add_feed($arg, $data);
			}

			$updatearray[] = "credits='$credits'";
			$member['credits'] = $credits;
		}

		if($group['type'] == 'member' && !($member['credits'] >= $group['creditshigher'] && $member['credits'] < $group['creditslower'])) {
			$query = $db->query("SELECT groupid FROM {$tablepre}usergroups WHERE type='member' AND $member[credits]>=creditshigher AND $member[credits]<creditslower LIMIT 1");
			if($db->num_rows($query)) {
				$newgroupid = $db->result($query, 0);
				$query = $db->query("SELECT groupid FROM {$tablepre}members WHERE uid='$uid'");
				$member['groupid'] = $db->result($query, 0);
				if($member['groupid'] != $newgroupid) {
					$member['groupid'] = $newgroupid;
					$updatearray[] = "groupid='$member[groupid]'";

					include language('notice');
					$grouptitle = $db->result_first("SELECT grouptitle FROM {$tablepre}usergroups WHERE groupid='$member[groupid]'");
					$data = array();
					$data['usergroup'] = "<a href=\"faq.php?action=grouppermission&searchgroupid={$member[groupid]}\" target=\"_blank\">{$grouptitle}</a>";
					$msg_template = $language['user_usergroup'];
					$message = transval($msg_template, $data);
					sendnotice($uid, $message, 'systempm');

					if(is_array($dzfeed_limit['user_usergroup']) && in_array($member['groupid'], $dzfeed_limit['user_usergroup'])) {
						$arg = $data = array();
						$arg['type'] = 'user_usergroup';
						$arg['uid'] = $uid;
						$arg['username'] = addslashes($member['username'] ? $member['username'] : $member['discuz_user']);
						$data['title']['actor'] = "<a href=\"space.php?uid={$arg[uid]}\" target=\"_blank\">".($member['username'] ? $member['username'] : $member['discuz_user'])."</a>";
						$data['title']['usergroup'] = "<a href=\"faq.php?action=grouppermission&searchgroupid={$member[groupid]}\" target=\"_blank\">{$grouptitle}</a>";
						add_feed($arg, $data);
					}
				}
			}
		}
		if($updatearray) {
			$db->query("UPDATE {$tablepre}members SET ".implode(', ', $updatearray)." WHERE uid='$uid'");
		}
	}

	return $member['groupid'];
}

function getrobot() {
	if(!defined('IS_ROBOT')) {
		$kw_spiders = 'Bot|Crawl|Spider|slurp|sohu-search|lycos|robozilla';
		$kw_browsers = 'MSIE|Netscape|Opera|Konqueror|Mozilla';
		if(!strexists($_SERVER['HTTP_USER_AGENT'], 'http://') && preg_match("/($kw_browsers)/i", $_SERVER['HTTP_USER_AGENT'])) {
			define('IS_ROBOT', FALSE);
		} elseif(preg_match("/($kw_spiders)/i", $_SERVER['HTTP_USER_AGENT'])) {
			define('IS_ROBOT', TRUE);
		} else {
			define('IS_ROBOT', FALSE);
		}
	}
	return IS_ROBOT;
}

function get_home($uid) {
	$uid = sprintf("%05d", $uid);
	$dir1 = substr($uid, 0, -4);
	$dir2 = substr($uid, -4, 2);
	$dir3 = substr($uid, -2, 2);
	return $dir1.'/'.$dir2.'/'.$dir3;
}

function groupexpiry($terms) {
	$terms = is_array($terms) ? $terms : unserialize($terms);
	$groupexpiry = isset($terms['main']['time']) ? intval($terms['main']['time']) : 0;
	if(is_array($terms['ext'])) {
		foreach($terms['ext'] as $expiry) {
			if((!$groupexpiry && $expiry) || $expiry < $groupexpiry) {
				$groupexpiry = $expiry;
			}
		}
	}
	return $groupexpiry;
}

function ipaccess($ip, $accesslist) {
	return preg_match("/^(".str_replace(array("\r\n", ' '), array('|', ''), preg_quote($accesslist, '/')).")/", $ip);
}

function implodeids($array) {
	if(!empty($array)) {
		return "'".implode("','", is_array($array) ? $array : array($array))."'";
	} else {
		return '';
	}
}

function ipbanned($onlineip) {
	global $ipaccess, $timestamp, $cachelost;

	if($ipaccess && !ipaccess($onlineip, $ipaccess)) {
		return TRUE;
	}

	$cachelost .= (@include DISCUZ_ROOT.'./forumdata/cache/cache_ipbanned.php') ? '' : ' ipbanned';
	if(empty($_DCACHE['ipbanned'])) {
		return FALSE;
	} else {
		if($_DCACHE['ipbanned']['expiration'] < $timestamp) {
			@unlink(DISCUZ_ROOT.'./forumdata/cache/cache_ipbanned.php');
		}
		return preg_match("/^(".$_DCACHE['ipbanned']['regexp'].")$/", $onlineip);
	}
}

function isemail($email) {
	return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}

function language($file, $templateid = 0, $tpldir = '') {
	$tpldir = $tpldir ? $tpldir : TPLDIR;
	$templateid = $templateid ? $templateid : TEMPLATEID;

	$languagepack = DISCUZ_ROOT.'./'.$tpldir.'/'.$file.'.lang.php';
	if(file_exists($languagepack)) {
		return $languagepack;
	} elseif($templateid != 1 && $tpldir != './templates/default') {
		return language($file, 1, './templates/default');
	} else {
		return FALSE;
	}
}

function modthreadkey($tid) {
	global $adminid, $discuz_user, $discuz_uid, $discuz_pw, $timestamp, $discuz_auth_key;
	return $adminid > 0 ? md5($discuz_user.$discuz_uid.$discuz_auth_key.substr($timestamp, 0, -7).$tid) : '';
}

function multi($num, $perpage, $curpage, $mpurl, $maxpages = 0, $page = 10, $autogoto = TRUE, $simple = FALSE) {
	global $maxpage;
	$ajaxtarget = !empty($_GET['ajaxtarget']) ? " ajaxtarget=\"".dhtmlspecialchars($_GET['ajaxtarget'])."\" " : '';

	if(defined('IN_ADMINCP')) {
		$shownum = $showkbd = TRUE;
		$lang['prev'] = '&lsaquo;&lsaquo;';
		$lang['next'] = '&rsaquo;&rsaquo;';
	} else {
		$shownum = $showkbd = FALSE;
		$lang['prev'] = '&nbsp';
		$lang['next'] = $GLOBALS['dlang']['nextpage'];
	}

	$multipage = '';
	$mpurl .= strpos($mpurl, '?') ? '&amp;' : '?';
	$realpages = 1;
	if($num > $perpage) {
		$offset = 2;

		$realpages = @ceil($num / $perpage);
		$pages = $maxpages && $maxpages < $realpages ? $maxpages : $realpages;

		if($page > $pages) {
			$from = 1;
			$to = $pages;
		} else {
			$from = $curpage - $offset;
			$to = $from + $page - 1;
			if($from < 1) {
				$to = $curpage + 1 - $from;
				$from = 1;
				if($to - $from < $page) {
					$to = $page;
				}
			} elseif($to > $pages) {
				$from = $pages - $page + 1;
				$to = $pages;
			}
		}

		$multipage = ($curpage - $offset > 1 && $pages > $page ? '<a href="'.$mpurl.'page=1" class="first"'.$ajaxtarget.'>1 ...</a>' : '').
			($curpage > 1 && !$simple ? '<a href="'.$mpurl.'page='.($curpage - 1).'" class="prev"'.$ajaxtarget.'>'.$lang['prev'].'</a>' : '');
		for($i = $from; $i <= $to; $i++) {
			$multipage .= $i == $curpage ? '<strong>'.$i.'</strong>' :
				'<a href="'.$mpurl.'page='.$i.($ajaxtarget && $i == $pages && $autogoto ? '#' : '').'"'.$ajaxtarget.'>'.$i.'</a>';
		}

		$multipage .= ($to < $pages ? '<a href="'.$mpurl.'page='.$pages.'" class="last"'.$ajaxtarget.'>... '.$realpages.'</a>' : '').
			($curpage < $pages && !$simple ? '<a href="'.$mpurl.'page='.($curpage + 1).'" class="next"'.$ajaxtarget.'>'.$lang['next'].'</a>' : '').
			($showkbd && !$simple && $pages > $page && !$ajaxtarget ? '<kbd><input type="text" name="custompage" size="3" onkeydown="if(event.keyCode==13) {window.location=\''.$mpurl.'page=\'+this.value; return false;}" /></kbd>' : '');

		$multipage = $multipage ? '<div class="pages">'.($shownum && !$simple ? '<em>&nbsp;'.$num.'&nbsp;</em>' : '').$multipage.'</div>' : '';
	}
	$maxpage = $realpages;
	return $multipage;
}

function output() {
	if(defined('DISCUZ_OUTPUTED')) {
		return;
	}
	define('DISCUZ_OUTPUTED', 1);
	global $sid, $transsidstatus, $rewritestatus, $ftp, $advlist, $thread, $inajax, $forumdomains, $binddomains, $indexname;

	if($advlist && !defined('IN_ADMINCP') && !$inajax) {
		include template('adv');
	}
	funcstat();
	stat_code();

	if(($transsidstatus = empty($GLOBALS['_DCOOKIE']['sid']) && $transsidstatus) || $rewritestatus || ($binddomains && $forumdomains)) {
		$content = ob_get_contents();
		if($transsidstatus) {
			$searcharray = array
				(
				"/\<a(\s*[^\>]+\s*)href\=([\"|\']?)([^\"\'\s]+)/ies",
				"/(\<form.+?\>)/is"
				);
			$replacearray = array
				(
				"transsid('\\3','<a\\1href=\\2')",
				"\\1\n<input type=\"hidden\" name=\"sid\" value=\"$sid\" />"
				);
			$content = preg_replace($searcharray, $replacearray, $content);
		}

		if($binddomains && $forumdomains) {
			$bindsearcharray = $bindreplacearray = array();
			$indexname = basename($indexname);
			foreach($forumdomains as $fid => $domain) {
				$bindsearcharray[] = "href=\"forumdisplay.php?fid=$fid&amp;";
				$bindreplacearray[] = 'href="http://'.$domain.'/'.$indexname.'?';
				$bindsearcharray[] = "href=\"forumdisplay.php?fid=$fid";
				$bindreplacearray[] = 'href="http://'.$domain.'/'.$indexname;
			}
			$content = str_replace($bindsearcharray, $bindreplacearray, $content);
		}

		if($rewritestatus) {
			$searcharray = $replacearray = array();
			if($rewritestatus & 1) {
				$searcharray[] = "/\<a href\=\"forumdisplay\.php\?fid\=(\d+)(&amp;page\=(\d+))?\"([^\>]*)\>/e";
				$replacearray[] = "rewrite_forum('\\1', '\\3', '\\4')";
			}
			if($rewritestatus & 2) {
				$searcharray[] = "/\<a href\=\"viewthread\.php\?tid\=(\d+)(&amp;extra\=page\%3D(\d+))?(&amp;page\=(\d+))?\"([^\>]*)\>/e";
				$replacearray[] = "rewrite_thread('\\1', '\\5', '\\3', '\\6')";
			}
			if($rewritestatus & 4) {
				$searcharray[] = "/\<a href\=\"space\.php\?(uid\=(\d+)|username\=([^&]+?))\"([^\>]*)\>/e";
				$replacearray[] = "rewrite_space('\\2', '\\3', '\\4')";
			}
			if($rewritestatus & 8) {
				$searcharray[] = "/\<a href\=\"tag\.php\?name\=([^&]+?)\"([^\>]*)\>/e";
				$replacearray[] = "rewrite_tag('\\1', '\\2')";
			}
			$content = preg_replace($searcharray, $replacearray, $content);
		}

		ob_end_clean();
		$GLOBALS['gzipcompress'] ? ob_start('ob_gzhandler') : ob_start();

		echo $content;
	}
	if($ftp['connid']) {
		@ftp_close($ftp['connid']);
	}
	$ftp = array();

	if(defined('CACHE_FILE') && CACHE_FILE && !defined('CACHE_FORBIDDEN')) {
		global $cachethreaddir;
		if(diskfreespace(DISCUZ_ROOT.'./'.$cachethreaddir) > 1000000) {
			if($fp = @fopen(CACHE_FILE, 'w')) {
				flock($fp, LOCK_EX);
				fwrite($fp, empty($content) ? ob_get_contents() : $content);
			}
			@fclose($fp);
			chmod(CACHE_FILE, 0777);
		}
	}
}

function periodscheck($periods, $showmessage = 1) {
	global $timestamp, $disableperiodctrl, $_DCACHE, $banperiods;

	if(!$disableperiodctrl && $_DCACHE['settings'][$periods]) {
		$now = gmdate('G.i', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);
		foreach(explode("\r\n", str_replace(':', '.', $_DCACHE['settings'][$periods])) as $period) {
			list($periodbegin, $periodend) = explode('-', $period);
			if(($periodbegin > $periodend && ($now >= $periodbegin || $now < $periodend)) || ($periodbegin < $periodend && $now >= $periodbegin && $now < $periodend)) {
				$banperiods = str_replace("\r\n", ', ', $_DCACHE['settings'][$periods]);
				if($showmessage) {
					showmessage('period_nopermission', NULL, 'NOPERM');
				} else {
					return TRUE;
				}
			}
		}
	}
	return FALSE;
}

function quescrypt($questionid, $answer) {
	return $questionid > 0 && $answer != '' ? substr(md5($answer.md5($questionid)), 16, 8) : '';
}

function rewrite_thread($tid, $page = 0, $prevpage = 0, $extra = '') {
	return '<a href="thread-'.$tid.'-'.($page ? $page : 1).'-'.($prevpage && !IS_ROBOT ? $prevpage : 1).'.html"'.stripslashes($extra).'>';
}

function rewrite_forum($fid, $page = 0, $extra = '') {
	return '<a href="forum-'.$fid.'-'.($page ? $page : 1).'.html"'.stripslashes($extra).'>';
}

function rewrite_space($uid, $username, $extra = '') {
	$GLOBALS['rewritecompatible'] && $username = rawurlencode($username);
	return '<a href="space-'.($uid ? 'uid-'.$uid : 'username-'.$username).'.html"'.stripslashes($extra).'>';
}

function rewrite_tag($name, $extra = '') {
	$GLOBALS['rewritecompatible'] && $name = rawurlencode($name);
	return '<a href="tag-'.$name.'.html"'.stripslashes($extra).'>';
}

function random($length, $numeric = 0) {
	PHP_VERSION < '4.2.0' ? mt_srand((double)microtime() * 1000000) : mt_srand();
	$seed = base_convert(md5(print_r($_SERVER, 1).microtime()), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

function request($cachekey, $fid = 0, $type = 0, $return = 0) {
	global $timestamp, $_DCACHE;
	$datalist = '';
	if($fid && CURSCRIPT == 'forumdisplay') {
		$specialfid = $GLOBALS['forum']['fid'];
		$key = $cachekey = empty($GLOBALS['infosidestatus']['f'.$specialfid][$type]) ? $GLOBALS['infosidestatus'][$type] : $GLOBALS['infosidestatus']['f'.$specialfid][$type];
		$cachekey .= '_fid'.$specialfid;
	} else {
		$specialfid = 0;
		if(!$type) {
			$key = $cachekey;
		} else {
			$key = $cachekey = $cachekey[$type];
		}
	}
	$cachefile = DISCUZ_ROOT.'./forumdata/cache/request_'.md5($cachekey).'.php';
	if(((@!include($cachefile)) || $expiration < $timestamp) && (!file_exists($cachefile.'.lock') || $timestamp - filemtime($cachefile.'.lock') > 3600)) {
		include_once DISCUZ_ROOT.'./forumdata/cache/cache_request.php';
		require_once DISCUZ_ROOT.'./include/request.func.php';
		parse_str($_DCACHE['request'][$key]['url'], $requestdata);
		$datalist = parse_request($requestdata, $cachefile, 0, $specialfid, $key);
	}
	if(!empty($nocachedata)) {
		include_once DISCUZ_ROOT.'./forumdata/cache/cache_request.php';
		require_once DISCUZ_ROOT.'./include/request.func.php';
		foreach($nocachedata as $key => $v) {
			$cachefile = DISCUZ_ROOT.'./forumdata/cache/request_'.md5($key).'.php';
			if(!file_exists($cachefile.'.lock')) {
				parse_str($_DCACHE['request'][$key]['url'], $requestdata);
				$datalist = str_replace($v, parse_request($requestdata, $cachefile, 0, $specialfid, $key), $datalist);
			}
		}
	}
	if(!$return) {
		echo $datalist;
	} else {
		return $datalist;
	}
}

function sendmail($email_to, $email_subject, $email_message, $email_from = '') {
	extract($GLOBALS, EXTR_SKIP);
	require DISCUZ_ROOT.'./include/sendmail.inc.php';
}

function sendnotice($toid, $message, $type, $extraid = 0, $actor = array(), $uselang = 1) {
	if(!$toid || $message === '') {
		return;
	}
	extract($GLOBALS, EXTR_SKIP);
	if($uselang) {
		include language('notice');
		if(isset($language[$message])) {
			eval("\$message = addslashes(\"".$language[$message]."\");");
		}
	}

	$typeid = $prompts[$type]['id'];
	if(!$typeid) {
		return;
	}
	$toids = explode(',', $toid);
	foreach($toids as $toid) {
		$keysadd = $valuesadd = $statnewnotice = '';
		if($extraid && $actor) {
			$promptmsg = $db->fetch_first("SELECT actor FROM {$tablepre}promptmsgs WHERE uid='$toid' AND typeid='$typeid' AND extraid='$extraid' LIMIT 1");
			if($promptmsg) {
				list($actorcount, $actors) = explode("\t", $promptmsg['actor']);
				$actorarray = array_unique(explode(',', $actors));
				if(!in_array($actor['user'], $actorarray)) {
					array_unshift($actorarray, $actor['user']);
					$actors = implode(',', array_slice($actorarray, 0, $actor['maxusers']));
					$actorcount++;
				}
				$statnewnotice = 1;
				$db->query("UPDATE {$tablepre}promptmsgs SET actor='".addslashes($actorcount."\t".$actors)."', dateline='$timestamp', new='1' WHERE uid='$toid' AND typeid='$typeid' AND extraid='$extraid'");
			} else {
				$statnewnotice = 1;
				$db->query("INSERT INTO {$tablepre}promptmsgs (typeid, uid, new, dateline, message, extraid, actor) VALUES ('$typeid', '$toid', '1', '$timestamp', '$message', '$extraid', '".addslashes("1\t".$actor['user'])."')");
			}
		} else {
			$statnewnotice = 1;
			$db->query("INSERT INTO {$tablepre}promptmsgs (typeid, uid, new, dateline, message) VALUES ('$typeid', '$toid', '1', '$timestamp', '$message')");
		}
		if($statnewnotice) {
			write_statlog('', 'action=counttype&typeid='.$typeid, '', '', 'notice.php');
		}
		$count = $db->result_first("SELECT count(*) FROM {$tablepre}promptmsgs WHERE uid='$toid' AND typeid='$typeid' AND new='1'");
		updateprompt($type, $toid, $count);
	}
}

function sendpm($toid, $subject, $message, $fromid = '') {
	if($fromid === '') {
		require_once DISCUZ_ROOT.'./uc_client/client.php';
		$fromid = $discuz_uid;
	}
	if($fromid) {
		uc_pm_send($fromid, $toid, $subject, $message);
	} else {
		global $promptkeys;
		if(in_array($subject, $promptkeys)) {
			$type = $subject;
		} else {
			extract($GLOBALS, EXTR_SKIP);
			require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
			eval("\$message = addslashes(\"".$message."\");");
			$type = 'systempm';
			$message = '<div>'.$subject.' {time}<br />'.discuzcode($message, 1, 0).'</div>';
		}
		sendnotice($toid, $message, $type);
	}
}

function showmessage($message, $url_forward = '', $extra = '', $forwardtype = 0) {
	extract($GLOBALS, EXTR_SKIP);
	global $hookscriptmessage, $extrahead, $discuz_uid, $discuz_action, $debuginfo, $seccode, $seccodestatus, $fid, $tid, $charset, $show_message, $inajax, $_DCACHE, $advlist;
	define('CACHE_FORBIDDEN', TRUE);
	$hookscriptmessage = $show_message = $message;$messagehandle = 0;
	$msgforward = unserialize($_DCACHE['settings']['msgforward']);
	$refreshtime = intval($msgforward['refreshtime']);
	$refreshtime = empty($forwardtype) ? $refreshtime : ($refreshtime ? $refreshtime : 3);
	$msgforward['refreshtime'] = $refreshtime * 1000;
	$url_forward = empty($url_forward) ? '' : (empty($_DCOOKIE['sid']) && $transsidstatus ? transsid($url_forward) : $url_forward);
	$seccodecheck = $seccodestatus & 2;
	if($_DCACHE['settings']['funcsiteid'] && $_DCACHE['settings']['funckey'] && $funcstatinfo && !IS_ROBOT) {
		$statlogfile = DISCUZ_ROOT.'./forumdata/funcstat.log';
		if($fp = @fopen($statlogfile, 'a')) {
			@flock($fp, 2);
			if(is_array($funcstatinfo)) {
				$funcstatinfo = array_unique($funcstatinfo);
				foreach($funcstatinfo as $funcinfo) {
					fwrite($fp, funcstat_query($funcinfo, $message)."\n");
				}
			} else {
				fwrite($fp, funcstat_query($funcstatinfo, $message)."\n");
			}
			fclose($fp);
			$funcstatinfo = $GLOBALS['funcstatinfo'] = '';
		}
	}

	if(!defined('STAT_DISABLED') && STAT_ID > 0 && !IS_ROBOT) {
		write_statlog($message);
	}

	if($url_forward && (!empty($quickforward) || empty($inajax) && $msgforward['quick'] && $msgforward['messages'] && @in_array($message, $msgforward['messages']))) {
		updatesession();
		dheader("location: ".str_replace('&amp;', '&', $url_forward));
	}
	if(!empty($infloat)) {
		if($extra) {
			$messagehandle = $extra;
		}
		$extra = '';
	}
	if(in_array($extra, array('HALTED', 'NOPERM'))) {
		$discuz_action = 254;
	} else {
		$discuz_action = 255;
	}

	include language('messages');

	$vars = explode(':', $message);
	if(count($vars) == 2 && isset($scriptlang[$vars[0]][$vars[1]])) {
		eval("\$show_message = \"".str_replace('"', '\"', $scriptlang[$vars[0]][$vars[1]])."\";");
	} elseif(isset($language[$message])) {
		$pre = $inajax ? 'ajax_' : '';
		eval("\$show_message = \"".(isset($language[$pre.$message]) ? $language[$pre.$message] : $language[$message])."\";");
		unset($pre);
	}

	if(empty($infloat)) {
		$show_message .= $url_forward && empty($inajax) ? '<script>setTimeout("window.location.href =\''.$url_forward.'\';", '.$msgforward['refreshtime'].');</script>' : '';
	} elseif($handlekey) {
		$show_message = str_replace("'", "\'", $show_message);
		if($url_forward) {
			$show_message = "<script type=\"text/javascript\" reload=\"1\">\nif($('return_$handlekey')) $('return_$handlekey').className = 'onright';\nif(typeof submithandle_$handlekey =='function') {submithandle_$handlekey('$url_forward', '$show_message');} else {location.href='$url_forward'}\n</script>";
		} else {
			$show_message .= "<script type=\"text/javascript\" reload=\"1\">\nif(typeof messagehandle_$handlekey =='function') {messagehandle_$handlekey('$messagehandle', '$show_message');}\n</script>";
		}
	}

	if($advlist = array_merge($globaladvs ? $globaladvs['type'] : array(), $redirectadvs ? $redirectadvs['type'] : array())) {
		$advitems = ($globaladvs ? $globaladvs['items'] : array()) + ($redirectadvs ? $redirectadvs['items'] : array());
		foreach($advlist AS $type => $redirectadvs) {
			$advlist[$type] = $advitems[$redirectadvs[array_rand($redirectadvs)]];
		}
	}

	if($extra == 'NOPERM') {
		include template('nopermission');
	} else {
		include template('showmessage');
	}
	dexit();
}

function showmessagenoperm($type, $fid) {
	include DISCUZ_ROOT.'./forumdata/cache/cache_nopermission.php';
	include DISCUZ_ROOT.'./forumdata/cache/cache_usergroups.php';
	$v = $noperms[$fid][$type][$GLOBALS['groupid']][0];
	$gids = $noperms[$fid][$type][$GLOBALS['groupid']][1];
	$comma = $GLOBALS['permgroups'] = '';
	foreach($gids as $gid) {
		if($gid && $_DCACHE['usergroups'][$gid]) {
			$GLOBALS['permgroups'] .= $comma.$_DCACHE['usergroups'][$gid]['grouptitle'];
			$comma = ', ';
		}
	}
	showmessage($type.'_'.$v.'_nopermission', NULL, 'NOPERM');
}

function site() {
	return $_SERVER['HTTP_HOST'];
}

function strexists($haystack, $needle) {
	return !(strpos($haystack, $needle) === FALSE);
}

function seccodeconvert(&$seccode) {
	global $seccodedata, $charset;
	$seccode = substr($seccode, -6);
	if($seccodedata['type'] == 1) {
		include_once language('seccode');
		$len = strtoupper($charset) == 'GBK' ? 2 : 3;
		$code = array(substr($seccode, 0, 3), substr($seccode, 3, 3));
		$seccode = '';
		for($i = 0; $i < 2; $i++) {
			$seccode .= substr($lang['chn'], $code[$i] * $len, $len);
		}
		return;
	} elseif($seccodedata['type'] == 3) {
		$s = sprintf('%04s', base_convert($seccode, 10, 20));
		$seccodeunits = 'CEFHKLMNOPQRSTUVWXYZ';
	} else {
		$s = sprintf('%04s', base_convert($seccode, 10, 24));
		$seccodeunits = 'BCEFGHJKMPQRTVWXY2346789';
	}
	$seccode = '';
	for($i = 0; $i < 4; $i++) {
		$unit = ord($s{$i});
		$seccode .= ($unit >= 0x30 && $unit <= 0x39) ? $seccodeunits[$unit - 0x30] : $seccodeunits[$unit - 0x57];
	}
}

function submitcheck($var, $allowget = 0, $seccodecheck = 0, $secqaacheck = 0) {
	if(empty($GLOBALS[$var])) {
		return FALSE;
	} else {
		global $_SERVER, $seclevel, $seccode, $seccodedata, $seccodeverify, $secanswer, $_DCACHE, $_DCOOKIE, $timestamp, $discuz_uid;
		if($allowget || ($_SERVER['REQUEST_METHOD'] == 'POST' && $GLOBALS['formhash'] == formhash() && empty($_SERVER['HTTP_X_FLASH_VERSION']) && (empty($_SERVER['HTTP_REFERER']) ||
			preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {
        		if($seccodecheck) {
        			if(!$seclevel) {
        				$key = $seccodedata['type'] != 3 ? '' : $_DCACHE['settings']['authkey'].date('Ymd');
        				list($seccode, $expiration, $seccodeuid) = explode("\t", authcode($_DCOOKIE['secc'], 'DECODE', $key));
        				if($seccodeuid != $discuz_uid || $timestamp - $expiration > 600) {
        					showmessage('submit_seccode_invalid');
        				}
        				dsetcookie('secc', '');
        			} else {
        				$tmp = substr($seccode, 0, 1);
        			}
        			seccodeconvert($seccode);
        			if(strtoupper($seccodeverify) != $seccode) {
        				showmessage('submit_seccode_invalid');
        			}
				$seclevel && $seccode = random(6, 1) + $tmp * 1000000;
        		}
			if($secqaacheck) {
        			if(!$seclevel) {
        				list($seccode, $expiration, $seccodeuid) = explode("\t", authcode($_DCOOKIE['secq'], 'DECODE'));
        				if($seccodeuid != $discuz_uid || $timestamp - $expiration > 600) {
        					showmessage('submit_secqaa_invalid');
        				}
        				dsetcookie('secq', '');
        			}
        			require_once DISCUZ_ROOT.'./forumdata/cache/cache_secqaa.php';
        			if(md5($secanswer) != $_DCACHE['secqaa'][substr($seccode, 0, 1)]['answer']) {
        			        showmessage('submit_secqaa_invalid');
        			}
				$seclevel && $seccode = random(1, 1) * 1000000 + substr($seccode, -6);
        		}
			return TRUE;
		} else {
			showmessage('submit_invalid');
		}
	}
}

function template($file, $templateid = 0, $tpldir = '') {
	global $inajax, $hookscript;
	if(strexists($file, ':')) {
		list($templateid, $file) = explode(':', $file);
		$tpldir = './plugins/'.$templateid.'/templates';
	}
	$file .= $inajax && ($file == 'header' || $file == 'footer') ? '_ajax' : '';
	$tpldir = $tpldir ? $tpldir : TPLDIR;
	$templateid = $templateid ? $templateid : TEMPLATEID;
	$tplfile = DISCUZ_ROOT.'./'.$tpldir.'/'.$file.'.htm';
	$filebak = $file;
	$file == 'header' && CURSCRIPT && $file = 'header_'.CURSCRIPT;
	$objfile = DISCUZ_ROOT.'./forumdata/templates/'.STYLEID.'_'.$templateid.'_'.$file.'.tpl.php';
	if($templateid != 1 && !file_exists($tplfile)) {
		$tplfile = DISCUZ_ROOT.'./templates/default/'.$filebak.'.htm';
	}
	@checktplrefresh($tplfile, $tplfile, filemtime($objfile), $templateid, $tpldir);

	return $objfile;
}

function transsid($url, $tag = '', $wml = 0) {
	global $sid;
	$tag = stripslashes($tag);
	if(!$tag || (!preg_match("/^(http:\/\/|mailto:|#|javascript)/i", $url) && !strpos($url, 'sid='))) {
		if($pos = strpos($url, '#')) {
			$urlret = substr($url, $pos);
			$url = substr($url, 0, $pos);
		} else {
			$urlret = '';
		}
		$url .= (strpos($url, '?') ? ($wml ? '&amp;' : '&') : '?').'sid='.$sid.$urlret;
	}
	return $tag.$url;
}

function typeselect($curtypeid = 0) {
	if($threadtypes = $GLOBALS['forum']['threadtypes']) {
		$html = '<select name="typeid" id="typeid"><option value="0">&nbsp;</option>';
		foreach($threadtypes['types'] as $typeid => $name) {
			$html .= '<option value="'.$typeid.'" '.($curtypeid == $typeid ? 'selected' : '').'>'.strip_tags($name).'</option>';
		}
		$html .= '</select>';
		return $html;
	} else {
		return '';
	}
}

function sortselect($cursortid = 0, $modelid = 0, $onchange = '') {
	global $fid, $sid, $extra;
	if($threadsorts = $GLOBALS['forum']['threadsorts']) {
		$onchange = $onchange ? $onchange : "onchange=\"ajaxget('post.php?action=threadsorts&sortid='+this.options[this.selectedIndex].value+'&fid=$fid&sid=$sid', 'threadsorts', 'threadsortswait')\"";
		$selecthtml = '';
		foreach($threadsorts['types'] as $sortid => $name) {
			$sorthtml = '<option value="'.$sortid.'" '.($cursortid == $sortid ? 'selected="selected"' : '').' class="special">'.strip_tags($name).'</option>';
			$selecthtml .= $modelid ? ($threadsorts['modelid'][$sortid] == $modelid ? $sorthtml : '') : $sorthtml;
		}
		$hiddeninput = $cursortid ? '<input type="hidden" name="sortid" value="'.$cursortid.'" />' : '';
		$html = '<select name="sortid" '.$onchange.'><option value="0">&nbsp;</option>'.$selecthtml.'</select><span id="threadsortswait"></span>'.$hiddeninput;
		return $html;
	} else {
		return '';
	}
}

function updatecredits($uids, $creditsarray, $coef = 1, $extrasql = '') {
	if($uids && ((!empty($creditsarray) && is_array($creditsarray)) || $extrasql)) {
		global $db, $tablepre, $discuz_uid, $creditnotice, $cookiecredits;
		$self = $creditnotice && $uids == $discuz_uid;
		if($self && !isset($cookiecredits)) {
			$cookiecredits = !empty($_COOKIE['discuz_creditnotice']) ? explode('D', $_COOKIE['discuz_creditnotice']) : array_fill(0, 9, 0);
		}
		$creditsadd = $comma = '';
		foreach($creditsarray as $id => $addcredits) {
			$creditsadd .= $comma.'extcredits'.$id.'=extcredits'.$id.'+('.intval($addcredits).')*('.$coef.')';
			$comma = ', ';
			if($self) {
				$cookiecredits[$id] += intval($addcredits) * $coef;
			}
		}
		if($self) {
			dsetcookie('discuz_creditnotice', implode('D', $cookiecredits).'D'.$discuz_uid, 43200, 0);
		}

		if($creditsadd || $extrasql) {
			$db->query("UPDATE {$tablepre}members SET $creditsadd ".($creditsadd && $extrasql ? ', ' : '')." $extrasql WHERE uid IN ('$uids')", 'UNBUFFERED');
		}
	}
}

function updatesession() {
	if(!empty($GLOBALS['sessionupdated'])) {
		return TRUE;
	}

	global $db, $tablepre, $sessionexists, $sessionupdated, $sid, $onlineip, $discuz_uid, $discuz_user, $timestamp, $lastactivity, $seccode,
		$pvfrequence, $spageviews, $lastolupdate, $oltimespan, $onlinehold, $groupid, $styleid, $invisible, $discuz_action, $fid, $tid;

	$fid = intval($fid);
	$tid = intval($tid);

	if($oltimespan && $discuz_uid && $lastactivity && $timestamp - ($lastolupdate ? $lastolupdate : $lastactivity) > $oltimespan * 60) {
		$lastolupdate = $timestamp;
		$db->query("UPDATE {$tablepre}onlinetime SET total=total+'$oltimespan', thismonth=thismonth+'$oltimespan', lastupdate='$timestamp' WHERE uid='$discuz_uid' AND lastupdate<='".($timestamp - $oltimespan * 60)."'");
		if(!$db->affected_rows()) {
			$db->query("INSERT INTO {$tablepre}onlinetime (uid, thismonth, total, lastupdate)
				VALUES ('$discuz_uid', '$oltimespan', '$oltimespan', '$timestamp')", 'SILENT');
		}
	} else {
		$lastolupdate = intval($lastolupdate);
	}

	if($sessionexists == 1) {
		if($pvfrequence && $discuz_uid) {
			if($spageviews >= $pvfrequence) {
				$pageviewsadd = ', pageviews=\'0\'';
				$db->query("UPDATE {$tablepre}members SET pageviews=pageviews+'$spageviews' WHERE uid='$discuz_uid'", 'UNBUFFERED');
			} else {
				$pageviewsadd = ', pageviews=pageviews+1';
			}
		} else {
			$pageviewsadd = '';
		}
		$db->query("UPDATE {$tablepre}sessions SET uid='$discuz_uid', username='$discuz_user', groupid='$groupid', styleid='$styleid', invisible='$invisible', action='$discuz_action', lastactivity='$timestamp', lastolupdate='$lastolupdate', seccode='$seccode', fid='$fid', tid='$tid' $pageviewsadd WHERE sid='$sid'");
	} else {
		$ips = explode('.', $onlineip);

		$db->query("DELETE FROM {$tablepre}sessions WHERE sid='$sid' OR lastactivity<($timestamp-$onlinehold) OR ('$discuz_uid'<>'0' AND uid='$discuz_uid') OR (uid='0' AND ip1='$ips[0]' AND ip2='$ips[1]' AND ip3='$ips[2]' AND ip4='$ips[3]' AND lastactivity>$timestamp-60)");
		$db->query("INSERT INTO {$tablepre}sessions (sid, ip1, ip2, ip3, ip4, uid, username, groupid, styleid, invisible, action, lastactivity, lastolupdate, seccode, fid, tid)
			VALUES ('$sid', '$ips[0]', '$ips[1]', '$ips[2]', '$ips[3]', '$discuz_uid', '$discuz_user', '$groupid', '$styleid', '$invisible', '$discuz_action', '$timestamp', '$lastolupdate', '$seccode', '$fid', '$tid')", 'SILENT');
		if($discuz_uid && $timestamp - $lastactivity > 21600) {
			if($oltimespan && $timestamp - $lastactivity > 86400) {
				$query = $db->query("SELECT total FROM {$tablepre}onlinetime WHERE uid='$discuz_uid'");
				$oltimeadd = ', oltime='.round(intval($db->result($query, 0)) / 60);
			} else {
				$oltimeadd = '';
			}
			$db->query("UPDATE {$tablepre}members SET lastip='$onlineip', lastvisit=lastactivity, lastactivity='$timestamp' $oltimeadd WHERE uid='$discuz_uid'", 'UNBUFFERED');
		}
	}

	$sessionupdated = 1;
}
function updatemodworks($modaction, $posts = 1) {
	global $modworkstatus, $db, $tablepre, $discuz_uid, $timestamp, $_DCACHE;
	$today = gmdate('Y-m-d', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);
	if($modworkstatus && $modaction && $posts) {
		$db->query("UPDATE {$tablepre}modworks SET count=count+1, posts=posts+'$posts' WHERE uid='$discuz_uid' AND modaction='$modaction' AND dateline='$today'");
		if(!$db->affected_rows()) {
			$db->query("INSERT INTO {$tablepre}modworks (uid, modaction, dateline, count, posts) VALUES ('$discuz_uid', '$modaction', '$today', 1, '$posts')");
		}
	}
}

function updateannouncements($threadarray) {
	global $db, $tablepre, $discuz_user, $timestamp;
	if($threadarray && is_array($threadarray)) {
		$endtime = $timestamp + 3600;
		foreach($threadarray as $thread) {
			$db->query("INSERT INTO {$tablepre}announcements (author, subject, type, starttime, endtime, message) VALUES ('$discuz_user', '$thread[subject]', 1, '$timestamp', '$endtime', '$thread[url]')");
		}
	}
}

function writelog($file, $log) {
	global $timestamp, $_DCACHE;
	$yearmonth = gmdate('Ym', $timestamp + $_DCACHE['settings']['timeoffset'] * 3600);
	$logdir = DISCUZ_ROOT.'./forumdata/logs/';
	$logfile = $logdir.$yearmonth.'_'.$file.'.php';
	if(@filesize($logfile) > 2048000) {
		$dir = opendir($logdir);
		$length = strlen($file);
		$maxid = $id = 0;
		while($entry = readdir($dir)) {
			if(strexists($entry, $yearmonth.'_'.$file)) {
				$id = intval(substr($entry, $length + 8, -4));
				$id > $maxid && $maxid = $id;
			}
		}
		closedir($dir);

		$logfilebak = $logdir.$yearmonth.'_'.$file.'_'.($maxid + 1).'.php';
		@rename($logfile, $logfilebak);
	}
	if($fp = @fopen($logfile, 'a')) {
		@flock($fp, 2);
		$log = is_array($log) ? $log : array($log);
		foreach($log as $tmp) {
			fwrite($fp, "<?PHP exit;?>\t".str_replace(array('<?', '?>'), '', $tmp)."\n");
		}
		fclose($fp);
	}
}

function wipespecial($str) {
	return str_replace(array( "\n", "\r", '..'), array('', '', ''), $str);
}

function discuz_uc_avatar($uid, $size = '', $returnsrc = FALSE) {
	if($uid > 0) {
		$size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
		$uid = abs(intval($uid));
		if(empty($GLOBALS['avatarmethod'])) {
			return $returnsrc ? UC_API.'/avatar.php?uid='.$uid.'&size='.$size : '<img src="'.UC_API.'/avatar.php?uid='.$uid.'&size='.$size.'" />';
		} else {
			$uid = sprintf("%09d", $uid);
			$dir1 = substr($uid, 0, 3);
			$dir2 = substr($uid, 3, 2);
			$dir3 = substr($uid, 5, 2);
			$file = UC_API.'/data/avatar/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).'_avatar_'.$size.'.jpg';
			return $returnsrc ? $file : '<img src="'.$file.'" onerror="this.onerror=null;this.src=\''.UC_API.'/images/noavatar_'.$size.'.gif\'" />';
		}
	} else {
		$file = $GLOBALS['boardurl'].IMGDIR.'/syspm.gif';
		return $returnsrc ? $file : '<img src="'.$file.'" />';
	}
}


function loadmultiserver($type = '') {
	global $db, $dbcharset, $multiserver;
	$type = empty($type) && defined('CURSCRIPT') ? CURSCRIPT : $type;
	static $sdb = null;
	if($type && !empty($multiserver['enable'][$type])) {
		if(!is_a($sdb, 'dbstuff')) $sdb = new dbstuff();
		if($sdb->link > 0) {
			return $sdb;
		} elseif($sdb->link === null && (!empty($multiserver['slave']['dbhost']) || !empty($multiserver[$type]['dbhost']))) {
			$setting = !empty($multiserver[$type]['host']) ? $multiserver[$type] : $multiserver['slave'];
			$sdb->connect($setting['dbhost'], $setting['dbuser'], $setting['dbpw'], $setting['dbname'], $setting['pconnect'], false, $dbcharset);
			if($sdb->link) {
				return $sdb;
			} else {
				$sdb->link = -32767;
			}
		}
	}
	return $db;
}

function swapclass($classname) {
	global $swapc;
	$swapc = isset($swapc) && $swapc != $classname ? $classname : '';
	return $swapc;
}

function hookscript($script, $type = 'funcs', $param = array()) {
	global $hookscript, $pluginhooks, $pluginclasses, $pluginlangs, $adminid, $scriptlang;
	foreach($hookscript[$script]['module'] as $identifier => $include) {
		$hooksadminid[$identifier] = !$hookscript[$script]['adminid'][$identifier] || ($hookscript[$script]['adminid'][$identifier] && $adminid > 0 && $hookscript[$script]['adminid'][$identifier] >= $adminid);
		if($hooksadminid[$identifier]) {
			if(@in_array($identifier, $pluginlangs)) {
				@include_once DISCUZ_ROOT.'./forumdata/cache/cache_scriptlang.php';
			}
			@include_once DISCUZ_ROOT.'./plugins/'.$include.'.class.php';
		}
	}
	if(is_array($hookscript[$script][$type])) {
		foreach($hookscript[$script][$type] as $hookkey => $hookfuncs) {
			foreach($hookfuncs as $hookfunc) {
				if($hooksadminid[$hookfunc[0]]) {
					if(!isset($pluginclasses[$hookfunc[0]])) {
						eval('$pluginclasses[$hookfunc[0]] = new plugin_'.$hookfunc[0].';');
					}
					eval('$return = $pluginclasses[$hookfunc[0]]->'.$hookfunc[1].'($param);');
					if(is_array($return)) {
						foreach($return as $k => $v) {
							$pluginhooks[$hookkey][$k] .= $v;
						}
					} else {
						$pluginhooks[$hookkey] .= $return;
					}
				}
			}
		}
	}
}

function hookscriptoutput($tplfile) {
	global $hookscript, $hookscriptmessage;
	if(isset($hookscript['global']['module'])) {
		hookscript('global');
	}
	if(isset($hookscript[CURSCRIPT]['outputfuncs'])) {
		hookscript(CURSCRIPT, 'outputfuncs', array('template' => $tplfile, 'message' => $hookscriptmessage));
	}
}

function updateprompt($key, $uid, $number, $updatedb = 1) {
	global $db, $tablepre, $prompts;
	$prompts = $prompts ? $prompts : $GLOBALS['_DCACHE']['settings']['prompts'];
	if($updatedb) {
		if($number) {
			$db->query("REPLACE INTO {$tablepre}prompt (uid, typeid, number) VALUES ('$uid', '".$prompts[$key]['id']."', '$number')");
			$db->query("UPDATE {$tablepre}members SET prompt=prompt|1 WHERE uid='$uid'", 'UNBUFFERED');
		} else {
			$db->query("DELETE FROM {$tablepre}prompt WHERE uid='$uid' AND typeid='".$prompts[$key]['id']."'");
			if(!$db->result_first("SELECT count(*) FROM {$tablepre}prompt WHERE uid='$uid'")) {
				$db->query("UPDATE {$tablepre}members SET prompt=prompt^1 WHERE uid='$discuz_uid' AND prompt=prompt|1", 'UNBUFFERED');
			}
		}
	}
	return '$(\'prompt_'.$key.'\').innerHTML=\''.$prompts[$key]['name'].($number ? ' ('.$number.')\';$(\'prompt_'.$key.'\').parentNode.style.display=\'\';' : '\';');
}

function manyoulog($type, $uid, $action, $fuid = 0) {
	if(!$GLOBALS['my_status'] || !in_array($type, array('user', 'friend')) || !in_array($action, array('add', 'update', 'delete'))) {
		return;
	}
	$logfile = DISCUZ_ROOT.'./forumdata/logs/manyou_'.$type.'.log';
	if($fp = @fopen($logfile, 'a')) {
		@flock($fp, 2);
		$uids = !is_array($uid) ? array($uid) : $uid;
		$fuid = intval($fuid);
		foreach($uids as $uid) {
			fwrite($fp, "<?PHP exit;?>\t".$GLOBALS['timestamp']."\t".$uid."\t".$action.($fuid ? "\t".$fuid : '')."\n");
		}
		fclose($fp);
	}
}

function add_feed($arg, $data, $template = '') {
	global $tablepre, $db, $timestamp;

	$type = 'default';
	$fid = 0;
	$typeid = 0;
	$sortid = 0;
	$appid = '';
	$uid = 0;
	$username = '';

	extract($arg, EXTR_OVERWRITE);

	if(empty($data['title'])) {
		return false;
	}

	if($uid && in_array($type, array('thread_views', 'thread_replies', 'thread_rate', 'post_rate', 'user_credit', 'user_threads', 'user_posts', 'user_digest'))) {

		include language('notice');

		$title_template = $language[$type];
		$body_template = $language[$type];
		$noticemsg['title'] = transval($title_template, $data['title']);
		$noticemsg['body'] = transval($body_template, $data['body']);

		$message = $noticemsg['title'];
		if($noticemsg['body']) {
			$message .= '<br /><br />'.$noticemsg['body'];
		}
		if(in_array($type, array('thread_views', 'thread_replies', 'thread_rate', 'post_rate'))) {
			sendnotice($uid, $message, 'threads');
		} else {
			sendnotice($uid, $message, 'systempm');
		}

	}

	$db->query("INSERT INTO {$tablepre}feeds (type, fid, typeid, sortid, appid, uid, username, data, template, dateline)
				VALUES ('$type', '$fid', '$typeid', '$sortid', '$appid', '$uid', '$username', '".addslashes(serialize($data))."', '".addslashes(serialize($template))."', '$timestamp')");
	return $db->insert_id();
}

function get_feed($conf = array()) {
	global $tablepre, $db, $timestamp;

	$where = '1';
	$page_url = '';
	if(empty($conf['type'])) {
		$where .= " AND type != 'manyou'";
	} elseif($conf['type'] = trim($conf['type'])) {
		$where .= " AND type='$conf[type]'";
		$page_url .= "type={$conf[type]}&";
	}
	if($conf['fid'] = intval($conf['fid'])) {
		$where .= " AND fid='$conf[fid]'";
		$page_url .= "fid={$conf[fid]}&";
	}
	if($conf['typeid'] = intval($conf['typeid'])) {
		$where .= " AND typeid='$conf[typeid]'";
		$page_url .= "typeid={$conf[typeid]}&";
	}
	if($conf['sortid'] = intval($conf['sortid'])) {
		$where .= " AND sortid='$conf[sortid]'";
		$page_url .= "sortid={$conf[sortid]}&";
	}
	if(!empty($conf['uid']) && is_array($conf['uid'])) {
		$where .= " AND uid IN (".implodeids($conf['uid']).")";
	} elseif($conf['uid'] = intval($conf['uid'])) {
		$where .= " AND uid='$conf[uid]'";
		$page_url .= "uid={$conf[uid]}&";
	}
	if(!empty($conf['appid']) && is_array($conf['appid'])) {
		$where .= " AND appid IN (".implodeids($conf['appid']).")";
	}

	$conf['num'] = empty($conf['num']) ? 3 : intval($conf['num']);
	if($conf['multipage']) {
		$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
		$start_limit = ($page - 1) * $conf['num'];
	} else {
		$start_limit = 0;
	}

	$sql = "SELECT * FROM {$tablepre}feeds WHERE $where ORDER BY feed_id DESC LIMIT $start_limit, $conf[num]";

	$nocache = true;
	if($conf['cachelife'] = intval($conf['cachelife'])) {
		$cache_file = DISCUZ_ROOT.'./forumdata/feedcaches/'.md5($sql);
		$nocache = !file_exists($cache_file) || (filemtime($cache_file) < $timestamp - $conf['cachelife']);
	}

	if($nocache) {
		include language('dz_feeds');

		$feedlist = array();
		$feedlist['data'] = array();
		if($conf['multipage']) {
			$feeds = $db->result_first("SELECT COUNT(*) FROM {$tablepre}feeds WHERE $where");
			$page_url = $page_url ? (strpos($conf['page_url'], '?') ? $conf['page_url'].'&'.$page_url : $conf['page_url'].'?'.$page_url) : $conf['page_url'];
			$feedlist['multipage'] = multi($feeds, $conf['num'], $page, $page_url);
		}

		$query = $db->query($sql);
		while($row = $db->fetch_array($query)) {
			$row['data'] = unserialize($row['data']);
			$row['template'] = empty($row['template']) ? array() : unserialize($row['template']);
			$title_template = empty($row['template']['title']) ? $language['feed_'.$row['type'].'_title'] : $row['template']['title'];
			$body_template = empty($row['template']['body']) ? $language['feed_'.$row['type'].'_body'] : $row['template']['body'];

			$feed = array();
			$feed['title'] = transval($title_template, $row['data']['title']);
			$feed['body'] = transval($body_template, $row['data']['body']);
			$feed['dbdateline'] = $row['dateline'];
			$feed['dateline'] = dgmdate($GLOBALS['timeformat'], $row['dateline'] + $GLOBALS['timeoffset'] * 3600);
			$feed['image'] = array();
			if(is_array($row['data']['image'])) foreach($row['data']['image'] as $val) {
				$feed['image'][] = '<a href="'.$val['link'].'" target="_blank"><img src="'.$val['src'].'" /></a>';
			}
			$feed['icon'] = 'images/feeds/'.$row['type'].'.gif';
			$feed['appid'] = $row['appid'];
			$feed['uid'] = $row['uid'];
			$feed['username'] = $row['username'];
			$feedlist['data'][$row['feed_id']] = $feed;
		}
		if($conf['cachelife']) {
			if($fp = @fopen($cache_file, 'w')) {
				flock($fp, LOCK_EX);
				fwrite($fp, serialize($feedlist));
			}
			@fclose($fp);
			@chmod(CACHE_FILE, 0777);
		}
	} else {
		$feedlist = unserialize(file_get_contents($cache_file));
	}
	return $feedlist;
}

function transval($template, $data) {
	if(empty($template) || empty($data)) return;
	$trans = array();
	foreach($data as $key => $val) {
		$trans['{'.$key.'}'] = $val;
	}
	return strtr($template, $trans);
}

function stat_code($scriptpath = '', $imgcode = 0) {
	if(!defined('STAT_DISABLED') && STAT_ID > 0 && !IS_ROBOT) {
		$statserver = 'http://stat.discuz.com/';
		if(!defined('CACHE_FILE') || $GLOBALS['discuz_uid']) {
			$url = $statserver.'stat.php?q='.rawurlencode(base64_encode(stat_query('', '', '', $scriptpath)));
			echo !$imgcode ? '<script type="text/javascript" src="'.$url.'" reload="1"></script>' : '<img src="'.$url.'&amp;img=1" />';
			$statlogold = DISCUZ_ROOT.'./forumdata/stat.log';
			if(file_exists($statlogold)) {
				$statlogfile = DISCUZ_ROOT.'./forumdata/stat.log.'.random(3);
				@rename($statlogold, $statlogfile);
				if(($logs = @file($statlogfile)) !== FALSE && is_array($logs)) {
					foreach($logs as $log) {
						if($log) {
							$url = $statserver.'stat.php?q='.rawurlencode(base64_encode(trim($log)));
							echo !$imgcode ? '<script type="text/javascript" src="'.$url.'" reload="1"></script>' : '<img src="'.$url.'&amp;img=1" />';
						}
					}
				}
				@unlink($statlogfile);
			}
		} else {
			echo '<script type="text/javascript" src="'.$statserver.'stat.php" reload="1"></script>';
			echo '<script text="text/javascript" reload="1">
			if(window.addEventListener) {
				window.addEventListener("load", function () {document.body.stat("", "'.$GLOBALS['BASESCRIPT'].'")}, false);
			} else if(document.attachEvent) {
				window.attachEvent("onload", function () {document.body.stat("", "'.$GLOBALS['BASESCRIPT'].'")});
			}
			</script>';
		}
	}
}

function stat_query($message = '', $query = '', $referer = '', $scriptpath = '', $script = '') {
	preg_match("/(Netscape|Lynx|Opera|Konqueror|MSIE|Firefox|Safari|Chrome)[\/\s]([\.\d]+)/", $_SERVER['HTTP_USER_AGENT'], $a);
	empty($a[1]) && preg_match("/Mozilla[\/\s]([\.\d]+)/", $_SERVER['HTTP_USER_AGENT'], $a);
	$query = array(
		'siteid'	=> STAT_ID,
		'timestamp'	=> $GLOBALS['timestamp'],
		'sid'		=> $GLOBALS['sid'],
		'uid'		=> $GLOBALS['discuz_uid'],
		'ip'		=> $GLOBALS['onlineip'],
		'script'	=> !$script ? $scriptpath.basename($_SERVER['SCRIPT_NAME']) : $script,
		'query'		=> !$query ? $_SERVER['QUERY_STRING'] : $query,
		'referer'	=> !$referer ? $_SERVER['HTTP_REFERER'] : $referer,
		'message'	=> !$message ? '' : $message,
		'succeed'	=> !$message ? '' : (strpos($message, 'succeed') !== FALSE ? 1 : 0),
		'browser'	=> !empty($a[1]) ? $a[1].'/'.$a[2] : 'Other',
	);
	$s = '';
	foreach($query as $k => $v) {
		trim($v) !== '' && $s .= '&'.$k.'='.rawurlencode($v);
	}
	return substr($s, 1);
}

function write_statlog($message = '', $query = '', $referer = '', $scriptpath = '', $script = '') {
	if(defined('STAT_ID') && STAT_ID > 0) {
		$statlogfile = DISCUZ_ROOT.'./forumdata/stat.log';
		if($fp = @fopen($statlogfile, 'a')) {
			@flock($fp, 2);
			fwrite($fp, stat_query($message, $query, $referer, $scriptpath, $script)."\n");
			fclose($fp);
		}
	}
}

function funcstat($funcinfo = '', $scriptpath = '', $imgcode = 0) {
	global $_DCACHE, $funcstatinfo;
	$funcsiteid = $_DCACHE['settings']['funcsiteid'];
	$funckey = $_DCACHE['settings']['funckey'];
	$funcinfo = empty($funcinfo) ? $funcstatinfo : $funcinfo;
	if(is_array($funcinfo)) {
		$funcinfo = array_unique($funcinfo);
		foreach($funcinfo as $finfo) {
			funcstat($finfo);
		}
	} else {
		list($funcmark, $funcversion) = explode(',', $funcinfo);
		if($funcsiteid && $funckey && $funcmark && $funcversion && !IS_ROBOT) {
			$statserver = 'http://stat.discuz.com/func/';
			if(!defined('CACHE_FILE') || $GLOBALS['discuz_uid']) {
				$url = $statserver.'funcstat.php?q='.rawurlencode(base64_encode(funcstat_query($funcinfo,'', '', '', $scriptpath)));
				echo !$imgcode ? '<script type="text/javascript" src="'.$url.'" reload="1"></script>' : '<img src="'.$url.'&amp;img=1" />';
				$statlogold = DISCUZ_ROOT.'./forumdata/funcstat.log';
				if(file_exists($statlogold)) {
					$statlogfile = DISCUZ_ROOT.'./forumdata/funcstat.log.'.random(3);
					@rename($statlogold, $statlogfile);
					if(($logs = @file($statlogfile)) !== FALSE && is_array($logs)) {
						foreach($logs as $log) {
							if($log) {
								$url = $statserver.'funcstat.php?q='.rawurlencode(base64_encode(trim($log)));
								echo !$imgcode ? '<script type="text/javascript" src="'.$url.'" reload="1"></script>' : '<img src="'.$url.'&amp;img=1" />';
							}
						}
					}
					@unlink($statlogfile);
				}
			}
		}
	}


}

function funcstat_query($funcinfo, $message = '', $query = '', $referer = '', $scriptpath = '', $script = '') {
	preg_match("/(Netscape|Lynx|Opera|Konqueror|MSIE|Firefox|Safari|Chrome)[\/\s]([\.\d]+)/", $_SERVER['HTTP_USER_AGENT'], $a);
	empty($a[1]) && preg_match("/Mozilla[\/\s]([\.\d]+)/", $_SERVER['HTTP_USER_AGENT'], $a);
	if(empty($funcinfo)) {
		return;
	}
	list($funcmark, $funcversion) = explode(',', $funcinfo);
	$query = array(
		'funcmark'	=> $funcmark,
		'funcversion'	=> $funcversion,
		'siteid'	=> $GLOBALS['_DCACHE']['settings']['funcsiteid'],
		'timestamp'	=> $GLOBALS['timestamp'],
		'sid'		=> $GLOBALS['sid'],
		'uid'		=> $GLOBALS['discuz_uid'],
		'ip'		=> $GLOBALS['onlineip'],
		'script'	=> !$script ? $scriptpath.basename($_SERVER['SCRIPT_NAME']) : $script,
		'query'		=> !$query ? $_SERVER['QUERY_STRING'] : $query,
		'referer'	=> !$referer ? $_SERVER['HTTP_REFERER'] : $referer,
		'message'	=> !$message ? '' : $message,
		'succeed'	=> !$message ? '' : (strpos($message, 'succeed') !== FALSE ? 1 : 0),
		'browser'	=> !empty($a[1]) ? $a[1].'/'.$a[2] : 'Other',
	);
	if($GLOBALS['adminid'] == 1) {
		$query['adminid'] = 1;
	}
	if($GLOBALS['discuz_uid']) {
		$mq = $GLOBALS['db']->query("SELECT gender,regdate,bday,groupid,lastvisit,lastactivity,lastpost,posts FROM {$GLOBALS[tablepre]}members WHERE uid='$GLOBALS[discuz_uid]'");
		$m = $GLOBALS['db']->fetch_array($mq);
		foreach($m as $k => $v) {
			$query[$k] = $v;
		}
	}
	$s = '';
	foreach($query as $k => $v) {
		trim($v) !== '' && $s .= '&'.$k.'='.rawurlencode($v);
	}
	return substr($s, 1);
}

function setstatus($position, $value, $baseon = null) {
	$t = pow(2, $position - 1);
	if($value) {
		$t = $baseon | $t;
	} elseif ($baseon !== null) {
		$t = $baseon & ~$t;
	} else {
		$t = ~$t;
	}
	return $t & 0xFFFF;
}

function getstatus($status, $position) {
	$t = $status & pow(2, $position - 1) ? 1 : 0;
	return $t;
}

function buildbitsql($fieldname, $position, $value) {
	$t = " `$fieldname`=`$fieldname`";
	if($value) {
		$t .= ' | '.setstatus($position, 1);
	} else {
		$t .= ' & '.setstatus($position, 0);
	}
	return $t.' ';
}
?>