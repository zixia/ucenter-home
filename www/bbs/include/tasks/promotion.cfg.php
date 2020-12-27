<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: promotion.cfg.php 16697 2008-11-14 07:36:51Z monkey $
*/

	$task_name = $tasklang['promotion_name'];

	$task_description = $tasklang['promotion_desc'];

	$task_icon = '';

	$task_period = '';

	$task_conditions = array(
		array('sort' => 'complete', 'name' => $tasklang['promotion_complete_var_name_iplimit'], 'description' => $tasklang['promotion_complete_var_desc_iplimit'], 'variable' => 'num', 'value' => '100', 'type' => 'number', 'extra' => ''),
	);

	$task_version = '1.0';

	$task_copyright = $tasklang['promotion_copyright'];

?>