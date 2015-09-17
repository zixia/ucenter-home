<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: advertisements.inc.php 21075 2009-11-11 02:05:08Z liuqiang $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$advarray = array();
if(!empty($_DCACHE['advs'])) {
	$advs = CURSCRIPT == 'index' && !empty($gid) ? (array)$_DCACHE['advs']['cat'] : $_DCACHE['advs']['type'];
	$advitems = $_DCACHE['advs']['items'];
	if(in_array(CURSCRIPT, array('forumdisplay', 'viewthread')) && !empty($fid)) {
		$thisgid = $forum['type'] == 'forum' ? $forum['fup'] : $_DCACHE['forums'][$forum['fup']]['fup'];
		foreach($advs AS $type => $advitem) {
			if($advitem = array_unique(array_merge((!empty($advitem['forum_'.$fid]) ? $advitem['forum_'.$fid] : array()), (!empty($advitem['forum_'.$thisgid]) ? $advitem['forum_'.$thisgid] : array()), (!empty($advitem['forum_all']) ? $advitem['forum_all'] : array())))) {
				if(substr($type, 0, 6) == 'thread') {
					$advarray[substr($type, 0, 7)][substr($type, 8, strlen($type))] = $advitem;
				} else {
					$advarray[$type] = $advitem;
				}
			}
		}
		$advs = $advarray;
	}
	if($globaladvs) {
		foreach($globaladvs['type'] AS $key => $value) {
			if(isset($advs[$key])) {
				$advs[$key] = array_merge($advs[$key], $value);
			} else {
				$advs[$key] = $value;
			}
		}
		$advitems = $advitems + $globaladvs['items'];
	}
	$advarray = $advs;
} else {
	$advarray = $globaladvs['type'];
	$advitems = $globaladvs['items'];
}

foreach($advarray as $advtype => $advcodes) {
        if(substr($advtype, 0, 6) == 'thread') {
                for($i = 1; $i <= $ppp; $i++) {
                        $adv_codes = @array_unique(array_merge((isset($advcodes[$i]) ? $advcodes[$i] : array()), (isset($advcodes[0]) ? $advcodes[0] : array())));
                        $advcount = count($adv_codes);
                        $advlist[$advtype][$i - 1] = $advitems[$advcount > 1 ? $adv_codes[mt_rand(0, $advcount -1)] : $adv_codes[0]];
                }
        } elseif($advtype == 'intercat') {
                $advlist['intercat'] = $advcodes;
        } else {
        	$advcodes = CURSCRIPT == 'index' && !empty($gid) ? $advcodes[$gid] : $advcodes;
        	$advcount = count($advcodes);
        	if($advtype == 'text') {
        		if($advcount > 5) {
        			$minfillpercent = 0;
        			for($cols = 5; $cols >= 3; $cols--) {
        				if(($remainder = $advcount % $cols) == 0) {
        					$advcols = $cols;
        					break;
        				} elseif($remainder / $cols > $minfillpercent)  {
        					$minfillpercent = $remainder / $cols;
        					$advcols = $cols;
        				}
        			}
        		} else {
        			$advcols = $advcount;
        		}

        		$advlist[$advtype] = '';
        		for($i = 0; $i < $advcols * ceil($advcount / $advcols); $i++) {
        			$advlist[$advtype] .= (($i + 1) % $advcols == 1 || $advcols == 1 ? '<tr>' : '').
        				'<td width="'.intval(100 / $advcols).'%">'.(isset($advcodes[$i]) ? $advitems[$advcodes[$i]] : '&nbsp;').'</td>'.
        				(($i + 1) % $advcols == 0 ? "</tr>\n" : '');
        		}
        	} else {
        		$advlist[$advtype] = $advitems[$advcount > 1 ? $advcodes[mt_rand(0, $advcount - 1)] : $advcodes[0]];
        	}
        }
}

unset($_DCACHE['advs'], $advs, $advarray);
if(empty($advlist['intercat'])) {
	unset($advitems);
}

?>