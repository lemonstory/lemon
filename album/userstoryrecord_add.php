<?php

include_once '../controller.php';
class userstoryrecord_add extends controller
{
    function action() {
    	$userid    = (int)$this->getRequest('userid', 0);
    	$albumid   = (int)$this->getRequest('albumid', 0);
    	$storyid   = (int)$this->getRequest('storyid', 0);
    	$playtimes = (int)$this->getRequest('playtimes', 0);

    	$albuminfo = $storyinfo = array();

        $album = new Album();
        $story = new Story();

        if (!$userid) {
    		return $this->showErrorJson(ErrorConf::noLogin());
    	}

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

        $userstoryrecord->insert(array(
        	'userid'    => $userid,
        	'albumid'   => $albumid,
        	'storyid'   => $storyid,
        	'playtimes' => $playtimes,
        	'addtime'   => date('Y-m-d H:i:s'),
        ));

        $this->showSuccJson();
    }
}
new userstoryrecord_add();

