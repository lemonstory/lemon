<?php
include_once '../controller.php';
class hotrecommendlist extends controller 
{
    public function action() 
    {
        $currentfirsttagid = $this->getRequest("currentfirsttagid", 0);
        $isgettag = $this->getRequest("isgettag", 1);
        $p = $this->getRequest("p", 1);
        $len = $this->getRequest("len", 20);
        
        $userinfo = array();
        $albumids = array();
        $albumlist = array();
        $firsttaglist = array();
        $secondtagids = array();
        $listenobj = new Listen();
        $aliossobj = new AliOss();
        $tagnewobj = new TagNew();
        
        // 热门推荐
        if ($_SERVER['visitorappversion'] < "130000") {
            $recommendobj = new Recommend();
            $hotrecommendres = $recommendobj->getRecommendHotList($p, $len);
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
            $hotrecommendres = $tagnewobj->getAlbumTagRelationListFromRecommend($secondtagids, 1, 0, 0, $p, $len);
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