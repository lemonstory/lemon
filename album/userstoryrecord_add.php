<?php

include_once '../controller.php';
class userstoryrecord_add extends controller
{
    function action() {
        $uid = $this->getUid();
        $uimid = $this->getUimid($uid);
        if (empty($uimid)) {
            $this->showErrorJson(ErrorConf::userImsiIdError());
        }
        
        $albumid   = (int)$this->getRequest('albumid', 0);
        $storyid   = (int)$this->getRequest('storyid', 0);
        $playtimes = (int)$this->getRequest('playtimes', 0);
        if (empty($albumid) || empty($storyid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        $albuminfo = $storyinfo = array();

        $album = new Album();
        $story = new Story();

        if ($albumid) {
            $albuminfo = $album->get_album_info($albumid);
        }
        if ($storyid) {
            $storyinfo = $story->get_story_info($storyid);
        }
        if (!$albuminfo) {
            return $this->showErrorJson(ErrorConf::albumInfoIsEmpty());
        }
        if (!$storyinfo) {
            return $this->showErrorJson(ErrorConf::storyInfoIsEmpty());
        }

        $useralbumlog = new UserAlbumLog();
        $lastid = $useralbumlog->insert(array(
            'uid'       => $uimid,
            'albumid'   => $albumid,
            'storyid'   => $storyid,
            'playtimes' => $playtimes,
            'addtime'   => date('Y-m-d H:i:s'),
        ));
        if ($lastid) {
            $useralbumlastlog = new UserAlbumLastlog();
            $useralbumlastlog->replace(array(
                'uid'       => $uimid,
                'albumid'   => $albumid,
                'lastlogid' => $lastid,
            ));
        }

        if (!empty($lastid)) {
            // 添加收听处理队列
            MnsQueueManager::pushListenStoryQueue($uimid, $storyid);
        }

        $this->showSuccJson();
    }
}
new userstoryrecord_add();