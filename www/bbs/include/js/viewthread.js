/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: viewthread.js 21279 2009-11-24 09:59:28Z monkey $
*/

var replyreload = '';

function attachimgshow(pid) {
	aimgs = aimgcount[pid];
	aimgcomplete = 0;
	loadingcount = 0;
	for(i = 0;i < aimgs.length;i++) {
		obj = $('aimg_' + aimgs[i]);
		if(!obj) {
			aimgcomplete++;
			continue;
		}
		if(!obj.status) {
			obj.status = 1;
			obj.src = obj.getAttribute('file');
			loadingcount++;
		} else if(obj.status == 1) {
			if(obj.complete) {
				obj.status = 2;
			} else {
				loadingcount++;
			}
		} else if(obj.status == 2) {
			aimgcomplete++;
			if(obj.getAttribute('thumbImg')) {
				thumbImg(obj);
			}
		}
		if(loadingcount >= 10) {
			break;
		}
	}
	if(aimgcomplete < aimgs.length) {
		setTimeout("attachimgshow('" + pid + "')", 100);
	}
}

function attachimginfo(obj, infoobj, show, event) {
	objinfo = fetchOffset(obj);
	if(show) {
		$(infoobj).style.left = objinfo['left'] + 'px';
		$(infoobj).style.top = obj.offsetHeight < 40 ? (objinfo['top'] + obj.offsetHeight) + 'px' : objinfo['top'] + 'px';
		$(infoobj).style.display = '';
	} else {
		if(BROWSER.ie) {
			$(infoobj).style.display = 'none';
			return;
		} else {
			var mousex = document.body.scrollLeft + event.clientX;
			var mousey = document.documentElement.scrollTop + event.clientY;
			if(mousex < objinfo['left'] || mousex > objinfo['left'] + objinfo['width'] || mousey < objinfo['top'] || mousey > objinfo['top'] + objinfo['height']) {
				$(infoobj).style.display = 'none';
			}
		}
	}
}

function copycode(obj) {
	setCopy(BROWSER.ie ? obj.innerText.replace(/\r\n\r\n/g, '\r\n') : obj.textContent, '代码已复制到剪贴板');
}

function signature(obj) {
	if(obj.style.maxHeightIE != '') {
		var height = (obj.scrollHeight > parseInt(obj.style.maxHeightIE)) ? obj.style.maxHeightIE : obj.scrollHeight + 'px';
		if(obj.innerHTML.indexOf('<IMG ') == -1) {
			obj.style.maxHeightIE = '';
		}
		return height;
	}
}

function tagshow(event) {
	var obj = BROWSER.ie ? event.srcElement : event.target;
	ajaxmenu(obj, 0, 1, 2);
}

