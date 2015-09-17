/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: moderate.js 20061 2009-09-18 02:07:08Z monkey $
*/

function modaction(action, pid, extra) {
	if(!action) {
		return;
	}
	var extra = !extra ? '' : '&' + extra;
	if(!pid && in_array(action, ['delpost', 'banpost'])) {
		var checked = 0;
		var pid = '';
		for(var i = 0; i < $('modactions').elements.length; i++) {
			if($('modactions').elements[i].name.match('topiclist')) {
				checked = 1;
				break;
			}
		}
	} else {
		var checked = 1;
	}
	if(!checked) {
		alert('请选择需要操作的帖子');
	} else {
		$('modactions').action = 'topicadmin.php?action='+ action +'&fid=' + fid + '&tid=' + tid + '&infloat=yes&nopost=yes' + (!pid ? '' : '&topiclist[]=' + pid) + extra + '&r' + Math.random();
		showWindow('mods', 'modactions', 'post');
		if(BROWSER.ie) {
			doane(event);
		}
		hideMenu();
	}
}

function modthreads(optgroup, operation) {
	var operation = !operation ? '' : operation;
	$('modactions').action = 'topicadmin.php?action=moderate&fid=' + fid + '&moderate[]=' + tid + '&infloat=yes&nopost=yes' + (optgroup != 3 && optgroup != 2 ? '&from=' + tid : '');
	$('modactions').optgroup.value = optgroup;
	$('modactions').operation.value = operation;
	hideWindow('mods');
	showWindow('mods', 'modactions', 'post', 0);
	if(BROWSER.ie) {
		doane(event);
	}
}

function pidchecked(obj) {
	if(obj.checked) {
		if(BROWSER.ie && !BROWSER.opera) {
			var inp = document.createElement('<input name="topiclist[]" />');
		} else {
			var inp = document.createElement('input');
			inp.name = 'topiclist[]';
		}
		inp.id = 'topiclist_' + obj.value;
		inp.value = obj.value;
		inp.style.display = 'none';
		$('modactions').appendChild(inp);
	} else {
		$('modactions').removeChild($('topiclist_' + obj.value));
	}
}

var modclickcount = 0;
function modclick(obj, pid) {
	if(obj.checked) {
		modclickcount++;
	} else {
		modclickcount--;
	}
	$('modcount').innerHTML = modclickcount;
	if(modclickcount > 0) {
		var offset = fetchOffset(obj);
		$('modlayer').style.top = offset['top'] - 65 + 'px';
		$('modlayer').style.left = offset['left'] - 215 + 'px';
		$('modlayer').style.display = '';
	} else {
		$('modlayer').style.display = 'none';
	}
}

function tmodclick(obj) {
	if(obj.checked) {
		modclickcount++;
	} else {
		modclickcount--;
	}
	$('modcount').innerHTML = modclickcount;
	if(modclickcount > 0) {
		var top_offset = obj.offsetTop;
		while((obj = obj.offsetParent).id != 'threadlist') {
			top_offset += obj.offsetTop;
		}
		$('modlayer').style.top = top_offset - 7 + 'px';
		$('modlayer').style.display = '';
	} else {
		$('modlayer').style.display = 'none';
	}
}

function tmodthreads(optgroup, operation) {
	var checked = 0;
	var operation = !operation ? '' : operation;
	for(var i = 0; i < $('moderate').elements.length; i++) {
		if($('moderate').elements[i].name.match('moderate') && $('moderate').elements[i].checked) {
			checked = 1;
			break;
		}
	}
	if(!checked) {
		alert('请选择需要操作的帖子');
	} else {
		$('moderate').optgroup.value = optgroup;
		$('moderate').operation.value = operation;
		showWindow('mods', 'moderate', 'post');
	}
}