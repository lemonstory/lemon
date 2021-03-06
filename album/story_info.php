<?php

include_once '../controller.php';
class story_info extends controller
{
    function action() {

    	$fav   = new Fav();
        $story = new Story();

        $story_id   = (int)$this->getRequest("storyid", "1");;

        $story_info = $story->get_story_info($story_id);

        $aliossobj = new AliOss();
        if ($story_info['cover']) {
            $story_info['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_STORY, $story_info['cover'], 200, $story_info['cover_time']);
        } else {
            $story_info['cover'] = $story_info['s_cover'];
        }

		// 专辑收藏总数
		$fav_list   = $fav->getAlbumFavCount($story_info['albumid']);
		if ($fav_list) {
			$story_info['favnum'] = (int)$fav_list[$story_info['albumid']]['num'];
		} else {
			$story_info['favnum'] = 0;
		}

        // 返回成功json
        $this->showSuccJson($story_info);
    }
}
new story_info();