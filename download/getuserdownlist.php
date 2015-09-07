<?php
/*
 * 我的下载列表
 */
include_once '../controller.php';

class getdownlist extends controller
{
    public function action()
    {
        die();
        
        $taskstatus = $this->getRequest("taskstatus");
        $direction = $this->getRequest("direction", "down");
        $startid = $this->getRequest("startid", 0);
        $len = $this->getRequest("len", 0);
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $downloadlist = array();
        $downobj = new DownLoad();
        $list = $downobj->getUserDownLoadList($uid, $taskstatus, $direction, $startid, $len);
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