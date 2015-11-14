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
            // 未登录返回空数据
            $this->showSuccJson();
        }
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid($uid);
        if (empty($uimid)) {
            $this->showErrorJson(ErrorConf::userImsiIdError());
        }
        
        $aliossobj = new AliOss();
        $listenobj = new Listen();
        $favobj = new Fav();
        $storyobj = new Story();
        
        $listenalbumlist = array();
        $storylist = array();
        
        $listenalbumres = $listenobj->getUserAlbumListenList($uimid, $direction, $startalbumid, $len);
        if (!empty($listenalbumres)) {
            $albumids = array();
            $albumlist = array();
            $playloglist = array();
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
                
                // 专辑下最近播放的故事记录
                $useralbumlogobj = new UserAlbumLog();
                $playloglist = $useralbumlogobj->getPlayInfoByAlbumIds($albumids);
                
                // 专辑下，uid或设备收听的故事列表
                //$listenstorylist = array();
                //$listenstoryres = $listenobj->getUserListenStoryListByAlbumId($uimid, $albumids);
                $albumidstr = implode(",", $albumids);
                $albumstoryres = $storyobj->get_list("`album_id` IN ({$albumidstr})");
                if (!empty($albumstoryres)) {
                    foreach ($albumstoryres as $storyinfo) {
                        $albumid = $storyinfo['album_id'];
                        $storylist[$albumid][] = $storyinfo;
                    }
                }
            }
            
            foreach ($listenalbumres as $value) {
                $albumid = $value['albumid'];
                if (empty($albumlist[$albumid])) {
                    continue;
                }
                // 专辑收听历史更新时间
                $value['listenalbumuptime'] = date("Y-m-d H:i:s", $value['uptime']);
                $value['playstoryid'] = 0;
                if (!empty($playloglist[$albumid])) {
                    $value['playstoryid'] = $playloglist[$albumid]['storyid'] + 0;
                    $value['playtimes'] = $playloglist[$albumid]['playtimes'] + 0;
                }
                
                $albuminfo = $albumlist[$albumid];
                if (!empty($albuminfo['cover'])) {
                    $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 100, $albuminfo['cover_time']);
                }
                
                $albuminfo['listennum'] = 0;
                if (!empty($albumlistennum[$albumid])) {
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
                
                $albuminfo['storylist'] = array();
                if (!empty($storylist[$albumid])) {
                    $albuminfo['storylist'] = $storylist[$albumid];
                }
                
                $value['albuminfo'] = $albuminfo;
                $listenalbumlist[] = $value;
            }
        }
        
        $favcount = 0;
        if ($isgetcount == 1) {
            // 我的收藏总数
            if (!empty($uid)) {
                $favcount = $favobj->getUserFavCount($uid);
            }
            // 我的下载总数本地存储
            
        }
        
        $data = array('listenalbumlist' => $listenalbumlist, 'favcount' => $favcount);
        $this->showSuccJson($data);
    }
}
new mystory();