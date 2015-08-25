<?php

include_once '../controller.php';
class story_info extends controller
{
    function action() {

        $story = new Story();

        $story_id = (int)$this->getRequest("storyid", "1");;

        $story_info = $story->get_story_info($story_id);

		$story_info = $story->format_to_api($story_info);

        // 返回成功json
        $this->showSuccJson($story_info);
    }
}
new story_info();