var zoomclick = 0, zoomstatus = 1;
function zoom(obj, zimg) {
	if(!zoomstatus) {
		window.open(zimg, '', '');
		return;
	}
	if(!obj.id) obj.id = 'img_' + Math.random();
	var menuid = obj.id + '_zmenu';
	var menu = $(menuid);
	var imgid = menuid + '_img';
	var zoomid = menuid + '_zimg';
	var maxh = (document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight) - 70;
	zimg = zimg ? zimg : obj.src;

	if(!menu) {
		menu = document.createElement('div');
		menu.id = menuid;
		var objpos = fetchOffset(obj);
		menu.innerHTML = '<div onclick="$(\'append_parent\').removeChild($(\'' + obj.id + '_zmenu\'))" style="filter:alpha(opacity=50);opacity:0.5;background:#FFF;position:absolute;width:' + obj.clientWidth + 'px;height:' + obj.clientHeight + 'px;left:' + objpos['left'] + 'px;top:' + objpos['top'] + 'px"><table width="100%" height="100%"><tr><td valign="middle" align="center"><img src="' + IMGDIR + '/loading.gif" /></td></tr></table></div>' +
			'<div style="position:absolute;top:-100000px;display:none"><img id="' + imgid + '" src="' + zimg + '"></div>';
		$('append_parent').appendChild(menu);
		$(imgid).onload = function() {
			$(imgid).parentNode.style.display = '';
			var imgw = $(imgid).width;
			var imgh = $(imgid).height;
			var r = imgw / imgh;
			var w = document.body.clientWidth * 0.95;
			w = imgw > w ? w : imgw;
			var h = w / r;
			if(h > maxh) {
				h = maxh;
				w = h * r;
			}
			$('append_parent').removeChild(menu);
			menu = document.createElement('div');
			menu.id = menuid;
			menu.style.overflow = 'visible';
			menu.style.width = (w < 300 ? 300 : w) + 20 + 'px';
			menu.style.height = h + 50 + 'px';
			menu.innerHTML = '<div class="zoominner"><p id="' + menuid + '_ctrl"><span class="right"><a href="' + zimg + '" class="imglink" target="_blank" title="在新窗口打开">在新窗口打开</a><a href="javascipt:;" id="' + menuid + '_adjust" class="imgadjust" title="实际大小">实际大小</a><a href="javascript:;" onclick="hideMenu()" class="imgclose" title="关闭">关闭</a></span>鼠标滚轮缩放图片</p><div align="center" onmousedown="zoomclick=1" onmousemove="zoomclick=2" onmouseup="if(zoomclick==1) hideMenu()"><img id="' + zoomid + '" src="' + zimg + '" width="' + w + '" height="' + h + '" w="' + imgw + '" h="' + imgh + '"></div></div>';
			$('append_parent').appendChild(menu);
			$(menuid + '_adjust').onclick = function(e) {adjust(e, 1)};
			if(BROWSER.ie){
				menu.onmousewheel = adjust;
			} else {
				menu.addEventListener('DOMMouseScroll', adjust, false);
			}
			showMenu({'menuid':menuid,'duration':3,'pos':'00','cover':1,'drag':menuid,'maxh':maxh+70});
		};
	} else {
		showMenu({'menuid':menuid,'duration':3,'pos':'00','cover':1,'drag':menuid,'maxh':menu.clientHeight});
	}
	if(BROWSER.ie) doane(event);
	var adjust = function(e, a) {
		var imgw = $(zoomid).getAttribute('w');
		var imgh = $(zoomid).getAttribute('h');
		var imgwstep = imgw / 10;
		var imghstep = imgh / 10;
		if(!a) {
			if(!e) e = window.event;
			if(e.altKey || e.shiftKey || e.ctrlKey) return;
			if(e.wheelDelta <= 0 || e.detail > 0) {
				if($(zoomid).width - imgwstep <= 200 || $(zoomid).height - imghstep <= 200) {
					doane(e);return;
				}
				$(zoomid).width -= imgwstep;
				$(zoomid).height -= imghstep;
			} else {
				if($(zoomid).width + imgwstep >= imgw) {
					doane(e);return;
				}
				$(zoomid).width += imgwstep;
				$(zoomid).height += imghstep;
			}
		} else {
			$(zoomid).width = imgw;
			$(zoomid).height = imgh;
		}
		menu.style.width = (parseInt($(zoomid).width < 300 ? 300 : parseInt($(zoomid).width)) + 20) + 'px';
		menu.style.height = (parseInt($(zoomid).height) + 50) + 'px';
		setMenuPosition('', menuid, '00');
		doane(e);
	};
}

function parsetag(pid) {
	if(!$('postmessage_'+pid) || $('postmessage_'+pid).innerHTML.match(/<script[^\>]*?>/i)) {
		return;
	}
	var havetag = false;
	var tagfindarray = new Array();
	var str = $('postmessage_'+pid).innerHTML.replace(/(^|>)([^<]+)(?=<|$)/ig, function($1, $2, $3, $4) {
		for(i in tagarray) {
			if(tagarray[i] && $3.indexOf(tagarray[i]) != -1) {
				havetag = true;
				$3 = $3.replace(tagarray[i], '<h_ ' + i + '>');
				tmp = $3.replace(/&[a-z]*?<h_ \d+>[a-z]*?;/ig, '');
				if(tmp != $3) {
					$3 = tmp;
				} else {
					tagfindarray[i] = tagarray[i];
					tagarray[i] = '';
				}
			}
		}
		return $2 + $3;
    	});
    	if(havetag) {
		$('postmessage_'+pid).innerHTML = str.replace(/<h_ (\d+)>/ig, function($1, $2) {
			return '<span href=\"tag.php?name=' + tagencarray[$2] + '\" onclick=\"tagshow(event)\" class=\"t_tag\">' + tagfindarray[$2] + '</span>';
	    	});
	}
}

function setanswer(pid){
	if(confirm('您确认要把该回复选为“最佳答案”吗？')){
		if(BROWSER.ie) {
			doane(event);
		}
		$('modactions').action='misc.php?action=bestanswer&tid=' + tid + '&pid=' + pid + '&bestanswersubmit=yes';
		$('modactions').submit();
	}
}

var authort;
function showauthor(ctrlObj, menuid) {
	authort = setTimeout(function () {
		showMenu({'menuid':menuid});
		if($(menuid + '_ma').innerHTML == '') $(menuid + '_ma').innerHTML = ctrlObj.innerHTML;
	}, 500);
	if(!ctrlObj.onmouseout) {
		ctrlObj.onmouseout = function() {
			clearTimeout(authort);
		}
	}
}

