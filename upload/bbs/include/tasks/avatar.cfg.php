<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: avatar.cfg.php 16697 2008-11-14 07:36:51Z monkey $
*/

	$task_name = $tasklang['avatar_name'];

	$task_description = $tasklang['avatar_desc'];

	$task_icon = '';

	$task_period = '';

	$task_conditions = array(
		array('sort' => 'apply', 'name' => $tasklang['avatar_apply_var_name_noavatar'], 'description' => $tasklang['avatar_apply_var_desc_noavatar'], 'variable' => '', 'value' => '', 'type' => '', 'extra' => ''),
		array('sort' => 'complete', 'name' => $tasklang['avatar_complete_var_name_uploadavatar'], 'description' => $tasklang['avatar_complete_var_desc_uploadavatar'], 'variable' => '', 'value' => '', 'type' => '', 'extra' => '')
	);

	$task_version = '1.0';

	$task_copyright = $task_copyright = $tasklang['avatar_copyright'];

?>