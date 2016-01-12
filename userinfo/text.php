<?php
include_once '../controller.php';
class text extends controller 
{
    function action() {
        /* $key = RedisKey::getQqLoginFirstKey("12533DC7E4738C7649395DB191903F2E");
        $redisObj = AliRedisConnecter::connRedis('cache');
        $cacheData = $redisObj->delete($key); */
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
