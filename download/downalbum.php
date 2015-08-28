<?php
/*
 * 下载整个专辑
 */
include_once '../controller.php';
class downalbum extends controller
{
    public function action()
    {
        $albumid = $this->getRequest("albumid");
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        if (empty($albumid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        // 获取专辑所有故事列表、以及音频文件地址、总下载大小
        $storyobj = new Story();
        $storylist = $storyobj->get_album_story_list($albumid);
        if (empty($storylist)) {
            $this->showErrorJson(ErrorConf::albumStoryListIsEmpty());
        }
        
        $downurllist = array();
        
        $aliossobj = new AliOss();
        $mediafile = "/2015/08/19/c4ca4238a0b923820dcc509a6f75849b.mp4";
        $mediaurl = $aliossobj->getMediaUrl($mediafile);
        $downurllist = array(
                array(
                        "name" => '',
                        "times" => 0,
                        "size" => 0,
                        "mediaurl" => $mediaurl
                        ),
                array(
                        "name" => '',
                        "times" => 0,
                        "size" => 0,
                        "mediaurl" => $mediaurl
                ),
        );
        $this->showSuccJson($downurllist);
    }
}
new downalbum();