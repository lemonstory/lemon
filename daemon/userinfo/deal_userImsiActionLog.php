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
	    
	    $queuearr = explode(":", $queuevalue);
	    $actionid = $queuearr[0];
	    $actiontype = $queuearr[1];
	    if (empty($actionid) || empty($actiontype)) {
	        return true;
	    }
	    
	    
	    $actionlogobj = new ActionLog();
	    if ($actiontype == $actionlogobj->ACTION_TYPE_LOGIN) {
	        // 记录登录日志
	        $uid = $actionid;
	        $loginlogobj = new UserLoginLog();
	        $loginlogobj->addUserLoginLog($uid, $imsi);
	        
	    }
	    
	    $actionlogobj->addUserImsiActionLog($uimid, $actionid, $actiontype);
	}

	protected function checkLogPath() {}

}
new deal_userImsiActionLog ();