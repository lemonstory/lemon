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
        
        $albumids = array();
        $storyids = array();
    	foreach ($data as $value) {
        	if (empty($value['clientid']) || empty($value['albumid']) || empty($value['storyid']) || empty($value['status'])) {
        		continue;
        	}
        	$albumids[] = $value['albumid'];
        	$storyids[] = $value['storyid'];
    	}
    	if (empty($albumids) || empty($storyids)) {
    		$this->showErrorJson(ErrorConf::paramError());
    	}
    	$albumids = array_unique($albumids);
    	$storyids = array_unique($storyids);
    	
    	$albumobj = new Album();
    	$albumlist = $albumobj->getListByIds($albumids);
    	if (empty($albumlist)) {
    		$this->showErrorJson(ErrorConf::albumInfoIsEmpty());
    	}
    	$storyobj = new Story();
    	$storylist = $storyobj->getListByIds($storyids);
    	if (empty($storylist)) {
    		$this->showErrorJson(ErrorConf::storyInfoIsEmpty());
    	}
        
        $downloadobj = new DownLoad();
        $actionlogobj = new ActionLog();
        $alislsobj = new AliSlsUserActionLog();
        $successdata = array();
        $addtime = date("Y-m-d H:i:s");
        $ip = getClientIp();
        foreach ($data as $value) {
        	$tmplist = array('clientid' => $value['clientid'], 'result' => false);
        	if (empty($value['clientid']) || empty($value['albumid']) || empty($value['storyid']) || empty($value['status'])) {
        		continue;
        	}
        	if (empty($albumlist[$value['albumid']]) || empty($storylist[$value['storyid']])) {
        		continue;
        	}
        	$lastdownloadid = $downloadobj->addDownLoadStoryInfo($uimid, $value['albumid'], $value['storyid'], $value['status']);
        	if ($lastdownloadid == true) {
        		$tmplist = array('clientid' => $value['clientid'], 'result' => true);
		        MnsQueueManager::pushActionLogQueue($uimid, $value['storyid'], $actionlogobj->ACTION_TYPE_DOWNLOAD_STORY);
		        
		        // add sls log
		        $alislsobj->addDownloadStoryActionLog($uimid, $uid, $lastdownloadid, $value['storyid'], $value['albumid'], $ip, $addtime);
        	}
        	
        	$successdata[] = $tmplist;
        }
        
        $this->showSuccJson($successdata);
    }
    
}
new syncdown();