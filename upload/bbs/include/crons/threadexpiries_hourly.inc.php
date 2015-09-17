<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: threadexpiries_hourly.inc.php 21052 2009-11-09 10:12:34Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$actionarray = array();
$query = $db->query("SELECT * FROM {$tablepre}threadsmod WHERE expiration>'0' AND expiration<'$timestamp' AND status='1'");
while($expiry = $db->fetch_array($query)) {
	$threads[] = $expiry;
	switch($expiry['action']) {
		case 'EST':	$actionarray['UES'][] = $expiry['tid']; break;
		case 'EHL':	$actionarray['UEH'][] = $expiry['tid'];	break;
		case 'ECL':	$actionarray['UEC'][] = $expiry['tid'];	break;
		case 'EOP':	$actionarray['UEO'][] = $expiry['tid'];	break;
		case 'EDI':	$actionarray['UED'][] = $expiry['tid'];	break;
		case 'TOK':	$actionarray['UES'][] = $expiry['tid']; break;
		case 'CCK':	$actionarray['UEH'][] = $expiry['tid'];	break;
		case 'CLK':	$actionarray['UEC'][] = $expiry['tid']; break;
		case 'SPA':	$actionarray['SPD'][] = $expiry['tid']; break;
	}
}

if($actionarray) {

	foreach($actionarray as $action => $tids) {

		$tids = implode(',', $tids);

		switch($action) {

			case 'UES':
				$db->query("UPDATE {$tablepre}threads SET displayorder='0' WHERE tid IN ($tids)", 'UNBUFFERED');
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($tids) AND action IN ('EST', 'TOK')", 'UNBUFFERED');

				require_once DISCUZ_ROOT.'./include/cache.func.php';
				updatecache('globalstick');
				break;

			case 'UEH':
				$db->query("UPDATE {$tablepre}threads SET highlight='0' WHERE tid IN ($tids)", 'UNBUFFERED');
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($tids) AND action IN ('EHL', 'CCK')", 'UNBUFFERED');
				break;

			case 'UEC':
			case 'UEO':
				$closed = $action == 'UEO' ? 1 : 0;
				$db->query("UPDATE {$tablepre}threads SET closed='$closed' WHERE tid IN ($tids)", 'UNBUFFERED');
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($tids) AND action IN ('EOP', 'ECL', 'CLK')", 'UNBUFFERED');
				break;

			case 'UED':
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($tids) AND action='EDI'", 'UNBUFFERED');

				$digestarray = $authoridarry = array();
				$query = $db->query("SELECT authorid, digest FROM {$tablepre}threads WHERE tid IN ($tids)");
				while($digest = $db->fetch_array($query)) {
					$authoridarry[] = $digest['authorid'];
					$digestarray[$digest['digest']][] = $digest['authorid'];
				}
				$db->query("UPDATE {$tablepre}members SET digestposts=digestposts+'-1' WHERE uid IN (".implode(',', $authoridarry).")", 'UNBUFFERED');
				foreach($digestarray as $digest => $authorids) {
					updatecredits(implode('\',\'', $authorids), $creditspolicy['digest'], 0 - $digest);
				}
				$db->query("UPDATE {$tablepre}threads SET digest='0' WHERE tid IN ($tids)", 'UNBUFFERED');
				break;

			case 'SPD':
				$db->query("UPDATE {$tablepre}threads SET ".buildbitsql('status', 5, FALSE)." WHERE tid IN ($tids)", 'UNBUFFERED');
				$db->query("UPDATE {$tablepre}threadsmod SET status='0' WHERE tid IN ($tids) AND action IN ('SPA')", 'UNBUFFERED');
				break;

		}
	}

	require_once DISCUZ_ROOT.'./include/post.func.php';

	foreach($actionarray as $action => $tids) {
		updatemodlog(implode(',', $tids), $action, 0, 1);
	}

}

?>