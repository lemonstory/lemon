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
        $file = "http://lemonpic.oss-cn-hangzhou.aliyuncs.com/2015/08/19/c4ca4238a0b923820dcc509a6f75849b.png";
        $filelen = 516424;
        
        // 开始下载
        $startBytes = 0;
        $bytesLen = 102400;
        $downobj = new DownLoad();
        //$res = $downobj->getFileContent($file, $startBytes, $bytesLen);
        $fp = fopen($file, 'r');
        var_dump($fp);
        die();
        
        //$downResult = $downobj->startDownload($file, $filelen);
        if ($downResult == false) {
            $this->emptyHttpHeader();
        }
    }
    
    public function emptyHttpHeader()
    {
        header("Accept-Length: 0");
        header("Content-Length: 0");
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-ridate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        die();
    }
}
new downalbum();