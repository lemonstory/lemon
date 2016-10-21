<?php
include_once '../controller.php';
class hotrecommendlist extends controller 
{
    public function action() 
    {

        $first_tags_count = 8;
        $currentfirsttagid = $this->getRequest("currentfirsttagid", 0);
        $isgettag = $this->getRequest("isgettag", 1);
        $p = $this->getRequest("p", 1);
        $len = $this->getRequest("len", 36);
        
        $userinfo = array();
        $albumids = array();
        $albumlist = array();
        $firsttaglist = array();
        $secondtagids = array();
        $listenobj = new Listen();
        $aliossobj = new AliOss();
        $tagnewobj = new TagNew();
        $configVar = new ConfigVar();
        $albumObj = new Album();

        // 热门推荐
        if ($_SERVER['visitorappversion'] < "130000") {
            $hotrecommendres = $tagnewobj->getAlbumTagRelationListFromRecommend($currentfirsttagid, 1, 0, 0, $p, $len);
        } else {

            if ($isgettag == 1) {
                // 一级标签
                $firsttaglist = $tagnewobj->getFirstTagList($first_tags_count);
            }
            //热门推荐->全部
            //热门推荐->子标签
            $hotrecommendres = $tagnewobj->getAlbumTagRelationListFromRecommend($currentfirsttagid, 1, 0, 0, $p, $len);
        }

        if (! empty($hotrecommendres)) {
            foreach ($hotrecommendres as $value) {
                $albumids[] = $value['albumid'];
            }
        }

        if (! empty($albumids)) {
            $albumids = array_unique($albumids);
            // 专辑信息
            $albumobj = new Album();
            $albumlist = $albumobj->getListByIds($albumids);
            // 专辑收听数
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
        $hotrecommendlist = array();
        if (! empty($hotrecommendres)) {
            foreach ($hotrecommendres as $value) {
                $albumid = $value['albumid'];
                if (! empty($albumlist[$albumid])) {
                    $albuminfo = $albumlist[$albumid];
                    if (!empty($albuminfo['cover'])) {
                        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
                    }
                    $albuminfo['listennum'] = 0;
                    if (!empty($albumlistennum[$albumid]) && intval($albumlistennum[$albumid]['num']) > 0) {
                        $albuminfo['listennum'] = substr($albumlistennum[$albumid]['num'], 0, 5);
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
                    
                    $hotrecommendlist[] = $albuminfo;
                }
            }
        }

        if ($_SERVER['visitorappversion'] < "130000") {
            $this->showSuccJson($hotrecommendlist);
        } else {
            $data = array(
                "hotrecommendlist" => $hotrecommendlist,
                "firsttaglist" => $firsttaglist
            );
            $this->showSuccJson($data);
        }
    }
}
new hotrecommendlist();