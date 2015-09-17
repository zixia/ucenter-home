<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: plugin.class.php 20939 2009-11-02 01:14:31Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_uchome {

	function profile_baseinfo_top() {
		return '<div id="uch_feed"></div>';
	}

	function profile_side_top() {
		return '<div id="profile_stats"></div>';
	}

	function profile_side_bottom_output() {
		global $uchomeurl, $_DCACHE, $discuz_uid, $infosidestatus, $profileuid;
		if($uchomeurl && $_DCACHE['settings']['uchomeurl'] && $discuz_uid) {
			return '<script type="text/javascript" src="'.$uchomeurl.'/api/discuz.php?pagetype=profile&amp;status='.$_DCACHE['settings']['homeshow'].'&amp;uid='.$discuz_uid.'&amp;infosidestatus='.$infosidestatus['allow'].'&amp;updateuid='.$profileuid.'&amp;plugin=1"></script>';
		}
	}

	function viewthread_postheader() {
		global $page, $uchomeurl, $_DCACHE, $discuz_uid;
		if($page == 1 && $uchomeurl && $_DCACHE['settings']['uchomeurl'] && $discuz_uid) {
			return array('<span id="authorfeed"></span>');
		} else {
			return array();
		}
	}

	function viewthread_bottom_output() {
		global $page, $uchomeurl, $_DCACHE, $discuz_uid, $infosidestatus, $feedpostnum, $feeduid, $firstpid, $postlist;
		if($page == 1 && $uchomeurl && $_DCACHE['settings']['uchomeurl'] && $discuz_uid && !$postlist[$firstpid]['anonymous']) {
			return '<div id="authornewfeed_menu" style="display:none"></div><script type="text/javascript" src="'.$uchomeurl.'/api/discuz.php?pagetype=viewthread&amp;status='.$_DCACHE['settings']['homeshow'].'&amp;uid='.$discuz_uid.'&amp;infosidestatus='.$infosidestatus['allow'].'&amp;feedpostnum='.$feedpostnum.'&amp;updateuid='.$feeduid.'&amp;pid='.$firstpid.'&amp;plugin=1"></script>';
		}
	}

	function global_footer() {
		global $uchomeurl, $_DCACHE, $discuz_uid, $infosidestatus;
		if(CURSCRIPT != 'viewthread' && CURSCRIPT != 'profile' && $_DCACHE['settings']['uchomeurl'] && $discuz_uid) {
			return '<script type="text/javascript" src="'.$uchomeurl.'/api/discuz.php?pagetype='.CURSCRIPT.'&amp;status='.$_DCACHE['settings']['homeshow'].'&amp;uid='.$discuz_uid.'&amp;infosidestatus='.$infosidestatus['allow'].'"></script>';
		}
	}

}