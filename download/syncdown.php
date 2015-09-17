<?php
/*
 * 上传同步用户下载的专辑、故事状态，用于统计
 */
include_once '../controller.php';
class syncdown extends controller
{
    public function action()
    {
        $uid = $this->getUid();
        $restype = $this->getRequest("restype");// album or story
        $actiontype = $this->getRequest("actiontype"); // start or end
        $albumid = $this->getRequest("albumid");
        $storyid = $this->getRequest("storyid");
        if (empty($restype) || empty($actiontype) || empty($albumid) || empty($storyid)) {
            $this->showErrorJson(ErrorConf::paramError());
            return false;
        }
        if (empty($uid)) {
            // 记录未登录用户的下载数据
            $uid = 0;
        }
    }
    
}
new syncdown();