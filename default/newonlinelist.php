<?php
include_once '../controller.php';
class newonlinelist extends controller 
{
    public function action() 
    {
        $p = $this->getRequest("p", 1);
        $len = $this->getRequest("len", 20);
        
        $uid = $this->getUid();
        $albumids = array();
        $aliossobj = new AliOss();
        
        $babyagetype = 0;
        if (!empty($uid)) {
            $userobj = new User();
            $userinfo = current($userobj->getUserInfo($uid, 1));
            if (!empty($userinfo)) {
                $userextobj = new UserExtend();
                $babyagetype = $userextobj->getBabyAgeType($userinfo['age']);
            }
        }
        
        // 最新上架
        $listenobj = new Listen();
        $recommendobj = new Recommend();
        $newonlineres = $recommendobj->getNewOnlineList($babyagetype, $p, $len);
        if (! empty($newonlineres)) {
            foreach ($newonlineres as $value) {
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
        
        $newalbumlist = array();
        if (! empty($newonlineres)) {
            foreach ($newonlineres as $value) {
                $albumid = $value['albumid'];
                if (! empty($albumlist[$albumid])) {
                    $albuminfo = $albumlist[$albumid];
                    if (!empty($albuminfo['cover'])) {
                        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 100, $albuminfo['cover_time']);
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
                    $newalbumlist[] = $albuminfo;
                }
            }
        }
        
        $this->showSuccJson($newalbumlist);
    }
}
new newonlinelist();