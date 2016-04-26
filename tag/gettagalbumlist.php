<?php
include_once '../controller.php';
class gettagalbumlist extends controller
{
    public function action()
    {
        $currenttagid = $this->getRequest("currenttagid", 0);
        $recommend = $this->getRequest("recommend", 0); // 是否为推荐
        $hot = $this->getRequest("hot", 0); // 是否为最热门
        $goodcomment = $this->getRequest("goodcomment", 0); // 是否为好评榜
        
        $isgettag = $this->getRequest("isgettag", 1);
        $direction = $this->getRequest("direction", "down");
        $startrelationid = $this->getRequest("startrelationid", 0);
        $len = $this->getRequest("len", 20);
        if (empty($currenttagid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }

        //一级标签和二级标签的最大数量
        $firsttagnum = 8;
        $secondtagnum = 15;
        $selectfirsttagid = 0; // 当前选中的一级标签id
        $selectsecondtagid = 0; // 当前选中的二级标签id
        $firsttaglist = array(); // 一级标签列表
        $secondtaglist = array(); // 二级标签列表
        
        $tagnewobj = new TagNew();
        if ($isgettag == 1) {
            // 获取一级标签列表
            $firsttaglist = $tagnewobj->getFirstTagList($firsttagnum);
        }
        
        $currenttaginfo = current($tagnewobj->getTagInfoByIds($currenttagid));
        if (empty($currenttaginfo)) {
            $this->showErrorJson(ErrorConf::TagInfoIsEmpty());
        }
        $currentpid = $currenttaginfo['pid'];
        if ($currentpid == 0) {
            // 当前选中的currenttagid为一级标签，获取该标签下的二级标签列表
            $selectfirsttagid = $currenttagid;
            if ($recommend == 1) {
                $selectsecondtagid = "recommend"; // 推荐
            } elseif ($hot == 1) {
                $selectsecondtagid = "hot";// 表示二级标签选中最热门
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
            // 二级标签为全部
            $tagids = array($currenttagid);
        } else {
            // 指定二级标签下的专辑列表
            $tagids = array($selectsecondtagid);
        }
        if (!empty($tagids)) {
            $tagids = array_unique($tagids);
        }
        
        $tagalbumlist = array();
        $aliossobj = new AliOss();


        // 获取指定二级标签下，指定长度的专辑与标签关联列表
        // 避免取到故事数量为0的故事专辑,做排查处理
        //TODO:该业务效率偏低
        $albumrelationlist = array();
        $max_len = 500;
        $index = 0;
        $albumobj = new Album();

        //故事辑去重
        $albumrelationlist = $tagnewobj->getAlbumTagRelationListFromTag($tagids, $recommend, $hot, $goodcomment, $direction, $startrelationid, $max_len);
        $albumids = array();
        foreach ($albumrelationlist as $k => $relationlist) {
            $albumids[] = $relationlist['albumid'];
        }

        //键名保留不变
        $albumids = array_unique($albumids);

        $unique_album_relation_list = array();
        foreach ($albumids as $i => $item) {
            $unique_album_relation_list[] = $albumrelationlist[$i];
        }

        $albumrelationlist = $unique_album_relation_list;
        $album_infos = $albumobj->getListByIds($albumids);

        //取出$len长度的story_num大于0的$albumrelationlist
        if (!empty($albumrelationlist) && !empty($album_infos)) {
            foreach ($albumrelationlist as $k => $relationinfo) {
                if ($index < $len) {
                    $albumid = $relationinfo['albumid'];
                    if ($album_infos[$albumid]['story_num'] > 0 && $album_infos[$albumid]['status'] == 1) {
                        $index++;
                    } else {

                        //array_splice($albumrelationlist, $k, 1);
                        unset($albumrelationlist[$k]);
                    }
                } else {

                    $albumrelationlist = array_slice($albumrelationlist, 0, $index);
                    break;
                }
            }
        }

        $albumrelationlistcount = count($albumrelationlist);
        if ($albumrelationlistcount <= $len) {
            foreach ($albumrelationlist as $relationinfo) {
                $albumid = $relationinfo['albumid'];
                $relationinfo['albuminfo'] = array();
                if (!empty($album_infos[$albumid])) {
                    $albuminfo = $album_infos[$albumid];
                    if (!empty($albuminfo['cover'])) {
                        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
                    }
                    $albuminfo['listennum'] = $relationinfo['albumlistennum'];
                    $albuminfo['favnum'] = $relationinfo['albumfavnum'];
                    $albuminfo['star_level'] = $relationinfo['commentstarlevel'];
                    $relationinfo['albuminfo'] = $albuminfo;
                }
                $tagalbumlist[] = $relationinfo;
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