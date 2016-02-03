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
        $first_tags_count = 8;
        
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
                $firsttaglist = $tagnewobj->getFirstTagList($first_tags_count);
            }

            //热门推荐->全部
            if (empty($currentfirsttagid)) {

                $recommendobj = new Recommend();
                $sameageres = $recommendobj->getSameAgeListenList($babyagetype, $p, $len);

                //无法识别年龄段及排序
                //$currentfirsttagid = $tagnewobj->getFirstTagIds($first_tags_count);

            } else {

                //热门推荐->子标签
                $sameageres = $tagnewobj->getAlbumTagRelationListFromRecommend($currentfirsttagid, 0, 0, 1, $p, $len);
            }
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
                        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
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