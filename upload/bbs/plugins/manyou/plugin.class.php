<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: plugin.class.php 20546 2009-10-09 01:09:29Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_manyou {
	var $feeddata;
	var $recommendlimit;

	function profile_baseinfo_top_output() {
		return '<div id="uch_feed">'.$this->_getfeed($GLOBALS['member']['uid']).'</div>';
	}

	function profile_baseinfo_bottom_output() {
		return '<script>$(\'baseprofile\').style.width=\'48%\';</script>';
	}

	function index_middle() {
		if($GLOBALS['indextype'] != 'classics') {
			return;
		}
		@include_once DISCUZ_ROOT . './forumdata/cache/plugin_manyou.php';
		$this->recommendlimit = $_DPLUGIN['manyou']['vars']['limit'];
		if(!$this->recommendlimit) {
			return;
		}
		$cachefile = DISCUZ_ROOT.'./forumdata/cache/manyou_recommend.php';
		if((@!include($cachefile)) || $limit != $limitcache) {
			require_once DISCUZ_ROOT.'./include/request.func.php';
			global $db, $tablepre;
			$query = $db->query("SELECT appid, appname FROM {$tablepre}myapp ORDER BY rand() DESC LIMIT ".$this->recommendlimit);
			$applist = array();
			while($app = $db->fetch_array($query)) {
				$applist[] = $app;
			}
			writetorequestcache($cachefile, 0, "\$limitcache = ".$this->recommendlimit.";\n\$applist = ".var_export($applist, 1).';');
		}
		include template('manyou_recommend');
		return $return;
	}

	function viewthread_postheader_output() {
		if($GLOBALS['page'] == 1 && $GLOBALS['discuz_uid'] && !$GLOBALS['postlist'][$GLOBALS['firstpid']]['anonymous'] && ($uid = $GLOBALS['postlist'][$GLOBALS['firstpid']]['authorid'])) {
			$this->_getfeed($uid);
			if($this->feeddata[$uid]) {
				$return = '<span id="authornewfeed" onmouseover="showMenu({\'ctrlid\':this.id});">'.$GLOBALS['scriptlang']['manyou']['viewthread_link'].'</span>';
				return array($return);
			} else {
				return array();
			}
		} else {
			return array();
		}
	}

	function viewthread_bottom_output() {
		if($GLOBALS['page'] == 1 && $GLOBALS['discuz_uid'] && !$GLOBALS['postlist'][$GLOBALS['firstpid']]['anonymous'] && ($uid = $GLOBALS['postlist'][$GLOBALS['firstpid']]['authorid']) && $this->feeddata[$uid]) {
			return '<div id="authornewfeed_menu" style="display:none"><div>'.$this->feeddata[$uid].'</div></div>';
		}
	}

	function _getfeed($uid = 0) {
		if(!isset($this->feeddata[$uid])) {
			$conf = array(
				'type' => 'manyou',
				'num' => 5,
				'cachelife' => 0,
				'multipage' => 0,
				'uid' => $uid,
			);
			$feeds = get_feed($conf);
			$bi = 1;
			$str = '';
			if($feeds['data']) {
				$str = '<ul>';
				foreach($feeds['data'] as $k => $feed) {
					$trans['{addbuddy}'] = $view == 'all' && $feed['uid'] != $discuz_uid ? '<a href="my.php?item=buddylist&newbuddyid='.$feed['uid'].'&buddysubmit=yes" id="ajax_buddy_'.($bi++).'" title="'.$GLOBALS['scriptlang']['manyou']['add_buddy'].'" onclick="ajaxmenu(this, 3000);doane(event);"><img style="vertical-align:middle" src="manyou/images/myadd.gif" /></a>' : '';
					$feeds['data'][$k]['title'] = strtr($feed['title'], $trans);
					$feeds['data'][$k]['title'] = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a target="_blank" \\1 href="\\3" \\4>', $feeds['data'][$k]['title']);
					$feeds['data'][$k]['icon_image'] = 'http://appicon.manyou.com/icons/'.$feed['appid'];
					$str .= '<li><img class="appicon" src="'.$feeds['data'][$k]['icon_image'].'" /> '.$feeds['data'][$k]['title'].'</li>';
				}
				$str .= '</ul>';
			}
			$this->feeddata[$uid] = $str;
		}
		return $this->feeddata[$uid];
	}
}

?>