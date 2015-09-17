<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: admincp.php 20568 2009-10-09 09:38:53Z monkey $
*/

define('IN_ADMINCP', TRUE);
define('NOROBOT', TRUE);
require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./admin/global.func.php';
require_once DISCUZ_ROOT.'./admin/cpanel.share.php';
require_once DISCUZ_ROOT.'./include/cache.func.php';

include language('admincp');

$discuz_action = 211;

//$admincp['checkip'] && $onlineip = empty($_SERVER['REMOTE_ADDR']) ? getenv('REMOTE_ADDR') : $_SERVER['REMOTE_ADDR'];

$adminsession = new AdminSession($discuz_uid, $groupid, $adminid, $onlineip);
$dactionarray = $adminsession->get('dactionarray');
if($dactionarray ===  null) {
	$dactionarray = array();
	if($radminid != $groupid) {
		$tmp = unserialize($db->result_first("SELECT disabledactions FROM {$tablepre}adminactions WHERE admingid='$groupid'"));
		$dactionarray = $tmp ? $tmp : array();
	}
	$adminsession->set('dactionarray', $dactionarray, true);
}

$cpaccess = $adminsession->cpaccess;
if($cpaccess == 0 || (!$discuz_secques && $admincp['forcesecques'])) {
	require_once DISCUZ_ROOT.'./admin/login.inc.php';
} elseif($cpaccess == 1) {
	if($admin_password != '') {
		require_once DISCUZ_ROOT.'./uc_client/client.php';
		$ucresult = uc_user_login($discuz_uid, $admin_password, 1, 1, $admin_questionid, $admin_answer);
		if($ucresult[0] > 0) {
			$adminsession->errorcount = -1;
			$adminsession->update();
			dheader('Location: '.$BASESCRIPT.'?'.cpurl('url', array('sid')));
		} else {
			$adminsession->errorcount ++;
			$adminsession->update();
			writelog('cplog', dhtmlspecialchars("$timestamp\t$discuz_userss\t$adminid\t$onlineip\t$action\tAUTHENTIFICATION(PASSWORD)"));
		}
	}
	require_once DISCUZ_ROOT.'./admin/login.inc.php';
} else {

	$username = !empty($username) ? dhtmlspecialchars($username) : '';
	$action = !empty($action) && is_string($action) ? trim($action) : '';
	$operation = !empty($operation) && is_string($operation) ? trim($operation) : '';
	$page = isset($page) ? intval((max(1, $page))) : 0;

	if(!empty($action) && !in_array($action, array('main', 'logs'))) {
		switch($cpaccess) {
			case 1:
				$extralog = 'AUTHENTIFICATION(ERROR #'.intval($adminsession['errorcount']).')';
				break;
			case 3:
				$extralog = implodearray(array('GET' => $_GET, 'POST' => $_POST), array('formhash', 'submit', 'addsubmit', 'admin_password', 'sid', 'action'));
				break;
			default:
				$extralog = '';
		}
		$extralog = trim(str_replace(array('GET={};', 'POST={};'), '', $extralog));
		$extralog = $action == 'home' && isset($securyservice) ? '' : $extralog;
		writelog('cplog', implode("\t", clearlogstring(array($timestamp,$discuz_userss,$adminid,$onlineip,$action,$extralog))));
		unset($extralog);
	}

	$isfounder = $adminsession->isfounder = isfounder();
	if(empty($action) || isset($frames)) {
		$extra = cpurl('url');
		$extra = $extra && $action ? $extra : (!empty($runwizard) ? 'action=runwizard' : 'action=home');
		require_once DISCUZ_ROOT.'./admin/main.inc.php';
	} elseif($action == 'logout') {
		$adminsession ->destroy();
		dheader("Location: $indexname");
	} else {
		checkacpaction($action, $operation);
		if(in_array($action, array('home', 'settings', 'members', 'profilefields', 'admingroups', 'usergroups', 'ranks', 'forums', 'threadtypes', 'threads', 'moderate', 'attach', 'smilies', 'recyclebin', 'prune', 'styles', 'addons', 'plugins', 'tasks', 'magics', 'medals', 'google', 'qihoo', 'announce', 'faq', 'ec', 'tradelog', 'creditwizard', 'jswizard', 'project', 'counter', 'misc', 'adv', 'logs', 'tools', 'checktools', 'search', 'upgrade')) || ($isfounder && in_array($action, array('runwizard', 'templates', 'db')))) {
			require_once DISCUZ_ROOT.'./admin/'.$action.'.inc.php';
			$title = 'cplog_'.$action.($operation ? '_'.$operation : '');
		} else {
			cpheader();
			cpmsg('noaccess');
		}
		cpfooter();

	}
}

?>