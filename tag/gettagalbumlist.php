<?php
include_once '../controller.php';
class gettagalbumlist extends controller
{
    public function action()
    {
        $currenttagid = $this->getRequest("currenttagid", 0);
        
        // 获取一级标签
        $tagnewobj = new TagNew();
        $firsttaglist = $tagnewobj->getFirstTagList(8);
        
        if (empty($currenttagid)) {
            $currenttaginfo = current($firsttaglist);
        } else {
            $currenttaginfo = $tagnewobj->getTagInfoById($currenttagid);
        }
        if (empty($currenttaginfo)) {
            // 标签错误
            $this->showErrorJson();
        }
        
        $currentpid = $currenttaginfo['pid'];
        if ($currentpid == 0) {
            // currenttagid为一级标签
        } else {
            // currenttagid为二级标签
        }
        
        
    }
}
new gettagalbumlist();