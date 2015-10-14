<?php
include_once '../controller.php';
class gethomeinfo extends controller 
{
    public function action() 
    {
        // 个人主页
        $uid = $this->getRequest("uid", 0); // 被访问的用户uid
        $isgetuserinfo = $this->getRequest("isgetuserinfo", 0);
        $direction = $this->getRequest("direction");
        $startalbumid = $this->getRequest("startalbumid");
        $len = $this->getRequest("len");
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid($uid);
        if (empty($uimid)) {
            $this->showErrorJson(ErrorConf::userImsiIdError());
        }
        
        $data = array();
        if ($isgetuserinfo == 1 && !empty($uid)) {
            $userobj = new User();
            $userinfo = current($userobj->getUserInfo($uid));
            if (empty($userinfo)) {
                $this->showErrorJson(ErrorConf::userNoExist());
            }
            $data = $userinfo;
        }
        
        $listenalbumlist = array();
        $listenobj = new Listen();
        $listenalbumres = $listenobj->getUserAlbumListenList($uimid, $direction, $startalbumid, $len);
        if (!empty($listenalbumres)) {
            $albumids = array();
            $albumlist = array();
            foreach ($listenalbumres as $value) {
                $albumids[] = $value['albumid'];
            }
            if (!empty($albumids)) {
                $albumids = array_unique($albumids);
                
                $albumobj = new Album();
                $albumlist = $albumobj->getListByIds($albumids);
                
                // 专辑收听总数
                $albumlistennum = $listenobj->getAlbumListenNum($albumids);
                
                // 专辑收藏总数
                $favobj = new Fav();
                $albumfavnum = $favobj->getAlbumFavCount($albumids);
                
                // 专辑评论总数
                $commentobj = new Comment();
                $albumcommentnum = $commentobj->countAlbumComment($albumids);
            }
            
            foreach ($listenalbumres as $value) {
                $albumid = $value['albumid'];
                if (empty($albumlist[$albumid])) {
                    continue;
                }
                $albuminfo = $albumlist[$albumid];
                $albuminfo['listennum'] = 0;
                if (!empty($albumlistennum[$albumid])) {
                    $albuminfo['listennum'] = $albumlistennum[$albumid]['num']+0;
                }
                $albuminfo['favnum'] = 0;
                if (!empty($albumfavnum[$albumid])) {
                    $albuminfo['favnum'] = $albumfavnum[$albumid]['num']+0;
                }
                $albuminfo['commentnum'] = 0;
                if (!empty($albumcommentnum[$albumid])) {
                    $albuminfo['commentnum'] = $albumcommentnum[$albumid]+0;
                }
                
                $value['albuminfo'] = $albuminfo;
                $listenalbumlist[] = $value;
            }
        }
        
        $data['listenalbumlist'] = $listenalbumlist;
        $this->showSuccJson($data);
    }
}
new gethomeinfo();