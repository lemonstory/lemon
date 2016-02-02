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

            $intro = sprintf("%暂时没有简介内容 (>.<)", $result['albuminfo']['title']);
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
            $relationreslist = current($tagnewobj->getAlbumTagRelationListByAlbumIds($album_id));
            $taglist = array();
            $recommendalbumlist = array();
            if (!empty($relationreslist)) {
                $tagids = array();
                foreach ($relationreslist as $value) {
                    $tagids[] = $value['tagid'];
                }
                if (!empty($tagids)) {
                    $tagids = array_unique($tagids);
                    $taglist = $tagnewobj->getTagInfoByIds($tagids);
                    if (!empty($taglist)) {
                        $taglist = array_values($taglist);
                    }
                    
                    // 相关推荐专辑
                    $tagrelationalbumids = array();
                    $tagrelationalbumlist = array();
                    $recommendalbumlist = array();
                    
                    $dataanalyticsobj = new DataAnalytics();
                    $tagrelationlist = $dataanalyticsobj->getRecommendAlbumTagRelationListByAlbumTagIds($tagids, 6);
                    foreach ($tagrelationlist as $value) {
                        $tagrelationalbumids[] = $value['albumid'];
                    }
                    if (!empty($tagrelationalbumids)) {
                        $tagrelationalbumids = array_unique($tagrelationalbumids);
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
                }
            }
            
            $result['taglist'] = $taglist;
            $result['recommendalbumlist'] = $recommendalbumlist;
        }

        // 返回成功json
        $this->showSuccJson($result);
    }
}
new info();