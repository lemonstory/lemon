<?php

include_once '../controller.php';
class story_info extends controller
{
    function action() {

    	$fav   = new Fav();
        $story = new Story();

        $story_id   = (int)$this->getRequest("storyid", "1");;

        $story_info = $story->get_story_info($story_id);

		$story_info = $story->format_to_api($story_info);

		// 专辑收藏总数
		$fav_list   = $fav->getAlbumFavCount($story_info['albumid']);
		if ($fav_list) {
			$story_info['albumfavcount'] = $fav_list[$story_info['albumid']['num']];
		} else {
			$story_info['albumfavcount'] = 0;
		}

        // 返回成功json
        $this->showSuccJson($story_info);
    }
}
new story_info();