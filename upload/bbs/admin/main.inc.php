<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: main.inc.php 20857 2009-10-28 00:57:24Z wangjinbo $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

include language('admincp.menu');
$lang = array_merge($lang, $menulang);

echo <<<EOT

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<title>$lang[admincp_title]</title>
<meta http-equiv="Content-Type" content="text/html; charset=$charset">
<meta content="Comsenz Inc." name="Copyright" />
<link rel="stylesheet" href="images/admincp/admincp.css" type="text/css" media="all" />
<script src="include/js/common.js" type="text/javascript"></script>
</head>
<body style="margin: 0px" scroll="no">
<div id="append_parent"></div>
<table cellpadding="0" cellspacing="0" width="100%" height="100%">
<tr>
<td colspan="2" height="90">
<div class="mainhd">
<div class="logo">Discuz! Administrator's Control Panel</div>
<div class="uinfo">
<p>$lang[header_welcome], <em>$discuz_userss</em> [ <a href="$BASESCRIPT?action=logout&sid=$sid" target="_top">$lang[header_logout]</a> ]</p>
<p class="btnlink"><a href="$indexname" target="_blank">$lang[header_bbs]</a></p>
</div>
<div class="navbg"></div>
<div class="nav">
<ul id="topmenu">

EOT;

showheader('index', 'home');
showheader('global', 'settings&operation=basic');
showheader('style', 'settings&operation=styles');
showheader('forum', 'forums');
showheader('user', 'members');
showheader('topic', 'moderate&operation=threads');
showheader('extended', 'tasks');
showheader('plugin', 'plugins');
showheader('adv', 'adv');
showheader('tool','tools&operation=updatecache');
if($isfounder) {
	echo '<li><em><a id="header_uc" hidefocus="true" href="'.UC_API.'/admin.php?m=frame&a=main&iframe=1" onclick="uc_login=1;toggleMenu(\'uc\', \'\');" target="main">'.$lang['header_uc'].'</a></em></li>';
}

echo <<<EOT

</ul>
<div class="currentloca">
<p id="admincpnav"></p>
</div>
<div class="navbd"></div>
<div class="sitemapbtn">
	<div style="float: left; margin:-5px 10px 0 0"><form name="search" method="post" action="$BASESCRIPT?action=search" target="main"><input type="text" name="keywords" value="" class="txt" /> <input type="hidden" name="searchsubmit" value="yes" class="btn" /><input type="submit" name="searchsubmit" value="$lang[search]" class="btn" style="margin-top: 5px;vertical-align:middle" /></form></div>
	<span id="add2custom"></span>
	<a href="###" id="cpmap" onclick="showMap();return false;"><img src="images/admincp/btn_map.gif" title="$lang[admincp_sitemap]" width="72" height="18" /></a>
</div>
</div>
</div>
</td>
</tr>
<tr>
<td valign="top" width="160" class="menutd">
<div id="leftmenu" class="menu">

EOT;

require_once DISCUZ_ROOT.'./admin/menu.inc.php';
$uc_api_url = UC_API;

$ucadd = $isfounder ? ", 'uc'" : '';

echo <<<EOT

</div>
</td>
<td valign="top" width="100%" class="mask" id="mainframes">
	<iframe src="$BASESCRIPT?$extra&sid=$sid" id="main" name="main" onload="mainFrame(0)" width="100%" height="100%" frameborder="0" scrolling="yes" style="overflow: visible;display:"></iframe>
</td>
</tr>
</table>
<div class="custombar" id="custombarpanel">
	&nbsp;<span id="custombar"></span><span id="custombar_add"></span>
</div>
<div id="scrolllink" style="display: none">
	<span onclick="menuScroll(1)"><img src="images/admincp/scrollu.gif" /></span>
	<span onclick="menuScroll(2)"><img src="images/admincp/scrolld.gif" /></span>
</div>
<div class="copyright">
	<p>Powered by <a href="http://www.discuz.net/" target="_blank">Discuz!</a> $version</p>
	<p>&copy; 2001-2009, <a href="http://www.comsenz.com/" target="_blank">Comsenz Inc.</a></p>
</div>

<div id="cpmap_menu" class="custom" style="display: none">
	<div class="cside">
		<h3><span class="ctitle1">$lang[custommenu]</span><a href="javascript:;" onclick="toggleMenu('tool', 'misc&operation=custommenu');hideMenu();" class="cadmin">$lang[admin]</a></h3>
		<ul class="cslist" id="custommenu"></ul>
	</div>
	<div class="cmain" id="cmain"></div>
	<div class="cfixbd"></div>
</div>

