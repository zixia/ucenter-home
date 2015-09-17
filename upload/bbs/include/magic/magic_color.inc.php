<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: magic_color.inc.php 19908 2009-09-14 13:14:41Z liuqiang $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(submitcheck('usesubmit')) {

	if(empty($highlight_color)) {
		showmessage('magics_info_nonexistence');
	}

	$thread = getpostinfo($tid, 'tid', array('fid', 'authorid', 'subject'));
	checkmagicperm($magicperm['forum'], $thread['fid']);
	magicthreadmod($tid);

	$db->query("UPDATE {$tablepre}threads SET highlight='$highlight_color', moderated='1' WHERE tid='$tid'", 'UNBUFFERED');
	$expiration = $timestamp + 86400;

	usemagic($magicid, $magic['num']);
	updatemagiclog($magicid, '2', '1', '0', $tid);
	updatemagicthreadlog($tid, $magicid, $magic['identifier'], $expiration);
	
	if($thread['authorid'] != $discuz_uid) {
		sendnotice($thread['authorid'], 'magic_thread', 'systempm');
	}
	
	showmessage('magics_operation_succeed', '', 1);

}

function showmagic() {
	global $tid, $lang;
	echo <<<EOT
	<table cellspacing="0" cellpadding="2" style="margin-top:-20px;">
		<tr>
			<td>$lang[target_tid]</td>
			<td>$lang[CCK_color]</td>
		</tr>
		<tr>
			<td><input type="text" value="$tid" name="tid" size="12" class="txt" /></td>
			<td class="hasdropdownbtn" style="position: relative;">
				<input type="hidden" id="highlight_color" name="highlight_color" />
				<input type="text" readonly="readonly" class="txt" id="highlight_color_show" style="width: 18px; border-right: none;" />
				<a href="javascript:;" id="highlight_color_ctrl" class="dropdownbtn" onclick="showHighLightColor('highlight_color')">^</a>

	 		</td>
	 	</tr>
	</table>
	<script type="text/javascript" reload="1">
		function showHighLightColor(hlid) {
			var showid = hlid + '_show';
			if(!$(showid + '_menu')) {
				var str = '';
				var coloroptions = {'0' : '#000', '1' : '#EE1B2E', '2' : '#EE5023', '3' : '#996600', '4' : '#3C9D40', '5' : '#2897C5', '6' : '#2B65B7', '7' : '#8F2A90', '8' : '#EC1282'};
				var menu = document.createElement('div');
				menu.id = showid + '_menu';
				menu.className = 'color_menu';
				menu.style.display = 'none';
				for(var i in coloroptions) {
					str += '<a href="javascript:;" onclick="$(\'' + hlid + '\').value=' + i + ';$(\'' + showid + '\').style.backgroundColor=\'' + coloroptions[i] + '\';hideMenu(\'' + menu.id + '\')" style="background:' + coloroptions[i] + ';color:' + coloroptions[i] + ';">' + coloroptions[i] + '</a>';
				}
				menu.innerHTML = str;
				$('append_parent').appendChild(menu);
			}
			showMenu({'ctrlid':hlid + '_ctrl','evt':'click','showid':showid});
		}
	</script>
EOT;
}

?>