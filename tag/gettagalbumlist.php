<?php
include_once '../controller.php';
class gettagalbumlist extends controller
{
    public function action()
    {
        $currenttagid = $this->getRequest("currenttagid", 0);
        $hotrecommend = $this->getRequest("hotrecommend", 0); // 是否为最热门
        $goodcomment = $this->getRequest("goodcomment", 0); // 是否为好评榜
        
        $isgettag = $this->getRequest("isgettag", 1);
        $direction = $this->getRequest("direction", "down");
        $startalbumid = $this->getRequest("startalbumid", 0);
        $len = $this->getRequest("len", 20);
        if (empty($currenttagid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        $firsttagnum = 8;
        $secondtagnum = 10;
        $selectfirsttagid = 0; // 当前选中的一级标签id
        $selectsecondtagid = 0; // 当前选中的二级标签id
        $firsttaglist = array(); // 一级标签列表
        $secondtaglist = array(); // 二级标签列表
        
        $tagnewobj = new TagNew();
        if ($isgettag == 1) {
            // 获取一级标签列表
            $firsttaglist = $tagnewobj->getFirstTagList($firsttagnum);
        }
        
        $currenttaginfo = $tagnewobj->getTagInfoById($currenttagid);
        if (empty($currenttaginfo)) {
            $this->showErrorJson(ErrorConf::TagInfoIsEmpty());
        }
        $currentpid = $currenttaginfo['pid'];
        if ($currentpid == 0) {
            // 当前选中的currenttagid为一级标签，获取该标签下的二级标签列表
            $selectfirsttagid = $currenttagid;
            if ($hotrecommend == 1) {
                $selectsecondtagid = "hotrecommend";// 表示二级标签选中最热门
            } elseif ($goodcomment == 1) {
                $selectsecondtagid = "goodcomment"; // 表示二级标签选中好评榜
            } else {
                $selectsecondtagid = 0; // 表示二级标签选中全部
            }
            if ($isgettag == 1) {
                $secondtaglist = $tagnewobj->getSecondTagList($selectfirsttagid, $secondtagnum);
            }
        } else {
            // 当前选中的currenttagid为二级标签，获取该标签的父级下的所有二级标签列表
            $selectfirsttagid = $currentpid;
            $selectsecondtagid = $currenttagid;
            if ($isgettag == 1) {
                $secondtaglist = $tagnewobj->getSecondTagList($selectfirsttagid, $secondtagnum);
            }
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
        $albumrelationlist = $tagnewobj->getAlbumTagRelationList($tagids, $direction, $startalbumid, $len);
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
        
        $specialtaglist = array(
                array("name" => "推荐", "paramkey" => "recommend", "paramvalue" => 1),
                array("name" => "最热门", "paramkey" => "hot", "paramvalue" => 1),
                array("name" => "好评榜", "paramkey" => "goodcomment", "paramvalue" => 1),
                );
        $data = array(
            "selectfirsttagid" => $selectfirsttagid,
            "selectsecondtagid" => $selectsecondtagid,
            "firsttaglist" => $firsttaglist,
            "secondtaglist" => $secondtaglist,
            "tagalbumlist" => $tagalbumlist,
            "specialtaglist" => $specialtaglist
        );
        $this->showSuccJson($data);
    }
}
new gettagalbumlist();