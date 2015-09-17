<?php

function getuidfields() {
	return array(
		'members',
		'memberfields',
		'access',
		'activities',
		'activityapplies',
		'attachments',
		'attachpaymentlog',
		'creditslog',
		'debateposts',
		'debates',
		'favorites',
		'forumrecommend|authorid,moderatorid',
		'invites',
		'magiclog',
		'magicmarket',
		'membermagics',
		'memberspaces',
		'moderators',
		'modworks',
		'onlinetime',
		'orders',
		'paymentlog|uid,authorid',
		'posts|authorid|pid',
		'promotions',
		'ratelog',
		'rewardlog|authorid,answererid',
		'searchindex|uid',
		'spacecaches',
		'threads|authorid|tid',
		'threadsmod',
		'tradecomments|raterid,rateeid',
		'tradelog|sellerid,buyerid',
		'trades|sellerid',
		'validating',
	);
}

function membermerge($olduid, $newuid) {
	global $db, $tablepre;
	$uidfields = getuidfields();
	foreach($uidfields as $value) {
		list($table, $field, $stepfield) = explode('|', $value);
		$fields = !$field ? array('uid') : explode(',', $field);
		foreach($fields as $field) {
			$db->query("UPDATE `{$tablepre}$table` SET `$field`='$newuid' WHERE `$field`='$olduid'");
		}
	}
}

?>