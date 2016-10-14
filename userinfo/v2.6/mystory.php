<?php
/*
 * 我的故事
 */
include_once '../../controller.php';

class myStory extends controller
{
    public function action()
    {
        $direction = $this->getRequest("direction", "down");
        $startAlbumId = $this->getRequest("start_album_id", 0);
        $len = $this->getRequest("len", 20);
        $uid = $this->getUid();
        $storyCoverSize = 230;
        $favCount = 0;
        $listenAlbumList = array();

        if (!empty($uid)) {

            $userImsiObj = new UserImsi();
            $uimid = $userImsiObj->getUimid($uid);
            if (empty($uimid)) {
                $this->showErrorJson(ErrorConf::userImsiIdError());
            }

            $aliossObj = new AliOss();
            $listenObj = new Listen();
            $favObj = new Fav();
            $storyObj = new Story();
            $storyList = array();

            //我的收藏总数
            $favCount = $favObj->getUserFavCount($uid);

            //我的下载总数本地存储

            //收听历史
            $listenAlbumRes = $listenObj->getUserAlbumListenList($uimid, $direction, $startAlbumId, $len);
            if (!empty($listenAlbumRes)) {
                $albumIds = array();
                foreach ($listenAlbumRes as $value) {
                    $albumIds[] = $value['albumid'];
                }
                if (!empty($albumIds)) {
                    $albumIds = array_unique($albumIds);

                    // 专辑下最近播放的故事记录
                    $useralbumlogobj = new UserAlbumLog();
                    $playLogList = array();
                    $playLogList = $useralbumlogobj->getPlayInfoByAlbumIds($albumIds);

                    if (!empty($playLogList)) {
                        $playStoryIds = array();
                        foreach ($playLogList as $value) {
                            $playStoryIds[] = $value['storyid'];
                        }
                        $playStoryIds = array_unique($playStoryIds);
                        $playStoryIdstr = implode(",", $playStoryIds);
                        $albumStoryRes = $storyObj->get_list("`id` IN ({$playStoryIdstr})");
                        if (!empty($albumStoryRes)) {
                            foreach ($albumStoryRes as $item) {
                                $storyInfo = array();
                                $storyInfo['id'] = $item['id'];
                                $storyInfo['album_id'] = $item['album_id'];
                                $storyInfo['title'] = $item['title'];
                                $item['playcover'] = "";
                                if (!empty($item['cover'])) {
                                    $storyInfo['playcover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_STORY, $item['cover'], $storyCoverSize, $item['cover_time']);
                                }
                                $storyInfo['mediapath'] = $item['mediapath'];
                                //$storyInfo['view_order'] = $item['view_order'];
                                $storyList[$storyInfo['album_id']] = $storyInfo;
                            }
                        }
                    }

                    // 专辑列表
                    $albumObj = new Album();
                    $albumList = array();
                    $albumList = $albumObj->getListByIds($albumIds);
                    foreach ($listenAlbumRes as $item) {
                        $listenAlbumItem = array();
                        $albumId = $item['albumid'];
                        if (empty($albumList[$albumId])) {
                            continue;
                        }
                        // 专辑收听历史更新时间
                        $listenAlbumItem['listenalbumuptime'] = date("Y-m-d H:i:s", $item['uptime']);
                        $listenAlbumItem['playstoryid'] = $item['playtimes'] = 0;
                        if (!empty($playLogList[$albumId])) {
                            $listenAlbumItem['playstoryid'] = $playLogList[$albumId]['storyid'] + 0;
                            $listenAlbumItem['playtimes'] = $playLogList[$albumId]['playtimes'] + 0;
                        }

                        $albumItem = $albumList[$albumId];

                        $albumInfo = array();
                        $albumInfo['id'] = $albumItem['id'];
                        $albumInfo['title'] = $albumItem['title'];
                        if (!empty($albumItem['cover'])) {
                            $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albumItem['cover'], 100, $albumItem['cover_time']);
                        }
                        //$albumInfo['star_level'] = $albumItem['star_level'];
                        //$albumInfo['intro'] = $albumItem['intro'];
                        $albumAgeLevelStr = $albumObj->getAgeLevelStr($albumItem['min_age'], $albumItem['max_age']);
                        $albumInfo['age_str'] = sprintf("(%s)岁", $albumAgeLevelStr);
                        //$albumInfo['story_num'] = $albumItem['story_num'];
                        //$albumInfo['min_age'] = $albumItem['min_age'];
                        //$albumInfo['max_age'] = $albumItem['max_age'];

                        $albumInfo['storyinfo'] = array();
                        if (!empty($storyList[$albumId])) {
                            $albumInfo['storyinfo'] = $storyList[$albumId];
                        }
                        $listenAlbumItem['albuminfo'] = $albumInfo;
                        $listenAlbumList[] = $listenAlbumItem;
                    }
                }
            }


        }
        $data = array('favcount' => $favCount, 'listenalbumlist' => $listenAlbumList,);
        $this->showSuccJson($data);
    }
}

new myStory();