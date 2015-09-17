<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: ranks.inc.php 16698 2008-11-14 07:58:56Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

cpheader();

if(!submitcheck('ranksubmit')) {

	echo <<<EOT
<script type="text/JavaScript">
var rowtypedata = [
	[
		[1,'', 'td25'],
		[1,'<input type="text" class="txt" size="12" name="newranktitle[]">'],
		[1,'<input type="text" class="txt" size="6" name="newpostshigher[]">'],
		[1,'<input type="text" class="txt" size="2" name="newstars[]">', 'td28'],
		[1,'<input type="text" class="txt" size="6" name="newcolor[]">']
	]
];
</script>
EOT;
	shownav('user', 'nav_ranks');
	showsubmenu('nav_ranks');
	showtips('ranks_tips');
	showformheader('ranks');
	showtableheader();
	showsubtitle(array('', 'ranks_title', 'ranks_postshigher', 'ranks_stars', 'ranks_color'));

	$query = $db->query("SELECT * FROM {$tablepre}ranks ORDER BY postshigher");
	while($rank = $db->fetch_array($query)) {
		showtablerow('', array('class="td25"', '', '', 'class="td28"'), array(
			"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[{$rank[rankid]}]\" value=\"$rank[rankid]\">",
			"<input type=\"text\" class=\"txt\" size=\"12\" name=\"ranktitlenew[{$rank[rankid]}]\" value=\"$rank[ranktitle]\">",
			"<input type=\"text\" class=\"txt\" size=\"6\" name=\"postshighernew[{$rank[rankid]}]\" value=\"$rank[postshigher]\">",
			"<input type=\"text\" class=\"txt\" size=\"2\"name=\"starsnew[{$rank[rankid]}]\" value=\"$rank[stars]\">",
			"<input type=\"text\" class=\"txt\" size=\"6\"name=\"colornew[{$rank[rankid]}]\" value=\"$rank[color]\">",
		));
	}

	echo '<tr><td></td><td colspan="4"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['usergroups_level_add'].'</a></div></td></tr>';
	showsubmit('ranksubmit', 'submit', 'del');
	showtablefooter();
	showformfooter();

} else {

	if($delete) {
		$ids = implode('\',\'', $delete);
		$db->query("DELETE FROM {$tablepre}ranks WHERE rankid IN ('$ids')");
	}

	foreach($ranktitlenew as $id => $value) {
		$db->query("UPDATE {$tablepre}ranks SET ranktitle='$ranktitlenew[$id]', postshigher='$postshighernew[$id]', stars='$starsnew[$id]', color='$colornew[$id]' WHERE rankid='$id'");
	}

	if(is_array($newranktitle)) {
		foreach($newranktitle as $key => $value) {
			if($value = trim($value)) {
				$db->query("INSERT INTO {$tablepre}ranks (ranktitle, postshigher, stars, color)
					VALUES ('$value', '$newpostshigher[$key]', '$newstars[$key]', '$newcolor[$key]')");
			}
		}
	}

	updatecache('ranks');
	cpmsg('ranks_succeed', $BASESCRIPT.'?action=ranks', 'succeed');
}

?>