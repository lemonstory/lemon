<?php

include_once '../../controller.php';

class info extends controller
{
    function action()
    {
        $result = array();
        $albumId = $this->getRequest("album_id", "0");
        // 获取专辑信息参数
        $albumObj = new Album();
        $story = new Story();
        $comment = new Comment();
        $fav = new Fav();
        $listenobj = new Listen();
        $configVarObj = new ConfigVar();
        $uid = $this->getUid();

        // 专辑信息
        $albumInfo = $albumObj->get_album_info($albumId);
        $result['albumInfo']['id'] = $albumInfo['id'];
        $result['albumInfo']['title'] = $albumInfo['title'];
        $result['albumInfo']['star_level'] = $albumInfo['star_level'];
        $result['albumInfo']['story_num'] = $albumInfo['story_num'];
        $result['albumInfo']['intro'] = $albumInfo['intro'];
        $albumAgeLevelStr = $albumObj->getAgeLevelStr($albumInfo['min_age'], $albumInfo['max_age']);
        $result['albumInfo']['age_str'] = sprintf("适合%s岁", $albumAgeLevelStr);

        $aliossObj = new AliOss();
        $cover = $configVarObj->DEFAULT_ALBUM_COVER;
        if (!empty($albumInfo['cover'])) {
            $cover = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albumInfo['cover'], 460, $albumInfo['cover_time']);
        }
        $result['albumInfo']['cover'] = $cover;

        //简介为空的处理
        if (empty($result['albumInfo']['intro'])) {

            $intro = sprintf("《%s》暂时没有简介(>.<)", $result['albumInfo']['title']);
            $result['albumInfo']['intro'] = $intro;
        }

        // 是否收藏
        $favInfo = $fav->getUserFavInfoByAlbumId($uid, $albumId);
        if ($favInfo) {
            $result['albumInfo']['fav'] = 1;
        } else {
            $result['albumInfo']['fav'] = 0;
        }
        // 收听数量
        $albumListenNum = $listenobj->getAlbumListenNum($albumId);
        if ($albumListenNum) {
            $result['albumInfo']['listennum'] = $albumObj->format_album_listen_num((int)$albumListenNum[$albumId]['num']);
        } else {
            $result['albumInfo']['listennum'] = 0;
        }

        // 专辑收藏数
        $favobj = new Fav();
        $albumfavnum = $favobj->getAlbumFavCount($albumId);
        if ($albumfavnum) {
            $result['albumInfo']['favnum'] = (int)$albumfavnum[$albumId]['num'];
        } else {
            $result['albumInfo']['favnum'] = 0;
        }

        $storyList = array();
        $aliossObj = new AliOss();
        $storyResList = $story->get_album_story_list($albumId);
        $storyTotal = $story->get_total(" `album_id`={$albumId} and `status`=1 ");
        if (!empty($storyResList)) {
            foreach ($storyResList as $value) {
                
                $storyInfo = array();
                $storyInfo['id'] = $value['id'];
                $storyInfo['album_id'] = $value['album_id'];
                //部分英文故事辑里面会有多余的反斜杠
                $storyInfo['title'] = stripslashes($value['title']);
                //$storyInfo['intro'] = $value['intro'];
                $storyInfo['times'] = $value['times'];
                $storyInfo['mediapath'] = $value['mediapath'];
                $storyInfo['view_order'] = $value['view_order'];
                $storyInfo['playcover'] = $configVarObj->DEFAULT_STORY_COVER;
                if (!empty($albumInfo['cover'])) {
                    $storyInfo['playcover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albumInfo['cover'], 460, $albumInfo['cover_time']);
                }
                $storyList[] = $storyInfo;
            }
        }
        $result['storyList']['total'] = $storyTotal;
        $result['storyList']['items'] = $storyList;

        // 评论数量
        $result['albumInfo']['commentnum'] = (int)$comment->get_total("`albumid`={$albumId} and `status`=1");

        //TODO:购买图书
        $bugLinkArr = array(
            'http://s.click.taobao.com/XHOTOQx',
            '',
        );
        $key = rand(1, 1);
        $result['albumInfo']['buy_link'] = $bugLinkArr[$key];

        // 获取专辑标签列表
        $tagNewObj = new TagNew();
        $uimidInterestObj = new UimidInterest();
        $dataAnalyticsObj = new DataAnalytics();
        $userImsiObj = new UserImsi();
        $tagList = array();
        $recommendAlbumList = array();
        $tagIds = array();

        // 获取当前专辑的标签
        $relationTagList = current($tagNewObj->getAlbumTagRelationListByAlbumIds($albumId));
        if (!empty($relationTagList)) {
            foreach ($relationTagList as $value) {
                $tagIds[] = $value['tagid'];
            }
            if (!empty($tagIds)) {
                $tagIds = array_unique($tagIds);
                $tagInfos = $tagNewObj->getTagInfoByIds($tagIds);
                if (!empty($tagInfos)) {

                    $tagInfo = array();
                    foreach ($tagInfos as $key => $item) {
                        $tagInfo['id'] = $item['id'];
                        $tagInfo['name'] = $item['name'];
                        if (!empty($item['cover'])) {
                            $tagInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_TAG, $item['cover'], 0, $item['covertime']);
                        } else {
                            $tagInfo['cover'] = "";
                        }
                        $tagList[] = $tagInfo;
                    }
                }
            }
        }
        $result['tagList'] = $tagList;

