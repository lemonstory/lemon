<?php
include_once '../controller.php';
class text extends controller 
{
    function action() {
        $uid = 10001;
        $albumid = 3329;
        $favobj = new Fav();
        //$res = $favobj->getUserFavInfoByAlbumId($uid, $albumid);
        //$res = $favobj->clearUserFavInfoByAlbumIdCache($uid, $albumid);
        //$res = $favobj->getAlbumFavCount($albumid);
        //$res = $favobj->clearAlbumFavCountCache($albumid);
        //$res = $favobj->getUserFavCount($uid);
        //$res = $favobj->clearUserFavCountCache($uid);
        var_dump($res);
        die();
        
        
        $file = "2015/08/19/c4ca4238a0b923820dcc509a6f75849b.png";
        $aliossobj = new AliOss();
        
        //$imgurl = $aliossobj->getImageUrlNg($file);
        //$smallimgurl = $aliossobj->getImageUrlNg($file, "@!200x200");
        
        $mediafile = "/2015/08/19/c4ca4238a0b923820dcc509a6f75849b.mp4";
        //$mediaurl = $aliossobj->getMediaUrl($mediafile);
        
        //$focuscover = $aliossobj->getFocusUrl(1);
        
        $smartyObj = $this->getSmartyObj();
        $smartyObj->display("userinfo/text.html");
    }
}
new text();
