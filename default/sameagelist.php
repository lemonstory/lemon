<?php
include_once '../controller.php';
class sameagelist extends controller 
{
    public function action() 
    {
        $direction = $this->getRequest("direction", "down");
        $startalbumid = $this->getRequest("startalbumid", 0);
        $len = $this->getRequest("len", 20);
        
        $uid = $this->getUid();
        $userinfo = array();
        $albumids = array();
        
        $babyagetype = 0;
        if (! empty($uid)) {
            $userobj = new User();
            $userinfo = current($userobj->getUserInfo($uid));
            if (! empty($userinfo)) {
                $defaultbabyid = $userinfo['defaultbabyid'];
                if (! empty($defaultbabyid)) {
                    $userextobj = new UserExtend();
                    $babyinfo = current($userextobj->getUserBabyInfo($defaultbabyid));
                    if (! empty($babyinfo)) {
                        $babyagetype = $userextobj->getBabyAgeType($babyinfo['age']);
                    }
                }
            }
        }
        
        // 同龄在听
        $listenobj = new Listen();
        $sameageres = $listenobj->getSameAgeListenList($babyagetype, $direction, $startalbumid, $len);
        if (! empty($sameageres)) {
            foreach ($sameageres as $value) {
                $albumids[] = $value['albumid'];
            }
        }
        
        $albumlist = array();
        if (! empty($albumids)) {
            $albumids = array_unique($albumids);
            // 专辑信息
            $albumobj = new Album();
            $albumlist = $albumobj->getListByIds($albumids);
            // 专辑收听数
            $albumlistennum = $listenobj->getAlbumListenNum($albumids);
            // 专辑收藏数
            $favobj = new Fav();
            $albumfavnum = $favobj->getAlbumFavCount($albumids);
        }
        
        $sameagealbumlist = array();
        if (! empty($sameageres)) {
            foreach ($sameageres as $value) {
                $albumid = $value['albumid'];
                if (! empty($albumlist[$albumid])) {
                    $albuminfo = $albumlist[$albumid];
                    $albuminfo['listennum'] = 0;
                    if (! empty($albumlistennum[$albumid])) {
                        $albuminfo['listennum'] = $albumlistennum[$albumid]['num'] + 0;
                    }
                    $albuminfo['favnum'] = 0;
                    if (!empty($albumfavnum[$albumid])) {
                        $albuminfo['favnum'] = $albumfavnum[$albumid]['num'] + 0;
                    }
                    $sameagealbumlist[] = $albuminfo;
                }
            }
        }
        
        $this->showSuccJson($sameagealbumlist);
    }
}
new sameagelist();