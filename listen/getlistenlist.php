<?php
/*
 * 用户收听列表
 */
include_once '../controller.php';
class getlistenlist extends controller 
{
    public function action() 
    {
        $direction = $this->getRequest("direction", "down");
        $startid = $this->getRequest("startid", 0);
        $len = $this->getRequest("len", 0);
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $listenobj = new Listen();
        $listenlist = $listenobj->getUserListenList($uid, $direction, $startid, $len);
        if (empty($listenlist)) {
            $this->showErrorJson(ErrorConf::userListenIsEmpty());
        }
        
        $storyids = $albumids = array();
        foreach ($listenlist as $value) {
            $albumids[] = $value['albumid'];
            $storyids[] = $value['storyid'];
        }
        if (empty($albumids) || empty($storyids)) {
            $this->showErrorJson(ErrorConf::userListenDataError());
        }
        $albumids = array_unique($albumids);
        $storyids = array_unique($storyids);
        
        // 批量获取专辑、故事信息
        $albumlist = array();
        $storylist = array();
        
        $data = array();
        foreach ($listenlist as $value) {
            $albumid = $value['albumid'];
            $storyid = $value['storyid'];
            if (!empty($albumlist[$albumid])) {
                $value['albuminfo'] = $albumlist[$albumid];
            } else {
                $value['albuminfo'] = array();
            }
            if (!empty($storylist[$storyid])) {
                $value['storyinfo'] = $storylist[$storyid];
            } else {
                $value['storyinfo'] = array();
            }
            
            $data[] = $value;
        }
        
        $this->showSuccJson($data);
    }
}
new getlistenlist();