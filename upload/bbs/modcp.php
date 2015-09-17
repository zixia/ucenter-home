<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: modcp.php 21068 2009-11-10 04:48:56Z monkey $
*/

define('NOROBOT', TRUE);
define('IN_MODCP', true);
define('CURSCRIPT', 'modcp');

$action = !empty($_REQUEST['action']) ? (!empty($_GET['action']) ? $_GET['action'] : '') : (!empty($_POST['action']) ? $_POST['action'] : '');

require_once './include/common.inc.php';
require_once './admin/cpanel.share.php';

$discuz_action = 210;

$action = empty($action) && $fid ? 'threads' : $action;
$cpscript = basename($PHP_SELF);
$modsession = new AdminSession($discuz_uid, $groupid, $adminid, $onlineip);

if($modsession->cpaccess == 1) {
	if($action == 'login' && $cppwd && submitcheck('submit')) {
		require_once DISCUZ_ROOT.'./uc_client/client.php';
		$ucresult = uc_user_login($discuz_uid, $cppwd, 1);
		if($ucresult[0] > 0) {
			$modsession->errorcount = '-1';
			$url_forward = $modsession->get('url_forward');
			$modsession->clear(true);
			$url_forward && dheader("Location: $cpscript?$url_forward");
			$action = 'home';
		} else{
			$modsession->errorcount ++;
			$modsession->update();
		}
	} else {
		$action = 'login';
	}
}

if($action == 'logout') {
	$modsession->destroy();
	showmessage('modcp_logout_succeed', $indexname);
}

$modforums = $modsession->get('modforums');
if($modforums === null) {
	$modforums = array('fids' => '', 'list' => array(), 'recyclebins' => array());
	$comma = '';
	if($adminid == 3) {
		$query = $db->query("SELECT m.fid, f.name, f.recyclebin
				FROM {$tablepre}moderators m
				LEFT JOIN {$tablepre}forums f ON f.fid=m.fid
				WHERE m.uid='$discuz_uid' AND f.status='1' AND f.type<>'group'");
		while($tforum = $db->fetch_array($query)) {
			$modforums['fids'] .= $comma.$tforum['fid']; $comma = ',';
			$modforums['recyclebins'][$tforum['fid']] = $tforum['recyclebin'];
			$modforums['list'][$tforum['fid']] = strip_tags($tforum['name']);
		}
	} else {
		$sql = !empty($accessmasks) ?
			"SELECT f.fid, f.name, f.threads, f.recyclebin, ff.viewperm, a.allowview FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid
				LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid
				WHERE f.status='1' AND ff.redirect=''"
			: "SELECT f.fid, f.name, f.threads, f.recyclebin, ff.viewperm, ff.redirect FROM {$tablepre}forums f
				LEFT JOIN {$tablepre}forumfields ff USING(fid)
				WHERE f.status='1' AND f.type<>'group' AND ff.redirect=''";
		$query = $db->query($sql);
		while ($tforum = $db->fetch_array($query)) {
			if($tforum['allowview'] == 1 || ($tforum['allowview'] == 0 && ((!$tforum['viewperm'] && $readaccess) || ($tforum['viewperm'] && forumperm($tforum['viewperm']))))) {
				$modforums['fids'] .= $comma.$tforum['fid']; $comma = ',';
				$modforums['recyclebins'][$tforum['fid']] = $tforum['recyclebin'];
				$modforums['list'][$tforum['fid']] = strip_tags($tforum['name']);
			}
		}
	}

	$modsession->set('modforums', $modforums, true);
}

if($fid && $forum['ismoderator']) {
	dsetcookie('modcpfid', $fid);
	$forcefid = "&amp;fid=$fid";
} elseif(!empty($modforums) && count($modforums['list']) == 1) {
	$forcefid = "&amp;fid=$modforums[fids]";
} else {
	$forcefid = '';
}

$script = $modtpl = '';

switch ($action) {

	case 'announcements':
		$allowpostannounce && $script = 'announcements';
		break;

	case 'members':
		$op == 'edit' && $allowedituser && $script = 'members';
		$op == 'ban' && $allowbanuser && $script = 'members';
		$op == 'ipban' && $allowbanip && $script = 'members';
		break;

	case 'report':
		$allowviewreport && $script = 'report';
		break;

	case 'moderate':
		($op == 'threads' || $op == 'replies') && $allowmodpost && $script = 'moderate';
		$op == 'members' && $allowmoduser && $script = 'moderate';
		break;

	case 'forums':
		$op == 'editforum' && $alloweditforum && $script = 'forums';
		$op == 'recommend' && $allowrecommendthread && $script = 'forums';
		break;

	case 'forumaccess':
		$allowedituser && $script = 'forumaccess';
		break;

	case 'logs':
		$allowviewlog && $script = 'logs';
		break;

	case 'login':
		$script = $modsession->cpaccess == 1 ? 'login' : 'home';
		break;

	case 'threads':
		$script = 'threads';
		break;

	case 'recyclebins':
		$script = 'recyclebins';
		break;

	case 'plugin':
		$script = 'plugin';
		break;

	default:
		$action = $script = 'home';
		$modtpl = 'modcp_home';
}

$script = empty($script) ? 'noperm' : $script;
$modtpl = empty($modtpl) ? (!empty($script) ? 'modcp_'.$script : '') : $modtpl;
$op = isset($op) ? trim($op) : '';

if($script != 'logs') {
	$extra = implodearray(array('GET' => $_GET, 'POST' => $_POST), array('cppwd', 'formhash', 'submit', 'addsubmit'));
	$modcplog = array($timestamp, $discuz_user, $adminid, $onlineip, $action, $op, $fid, $extra);
	writelog('modcp', implode("\t", clearlogstring($modcplog)));
}

require DISCUZ_ROOT.'./modcp/'.$script.'.inc.php';

$reportnum = $modpostnum = $modthreadnum = $modforumnum = 0;
$modforumnum = count($modforums['list']);
if($modforumnum) {
	$reportnum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}reportlog WHERE fid IN($modforums[fids]) AND status='1'");
	$modnum = ($allowmodpost ? ($db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE invisible='-2' AND first='0' and fid IN($modforums[fids])") +
		$db->result_first("SELECT COUNT(*) FROM {$tablepre}threads WHERE fid IN($modforums[fids]) AND displayorder='-2'")) : 0) +
		($allowmoduser ? $db->result_first("SELECT COUNT(*) FROM {$tablepre}validating WHERE status='0'") : 0);
}

switch($adminid) {
	case 1: $access = '1,2,3,4,5,6,7'; break;
	case 2: $access = '2,3,6,7'; break;
	default: $access = '1,3,5,7'; break;
}
$notenum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}adminnotes WHERE access IN ($access)");

include template('modcp');

?>