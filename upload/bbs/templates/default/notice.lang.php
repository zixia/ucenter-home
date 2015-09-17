<?php

$language = array
(

	'reason_moderate' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> {$modaction} {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reason_merge' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> {$modaction} {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reason_delete_post' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> {$modaction} {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reason_ban_post' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> {$modaction} {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reason_warn_post' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> {$modaction} {time}<br />
连续 $warningexpiration 天内累计 $warninglimit 次警告，您将被自动禁止发帖 $warningexpiration 天。<br />
截至目前，您已被警告 $authorwarnings 次，请注意！
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reason_move' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 移动到 <a href=\"{boardurl}forumdisplay.php?from=notice&fid={$toforum[fid]}\">{$toforum[name]}</a> {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reason_copy' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 复制为 <a href=\"{boardurl}viewthread.php?from=notice&tid=$threadid\">{$thread[subject]}</a> {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reason_stamp_update' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 添加了图章 {$_DCACHE[stamps][$stamp][text]} {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reason_stamp_delete' => '<div class=\"f_manage\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 撤销了图章 {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'modthreads_delete' => '<div class=\"f_manage\">您发表的主题 {$threadsubject} 没有通过审核，现已被删除！ {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'modthreads_validate' => '<div class=\"f_manage\">您发表的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$tid}\">{$threadsubject}</a> 已经审核通过！ {time}
<a href=\"{boardurl}viewthread.php?from=notice&tid={$tid}\" class=\"il to\">查看</a>
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'modreplies_delete' => '<div class=\"f_manage\">您发表回复没有通过审核，现已被删除！ {time}
<dl class=\"summary\"><dt>回复内容：</dt><dd>$post</dd></dl>
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'modreplies_validate' => '<div class=\"f_manage\">您发表的回复已经审核通过！ {time}
<a href=\"{boardurl}viewthread.php?from=notice&tid={$tid}\" class=\"il to\">查看</a>
<dl class=\"summary\"><dt>回复内容：</dt><dd>$post</dd></dl>
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'reportpost' => '<div><a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 向您报告 {time}
<a href=\"{boardurl}{$posturl}\" class=\"il to\">查看</a>
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'transfer' => '<div class=\"f_credit\">您收到一笔来自 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 的积分转账 {$extcredits[$creditstrans][title]} {$netamount} {$extcredits[$creditstrans][unit]} {time}
<a href=\"{boardurl}memcp.php?from=notice&action=creditslog\" class=\"il to\">查看</a>
<fieldset><ins>{$transfermessage}</ins></fieldset></div>',

	'addfunds' => '<div class=\"f_credit\">您提交的积分充值请求已成功完成，相应数额的积分已经存入您的积分账户 {time}
<a href=\"{boardurl}memcp.php?from=notice&action=creditslog\" class=\"il to\">查看</a>
<dl class=\"summary\"><dt>订单号：</dt><dd>{$order[orderid]}<dt>支出：</dt><dd>人民币 {$order[price]} 元</dd><dt>收入：</dt><dd>{$extcredits[$creditstrans][title]} {$order[amount]} {$extcredits[$creditstrans][unit]}</dd></dl></div>',

	'rate_reason' => '<div class=\"f_rate\">您的主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 评分 {$ratescore} {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'rate_removereason' => '<div class=\"f_rate\"><a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 撤销了对您主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 的评分 {$ratescore} {time}
<fieldset><ins>{$reason}</ins></fieldset></div>',

	'trade_seller_send' => '<div class=\"f_trade\"><a href=\"{boardurl}space.php?from=notice&uid={$userid}\">{$user}</a> 购买您的商品 <a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\">{$itemsubject}</a>，对方已经付款，等待您发货 {time}
<a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\" class=\"il to\">查看</a></div>',

	'trade_buyer_confirm' => '<div class=\"f_trade\">您购买的商品 <a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\">{$itemsubject}</a>，<a href=\"{boardurl}space.php?from=notice&uid={$userid}\">{$user}</a> 已发货，等待您确认 {time}
<a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\" class=\"il to\">查看</a></div>',

	'trade_fefund_success' => '<div class=\"f_trade\">商品 <a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\">{$itemsubject}</a> 已退款成功 {time}
<a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\" class=\"il to\">评价</a></div>',

	'trade_success' => '<div class=\"f_trade\">商品 <a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\">{$itemsubject}</a> 已交易成功 {time}
<a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\" class=\"il to\">评价</a></div>',

	'eccredit' => '<div class=\"f_trade\">与您交易的 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 已经给您作了评价 {time}
<a href=\"{boardurl}trade.php?from=notice&orderid={$orderid}\" class=\"il to\">回评</a></div>',

	'activity_apply' => '<div class=\"f_activity\">活动 <a href=\"{boardurl}viewthread.php?from=notice&tid={$tid}\">{$activity_subject}</a> 的发起者已批准您参加此活动 {time}
<a href=\"{boardurl}viewthread.php?from=notice&tid={$tid}\" class=\"il to\">查看</a></div>',

	'activity_delete' => '<div class=\"f_activity\">活动 <a href=\"{boardurl}viewthread.php?from=notice&tid={$tid}\">{$activity_subject}</a> 的发起者拒绝您参加此活动 {time}
<a href=\"{boardurl}viewthread.php?from=notice&tid={$tid}\" class=\"il to\">查看</a></div>',

	'reward_question' => '<div class=\"f_reward\">您的悬赏主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 设置了最佳答案 {time}
<a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\" class=\"il to\">查看</a></div>',

	'reward_bestanswer' => '<div class=\"f_reward\">您的回复被的悬赏主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 的作者 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 选为悬赏最佳答案 {time}
<a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\" class=\"il to\">查看</a></div>',

	'favoritethreads_notice' => '<div class=\"f_thread\">{actor}回复了您关注的主题 <a href=\"{boardurl}redirect.php?from=notice&goto=findpost&pid={$pid}&ptid={$thread[tid]}\">{$thread[subject]}</a> {time}
<a href=\"{boardurl}redirect.php?from=notice&goto=findpost&pid={$pid}&ptid={$thread[tid]}\" class=\"il to\">查看</a>
<dfn><a href=\"my.php?from=notice&item=attention&action=remove&tid={$thread[tid]}\" onclick=\"ajaxmenu(this, 3000);doane(event);\" class=\"deloption\" title=\"取消提醒\">取消提醒</a></dfn></div>',

	'repquote_noticeauthor' => '<div class=\"f_quote\"><a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 引用了您曾经在主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 发表的帖子 {time}
<dl class=\"summary\"><dt>您的帖子：<dt><dd>{$noticeauthormsg}</dd><dt><a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 说：</dt><dd>{$postmsg}</dd></dl>
<p><a href=\"{boardurl}post.php?from=notice&action=reply&fid={$fid}&tid={$thread[tid]}&reppost={$pid}\">回复</a><i>|</i><a href=\"{boardurl}redirect.php?from=notice&goto=findpost&pid={$pid}&ptid={$thread[tid]}\">查看</a></p></div>',

	'reppost_noticeauthor' => '<div class=\"f_reply\"><a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 答复了您曾经在主题 <a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\">{$thread[subject]}</a> 发表的帖子 {time}
<dl class=\"summary\"><dt>您的帖子：<dt><dd>{$noticeauthormsg}</dd><dt><a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 说：</dt><dd>{$postmsg}</dd></dl>
<p><a href=\"{boardurl}post.php?from=notice&action=reply&fid={$fid}&tid={$thread[tid]}&reppost={$pid}\">回复</a><i>|</i><a href=\"{boardurl}redirect.php?from=notice&goto=findpost&pid={$pid}&ptid={$thread[tid]}\">查看</a></p></div>',

	'magics_sell' => '<div class=\"f_magic\">您的道具 {$magic[name]} 被 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 购买，获得收益 {$totalcredit} {time}</div>',

	'magics_receive' => '<div class=\"f_magic\">您收到 <a href=\"{boardurl}space.php?from=notice&uid={$discuz_uid}\">{$discuz_userss}</a> 送给您的道具 {$magicarray[$magicid][name]} {time}
<fieldset><ins>{$givemessage}</ins></fieldset>
<p><a href=\"{boardurl}magic.php\">回赠道具</a><i>|</i><a href=\"{boardurl}magic.php?from=notice&action=mybox\" class=\"to\">去我的道具箱</a></p></div>',

	'magic_thread' => '<div class=\"f_magic\">你的帖子 {$thread[subject]} 被 <a href=\"{boardurl}space.php?from=notice&uid=$discuz_uid\">{$discuz_user}</a> 使用了 {$magic[name]} {time}
<a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\" class=\"il to\">快去看看吧！</a></div>',

	'magic_thread_anonymous' => '<div class=\"f_magic\">你的帖子 {$thread[subject]} 被其他人使用了 {$magic[name]} {time}
<a href=\"{boardurl}viewthread.php?from=notice&tid={$thread[tid]}\" class=\"il to\">快去看看吧！</a></div>',

	'magic_user' => '<div class=\"f_magic\">{$discuz_user} 对你使用了 {$magic[name]} {time}
<a href=\"{boardurl}space.php?from=notice&uid=$discuz_uid]\" class=\"il to\">快去看看吧！</a></div>',

	'magic_user_anonymous' => '<div class=\"f_magic\">你被其他人使用了 {$magic[name]}！ {time}</div>',

	'buddy_new' => '<div class=\"f_buddy\"><a href=\"{boardurl}space.php?from=notice&uid=$discuz_uid\">{$discuz_userss}</a> 添加您为好友 {time}
<a href=\"{boardurl}my.php?from=notice&item=buddylist&newbuddyid={$discuz_uid}&buddysubmit=yes\" class=\"il to\" onclick=\"ajaxmenu(this, 3000);doane(event);\">加 {$discuz_userss} 为好友</a></div>',

	'buddy_new_uch' => '<div class=\"f_buddy\"><a href=\"{boardurl}space.php?from=notice&uid=$discuz_uid\">{$discuz_userss}</a> 添加您为好友 {time}
<p><a href=\"{boardurl}my.php?from=notice&item=buddylist&newbuddyid={$discuz_uid}&buddysubmit=yes\" onclick=\"ajaxmenu(this, 3000);doane(event);\">加 {$discuz_userss} 为好友</a><i>|</i>
<a href=\"{$uchomeurl}/space.php?from=notice&uid={$discuz_uid}\" class=\"to\">查看 {$discuz_userss} 的个人空间</a></p></div>',

	'task_reward_credit' => '<div class=\"f_task\">恭喜您完成任务：<a href=\"{boardurl}task.php?from=notice&action=view&id={$task[taskid]}\">{$task[name]}</a>，获得积分 {$extcredits[$task[prize]][title]} {$task[bonus]} {$extcredits[$task[prize]][unit]} {time}
<p><a href=\"{boardurl}memcp.php?from=notice&action=credits\">查看我的积分</a><i>|</i><a href=\"{boardurl}memcp.php?from=notice&action=creditslog&operation=creditslog\" class=\"il to\">查看积分收益记录</a></p></div>',

	'task_reward_magic' => '<div class=\"f_task\">恭喜您完成任务：<a href=\"{boardurl}task.php?from=notice&action=view&id={$task[taskid]}\">{$task[name]}</a>，获得道具 <a href=\"{boardurl}magic.php\">{$magicname}</a> {$task[bonus]} 枚 {time}</div>',

	'task_reward_medal' => '<div class=\"f_task\">恭喜您完成任务：<a href=\"{boardurl}task.php?from=notice&action=view&id={$task[taskid]}\">{$task[name]}</a>，获得勋章 <a href=\"{boardurl}medal.php\">{$medalname}</a> 有效期 {$task[bonus]} 天 {time}</div>',

	'task_reward_invite' => '<div class=\"f_task\">恭喜您完成任务：<a href=\"{boardurl}task.php?from=notice&action=view&id={$task[taskid]}\">{$task[name]}</a>，获得邀请码 <a href=\"{boardurl}invite.php\">{$task[prize]}</a> 个有效期 {$task[bonus]} 天 {time}
<dl class=\"summary\"><dt>邀请码：</dt><dd>{$rewards}</dd></dl></div>',

	'task_reward_group' => '<div class=\"f_task\">恭喜您完成任务：<a href=\"{boardurl}task.php?from=notice&action=view&id={$task[taskid]}\">{$task[name]}</a>，获得用户组 {$grouptitle} 有效期 {$task[bonus]} 天 {time}
<a href=\"{boardurl}faq.php?from=notice&action=grouppermission\" class=\"il to\">看看我能做什么</a></div>',

	'thread_views' => '<div>您的主题 {subject} 查看数超过了 {count} {time}</div>',

	'thread_replies' => '<div>您的主题 {subject} 回复数超过了 {count} {time}</div>',

	'thread_rate' => '<div>您的主题 {subject} 评分超过了 {count} {time}</div>',

	'post_rate' => '<div>您在 {thread} 的回复评分超过了{count} {time}</div>',

	'user_usergroup' => '<div>您的用户组升级为 {usergroup} {time}
<a href=\"{boardurl}faq.php?from=notice&action=grouppermission\" class=\"il to\">看看我能做什么</a></div>',

	'user_credit' => '<div>您的总积分达到 {count} {time}</div>',

	'user_threads' => '<div>您发表的主题数达到 {count} {time}</div>',

	'user_posts' =>	'<div>您的发帖数达到 {count} {time}</div>',

	'user_digest' => '<div>您的精华贴数达到 {count} {time}</div>',

);

?>