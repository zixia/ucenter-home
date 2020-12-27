<?php

$requestlang = array
(
	'assistant_name' => '我的助手',
	'assistant_desc' => '我的助手，适用于边栏，显示当前用户的信息以及常用链接',

	'birthday_name' => '今日生日会员',
	'birthday_desc' => '显示今日生日会员的头像',
	'birthday_limit' => '显示会员数',
	'birthday_limit_comment' => '设置今天过生日的最大会员人数',

	'forumtree_name' => '版块树形列表',
	'forumtree_desc' => '树形显示版块列表',

	'html_name' => '自由代码',
	'html_desc' => '本模块可自由书写 HTML、Discuz 代码',
	'html_type' => '代码格式',
	'html_type_comment' => '选择代码的格式，如果您对 HTML 知识了解的不多，可以选择 Discuz! 代码格式',
	'html_type_html' => 'HTML 代码',
	'html_type_code' => 'Discuz! 代码',
	'html_code' => '代码内容',
	'html_code_comment' => '按照您选定的代码格式输入相应的代码',
	'html_side' => '用于边栏',
	'html_side_comment' => '如果本模块用于边栏，请选择此项',

	'modlist_name' => '版块版主排行',
	'modlist_desc' => '列出当前版块的版主，主题列表页面(forumdisplay.php)专用模块<br />开启“论坛管理工作统计”时将按照管理工作次数进行排行，否则将按照版主的发帖数进行排行',

	'rowcombine_title' => '模块标题',
	'rowcombine_title_comment' => '如果聚合的模块是同类数据，可在此处命名一个通用的名称作为标题显示',
	'rowcombine_name' => '横向聚合模块',
	'rowcombine_desc' => '横向聚合显示多个模块',
	'rowcombine_data' => '聚合模块',
	'rowcombine_data_comment' => '一行一个模块，逗号前为模块名称，逗号后为显示名称<br />如:<br />边栏模块_最新主题,最新主题<br />边栏模块_最新回复,最新回复',

	'tag_name' => '标签',
	'tag_desc' => '标签调用，返回指定数目的标签',
	'tag_type' => '标签获取方式',
	'tag_type_comment' => '设置标签数据的获取方式',
	'tag_type_0' => '随机显示标签',
	'tag_type_1' => '显示热门标签',
	'tag_type_limit' => '返回条目数',
	'tag_type_limit_comment' => '设置返回的条目数',

	'thread_name' => '主题帖内容',
	'thread_desc' => '主题帖内容调用，输入主题 ID(TID)即可，特殊主题也会依照其自己的风格显示',
	'thread_id' => '主题 ID',

	'google_name' => 'Google 搜索',
	'google_desc' => 'Google 搜索框',
	'google_lang' => '搜索网页的语言',
	'google_lang_comment' => '设置适合自己论坛的网页语言可以有效的提高搜索结果的质量',
	'google_lang_any' => '任何语言',
	'google_lang_en' => '英文',
	'google_lang_zh-CN' => '简体中文',
	'google_lang_zh-TW' => '繁体中文',
	'google_default' => '默认搜索选择',
	'google_default_comment' => '搜索框默认选择的项目',
	'google_default_0' => '网页搜索',
	'google_default_1' => '站内搜索',

	'feed_name' => 'UCHome 动态调用模块',
	'feed_desc' => '调用 UCHome 中的用户动态',
	'feed_title' => '模块标题',
	'feed_title_comment' => '模块在侧边栏显示的标题',
	'feed_title_value' => '最新成员',
	'feed_uids' => '指定用户 UID',
	'feed_uids_comment' => '多个 UID 请用半角逗号 "," 隔开',
	'feed_friend' => '动态类型',
	'feed_friend_nolimit' => '不限制',
	'feed_friend_friendonly' => '只获取好友',
	'feed_start' => '起始数据行数',
	'feed_start_comment' => '如需设定起始的数据行数，请输入具体数值，0 为从第一行开始，以此类推',
	'feed_limit' => '显示数据条数',
	'feed_limit_comment' => '设置一次显示的主题条目数，请设置为大于 0 的整数',
	'feed_template' => '单条显示模板',
	'feed_template_comment' => '<div class="extcredits">
		<a href="###" onclick="insertunit(\'{iconurl}\', \'parameter[settings][template]\')">{iconurl}</a>代表 动态类型图标
		<a href="###" onclick="insertunit(\'{username}\', \'parameter[settings][template]\')">{username}</a>代表 用户名<br />
		<a href="###" onclick="insertunit(\'{photo}\', \'parameter[settings][template]\')">{photo}</a>代表 用户头像地址<br />
		<a href="###" onclick="insertunit(\'{title_template}\', \'parameter[settings][template]\')">{title_template}</a>代表 动态标题
		<a href="###" onclick="insertunit(\'{userlink}\', \'parameter[settings][template]\')">{userlink}</a>代表 用户个人主页地址<br />
		<a href="###" onclick="insertunit(\'{body_template}\', \'parameter[settings][template]\')">{body_template}</a>代表 动态内容
		<a href="###" onclick="insertunit(\'{dateline}\', \'parameter[settings][template]\')">{dateline}</a>代表 创建时间<br />
		</div>',

	'doing_name' => 'UCHome 记录调用模块',
	'doing_desc' => '调用 UCHome 中的记录',
	'doing_title' => '模块标题',
	'doing_title_comment' => '模块在侧边栏显示的标题',
	'doing_title_value' => '最新记录',
	'doing_uids' => '指定用户 UID',
	'doing_uids_comment' => '多个 UID 请用半角逗号 "," 隔开',
	'doing_mood' => '记录类型',
	'doing_mood_nolimit' => '不限制',
	'doing_mood_moodonly' => '只获取心情记录',
	'doing_start' => '起始数据行数',
	'doing_start_comment' => '如需设定起始的数据行数，请输入具体数值，0 为从第一行开始，以此类推',
	'doing_limit' => '显示数据条数',
	'doing_limit_comment' => '设置一次显示的主题条目数，请设置为大于 0 的整数',
	'doing_template' => '单条显示模板',
	'doing_template_comment' => '<div class="extcredits">
		<a href="###" onclick="insertunit(\'{username}\', \'parameter[settings][template]\')">{username}</a>代表 用户名
		<a href="###" onclick="insertunit(\'{photo}\', \'parameter[settings][template]\')">{photo}</a>代表 用户头像地址<br />
		<a href="###" onclick="insertunit(\'{userlink}\', \'parameter[settings][template]\')">{userlink}</a>代表 用户个人主页地址<br />
		<a href="###" onclick="insertunit(\'{replynum}\', \'parameter[settings][template]\')">{replynum}</a>代表 回复数
		<a href="###" onclick="insertunit(\'{link}\', \'parameter[settings][template]\')">{link}</a>代表 记录地址<br />
		<a href="###" onclick="insertunit(\'{message}\', \'parameter[settings][template]\')">{message}</a>代表 记录内容
		<a href="###" onclick="insertunit(\'{dateline}\', \'parameter[settings][template]\')">{dateline}</a>代表 创建时间<br />
		</div>',
	
	'app_name' => 'UCHome 应用调用模块',
	'app_desc' => '调用 UCHome 中的应用列表',
	'app_title' => '模块标题',
	'app_title_comment' => '模块在侧边栏显示的标题',
	'app_title_value' => '应用列表',
	'app_uids' => '指定用户 UID',
	'app_uids_comment' => '多个 UID 请用半角逗号 "," 隔开',
	'app_type' => '应用类型',
	'app_type_nolimit' => '不限制',
	'app_type_default' => '只获取默认',
	'app_type_userapp' => '只用户自已的应用',
	'app_start' => '起始数据行数',
	'app_start_comment' => '如需设定起始的数据行数，请输入具体数值，0 为从第一行开始，以此类推',
	'app_limit' => '显示数据条数',
	'app_limit_comment' => '设置一次显示的应用条目数，请设置为大于 0 的整数，该条件对默认应用无效',
	'app_template' => '单条显示模板',
	'app_template_comment' => '<div class="extcredits">
		<a href="###" onclick="insertunit(\'{icon}\', \'parameter[settings][template]\')">{icon}</a>代表 应用小图标
		<a href="###" onclick="insertunit(\'{link}\', \'parameter[settings][template]\')">{link}</a>代表 应用地址<br />
		<a href="###" onclick="insertunit(\'{appname}\', \'parameter[settings][template]\')">{appname}</a>代表 应用名称<br />
		</div>',


	'space_name' => 'UCHome 成员调用模块',
	'space_desc' => '调用 UCHome 中的用户',
	'space_title' => '模块标题',
	'space_title_comment' => '模块在侧边栏显示的标题',
	'space_title_value' => '最新成员',
	'space_uids' => '指定用户 UID',
	'space_uids_comment' => '多个 UID 请用半角逗号 "," 隔开',
	'space_startfriendnum' => '空间好友数起始值',
	'space_endfriendnum' => '空间好友数结束值',
	'space_startviewnum' => '空间访问数起始值',
	'space_endviewnum' => '空间访问数结束值',
	'space_startcredit' => '积分起始值',
	'space_endcredit' => '积分结束值',
	'space_avatar' => '上传头像',
	'space_avatar_comment' => '用户是否上传过头像',
	'space_avatar_nolimit' => '不限制',
	'space_avatar_noexists' => '未上传',
	'space_avatar_exists' => '已上传',
	'space_namestatus' => '实名认证',
	'space_namestatus_comment' => '获取是否通过实名认证的用户',
	'space_namestatus_nolimit' => '不限制',
	'space_namestatus_nopass' => '未通过',
	'space_namestatus_pass' => '已通过',
	'space_dateline' => '建立时间',
	'space_dateline_comment' => '空间创建时间',
	'space_updatetime' => '更新时间',
	'space_updatetime_comment' => '空间更新时间',
	'space_order' => '排序类型',
	'space_order_comment' => '数据排序类型',
	'space_orderselect' => array(
		array('', '默认顺序'),
		array('dateline', '建立时间'),
		array('updatetime', '更新时间'),
		array('viewnum', '空间访问数'),
		array('friendnum', '空间好友数'),
		array('credit', '成员积分')
	),
	'space_dateselect' => array(
		array('0' , '不限制'),
		array('86400' , '一天以来'),
		array('172800' , '两天以来'),
		array('604800' , '一周以来'),
		array('1209600' , '两周以来'),
		array('2592000' , '一个月以来'),
		array('7948800' , '三个月以来'),
		array('15897600' , '六个月以来'),
		array('31536000' , '一年以来')
	),
	'space_sc' => '排序方式',
	'space_sc_comment' => '返回记录的排序方式',
	'space_sc_asc' => '递增',
	'space_sc_desc' => '递减',
	'space_start' => '起始数据行数',
	'space_start_comment' => '如需设定起始的数据行数，请输入具体数值，0 为从第一行开始，以此类推',
	'space_limit' => '显示数据条数',
	'space_limit_comment' => '设置一次显示的主题条目数，请设置为大于 0 的整数',
	'space_template' => '单条显示模板',
	'space_template_comment' => '<div class="extcredits">
		<a href="###" onclick="insertunit(\'{username}\', \'parameter[settings][template]\')">{username}</a>代表 用户名
		<a href="###" onclick="insertunit(\'{photo}\', \'parameter[settings][template]\')">{photo}</a>代表 用户头像地址<br />
		<a href="###" onclick="insertunit(\'{viewnum}\', \'parameter[settings][template]\')">{viewnum}</a>代表 查看数
		<a href="###" onclick="insertunit(\'{credit}\', \'parameter[settings][template]\')">{credit}</a>代表 积分<br />
		<a href="###" onclick="insertunit(\'{userlink}\', \'parameter[settings][template]\')">{userlink}</a>代表 用户个人主页地址
		<a href="###" onclick="insertunit(\'{friendnum}\', \'parameter[settings][template]\')">{friendnum}</a>代表 用户好友数<br />
		<a href="###" onclick="insertunit(\'{updatetime}\', \'parameter[settings][template]\')">{updatetime}</a>代表 更新时间
		<a href="###" onclick="insertunit(\'{dateline}\', \'parameter[settings][template]\')">{dateline}</a>代表 创建时间<br />
	</div>',

);

?>