<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: myrepeats.class.php 21277 2009-11-24 08:49:22Z monkey $
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

class plugin_myrepeats {

	function logging_myrepeats_output() {
		dsetcookie('mrn', '', -1);
		dsetcookie('mrd', '', -1);
	}

	function global_footer() {
		global $db, $tablepre, $discuz_uid, $discuz_user, $scriptlang;
		if(!$discuz_uid) {
			return;
		}

		@include DISCUZ_ROOT.'./forumdata/cache/plugin_myrepeats.php';
		$_DPLUGIN['myrepeats']['vars']['usergroups'] = (array)unserialize($_DPLUGIN['myrepeats']['vars']['usergroups']);
		if(in_array('', $_DPLUGIN['myrepeats']['vars']['usergroups'])) {
			$_DPLUGIN['myrepeats']['vars']['usergroups'] = array();
		}
		if(!in_array($GLOBALS['groupid'], $_DPLUGIN['myrepeats']['vars']['usergroups'])) {
			if(isset($GLOBALS['_DCOOKIE']['mrn'])) {
				$count = $GLOBALS['_DCOOKIE']['mrn'];
			} else {
				$count = $db->result_first("SELECT COUNT(*) FROM {$tablepre}myrepeats WHERE username='$discuz_user'");
				dsetcookie('mrn', $count, 3600);
			}
			if(!$count) {
				return;
			}
		}

		if(isset($GLOBALS['_DCOOKIE']['mrd'])) {
			$userlist = explode("\t", $GLOBALS['_DCOOKIE']['mrd']);
		} else {
			$userlist = array();
			$query = $db->query("SELECT username FROM {$tablepre}myrepeats WHERE uid='$discuz_uid'");
			while($user = $db->fetch_array($query)) {
				$userlist[] = $user['username'];
			}
			dsetcookie('mrd', implode("\t", $userlist), 3600);
		}
		$widthstr = count($userlist) > 5 ? ' inlinelist" style="width:255px;' : '" style="';
		$list = '<script>$(\'umenu\').innerHTML = \'<span id="myrepeats" onmouseover="showMenu(this.id)">['.$scriptlang['myrepeats']['switch'].']</span>\' + $(\'umenu\').innerHTML;</script><ul id="myrepeats_menu" class="popupmenu_popup'.$widthstr.'display:none;">';
		foreach($userlist as $user) {
			$user = stripslashes($user);
			$list .= '<li class="wide"><a href="plugin.php?id=myrepeats:switch&username='.rawurlencode($user).'">'.$user.'</a></li>';
		}
		$list .= '<li class="wide" style="clear:both"><a href="plugin.php?id=myrepeats:memcp">'.$scriptlang['myrepeats']['memcp'].'</a></li></ul>';
		return $list;
	}

}