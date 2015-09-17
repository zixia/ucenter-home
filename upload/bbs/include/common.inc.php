<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: common.inc.php 21331 2010-01-06 06:42:22Z cnteacher $
*/
error_reporting(0);
set_magic_quotes_runtime(0);
$mtime = explode(' ', microtime());
$discuz_starttime = $mtime[1] + $mtime[0];

define('SYS_DEBUG', FALSE);
define('IN_DISCUZ', TRUE);
define('DISCUZ_ROOT', substr(dirname(__FILE__), 0, -7));
define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
//!defined('CURSCRIPT') && define('CURSCRIPT', '');

if(PHP_VERSION < '4.1.0') {
	$_GET = &$HTTP_GET_VARS;
	$_POST = &$HTTP_POST_VARS;
	$_COOKIE = &$HTTP_COOKIE_VARS;
	$_SERVER = &$HTTP_SERVER_VARS;
	$_ENV = &$HTTP_ENV_VARS;
	$_FILES = &$HTTP_POST_FILES;
}

if (isset($_REQUEST['GLOBALS']) OR isset($_FILES['GLOBALS'])) {
	exit('Request tainting attempted.');
}

require_once DISCUZ_ROOT.'./include/global.func.php';

getrobot();
if(defined('NOROBOT') && IS_ROBOT) {
	exit(header("HTTP/1.1 403 Forbidden"));
}

foreach(array('_COOKIE', '_POST', '_GET') as $_request) {
	foreach($$_request as $_key => $_value) {
		$_key{0} != '_' && $$_key = daddslashes($_value);
	}
}

if (!MAGIC_QUOTES_GPC && $_FILES) {
	$_FILES = daddslashes($_FILES);
}

$charset = $dbs = $dbcharset = $forumfounders = $metakeywords = $extrahead = $seodescription = $mnid = '';
$plugins = $admincp = $scriptlang = $forum = $thread = $language = $jsmenu = $actioncode = $modactioncode = $pluginclasses = $hooks = $lang = array();
$_DCOOKIE = $_DSESSION = $_DCACHE = $_DPLUGIN = $advlist = array();

require_once DISCUZ_ROOT.'./config.inc.php';

if($urlxssdefend && !empty($_SERVER['REQUEST_URI'])) {
	$temp = urldecode($_SERVER['REQUEST_URI']);
	if(strpos($temp, '<') !== false || strpos($temp, '"') !== false)
		exit('Request Bad url');
}

$prelength = strlen($cookiepre);
foreach($_COOKIE as $key => $val) {
	if(substr($key, 0, $prelength) == $cookiepre) {
		$_DCOOKIE[(substr($key, $prelength))] = MAGIC_QUOTES_GPC ? $val : daddslashes($val);
	}
}
unset($prelength, $_request, $_key, $_value);

$inajax = !empty($inajax);
$handlekey = !empty($handlekey) ? htmlspecialchars($handlekey) : '';
$timestamp = time();

if($attackevasive && (!define('CURSCRIPT') || CURSCRIPT != 'seccode')) {
	require_once DISCUZ_ROOT.'./include/security.inc.php';
}

require_once DISCUZ_ROOT.'./include/db_'.$database.'.class.php';


$PHP_SELF = dhtmlspecialchars($_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
$BASESCRIPT = basename($PHP_SELF);
list($BASEFILENAME) = explode('.', $BASESCRIPT);
$boardurl = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].preg_replace("/\/+(api|archiver|wap)?\/*$/i", '', substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'))).'/');

if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
	$onlineip = getenv('HTTP_CLIENT_IP');
} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
	$onlineip = getenv('HTTP_X_FORWARDED_FOR');
} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
	$onlineip = getenv('REMOTE_ADDR');
} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
	$onlineip = $_SERVER['REMOTE_ADDR'];
}

preg_match("/[\d\.]{7,15}/", $onlineip, $onlineipmatches);
$onlineip = $onlineipmatches[0] ? $onlineipmatches[0] : 'unknown';
unset($onlineipmatches);

