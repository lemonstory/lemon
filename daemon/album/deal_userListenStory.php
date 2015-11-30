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
	    $ip = $queuearr[2];
	    if (empty($uimid) || empty($storyid)) {
	        return true;
	    }
	    $uid = 0;
	    
        $userimsiobj = new UserImsi();
        $uiminfo = $userimsiobj->getUserImsiInfoByUimid($uimid);
        if (empty($uiminfo)) {
            return true;
        }
        $resid = $uiminfo['resid'];
        $restype = $uiminfo['restype'];
        
        $story = new Story();
        $storyinfo = $story->get_story_info($storyid);
        if (empty($storyinfo)) {
            return true;
        }
        $albumid = $storyinfo['album_id'];
        
        // 第一次或重复收听该故事
        if ($restype == $userimsiobj->USER_IMSI_INFO_RESTYPE_UID) {
            // 登录后的收听
            $userobj = new User();
            $uid = $resid;
	        $userinfo = current($userobj->getUserInfo($uid, 1));
	        if (empty($userinfo)) {
	            return true;
	        }
	        
	        $userextobj = new UserExtend();
	        $babyagetype = $userextobj->getBabyAgeType($userinfo['age']);
        } else {
            // 未登录的收听
            $babyagetype = 0;
        }
        
        // 添加收听记录
        $listenobj = new Listen();
        $listenobj->addUserListenStory($uimid, $uid, $albumid, $storyid, $babyagetype);
	    
	    // 收听故事行为log
	    $actionlogobj = new ActionLog();
	    MnsQueueManager::pushActionLogQueue($uimid, $storyid, $actionlogobj->ACTION_TYPE_LISTEN_STORY);
	    
	    // add sls log
	    $listenid = $uimid . "_" . $storyid;
	    $addtime = date("Y-m-d H:i:s");
	    $alislsobj = new AliSlsUserActionLog();
	    $alislsobj->addListenStoryActionLog($uimid, $uid, $listenid, $storyid, $albumid, $ip, $addtime);
	}

	protected function checkLogPath() {}

}
new deal_userListenStory ();