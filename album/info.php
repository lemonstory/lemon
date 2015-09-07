<?php

include_once '../controller.php';
class info extends controller
{
    function action() {
    	$result  = array();
        $album   = new Album();
        $story   = new Story();
        $comment = new Comment();
        $userstoryrecord = new UserStoryRecord();

        $uid     = $this->getUid();
        $uid     = 1;

        $album_id = $storyid = $this->getRequest("albumid", "1");;
        $album_info = $album->get_album_info($album_id);

        $result['albuminfo'] = $album->format_to_api($album_info);
        $playinfo            = $userstoryrecord->get_last_record("`userid`={$uid} and `albumid`={$album_id} ");
        if ($playinfo) {
            $playinfo = $userstoryrecord->format_to_api($playinfo);
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

