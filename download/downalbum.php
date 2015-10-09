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
        if (empty($albumid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
    	
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid();
        if (empty($uimid)) {
            $this->showErrorJson(ErrorConf::userImsiIdError());
        }
        
        // 获取专辑所有故事列表、以及音频文件地址、总下载大小
        $storyobj = new Story();
        $storylist = $storyobj->get_album_story_list($albumid);
        if (empty($storylist)) {
            $this->showErrorJson(ErrorConf::albumStoryListIsEmpty());
        }
        
        $downurllist = array();
        $aliossobj = new AliOss();
        foreach ($storylist as $key => $storyinfo) {
            $downurllist[$key]['id'] = $storyinfo['id'];
            $downurllist[$key]['title'] = $storyinfo['title'];
            $downurllist[$key]['times'] = $storyinfo['times'];
            $downurllist[$key]['filesize'] = $storyinfo['file_size'];
            
            $mediafile = $storyinfo['mediapath'];
            $downurllist[$key]['mediaurl'] = $aliossobj->getMediaUrl($mediafile);
        }
        
        $this->showSuccJson($downurllist);
    }
}
new downalbum();