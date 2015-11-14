<?php

include_once '../controller.php';
class info extends controller
{
    function action() {
    	$result  = array();
        $album            = new Album();
        $story            = new Story();
        $comment          = new Comment();
        $useralbumlog     = new UserAlbumLog();
        $useralbumlastlog = new UserAlbumLastlog();
        $fav              = new Fav();
        $listenobj        = new Listen();



        $uid = $this->getUid();

        $album_id = $this->getRequest("albumid", "1");
        // 专辑信息
        $result['albuminfo']  = $album->get_album_info($album_id);

        $aliossobj = new AliOss();
        if ($result['albuminfo']['cover']) {
            $result['albuminfo']['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $result['albuminfo']['cover'], 200, $result['albuminfo']['cover_time']);
        } else {
            $result['albuminfo']['cover'] = $result['albuminfo']['s_cover'];
        }

        // 获取播放信息
        // $useralbumlastloginfo = $useralbumlastlog->getInfo("`uimid`={$uid} and `albumid`={$album_id} ");
        // if ($useralbumlastloginfo) {
        //     $useralbumloginfo = $useralbumlog->getInfo("`logid`={$useralbumlastloginfo['lastlogid']}");
        //     $playinfo = $useralbumlog->format_to_api($useralbumloginfo);
        // } else {
        //     $playinfo = array();
        // }
        // $result['playinfo'] = $playinfo;
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

        $result['storylist'] = $story->get_album_story_list($album_id);
        // 评论数量
        $result['albuminfo']['commentnum'] = (int)$comment->get_total("`albumid`={$album_id}");
        $result['commentlist'] = $comment->get_comment_list("`albumid`={$album_id}", "ORDER BY `id` DESC ");

        // 返回成功json
        $this->showSuccJson($result);
    }
}
new info();

