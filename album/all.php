<?php

include_once '../controller.php';
class all extends controller
{
    function action() {
    	$direction = $this->getRequest("direction", "down");
        $startid = $this->getRequest("startid", 0);
        $len = $this->getRequest("len", 0);
        // 长度限制
        if ($len > 50) {
            $len = 50;
        }

        $album = new Album();

        $albumlist = $album->getAlbumList($direction, $startid, $len);

        $this->showSuccJson($albumlist);
    }
}
new all();

