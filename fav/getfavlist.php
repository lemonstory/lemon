<?php
/*
 * 用户收藏列表
 */
include_once '../controller.php';
class getfavlist extends controller 
{
    public function action() 
    {
        $direction = $this->getRequest("direction", "down");
        $startfavid = $this->getRequest("startfavid", 0);
        $len = $this->getRequest("len", 20);
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $albumids = array();
        $favobj = new Fav();
        $favlist = $favobj->getUserFavList($uid, $direction, $startfavid, $len);
        if (!empty($favlist)) {
            $albumids = array();
            foreach ($favlist as $value) {
                $albumids[] = $value['albumid'];
            }
        }
        
        $data = array();
        if (!empty($albumids)) {
            $albumids = array_unique($albumids);
            // 批量获取专辑信息
            $albumobj = new Album();
            $albumlist = $albumobj->getListByIds($albumids);
            // 专辑收听数
            $listenobj = new Listen();
            $albumlistennum = $listenobj->getAlbumListenNum($albumids);
            // 专辑收藏数
            $favobj = new Fav();
            $albumfavnum = $favobj->getAlbumFavCount($albumids);
            // 专辑评论总数
            $commentobj = new Comment();
            $albumcommentnum = $commentobj->countAlbumComment($albumids);
            
            foreach ($favlist as $value) {
                $albuminfo = array();
                $favinfo = array();
                
                $favinfo['favid'] = $value['id'];
                $favinfo['albumid'] = $value['albumid'];
                $favinfo['favtime'] = $value['addtime'];
                
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
                }
                
                $data[] = array_merge($favinfo, $albuminfo);
            }
        }
        
        $this->showSuccJson($data);
    }
}
new getfavlist();