<?php
/*
 * 用户取消收听故事
 */
include_once '../controller.php';
class dellistenstory extends controller 
{
    public function action() 
    {
        $albumid = $this->getRequest("albumid");
        $storyid = $this->getRequest("storyid");
        if (empty($albumid) || empty($storyid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        // 获取专辑信息
        
        
        /* $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        } */
        
        $listenobj = new Listen();
        $listeninfo = $listenobj->getUserListenInfoByStoryId($uid, $storyid);
        if (empty($listeninfo)) {
            $this->showErrorJson(ErrorConf::userListenIsEmpty());
        }
        $listenobj->delUserListenStory($uid, $storyid);
        
        $this->showSuccJson();
    }
}
new dellistenstory();