function fastpostvalidate(theform) {
	s = '';
	if(theform.message.value == '' && theform.subject.value == '') {
		s = '请完成标题或内容栏。';
		theform.message.focus();
	} else if(mb_strlen(theform.subject.value) > 80) {
		s = '您的标题超过 80 个字符的限制。';
		theform.subject.focus();
	}
	if(!disablepostctrl && ((postminchars != 0 && mb_strlen(theform.message.value) < postminchars) || (postmaxchars != 0 && mb_strlen(theform.message.value) > postmaxchars))) {
		s = '您的帖子长度不符合要求。\n\n当前长度: ' + mb_strlen(theform.message.value) + ' ' + '字节\n系统限制: ' + postminchars + ' 到 ' + postmaxchars + ' 字节';
	}
	if(s) {
		$('fastpostreturn').className = 'onerror';
		$('fastpostreturn').innerHTML = s;
		$('fastpostsubmit').disabled = false;
		return false;
	}
	$('fastpostsubmit').disabled = true;
	theform.message.value = parseurl(theform.message.value);
	ajaxpost('fastpostform', 'fastpostreturn', 'fastpostreturn', 'onerror', $('fastpostsubmit'));
	return false;
}

function fastpostappendreply() {
	setcookie('discuz_fastpostrefresh', $('fastpostrefresh').checked ? 1 : 0, 2592000);
	if($('fastpostrefresh').checked) {
		location.href = 'redirect.php?tid='+tid+'&goto=lastpost&from=fastpost&random=' + Math.random() + '#lastpost';
		return;
	}
	newpos = fetchOffset($('post_new'));
	document.documentElement.scrollTop = newpos['top'];
	$('post_new').style.display = '';
	$('post_new').id = '';
	div = document.createElement('div');
	div.id = 'post_new';
	div.style.display = 'none';
	div.className = 'viewthread_table';
	$('postlistreply').appendChild(div);
	$('fastpostsubmit').disabled = false;
	$('fastpostmessage').value = '';
	if($('secanswer3')) {
		$('checksecanswer3').innerHTML = '<img src="images/common/none.gif" width="17" height="17">';
		$('secanswer3').value = '';
		secclick3['secanswer3'] = 0;
	}
	if($('seccodeverify3')) {
		$('checkseccodeverify3').innerHTML = '<img src="images/common/none.gif" width="17" height="17">';
		$('seccodeverify3').value = '';
		secclick3['seccodeverify3'] = 0;
	}
	showCreditPrompt();
}

function submithandle_fastpost(locationhref) {
	var pid = locationhref.lastIndexOf('#pid');
	if(pid != -1) {
		pid = locationhref.substr(pid + 4);
		ajaxget('viewthread.php?tid=' + tid + '&viewpid=' + pid, 'post_new', 'ajaxwaitid', '', null, 'fastpostappendreply()');
		if(replyreload) {
			var reloadpids = replyreload.split(',');
			for(i = 1;i < reloadpids.length;i++) {
				ajaxget('viewthread.php?tid=' + tid + '&viewpid=' + reloadpids[i], 'post_' + reloadpids[i]);
			}
		}
		$('fastpostreturn').className = '';
	} else {
		$('post_new').style.display = $('fastpostmessage').value = $('fastpostreturn').className = '';
		$('fastpostreturn').innerHTML = '本版回帖需要审核，您的帖子将在通过审核后显示';
	}
}

function messagehandle_fastpost() {
	$('fastpostsubmit').disabled = false;
}

function recommendupdate(n) {
	if(getcookie('discuz_recommend')) {
		var objv = n > 0 ? $('recommendv_add') : $('recommendv_subtract');
		objv.innerHTML = parseInt(objv.innerHTML) + 1;
		$('recommendv').innerHTML = parseInt($('recommendv').innerHTML) + n;
		setcookie('discuz_recommend', '', -2592000);
	}
}

function switchrecommendv() {
	display('recommendv');
	display('recommendav');
}

function appendreply() {
	newpos = fetchOffset($('post_new'));
	document.documentElement.scrollTop = newpos['top'];
	$('post_new').style.display = '';
	$('post_new').id = '';
	div = document.createElement('div');
	div.id = 'post_new';
	div.style.display = 'none';
	div.className = '';
	$('postlistreply').appendChild(div);
	$('postform').replysubmit.disabled = false;
	showCreditPrompt();
}

function creditconfirm(v) {
	return confirm('下载需要消耗' + v + '，您是否要下载？');
}