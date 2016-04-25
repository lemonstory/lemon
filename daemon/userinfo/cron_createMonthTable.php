<?php
/*
 * 每月执行一次，创建下个月的分表
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class cron_createMonthTable extends DaemonBase {
    protected $processnum = 1;
    protected $isWhile = false;
	protected function deal() {
	    $nextmonth = date("Ym", strtotime("+1 month"));
	    
	    // 创建user_imsi_action_log分表
	    $db = DbConnecter::connectMysql("share_main");
	    $sql = "CREATE TABLE `user_imsi_action_log_{$nextmonth}` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `uimid` int(11) unsigned NOT NULL DEFAULT '0',
          `actionid` varchar(100) NOT NULL DEFAULT '',
          `actiontype` varchar(100) NOT NULL DEFAULT '',
          `addtime` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `uimid` (`uimid`) USING BTREE,
          KEY `addtime` (`addtime`) USING BTREE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
	    $st = $db->prepare($sql);
	    $st->execute();
	    exit();
	}

	protected function checkLogPath() {}

}
new cron_createMonthTable ();