<?php

include_once '../controller.php';
class userstoryrecord_get extends controller
{
    function action() {
    	$userid    = (int)$this->getRequest('userid', 0);

    	if (!$userid) {
    		return $this->showErrorJson(ErrorConf::noLogin());
    	}

        $userstoryrecord = new UserStoryRecord();
    	
    	$lastinfo = $userstoryrecord->get_last_record("`userid`={$userid}");

    	if ($lastinfo) {
    		$this->showSuccJson($lastinfo);
    	} else {
    		$this->showErrorJson(ErrorConf::userListenStoryNotExists());
    	}

        
    }
}
new userstoryrecord_get();

