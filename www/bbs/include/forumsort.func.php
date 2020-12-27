<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forumselect.inc.php 20900 2009-10-29 02:49:38Z tiger $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function quicksearch() {
	global $_DTYPE;

	$quicksearch = array();
	foreach($_DTYPE as $optionid => $option) {
		if(in_array($option['type'], array('radio', 'select'))) {
			$quicksearch['option'][$optionid]['title'] =  $option['title'];
			$quicksearch['option'][$optionid]['choices'] =  $option['choices'];
			$quicksearch['option'][$optionid]['identifier'] =  $option['identifier'];
		}
		$quicksearch['search'] .= $option['search'] ? $option['search'] : '';
	}

	return $quicksearch;
}

function sortshowlist($searchoid = 0, $searchvid = 0, $threadids = array(), $searchoption = array(), $selecturladd = array()) {
	global $sdb, $bbname, $tablepre, $_DTYPE, $_DSTYPETEMPLATE, $tpp, $page, $threadmaxpages, $optionvaluelist, $threadlist, $threadcount, $sortid, $filter;

	$searchtitle = $searchvalue = $searchunit = $stemplate = $optionvaluelist = $searchtids = $sortlistarray = $optionide = array();
	$selectadd = $and = $selectsql = '';

	$searchoid = intval($searchoid);
	$searchvid = intval($searchvid);
	$sortid = intval($sortid);

	if($filter == 'sort' && $selecturladd) {
		foreach($_DTYPE as $option) {
			if(in_array($option['type'], array('radio', 'select'))) {
				$optionide[$option['identifier']] = 1;
			}
		}

		$optionadd = "&amp;sortid=$sortid";
		foreach($selecturladd as $fieldname => $value) {
			if($optionide[$fieldname] && $value != 'all') {
				$selectsql .= $and."$fieldname='$value'";
				$and = ' AND ';
			}
		}
	}

	if(!empty($searchoption) && is_array($searchoption)) {
		foreach($searchoption as $optionid => $option) {
			$fieldname = $_DTYPE[$optionid]['identifier'] ? $_DTYPE[$optionid]['identifier'] : 1;
			if($option['value']) {
				if(in_array($option['type'], array('number', 'radio', 'select'))) {
					$option['value'] = intval($option['value']);
					$exp = '=';
					if($option['condition']) {
						$exp = $option['condition'] == 1 ? '>' : '<';
					}
					$sql = "$fieldname$exp'$option[value]'";
				} elseif($option['type'] == 'checkbox') {
					$sql = "$fieldname LIKE '%".(implode("%", $option['value']))."%'";
				} else {
					$sql = "$fieldname LIKE '%$option[value]%'";
				}
				$selectsql .= $and."$sql ";
				$and = 'AND ';
			}
		}
	}

	if($selectsql) {
		$query = $sdb->query("SELECT tid FROM {$tablepre}optionvalue$sortid ".($selectsql ? 'WHERE '.$selectsql : '')."");
		while($thread = $sdb->fetch_array($query)) {
			$searchtids[$thread['tid']] = $thread['tid'];
		}
		$threadids = $searchtids ? $searchtids : '';
	}

	if($threadids && is_array($threadids)) {
		$query = $sdb->query("SELECT tid, optionid, value FROM {$tablepre}typeoptionvars WHERE tid IN (".implodeids($threadids).")");
		while($sortthread = $sdb->fetch_array($query)) {
			$optionid = $sortthread['optionid'];
			if($_DTYPE[$optionid]['subjectshow']) {
				$optionvaluelist[$sortthread['tid']][$_DTYPE[$optionid]['identifier']]['title'] = $_DTYPE[$optionid]['title'];
				$optionvaluelist[$sortthread['tid']][$_DTYPE[$optionid]['identifier']]['unit'] = $_DTYPE[$optionid]['unit'];
				if(in_array($_DTYPE[$optionid]['type'], array('radio', 'checkbox', 'select'))) {
					if($_DTYPE[$optionid]['type'] == 'checkbox') {
						foreach(explode("\t", $sortthread['value']) as $choiceid) {
							$sortthreadlist[$sortthread['tid']][$_DTYPE[$optionid]['title']] .= $_DTYPE[$optionid]['choices'][$choiceid].'&nbsp;';
							$optionvaluelist[$sortthread['tid']][$_DTYPE[$optionid]['identifier']]['value'] .= $_DTYPE[$optionid]['choices'][$choiceid].'&nbsp;';
						}
					} else {
						$sortthreadlist[$sortthread['tid']][$_DTYPE[$optionid]['title']] = $optionvaluelist[$sortthread['tid']][$_DTYPE[$optionid]['identifier']]['value'] = $_DTYPE[$optionid]['choices'][$sortthread['value']];
					}
				} else {
					$sortthreadlist[$sortthread['tid']][$_DTYPE[$optionid]['title']] = $optionvaluelist[$sortthread['tid']][$_DTYPE[$optionid]['identifier']]['value'] = $sortthread['value'];
				}
			}
		}

		if($_DSTYPETEMPLATE && $sortthreadlist) {
			foreach($_DTYPE as $option) {
				if($option['subjectshow']) {
					$searchtitle[] = '/{('.$option['identifier'].')}/e';
					$searchvalue[] = '/\[('.$option['identifier'].')value\]/e';
					$searchunit[] = '/\[('.$option['identifier'].')unit\]/e';
				}
			}
			foreach($sortthreadlist as $tid => $option) {
				$stemplate[$tid] = preg_replace($searchtitle, "showoption('\\1', 'title', '$tid')", $_DSTYPETEMPLATE);
				$stemplate[$tid] = preg_replace($searchvalue, "showoption('\\1', 'value', '$tid')", $stemplate[$tid]);
				$stemplate[$tid] = preg_replace($searchunit, "showoption('\\1', 'unit', '$tid')", $stemplate[$tid]);
			}
		}

		if($searchtids) {
			foreach($threadlist as $thread) {
				if(in_array($thread['tid'], $searchtids) || in_array($thread['displayorder'], array(1, 2, 3, 4))) {
					$resultlist[] = $thread;
				}
			}
			$threadlist = $resultlist ? $resultlist : array();
			$threadcount = count($threadlist);
			$multipage = multi($threadcount, $tpp, $page, "forumdisplay.php?fid=$fid&amp;sortid=$sortid&amp;optionid=$optionid&amp;valueid=$valueid$forumdisplayadd", $threadmaxpages);
		}
	} else {
		$threadlist = array();
		$threadcount = 0;
	}

	$sortlistarray['sortthreadlist'] = $sortthreadlist;
	$sortlistarray['stemplate'] = $stemplate;
	$sortlistarray['thread']['list'] = $threadlist;
	$sortlistarray['thread']['count'] = $threadcount;
	$sortlistarray['thread']['multipage'] = $multipage;

	return $sortlistarray;
}

function showoption($var, $type, $tid) {
	global $optionvaluelist;
	if($optionvaluelist[$tid][$var][$type]) {
		return $optionvaluelist[$tid][$var][$type];
	} else {
		return '';
	}
}

?>