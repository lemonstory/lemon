<?php
include_once '../controller.php';

class downalbum extends controller
{
    public function action()
    {
        $albumid = $this->getRequest("albumid");
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        if (empty($albumid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        // 获取专辑信息
        
        // 获取专辑所有故事列表、以及音频文件地址、总下载大小
        
        
    }
    
    /* public function emptyHttpHeader()
    {
        header("Accept-Length: 0");
        header("Content-Length: 0");
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-ridate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        die();
    } */
}
new downalbum();