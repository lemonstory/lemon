<?php
/*
 * 守护进程，添加收听记录
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class deal_userListenStory extends DaemonBase {
    protected $processnum = 1;
	protected function deal() {
	    $queuevalue = MnsQueueManager::popListenStoryQueue();
	    if (empty($queuevalue)) {
	        sleep(10);
	        return true;
	    }
	    $queuearr = explode("@@", $queuevalue);
	    $uimid = $queuearr[0];
	    $storyid = $queuearr[1];
	    if (empty($uimid) || empty($storyid)) {
	        return true;
	    }
	    
	    $listenobj = new Listen();
	    $actionlogobj = new ActionLog();
        $userimsiobj = new UserImsi();
        $uid = 0;
        
	    // 检测是否已收听过
	    $listeninfo = $listenobj->getUserListenStoryInfo($uimid, $storyid);
	    if (empty($listeninfo)) {
	        $uiminfo = $userimsiobj->getUserImsiInfoByUimid($uimid);
	        if (empty($uiminfo)) {
	            return true;
	        }
	        $resid = $uiminfo['resid'];
	        $restype = $uiminfo['restype'];
	        
	        if ($restype == $userimsiobj->USER_IMSI_INFO_RESTYPE_UID) {
	            // 登录后的收听
	            $userobj = new User();
	            $uid = $resid;
    	        $userinfo = current($userobj->getUserInfo($uid));
    	        if (empty($userinfo)) {
    	            return true;
    	        }
    	        $babyid = $userinfo['defaultbabyid'];
    	        
    	        $userextobj = new UserExtend();
    	        $babyinfo = current($userextobj->getUserBabyInfo($babyid));
    	        if (empty($babyinfo)) {
    	            return true;
    	        }
    	        $babyagetype = $userextobj->getBabyAgeType($babyinfo['age']);
	        } else {
	            // 未登录的收听
	            $babyagetype = 0;
	        }
	        
	        $story = new Story();
            $storyinfo = $story->get_story_info($storyid);
            if (empty($storyinfo)) {
                return true;
            }
            $albumid = $storyinfo['albumid'];
            
            // 添加收听记录
	        $listenobj->addUserListenStory($uimid, $uid, $albumid, $storyid, $babyagetype);
	    }
	    
	    // 收听故事行为log
	    MnsQueueManager::pushActionLogQueue($uimid, $storyid, $actionlogobj->ACTION_TYPE_LISTEN_STORY);
	}

	protected function checkLogPath() {}

}
new deal_userListenStory ();