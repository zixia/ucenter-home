<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: tag.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

define('CURSCRIPT', 'tag');
require_once './include/common.inc.php';

if(isset($action) && $action == 'relatetag') {

	dheader('Expires: '.gmdate('D, d M Y H:i:s', $timestamp + 3600).' GMT');
	!($rtid = intval($rtid)) && exit;
	$extscript = '';
	$threadtag = array();
	$query = $db->query("SELECT tagname FROM {$tablepre}threadtags WHERE tid='$rtid'");
	while($tags = $db->fetch_array($query)) {
		$threadtag[] = $tags['tagname'];
	}

	if($threadtag) {
		$requesttag = $threadtag[array_rand($threadtag)];
	} else {
		@include_once DISCUZ_ROOT.'./forumdata/cache/cache_viewthread.php';
		$requesttag = $db->result_first("SELECT tagname FROM {$tablepre}tags LIMIT ".rand(0, $_DCACHE['tags'][2] - 1).", 1", 0);
	}

	if(empty($requesttag)) {
		exit;
	}

	include_once DISCUZ_ROOT.'./uc_client/client.php';
	include_once template('relatetag');
	$datalist = uc_tag_get($requesttag, $relatedtag['limit']);
	$write = '';
	if(is_array($datalist)) {
		if(empty($datalist)) {
			@include_once DISCUZ_ROOT.'./uc_client/data/cache/apps.php';
			if(is_array($_CACHE['apps'])) {
				foreach($_CACHE['apps'] as $app) {
						if(array_key_exists($app['appid'], $relatedtag['limit'])) {
						$datalist[$app['appid']] = array('data' => array(), 'type' => $app['type']);
					}
				}
			}
		}

		$count = 0;
		foreach($datalist as $appid => $data) {
			$tagdata = '';
			$template = $relatedtag['template'][$appid];
			$datakey = $ext = array();
			$type = $data['type'];
			$data = $data['data'];
			$i = 0;
			foreach($data as $key => $value) {
				if($appid == UC_APPID && $value['url'] == $boardurl.'viewthread.php?tid='.$rtid) {
					continue;
				}
				if($type == 'SUPEV') {
					$extmsg = '<img src="'.$value['thumb'].'" />';
				} elseif(substr($type, 0, 6) == 'ECSHOP') {
					$extmsg = '<img src="'.$value['image'].'" />';
				} else {
					$extmsg = '';
				}
				if(!$datakey) {
					$tmp = array_keys($value);
					foreach($tmp as $k => $v) {
						$datakey[$k] = '{'.$v.'}';
					}
				}
				if($extmsg) {
					$ext[] = '<span id="app_'.$appid.'_'.$i.'"'.($i ? ' style="display: none"' : '').'>'.$extmsg.'</span>';
					$tmp = '<li onmouseover="$(\'app_'.$appid.'_\' + last_app_'.$appid.').style.display = \'none\';last_app_'.$appid.' = \''.$i.'\';$(\'app_'.$appid.'_'.$i.'\').style.display = \'\'">'.$template['template'].'</li>';
				} else {
					$tmp = '<li>'.$template['template'].'</li>';
				}

				$tmp = str_replace($datakey, $value, $tmp);
				$tagdata .= $tmp;
				$i++;
			}

			$ext = implode('', $ext);
			$tagdata = str_replace(array('\"', '\\\''), array('"', '\''), $tagdata);

			if($data['type'] == 'SUPEV') {
				$imgfield = 'thumb';
			} elseif(substr($data['type'], 0, 6) == 'ECSHOP') {
				$imgfield = 'image';
			} else {
				$imgfield = '';
			}
			$write .= tpl_relatetag($tagdata, $relatedtag['name'][$appid], $ext, $count);
			if($ext) {
				$extscript .= 'var last_app_'.$appid.' = \'0\';';
			}
			$count++;
			if($count == 3) {
				break;
			}
		}
	}

	$write = preg_replace("/\r\n|\n|\r/", '\n', tpl_relatetagwrap($write));
	echo '$(\'relatedtags\').innerHTML = "'.addcslashes($write, '"').'";'.$extscript;
	exit;

}

if(!$tagstatus) {
	showmessage('undefined_action', NULL, 'HALTED');
}

if(!empty($name)) {

	if(!preg_match('/^([\x7f-\xff_-]|\w|\s)+$/', $name) || strlen($name) > 20) {
		showmessage('undefined_action', NULL, 'HALTED');
	}

	require_once DISCUZ_ROOT.'./include/misc.func.php';
	require_once DISCUZ_ROOT.'./forumdata/cache/cache_forums.php';
	require_once DISCUZ_ROOT.'./forumdata/cache/cache_icons.php';

	$tpp = $inajax ? 5 : $tpp;
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $tpp;

	$tag = $db->fetch_first("SELECT * FROM {$tablepre}tags WHERE tagname='$name'");
	if($tag['closed']) {
		showmessage('tag_closed');
	}

	$count = $db->result_first("SELECT count(*) FROM {$tablepre}threadtags WHERE tagname='$name'");
	$query = $db->query("SELECT t.*,tt.tid as tagtid FROM {$tablepre}threadtags tt LEFT JOIN {$tablepre}threads t ON t.tid=tt.tid AND t.displayorder>='0' WHERE tt.tagname='$name' ORDER BY lastpost DESC LIMIT $start_limit, $tpp");
	$cleantid = $threadlist = array();
	while($tagthread = $db->fetch_array($query)) {
		if($tagthread['tid']) {
			$threadlist[] = procthread($tagthread);
		} else {
			$cleantid[] = $tagthread['tagtid'];
		}
	}
	if($cleantid) {
		$db->query("DELETE FROM {$tablepre}threadtags WHERE tagname='$name' AND tid IN (".implodeids($cleantid).")", 'UNBUFFERED');
		$cleancount = count($cleantid);
		if($count > $cleancount) {
			$db->query("UPDATE {$tablepre}tags SET total=total-'$cleancount' WHERE tagname='$name'", 'UNBUFFERED');
		} else {
			$db->query("DELETE FROM {$tablepre}tags WHERE tagname='$name'", 'UNBUFFERED');
		}
	}
	$tagnameenc = rawurlencode($name);
	$navtitle = $name.' - ';
	$multipage = multi($count, $tpp, $page, "tag.php?name=$tagnameenc");

	include template('tag_threads');

} else {

	$max = $db->result_first("SELECT total FROM {$tablepre}tags WHERE closed=0 ORDER BY total DESC LIMIT 1");
	$viewthreadtags = intval($viewthreadtags);

	$count = $db->result_first("SELECT count(*) FROM {$tablepre}tags WHERE closed=0");
	$randlimit = mt_rand(0, $count <= $viewthreadtags ? 0 : $count - $viewthreadtags);

	$query = $db->query("SELECT tagname,total FROM {$tablepre}tags WHERE closed=0 LIMIT $randlimit, $viewthreadtags");
	$randtaglist = array();
	while($tagrow = $db->fetch_array($query)) {
		$tagrow['level'] = ceil($tagrow['total'] * 5 / $max);
		$tagrow['tagnameenc'] = rawurlencode($tagrow['tagname']);
		$randtaglist[] = $tagrow;
	}
	shuffle($randtaglist);

	include template('tag');

}

?>