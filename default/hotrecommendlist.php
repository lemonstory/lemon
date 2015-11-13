<?php
include_once '../controller.php';
class hotrecommendlist extends controller 
{
    public function action() 
    {
        $p = $this->getRequest("p", 1);
        $len = $this->getRequest("len", 20);
        
        $userinfo = array();
        $albumids = array();
        $albumlist = array();
        $listenobj = new Listen();
        $managesysobj = new ManageSystem();
        $aliossobj = new AliOss();
        
        // 热门推荐
        $hotrecommendres = $managesysobj->getRecommendHotList($p, $len);
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
            // 专辑收藏数
            $favobj = new Fav();
            $albumfavnum = $favobj->getAlbumFavCount($albumids);
            // 专辑评论总数
            $commentobj = new Comment();
            $albumcommentnum = $commentobj->countAlbumComment($albumids);
        }
        
        $hotrecommendlist = array();
        if (! empty($hotrecommendres)) {
            foreach ($hotrecommendres as $value) {
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
                    $hotrecommendlist[] = $albuminfo;
                }
            }
        }
        
        $this->showSuccJson($hotrecommendlist);
    }
}
new hotrecommendlist();