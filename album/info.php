<?php

include_once '../controller.php';
class info extends controller
{
    function action() {
    	$result  = array();
        $album   = new Album();
        $story   = new Story();
        $comment = new Comment();
        $useralbumlog = new UserAlbumLog();
        $useralbumlastlog = new UserAlbumLastlog();

        $uid     = $this->getUid();
        $uid =1;

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

        $story_list = $story->get_list("`album_id`={$album_id}");
        foreach ($story_list as $k => $v) {
        	$result['storylist'][] = $story->format_to_api($v);
        }
        $result['commentlist'] = $comment->get_comment_list();

        // 返回成功json
        $this->showSuccJson($result);
    }
}
new info();