$cachelost = (@include DISCUZ_ROOT.'./forumdata/cache/cache_settings.php') ? '' : 'settings';
@extract($_DCACHE['settings']);

if(defined('BINDDOMAIN') && BINDDOMAIN && !$cachelost && $binddomains && $forumdomains) {
	$loadforum = isset($binddomains[$_SERVER['HTTP_HOST']]) ? max(0, intval($binddomains[$_SERVER['HTTP_HOST']])) : 0;
	if($loadforum) {
		if(BINDDOMAIN == 'forumdisplay' && $loadforum == $fid) {
			header("HTTP/1.1 301 Moved Permanently");
			$query_string = preg_replace('/\??fid='.$fid.'&?/is', '', $_SERVER['QUERY_STRING']);
			dheader("Location: http://$_SERVER[HTTP_HOST]/{$indexname}".($query_string ? "?{$query_string}" : ''));
		}
		if(BINDDOMAIN == 'index') {
			$fid = $_GET['fid'] = $_REQUEST['fid'] = $loadforum;
			define('CURSCRIPT', 'forumdisplay');
		}
	} else {
		if(BINDDOMAIN == 'forumdisplay' && isset($forumdomains[$fid])) {
			$host = $forumdomains[$fid];
			header("HTTP/1.1 301 Moved Permanently");
			dheader("Location: http://{$host}/{$indexname}");
		}
		define('CURSCRIPT', BINDDOMAIN);
	}
}
if(!defined('CURSCRIPT')) {
	define('CURSCRIPT', defined('BINDDOMAIN') ? BINDDOMAIN : '');
}


if(!defined('STAT_ID') && isset($statdisable) && empty($statdisable)) {
	define('STAT_ID', $_DCACHE['settings']['statid']);
	define('STAT_KEY', $_DCACHE['settings']['statkey']);
}
if($gzipcompress && function_exists('ob_gzhandler') && !in_array(CURSCRIPT, array('attachment', 'wap')) && !$inajax) {
	ob_start('ob_gzhandler');
} else {
	$gzipcompress = 0;
	ob_start();
}

if(!empty($loadctrl) && substr(PHP_OS, 0, 3) != 'WIN') {
	if($fp = @fopen('/proc/loadavg', 'r')) {
		list($loadaverage) = explode(' ', fread($fp, 6));
		fclose($fp);
		if($loadaverage > $loadctrl) {
			header("HTTP/1.0 503 Service Unavailable");
			include DISCUZ_ROOT.'./include/serverbusy.htm';
			exit();
		}
	}
}

if(in_array(CURSCRIPT, array('index', 'forumdisplay', 'viewthread', 'post', 'topicadmin', 'register', 'archiver'))) {
	$cachelost .= (@include DISCUZ_ROOT.'./forumdata/cache/cache_'.CURSCRIPT.'.php') ? '' : ' '.CURSCRIPT;
}

$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
$dbuser = $dbpw = $pconnect = $sdb = NULL;

$sid = daddslashes(($transsidstatus || CURSCRIPT == 'wap') && (isset($_GET['sid']) || isset($_POST['sid'])) ?
	(isset($_GET['sid']) ? $_GET['sid'] : $_POST['sid']) :
	(isset($_DCOOKIE['sid']) ? $_DCOOKIE['sid'] : ''));

CURSCRIPT == 'attachment' && isset($_GET['sid']) && $sid = addslashes(authcode($_GET['sid'], 'DECODE', $_DCACHE['settings']['authkey']));

$discuz_auth_key = md5($_DCACHE['settings']['authkey'].$_SERVER['HTTP_USER_AGENT']);
list($discuz_pw, $discuz_secques, $discuz_uid) = empty($_DCOOKIE['auth']) ? array('', '', 0) : daddslashes(explode("\t", authcode($_DCOOKIE['auth'], 'DECODE')), 1);

