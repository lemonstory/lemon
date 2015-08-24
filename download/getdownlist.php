<?php
include_once '../controller.php';

class getdownlist extends controller
{
    public function action()
    {
        $taskstatus = $this->getRequest("taskstatus");
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $downloadlist = array();
        $downobj = new DownLoad();
        $list = $downobj->getUserDownLoadList($uid, $taskstatus);
        if (!empty($list)) {
            $albumids = array();
            foreach ($list as $value) {
                $albumids[] = $value['albumid'];
            }
            
            // 获取专辑数据
            $albumlist = array();
            
            foreach ($list as $value) {
                $value['albuminfo'] = $value['albumid'];
                $downloadlist[] = $value;
            }
        }
        
        $this->showSuccJson($downloadlist);
    }
}
new getdownlist();