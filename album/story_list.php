<?php

include_once '../controller.php';
class story_list extends controller
{
    function action() {
    	$direction = $this->getRequest("direction", "down");
        $startid = $this->getRequest("startid", 0);
        $albumid = $this->getRequest("albumid", 0);
        $len = $this->getRequest("len", 0);

        $story = new Story();

        $storylist = $story->getStoryList($albumid, $direction, $startid, $len);
        $aliossobj = new AliOss();
        foreach ($storylist as $k => $v) {
        	if ($storylist[$k]['cover']) {
	            $storylist[$k]['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_STORY, $v['cover'], 200, $v['cover_time']);
	        } else {
	            $storylist[$k]['cover'] = $v['s_cover'];
	        }
        }

        $this->showSuccJson($storylist);
    }
}
new story_list();

