<?php
/*
 * 用户收藏专辑
 */
include_once '../controller.php';
class addfavalbum extends controller 
{
    function action() {
        $albumid = $this->getRequest("albumid");
        if (empty($albumid)) {
            $this->showErrorJson(ErrorConf::paramErrorWithParam(array("param" => "专辑id")));
        }
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        // 获取专辑信息
        $albumobj = new Album();
        $albuminfo = $albumobj->get_album_info($albumid);
        if (empty($albuminfo)) {
            $this->showErrorJson(ErrorConf::albumInfoIsEmpty());
        }
        
        $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $favobj = new Fav();
        $favinfo = $favobj->getUserFavInfoByAlbumId($uid, $albumid);
        if (!empty($favinfo)) {
            $this->showErrorJson(ErrorConf::userFavAlbumIsExist());
        }
        $favobj->addUserFavAlbum($uid, $albumid);
        
        $this->showSuccJson();
    }
}
new addfavalbum();