<?php

include_once '../controller.php';
class all extends controller
{
    function action() {
        $album = new Album();
        $album_id = $storyid = $this->getRequest("albumid", "1");;
        $album_info = $album->get_album_info($album_id);
        foreach ($story_list as $k => $v) {
        	# code...
        }
        $album_info['storylist'] = $story->format();
    }
}
new all();

