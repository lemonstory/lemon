<?php
/*
 * 守护进程，添加收听记录
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class deal_userListenStory extends DaemonBase {
    protected $processnum = 1;
	protected function deal() {
	    $queuevalue = QueueManager::popListenStoryQueue();
	    if (empty($queuevalue)) {
	        sleep(10);
	        return true;
	    }
	    $queuearr = explode(":", $queuevalue);
	    $uid = $queuearr[0];
	    $storyid = $queuearr[1];
	    if (empty($uid) || empty($storyid)) {
	        return true;
	    }
	    
	    $listenobj = new Listen();
	    // 检测是否已收听过
	    $listeninfo = $listenobj->getUserListenStoryInfo($uid, $storyid);
	    if (empty($listeninfo)) {
	        $userobj = new User();
	        $userinfo = current($userobj->getUserInfo($uid));
	        if (empty($userinfo)) {
	            return true;
	        }
	        $babyid = $userinfo['defaultbabyid'];
	        
	        $userextobj = new UserExtend();
	        $babyinfo = $userextobj->getUserBabyInfo($babyid);
	        if (empty($babyinfo)) {
	            return true;
	        }
	        $babyagetype = $userextobj->getBabyAgeType($babyinfo['age']);
	        
	        $story = new Story();
            $storyinfo = $story->get_story_info($storyid);
            if (empty($storyinfo)) {
                return true;
            }
            $albumid = $storyinfo['albumid'];
            
            // 添加收听记录
	        $listenobj->addUserListenStory($uid, $albumid, $storyid, $babyagetype);
	    }
	}

	protected function checkLogPath() {}

}
new deal_userListenStory ();