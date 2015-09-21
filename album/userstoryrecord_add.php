<?php

include_once '../controller.php';
class userstoryrecord_add extends controller
{
    function action() {
        $uid = $this->getUid();

        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }

        $albumid   = (int)$this->getRequest('albumid', 0);
        $storyid   = (int)$this->getRequest('storyid', 0);
        $playtimes = (int)$this->getRequest('playtimes', 0);
        if (empty($albumid) || empty($storyid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
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
            'uid'       => $uid,
            'albumid'   => $albumid,
            'storyid'   => $storyid,
            'playtimes' => $playtimes,
            'addtime'   => date('Y-m-d H:i:s'),
        ));
        if ($lastid) {
            $useralbumlastlog = new UserAlbumLastlog();
            $useralbumlastlog->replace(array(
                'uid'       => $uid,
                'albumid'   => $albumid,
                'lastlogid' => $lastid,
            ));
        }

        $userstoryrecord = new UserStoryRecord();

        if (!empty($lastid)) {
            // 添加收听处理队列
            QueueManager::pushListenStoryQueue($uid, $storyid);
        }

        $this->showSuccJson();
    }
}
new userstoryrecord_add();