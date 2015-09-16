<?php
/*
 * 我的故事页
 */
include_once '../controller.php';
class mystory extends controller 
{
    public function action() 
    {
        $isgetcount = $this->getRequest("isgetcount", 0);
        $direction = $this->getRequest("direction", "down");
        $startalbumid = $this->getRequest("startalbumid", 0);
        $len = $this->getRequest("len", 0);
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        // 收听历史列表
        $listenalbumlist = array();
        $listenobj = new Listen();
        $favobj = new Fav();
        $listenalbumres = $listenobj->getUserAlbumListenList($uid, $direction, $startalbumid, $len);
        if (!empty($listenalbumres)) {
            $albumids = array();
            $albumlist = array();
            foreach ($listenalbumres as $value) {
                $albumids[] = $value['albumid'];
            }
            if (!empty($albumids)) {
                $albumids = array_unique($albumids);
                // 专辑列表
                $albumobj = new Album();
                $albumlist = $albumobj->getListByIds($albumids);
                // 专辑收听总数
                $albumlistennum = $listenobj->getAlbumListenNum($albumids);
                // 专辑收藏总数
                $albumfavnum = $favobj->getAlbumFavCount($albumids);
                // 专辑评论总数
                $commentobj = new Comment();
                $albumcommentnum = $commentobj->countAlbumComment($albumids);
                
                // 专辑下，用户收听的故事列表
                $listenstorylist = array();
                $listenstoryres = $listenobj->getUserListenStoryListByAlbumId($uid, $albumids);
                if (!empty($listenstoryres)) {
                    $storyids = array();
                    $storylist = array();
                    foreach ($listenstoryres as $value) {
                        $storyids[] = $value['storyid'];
                    }
                    if (!empty($storyids)) {
                        $storyids = array_unique($storyids);
                        $storyobj = new Story();
                        $storyres = $storyobj->getListByIds($storyids);
                        if (!empty($storyres)) {
                            foreach ($storyres as $value) {
                                $storylist[$value['album_id']][] = $value;
                            }
                        }
                    }
                }
            }
            
            foreach ($listenalbumres as $value) {
                $albumid = $value['albumid'];
                if (empty($albumlist[$albumid])) {
                    continue;
                }
                $albuminfo = $albumlist[$albumid];
                $albuminfo['listennum'] = 0;
                if (!empty($albumlistennum[$albumid])) {
                    $albuminfo['listennum'] = $albumlistennum[$albumid]['num']+0;
                }
                $albuminfo['favnum'] = 0;
                if (!empty($albumfavnum[$albumid])) {
                    $albuminfo['favnum'] = $albumfavnum[$albumid]['num']+0;
                }
                $albuminfo['commentnum'] = 0;
                if (!empty($albumcommentnum[$albumid])) {
                    $albuminfo['commentnum'] = $albumcommentnum[$albumid]['num']+0;
                }
                $albuminfo['storylist'] = array();
                if (!empty($storylist[$albumid])) {
                    $albuminfo['storylist'] = $storylist[$albumid];
                }
                $value['albuminfo'] = $albuminfo;
                
                $listenalbumlist[] = $value;
            }
        }
        
        if ($isgetcount == 1) {
            // 我的收藏总数
            $favcount = $favobj->getUserFavCount($uid);
            // 我的下载总数
            
        }
        
        $data = array('listenalbumlist' => $listenalbumlist, 'favcount' => $favcount);
        $this->showSuccJson($data);
    }
}
new mystory();