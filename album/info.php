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
            $result['albuminfo']['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $result['albuminfo']['cover'], 200, $result['albuminfo']['cover_time']);
        } else {
            $result['albuminfo']['cover'] = $result['albuminfo']['s_cover'];
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
        $result['commentlist'] = $comment->get_comment_list("`albumid`={$album_id}", "ORDER BY `id` DESC ", 'up', 0, $len);

        // 返回成功json
        $this->showSuccJson($result);
    }
}
new info();

