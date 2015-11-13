<?php
include_once '../controller.php';
class text extends controller 
{
    function action() {
        $from = "2015/10/31/006f52e9102a8d3be2fe5614f42ba989.jpg";
        $to = "album/111/006f52e9102a8d3be2fe5614f42ba989.jpg";
        $aliossobj = new AliOss();
        $aliossobj->copyImageOss($from, $to);
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
