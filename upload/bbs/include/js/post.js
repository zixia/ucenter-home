/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: post.js 21297 2009-11-25 09:40:15Z monkey $
*/

var postSubmited = false;
var AID = 1;
var UPLOADSTATUS = -1;
var UPLOADFAILED = UPLOADCOMPLETE = AUTOPOST =  0;
var CURRENTATTACH = '0';
var FAILEDATTACHS = '';
var UPLOADWINRECALL = null;
var STATUSMSG = {'-1' : '内部服务器错误', '0' : '上传成功', '1' : '不支持此类扩展名', '2' : '附件大小为 0', '3' : '附件大小超限', '4' : '不支持此类扩展名', '5' : '附件大小超限', '6' : '附件总大小超限', '7' : '图片附件不合法', '8' : '附件文件无法保存', '9' : '没有合法的文件被上传', '10' : '非法操作'};

function checkFocus() {
	var obj = wysiwyg ? editwin : textobj;
	if(!obj.hasfocus) {
		obj.focus();
	}
}

function ctlent(event) {
	if(postSubmited == false && (event.ctrlKey && event.keyCode == 13) || (event.altKey && event.keyCode == 83) && $('postsubmit')) {
		if(in_array($('postsubmit').name, ['topicsubmit', 'replysubmit', 'editsubmit']) && !validate($('postform'))) {
			doane(event);
			return;
		}
		postSubmited = true;
		$('postsubmit').disabled = true;
		$('postform').submit();
	}
	if(event.keyCode == 9) {
		doane(event);
	}
}

function checklength(theform) {
	var message = wysiwyg ? html2bbcode(getEditorContents()) : (!theform.parseurloff.checked ? parseurl(theform.message.value) : theform.message.value);
	showDialog('当前长度: ' + mb_strlen(message) + ' 字节，' + (postmaxchars != 0 ? '系统限制: ' + postminchars + ' 到 ' + postmaxchars + ' 字节。' : ''), 'notice', '字数检查');
}

if(!tradepost) {
	var tradepost = 0;
}

function validate(theform) {
	var message = trim(wysiwyg ? html2bbcode(getEditorContents()) : (!theform.parseurloff.checked ? parseurl(theform.message.value) : theform.message.value));
	if(($('postsubmit').name != 'replysubmit' && !($('postsubmit').name == 'editsubmit' && !isfirstpost) && theform.subject.value == "") || !sortid && !special && message == "") {
		showDialog('请完成标题或内容栏。');
		return false;
	} else if(mb_strlen(theform.subject.value) > 80) {
		showDialog('您的标题超过 80 个字符的限制。');
		return false;
	}
	if(tradepost) {
		if(theform.item_name.value == '') {
			showDialog('对不起，请输入商品名称。');
			return false;
		} else if(theform.item_price.value == '') {
			showDialog('对不起，请输入商品现价。');
			return false;
		} else if(!parseInt(theform.item_price.value)) {
			showDialog('对不起，商品现价必须为有效数字。');
			return false;
		} else if(theform.item_costprice.value != '' && !parseInt(theform.item_costprice.value)) {
			showDialog('对不起，商品原价必须为有效数字。');
			return false;
		} else if(theform.item_number.value != '0' && !parseInt(theform.item_number.value)) {
			showDialog('对不起，商品数量必须为数字。');
			theform.item_number.focus();
			return false;
		}
	}
	if(in_array($('postsubmit').name, ['topicsubmit', 'editsubmit'])) {
		if(theform.typeid && (theform.typeid.options && theform.typeid.options[theform.typeid.selectedIndex].value == 0) && typerequired) {
			showDialog('请选择主题对应的分类。');
			return false;
		}
		if(special == 3 && isfirstpost) {
			if(theform.rewardprice.value == "") {
				showDialog('对不起，请输入悬赏积分。');
				return false;
			}
		} else if(special == 4 && isfirstpost) {
			if(theform.activityclass.value == "") {
				showDialog('对不起，请输入活动所属类别。');
				return false;
			} else if($('starttimefrom_0').value == "" && $('starttimefrom_1').value == "") {
				showDialog('对不起，请输入活动开始时间。');
				return false;
			} else if(theform.activityplace.value == "") {
				showDialog('对不起，请输入活动地点。');
				return false;
			}
		}
	}
	if(isfirstpost && sortid && typeof checkallsort == 'function') {
		if(!checkallsort()) return false;
	}

	if(!disablepostctrl && !sortid && !special && ((postminchars != 0 && mb_strlen(message) < postminchars) || (postmaxchars != 0 && mb_strlen(message) > postmaxchars))) {
		showDialog('您的帖子长度不符合要求。\n\n当前长度: ' + mb_strlen(message) + ' 字节\n系统限制: ' + postminchars + ' 到 ' + postmaxchars + ' 字节');
		return false;
	}
	if(UPLOADSTATUS == 0) {
		if(!confirm('您有等待上传的附件，确认不上传这些附件吗？')) {
			return false;
		}
	} else if(UPLOADSTATUS == 1) {
		showDialog('您有正在上传的附件，请稍候，上传完成后帖子将会自动发表...', 'notice');
		AUTOPOST = 1;
		return false;
	}
	if($(editorid + '_attachlist')) {
		$('postbox').appendChild($(editorid + '_attachlist'));
		$(editorid + '_attachlist').style.display = 'none';
	}
	if($(editorid + '_imgattachlist')) {
		$('postbox').appendChild($(editorid + '_imgattachlist'));
		$(editorid + '_imgattachlist').style.display = 'none';
	}
	hideMenu();
	theform.message.value = message;
	if($('postsubmit').name == 'editsubmit') {
		return true;
	} else if(in_array($('postsubmit').name, ['topicsubmit', 'replysubmit'])) {
		seccheck(theform, seccodecheck, secqaacheck);
		return false;
	}
}

function seccheck(theform, seccodecheck, secqaacheck) {
	if(seccodecheck || secqaacheck) {
		var url = 'ajax.php?inajax=1&action=';
		if(seccodecheck) {
			var x = new Ajax();
			x.get(url + 'checkseccode&seccodeverify=' + (BROWSER.ie && document.charset == 'utf-8' ? encodeURIComponent($('seccodeverify').value) : $('seccodeverify').value), function(s) {
				if(s.substr(0, 7) != 'succeed') {
					showDialog(s);
					$('seccodeverify').focus();
				} else if(secqaacheck) {
					checksecqaa(url, theform);
				} else {
					postsubmit(theform);
				}
			});
		} else if(secqaacheck) {
			checksecqaa(url, theform);
		}
	} else {
		postsubmit(theform);
	}
}

function checksecqaa(url, theform) {
	var x = new Ajax();
	var secanswer = $('secanswer').value;
	secanswer = BROWSER.ie && document.charset == 'utf-8' ? encodeURIComponent(secanswer) : secanswer;
	x.get(url + 'checksecanswer&secanswer=' + secanswer, function(s) {
		if(s.substr(0, 7) != 'succeed') {
			showDialog(s);
			$('secanswer').focus();
		} else {
			postsubmit(theform);
		}
	});
}

function postsubmit(theform) {
	theform.replysubmit ? theform.replysubmit.disabled = true : (theform.editsubmit ? theform.editsubmit.disabled = true : theform.topicsubmit.disabled = true);
	theform.submit();
}

