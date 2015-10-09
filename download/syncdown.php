<?php
/*
 * 上传同步用户下载的专辑、故事状态，用于统计
 */
include_once '../controller.php';
class syncdown extends controller
{
    public function action()
    {
        $syncdata = $this->getRequest("syncdata");
        if (empty($syncdata)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        $data = json_decode($syncdata, true);
        if (!is_array($data)) {
        	$this->showErrorJson(ErrorConf::paramError());
        }
        
        $uid = $this->getUid();
    	$userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid($uid);
        if (empty($uimid)) {
            $this->showErrorJson(ErrorConf::userImsiIdError());
        }
        
        $downloadobj = new DownLoad();
        $actionlogobj = new ActionLog();
        foreach ($data as $value) {
        	if (empty($value['albumid']) || empty($value['storyid']) || empty($value['status'])) {
        		continue;
        	}
        	$res = $downloadobj->addDownLoadStoryInfo($uimid, $value['albumid'], $value['storyid'], $value['status']);
        	if ($res == true) {
		        MnsQueueManager::pushActionLogQueue($uimid, $uid, $actionlogobj->ACTION_TYPE_DOWNLOAD_STORY);
        	}
        }
        
    }
    
}
new syncdown();