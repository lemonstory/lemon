<?php
/*
 * 用户取消收藏专辑
 */
include_once '../controller.php';
class delfavalbum extends controller 
{
    function action() 
    {
        $albumid = $this->getRequest("albumid");
        if (empty($albumid)) {
            $this->showErrorJson(ErrorConf::paramErrorWithParam(array("param" => "专辑id")));
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
        
        $favobj = new Fav();
        $favinfo = $favobj->getUserFavInfoByAlbumId($uid, $albumid);
        if (empty($favinfo)) {
            $this->showErrorJson(ErrorConf::userFavIsEmpty());
        }
        $favobj->delUserFavAlbum($uid, $albumid);
        
        $this->showSuccJson();
    }
}
new delfavalbum();