function loadData(quiet) {
	var data = '';
	if(BROWSER.ie){
		with(document.documentElement) {
            		load('Discuz');
			data = getAttribute("value");
        	}
	} else if(window.sessionStorage){
		data = sessionStorage.getItem('Discuz');
        }

	if(in_array((data = trim(data)), ['', 'null', 'false', null, false])) {
		if(!quiet) {
			showDialog('没有可以恢复的数据！');
		}
		return;
	}

	if(!quiet && !confirm('此操作将覆盖当前帖子内容，确定要恢复数据吗？')) {
		return;
	}

	var data = data.split(/\x09\x09/);
	for(var i = 0; i < $('postform').elements.length; i++) {
		var el = $('postform').elements[i];
		if(el.name != '' && (el.tagName == 'TEXTAREA' || el.tagName == 'INPUT' && (el.type == 'text' || el.type == 'checkbox' || el.type == 'radio'))) {
			for(var j = 0; j < data.length; j++) {
				var ele = data[j].split(/\x09/);
				if(ele[0] == el.name) {
					elvalue = !isUndefined(ele[3]) ? ele[3] : '';
					if(ele[1] == 'INPUT') {
						if(ele[2] == 'text') {
							el.value = elvalue;
						} else if((ele[2] == 'checkbox' || ele[2] == 'radio') && ele[3] == el.value) {
							el.checked = true;
							evalevent(el);
						}
					} else if(ele[1] == 'TEXTAREA') {
						if(ele[0] == 'message') {
							if(!wysiwyg) {
								textobj.value = elvalue;
							} else {
								editdoc.body.innerHTML = bbcode2html(elvalue);
							}
						} else {
							el.value = elvalue;
						}
					}
					break
				}
			}
		}
	}
}

function evalevent(obj) {
	var script = obj.parentNode.innerHTML;
	var re = /onclick="(.+?)["|>]/ig;
	var matches = re.exec(script);
	if(matches != null) {
		matches[1] = matches[1].replace(/this\./ig, 'obj.');
		eval(matches[1]);
	}
}

function setCaretAtEnd() {
	if(wysiwyg) {
		editdoc.body.innerHTML += '';
	} else {
		editdoc.value += '';
	}
}

function relatekw(subject, message, recall) {
	if(isUndefined(recall)) recall = '';
	if(isUndefined(subject) || subject == -1) subject = $('subject').value;
	if(isUndefined(message) || message == -1) message = getEditorContents();
	subject = (BROWSER.ie && document.charset == 'utf-8' ? encodeURIComponent(subject) : subject);
	message = (BROWSER.ie && document.charset == 'utf-8' ? encodeURIComponent(message) : message);
	message = message.replace(/&/ig, '', message).substr(0, 500);
	ajaxget('relatekw.php?subjectenc=' + subject + '&messageenc=' + message, 'tagselect', '', '', '', recall);
}

function switchicon(iconid, obj) {
	$('iconid').value = iconid;
	$('icon_img').src = obj.src;
	hideMenu();
}

var editbox = editwin = editdoc = editcss = null;
var cursor = -1;
var stack = [];
var initialized = false;

function newEditor(mode, initialtext) {
	wysiwyg = parseInt(mode);
	if(!(BROWSER.ie || BROWSER.firefox || (BROWSER.opera >= 9))) {
		allowswitcheditor = wysiwyg = 0;
	}
	if(!BROWSER.ie) {
		$(editorid + '_cmd_paste').parentNode.style.display = 'none';
	}
	if(!allowswitcheditor) {
		$(editorid + '_switcher').style.display = 'none';
	}

	$(editorid + '_cmd_table').disabled = wysiwyg ? false : true;
	$(editorid + '_cmd_table').className = wysiwyg ? '' : 'tblbtn_disabled';

	if(wysiwyg) {
		if($(editorid + '_iframe')) {
			editbox = $(editorid + '_iframe');
		} else {
			var iframe = document.createElement('iframe');
			editbox = textobj.parentNode.appendChild(iframe);
			editbox.id = editorid + '_iframe';
		}

		editwin = editbox.contentWindow;
		editdoc = editwin.document;
		writeEditorContents(isUndefined(initialtext) ?  textobj.value : initialtext);
	} else {
		editbox = editwin = editdoc = textobj;
		if(!isUndefined(initialtext)) {
			writeEditorContents(initialtext);
		}
		addSnapshot(textobj.value);
	}
	setEditorEvents();
	initEditor();
}

function initEditor() {
	var buttons = $(editorid + '_controls').getElementsByTagName('a');
	for(var i = 0; i < buttons.length; i++) {
		if(buttons[i].id.indexOf(editorid + '_cmd_') != -1) {
			buttons[i].href = 'javascript:;';
			buttons[i].onclick = function(e) {discuzcode(this.id.substr(this.id.lastIndexOf('_cmd_') + 5));try{ajaxget('forumstat.php?action='+this.id);} catch(e) {}};
		}
	}
	setUnselectable($(editorid + '_controls'));
	textobj.onkeydown = function(e) {ctlent(e ? e : event)};
}

function setUnselectable(obj) {
	if(BROWSER.ie && BROWSER.ie > 4 && typeof obj.tagName != 'undefined') {
		if(obj.hasChildNodes()) {
			for(var i = 0; i < obj.childNodes.length; i++) {
				setUnselectable(obj.childNodes[i]);
			}
		}
		if(obj.tagName != 'INPUT') {
			obj.unselectable = 'on';
		}
	}
}

function writeEditorContents(text) {
	if(wysiwyg) {
		if(text == '' && (BROWSER.firefox || BROWSER.opera)) {
			text = '<br />';
		}
		if(initialized && !(BROWSER.firefox && BROWSER.firefox >= 3 || BROWSER.opera)) {
			editdoc.body.innerHTML = text;
		} else {
			editdoc.designMode = 'on';
			editdoc = editwin.document;
			editdoc.open('text/html', 'replace');
			editdoc.write(text);
			editdoc.close();
			editdoc.body.contentEditable = true;
			initialized = true;
		}
	} else {
		textobj.value = text;
	}

	setEditorStyle();

}

function getEditorContents() {
	return wysiwyg ? editdoc.body.innerHTML : editdoc.value;
}

function setEditorStyle() {
	if(wysiwyg) {
		textobj.style.display = 'none';
		editbox.style.display = '';
		editbox.className = textobj.className;

		var headNode = editdoc.getElementsByTagName("head")[0];
		if(!headNode) {
			headNode = editdoc.getElementsByTagName("body")[0];
		}
		if(!headNode.getElementsByTagName('link').length) {
			editcss = editdoc.createElement('link');
			editcss.type = 'text/css';
			editcss.rel = 'stylesheet';
			editcss.href = editorcss;
			headNode.appendChild(editcss);
		}

		if(BROWSER.firefox || BROWSER.opera) {
			editbox.style.border = '0px';
		} else if(BROWSER.ie) {
			editdoc.body.style.border = '0px';
			editdoc.body.addBehavior('#default#userData');
		}
		editbox.style.width = textobj.style.width;
		editbox.style.height = textobj.style.height;
		editdoc.firstChild.style.background = 'none';
		editdoc.body.style.backgroundColor = TABLEBG;
		editdoc.body.style.textAlign = 'left';
		if(BROWSER.ie) {
			try{$('subject').focus();} catch(e) {editwin.focus();}
		}
	} else {
		var iframe = textobj.parentNode.getElementsByTagName('iframe')[0];
		if(iframe) {
			textobj.style.display = '';
			textobj.style.width = iframe.style.width;
			textobj.style.height = iframe.style.height;
			iframe.style.display = 'none';
		}
		if(BROWSER.ie) {
			try{$('subject').focus();} catch(e) {textobj.focus();}
		}
	}
}

