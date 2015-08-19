<?php
include_once '../controller.php';
class text extends controller 
{
    function action() {
        $type = $this->getRequest("type");
        
        if ($type == 1) {
            $storyid = $this->getRequest("storyid", "1");
            $uploadobj = new Upload();
            $res = $uploadobj->uploadStoryMedia($storyid, "media");
        } elseif ($type == 2) {
            $albumid = $this->getRequest("albumid", "1");
            $uploadobj = new Upload();
            $res = $uploadobj->uploadAlbumImage($albumid, "content");
        }
        
        $smartyObj = $this->getSmartyObj();
        $smartyObj->display("userinfo/text.html");
    }
}
new text();