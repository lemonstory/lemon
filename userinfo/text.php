<?php
include_once '../controller.php';
class text extends controller 
{
    function action() {
        $uid = 10118;
        $uimid = 2621;
        $storyid = 80853;
        $albumid = 6819;
        
        $listenid = $uimid . "_" . $storyid;
        $addtime = date("Y-m-d H:i:s");
        $alislsobj = new AliSlsUserActionLog();
        $res = $alislsobj->addListenStoryActionLog($uimid, $uid, $listenid, $storyid, $albumid, getClientIp(), $addtime);
        var_dump($res);
        die();
        
        $uimid = 1;
        $albumid = 3329;
        $storyid = 121164;
        
        $recommendobj = new Recommend();
        //$res = $recommendobj->getRecommendHotList(3, 9);
        //$res = $recommendobj->getSameAgeListenList(1, 1, 9);
        //$res = $recommendobj->getNewOnlineList(1, 1, 9);
        //$res = $recommendobj->getFocusList(6);
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
