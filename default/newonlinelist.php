<?php
include_once '../controller.php';
class newonlinelist extends controller 
{
    public function action() 
    {
        $direction = $this->getRequest("direction", "down");
        $startalbumid = $this->getRequest("startalbumid", 0);
        $len = $this->getRequest("len", 20);
        
        $uid = $this->getUid();
        $albumids = array();
        
        $babyagetype = 0;
        if (!empty($uid)) {
            $userobj = new User();
            $userinfo = current($userobj->getUserInfo($uid));
            if (!empty($userinfo)) {
                $defaultbabyid = $userinfo['defaultbabyid'];
                if (!empty($defaultbabyid)) {
                    $userextobj = new UserExtend();
                    $babyinfo = current($userextobj->getUserBabyInfo($defaultbabyid));
                    if (!empty($babyinfo)) {
                        $babyagetype = $userextobj->getBabyAgeType($babyinfo['age']);
                    }
                } else {
                    MnsQueueManager::pushRepairUserInfo($uid, "defaultbabyid", 0);
                }
            }
        }
        
        // 最新上架
        $listenobj = new Listen();
        $managesysobj = new ManageSystem();
        $newonlineres = $managesysobj->getNewOnlineList($babyagetype, $direction, $startalbumid, $len);
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