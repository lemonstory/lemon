<?php
/*
 * 用户收听故事
 */
include_once '../controller.php';
class addlistenstory extends controller 
{
    function action() {
        $albumid = $this->getRequest("albumid");
        $storyid = $this->getRequest("storyid");
        if (empty($albumid) || empty($storyid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        // 获取故事信息
        $storyobj = new Story();
        $storyinfo = $storyobj->get_story_info($storyid);
        if (empty($storyinfo)) {
            $this->showErrorJson(ErrorConf::storyInfoIsEmpty());
        }
        
        $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $listenobj = new Listen();
        $listeninfo = $listenobj->getUserListenInfoByStoryId($uid, $storyid);
        if (!empty($listeninfo)) {
            $this->showErrorJson(ErrorConf::userListenStoryIsExist());
        }
        
        $babyid = $userinfo['defaultbabyid'];
        $userextobj = new UserExtend();
        $babyinfo = $userextobj->getUserBabyInfo($babyid);
        $babyagetype = $userextobj->getBabyAgeType($babyinfo['age']);
        $listenobj->addUserListenStory($uid, $albumid, $storyid, $babyagetype);
        
        $this->showSuccJson();
    }
}
new addlistenstory();