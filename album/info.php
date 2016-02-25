<?php

include_once '../controller.php';
class info extends controller
{
    function action() {
    	$result  = array();
        $album_id      = $this->getRequest("albumid", "0");
        $iscommentpage = $this->getRequest('iscommentpage', 0);
        $len           = $this->getRequest("len", 10);
        $comment       = new Comment();
        // 长度限制
        if ($len > 50) {
            $len = 50;
        }
        // 评论分页
        if ($iscommentpage == 1) {
            $direction = $this->getRequest("direction", "down");
            $startid = $this->getRequest("startid", 0);
            
            // 评论列表
            $result['commentlist'] = $comment->get_comment_list(
                                         "`albumid`={$album_id}",
                                         "ORDER BY `id` DESC ",
                                         $direction,
                                         $startid,
                                         $len
                                     );
            $this->showSuccJson($result);
        }
        // 获取专辑信息参数
        $album            = new Album();
        $story            = new Story();
        $comment          = new Comment();
        $useralbumlog     = new UserAlbumLog();
        $useralbumlastlog = new UserAlbumLastlog();
        $fav              = new Fav();
        $listenobj        = new Listen();

        $uid = $this->getUid();
        // 专辑信息
        $result['albuminfo']  = $album->get_album_info($album_id);

        $aliossobj = new AliOss();
        if ($result['albuminfo']['cover']) {
            $result['albuminfo']['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $result['albuminfo']['cover'], 460, $result['albuminfo']['cover_time']);
        } else {
            $result['albuminfo']['cover'] = $result['albuminfo']['s_cover'];
        }


        //简介为空的处理
        if (empty($result['albuminfo']['intro'])) {

            $intro = sprintf("《%s》暂时没有简介(>.<)", $result['albuminfo']['title']);
            $result['albuminfo']['intro'] = $intro;
        }

        // 是否收藏
        $favinfo = $fav->getUserFavInfoByAlbumId($uid, $album_id);
        if ($favinfo) {
            $result['albuminfo']['fav'] = 1;
        } else {
            $result['albuminfo']['fav'] = 0;
        }
        // 收听数量
        $albumlistennum = $listenobj->getAlbumListenNum($album_id);
        if ($albumlistennum) {
            $result['albuminfo']['listennum'] = (int)$albumlistennum[$album_id]['num'];
        } else {
            $result['albuminfo']['listennum'] = 0;
        }
        
        // 专辑收藏数
        $favobj = new Fav();
        $albumfavnum = $favobj->getAlbumFavCount($album_id);
        if ($albumfavnum) {
            $result['albuminfo']['favnum'] = (int)$albumfavnum[$album_id]['num'];
        } else {
            $result['albuminfo']['favnum'] = 0;
        }

        $storylist = array();
        $aliossobj = new AliOss();
        $storyreslist = $story->get_album_story_list($album_id);
        if (!empty($storyreslist)) {
            foreach ($storyreslist as $value) {
                $value['playcover'] = "";
                if (!empty($value['cover'])) {
                    $value['playcover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_STORY, $value['cover'], 230, $value['cover_time']);
                }
                $storylist[] = $value;
            }
        }
        $result['storylist'] = $storylist;
        
        // 评论数量
        $result['albuminfo']['commentnum'] = (int)$comment->get_total("`albumid`={$album_id} and `status`=1");

        // 评论列表
        if ($_SERVER['visitorappversion'] < "120000") {
            // 1.2版本以前的老版本，展示200条评论
            $result['commentlist'] = $comment->get_comment_list("`albumid`={$album_id}", "ORDER BY `id` DESC ", 'up', 0, 200);
        } else {
            $result['commentlist'] = $comment->get_comment_list("`albumid`={$album_id}", "ORDER BY `id` DESC ", 'up', 0, $len);
        }
        
        if ($_SERVER['visitorappversion'] >= "130000") {
            // 获取专辑标签列表
            $tagnewobj = new TagNew();
            $uimidinterestobj = new UimidInterest();
            $dataanalyticsobj = new DataAnalytics();
            $userimsiobj = new UserImsi();
            $taglist = array();
            $recommendalbumlist = array();
            
            // 获取当前专辑的标签
            $relationtaglist = current($tagnewobj->getAlbumTagRelationListByAlbumIds($album_id));
            if (!empty($relationtaglist)) {
                $tagids = array();
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
            
            $interestlist = array();
            $interesttagids = array();
            $uimid = $userimsiobj->getUimid($uid);
            
            $logfile = "/alidata1/rc.log";
            $fp = @fopen($logfile, "a+");
            @fwrite($fp, "uid={$uid}##uimid={$uimid}");
            
            // 获取设备喜好的标签
            $interestlist = $uimidinterestobj->getUimidInterestTagListByUimid($uimid, 10);
            if (!empty($interestlist)) {
                foreach ($interestlist as $interestinfo) {
                    $interesttagids[] = $interestinfo['tagid'];
                }
            }
            
            $tagrelationalbumids = array();
            $tagrelationalbumlist = array();
            
            // 获取喜好标签的专辑
            $tagrelationlist = array();
            $tagrelationlist = $dataanalyticsobj->getRecommendAlbumTagRelationListByInterestTag($interesttagids, 100);
            if (!empty($tagrelationlist)) {
                foreach ($tagrelationlist as $value) {
                    // 过滤当前专辑
                    if ($value['albumid'] == $album_id) {
                        continue;
                    }
                    $tagrelationalbumids[] = $value['albumid'];
                }
            }
            
            // 获取指定长度的推荐专辑id数组
            if (!empty($tagrelationalbumids)) {
                $tagrelationalbumids = array_unique($tagrelationalbumids);
                $tagrelationalbumids = array_slice($tagrelationalbumids, 0, 6);
                $tagrelationalbumlist = $album->getListByIds($tagrelationalbumids);
            }
            
            if (!empty($tagrelationalbumlist)) {
                foreach ($tagrelationalbumlist as $value) {
                    if (!empty($value['cover'])) {
                        $value['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $value['cover'], 460, $value['cover_time']);
                    }
                    $value['listennum'] = 0;
                    if (!empty($tagrelationlist[$value['id']])) {
                        $value['listennum'] = $tagrelationlist[$value['id']]['albumlistennum'] + 0;
                    }
                    $recommendalbumlist[] = $value;
                }
            }
            
            $result['recommendalbumlist'] = $recommendalbumlist;
        }

        // 返回成功json
        $this->showSuccJson($result);
    }
}
new info();