function setEditorEvents() {
	var floatPic = function(e) {
		var obj = BROWSER.ie ? e.srcElement : e.target;
		var tag = obj.tagName.toLowerCase();
		var menuid = obj.id + '_menu';
		var menu = $(menuid);

		if(JSMENU['float']) $(JSMENU['float']).style.display = 'none';
		if(tag != 'img') return;
		if(!obj.id) obj.id = editorid + '_f_' + tag + '_' + Math.random();

		if(!menu) {
			menu = document.createElement('div');
			menu.id = menuid;
			menu.style.position = 'absolute';
			menu.style.zIndex = '999';
			menu.className = 'popupmenu_popup popupfix simple_menu';
			menu.style.width = '80px';
			menu.innerHTML = '<div class="popupmenu_option" unselectable="on"><ul unselectable="on"><li id="' + menuid + '_left" unselectable="on">图片居左混排</li><li id="' + menuid + '_right" unselectable="on">图片居右混排</li></ul></div>';
			$('append_parent').appendChild(menu);
			$(menuid + '_left').onclick = function(e) {discuzcode('floatleft', obj.id);menu.style.display='none';doane(e)};
			$(menuid + '_right').onclick = function(e) {discuzcode('floatright', obj.id);menu.style.display='none';doane(e)};
		}
		var pos = fetchOffset($(editorid + '_iframe'));
		menu.style.left = pos['left'] + e.clientX + 'px';
		menu.style.top = pos['top'] + e.clientY + 'px';
		menu.style.display = '';
		JSMENU['float'] = menuid;
		doane(e);
	};
	if(wysiwyg) {
		if(BROWSER.firefox || BROWSER.opera) {
			editwin.addEventListener('keydown', function(e) {ctlent(e);}, true);
			editwin.addEventListener('mouseup', floatPic, true);
		} else if(editdoc.attachEvent) {
			editdoc.body.attachEvent('onkeydown', ctlent);
			editdoc.body.attachEvent('onmouseup', floatPic);
		}
	}
	editwin.onfocus = function(e) {this.hasfocus = true;};
	editwin.onblur = function(e) {this.hasfocus = false;};
}

function wrapTags(tagname, useoption, selection) {
	if(isUndefined(selection)) {
		var selection = getSel();
		if(selection === false) {
			selection = '';
		} else {
			selection += '';
		}
	}

	if(useoption !== false) {
		var opentag = '[' + tagname + '=' + useoption + ']';
	} else {
		var opentag = '[' + tagname + ']';
	}

	var closetag = '[/' + tagname + ']';
	var text = opentag + selection + closetag;

	insertText(text, strlen(opentag), strlen(closetag), in_array(tagname, ['code', 'quote', 'free', 'hide']) ? true : false);
}

function applyFormat(cmd, dialog, argument) {
	if(wysiwyg) {
		editdoc.execCommand(cmd, (isUndefined(dialog) ? false : dialog), (isUndefined(argument) ? true : argument));
		return;
	}
	switch(cmd) {
		case 'paste':
			if(BROWSER.ie) {
				var str = clipboardData.getData("TEXT");
				insertText(str, str.length, 0);
			}
			break;
		case 'bold':
		case 'italic':
		case 'underline':
		case 'strikethrough':
			wrapTags(cmd.substr(0, 1), false);
			break;
		case 'inserthorizontalrule':
			insertText('[hr]', 4, 0);
			break;
		case 'justifyleft':
		case 'justifycenter':
		case 'justifyright':
			wrapTags('align', cmd.substr(7));
			break;
		case 'fontname':
			wrapTags('font', argument);
			break;
		case 'fontsize':
			wrapTags('size', argument);
			break;
		case 'forecolor':
			wrapTags('color', argument);
			break;
	}
}

function getCaret() {
	if(wysiwyg) {
		var obj = editdoc.body;
		var s = document.selection.createRange();
		s.setEndPoint('StartToStart', obj.createTextRange());
		var matches1 = s.htmlText.match(/<\/p>/ig);
		var matches2 = s.htmlText.match(/<br[^\>]*>/ig);
		var fix = (matches1 ? matches1.length - 1 : 0) + (matches2 ? matches2.length : 0);
		var pos = s.text.replace(/\r?\n/g, ' ').length;
		if(matches3 = s.htmlText.match(/<img[^\>]*>/ig)) pos += matches3.length;
		if(matches4 = s.htmlText.match(/<\/tr|table>/ig)) pos += matches4.length;
		return [pos, fix];
	} else {
		var sel = getSel();
		return [sel === false ? 0 : sel.length, 0];
	}
}

function setCaret(pos) {
	var obj = wysiwyg ? editdoc.body : editbox;
	var r = obj.createTextRange();
	r.moveStart('character', pos);
	r.collapse(true);
	r.select();
}

function isEmail(email) {
	return email.length > 6 && /^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/.test(email);
}

function insertAttachTag(aid) {
	var txt = '[attach]' + aid + '[/attach]';
	if(wysiwyg) {
		insertText(txt, false);
	} else {
		insertText(txt, strlen(txt), 0);
	}
}

function insertAttachimgTag(aid) {
	if(wysiwyg) {
		insertText('<img src="' + $('image_' + aid).src + '" border="0" aid="attachimg_' + aid + '" width="' + $('image_' + aid).width + '" alt="" />', false);
	} else {
		var txt = '[attachimg]' + aid + '[/attachimg]';
		insertText(txt, strlen(txt), 0);
	}
}

function insertSmiley(smilieid) {
	checkFocus();
	var src = $('smilie_' + smilieid).src;
	var code = $('smilie_' + smilieid).alt;
	if(wysiwyg && allowsmilies && (!$('smileyoff') || $('smileyoff').checked == false)) {
		if(BROWSER.firefox) {
			applyFormat('InsertImage', false, src);
			var smilies = editdoc.body.getElementsByTagName('img');
			for(var i = 0; i < smilies.length; i++) {
				if(smilies[i].src == src && smilies[i].getAttribute('smilieid') < 1) {
					smilies[i].setAttribute('smilieid', smilieid);
					smilies[i].setAttribute('border', "0");
				}
			}
		} else {
			insertText('<img src="' + src + '" border="0" smilieid="' + smilieid + '" alt="" /> ', false);
		}
	} else {
		code += ' ';
		insertText(code, strlen(code), 0);
	}
	hideMenu();
}

