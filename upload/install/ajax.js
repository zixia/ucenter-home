/*
	[SupeSite] (C) 2007-2008 Comsenz Inc.
	$Id: ajax.js 10839 2008-12-29 02:27:42Z zhaolei $
*/

var Ajaxs = new Array();
var AjaxStacks = new Array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
var ajaxpostHandle = 0;
var evalscripts = new Array();
var ajaxpostresult = 0;
var f;

function $(id) {
	return document.getElementById(id);
}

function in_array(needle, haystack) {
	if(typeof needle == 'string' || typeof needle == 'number') {
		for(var i in haystack) {
			if(haystack[i] == needle) {
					return true;
			}
		}
	}
	return false;
}

function Ajax(recvType, waitId) {

	for(var stackId = 0; stackId < AjaxStacks.length && AjaxStacks[stackId] != 0; stackId++);
	AjaxStacks[stackId] = 1;

	var aj = new Object();

	aj.loading = 'Loading...';//public
	aj.recvType = recvType ? recvType : 'XML';//public
	aj.waitId = waitId ? $(waitId) : null;//public

	aj.resultHandle = null;//private
	aj.sendString = '';//private
	aj.targetUrl = '';//private
	aj.stackId = 0;
	aj.stackId = stackId;

	aj.setLoading = function(loading) {
		if(typeof loading !== 'undefined' && loading !== null) aj.loading = loading;
	}

	aj.setRecvType = function(recvtype) {
		aj.recvType = recvtype;
	}

	aj.setWaitId = function(waitid) {
		aj.waitId = typeof waitid == 'object' ? waitid : $(waitid);
	}

	aj.createXMLHttpRequest = function() {
		var request = false;
		if(window.XMLHttpRequest) {
			request = new XMLHttpRequest();
			if(request.overrideMimeType) {
				request.overrideMimeType('text/xml');
			}
		} else if(window.ActiveXObject) {
			var versions = ['Microsoft.XMLHTTP', 'MSXML.XMLHTTP', 'Microsoft.XMLHTTP', 'Msxml2.XMLHTTP.7.0', 'Msxml2.XMLHTTP.6.0', 'Msxml2.XMLHTTP.5.0', 'Msxml2.XMLHTTP.4.0', 'MSXML2.XMLHTTP.3.0', 'MSXML2.XMLHTTP'];
			for(var i=0; i<versions.length; i++) {
				try {
					request = new ActiveXObject(versions[i]);
					if(request) {
						return request;
					}
				} catch(e) {}
			}
		}
		return request;
	}

	aj.XMLHttpRequest = aj.createXMLHttpRequest();
	aj.showLoading = function() {
		if(aj.waitId && (aj.XMLHttpRequest.readyState != 4 || aj.XMLHttpRequest.status != 200)) {
			changedisplay(aj.waitId, '');
			aj.waitId.innerHTML = '<span><img src="img/loading.gif"> ' + aj.loading + '</span>';
		}
	}

	aj.processHandle = function() {
		if(aj.XMLHttpRequest.readyState == 4 && aj.XMLHttpRequest.status == 200) {
			for(k in Ajaxs) {
				if(Ajaxs[k] == aj.targetUrl) {
					Ajaxs[k] = null;
				}
			}
			if(aj.waitId) changedisplay(aj.waitId, 'none');
			if(aj.recvType == 'HTML') {
				aj.resultHandle(aj.XMLHttpRequest.responseText, aj);
			} else if(aj.recvType == 'XML') {
				try {
					aj.resultHandle(aj.XMLHttpRequest.responseXML.lastChild.firstChild.nodeValue, aj);
				} catch(e) {
					aj.resultHandle('', aj);
				}
			}
			AjaxStacks[aj.stackId] = 0;
		}
	}

	aj.get = function(targetUrl, resultHandle) {
		if(targetUrl.indexOf('?') != -1) {
			targetUrl = targetUrl + '&inajax=1';
		} else {
			targetUrl = targetUrl + '?inajax=1';
		}
		setTimeout(function(){aj.showLoading()}, 500);
		if(in_array(targetUrl, Ajaxs)) {
			return false;
		} else {
			Ajaxs.push(targetUrl);
		}
		aj.targetUrl = targetUrl;
		aj.XMLHttpRequest.onreadystatechange = aj.processHandle;
		aj.resultHandle = resultHandle;
		var delay = 100;
		if(window.XMLHttpRequest) {
			setTimeout(function(){
			aj.XMLHttpRequest.open('GET', aj.targetUrl);
			aj.XMLHttpRequest.send(null);}, delay);
		} else {
			setTimeout(function(){
			aj.XMLHttpRequest.open("GET", targetUrl, true);
			aj.XMLHttpRequest.send();}, delay);
		}

	}
	aj.post = function(targetUrl, sendString, resultHandle) {
		if(targetUrl.indexOf('?') != -1) {
			targetUrl = targetUrl + '&inajax=1';
		} else {
			targetUrl = targetUrl + '?inajax=1';
		}

		setTimeout(function(){aj.showLoading()}, 500);
		if(in_array(targetUrl, Ajaxs)) {
			return false;
		} else {
			Ajaxs.push(targetUrl);
		}
		aj.targetUrl = targetUrl;
		aj.sendString = sendString;
		aj.XMLHttpRequest.onreadystatechange = aj.processHandle;
		aj.resultHandle = resultHandle;
		aj.XMLHttpRequest.open('POST', targetUrl);
		aj.XMLHttpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		aj.XMLHttpRequest.send(aj.sendString);
	}
	return aj;
}

