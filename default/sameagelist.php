<?php
include_once '../controller.php';
class sameagelist extends controller 
{
    public function action() 
    {
        $currentfirsttagid = $this->getRequest("currentfirsttagid", 0);
        $isgettag = $this->getRequest("isgettag", 1);
        $p = $this->getRequest("p", 1);
        $len = $this->getRequest("len", 20);
        
        $uid = $this->getUid();
        $userinfo = array();
        $albumids = array();
        $albumlist = array();
        $firsttaglist = array();
        $secondtagids = array();
        $listenobj = new Listen();
        $aliossobj = new AliOss();
        $tagnewobj = new TagNew();;
        
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
        if ($_SERVER['visitorappversion'] < "130000") {
            $recommendobj = new Recommend();
            $sameageres = $recommendobj->getSameAgeListenList($babyagetype, $p, $len);
        } else {
            if ($isgettag == 1) {
                // 一级标签
                $firsttaglist = $tagnewobj->getFirstTagList(8);
            }
            
            if (!empty($currentfirsttagid)) {
                // 获取当前一级标签下，前50个二级标签
                $secondtaglist = $tagnewobj->getSecondTagList($currentfirsttagid, 50);
                if (!empty($secondtaglist)) {
                    foreach ($secondtaglist as $value) {
                        $secondtagids[] = $value['id'];
                    }
                    $secondtagids = array_unique($secondtagids);
                }
            } else {
                // 获取全部标签
            }
            $sameageres = $tagnewobj->getAlbumTagRelationListFromRecommend($secondtagids, 0, 1, 0, $p, $len);
        }
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
            $listenobj = new Listen();
            $albumlistennum = $listenobj->getAlbumListenNum($albumids);
            if ($_SERVER['visitorappversion'] < "130000") {
                // 专辑收藏数
                $favobj = new Fav();
                $albumfavnum = $favobj->getAlbumFavCount($albumids);
                // 专辑评论总数
                $commentobj = new Comment();
                $albumcommentnum = $commentobj->countAlbumComment($albumids);
            }
        }
        
        $sameagealbumlist = array();
        if (! empty($sameageres)) {
            foreach ($sameageres as $value) {
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
                    if ($_SERVER['visitorappversion'] < "130000") {
                        $albuminfo['favnum'] = 0;
                        if (!empty($albumfavnum[$albumid])) {
                            $albuminfo['favnum'] = $albumfavnum[$albumid]['num'] + 0;
                        }
                        $albuminfo['commentnum'] = 0;
                        if (!empty($albumcommentnum[$albumid])) {
                            $albuminfo['commentnum'] = $albumcommentnum[$albumid] + 0;
                        }
                    }
                    $sameagealbumlist[] = $albuminfo;
                }
            }
        }
        
        if ($_SERVER['visitorappversion'] < "130000") {
            $this->showSuccJson($sameagealbumlist);
        } else {
            $data = array(
                "sameagelist" => $sameagealbumlist,
                "firsttaglist" => $firsttaglist
            );
            $this->showSuccJson($data);
        }
    }
}
new sameagelist();