<?php

include_once '../controller.php';
class story_list extends controller
{
    function action() {
    	$direction = $this->getRequest("direction", "down");
        $startid = $this->getRequest("startid", 0);
        $len = $this->getRequest("len", 0);

        $story = new Story();

        $storylist = $story->getStoryList($direction, $startid, $len);

        $newstorylist = array();

        foreach ($storylist as $k => $v) {
        	$newstorylist[] = $story->format_to_api($v);
        }

        $this->showSuccJson($newstorylist);
    }
}
new story_list();

