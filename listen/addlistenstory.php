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
        
        
        /* $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        } */
        
        $listenobj = new Listen();
        $listeninfo = $listenobj->getUserListenInfoByStoryId($uid, $storyid);
        if (!empty($listeninfo)) {
            $this->showErrorJson(ErrorConf::userListenStoryIsExist());
        }
        $listenobj->addUserListenStory($uid, $albumid, $storyid);
        
        $this->showSuccJson();
    }
}
new addlistenstory();