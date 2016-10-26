<?php

include_once '../controller.php';

class info extends controller
{
    function action()
    {
        $albumId = $this->getRequest("albumid", "0");
        $isCommentPage = $this->getRequest('iscommentpage', 0);
        $len = $this->getRequest("len", 10);
        $configVarObj = new ConfigVar();

        // 长度限制
        if ($len > 50) {
            $len = 50;
        }
        $result = array();

        // 评论分页
        $commentObj = new Comment();
        if ($isCommentPage == 1) {

            $direction = $this->getRequest("direction", "down");
            $startId = $this->getRequest("startid", 0);

            // 评论列表
            $result['commentlist'] = $commentObj->get_comment_list(
                "`albumid`={$albumId}",
                "ORDER BY `id` DESC ",
                $direction,
                $startId,
                $len
            );

        } else {

            // 获取专辑信息参数
            $albumObj = new Album();
            $storyObj = new Story();
            $favObj = new Fav();
            $listenObj = new Listen();
            $uid = $this->getUid();

            // 专辑信息
            $albumInfo = $albumObj->get_album_info($albumId);
            $result['albuminfo']['id'] = $albumInfo['id'];
            $result['albuminfo']['title'] = $albumInfo['title'];
            $star_level = 0;
            if (!empty($albumInfo['star_level'])) {
                $star_level = $albumInfo['star_level'];
            }       
            $result['albuminfo']['star_level'] = $star_level;
            $result['albuminfo']['story_num'] = $albumInfo['story_num'];
            $result['albuminfo']['intro'] = $albumInfo['intro'];

            $aliossObj = new AliOss();
            $cover = $configVarObj->DEFAULT_ALBUM_COVER;
            if (!empty($albumInfo['cover'])) {
                $cover = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albumInfo['cover'], 460, $albumInfo['cover_time']);
            }
            $result['albuminfo']['cover'] = $cover;

            //简介为空的处理
            if (empty($albumInfo['intro'])) {

                $intro = sprintf("《%s》暂时没有简介(>.<)", $albumInfo['title']);
                $result['albuminfo']['intro'] = $intro;
            }

            // 是否收藏
            $favInfo = $favObj->getUserFavInfoByAlbumId($uid, $albumId);
            if ($favInfo) {
                $result['albuminfo']['fav'] = 1;
            } else {
                $result['albuminfo']['fav'] = 0;
            }

            // 收听数量
            $albumListenNum = $listenObj->getAlbumListenNum($albumId);
            if ($albumListenNum && intval($albumListenNum[$albumId]['num']) > 0) {
                $result['albuminfo']['listennum'] = substr($albumListenNum[$albumId]['num'], 0, 5);
            } else {
                $result['albuminfo']['listennum'] = "0";
            }

            // 专辑收藏数
            $albumFavNum = $favObj->getAlbumFavCount($albumId);
            if ($albumFavNum) {
                $result['albuminfo']['favnum'] = (int)$albumFavNum[$albumId]['num'];
            } else {
                $result['albuminfo']['favnum'] = 0;
            }

            $storylist = array();
            $aliossObj = new AliOss();
            $storyreslist = $storyObj->get_album_story_list($albumId, 1, 10000);
            if (!empty($storyreslist)) {
                foreach ($storyreslist as $value) {

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
                    if (!empty($value['cover'])) {
                        $storyInfo['playcover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_STORY, $value['cover'], 460, $value['cover_time']);
                    } else if (!empty($albumInfo['cover'])) {
                        $storyInfo['playcover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albumInfo['cover'], 460, $albumInfo['cover_time']);
                    }
                    $storyList[] = $storyInfo;

                }
            }
            $result['storylist'] = $storyList;
            // 评论数量
            $result['albuminfo']['commentnum'] = (int)$commentObj->get_total("`albumid`={$albumId} and `status`=1");

            // 评论列表
            if ($_SERVER['visitorappversion'] < "120000") {
                // 1.2版本以前的老版本，展示200条评论
                $result['commentlist'] = $commentObj->get_comment_list("`albumid`={$albumId}", "ORDER BY `id` DESC ", 'up', 0, 200);
            } else {
                $result['commentlist'] = $commentObj->get_comment_list("`albumid`={$albumId}", "ORDER BY `id` DESC ", 'up', 0, $len);
            }

            if ($_SERVER['visitorappversion'] >= "130000") {
                // 获取专辑标签列表
                $tagnewobj = new TagNew();
                $uimidinterestobj = new UimidInterest();
                $dataAnalyticsObj = new DataAnalytics();
                $userImsiObj = new UserImsi();
                $taglist = array();
                $recommendAlbumList = array();
                $tagids = array();

                // 获取当前专辑的标签
                $relationtaglist = current($tagnewobj->getAlbumTagRelationListByAlbumIds($albumId));
                if (!empty($relationtaglist)) {
                    foreach ($relationtaglist as $value) {
                        $tagids[] = $value['tagid'];
                    }
                    if (!empty($tagids)) {
                        $tagids = array_unique($tagids);
                        $taglist = $tagnewobj->getTagInfoByIds($tagids);
                        if (!empty($taglist)) {
                            $taglist = array_values($taglist);
                        }
                    }
                }
                $result['taglist'] = $taglist;

                $interestList = array();
                $interestTagIds = array();
                $uimid = $userImsiObj->getUimid($uid);

                // 获取设备喜好的标签
                $interestList = $uimidinterestobj->getUimidInterestTagListByUimid($uimid, 10);
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
                    $tagRelationList = $dataAnalyticsObj->getRecommendAlbumListByTagids($tagids, 100);
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
                    $tagRelationAlbumIds = array_slice($tagRelationAlbumIds, 0, 6);
                    $tagRelationAlbumList = $albumObj->getListByIds($tagRelationAlbumIds);
                }

                if (!empty($tagRelationAlbumList)) {
                    foreach ($tagRelationAlbumList as $value) {

                        $albumInfo = array();
                        $albumIds[] = $value['id'];
                        $albumInfo['id'] = $value['id'];
                        $albumInfo['title'] = $value['title'];
                        if (!empty($value['cover'])) {
                            $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $value['cover'], 460, $value['cover_time']);
                        }
                        $albumInfo['listennum'] = 0;
                        if (!empty($tagRelationList[$value['id']])) {
                            $albumInfo['listennum'] = substr(intval($tagRelationList[$value['id']]['albumlistennum']), 0, 5);
                        }

                        $recommendAlbumList[] = $albumInfo;;
                    }
                }
                $result['recommendalbumlist'] = $recommendAlbumList;
            }
        }


        // 返回成功json
        $this->showSuccJson($result);
    }
}

new info();