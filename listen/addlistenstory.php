<?php
/*
 * 用户收听故事
 */
include_once '../controller.php';
class addlistenstory extends controller 
{
    function action() {
        $storyid = $this->getRequest("storyid");
        if (empty($storyid)) {
            $this->showErrorJson(ErrorConf::paramErrorWithParam(array("param" => "storyid")));
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
        $listenobj->addUserLisenStory($uid, $storyid);
        
        $this->showSuccJson();
    }
}
new addlistenstory();