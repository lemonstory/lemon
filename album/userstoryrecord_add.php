<?php

include_once '../controller.php';
class userstoryrecord_add extends controller
{
    function action() {
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
    	//$userid    = (int)$this->getRequest('userid', 0);
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

        $userstoryrecord = new UserStoryRecord();

        $res = $userstoryrecord->insert(array(
        	'userid'    => $uid,
        	'albumid'   => $albumid,
        	'storyid'   => $storyid,
        	'playtimes' => $playtimes,
        	'addtime'   => date('Y-m-d H:i:s'),
        ));
        
        if (!empty($res)) {
            // 添加收听数量
            $listenobj = new Listen();
            $listeninfo = $listenobj->getUserListenInfoByStoryId($uid, $storyid);
            if (empty($listeninfo)) {
                $babyid = $userinfo['defaultbabyid'];
                $userextobj = new UserExtend();
                $babyinfo = $userextobj->getUserBabyInfo($babyid);
                $babyagetype = $userextobj->getBabyAgeType($babyinfo['age']);
                $listenobj->addUserListenStory($uid, $albumid, $storyid, $babyagetype);
            }
        }

        $this->showSuccJson();
    }
}
new userstoryrecord_add();