$prompt = $sessionexists = $seccode = 0;
$membertablefields = 'm.uid AS discuz_uid, m.username AS discuz_user, m.password AS discuz_pw, m.secques AS discuz_secques,
	m.adminid, m.groupid, m.groupexpiry, m.extgroupids, m.email, m.timeoffset, m.tpp, m.ppp, m.posts, m.threads, m.digestposts,
	m.oltime, m.pageviews, m.credits, m.extcredits1, m.extcredits2, m.extcredits3, m.extcredits4, m.extcredits5,
	m.extcredits6, m.extcredits7, m.extcredits8, m.timeformat, m.dateformat, m.pmsound, m.sigstatus, m.invisible,
	m.lastvisit, m.lastactivity, m.lastpost, m.prompt, m.accessmasks, m.editormode, m.customshow, m.customaddfeed, m.newbietaskid';
if($sid) {
	if($discuz_uid) {
		$query = $db->query("SELECT s.sid, s.styleid, s.groupid='6' AS ipbanned, s.pageviews AS spageviews, s.lastolupdate, s.seccode, $membertablefields
			FROM {$tablepre}sessions s, {$tablepre}members m
			WHERE m.uid=s.uid AND s.sid='$sid' AND CONCAT_WS('.',s.ip1,s.ip2,s.ip3,s.ip4)='$onlineip' AND m.uid='$discuz_uid'
			AND m.password='$discuz_pw' AND m.secques='$discuz_secques'");
	} else {
		$query = $db->query("SELECT sid, uid AS sessionuid, groupid, groupid='6' AS ipbanned, pageviews AS spageviews, styleid, lastolupdate, seccode
			FROM {$tablepre}sessions WHERE sid='$sid' AND CONCAT_WS('.',ip1,ip2,ip3,ip4)='$onlineip'");
	}
	if($_DSESSION = $db->fetch_array($query)) {
		$sessionexists = 1;
		if(!empty($_DSESSION['sessionuid'])) {
			$_DSESSION = array_merge($_DSESSION, $db->fetch_first("SELECT $membertablefields
				FROM {$tablepre}members m WHERE uid='$_DSESSION[sessionuid]'"));
		}
	} else {
		if($_DSESSION = $db->fetch_first("SELECT sid, groupid, groupid='6' AS ipbanned, pageviews AS spageviews, styleid, lastolupdate, seccode
			FROM {$tablepre}sessions WHERE sid='$sid' AND CONCAT_WS('.',ip1,ip2,ip3,ip4)='$onlineip'")) {
			clearcookies();
			$sessionexists = 1;
		}
	}
}

if(!$sessionexists) {
	if($discuz_uid) {
		if(!($_DSESSION = $db->fetch_first("SELECT $membertablefields, m.styleid
			FROM {$tablepre}members m WHERE m.uid='$discuz_uid' AND m.password='$discuz_pw' AND m.secques='$discuz_secques'"))) {
			clearcookies();
		}
	}

	if(ipbanned($onlineip)) $_DSESSION['ipbanned'] = 1;

	$_DSESSION['sid'] = random(6);
	$_DSESSION['seccode'] = random(6, 1);
}

$_DSESSION['dateformat'] = empty($_DSESSION['dateformat']) || empty($_DCACHE['settings']['userdateformat'][$_DSESSION['dateformat'] -1])? $_DCACHE['settings']['dateformat'] : $_DCACHE['settings']['userdateformat'][$_DSESSION['dateformat'] -1];
$_DSESSION['timeformat'] = empty($_DSESSION['timeformat']) ? $_DCACHE['settings']['timeformat'] : ($_DSESSION['timeformat'] == 1 ? 'h:i A' : 'H:i');
$_DSESSION['timeoffset'] = isset($_DSESSION['timeoffset']) && $_DSESSION['timeoffset'] != 9999 ? $_DSESSION['timeoffset'] : $_DCACHE['settings']['timeoffset'];

$membertablefields = '';
@extract($_DSESSION);

$disableprompt = !empty($_DCOOKIE['disableprompt']) ? explode('|', $_DCOOKIE['disableprompt']) : array();

if($prompt) {
	if($taskon && ($prompt & 8)) {
		$prompts['newbietask'] = 1;
		$disallowfloat = str_replace('task', '', $disallowfloat);
		$disallowfloat .= '|newthread|reply';
		$editormode = 0;
	}
	$prompt = 0;
	$query = $db->query("SELECT typeid, number FROM {$tablepre}prompt WHERE uid='$discuz_uid'");
	while($promptrow = $db->fetch_array($query)) {
		if($disableprompt && in_array($promptkeys[$promptrow['typeid']], $disableprompt)) {
			continue;
		}
		$prompt = $promptrow['number'] ? 1 : $prompt;
		$prompts[$promptkeys[$promptrow['typeid']]]['new'] = $promptrow['number'];
	}
}

if($announcepm && !in_array('announcepm', $disableprompt)) {
	$prompts['announcepm']['new'] = $announcepm;
}

$lastvisit = empty($lastvisit) ? $timestamp - 86400 : $lastvisit;
$timenow = array('time' => gmdate("$dateformat $timeformat", $timestamp + 3600 * $timeoffset),
	'offset' => ($timeoffset >= 0 ? ($timeoffset == 0 ? '' : '+'.$timeoffset) : $timeoffset));

if(PHP_VERSION > '5.1') {
	@date_default_timezone_set('Etc/GMT'.($timeoffset > 0 ? '-' : '+').(abs($timeoffset)));
}

$accessadd1 = $accessadd2 = $modadd1 = $modadd2 = $metadescription = $hookscriptmessage = '';
if(empty($discuz_uid) || empty($discuz_user)) {
	$discuz_user = $extgroupids = '';
	$discuz_uid = $adminid = $posts = $digestposts = $pageviews = $oltime = $invisible
		= $credits = $extcredits1 = $extcredits2 = $extcredits3 = $extcredits4
		= $extcredits5 = $extcredits6 = $extcredits7 = $extcredits8 = 0;
	$groupid = empty($groupid) || $groupid != 6 ? 7 : 6;

} else {
	$discuz_userss = $discuz_user;
	$discuz_user = addslashes($discuz_user);

	if($accessmasks) {
		$accessadd1 = ', a.allowview, a.allowpost, a.allowreply, a.allowgetattach, a.allowpostattach';
		$accessadd2 = "LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid";
	}

	if($adminid == 3) {
		$modadd1 = ', m.uid AS ismoderator';
		$modadd2 = "LEFT JOIN {$tablepre}moderators m ON m.uid='$discuz_uid' AND m.fid=f.fid";
	}
}

if($errorreport == 2 || ($errorreport == 1 && $adminid > 0)) {
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

define('FORMHASH', formhash());

$statstatus && !$inajax && require_once DISCUZ_ROOT.'./include/counter.inc.php';

$extra = isset($extra) && @preg_match("/^[&=;a-z0-9]+$/i", $extra) ? $extra : '';

$rsshead = $navtitle = $navigation = '';

$_DSESSION['groupid'] = $groupid = empty($ipbanned) ? (empty($groupid) ? 7 : intval($groupid)) : 6;
if(!@include DISCUZ_ROOT.'./forumdata/cache/usergroup_'.$groupid.'.php') {
	$grouptype = $db->result_first("SELECT type FROM {$tablepre}usergroups WHERE groupid='$groupid'");
	if(!empty($grouptype)) {
		$cachelost .= ' usergroup_'.$groupid;
	} else {
		$grouptype = 'member';
	}
}

/*
$link_login = 'logging.php?action=login';
$link_logout = 'logging.php?action=logout&amp;formhash='.FORMHASH;
$link_register = $regname;
*/

if($discuz_uid && $_DSESSION) {
	if(!empty($groupexpiry) && $groupexpiry < $timestamp && !in_array(CURSCRIPT, array('wap', 'member'))) {
		dheader("Location: {$boardurl}member.php?action=groupexpiry");
	} elseif($grouptype && $groupid != getgroupid($discuz_uid, array
		(
		'type' => $grouptype,
		'creditshigher' => $groupcreditshigher,
		'creditslower' => $groupcreditslower
		), $_DSESSION)) {
		@extract($_DSESSION);
		$cachelost .= (@include DISCUZ_ROOT.'./forumdata/cache/usergroup_'.intval($groupid).'.php') ? '' : ' usergroup_'.$groupid;
	}
}

if(!in_array($adminid, array(1, 2, 3))) {
	$alloweditpost = $alloweditpoll = $allowstickthread = $allowmodpost = $allowdelpost = $allowmassprune
		= $allowrefund = $allowcensorword = $allowviewip = $allowbanip = $allowedituser = $allowmoduser
		= $allowbanuser = $allowpostannounce = $allowviewlog = $disablepostctrl = 0;
} elseif(isset($radminid) && $adminid != $radminid && $adminid != $groupid) {
	$cachelost .= (@include DISCUZ_ROOT.'./forumdata/cache/admingroup_'.intval($adminid).'.php') ? '' : ' admingroup_'.$groupid;
}


$page = isset($page) ? max(1, intval($page)) : 1;
$tid = isset($tid) && is_numeric($tid) ? $tid : 0;
$fid = isset($fid) && is_numeric($fid) ? $fid : 0;
$typeid = isset($typeid) ? intval($typeid) : 0;
$tpp = intval(empty($_DSESSION['tpp']) ? $topicperpage : $_DSESSION['tpp']);
$ppp = intval(empty($_DSESSION['ppp']) ? $postperpage : $_DSESSION['ppp']);

$modthreadkey = isset($modthreadkey) && $modthreadkey == modthreadkey($tid) ? $modthreadkey : '';
$auditstatuson = $modthreadkey ? true : false;

if(!empty($tid) || !empty($fid)) {
	if(empty($tid)) {
		$forum = $db->fetch_first("SELECT f.fid, f.*, ff.* $accessadd1 $modadd1, f.fid AS fid
			FROM {$tablepre}forums f
			LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid $accessadd2 $modadd2
			WHERE f.fid='$fid'");
	} else {
		$forum = $db->fetch_first("SELECT t.tid, t.closed,".(defined('SQL_ADD_THREAD') ? SQL_ADD_THREAD : '')." f.*, ff.* $accessadd1 $modadd1, f.fid AS fid
			FROM {$tablepre}threads t
			INNER JOIN {$tablepre}forums f ON f.fid=t.fid
			LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid $accessadd2 $modadd2
			WHERE t.tid='$tid'".($auditstatuson ? '' : " AND t.displayorder>='0'")." LIMIT 1");
		$tid = $forum['tid'];
	}

	if($forum) {
		$fid = $forum['fid'];
		$forum['ismoderator'] = !empty($forum['ismoderator']) || $adminid == 1 || $adminid == 2 ? 1 : 0;
		foreach(array('postcredits', 'replycredits', 'threadtypes', 'threadsorts', 'digestcredits', 'postattachcredits', 'getattachcredits', 'modrecommend') as $key) {
			$forum[$key] = !empty($forum[$key]) ? unserialize($forum[$key]) : array();
		}
	} else {
		$fid = 0;
	}
}

$styleid = intval(!empty($_GET['styleid']) ? $_GET['styleid'] :
		(!empty($_POST['styleid']) ? $_POST['styleid'] :
		(!empty($_DSESSION['styleid']) ? $_DSESSION['styleid'] :
		$_DCACHE['settings']['styleid'])));

$styleid = intval(isset($styles[$styleid]) ? $styleid : $_DCACHE['settings']['styleid']);

if(@!include DISCUZ_ROOT.'./forumdata/cache/style_'.intval(!empty($forum['styleid']) ? $forum['styleid'] : $styleid).'.php') {
	$cachelost .= (@include DISCUZ_ROOT.'./forumdata/cache/style_'.($styleid = $_DCACHE['settings']['styleid']).'.php') ? '' : ' style_'.$styleid;
}

if($cachelost) {
	require_once DISCUZ_ROOT.'./include/cache.func.php';
	updatecache();
	exit('Cache List: '.$cachelost.'<br />Caches successfully created, please refresh.');
}

if(CURSCRIPT != 'wap') {
	if($nocacheheaders) {
		@dheader("Expires: 0");
		@dheader("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
		@dheader("Pragma: no-cache");
	}
	if($headercharset) {
		@dheader('Content-Type: text/html; charset='.$charset);
	}
	if(empty($_DCOOKIE['sid']) || $sid != $_DCOOKIE['sid']) {
		dsetcookie('sid', $sid, 604800, 1, true);
	}
}

$_DCOOKIE['loginuser'] = !empty($_DCOOKIE['loginuser']) ? substr(htmlspecialchars($_DCOOKIE['loginuser']), 0, 15) : '';

if($cronnextrun && $cronnextrun <= $timestamp) {
	require_once DISCUZ_ROOT.'./include/cron.func.php';
	runcron();
}

if((!empty($_DCACHE['advs']) || $globaladvs) && !defined('IN_ADMINCP')) {
	require_once DISCUZ_ROOT.'./include/advertisements.inc.php';
}

if(isset($plugins['include']) && is_array($plugins['include'])) {
	foreach($plugins['include'] as $pluginid => $include) {
		if(!$include['adminid'] || ($include['adminid'] && $adminid > 0 && $include['adminid'] >= $adminid)) {
			if(@in_array($pluginid, $pluginlangs)) {
				@include_once DISCUZ_ROOT.'./forumdata/cache/cache_scriptlang.php';
			}
			@include_once DISCUZ_ROOT.'./plugins/'.$include['script'].'.inc.php';
		}
	}
}

if(isset($allowvisit) && $allowvisit == 0 && !(CURSCRIPT == 'member' && ($action == 'groupexpiry' || $action == 'activate'))) {
	showmessage('user_banned', NULL, 'HALTED');
} elseif(!(in_array(CURSCRIPT, array('logging', 'wap', 'seccode', 'ajax')) || $adminid == 1)) {
	if($bbclosed) {
		clearcookies();
		$closedreason = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='closedreason'");
		showmessage($closedreason ? $closedreason : 'board_closed', NULL, 'NOPERM');
	}
	periodscheck('visitbanperiods');
}

if((!empty($fromuid) || !empty($fromuser)) && ($creditspolicy['promotion_visit'] || $creditspolicy['promotion_register'])) {
	require_once DISCUZ_ROOT.'/include/promotion.inc.php';
}

if($uchome['addfeed']) {
	$customaddfeed = $customaddfeed == '-1' ? 0 : ($customaddfeed == 0 ? $uchome['addfeed'] : intval($customaddfeed));
} else {
	$customaddfeed = 0;
}

$rssauth = $rssstatus && $discuz_uid ? rawurlencode(authcode("$discuz_uid\t".($fid ? $fid : '')."\t".substr(md5($discuz_pw.$discuz_secques), 0, 8), 'ENCODE', md5($_DCACHE['settings']['authkey']))) : '0';
$transferstatus = $transferstatus && $allowtransfer;
$feedpostnum = $feedpostnum && $uchomeurl ? intval($feedpostnum) : 0;

$pluginhooks = array();
if(isset($hookscript[CURSCRIPT]['module'])) {
	hookscript(CURSCRIPT);
}

if($discuz_uid && $newbietaskupdate && $lastactivity < $newbietaskupdate) {
	require_once DISCUZ_ROOT.'./include/task.func.php';
	task_newfunction_autoapply();
}

?>