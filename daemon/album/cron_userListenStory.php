<?php
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class cron_userListenStory extends DaemonBase {
	public $isWhile = false;
	protected function deal() {
	    $queuevalue = QueueManager::popListenStoryQueue();
	    if (empty($queuevalue)) {
	        sleep(5);
	        return true;
	    }
	    $queuearr = explode(":", $queuevalue);
	    $uid = $queuearr[0];
	    $storyid = $queuearr[1];
	    if (empty($uid) || empty($storyid)) {
	        return false;
	    }
	    
	    $listenobj = new Listen();
	    // 检测是否已收听过
	    $listeninfo = $listenobj->getUserListenInfoByStoryId($uid, $storyid);
	    if (empty($listeninfo)) {
	        $userobj = new User();
	        $userinfo = current($userobj->getUserInfo($uid));
	        if (empty($userinfo)) {
	            return false;
	        }
	        $babyid = $userinfo['defaultbabyid'];
	        
	        $userextobj = new UserExtend();
	        $babyinfo = $userextobj->getUserBabyInfo($babyid);
	        if (empty($babyinfo)) {
	            return false;
	        }
	        $babyagetype = $userextobj->getBabyAgeType($babyinfo['age']);
	        
	        $story = new Story();
            $storyinfo = $story->get_story_info($storyid);
            if (empty($storyinfo)) {
                return false;
            }
            $albumid = $storyinfo['albumid'];
            
            // 添加收听记录
	        $listenobj->addUserListenStory($uid, $albumid, $storyid, $babyagetype);
	    }
	}

	protected function checkLogPath() {
		// TODO Auto-generated method stub
	}

}
new cron_userListenStory ();