function discuzcode(cmd, arg) {
	if(cmd != 'redo') {
		addSnapshot(getEditorContents());
	}

	checkFocus();

	if(in_array(cmd, ['simple', 'paragraph', 'list', 'smilies', 'createlink', 'quote', 'code', 'free', 'hide', 'audio', 'video', 'flash', 'attach', 'image']) || cmd == 'table' && wysiwyg || in_array(cmd, ['fontname', 'fontsize', 'forecolor']) && !arg) {
		showEditorMenu(cmd);
		return;
	} else if(cmd.substr(0, 6) == 'custom') {
		showEditorMenu(cmd.substr(8), cmd.substr(6, 1));
		return;
	} else if(wysiwyg && cmd == 'inserthorizontalrule') {
		insertText('<hr class="solidline" />', 24);
	} else if(cmd == 'autotypeset') {
		autoTypeset();
		return;
	} else if(!wysiwyg && cmd == 'removeformat') {
		var simplestrip = new Array('b', 'i', 'u');
		var complexstrip = new Array('font', 'color', 'size');

		var str = getSel();
		if(str === false) {
			return;
		}
		for(var tag in simplestrip) {
			str = stripSimple(simplestrip[tag], str);
		}
		for(var tag in complexstrip) {
			str = stripComplex(complexstrip[tag], str);
		}
		insertText(str);
	} else if(!wysiwyg && cmd == 'undo') {
		addSnapshot(getEditorContents());
		moveCursor(-1);
		if((str = getSnapshot()) !== false) {
			editdoc.value = str;
		}
	} else if(!wysiwyg && cmd == 'redo') {
		moveCursor(1);
		if((str = getSnapshot()) !== false) {
			editdoc.value = str;
		}
	} else if(!wysiwyg && in_array(cmd, ['insertorderedlist', 'insertunorderedlist'])) {
		var listtype = cmd == 'insertorderedlist' ? '1' : '';
		var opentag = '[list' + (listtype ? ('=' + listtype) : '') + ']\n';
		var closetag = '[/list]';

		if(txt = getSel()) {
			var regex = new RegExp('([\r\n]+|^[\r\n]*)(?!\\[\\*\\]|\\[\\/?list)(?=[^\r\n])', 'gi');
			txt = opentag + trim(txt).replace(regex, '$1[*]') + '\n' + closetag;
			insertText(txt, strlen(txt), 0);
		} else {
			insertText(opentag + closetag, opentag.length, closetag.length);

			while(listvalue = prompt('输入一个列表项目.\r\n留空或者点击取消完成此列表.', '')) {
				if(BROWSER.opera > 8) {
					listvalue = '\n' + '[*]' + listvalue;
					insertText(listvalue, strlen(listvalue) + 1, 0);
				} else {
					listvalue = '[*]' + listvalue + '\n';
					insertText(listvalue, strlen(listvalue), 0);
				}
			}
		}
	} else if(!wysiwyg && cmd == 'unlink') {
		var sel = getSel();
		sel = stripSimple('url', sel);
		sel = stripComplex('url', sel);
		insertText(sel);
	} else if(cmd == 'floatleft' || cmd == 'floatright') {
		if(wysiwyg) {
			if(selection = getSel()) {
				var span = editdoc.getElementById(arg).parentNode;
				if(span.tagName == 'SPAN') {
					if(typeof span.style.styleFloat != 'undefined') span.style.styleFloat = cmd.substr(5);
					else span.style.cssFloat = cmd.substr(5);
					return;
				} else {
					var ret = insertText('<br style="clear: both"><span style="float: ' + cmd.substr(5) + '">' + selection + '</span>', true);
				}
			}
		}
	} else if(cmd == 'loaddata') {
		loadData();
	} else if(cmd == 'savedata') {
		saveData();
	} else if(cmd == 'checklength') {
		checklength($('postform'));
	} else if(cmd == 'clearcontent') {
		clearContent();
	} else {
		try {
			var ret = applyFormat(cmd, false, (isUndefined(arg) ? true : arg));
		} catch(e) {
			var ret = false;
		}
	}

	if(cmd != 'undo') {
		addSnapshot(getEditorContents());
	}
	if(in_array(cmd, ['bold', 'italic', 'underline', 'strikethrough', 'inserthorizontalrule', 'fontname', 'fontsize', 'forecolor', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist', 'insertunorderedlist', 'floatleft', 'floatright', 'removeformat', 'unlink', 'undo', 'redo'])) {
		hideMenu();
	}
	return ret;
}

function showEditorMenu(tag, params) {
	var sel, selection;
	var str = '';
	var ctrlid = editorid + (params ? '_cmd_custom' + params + '_' : '_cmd_') + tag;
	var opentag = '[' + tag + ']';
	var closetag = '[/' + tag + ']';
	var menu = $(ctrlid + '_menu');
	var pos = [0, 0];

	if(BROWSER.ie) {
		sel = wysiwyg ? editdoc.selection.createRange() : document.selection.createRange();
		pos = getCaret();
	}
	selection = sel ? (wysiwyg ? sel.htmlText : sel.text) : getSel();

	if(menu) {
		if(menu.style.display == '') {
			hideMenu(ctrlid + '_menu', 'menu');
			return;
		}
		showMenu({'ctrlid':ctrlid,'evt':'click','duration':in_array(tag, ['simple', 'fontname', 'fontsize', 'paragraph', 'list', 'smilies']) ? 2 : 3,'drag':in_array(tag, ['attach', 'image']) ? ctrlid + '_ctrl' : 1});
	} else {
		switch(tag) {
			case 'createlink':
				str = '请输入链接的地址:<br /><input type="text" id="' + ctrlid + '_param_1" style="width: 98%" value="" class="txt" />'+
					(selection ? '' : '<br />请输入链接的文字:<br /><input type="text" id="' + ctrlid + '_param_2" style="width: 98%" value="" class="txt" />');
				break;
			case 'forecolor':
				showColorBox(ctrlid, 1);
				return;
			case 'code':
			case 'quote':
			case 'hide':
			case 'free':
				if(selection) {
					return insertText((opentag + selection + closetag), strlen(opentag), strlen(closetag), true, sel);
				}
				var lang = {'quote' : '请输入要插入的引用', 'code' : '请输入要插入的代码', 'hide' : '请输入要插入的隐藏内容', 'free' : '请输入要插入的免费信息'};
				str += lang[tag] + ':<br /><textarea id="' + ctrlid + '_param_1" style="width: 98%" cols="50" rows="5" class="txtarea"></textarea>' +
					(tag == 'hide' ? '<br /><input type="radio" name="' + ctrlid + '_radio" id="' + ctrlid + '_radio_1" class="txt" checked="checked" />只有当浏览者回复本帖时才显示<br /><input type="radio" name="' + ctrlid + '_radio" id="' + ctrlid + '_radio_2" class="txt" />只有当浏览者积分高于 <input type="text" size="3" id="' + ctrlid + '_param_2" class="txt" /> 时才显示' : '');
				break;
			case 'table':
				str = '表格行数: <input type="text" id="' + ctrlid + '_param_1" size="2" value="2" class="txt" /> &nbsp; 表格列数: <input type="text" id="' + ctrlid + '_param_2" size="2" value="2" class="txt" /><br />表格宽度: <input type="text" id="' + ctrlid + '_param_3" size="2" value="" class="txt" /> &nbsp; 背景颜色: <input type="text" id="' + ctrlid + '_param_4" size="2" class="txt" onclick="showColorBox(this.id, 2)" />';
				break;
			case 'audio':
				str = '请输入音乐文件地址:<br /><input type="text" id="' + ctrlid + '_param_1" class="txt" value="" style="width: 245px;" />';
				break;
			case 'video':
				str = '请输入视频地址:<br /><input type="text" value="" id="' + ctrlid + '_param_1" style="width: 245px;" class="txt" /><br />宽: <input id="' + ctrlid + '_param_2" size="5" value="400" class="txt" /> &nbsp; 高: <input id="' + ctrlid + '_param_3" size="5" value="300" class="txt" />';
				break;
			case 'flash':
				str = '请输入 Flash 文件地址:<br /><input type="text" id="' + ctrlid + '_param_1" class="txt" value="" style="width: 245px;" />';
				break;
			default:
				var haveSel = selection == null || selection == false || in_array(trim(selection), ['', 'null', 'undefined', 'false']) ? 0 : 1;
				if(params == 1 && haveSel) {
					return insertText((opentag + selection + closetag), strlen(opentag), strlen(closetag), true, sel);
				}
				var promptlang = custombbcodes[tag]['prompt'].split("\t");
				for(var i = 1; i <= params; i++) {
					if(i != params || !haveSel) {
						str += (promptlang[i - 1] ? promptlang[i - 1] : '请输入第 ' + i + ' 个参数:') + '<br /><input type="text" id="' + ctrlid + '_param_' + i + '" style="width: 98%" value="" class="txt" />' + (i < params ? '<br />' : '');
					}
				}
				break;
		}

		var menu = document.createElement('div');
		menu.id = ctrlid + '_menu';
		menu.style.display = 'none';
		menu.className = 'popupmenu_popup popupfix';
		menu.style.width = (tag == 'table' ? 192 : 250) + 'px';
		$(editorid + '_controls').appendChild(menu);
		menu.innerHTML = '<div class="popupmenu_option">' + str + '<br /><center><input type="button" id="' + ctrlid + '_submit" value="提交" /> &nbsp; <input type="button" onClick="hideMenu()" value="取消" /></center></div>';
		showMenu({'ctrlid':ctrlid,'evt':'click','duration':3,'cache':0,'drag':1});
	}

	try{$(ctrlid + '_param_1').focus()}catch(e){};
	var objs = menu.getElementsByTagName('*');
	for(var i = 0; i < objs.length; i++) {
		_attachEvent(objs[i], 'keydown', function(e) {
			e = e ? e : event;
			obj = BROWSER.ie ? event.srcElement : e.target;
			if((obj.type == 'text' && e.keyCode == 13) || (obj.type == 'textarea' && e.ctrlKey && e.keyCode == 13)) {
				if($(ctrlid + '_submit') && tag != 'image') $(ctrlid + '_submit').click();
				doane(e);
			} else if(e.keyCode == 27) {
				hideMenu();
				doane(e);
			}
		});
	}
	if($(ctrlid + '_submit')) $(ctrlid + '_submit').onclick = function() {
		checkFocus();
		if(BROWSER.ie) {
			setCaret(pos[0]);
		}
		switch(tag) {
			case 'createlink':
				var href = $(ctrlid + '_param_1').value;
				href = (isEmail(href) ? 'mailto:' : '') + href;
				if(href != '') {
					var v = selection ? selection : ($(ctrlid + '_param_2').value ? $(ctrlid + '_param_2').value : href);
					str = wysiwyg ? ('<a href="' + href + '">' + v + '</a>') : '[url=' + href + ']' + v + '[/url]';
					if(wysiwyg) insertText(str, str.length - v.length, 0, (selection ? true : false), sel);
					else insertText(str, str.length - v.length - 6, 6, (selection ? true : false), sel);
				}
				break;
			case 'code':
			case 'quote':
			case 'hide':
			case 'free':
				if(tag == 'hide' && $(ctrlid + '_radio_2').checked) {
					var mincredits = parseInt($(ctrlid + '_param_2').value);
					opentag = mincredits > 0 ? '[hide=' + mincredits + ']' : '[hide]';
				}
				str = selection ? selection : $(ctrlid + '_param_1').value;
				if(wysiwyg) {
					if(tag == 'code') {
						str = preg_replace(['<', '>'], ['&lt;', '&gt;'], str);
					}
					str = str.replace(/\r?\n/g, '<br />');
				}
				str = opentag + str + closetag;
				insertText(str, strlen(opentag), strlen(closetag), false, sel);
				break;
			case 'table':
				var rows = $(ctrlid + '_param_1').value;
				var columns = $(ctrlid + '_param_2').value;
				var width = $(ctrlid + '_param_3').value;
				var bgcolor = $(ctrlid + '_param_4').value;
				rows = /^[-\+]?\d+$/.test(rows) && rows > 0 && rows <= 30 ? rows : 2;
				columns = /^[-\+]?\d+$/.test(columns) && columns > 0 && columns <= 30 ? columns : 2;
				width = width.substr(width.length - 1, width.length) == '%' ? (width.substr(0, width.length - 1) <= 98 ? width : '98%') : (width <= 560 ? width : '98%');
				bgcolor = /[\(\)%,#\w]+/.test(bgcolor) ? bgcolor : '';
				str = '<table cellspacing="0" cellpadding="0" width="' + (width ? width : '50%') + '" class="t_table"' + (bgcolor ? ' bgcolor="' + bgcolor + '"' : '') + '>';
				for (var row = 0; row < rows; row++) {
					str += '<tr>\n';
					for (col = 0; col < columns; col++) {
						str += '<td>&nbsp;</td>\n';
					}
					str += '</tr>\n';
				}
				str += '</table>\n';
				insertText(str, str.length - pos[1], 0, false, sel);
				break;
			case 'audio':
			case 'flash':
				insertText(opentag + $(ctrlid + '_param_1').value + closetag, opentag.length, closetag.length, false, sel);
				break;
			case 'video':
				var mediaUrl = $(ctrlid + '_param_1').value;
				var ext = mediaUrl.lastIndexOf('.') == -1 ? '' : mediaUrl.substr(mediaUrl.lastIndexOf('.') + 1, mb_strlen(mediaUrl)).toLowerCase();
				ext = in_array(ext, ['mp3', 'wma', 'ra', 'rm', 'ram', 'mid', 'asx', 'wmv', 'avi', 'mpg', 'mpeg', 'rmvb', 'asf', 'mov', 'flv', 'swf']) ? ext : 'x';
				if(ext == 'x') {
					if(/^mms:\/\//.test(mediaUrl)) {
						ext = 'mms';
					} else if(/^(rtsp|pnm):\/\//.test(mediaUrl)) {
						ext = 'rtsp';
					}
				}
				var str = '[media=' + ext + ',' + $(ctrlid + '_param_2').value + ',' + $(ctrlid + '_param_3').value + ']' + mediaUrl + '[/media]';
				insertText(str, str.length - pos[1], 0, false, sel);
				break;
			case 'image':
				var width = parseInt($(ctrlid + '_param_2').value);
				var height = parseInt($(ctrlid + '_param_3').value);
				width = width > 0 && width <= 1024 ? width : 0;
				height = height && height <= 768 > 0 ? height : 0;
				var src = $(ctrlid + '_param_1').value;
				var style = '';
				if(wysiwyg) {
					style += width ? ' width=' + width : '';
					style += height ? ' height=' + height : '';
					var str = '<img src=' + src + style + ' border=0 /> ';
					insertText(str, str.length - pos[1], 0, false, sel);
				} else {
					style += width || height ? '=' + width + ',' + height : '';
					insertText('[img' + style + ']' + src + '[/img]', 0, 0, false, sel);
				}
				$(ctrlid + '_param_1').value = '';
			default:
				var first = $(ctrlid + '_param_1').value;
				if($(ctrlid + '_param_2')) var second = $(ctrlid + '_param_2').value;
				if($(ctrlid + '_param_3')) var third = $(ctrlid + '_param_3').value;
				if((params == 1 && first) || (params == 2 && first && (haveSel || second)) || (params == 3 && first && second && (haveSel || third))) {
					if(params == 1) {
						str = first;
					} else if(params == 2) {
						str = haveSel ? selection : second;
						opentag = '[' + tag + '=' + first + ']';
					} else {
						str = haveSel ? selection : third;
						opentag = '[' + tag + '=' + first + ',' + second + ']';
					}
					insertText((opentag + str + closetag), strlen(opentag), strlen(closetag), true, sel);
				}
				break;
		}
		hideMenu();
	};
}

function autoTypeset() {
	var sel;
	if(BROWSER.ie) {
		sel = wysiwyg ? editdoc.selection.createRange() : document.selection.createRange();
	}
	var selection = sel ? (wysiwyg ? sel.htmlText.replace(/<\/?p>/ig, '<br />') : sel.text) : getSel();
	selection = wysiwyg ? selection.replace(/<br[^\>]*>/ig, "\n") : selection.replace(/\r?\n/g, "\n");
	selection = trim(selection);
	selection = wysiwyg ? selection.replace(/\n+/g, '</p><p style="line-height: 30px; text-indent: 2em;">') : selection.replace(/\n/g, '[/p][p=30, 2, left]');
	opentag = wysiwyg ? '<p style="line-height: 30px; text-indent: 2em;">' : '[p=30, 2, left]';
	var s = opentag + selection + (wysiwyg ? '</p>' : '[/p]');
	insertText(s, strlen(opentag), 4, false, sel);
	hideMenu();
}

function getSel() {
	if(wysiwyg) {
		if(BROWSER.firefox || BROWSER.opera) {
			selection = editwin.getSelection();
			checkFocus();
			range = selection ? selection.getRangeAt(0) : editdoc.createRange();
			return readNodes(range.cloneContents(), false);
		} else {
			var range = editdoc.selection.createRange();
			if(range.htmlText && range.text) {
				return range.htmlText;
			} else {
				var htmltext = '';
				for(var i = 0; i < range.length; i++) {
					htmltext += range.item(i).outerHTML;
				}
				return htmltext;
			}
		}
	} else {
		if(!isUndefined(editdoc.selectionStart)) {
			return editdoc.value.substr(editdoc.selectionStart, editdoc.selectionEnd - editdoc.selectionStart);
		} else if(document.selection && document.selection.createRange) {
			return document.selection.createRange().text;
		} else if(window.getSelection) {
			return window.getSelection() + '';
		} else {
			return false;
		}
	}
}

function insertText(text, movestart, moveend, select, sel) {
	if(wysiwyg) {
		if(BROWSER.firefox || BROWSER.opera) {
			applyFormat('removeformat');
			var fragment = editdoc.createDocumentFragment();
			var holder = editdoc.createElement('span');
			holder.innerHTML = text;

			while(holder.firstChild) {
				fragment.appendChild(holder.firstChild);
			}
			insertNodeAtSelection(fragment);
		} else {
			checkFocus();
			if(!isUndefined(editdoc.selection) && editdoc.selection.type != 'Text' && editdoc.selection.type != 'None') {
				movestart = false;
				editdoc.selection.clear();
			}

			if(isUndefined(sel)) {
				sel = editdoc.selection.createRange();
			}

			sel.pasteHTML(text);

			if(text.indexOf('\n') == -1) {
				if(!isUndefined(movestart)) {
					sel.moveStart('character', -strlen(text) + movestart);
					sel.moveEnd('character', -moveend);
				} else if(movestart != false) {
					sel.moveStart('character', -strlen(text));
				}
				if(!isUndefined(select) && select) {
					sel.select();
				}
			}
		}
	} else {
		checkFocus();
		if(!isUndefined(editdoc.selectionStart)) {
			var opn = editdoc.selectionStart + 0;
			editdoc.value = editdoc.value.substr(0, editdoc.selectionStart) + text + editdoc.value.substr(editdoc.selectionEnd);

			if(!isUndefined(movestart)) {
				editdoc.selectionStart = opn + movestart;
				editdoc.selectionEnd = opn + strlen(text) - moveend;
			} else if(movestart !== false) {
				editdoc.selectionStart = opn;
				editdoc.selectionEnd = opn + strlen(text);
			}
		} else if(document.selection && document.selection.createRange) {
			if(isUndefined(sel)) {
				sel = document.selection.createRange();
			}
			sel.text = text.replace(/\r?\n/g, '\r\n');
			if(!isUndefined(movestart)) {
				sel.moveStart('character', -strlen(text) +movestart);
				sel.moveEnd('character', -moveend);
			} else if(movestart !== false) {
				sel.moveStart('character', -strlen(text));
			}
			sel.select();
		} else {
			editdoc.value += text;
		}
	}
}

function stripSimple(tag, str, iterations) {
	var opentag = '[' + tag + ']';
	var closetag = '[/' + tag + ']';

	if(isUndefined(iterations)) {
		iterations = -1;
	}
	while((startindex = stripos(str, opentag)) !== false && iterations != 0) {
		iterations --;
		if((stopindex = stripos(str, closetag)) !== false) {
			var text = str.substr(startindex + opentag.length, stopindex - startindex - opentag.length);
			str = str.substr(0, startindex) + text + str.substr(stopindex + closetag.length);
		} else {
			break;
		}
	}
	return str;
}

function stripComplex(tag, str, iterations) {
	var opentag = '[' + tag + '=';
	var closetag = '[/' + tag + ']';

	if(isUndefined(iterations)) {
		iterations = -1;
	}
	while((startindex = stripos(str, opentag)) !== false && iterations != 0) {
		iterations --;
		if((stopindex = stripos(str, closetag)) !== false) {
			var openend = stripos(str, ']', startindex);
			if(openend !== false && openend > startindex && openend < stopindex) {
				var text = str.substr(openend + 1, stopindex - openend - 1);
				str = str.substr(0, startindex) + text + str.substr(stopindex + closetag.length);
			} else {
				break;
			}
		} else {
			break;
		}
	}
	return str;
}

function stripos(haystack, needle, offset) {
	if(isUndefined(offset)) {
		offset = 0;
	}
	var index = haystack.toLowerCase().indexOf(needle.toLowerCase(), offset);

	return (index == -1 ? false : index);
}

function switchEditor(mode) {
	if(mode == wysiwyg || !allowswitcheditor)  {
		return;
	}
	if(!mode) {
		var controlbar = $(editorid + '_controls');
		var controls = [];
		var buttons = controlbar.getElementsByTagName('a');
		var buttonslength = buttons.length;
		for(var i = 0; i < buttonslength; i++) {
			if(buttons[i].id) {
				controls[controls.length] = buttons[i].id;
			}
		}
		var controlslength = controls.length;
		for(var i = 0; i < controlslength; i++) {
			var control = $(controls[i]);

			if(control.id.indexOf(editorid + '_cmd_') != -1) {
				control.className = control.id.indexOf(editorid + '_cmd_custom') == -1 ? '' : 'plugeditor';
				control.state = false;
				control.mode = 'normal';
			} else if(control.id.indexOf(editorid + '_popup_') != -1) {
				control.state = false;
			}
		}
	}
	cursor = -1;
	stack = [];
	var parsedtext = getEditorContents();
	parsedtext = mode ? bbcode2html(parsedtext) : html2bbcode(parsedtext);
	wysiwyg = mode;
	$(editorid + '_mode').value = mode;

	newEditor(mode, parsedtext);
	setEditorStyle();
	editwin.focus();
	setCaretAtEnd();
}

function insertNodeAtSelection(text) {
	checkFocus();

	var sel = editwin.getSelection();
	var range = sel ? sel.getRangeAt(0) : editdoc.createRange();
	sel.removeAllRanges();
	range.deleteContents();

	var node = range.startContainer;
	var pos = range.startOffset;

	switch(node.nodeType) {
		case Node.ELEMENT_NODE:
			if(text.nodeType == Node.DOCUMENT_FRAGMENT_NODE) {
				selNode = text.firstChild;
			} else {
				selNode = text;
			}
			node.insertBefore(text, node.childNodes[pos]);
			add_range(selNode);
			break;

		case Node.TEXT_NODE:
			if(text.nodeType == Node.TEXT_NODE) {
				var text_length = pos + text.length;
				node.insertData(pos, text.data);
				range = editdoc.createRange();
				range.setEnd(node, text_length);
				range.setStart(node, text_length);
				sel.addRange(range);
			} else {
				node = node.splitText(pos);
				var selNode;
				if(text.nodeType == Node.DOCUMENT_FRAGMENT_NODE) {
					selNode = text.firstChild;
				} else {
					selNode = text;
				}
				node.parentNode.insertBefore(text, node);
				add_range(selNode);
			}
			break;
	}
}

function add_range(node) {
	checkFocus();
	var sel = editwin.getSelection();
	var range = editdoc.createRange();
	range.selectNodeContents(node);
	sel.removeAllRanges();
	sel.addRange(range);
}

function readNodes(root, toptag) {
	var html = "";
	var moz_check = /_moz/i;

	switch(root.nodeType) {
		case Node.ELEMENT_NODE:
		case Node.DOCUMENT_FRAGMENT_NODE:
			var closed;
			if(toptag) {
				closed = !root.hasChildNodes();
				html = '<' + root.tagName.toLowerCase();
				var attr = root.attributes;
				for(var i = 0; i < attr.length; ++i) {
					var a = attr.item(i);
					if(!a.specified || a.name.match(moz_check) || a.value.match(moz_check)) {
						continue;
					}
					html += " " + a.name.toLowerCase() + '="' + a.value + '"';
				}
				html += closed ? " />" : ">";
			}
			for(var i = root.firstChild; i; i = i.nextSibling) {
				html += readNodes(i, true);
			}
			if(toptag && !closed) {
				html += "</" + root.tagName.toLowerCase() + ">";
			}
			break;

		case Node.TEXT_NODE:
			html = htmlspecialchars(root.data);
			break;
	}
	return html;
}

function moveCursor(increment) {
	var test = cursor + increment;
	if(test >= 0 && stack[test] != null && !isUndefined(stack[test])) {
		cursor += increment;
	}
}

function addSnapshot(str) {
	if(stack[cursor] == str) {
		return;
	} else {
		cursor++;
		stack[cursor] = str;

		if(!isUndefined(stack[cursor + 1])) {
			stack[cursor + 1] = null;
		}
	}
}

function getSnapshot() {
	if(!isUndefined(stack[cursor]) && stack[cursor] != null) {
		return stack[cursor];
	} else {
		return false;
	}
}

function clearContent() {
	if(wysiwyg) {
		editdoc.body.innerHTML = BROWSER.firefox ? '<br />' : '';
	} else {
		textobj.value = '';
	}
}

function uploadNextAttach() {
	var str = $('attachframe').contentWindow.document.body.innerHTML;
	if(str == '') return;
	var arr = str.split('|');
	var att = CURRENTATTACH.split('|');
	uploadAttach(parseInt(att[0]), arr[0] == 'DISCUZUPLOAD' ? parseInt(arr[1]) : -1, att[1]);
}

function uploadAttach(curId, statusid, prefix) {
	prefix = isUndefined(prefix) ? '' : prefix;
	var nextId = 0;
	for(var i = 0; i < AID - 1; i++) {
		if($(prefix + 'attachform_' + i)) {
			nextId = i;
			if(curId == 0) {
				break;
			} else {
				if(i > curId) {
					break;
				}
			}
		}
	}
	if(nextId == 0) {
		return;
	}
	CURRENTATTACH = nextId + '|' + prefix;
	if(curId > 0) {
		if(statusid == 0) {
			UPLOADCOMPLETE++;
		} else {
			FAILEDATTACHS += '<br />' + mb_cutstr($(prefix + 'attachnew_' + curId).value.substr($(prefix + 'attachnew_' + curId).value.replace(/\\/g, '/').lastIndexOf('/') + 1), 25) + ': ' + STATUSMSG[statusid];
			UPLOADFAILED++;
		}
		$(prefix + 'cpdel_' + curId).innerHTML = '<img src="' + IMGDIR + '/check_' + (statusid == 0 ? 'right' : 'error') + '.gif" alt="' + STATUSMSG[statusid] + '" />';
		if(nextId == curId || in_array(statusid, [6, 8])) {
			if(prefix == 'img') updateImageList();
			else updateAttachList();
			if(UPLOADFAILED > 0) {
				showDialog('附件上传完成！成功 ' + UPLOADCOMPLETE + ' 个，失败 ' + UPLOADFAILED + ' 个:' + FAILEDATTACHS);
				FAILEDATTACHS = '';
			}
			UPLOADSTATUS = 2;
			for(var i = 0; i < AID - 1; i++) {
				if($(prefix + 'attachform_' + i)) {
					reAddAttach(prefix, i)
				}
			}
			$(prefix + 'uploadbtn').style.display = '';
			$(prefix + 'uploading').style.display = 'none';
			if(AUTOPOST) {
				hideMenu();
				validate($('postform'));
			} else if(UPLOADFAILED == 0 && ((prefix == 'img' && $(editorid + '_cmd_image_menu').style.display == 'none') || (prefix == '' && $(editorid + '_cmd_attach_menu').style.display == 'none'))) {
				showDialog('附件上传完成！', 'notice');
			}
			UPLOADFAILED = UPLOADCOMPLETE = 0;
			CURRENTATTACH = '0';
			FAILEDATTACHS = '';
			return;
		}
	} else {
		$(prefix + 'uploadbtn').style.display = 'none';
		$(prefix + 'uploading').style.display = '';
	}
	$(prefix + 'cpdel_' + nextId).innerHTML = '<img src="' + IMGDIR + '/loading.gif" alt="上传中..." />';
	UPLOADSTATUS = 1;
	$(prefix + 'attachform_' + nextId).submit();
}

function addAttach(prefix) {
	var id = AID;
	var tags, newnode, i;
	prefix = isUndefined(prefix) ? '' : prefix;
	newnode = $(prefix + 'attachbtnhidden').firstChild.cloneNode(true);
	tags = newnode.getElementsByTagName('input');
	for(i in tags) {
		if(tags[i].name == 'Filedata') {
			tags[i].id = prefix + 'attachnew_' + id;
			tags[i].onchange = function() {insertAttach(prefix, id)};
			tags[i].unselectable = 'on';
		} else if(tags[i].name == 'attachid') {
			tags[i].value = id;
		}
	}
	tags = newnode.getElementsByTagName('form');
	tags[0].name = tags[0].id = prefix + 'attachform_' + id;
	$(prefix + 'attachbtn').appendChild(newnode);
	newnode = $(prefix + 'attachbodyhidden').firstChild.cloneNode(true);
	tags = newnode.getElementsByTagName('input');
	for(i in tags) {
		if(tags[i].name == prefix + 'localid[]') {
			tags[i].value = id;
		}
	}
	tags = newnode.getElementsByTagName('span');
	for(i in tags) {
		if(tags[i].id == prefix + 'localfile[]') {
			tags[i].id = prefix + 'localfile_' + id;
		} else if(tags[i].id == prefix + 'cpdel[]') {
			tags[i].id = prefix + 'cpdel_' + id;
		} else if(tags[i].id == prefix + 'localno[]') {
			tags[i].id = prefix + 'localno_' + id;
		} else if(tags[i].id == prefix + 'deschidden[]') {
			tags[i].id = prefix + 'deschidden_' + id;
		}
	}
	AID++;
	newnode.style.display = 'none';
	$(prefix + 'attachbody').appendChild(newnode);
}

function insertAttach(prefix, id) {
	var localimgpreview = '';
	var path = $(prefix + 'attachnew_' + id).value;
	var extpos = path.lastIndexOf('.');
	var ext = extpos == -1 ? '' : path.substr(extpos + 1, path.length).toLowerCase();
	var re = new RegExp("(^|\\s|,)" + ext + "($|\\s|,)", "ig");
	var localfile = $(prefix + 'attachnew_' + id).value.substr($(prefix + 'attachnew_' + id).value.replace(/\\/g, '/').lastIndexOf('/') + 1);
	var filename = mb_cutstr(localfile, 30);

	if(path == '') {
		return;
	}
	if(extensions != '' && (re.exec(extensions) == null || ext == '')) {
		reAddAttach(prefix, id);
		showDialog('对不起，不支持上传此类扩展名的附件。');
		return;
	}
	if(prefix == 'img' && imgexts.indexOf(ext) == -1) {
		reAddAttach(prefix, id);
		showDialog('请选择图片文件(' + imgexts + ')');
		return;
	}

	$(prefix + 'cpdel_' + id).innerHTML = '<a href="###" class="deloption" onclick="reAddAttach(\'' + prefix + '\', ' + id + ')">删除</a>';
	$(prefix + 'localfile_' + id).innerHTML = '<span>' + filename + '</span>';
	$(prefix + 'attachnew_' + id).style.display = 'none';
	$(prefix + 'deschidden_' + id).style.display = '';
	$(prefix + 'deschidden_' + id).title = localfile;
	$(prefix + 'localno_' + id).parentNode.parentNode.style.display = '';
	addAttach(prefix);
	UPLOADSTATUS = 0;
}

function reAddAttach(prefix, id) {
	$(prefix + 'attachbody').removeChild($(prefix + 'localno_' + id).parentNode.parentNode);
	$(prefix + 'attachbtn').removeChild($(prefix + 'attachnew_' + id).parentNode.parentNode);
	$(prefix + 'attachbody').innerHTML == '' && addAttach(prefix);
	$('localimgpreview_' + id) ? document.body.removeChild($('localimgpreview_' + id)) : null;
}

function delAttach(id, type) {
	appendAttachDel(id);
	$('attach_' + id).style.display = 'none';
	ATTACHNUM['attach' + (type ? 'un' : '') + 'used']--;
	updateattachnum('attach');
}

function delImgAttach(id, type) {
	appendAttachDel(id);
	$('image_td_' + id).className = 'imgdeleted';
	$('image_' + id).onclick = null;
	$('image_desc_' + id).disabled = true;
	ATTACHNUM['image' + (type ? 'un' : '') + 'used']--;
	updateattachnum('image');
}

function appendAttachDel(id) {
	var input = document.createElement('input');
	input.name = 'attachdel[]';
	input.value = id;
	input.type = 'hidden';
	$('postbox').appendChild(input);
}

function updateAttach(aid) {
	objupdate = $('attachupdate'+aid);
	obj = $('attach' + aid);
	if(!objupdate.innerHTML) {
		obj.style.display = 'none';
		objupdate.innerHTML = '<input type="file" name="attachupdate[paid' + aid + ']"><a href="javascript:;" onclick="updateAttach(' + aid + ')">取消</a>';
	} else {
		obj.style.display = '';
		objupdate.innerHTML = '';
	}
}

function updateattachnum(type) {
	ATTACHNUM[type + 'used'] = ATTACHNUM[type + 'used'] >= 0 ? ATTACHNUM[type + 'used'] : 0;
	ATTACHNUM[type + 'unused'] = ATTACHNUM[type + 'unused'] >= 0 ? ATTACHNUM[type + 'unused'] : 0;
	var num = ATTACHNUM[type + 'used'] + ATTACHNUM[type + 'unused'];
	if(num) {
		$(editorid + '_cmd_' + type).title = '包含 ' + num + (type == 'image' ? ' 个图片附件' : ' 个附件');
		$(editorid + '_cmd_' + type + '_notice').style.display = '';
	} else {
		$(editorid + '_cmd_' + type).title = type == 'image' ? '图片' : '附件';
		$(editorid + '_cmd_' + type + '_notice').style.display = 'none';
	}
}

function swfHandler(action, type) {
	if(type == 'image') {
		updateImageList(action);
	} else {
		updateAttachList(action);
	}
}

function updateAttachList(action) {
	if(action != 2) ajaxget('ajax.php?action=attachlist&posttime=' + $('posttime').value, 'attachlist');
	if(action != 1) switchAttachbutton('attachlist');$('attach_tblheader').style.display = $('attach_notice').style.display = '';
}

function updateImageList(action) {
	if(action != 2) ajaxget('ajax.php?action=imagelist&pid=' + pid + '&posttime=' + $('posttime').value, 'imgattachlist');
	if(action != 1) switchImagebutton('imgattachlist');$('imgattach_notice').style.display = '';
}

function switchButton(btn, btns) {
	if(!$(editorid + '_btn_' + btn) || !$(editorid + '_' + btn)) {
		return;
	}
	$(editorid + '_btn_' + btn).style.display = '';
	$(editorid + '_' + btn).style.display = '';
	$(editorid + '_btn_' + btn).className = 'current';
	for(i = 0;i < btns.length;i++) {
		if(btns[i] != btn) {
			if(!$(editorid + '_' + btns[i]) || !$(editorid + '_btn_' + btns[i])) {
				continue;
			}
			$(editorid + '_' + btns[i]).style.display = 'none';
			$(editorid + '_btn_' + btns[i]).className = '';
		}
	}
}

function uploadWindowstart() {
	$('uploadwindowing').style.visibility = 'visible';
	$('uploadsubmit').disabled = true;
}

function uploadWindowload() {
	$('uploadwindowing').style.visibility = 'hidden';
	$('uploadsubmit').disabled = false;
	var str = $('uploadattachframe').contentWindow.document.body.innerHTML;
	if(str == '') return;
	var arr = str.split('|');
	if(arr[0] == 'DISCUZUPLOAD' && arr[2] == 0) {
		UPLOADWINRECALL(arr[3], arr[5]);
		hideWindow('upload');
	} else {
		showDialog('上传失败:' + STATUSMSG[arr[2]]);
	}
}

function uploadWindow(recall, type) {
	var type = isUndefined(type) ? 'image' : type;
	UPLOADWINRECALL = recall;
	showWindow('upload', 'misc.php?action=upload&fid=' + fid + '&type=' + type, 'get', 0, 1, 0);
}

function updatetradeattach(aid, url, attachurl) {
	$('tradeaid').value = aid;
	$('tradeattach_image').innerHTML = '<img src="' + attachurl + '/' + url + '" class="goodsimg" />';
}