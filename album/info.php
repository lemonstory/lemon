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



        $uid     = $this->getUid();

        $album_id = $storyid = $this->getRequest("albumid", "1");;
        $album_info = $album->get_album_info($album_id);

        $result['albuminfo']  = $album->format_to_api($album_info);
        // 获取播放信息
        $useralbumlastloginfo = $useralbumlastlog->getInfo("`uid`={$uid} and `albumid`={$album_id} ");
        if ($useralbumlastloginfo) {
            $useralbumloginfo = $useralbumlog->getInfo("`logid`={$useralbumlastloginfo['lastlogid']}");
            $playinfo = $useralbumlog->format_to_api($useralbumloginfo);
        } else {
            $playinfo = [];
        }
        $result['playinfo'] = $playinfo;
        // 是否收藏
        $favinfo = $fav->getUserFavInfoByAlbumId($uid, $album_id);
        if ($favinfo) {
            $result['fav'] = 1;
        } else {
            $result['fav'] = 0;
        }
        // 收听数量
        $albumlistennum = $listenobj->getAlbumListenNum($album_id);
        if ($albumlistennum) {
            $result['albumlistennum'] = $albumlistennum[$album_id];
        } else {
            $result['albumlistennum'] = 0;
        }
        

        $story_list = $story->get_list("`album_id`={$album_id}");
        foreach ($story_list as $k => $v) {
        	$result['storylist'][] = $story->format_to_api($v);
        }
        // 评论数量
        $result['commentcount'] = $comment->get_total("`albumid`={$album_id}");
        $result['commentlist'] = $comment->get_comment_list();

        // 返回成功json
        $this->showSuccJson($result);
    }
}
new info();