function newfunction(func){
	var args = new Array();
	for(var i=1; i<arguments.length; i++) args.push(arguments[i]);
	return function(event){
		doane(event);
		window[func].apply(window, args);
		return false;
	}
}

function changedisplay(obj, display) {
	if(display == 'auto') {
		obj.style.display = obj.style.display == '' ? 'none' : '';
	} else {
		obj.style.display = display;
	}
	return false;
}

function evalscript(s) {
	if(s.indexOf('<script') == -1) return s;
	var p = /<script[^\>]*?src=\"([^\>]*?)\"[^\>]*?(reload=\"1\")?(?:charset=\"([\w\-]+?)\")?><\/script>/ig;
	var arr = new Array();
	while(arr = p.exec(s)) appendscript(arr[1], '', arr[2], arr[3]);
	p = /<script (?!src)[^\>]*?( reload=\"1\")?>([^\x00]+?)<\/script>/ig;
	while(arr = p.exec(s)) appendscript('', arr[2], arr[1]);
	return s;
}

function appendscript(src, text, reload, charset) {
	var id = hash(src + text);
	if(!reload && in_array(id, evalscripts)) return;
	if(reload && $(id)) {
		$(id).parentNode.removeChild($(id));
	}

	evalscripts.push(id);
	var scriptNode = document.createElement("script");
	scriptNode.type = "text/javascript";
	scriptNode.id = id;
	scriptNode.charset = charset;
	try {
		if(src) {
			scriptNode.src = src;
		} else if(text){
			scriptNode.text = text;
		}
		$('append_parent').appendChild(scriptNode);
	} catch(e) {}
}

function stripscript(s) {
	return s.replace(/<script.*?>.*?<\/script>/ig, '');
}

function ajaxupdateevents(obj, tagName) {
	tagName = tagName ? tagName : 'A';
	var objs = obj.getElementsByTagName(tagName);
	for(k in objs) {
		var o = objs[k];
		ajaxupdateevent(o);
	}
}

function ajaxupdateevent(o) {
	if(typeof o == 'object' && o.getAttribute) {
		if(o.getAttribute('ajaxtarget')) {
			if(!o.id) o.id = Math.random();
			var ajaxevent = o.getAttribute('ajaxevent') ? o.getAttribute('ajaxevent') : 'click';
			var ajaxurl = o.getAttribute('ajaxurl') ? o.getAttribute('ajaxurl') : o.href;
			_attachEvent(o, ajaxevent, newfunction('ajaxget', ajaxurl, o.getAttribute('ajaxtarget'), o.getAttribute('ajaxwaitid'), o.getAttribute('ajaxloading'), o.getAttribute('ajaxdisplay')));
			if(o.getAttribute('ajaxfunc')) {
				o.getAttribute('ajaxfunc').match(/(\w+)\((.+?)\)/);
				_attachEvent(o, ajaxevent, newfunction(RegExp.$1, RegExp.$2));
			}
		}
	}
}

function ajaxget(url, showid, waitid, loading, display, recall) {
	waitid = typeof waitid == 'undefined' || waitid === null ? showid : waitid;
	var x = new Ajax();
	x.setLoading(loading);
	x.setWaitId(waitid);
	x.display = typeof display == 'undefined' || display == null ? '' : display;
	x.showId = $(showid);
	if(x.showId) x.showId.orgdisplay = typeof x.showId.orgdisplay === 'undefined' ? x.showId.style.display : x.showId.orgdisplay;

	if(url.substr(strlen(url) - 1) == '#') {
		url = url.substr(0, strlen(url) - 1);
		x.autogoto = 1;
	}

	var url = url + '&inajax=1&ajaxtarget=' + showid;
	x.get(url, function(s, x) {
		evaled = false;
		if(s.indexOf('ajaxerror') != -1) {
			evalscript(s);
			evaled = true;
		}
		if(!evaled && (typeof ajaxerror == 'undefined' || !ajaxerror)) {
			if(x.showId) {
				changedisplay(x.showId, x.showId.orgdisplay);
				changedisplay(x.showId, x.display);
				x.showId.orgdisplay = x.showId.style.display;
				ajaxinnerhtml(x.showId, s);
				ajaxupdateevents(x.showId);
				if(x.autogoto) scroll(0, x.showId.offsetTop);
			}
		}
		if(!evaled)evalscript(s);
		ajaxerror = null;
		if(recall) {eval(recall);}
	});
}

function ajaxpost(formid, showid, func) {
	showloading();

	if(ajaxpostHandle != 0) {
		return false;
	}
	var ajaxframeid = 'ajaxframe';
	var ajaxframe = $(ajaxframeid);
	if(ajaxframe == null) {
		if (is_ie && !is_opera) {
			ajaxframe = document.createElement("<iframe name='" + ajaxframeid + "' id='" + ajaxframeid + "'></iframe>");
		} else {
			ajaxframe = document.createElement("iframe");
			ajaxframe.name = ajaxframeid;
			ajaxframe.id = ajaxframeid;
		}
		//ajaxframe.id = ajaxframeid;
		ajaxframe.style.display = 'none';
		$('append_parent').appendChild(ajaxframe);

	}
	$(formid).target = ajaxframeid;
	$(formid).action = $(formid).action + '&inajax=1';

	ajaxpostHandle = [showid, ajaxframeid, formid, $(formid).target, func];
	if(ajaxframe.attachEvent) {
		ajaxframe.detachEvent ('onload', ajaxpost_load);
		ajaxframe.attachEvent('onload', ajaxpost_load);
	} else {
		document.removeEventListener('load', ajaxpost_load, true);
		ajaxframe.addEventListener('load', ajaxpost_load, false);
	}
	$(formid).submit();
	return false;
}

function ajaxpost_load() {
	showloading('none');
	if(is_ie) {
		var s = $(ajaxpostHandle[1]).contentWindow.document.XMLDocument.text;
	} else {
		var s = $(ajaxpostHandle[1]).contentWindow.document.documentElement.firstChild.nodeValue;
	}
	evaled = false;
	if(s.indexOf('ajaxerror') != -1) {
		evalscript(s);
		evaled = true;
	}
	if(s.indexOf('a href=') != -1) {
		ajaxpostresult = 1;
	} else {
		ajaxpostresult = 0;
	}
	//function
	if(ajaxpostHandle[4]) {
		setTimeout(ajaxpostHandle[4] + '(\'' + ajaxpostHandle[0] + '\',' + ajaxpostresult + ')', 10);
	}
	if(!evaled && (typeof ajaxerror == 'undefined' || !ajaxerror)) {
		ajaxinnerhtml($(ajaxpostHandle[0]), '<div class="popupmenu_inner">' + s + '</div>');
		if(!evaled)evalscript(s);
	}
	ajaxerror = null;
	if($(ajaxpostHandle[2])) {
		$(ajaxpostHandle[2]).target = ajaxpostHandle[3];
	}

	ajaxpostHandle = 0;
}

function ajaxmenu(e, ctrlid, timeout, func, offset) {
	var box = 0;
	showloading();
	if(jsmenu['active'][0] && jsmenu['active'][0].ctrlkey == ctrlid) {
		hideMenu();
		doane(e);
		return;
	} else if(is_ie && is_ie < 7 && document.readyState.toLowerCase() != 'complete') {
		return;
	}
	cache = 0;
	divclass = 'popupmenu_popup';
	optionclass = 'popupmenu_option';
	if(isUndefined(timeout)) timeout = 3000;
	if(isUndefined(func)) func = '';
	if(isUndefined(offset)) offset = 0;
	duration = timeout > 10000 ? 3 : 0;
	executetime = duration ? 2000: timeout;
	if(offset == -1) {
		divclass = 'popupmenu_centerbox';
		box = 1;
	}
	var div = $(ctrlid + '_menu');
	if(cache && div) {
		showMenu(ctrlid, e.type == 'click', offset, duration, timeout, 0, ctrlid, 400, 1);
		if(func) setTimeout(func + '(' + ctrlid + ')', executetime);
		doane(e);
	} else {
		if(!div) {
			div = document.createElement('div');
			div.ctrlid = ctrlid;
			div.id = ctrlid + '_menu';
			div.style.display = 'none';
			div.className = divclass;
			$('append_parent').appendChild(div);
		}

		var x = new Ajax();
		var href = !isUndefined($(ctrlid).href) ? $(ctrlid).href : $(ctrlid).attributes['href'].value;
		x.div = div;
		x.etype = e.type;
		x.optionclass = optionclass;
		x.duration = duration;
		x.timeout = timeout;
		x.executetime = executetime;
		x.get(href + '&ajaxmenuid='+ctrlid+'_menu&popupmenu_box='+box, function(s) {
			evaled = false;
			if(s.indexOf('ajaxerror') != -1) {
				evalscript(s);
				evaled = true;
				if(!cache && duration != 3 && x.div.id) setTimeout('$("append_parent").removeChild($(\'' + x.div.id + '\'))', timeout);
			}
			if(!evaled && (typeof ajaxerror == 'undefined' || !ajaxerror)) {
				if(x.div) x.div.innerHTML = '<div class="' + x.optionclass + '">' + s + '</div>';
				showMenu(ctrlid, x.etype == 'click', offset, x.duration, x.timeout, 0, ctrlid, 400, 1);
				if(func) setTimeout(func + '("' + ctrlid + '")', x.executetime);
			}
			if(!evaled) evalscript(s);
			ajaxerror = null;
			showloading('none');
		});
		doane(e);
	}
	showloading('none');
	doane(e);
}
//得到一个定长的hash值,依赖于 stringxor()
function hash(string, length) {
	var length = length ? length : 32;
	var start = 0;
	var i = 0;
	var result = '';
	filllen = length - string.length % length;
	for(i = 0; i < filllen; i++){
		string += "0";
	}
	while(start < string.length) {
		result = stringxor(result, string.substr(start, length));
		start += length;
	}
	return result;
}

function stringxor(s1, s2) {
	var s = '';
	var hash = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	var max = Math.max(s1.length, s2.length);
	for(var i=0; i<max; i++) {
		var k = s1.charCodeAt(i) ^ s2.charCodeAt(i);
		s += hash.charAt(k % 52);
	}
	return s;
}

function showloading(display, wating) {
	var display = display ? display : 'block';
	var wating = wating ? wating : 'Loading...';
	$('ajaxwaitid').innerHTML = wating;
	$('ajaxwaitid').style.display = display;
}

function ajaxinnerhtml(showid, s) {
	if(showid.tagName != 'TBODY') {
		showid.innerHTML = s;
	} else {
		while(showid.firstChild) {
			showid.firstChild.parentNode.removeChild(showid.firstChild);
		}
		var div1 = document.createElement('DIV');
		div1.id = showid.id+'_div';
		div1.innerHTML = '<table><tbody id="'+showid.id+'_tbody">'+s+'</tbody></table>';
		$('append_parent').appendChild(div1);
		var trs = div1.getElementsByTagName('TR');
		var l = trs.length;
		for(var i=0; i<l; i++) {
			showid.appendChild(trs[0]);
		}
		var inputs = div1.getElementsByTagName('INPUT');
		var l = inputs.length;
		for(var i=0; i<l; i++) {
			showid.appendChild(inputs[0]);
		}
		div1.parentNode.removeChild(div1);
	}
}

function initdat() {

	f = document.forms['setupinfo'].elements;
	x = new Ajax('HTML', 'status');
	var keyarr = Array('dbhost', 'dbuser', 'dbpw' ,'dbname', 'dbtest');
	var datarr = Array(f['dbhost'].value, f['dbuser'].value, f['dbpw'].value, f['dbname'].value, 'true');

	var postdat = createdat(keyarr, datarr);
	x.loading = '<font color=red>正在检测数据......</font>';
	x.post(siteUrl+'/install/index.php', postdat, start);    //uc安装 step = 2

}

function start(s) {

	if(s == 1) {
		alert('数据库连接失败,请检查数据帐号信息');
	} else if(s == 2) {
		alert('无法创建数据库或数据不存在');
	}else if(s == 3){
		var re = confirm('当前数据库当中已经包含数据信息，单击“确定”会覆盖数据库中的数据安装，单击“取消”重新填写');
		if(re) {
			$('setupinfoform').style.display = 'none';
			$('app').style.display = 'block';
			setupuc();
		} else {
			f['dbname'].style.borderColor="#ff0000";
		}
	}else if(s == 0){
		alert('数据库连接成功！开始安装');
		$('setupinfoform').style.display = 'none';
		$('app').style.display = 'block';
		setupuc();
	}
}

function createdat(keyarr, datarr) {
	if(keyarr.length != 0 && datarr != 0){
		var configarr = Array();
		for(var i in keyarr) {
			configarr[i] = keyarr[i]+'='+datarr[i];
		}
		var datstr = configarr.join('&');
		return datstr;
	}
	return false;
}
/* 安装uc */
function setupuc() {

	x = new Ajax('HTML', 'status');
	var keyarr = Array('dbinfo[dbhost]', 'dbinfo[dbname]', 'dbinfo[dbuser]', 'dbinfo[dbpw]', 'dbinfo[tablepre]', 'admininfo[ucfounderpw]', 'admininfo[ucfounderpw2]', 'step','dbinfo[forceinstall]');
	var datarr = Array(f['dbhost'].value, f['dbname'].value, f['dbuser'].value, f['dbpw'].value, 'uc_', f['ucfounderpw'].value, f['ucfounderpw1'].value, '2','true');

	var postdat = createdat(keyarr, datarr);
	x.loading = '正在安装UCenter......';
	x.post(siteUrl+'/ucenter/install/index.php', postdat, ucsetupok);    //uc安装 step = 2

}

function ucsetupok(s) {

	x = new Ajax('HTML', 'status');
	if(s.length != 0) {
		x.loading = '正在完成安装UCenter..';
		x.post(siteUrl+'/ucenter/install/index.php?step=3&view_off=1',null, setuphome);
	} else {
		alert('安装UCenter 失败，请检查数据库信息！');
	}
}

/* 安装UCenter Home */
function setuphome(s) {          //UCenter Home 注册到UC

	x = new Ajax('HTML', 'status');
	$('ucenter').innerHTML = '<h3>UCenter 安装完成</h2>';

	var keyarr = Array('ucapi', 'ucfounderpw', 'ucsubmit', 'formhash');
	var datarr = Array(siteUrl+'/ucenter', f['ucfounderpw'].value, 'true', f['formhash'].value);

	var postdat = createdat(keyarr, datarr);

	x.loading = '正在向UCenter注册UCenter Home......';
	x.post(siteUrl+'/home/install/index.php', postdat, homeucsetupok);

}
function homeucsetupok(s) {

	x = new Ajax('HTML', 'status');
	var regkey = /name=\"uc\[key\]" value=\"(.*?)\"/;
	var regid = /name=\"uc\[appid\]" value=\"(.*?)\"/;

	if(s.length == 0) {
		alert('UCenter Home向UCenter注册失败，请检查网络连接');
		return false;
	}

	var k = s.match(regkey);
	var d = s.match(regid);

	if(k == null || d == null) {
		alert('UC中已经有一个UCenter Home或者请删除home/data/install.lock 请删除后重装');
		location.href='index.php?step=2';
		return false;
	}
	key = k[1];
	id = d[1];

	var keyarr = Array('uc[key]', 'uc[appid]', 'uc[dbhost]', 'uc[dbname]', 'uc[dbuser]', 'uc[dbpw]', 'c[dbcharset]', 'uc[dbtablepre]', 'uc[charset]', 'uc[connect]', 'uc[api]', 'uc[ip]', 'formhash', 'uc2submit');

	var datarr = Array(key, id, f['dbhost'].value, f['dbname'].value, f['dbuser'].value, f['dbpw'].value, f['dbcharset'].value, '`'+f['dbname'].value+'`.uc_',f['dbcharset'].value, 'mysql', siteUrl+'/ucenter','127.0.0.1', f['formhash'].value, 'true');

	var postdat = createdat(keyarr, datarr);
	x.loading = '正在写入API信息......';
	x.post(siteUrl+'/home/install/index.php', postdat, homedbset);

}

function homedbset(s) {

	if(s.length == 0){
		alert('UCenter Home向UCenter注册的api信息写入配置失败');
		return false;
	}

	x = new Ajax('HTML', 'status');
	var dbarr = Array('db[dbhost]', 'db[dbuser]', 'db[dbpw]', 'db[dbcharset]', 'db[dbname]', 'db[tablepre]', 'sqlsubmit', 'formhash');
	var fmarr = Array(f['dbhost'].value, f['dbuser'].value, f['dbpw'].value, f['dbcharset'].value, f['dbname'].value, 'uchome_', 'true', f['formhash'].value);

	var postdat = createdat(dbarr, fmarr);
	x.loading = '正在配置UCenter Home数据库信息......';
	x.post(siteUrl+'/home/install/index.php', postdat, homestep3);

}

function homestep3(s) {

	if(s.length == 0){
		alert('UCenter Home数据库配置失败');
		return false;
	}

	x = new Ajax('HTML', 'status');
	x.loading = '正在建立UCenter Home数据表信息......';
	x.post(siteUrl+'/home/install/index.php?step=3', null, homestep4);
}

function homestep4(s) {

	x = new Ajax('HTML', 'status');
	x.loading = '正在往UCenter Home数据表写入初始信息......';
	x.post(siteUrl+'/home/install/index.php?step=4', null, homestep5);
}
function homestep5(s) {
	x = new Ajax('HTML', 'status');
	x.post(siteUrl+'/home/install/index.php?step=5', null, addadmin);
}
function addadmin(s) {

	x = new Ajax('HTML', 'status');
	var adminarr = Array('username', 'password', 'opensubmit', 'formhash');
	var datarr = Array(f['username'].value, f['userpw'].value, 'true', f['formhash'].value);

	var postdat = createdat(adminarr, datarr);
	x.loading = '正在添加UCenter Home管理员......';
	x.post(siteUrl+'/home/install/index.php', postdat, ucaddbbs);

}

/***安装 Discuz! **/
function ucaddbbs(s) {

	$('home').innerHTML = '<h3>UCenter Home 安装完成</h2>';
	x = new Ajax('HTML', 'status');

	var keyarr = Array('ucenter[ucurl]', 'ucenter[ucip]', 'ucenter[ucpw]', 'siteinfo[sitename]', 'siteinfo[siteurl]', 'step', 'submitname');
	var datarr = Array(siteUrl+'/ucenter', '', f['ucfounderpw'].value, 'Discuz!', siteUrl+'/bbs', '2', 'true');
	var postdat = createdat(keyarr, datarr);
	x.loading = '正在向UCenter注册Discuz!......';
	x.post(siteUrl+'/bbs/install/index.php', postdat, bbsdbset);

}

function bbsdbset(s) {

	x = new Ajax('HTML', 'status');

	var keyarr = Array('dbinfo[dbhost]', 'dbinfo[dbname]', 'dbinfo[dbuser]', 'dbinfo[dbpw]', 'dbinfo[tablepre]', 'dbinfo[adminemail]', 'admininfo[username]', 'admininfo[email]', 'admininfo[password]', 'admininfo[password2]', 'submitname','dbinfo[forceinstall]', 'step');
	var datarr = Array(f['dbhost'].value, f['dbname'].value, f['dbuser'].value, f['dbpw'].value, 'cdb_', f['adminemail'].value, f['username'].value,f['adminemail'].value, f['userpw'].value, f['userpw1'].value, 'true', 'true', '3' );

	var postdat = createdat(keyarr, datarr);
	x.loading = '正在配置Discuz!数据信息......';
	x.post(siteUrl+'/bbs/install/index.php', postdat, allok);
}

/**安装善后**/

function allok(s) {
	if(s.length == 0){
		alert('Discuz!数据库配置失败');
		return false;
	}
	$('bbs').innerHTML = '<h3>Discuz!安装完成</h2><iframe src="../bbs" width="0" height="0" ></iframe>';
	$('indexselect').innerHTML += '<input type="hidden" name="ucf" value="'+f['ucfounderpw'].value+'" /><input type="hidden" name="admin" value="admin" /><input type="hidden" name="pass" value="'+f['userpw'].value+'" />';
	$('indexselect').style.display="block";
	alert('安装全部完成');
}

function setCopy(s){
    if(navigator.userAgent.toLowerCase().indexOf('ie') > -1) {
        clipboardData.setData('Text',s);
        alert ("安装信息已经复制到您的剪贴板中\n您可以使用Ctrl+V快捷键粘贴到需要的地方");
    } else {
		$('info').select();
    }
}
