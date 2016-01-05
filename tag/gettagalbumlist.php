<?php
include_once '../controller.php';
class gettagalbumlist extends controller
{
    public function action()
    {
        $currenttagid = $this->getRequest("currenttagid", 0);
        $secondtagnum = 10;
        $albumnum = 8;
        
        // 获取一级标签
        $tagnewobj = new TagNew();
        $firsttaglist = $tagnewobj->getFirstTagList(8);
        
        if (empty($currenttagid)) {
            // 默认选取第一个一级标签
            $currenttaginfo = current($firsttaglist);
            $currenttagid = $currenttaginfo['id'];
        } else {
            $currenttaginfo = $tagnewobj->getTagInfoById($currenttagid);
        }
        if (empty($currenttaginfo)) {
            $this->showErrorJson(ErrorConf::TagInfoIsEmpty());
        }
        $currentpid = $currenttaginfo['pid'];
        
        $selectfirsttagid = 0; // 当前选中的一级标签id
        $selectsecondtagid = 0; // 当前选中的二级标签id
        $secondtaglist = array();
        if ($currentpid == 0) {
            // 当前选中的currenttagid为一级标签，获取该标签下的二级标签列表
            $selectfirsttagid = $currenttagid;
            $selectsecondtagid = 0; // 表示二级标签选中全部
            $secondtaglist = $tagnewobj->getSecondTagList($selectfirsttagid, $secondtagnum);
        } else {
            // 当前选中的currenttagid为二级标签，获取该标签的父级下的所有二级标签列表
            $selectfirsttagid = $currentpid;
            $selectsecondtagid = $currenttagid;
            $secondtaglist = $tagnewobj->getSecondTagList($selectfirsttagid, $secondtagnum);
        }
        
        $tagids = array();
        if ($selectsecondtagid == 0) {
            // 二级标签为全部，选取一级标签下的所有专辑列表
            if (!empty($secondtaglist)) {
                foreach ($secondtaglist as $value) {
                    $tagids[] = $value['id'];
                }
            }
        } else {
            // 指定二级标签下的专辑列表
            $tagids = array($selectsecondtagid);
        }
        if (!empty($tagids)) {
            $tagids = array_unique($tagids);
        }
        
        $tagalbumlist = array();
        // 获取指定二级标签下，指定长度的专辑与标签关联列表
        $albumrelationlist = $tagnewobj->getAlbumTagRelationList($tagids, $albumnum);
        if (!empty($albumrelationlist)) {
            $albumids = array();
            foreach ($albumrelationlist as $relationinfo) {
                $albumids[] = $relationinfo['albumid'];
            }
            if (!empty($albumids)) {
                $albumids = array_unique($albumids);
                $albumidstr = implode(",", $albumids);
                // 获取专辑列表
                $albumobj = new Album();
                $tagalbumlist = $albumobj->get_list("id IN ($albumidstr)");
            }
        }
        
        $data = array(
            "selectfirsttagid" => $selectfirsttagid,
            "selectsecondtagid" => $selectsecondtagid,
            "firsttaglist" => $firsttaglist,
            "secondtaglist" => $secondtaglist,
            "tagalbumlist" => $tagalbumlist
        );
        $this->showSuccJson($data);
    }
}
new gettagalbumlist();