<?php

/*
[Discuz!] (C)2001-2009 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: counter.inc.php 21270 2009-11-24 06:26:41Z monkey $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

cpheader();

$pertask = isset($pertask) ? intval($pertask) : 100;
$current = isset($current) && $current > 0 ? intval($current) : 0;
$next = $current + $pertask;

if(submitcheck('forumsubmit', 1)) {

	$nextlink = "$BASESCRIPT?action=counter&current=$next&pertask=$pertask&forumsubmit=yes";
	$processed = 0;

	$queryf = $db->query("SELECT fid FROM {$tablepre}forums WHERE type<>'group' LIMIT $current, $pertask");
	while($forum = $db->fetch_array($queryf)) {
		$processed = 1;

		extract($db->fetch_first("SELECT COUNT(*) AS threads, SUM(replies)+COUNT(*) AS posts FROM {$tablepre}threads WHERE fid='$forum[fid]' AND displayorder>='0'"));

		$thread = $db->fetch_first("SELECT tid, subject, lastpost, lastposter FROM {$tablepre}threads WHERE fid='$forum[fid]' AND displayorder>='0' ORDER BY lastpost DESC LIMIT 1");
		$lastpost = addslashes("$thread[tid]\t$thread[subject]\t$thread[lastpost]\t$thread[lastposter]");

		$db->query("UPDATE {$tablepre}forums SET threads='$threads', posts='$posts', lastpost='$lastpost' WHERE fid='$forum[fid]'");
	}

	if($processed) {
		cpmsg("$lang[counter_forum]: $lang[counter_processing]", $nextlink, 'loading');
	} else {
		$db->query("UPDATE {$tablepre}forums SET threads='0', posts='0' WHERE type='group'");
		cpmsg('counter_forum_succeed', $BASESCRIPT.'?action=counter', 'succeed');
	}

} elseif(submitcheck('digestsubmit', 1)) {

	if(!$current) {
		$db->query("UPDATE {$tablepre}members SET digestposts=0", 'UNBUFFERED');
		$current = 0;
	}
	$nextlink = "$BASESCRIPT?action=counter&current=$next&pertask=$pertask&digestsubmit=yes";
	$processed = 0;
	$membersarray = $postsarray = array();

	$query = $db->query("SELECT authorid FROM {$tablepre}threads WHERE digest<>'0' AND displayorder>='0' LIMIT $current, $pertask");
	while($thread = $db->fetch_array($query)) {
		$processed = 1;
		$membersarray[$thread['authorid']]++;
	}

	foreach($membersarray as $uid => $posts) {
		$postsarray[$posts] .= ','.$uid;
	}
	unset($membersarray);

	foreach($postsarray as $posts => $uids) {
		$db->query("UPDATE {$tablepre}members SET digestposts=digestposts+'$posts' WHERE uid IN (0$uids)", 'UNBUFFERED');
	}

	if($processed) {
		cpmsg("$lang[counter_digest]: $lang[counter_processing]", $nextlink, 'loading');
	} else {
		cpmsg('counter_digest_succeed', $BASESCRIPT.'?action=counter', 'succeed');
	}

} elseif(submitcheck('membersubmit', 1)) {

	$nextlink = "$BASESCRIPT?action=counter&current=$next&pertask=$pertask&membersubmit=yes";
	$processed = 0;

	$queryt = $db->query("SELECT uid FROM {$tablepre}members LIMIT $current, $pertask");
	while($mem = $db->fetch_array($queryt)) {
		$processed = 1;
		$query = $db->query("SELECT COUNT(*) FROM {$tablepre}posts WHERE authorid='$mem[uid]' AND invisible='0'");
		$query_threads = $db->query("SELECT COUNT(*) FROM {$tablepre}threads WHERE authorid='$mem[uid]'");
		$db->query("UPDATE {$tablepre}members SET posts='".$db->result($query, 0)."', threads='".$db->result($query_threads, 0)."' WHERE uid='$mem[uid]'");
	}

	if($processed) {
		cpmsg("$lang[counter_member]: $lang[counter_processing]", $nextlink, 'loading');
	} else {
		cpmsg('counter_member_succeed', $BASESCRIPT.'?action=counter', 'succeed');
	}

} elseif(submitcheck('threadsubmit', 1)) {

	$nextlink = "$BASESCRIPT?action=counter&current=$next&pertask=$pertask&threadsubmit=yes";
	$processed = 0;

	$queryt = $db->query("SELECT tid, replies, lastpost, lastposter FROM {$tablepre}threads WHERE displayorder>='0' LIMIT $current, $pertask");
	while($threads = $db->fetch_array($queryt)) {
		$processed = 1;
		$replynum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$threads[tid]' AND invisible='0'");
		$replynum--;
		$lastpost  = $db->fetch_first("SELECT author, dateline FROM {$tablepre}posts WHERE tid='$threads[tid]' AND invisible='0' ORDER BY dateline DESC LIMIT 1");
		if($threads['replies'] != $replynum || $threads['lastpost'] != $lastpost['dateline'] || $threads['lastposter'] != $lastpost['author']) {
			$db->query("UPDATE LOW_PRIORITY {$tablepre}threads SET replies='$replynum', lastpost='$lastpost[dateline]', lastposter='".addslashes($lastpost['author'])."' WHERE tid='$threads[tid]'", 'UNBUFFERED');
		}
	}

	if($processed) {
		cpmsg("$lang[counter_thread]: $lang[counter_processing]", $nextlink, 'loading');
	} else {
		cpmsg('counter_thread_succeed', $BASESCRIPT.'?action=counter', 'succeed');
	}

} elseif(submitcheck('movedthreadsubmit', 1)) {

	$nextlink = "$BASESCRIPT?action=counter&current=$next&pertask=$pertask&movedthreadsubmit=yes";
	$processed = 0;

	$tids = 0;
	$query = $db->query("SELECT t1.tid, t2.tid AS threadexists FROM {$tablepre}threads t1
		LEFT JOIN {$tablepre}threads t2 ON t2.tid=t1.closed AND t2.displayorder>='0'
		WHERE t1.closed>'1' LIMIT $current, $pertask");

	while($thread = $db->fetch_array($query)) {
		$processed = 1;
		if($thread['threadexists']) {
			$tids .= ','.$thread['tid'];
		}
	}

	if($tids) {
		$db->query("DELETE FROM {$tablepre}threads WHERE tid IN ($tids)", 'UNBUFFERED');
	}

	if($processed) {
		cpmsg("$lang[counter_moved_thread]: $lang[counter_processing]", $nextlink, 'loading');
	} else {
		cpmsg('counter_moved_thread_succeed', $BASESCRIPT.'?action=counter', 'succeed');
	}

} elseif(submitcheck('cleanupsubmit', 1)) {

	$nextlink = "$BASESCRIPT?action=counter&current=$next&pertask=$pertask&cleanupsubmit=yes";
	$processed = 0;

	$queryt = $db->query("SELECT tid,fid FROM {$tablepre}favorites LIMIT $current, $pertask");
	while($fav = $db->fetch_array($queryt)) {
		$processed = 1;
		if($fav['tid']) {
			$favtid[]= $fav['tid'];
		} elseif($fav['fid']) {
			$favfid[]= $fav['fid'];
		}
	}

	if(!empty($favtid)) {
		foreach($favtid as $tid) {
			if(!$db->result_first("SELECT tid FROM {$tablepre}threads WHERE tid='$tid'")) {
				$db->query("DELETE FROM {$tablepre}favorites WHERE tid='$tid'");
				$db->query("DELETE FROM {$tablepre}rewardlog WHERE tid='$tid'");
			}
		}
	}

	if(!empty($favfid)) {
		foreach($favfid as $fid) {
			if(!$db->result_first("SELECT fid FROM {$tablepre}forums WHERE fid='$fid'")) {
				$db->query("DELETE FROM {$tablepre}favorites WHERE fid='$fid'");
			}
		}
	}

	if($processed) {
		cpmsg("$lang[counter_moved_favorites_logs]: $lang[counter_processing]", $nextlink, 'loading');
	} else {
		cpmsg('counter_moved_favorites_logs_succeed', $BASESCRIPT.'?action=counter', 'succeed');
	}

} elseif(submitcheck('specialarrange', 1)) {
	$cursort = empty($cursort) ? 0 : intval($cursort);
	$changesort = isset($changesort) && empty($changesort) ? 0 : 1;
	$processed = 0;

	$fieldtypes = array('number' => 'bigint(20)', 'text' => 'mediumtext', 'radio' => 'smallint(6)', 'checkbox' => 'mediumtext', 'textarea' => 'mediumtext', 'select' => 'smallint(6)', 'calendar' => 'mediumtext', 'email' => 'mediumtext', 'url' => 'mediumtext', 'image' => 'mediumtext');

	$optionvalues = array();
	
	$query = $db->query("SELECT v.*, p.identifier, p.type FROM {$tablepre}typevars v LEFT JOIN {$tablepre}typeoptions p ON p.optionid=v.optionid WHERE search='1' OR p.type IN('radio','select','number')");
	$optionvalues = $sortids = array();
	while($row = $db->fetch_array($query)) {
		$optionvalues[$row['sortid']][$row['identifier']] = $row['type'];
		$optionids[$row['sortid']][$row['optionid']] = $row['identifier'];
		$searchs[$row['sortid']][$row['optionid']] = $row['search'];
		$sortids[] = $row['sortid'];
	}
	$sortids = array_unique($sortids);
	sort($sortids);
	if($sortids[$cursort] && $optionvalues[$sortids[$cursort]]) {
		$processed = 1;
		$sortid = $sortids[$cursort];
		$options = $optionvalues[$sortid];
		$search = $searchs[$sortid];
		$tablename = "{$tablepre}optionvalue{$sortid}";
		$query = $db->query("SHOW TABLES LIKE '$tablename'");
		if($db->num_rows($query) != 1) {
			$create_table_sql = "CREATE TABLE $tablename (";
			$create_table_sql .= "tid mediumint(8) UNSIGNED NOT NULL DEFAULT '0',fid smallint(6) UNSIGNED NOT NULL DEFAULT '0',";
			$create_table_sql .= "KEY (fid)";
			$create_table_sql .= ") TYPE=MyISAM;";
			$dbcharset = empty($dbcharset) ? str_replace('-','',$charset) : $dbcharset;
			$create_table_sql = syntablestruct($create_table_sql, $db->version() > '4.1', $dbcharset);
			$db->query($create_table_sql);
		}
		if($changesort) $db->query("TRUNCATE $tablename");
		$opids = array_keys($optionids[$sortid]);
		$tables = array();
		if($db->version() > '4.1') {
			$query = $db->query("SHOW FULL COLUMNS FROM $tablename", 'SILENT');
		} else {
			$query = $db->query("SHOW COLUMNS FROM $tablename", 'SILENT');
		}
		while($field = @$db->fetch_array($query)) {
			$tables[$field['Field']] = 1;
		}
		foreach($optionids[$sortid] as $optionid => $identifier) {
			if(!$tables[$identifier] && (in_array($options[$identifier], array('radio', 'select', 'number')) || $search[$optionid])) {
				$fieldname = $identifier;
				if(in_array($options[$identifier], array('radio', 'select'))) {
					$fieldtype = 'smallint(6) UNSIGNED NOT NULL DEFAULT \'0\'';
				} elseif($options[$identifier] == 'number') {
					$fieldtype = 'int(10) UNSIGNED NOT NULL DEFAULT \'0\'';
				} else {
					$fieldtype = 'mediumtext NOT NULL';
				}
				$db->query("ALTER TABLE {$tablepre}optionvalue$sortid ADD $fieldname $fieldtype");

				if(in_array($options[$identifier], array('radio', 'select', 'number'))) {
					$db->query("ALTER TABLE {$tablepre}optionvalue$sortid ADD INDEX ($fieldname)");
				}
			}
		}
		
		$query = $db->query("SELECT t.*, th.fid FROM {$tablepre}typeoptionvars t left join {$tablepre}threads th ON th.tid=t.tid WHERE t.sortid='$sortid' AND t.optionid IN ('".implode("','", $opids)."')");
		$inserts = array();
		while($row = $db->fetch_array($query)) {
			$opname = $optionids[$sortid][$row['optionid']];
			if(empty($inserts[$row[tid]])) {
				$inserts[$row['tid']]['tid'] = $row['tid'];
				$inserts[$row['tid']]['fid'] = $row['fid'];
			}
			$inserts[$row['tid']][$opname] = addslashes($row['value']);
		}
		if($inserts) {
			foreach($inserts as $tid => $fieldval) {
				$rfields = array();
				$ikey = $ival = '';
				foreach($fieldval as $ikey => $ival) {
					$rfields[] = "`$ikey`='$ival'";
				}
				$db->query("REPLACE INTO $tablename SET ".implode(',', $rfields));
			}
		} 
		$cursort ++;
		$changesort = 1;
	}
	
	$nextlink = "$BASESCRIPT?action=counter&changesort=$changesort&cursort=$cursort&specialarrange=yes";
	if($processed) {
		cpmsg("$lang[counter_special_arrange]:(第{$cursort}类/共".count($sortids)."类)", $nextlink, 'loading');
	} else {
		cpmsg('counter_special_arrange_succeed', $BASESCRIPT.'?action=counter', 'succeed');
	}
} else {

	shownav('tools', 'nav_updatecounters');
	showsubmenu('nav_updatecounters');
	showformheader('counter');
	showtableheader();
	showsubtitle(array('', 'counter_amount'));
	showhiddenfields(array('pertask' => ''));
	showtablerow('', array('class="td21"'), array(
		"$lang[counter_forum]:",
		'<input name="pertask1" type="text" class="txt" value="15" /><input type="submit" class="btn" name="forumsubmit" onclick="this.form.pertask.value=this.form.pertask1.value" value="'.$lang['submit'].'" />'
	));
	showtablerow('', array('class="td21"'), array(
		"$lang[counter_digest]:",
		'<input name="pertask2" type="text" class="txt" value="1000" /><input type="submit" class="btn" name="digestsubmit" onclick="this.form.pertask.value=this.form.pertask2.value" value="'.$lang['submit'].'" />'
	));
	showtablerow('', array('class="td21"'), array(
		"$lang[counter_member]:",
		'<input name="pertask3" type="text" class="txt" value="1000" /><input type="submit" class="btn" name="membersubmit" onclick="this.form.pertask.value=this.form.pertask3.value" value="'.$lang['submit'].'" />'
	));
	showtablerow('', array('class="td21"'), array(
		"$lang[counter_thread]:",
		'<input name="pertask4" type="text" class="txt" value="500" /><input type="submit" class="btn" name="threadsubmit" onclick="this.form.pertask.value=this.form.pertask4.value" value="'.$lang['submit'].'" />'
	));
	showtablerow('', array('class="td21"'), array(
		"$lang[counter_moved_thread]:",
		'<input name="pertask5" type="text" class="txt" value="100" /><input type="submit" class="btn" name="movedthreadsubmit" onclick="this.form.pertask.value=this.form.pertask5.value" value="'.$lang['submit'].'" />'
	));
	showtablerow('', array('class="td21"'), array(
		"$lang[counter_moved_favorites_logs]:",
		'<input name="pertask6" type="text" class="txt" value="100" /><input type="submit" class="btn" name="cleanupsubmit" onclick="this.form.pertask.value=this.form.pertask6.value" value="'.$lang['submit'].'" />'
	));
	showtablerow('', array('class="td21"'), array(
		"$lang[counter_special_arrange]:",
		'<input name="pertask7" type="text" class="txt" value="1" disabled/><input type="submit" class="btn" name="specialarrange" onclick="this.form.pertask.value=this.form.pertask7.value" value="'.$lang['submit'].'" />'
	));
	showtablefooter();
	showformfooter();

}

function syntablestruct($sql, $version, $dbcharset) {

	if(strpos(trim(substr($sql, 0, 18)), 'CREATE TABLE') === FALSE) {
		return $sql;
	}

	$sqlversion = strpos($sql, 'ENGINE=') === FALSE ? FALSE : TRUE;

	if($sqlversion === $version) {

		return $sqlversion && $dbcharset ? preg_replace(array('/ character set \w+/i', '/ collate \w+/i', "/DEFAULT CHARSET=\w+/is"), array('', '', "DEFAULT CHARSET=$dbcharset"), $sql) : $sql;
	}

	if($version) {
		return preg_replace(array('/TYPE=HEAP/i', '/TYPE=(\w+)/is'), array("ENGINE=MEMORY DEFAULT CHARSET=$dbcharset", "ENGINE=\\1 DEFAULT CHARSET=$dbcharset"), $sql);

	} else {
		return preg_replace(array('/character set \w+/i', '/collate \w+/i', '/ENGINE=MEMORY/i', '/\s*DEFAULT CHARSET=\w+/is', '/\s*COLLATE=\w+/is', '/ENGINE=(\w+)(.*)/is'), array('', '', 'ENGINE=HEAP', '', '', 'TYPE=\\1\\2'), $sql);
	}
}
?>