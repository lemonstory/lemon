<?php
include_once '../controller.php';
class hotsearch extends controller
{
    public function action()
    {
        $len = $this->getRequest("len", 10);
        
        $searchcountobj = new SearchCount();
        $hotlist = $searchcountobj->getHotSearchContentList($len);
        $this->showSuccJson($hotlist);
    }
}
new hotsearch();