        $interestList = array();
        $interestTagIds = array();
        $uimid = $userImsiObj->getUimid($uid);

        // 获取设备喜好的标签
        $interestList = $uimidInterestObj->getUimidInterestTagListByUimid($uimid, 10);
        if (!empty($interestList)) {
            foreach ($interestList as $interestInfo) {
                $interestTagIds[] = $interestInfo['tagid'];
            }
        }

        $tagRelationAlbumIds = array();
        $tagRelationAlbumList = array();
        $tagRelationList = array();
        if (!empty($interestTagIds)) {
            // 获取喜好标签的专辑
            $tagRelationList = $dataAnalyticsObj->getRecommendAlbumListByTagids($interestTagIds, 100);
        } else {
            // 未登录、没有喜好的新用户，默认获取本专辑标签相同的其他专辑
            $tagRelationList = $dataAnalyticsObj->getRecommendAlbumListByTagids($tagIds, 100);
        }
        if (!empty($tagRelationList)) {
            foreach ($tagRelationList as $value) {
                // 过滤当前专辑
                if ($value['albumid'] == $albumId) {
                    continue;
                }
                $tagRelationAlbumIds[] = $value['albumid'];
            }
        }

        // 获取指定长度的推荐专辑id数组
        if (!empty($tagRelationAlbumIds)) {
            $tagRelationAlbumIds = array_unique($tagRelationAlbumIds);
            // 随机推荐
            shuffle($tagRelationAlbumIds);
            $tagRelationAlbumIds = array_slice($tagRelationAlbumIds, 0, 12);
            $tagRelationAlbumList = $albumObj->getListByIds($tagRelationAlbumIds);

            // 获取推荐语
            $recommenddescObj = new RecommendDesc();
            $recommendDescList = $recommenddescObj->getAlbumRecommendDescList($tagRelationAlbumIds);
        }

        if (!empty($tagRelationAlbumList)) {

            $albumIds = array();
            foreach ($tagRelationAlbumList as $value) {
                $albumInfo = array();
                $albumIds[] = $value['id'];
                $albumInfo['id'] = $value['id'];
                $albumInfo['title'] = $value['title'];
                $albumInfo['star_level'] = $value['star_level'];
                //$albumInfo['story_num'] = $value['story_num'];
                //$albumInfo['intro'] = $value['intro'];
                $albumAgeLevelStr = $albumObj->getAgeLevelStr($value['min_age'], $value['max_age']);
                //$albumInfo['age_str'] = sprintf("(%s)岁", $albumAgeLevelStr);
                if (!empty($value['cover'])) {
                    $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $value['cover'], 460, $value['cover_time']);
                }
                $albumInfo['listennum'] = 0;
                if (!empty($tagRelationList[$value['id']])) {
                    $albumInfo['listennum'] = $albumObj->format_album_listen_num($tagRelationList[$value['id']]['albumlistennum'] + 0);
                }

                //推荐语
                $albumInfo['recommenddesc'] = "";
                if (!empty($recommendDescList[$albumId])) {
                    $albumInfo['recommenddesc'] = $recommendDescList[$albumId]['desc'];
                }
                $recommendAlbumList[] = $albumInfo;
            }
        }

        $result['recommendAlbumList'] = $recommendAlbumList;

        // 返回成功json
        $this->showSuccJson($result);
    }
}

new info();