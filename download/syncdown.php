<?php
/*
 * 同步用户下载的专辑、故事状态
 */
include_once '../controller.php';
class syncdown extends controller
{
    public function action()
    {
        $uid = $this->getUid();
        $downtype = $this->getRequest("downtype");// album or story
    }
    
}
new syncdown();