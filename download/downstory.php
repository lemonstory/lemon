<?php
/*
 * 下载某个故事
 */
include_once '../controller.php';
class downstory extends controller
{
    public function action()
    {
        $storyid = $this->getRequest("storyid");
        if (empty($storyid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        $uid = $this->getUid();
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid($uid);
        if (empty($uimid)) {
            $this->showErrorJson(ErrorConf::userImsiIdError());
        }
        
        // 获取故事文件地址、下载大小
        $storyobj = new Story();
        $storyinfo = $storyobj->get_story_info($storyid);
        if (empty($storyinfo)) {
            $this->showErrorJson(ErrorConf::storyInfoIsEmpty());
        }
        
        $downurllist = array();
        $aliossobj = new AliOss();
        $mediafile = $storyinfo['mediapath'];
        $mediaurl = $aliossobj->getMediaUrl($mediafile);
        $downurllist = array(
                "id" => $storyinfo['id'],
                "title" => $storyinfo['title'],
                "times" => $storyinfo['times'],
                "filesize" => $storyinfo['file_size'],
                "mediaurl" => $mediaurl
        );
        $this->showSuccJson($downurllist);
    }
}
new downstory();