<?php
/*
 * 主页：http://addon.discuz.com/?@ailab
 * 人工智能实验室：Discuz!应用中心十大优秀开发者！
 * 插件定制 联系QQ594941227
 * From www.ailab.cn
 */
 
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$sql = <<<SQL
DROP TABLE IF EXISTS pre_autologin;
DROP TABLE IF EXISTS pre_autologin_log;
SQL;
runquery($sql);
$finish = TRUE;
?>