<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: search.inc.php 19537 2009-09-04 04:26:24Z wangjinbo $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

cpheader();

$actionarray = array(
	'settings' => array(
		'basic',
		'access' => array('register', 'access'),
		'styles' => array('global', 'index', 'forumdisplay', 'viewthread', 'member', 'refresh'),
		'seo',
		'cachethread',
		'serveropti',
		'editor',
		'functions' => array('editor', 'stat', 'mod', 'tags', 'other'),
		'permissions',
		'credits',
		'mail' => array('settings', 'check'),
		'sec' => array('seclevel', 'seccode', 'secqaa'),
		'datetime',
		'attach' => array('basic', 'image', 'remote', 'antileech'),
		'wap',
		'ec',
		'uc',
		'uchome',
		'msn'
	),
	'forums' => array(
		'admin',
		'edit' => array('basic', 'extend', 'posts', 'credits', 'threadtypes', 'threadsorts', 'perm'),
		'moderators',
		'delete',
		'merge',
		'copy'
	),
	'threadtypes' => array(),
	'members' => array(
		'clean',
		'newsletter',
		'reward',
		'confermedal',
		'add',
		'group',
		'access',
		'credit',
		'medal',
		'edit',
		'ipban',
		'ban'
	),
	'profilefields' => array(
		'admin',
		'edit'
	),
	'admingroups' => array(
		'admin',
		'edit'
	),
	'usergroups' => array(
		'admin',
		'edit' => array('basic', 'system', 'special', 'post', 'attach', 'magic', 'invite', 'credit'),
		'viewsgroup'
	),
	'ranks' => array(),
	'styles' => array(
		'admin',
		'edit',
		'config',
		'import'
	),
	'templates' => array(
		'admin',
		'add',
		'edit'
	),
	'moderate' => array(
		'members',
		'threads',
		'replies'
	),
	'threads' => array(),
	'prune' => array(),
	'recyclebin' => array('clean'),
	'announce' => array(
		'admin',
		'add',
		'edit'
	),
	'smilies' => array(),
	'misc' => array(
		'link',
		'onlinelist',
		'censor',
		'bbcode',
		'tag',
		'icon',
		'attachtype',
		'cron',
		'custommenu',
		'customnav',
		'focus'
	),
	'faq' => array(),
	'adv' => array(
		'admin',
		'add',
		'edit'
	),
	'db' => array(
		'runquery',
		'optimize',
		'export',
		'import'
	),
	'extended' => array(
		'tag'
	),
	'tasks' => array(
		'add',
		'edit',
		'type'
	),
	'tools' => array(
		'updatecache',
		'fileperms'
	),
	'attach' => array(),
	'counter' => array(),
	'jswizard' => array('admin', 'config', 'import'),
	'creditwizard' => array(),
	'google' => array(),
	'qihoo' => array(
		'config',
		'topics'
	),
	'ec' => array(
		'alipay',
		'credit',
		'orders'
	),
	'tradelog' => array(),
	'medals' => array(),
	'magics' => array('config', 'admin', 'market'),
	'plugins' => array(
		'config',
		'edit',
		'hooks',
		'vars'
	),
	'logs' => array(
		'illegal',
		'rate',
		'credit',
		'mod',
		'ban',
		'cp',
		'error',
		'invite',
		'magic',
		'medal'

	)
);

$keywords = trim($keywords);
$results = array();
$kws = explode(' ', $keywords);
$kws = array_map('trim', $kws);
$keywords = implode(' ', $kws);

if($searchsubmit && $keywords) {
	foreach($lang as $key => $value) {
		$matched = TRUE;
		foreach($kws as $kw) {
			if(strpos(strtolower($value), strtolower($kw)) === FALSE) {
				$matched = FALSE;
				break;
			}
		}
		if($matched) {
			$tmp = explode('_', $key);
			if(isset($actionarray[$tmp[0]])) {
				$url = $BASESCRIPT.'?action='.$tmp[0];
				$vname = $tmp[0];
				$subject = '<a href="'.$url.'&highlight='.urlencode($keywords).'" target="_blank"><u>'.lang($vname).'</u></a>';
				if(is_array($actionarray[$tmp[0]][$tmp[1]])) {
					$url .= '&operation='.$tmp[1];
					$vname .= '_'.$tmp[1];
					$subject .= '&nbsp;&raquo;&nbsp;<a href="'.$url.'&highlight='.urlencode($keywords).'" target="_blank"><u>'.lang($vname).'</u></a>';
					if(in_array($tmp[2], $actionarray[$tmp[0]][$tmp[1]])) {
						$url .= '&anchor='.$tmp[2];
						$vname .= '_'.$tmp[2];
						$subject .= '&nbsp;&raquo;&nbsp;<a href="'.$url.'&highlight='.urlencode($keywords).'" target="_blank"><u>'.lang($vname).'</u></a>';
					}
				} else {
					if(in_array($tmp[1], $actionarray[$tmp[0]])) {
						$url .=  '&operation='.$tmp[1];
						$vname .= '_'.$tmp[1];
						$subject .= '&nbsp;&raquo;&nbsp;<a href="'.$url.'&highlight='.urlencode($keywords).'" target="_blank"><u>'.lang($vname).'</u></a>';
					}
				}
				if(isset($results[$url])) {
					$results[$url]['message'] .= '<br />'.$value;
				} else {
					$results[$url] = array('subject' => $subject, 'message' => $value);
				}
			}
		}
	}

	if($results) {
		showsubmenu('search_result');
		foreach($results as $result) {
			echo '<div class="news"><h3>'.$result[subject].'</h3><p class="lineheight">'.strip_tags($result[message], '<br>').'</p></div>';
		}
		echo <<<EOT
<script type="text/JavaScript">
function parsetag(tag) {
	var str = $('cpcontainer').innerHTML.replace(/(^|>)([^<]+)(?=<|$)/ig, function($1, $2, $3) {
		if(tag && $3.indexOf(tag) != -1) {
			$3 = $3.replace(tag, '<h_>');
		}
		return $2 + $3;
    	});
	$('cpcontainer').innerHTML = str.replace(/<h_>/ig, function($1, $2) {
		return '<font color="#c60a00">' + tag + '</font>';
    	});
}
EOT;
		foreach($kws as $kw) {
			echo 'parsetag(\''.$kw.'\');';
		}
		echo '</script>';

	} else {
		cpmsg('search_result_noexists');
	}

} else {
	cpmsg('search_keyword_noexists', '', 'error');
}

?>