<script type="text/JavaScript">
	var headers = new Array('index', 'global', 'style', 'forum', 'user', 'topic', 'extended', 'plugin', 'adv', 'tool'$ucadd);
	var admincpfilename = '$BASESCRIPT';
	var menukey = '', custombarcurrent = 0;
	function toggleMenu(key, url) {
		if(key == 'index' && url == 'home') {
			if(BROWSER.ie) {
				doane(event);
			}
			parent.location.href = admincpfilename + '?frames=yes';
			return false;
		}
		menukey = key;
		for(var k in headers) {
			if($('menu_' + headers[k])) {
				$('menu_' + headers[k]).style.display = headers[k] == key ? '' : 'none';
			}
		}
		var lis = $('topmenu').getElementsByTagName('li');
		for(var i = 0; i < lis.length; i++) {
			if(lis[i].className == 'navon') lis[i].className = '';
		}
		$('header_' + key).parentNode.parentNode.className = 'navon';
		if(url) {
			parent.mainFrame(0);
			parent.main.location = admincpfilename + '?action=' + url;
			var hrefs = $('menu_' + key).getElementsByTagName('a');
			for(var j = 0; j < hrefs.length; j++) {
				hrefs[j].className = hrefs[j].href.substr(hrefs[j].href.indexOf(admincpfilename + '?action=') + 19) == url ? 'tabon' : (hrefs[j].className == 'tabon' ? '' : hrefs[j].className);
			}
		}
		setMenuScroll();
		return false;
	}
	function setMenuScroll() {
		var obj = $('menu_' + menukey);
		var scrollh = document.body.offsetHeight - 160;
		obj.style.overflow = 'visible';
		obj.style.height = '';
		$('scrolllink').style.display = 'none';
		if(obj.offsetHeight + 150 > document.body.offsetHeight && scrollh > 0) {
			obj.style.overflow = 'hidden';
			obj.style.height = scrollh + 'px';
			$('scrolllink').style.display = '';
		}
		custombar_resize();
	}
	function menuScroll(op, e) {
		var obj = $('menu_' + menukey);
		var scrollh = document.body.offsetHeight - 160;
		if(op == 1) {
			obj.scrollTop = obj.scrollTop - scrollh;
		} else if(op == 2) {
			obj.scrollTop = obj.scrollTop + scrollh;
		} else if(op == 3) {
			if(!e) e = window.event;
			if(e.wheelDelta <= 0 || e.detail > 0) {
				obj.scrollTop = obj.scrollTop + 20;
			} else {
				obj.scrollTop = obj.scrollTop - 20;
			}
		}
	}
	function initCpMenus(menuContainerid) {
		var key = '';
		var hrefs = $(menuContainerid).getElementsByTagName('a');
		for(var i = 0; i < hrefs.length; i++) {
			if(menuContainerid == 'leftmenu' && !key && '$extra'.indexOf(hrefs[i].href.substr(hrefs[i].href.indexOf(admincpfilename + '?action=') + 12)) != -1) {
				key = hrefs[i].parentNode.parentNode.id.substr(5);
				hrefs[i].className = 'tabon';
			}
			if(!hrefs[i].getAttribute('ajaxtarget')) hrefs[i].onclick = function() {
				if(menuContainerid != 'custommenu') {
					var lis = $(menuContainerid).getElementsByTagName('li');
					for(var k = 0; k < lis.length; k++) {
						if(lis[k].firstChild.className != 'menulink') lis[k].firstChild.className = '';
					}
					if(this.className == '') this.className = menuContainerid == 'leftmenu' ? 'tabon' : 'bold';
				}
				if(menuContainerid != 'leftmenu') {
					var hk, currentkey;
					var leftmenus = $('leftmenu').getElementsByTagName('a');
					for(var j = 0; j < leftmenus.length; j++) {
						hk = leftmenus[j].parentNode.parentNode.id.substr(5);
						if(this.href.indexOf(leftmenus[j].href) != -1) {
							leftmenus[j].className = 'tabon';
							if(hk != 'index') currentkey = hk;
						} else {
							leftmenus[j].className = '';
						}
					}
					if(currentkey) toggleMenu(currentkey);
					hideMenu();
				}
			}
		}
		return key;
	}
	var header_key = initCpMenus('leftmenu');
	toggleMenu(header_key ? header_key : 'index');
	function initCpMap() {
		var ul, hrefs, s;
		s = '<ul class="cnote"><li><img src="images/admincp/btn_map.gif" /></li><li> $lang[custommenu_tips]</li></ul><table class="cmlist" id="mapmenu"><tr>';

		for(var k in headers) {
			if(headers[k] != 'index' && headers[k] != 'uc') {
				s += '<td valign="top"><ul class="cmblock"><li><h4>' + $('header_' + headers[k]).innerHTML + '</h4></li>';
				ul = $('menu_' + headers[k]);
				hrefs = ul.getElementsByTagName('a');
				for(var i = 0; i < hrefs.length; i++) {
					s += '<li><a href="' + hrefs[i].href + '" target="' + hrefs[i].target + '" k="' + headers[k] + '">' + hrefs[i].innerHTML + '</a></li>';
				}
				s += '</ul></td>';
			}
		}
		s += '</tr></table>';
		return s;
	}
	$('cmain').innerHTML = initCpMap();
	initCpMenus('mapmenu');
	var cmcache = false;
	function showMap() {
		showMenu({'ctrlid':'cpmap','evt':'click', 'duration':3, 'pos':'00'});
		if(!cmcache) ajaxget(admincpfilename + '?action=misc&operation=custommenu&' + Math.random(), 'custommenu', '');
	}
	function resetEscAndF5(e) {
		e = e ? e : window.event;
		actualCode = e.keyCode ? e.keyCode : e.charCode;
		if(actualCode == 27) {
			if($('cpmap_menu').style.display == 'none') {
				showMap();
			} else {
				hideMenu();
			}
		}
		if(actualCode == 116 && parent.main) {
			if(custombarcurrent) {
				parent.$('main_' + custombarcurrent).contentWindow.location.reload();
			} else {
				parent.main.location.reload();
			}
			if(document.all) {
				e.keyCode = 0;
				e.returnValue = false;
			} else {
				e.cancelBubble = true;
				e.preventDefault();
			}
		}
	}
	function uc_left_menu(uc_menu_data) {
		var leftmenu = $('menu_uc');
		leftmenu.innerHTML = '';
		var html_str = '';
		for(var i=0;i<uc_menu_data.length;i+=2) {
			html_str += '<li><a href="'+uc_menu_data[(i+1)]+'" hidefocus="true" onclick="uc_left_switch(this)" target="main">'+uc_menu_data[i]+'</a></li>';
		}
		leftmenu.innerHTML = html_str;
		toggleMenu('uc', '');
		$('admincpnav').innerHTML = 'UCenter';
	}
	var uc_left_last = null;
	function uc_left_switch(obj) {
		if(uc_left_last) {
			uc_left_last.className = '';
		}
		obj.className = 'tabon';
		uc_left_last = obj;
	}
	function uc_modify_sid(sid) {
		$('header_uc').href = '$uc_api_url/admin.php?m=frame&a=main&iframe=1&sid=' + sid;
	}

	function mainFrame(id, src) {
		var setFrame = !id ? 'main' : 'main_' + id, obj = $('mainframes').getElementsByTagName('IFRAME'), exists = 0, src = !src ? '' : src;
		for(i = 0;i < obj.length;i++) {
			if(obj[i].name == setFrame) {
				exists = 1;
			}
			obj[i].style.display = 'none';
		}
		if(!exists) {
			if(BROWSER.ie) {
				frame = document.createElement('<iframe name="' + setFrame + '" id="' + setFrame + '"></iframe>');
			} else {
				frame = document.createElement('iframe');
				frame.name = setFrame;
				frame.id = setFrame;
			}
			frame.width = '100%';
			frame.height = '100%';
			frame.frameBorder = 0;
			frame.scrolling = 'yes';
			frame.style.overflow = 'visible';
			frame.style.display = 'none';
			if(src) {
				frame.src = src;
			}
			$('mainframes').appendChild(frame);
		}
		if(id) {
			custombar_set(id);
		}
		$(setFrame).style.display = '';
		if(!src && custombarcurrent) {
			$('custombar_' + custombarcurrent).className = '';
			custombarcurrent = 0;
		}
	}

	function custombar_update(deleteid) {
		var extra = !deleteid ? '' : '&deleteid=' + deleteid;
		if(deleteid && $('main_' + deleteid)) {
			$('mainframes').removeChild($('main_' + deleteid));
			if(deleteid == custombarcurrent) {
				mainFrame(0);
			}
		}
		ajaxget(admincpfilename + '?action=misc&operation=custombar' + extra, 'custombar', '', '', '', function () {custombar_resize();});
	}
	function custombar_resize() {
		custombarfixw = document.body.offsetWidth - 180;
		$('custombarpanel').style.width = custombarfixw + 'px';
	}
	function custombar_scroll(op, e) {
		var obj = $('custombarpanel');
		var step = 40;
		if(op == 1) {
			obj.scrollLeft = obj.scrollLeft - step;
		} else if(op == 2) {
			obj.scrollLeft = obj.scrollLeft + step;
		} else if(op == 3) {
			if(!e) e = window.event;
			if(e.wheelDelta <= 0 || e.detail > 0) {
				obj.scrollLeft = obj.scrollLeft + step;
			} else {
				obj.scrollLeft = obj.scrollLeft - step;
			}
		}
	}
	function custombar_set(id) {
		var currentobj = $('custombar_' + custombarcurrent), obj = $('custombar_' + id);
		if(currentobj == obj) {
			obj.className = 'current';
			return;
		}
		if(currentobj) {
			currentobj.className = '';
		}
		obj.className = 'current';
		custombarcurrent = id;
	}

	custombar_update();
	_attachEvent(document.documentElement, 'keydown', resetEscAndF5);
	_attachEvent(window, 'resize', setMenuScroll, document);
	if(BROWSER.ie){
		$('leftmenu').onmousewheel = function(e) { menuScroll(3, e) };
		$('custombarpanel').onmousewheel = function(e) { custombar_scroll(3, e) };
	} else {
		$('leftmenu').addEventListener("DOMMouseScroll", function(e) { menuScroll(3, e) }, false);
		$('custombarpanel').addEventListener("DOMMouseScroll", function(e) { custombar_scroll(3, e) }, false);
	}
</script>
</body>
</html>

EOT;

?>