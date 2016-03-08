<?php
include_once '../controller.php';
class text extends controller 
{
    function action() {
        set_time_limit(0);
        $url_page = "http://play.ximalaya.com/trackcount/12602679/played?device=android";
        $ch = curl_init();
        for ($i=0; $i<1000; $i++) {
            curl_setopt($ch,CURLOPT_URL, $url_page);
            $content = curl_exec($ch);
            var_dump($i);
            sleep(1);
        }
        curl_close($ch);
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
