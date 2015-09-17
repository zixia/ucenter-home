/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: qihoo.js 17449 2008-12-22 08:58:53Z cnteacher $
*/

var qihoo_num = 0;
var qihoo_perpage = 0;
var qihoo_threads = "";

function qihoothreads(num) {
	var threadslist = "";
	if(num) {
		for(i = 0; i < num; i++) {
			threadslist += "<tr><td><a href=\"viewthread.php?tid=" + qihoo_threads[i][1] + "\" target=\"_blank\">" + qihoo_threads[i][0] + "</a></td>" +
				"<td><a href=\"forumdisplay.php?fid=" + qihoo_threads[i][8] + "\" target=\"_blank\">" + qihoo_threads[i][2] + "</a></td>" +
				"<td><a href=\"space.php?username=" + qihoo_threads[i][3] + "\" target=\"_blank\">" + qihoo_threads[i][3] + "</a><br />" + qihoo_threads[i][6] + "</td>" +
				"<td>" + qihoo_threads[i][4] + "</td>" +
				"<td>" + qihoo_threads[i][5] + "</td>" +
				"<td>" + qihoo_threads[i][7] + "</td></tr>";
		}
	}
	return threadslist;
}

function multi(num, perpage, curpage, mpurl, maxpages) {
	var multipage = "";
	if(num > perpage) {
		var page = 10;
		var offset = 2;
		var form = 0;
		var to = 0;
		var maxpages = !maxpages ? 0 : maxpages;

		var realpages = Math.ceil(num / perpage);
		var pages = maxpages && maxpages < realpages ? maxpages : realpages;

		if(page > pages) {
			from = 1;
			to = pages;
		} else {
			from = curpage - offset;
			to = from + page - 1;
			if(from < 1) {
				to = curpage + 1 - from;
				from = 1;
				if(to - from < page) {
					to = page;
				}
			} else if(to > pages) {
				from = pages - page + 1;
				to = pages;
			}
		}

		multipage = (curpage - offset > 1 && pages > page ? "<a href=\"" + mpurl + "&page=1\" class=\"first\">1 ...</a>" : "") + (curpage > 1 ? "<a href=\"" + mpurl + "&page=" + (curpage - 1) + "\" class=\"prev\">&lsaquo;&lsaquo;</a>" : "");
		for(i = from; i <= to; i++) {
			multipage += (i == curpage ? "<strong>" + i + "</strong>" : "<a href=\"" + mpurl + "&page=" + i + "\">" + i + "</a>");
		}

		multipage += (curpage < pages ? "<a href=\"" + mpurl + "&page=" + (curpage + 1) + "\" class=\"next\" >&rsaquo;&rsaquo;</a>" : "") +
		(to < pages ? "<a href=\"" + mpurl + "&page=" + pages + "\ class=\"last\">... " + realpages + "</a>" : "") +
		(pages > page ? "<input type=\"text\" name=\"custompage\" size=\"3\" onKeyDown=\"if(event.keyCode==13) {window.location='" + mpurl + "&page=\'+this.value;}\">" : "");
		multipage = "<div class=\"pages\">" + multipage + "</div>";

	}
	return multipage;
}