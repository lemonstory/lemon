<?php
/*
 * 守护进程，处理用户或设备的行为日志
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class deal_userImsiActionLog extends DaemonBase {
    protected $processnum = 1;
	protected function deal() {
	    $queuevalue = MnsQueueManager::popActionLogQueue();
	    if (empty($queuevalue)) {
	        sleep(10);
	        return true;
	    }
	    
	    $queuearr = explode("@@", $queuevalue);
	    $uimid = $queuearr[0];
	    $actionid = $queuearr[1];
	    $actiontype = $queuearr[2];
	    $addtime = $queuearr[3];
	    if (empty($uimid) || empty($actionid) || empty($actiontype)) {
	        return true;
	    }
	    
	    // 记录user_imsi_action_log
	    $actionlogobj = new ActionLog();
	    $actionlogobj->addUserImsiActionLog($uimid, $actionid, $actiontype, $addtime);
	}

	protected function checkLogPath() {}

}
new deal_userImsiActionLog ();