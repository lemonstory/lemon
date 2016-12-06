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

        $albumlist = $album->getAlbumList($direction, $startid, $len, " ORDER BY `id` DESC,`view_order` DESC ");

        $aliossobj = new AliOss();
        foreach ($albumlist as $k => $v) {
            if ($v['cover']) {
                $albumlist[$k]['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $v['cover'], 200, $v['cover_time']);
            } else {
                $albumlist[$k]['cover'] = $v['s_cover'];
            }
        }

        $this->showSuccJson($albumlist);
    }
}
new all();

