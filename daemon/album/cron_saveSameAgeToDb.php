<?php
/*
 * 每天执行一次，将收听次数最多的专辑入库到同龄在听表
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