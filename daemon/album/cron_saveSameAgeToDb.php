<?php
/*
 * 每半小时执行一次，生成同龄在听专辑数据
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class cron_saveSameAgeToDb extends DaemonBase {
    protected $isWhile = false;
	protected function deal() {
	    $cronobj = new Cron();
	    $cronobj->cronSaveSameAgeToDb();
	}

	protected function checkLogPath() {}

}
new cron_saveSameAgeToDb ();