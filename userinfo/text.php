<?php
include_once '../controller.php';
class text extends controller 
{
    function action() {
        MnsQueueManager::pushActionLogQueue("10001", "login");
        die();
        $type = $this->getRequest("type");
        
        if ($type == 1) {
            $storyid = $this->getRequest("storyid", "1");
            $uploadobj = new Upload();
            $res = $uploadobj->uploadStoryMedia($storyid, "media");
        } elseif ($type == 2) {
            $albumid = $this->getRequest("albumid", "1");
            $uploadobj = new Upload();
            $res = $uploadobj->uploadAlbumImage($albumid, "content");
        } elseif ($type == 3) {
            $picid = 1;
            $uploadobj = new Upload();
            $res = $uploadobj->uploadFocusImage($_FILES['content'], $picid);
            var_dump($res);
            die();
        }
        
        $file = "2015/08/19/c4ca4238a0b923820dcc509a6f75849b.png";
        $aliossobj = new AliOss();
        
        //$imgurl = $aliossobj->getImageUrlNg($file);
        //$smallimgurl = $aliossobj->getImageUrlNg($file, "@!200x200");
        
        $mediafile = "/2015/08/19/c4ca4238a0b923820dcc509a6f75849b.mp4";
        //$mediaurl = $aliossobj->getMediaUrl($mediafile);
        
        $focuscover = $aliossobj->getFocusUrl(1);
        var_dump($focuscover);
        
        $smartyObj = $this->getSmartyObj();
        $smartyObj->display("userinfo/text.html");
    }
}
new text();
