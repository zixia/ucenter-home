<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc. & (C)2009 DPS LuciferSheng
	This is NOT a freeware, use is subject to license terms

	$Id: postawards.class.php 21306 2009-11-26 00:56:50Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_dps_postawards {

	function viewthread_bottom_output(){
		global $tid, $paward ,$groupid;

		if(!@include_once DISCUZ_ROOT.'./forumdata/cache/cache_postawards_setting.php') {
			$this->_creatCache();
		}

		require_once DISCUZ_ROOT.'./forumdata/plugins/dps_postawards.lang.php';
		if($PACACHE['userright'][$groupid]['postawards']){
			$str = '<script language="javascript">
$("modopt_menu").innerHTML+="<li class=\"wide\"><a href=\"plugin.php?id=dps_postawards:postawards&tid='.$tid.'\" onclick=\"showWindow(\'paward\', this.href);return false;\">'.$templatelang['dps_postawards']['dps_postawards_nolink'].'</a></li>";
</script>';
		} else {
			$str = '';
		}
		return $str;
	}

	function _creatCache(){
		global $db, $tablepre;
		include DISCUZ_ROOT.'./include/cache.func.php';
		$query = $db->query("SELECT data FROM {$tablepre}caches WHERE cachename='postawards'");
		$data = $db->fetch_array($query);
		writetocache('postawards_setting', '', $data['data']);
	}

}

?>