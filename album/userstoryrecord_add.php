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

        $content = $this->getRequest('content');
        if (!$content) {
            $this->showErrorJson(ErrorConf::UserAlbumLogContentError());
        }
        $r = json_decode($content, true);
        if (!$r) {
            $this->showErrorJson(ErrorConf::UserAlbumLogContentError());
        }

        $useralbumlog = new UserAlbumLog();
        $useralbumlastlog = new UserAlbumLastlog();

        foreach ($r as $k => $v) {
            $lastid = $useralbumlog->insert(array(
                'uimid'       => $uimid,
                'albumid'   => $v['albumid'],
                'storyid'   => $v['storyid'],
                'playtimes' => $v['playtimes'],
                'addtime'   => date('Y-m-d H:i:s'),
            ));
            if ($lastid) {
                $useralbumlastlog->replace(array(
                    'uimid'       => $uimid,
                    'albumid'   => $v['albumid'],
                    'lastlogid' => $lastid,
                ));
            }

            if (!empty($lastid)) {
                // 添加收听处理队列
                MnsQueueManager::pushListenStoryQueue($uimid, $v['storyid']);
            }
        }

        $this->showSuccJson();
    }
}
new userstoryrecord_add();