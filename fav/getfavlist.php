<?php
/*
 * 用户收藏列表
 */
include_once '../controller.php';
class getfavlist extends controller 
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
        
        $favobj = new Fav();
        $favlist = $favobj->getUserFavList($uid, $direction, $startid, $len);
        if (empty($favlist)) {
            $this->showErrorJson(ErrorConf::userFavIsEmpty());
        }
        
        $albumids = array();
        foreach ($favlist as $value) {
            $albumids[] = $value['albumid'];
        }
        if (empty($albumids)) {
            $this->showErrorJson(ErrorConf::userListenDataError());
        }
        $albumids = array_unique($albumids);
        
        // 批量获取专辑信息
        $albumlist = array();
        
        $data = array();
        foreach ($favlist as $value) {
            $albumid = $value['albumid'];
            if (!empty($albumlist[$albumid])) {
                $value['albuminfo'] = $albumlist[$albumid];
            } else {
                $value['albuminfo'] = array();
            }
            
            $data[] = $value;
        }
        
        $this->showSuccJson($data);
    }
}
new getfavlist();