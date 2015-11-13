<?php
include_once '../controller.php';
class sameagelist extends controller 
{
    public function action() 
    {
        $p = $this->getRequest("p", 1);
        $len = $this->getRequest("len", 20);
        
        $uid = $this->getUid();
        $userinfo = array();
        $albumids = array();
        $aliossobj = new AliOss();
        
        $babyagetype = 0;
        if (! empty($uid)) {
            $userobj = new User();
            $userinfo = current($userobj->getUserInfo($uid, 1));
            if (! empty($userinfo)) {
                $userextobj = new UserExtend();
                $babyagetype = $userextobj->getBabyAgeType($userinfo['age']);
            }
        }
        
        // 同龄在听
        $listenobj = new Listen();
        $sameageres = $listenobj->getSameAgeListenList($babyagetype, $p, $len);
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
            // 专辑评论总数
            $commentobj = new Comment();
            $albumcommentnum = $commentobj->countAlbumComment($albumids);
        }
        
        $sameagealbumlist = array();
        if (! empty($sameageres)) {
            foreach ($sameageres as $value) {
                $albumid = $value['albumid'];
                if (! empty($albumlist[$albumid])) {
                    $albuminfo = $albumlist[$albumid];
                    if (!empty($albuminfo['cover'])) {
                        $albuminfo['cover'] = $aliossobj->getImageUrlNg($albuminfo['cover'], 100, $albuminfo['cover_time']);
                    }
                    $albuminfo['listennum'] = 0;
                    if (! empty($albumlistennum[$albumid])) {
                        $albuminfo['listennum'] = $albumlistennum[$albumid]['num'] + 0;
                    }
                    $albuminfo['favnum'] = 0;
                    if (!empty($albumfavnum[$albumid])) {
                        $albuminfo['favnum'] = $albumfavnum[$albumid]['num'] + 0;
                    }
                    $albuminfo['commentnum'] = 0;
                    if (!empty($albumcommentnum[$albumid])) {
                        $albuminfo['commentnum'] = $albumcommentnum[$albumid] + 0;
                    }
                    $sameagealbumlist[] = $albuminfo;
                }
            }
        }
        
        $this->showSuccJson($sameagealbumlist);
    }
}
new sameagelist();