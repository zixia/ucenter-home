<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: quickqueries.inc.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
        exit('Access Denied');
}

$simplequeries = array(
	array('comment' => '快速开启论坛版块功能', 'sql' => ''),
	array('comment' => '开启 所有版块 主题回收站', 'sql' => 'UPDATE {tablepre}forums SET recyclebin=\'1\''),
	array('comment' => '开启 所有版块 Discuz! 代码”', 'sql' => 'UPDATE {tablepre}forums SET allowbbcode=\'1\''),
	array('comment' => '开启 所有版块 [IMG] 代码”', 'sql' => 'UPDATE {tablepre}forums SET allowimgcode=\'1\''),
	array('comment' => '开启 所有版块 Smilies 代码', 'sql' => 'UPDATE {tablepre}forums SET allowsmilies=\'1\''),
	array('comment' => '开启 所有版块 内容干扰码', 'sql' => 'UPDATE {tablepre}forums SET jammer=\'1\''),
	array('comment' => '开启 所有版块 允许匿名发贴”', 'sql' => 'UPDATE {tablepre}forums SET allowanonymous=\'1\''),

	array('comment' => '快速关闭论坛版块功能', 'sql' => ''),
	array('comment' => '关闭 所有版块 主题回收站', 'sql' => 'UPDATE {tablepre}forums SET recyclebin=\'0\''),
	array('comment' => '关闭 所有版块 HTML 代码', 'sql' => 'UPDATE {tablepre}forums SET allowhtml=\'0\''),
	array('comment' => '关闭 所有版块 Discuz! 代码', 'sql' => 'UPDATE {tablepre}forums SET allowbbcode=\'0\''),
	array('comment' => '关闭 所有版块 [IMG] 代码', 'sql' => 'UPDATE {tablepre}forums SET allowimgcode=\'0\''),
	array('comment' => '关闭 所有版块 Smilies 代码', 'sql' => 'UPDATE {tablepre}forums SET allowsmilies=\'0\''),
	array('comment' => '关闭 所有版块 内容干扰码', 'sql' => 'UPDATE {tablepre}forums SET jammer=\'0\''),
	array('comment' => '关闭 所有版块 允许匿名发贴', 'sql' => 'UPDATE {tablepre}forums SET allowanonymous=\'0\''),

	array('comment' => '会员操作相关', 'sql' => ''),
	array('comment' => '清除 所有会员 自定义风格', 'sql' => 'UPDATE {tablepre}members SET styleid=\'0\''),
	array('comment' => '清空 所有会员 积分交易记录', 'sql' => 'TRUNCATE {tablepre}creditslog;'),
	array('comment' => '清空 所有会员 收藏夹', 'sql' => 'TRUNCATE {tablepre}favorites;'),
);

?>