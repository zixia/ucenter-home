<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: cpanel.share.php 20964 2009-11-04 03:18:22Z zhaoxiongfei $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class AdminSession {

	var $uid = 0;
	var $panel = 0;
	var $inadmincp = false;
	var $isfounder = false;
	var $cpaccess = 0;
	var $checkip = 1;
	var $logfile = 'cplog';
	var $timelimit;
	var $errorcount = 0;
	var $storage = array();
	var $db = null;
	var $tablepre = '';

	function adminsession($uid, $groupid, $adminid, $ip) {

		global $adminipaccess, $db, $tablepre;

		$this->panel = defined('IN_ADMINCP') ? 1 : (defined('IN_MODCP') ? 2 : -1);

		$this->inadmincp = defined('IN_ADMINCP');
		$this->uid = $uid;
		$this->timelimit = time() - 1800;
		$this->db = &$db;
		$this->tablepre = &$tablepre;

		if($uid < 1 || $adminid < 1 || ($this->inadmincp && $adminid != 1)) {
			$cpaccess = 0;
		}elseif($this->inadmincp && $adminipaccess && !ipaccess($ip, $adminipaccess)) {
			$cpaccess = 2;
		} else {
			$session = $this->_loadsession($uid, $ip, $GLOBALS['admincp']['checkip']);
			$this->errorcount = $session['errorcount'];
			$this->storage = $session['storage'];
			if(empty($session)) {
				$this->creatsession($uid, $adminid, $ip);
				$cpaccess = 1;
			} elseif($session['errorcount'] == -1) {
				$this->update();
				$cpaccess = 3;
			} elseif($session['errorcount'] <= 3) {
				$cpaccess = 1;
			} else {
				$cpaccess = -1;
			}
		}

		if($cpaccess == 0) {
			//clearcookies();
			showmessage('admin_cpanel_noaccess', 'logging.php?action=login', 'HALTED');
		} elseif($cpaccess == 2) {
			showmessage('admin_cpanel_noaccess_ip', NULL, 'HALTED');
		} elseif($cpaccess == -1) {
			showmessage('admin_cpanel_locked', NULL, 'HALTED');
		}

		$this->cpaccess = $cpaccess;

	}

	function _loadsession($uid, $ip, $checkip = 1) {

		$session = array();

		$query = $this->db->query("SELECT uid, adminid, panel, ip, dateline, errorcount, storage FROM {$this->tablepre}adminsessions
			WHERE uid='$uid' ".($checkip ? "AND ip='$ip'" : '')." AND panel='{$this->panel}' AND dateline>'{$this->timelimit}'", 'SILENT');

		if(!$this->db->error()) {
			$session = $this->db->fetch_array($query);
			if(isset($session['storage'])) {
				$session['storage'] = $session['storage'] ? unserialize(base64_decode($session['storage'])) : array();
			}
		} else {
			$this->db->query("DROP TABLE IF EXISTS {$this->tablepre}adminsessions");
			$this->db->query("CREATE TABLE {$this->tablepre}adminsessions (
				uid mediumint(8) UNSIGNED NOT NULL default '0',
				adminid smallint(6) unsigned NOT NULL DEFAULT '0',
				panel tinyint(1) NOT NULL DEFAULT '0',
				ip varchar(15) NOT NULL default '',
				dateline int(10) unsigned NOT NULL default '0',
				errorcount tinyint(1) NOT NULL default '0',
				`storage` mediumtext NOT NULL,
				PRIMARY KEY (`uid`, `panel`))".(mysql_get_server_info() > '4.1' ? " ENGINE=MYISAM DEFAULT CHARSET=$GLOBALS[dbcharset]" : " TYPE=MYISAM")
				);
		}
		return $session;
	}

	function creatsession($uid, $adminid, $ip) {
		$url_forward = !empty($_SERVER['QUERY_STRING']) ? addslashes($_SERVER['QUERY_STRING']) : '';
		$this->destroy($uid);
		$this->db->query("INSERT INTO {$this->tablepre}adminsessions (uid, adminid, panel, ip, dateline, errorcount)
			VALUES ('$uid', '$adminid', '$this->panel', '$ip', '".time()."', '0')");
		$this->set('url_forward', $url_forward, true);
	}

	function destroy($uid = 0) {
		empty($uid) && $uid = $this->uid;
		$this->db->query("DELETE FROM {$this->tablepre}adminsessions WHERE (uid='$uid' AND panel='$this->panel') OR dateline<'$this->timelimit'");
	}

	function _loadstorage() {
		$storage = $this->db->result_first("SELECT storage FROM {$this->tablepre}adminsessions WHERE uid='{$this->uid}' AND panel='$this->panel'");
		if(!empty($storage)) {
			$this->storage = unserialize(base64_decode($storage));
		} else {
			$this->storage = array();
		}
	}

	function isfounder($user = '') {
		$user = empty($user) ? array('uid' => $GLOBALS['discuz_uid'], 'adminid' => $GLOBALS['adminid'], 'username' => $GLOBALS['discuz_userss']) : $user;
		$founders = str_replace(' ', '', $GLOBALS['forumfounders']);
		if($user['adminid'] <> 1) {
			return FALSE;
		} elseif(empty($founders)) {
			return TRUE;
		} elseif(strexists(",$founders,", ",$user[uid],")) {
			return TRUE;
		} elseif(!is_numeric($user['username']) && strexists(",$founders,", ",$user[username],")) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function set($varname, $value, $updatedb = false) {
		$this->storage[$varname] = $value;
		$updatedb && $this->update();
	}

	function get($varname, $fromdb = false) {
		$return = null;
		$fromdb && $this->_loadstorage();
		if(isset($this->storage[$varname])) {
			$return = $this->storage[$varname];
		}
		return $return;
	}

	function clear($updatedb = false) {
		$this->storage = array();
		$updatedb && $this->update();
	}

	function update() {
		if($this->uid) {
			$timestamp = time();
			$storage = !empty($this->storage) ? base64_encode((serialize($this->storage))) : '';
			$this->db->query("UPDATE {$this->tablepre}adminsessions SET dateline='$timestamp', errorcount='{$this->errorcount}', storage='{$storage}'
				WHERE uid='{$this->uid}' AND panel='$this->panel'", 'UNBUFFERED');
		}
	}
}

function acpmsg($message, $url = '', $type = '', $extra = '') {
	if(defined('IN_ADMINCP')) {
		!defined('CPHEADER_SHOWN') && cpheader();
		cpmsg($message, $url, $type, $extra);
	} else {
		showmessage($message, $url, $extra);
	}
}

function savebanlog($username, $origgroupid, $newgroupid, $expiration, $reason) {
	global $discuz_userss, $groupid, $onlineip, $timestamp, $forum, $reason;
	writelog('banlog', dhtmlspecialchars("$timestamp\t$discuz_userss\t$groupid\t$onlineip\t$username\t$origgroupid\t$newgroupid\t$expiration\t$reason"));
}

function clearlogstring($str) {
	if(!empty($str)) {
		if(!is_array($str)) {
			$str = dhtmlspecialchars(trim($str));
			$str = str_replace(array("\t", "\r\n", "\n", "   ", "  "), ' ', $str);
		} else {
			foreach ($str as $key => $val) {
				$str[$key] = clearlogstring($val);
			}
		}
	}
	return $str;
}

function implodearray($array, $skip = array()) {
	$return = '';
	if(is_array($array) && !empty($array)) {
		foreach ($array as $key => $value) {
			if(empty($skip) || !in_array($key, $skip)) {
				if(is_array($value)) {
					$return .= "$key={".implodearray($value, $skip)."}; ";
				} else {
					$return .= "$key=$value; ";
				}
			}
		}
	}
	return $return;
}

function deletethreads($tids = array()) {
	global $db, $tablepre, $losslessdel, $creditspolicy;

	static $cleartable = array(
		'threadsmod', 'relatedthreads', 'posts', 'polls',
		'polloptions', 'trades', 'activities', 'activityapplies', 'debates',
		'debateposts', 'attachments', 'favorites', 'typeoptionvars', 'forumrecommend', 'postposition'
	);
	$threadsdel = 0;
	if($tids = implodeids($tids)) {
		$auidarray = array();
		$query = $db->query("SELECT uid, attachment, dateline, thumb, remote FROM {$tablepre}attachments WHERE tid IN ($tids)");
		while($attach = $db->fetch_array($query)) {
			dunlink($attach['attachment'], $attach['thumb'], $attach['remote']);
			if($attach['dateline'] > $losslessdel) {
				$auidarray[$attach['uid']] = !empty($auidarray[$attach['uid']]) ? $auidarray[$attach['uid']] + 1 : 1;
			}
		}

		if($auidarray) {
			updateattachcredits('-', $auidarray, $creditspolicy['postattach']);
		}

		foreach($cleartable as $tb) {
			$db->query("DELETE FROM {$tablepre}$tb WHERE tid IN ($tids)", 'UNBUFFERED');
		}

		$db->query("DELETE FROM {$tablepre}threads WHERE tid IN ($tids)");
		$threadsdel = $db->affected_rows();
	}
	return $threadsdel;
}

function undeletethreads($tids) {
	global $db, $tablepre, $creditspolicy;
	$threadsundel = 0;
	if($tids && is_array($tids)) {
		$tids = '\''.implode('\',\'', $tids).'\'';

		$tuidarray = $ruidarray = $fidarray = array();
		$query = $db->query("SELECT fid, first, authorid FROM {$tablepre}posts WHERE tid IN ($tids)");
		while($post = $db->fetch_array($query)) {
			if($post['first']) {
				$tuidarray[] = $post['authorid'];
			} else {
				$ruidarray[] = $post['authorid'];
			}
			if(!in_array($post['fid'], $fidarray)) {
				$fidarray[] = $post['fid'];
			}
		}
		if($tuidarray) {
			updatepostcredits('+', $tuidarray, $creditspolicy['post']);
		}
		if($ruidarray) {
			updatepostcredits('+', $ruidarray, $creditspolicy['reply']);
		}

		$db->query("UPDATE {$tablepre}posts SET invisible='0' WHERE tid IN ($tids)", 'UNBUFFERED');
		$db->query("UPDATE {$tablepre}threads SET displayorder='0', moderated='1' WHERE tid IN ($tids)");
		$threadsundel = $db->affected_rows();

		updatemodlog($tids, 'UDL');
		updatemodworks('UDL', $threadsundel);

		foreach($fidarray as $fid) {
			updateforumcount($fid);
		}
	}
	return $threadsundel;
}
?>