<?php
include_once '../controller.php';

class getTagAlbumList extends controller
{
    public function action()
    {

        $currentTagId = $this->getRequest("currenttagid", 0); //指定标签id，可以是一级标签id，也可以是二级标签id
        $isGetTag = $this->getRequest("isgettag", 1); //是否获取一级和二级标签列表，默认为1获取，分页时不重复获取传0即可

        $recommend = $this->getRequest("recommend", 0); // 是否为推荐
        $hot = $this->getRequest("hot", 0); // 是否为最热门
        $goodComment = $this->getRequest("goodcomment", 0); // 是否为好评榜

        $direction = $this->getRequest("direction", "down");
        $startRelationId = $this->getRequest("startrelationid", 0);
        $len = $this->getRequest("len", 20);

        if (empty($currentTagId)) {
            $this->showErrorJson(ErrorConf::paramError());
        }

        $firstTagNum = 8; //一级标签的最大数量
        $secondTagNum = 15; //二级标签的最大数量
        $selectFirstTagId = 0; // 当前选中的一级标签id
        $selectSecondTagId = 0; // 当前选中的二级标签id
        $firstTagList = array(); // 一级标签列表
        $secondTagList = array(); // 二级标签列表
        $tagNewObj = new TagNew();

        $currentTagInfo = current($tagNewObj->getTagInfoByIds($currentTagId));
        if (empty($currentTagInfo)) {
            $this->showErrorJson(ErrorConf::TagInfoIsEmpty());
        }

        $currentpid = $currentTagInfo['pid'];
        if ($currentpid == 0) {
            // 当前选中的currenttagid为一级标签，获取该标签下的二级标签列表
            $selectFirstTagId = $currentTagId;
            if ($recommend == 1) {
                $selectSecondTagId = "recommend"; // 推荐
            } elseif ($hot == 1) {
                $selectSecondTagId = "hot";// 表示二级标签选中最热门
            } elseif ($goodComment == 1) {
                $selectSecondTagId = "goodcomment"; // 表示二级标签选中好评榜
            } else {
                $selectSecondTagId = 0; // 表示二级标签选中全部
            }
        } else {
            // 当前选中的currenttagid为二级标签，获取该标签的父级下的所有二级标签列表
            $selectFirstTagId = $currentpid;
            $selectSecondTagId = $currentTagId;
        }

        if ($isGetTag == 1) {
            //获取一级标签列表
            $firstTagList = $tagNewObj->getFirstTagList($firstTagNum);
            //获取二级标签列表
            $secondTagList = $tagNewObj->getSecondTagList($selectFirstTagId, $secondTagNum);
        }

        $tagIds = array();
        if ($selectSecondTagId == 0) {
            // 二级标签为全部
            $tagIds = array($currentTagId); //array(0)?
        } else {
            // 指定二级标签下的专辑列表
            $tagIds = array($selectSecondTagId);
        }
        if (!empty($tagIds)) {
            $tagIds = array_unique($tagIds); //?
        }

        $tagAlbumList = array();
        $aliossObj = new AliOss();


        // 获取指定二级标签下，指定长度的专辑与标签关联列表
        // 避免取到故事数量为0的故事专辑,做排查处理
        //TODO:该业务效率偏低
        $albumRelationList = array();
        $index = 0;
        $albumObj = new Album();


        $albumRelationList = $tagNewObj->getAlbumTagRelationListFromTag($tagIds, $recommend, $hot, $goodComment, $direction, $startRelationId, $len);
        $albumIds = array();
        foreach ($albumRelationList as $k => $relationlist) {
            $albumIds[] = $relationlist['albumid'];
        }

        //故事辑去重
        //键名保留不变
//        $albumIds = array_unique($albumIds);
//
//        $unique_album_relation_list = array();
//        foreach ($albumIds as $key => $item) {
//            $unique_album_relation_list[] = $albumRelationList[$key];
//        }
//
//        $albumRelationList = $unique_album_relation_list;
        $albumInfos = $albumObj->getListByIds($albumIds);


        //取出$len长度的story_num大于0的$albumRelationList
//        if (!empty($albumRelationList) && !empty($albumInfos)) {
//            foreach ($albumRelationList as $k => $relationInfo) {
//                if ($index < $len) {
//                    $albumid = $relationInfo['albumid'];
//                    if ($albumInfos[$albumid]['story_num'] > 0 && $albumInfos[$albumid]['status'] == 1) {
//                        $index++;
//                    } else {
//
//                        //array_splice($albumRelationList, $k, 1);
//                        unset($albumRelationList[$k]);
//                    }
//                } else {
//
//                    $albumRelationList = array_slice($albumRelationList, 0, $index);
//                    break;
//                }
//            }
//        }

        $albumRelationListcount = count($albumRelationList);
        if ($albumRelationListcount <= $len) {
            foreach ($albumRelationList as $relationInfo) {
                $albumid = $relationInfo['albumid'];
                $relationInfo['albuminfo'] = array();
                if (!empty($albumInfos[$albumid])) {
                    $albumInfo = $albumInfos[$albumid];
                    if (!empty($albumInfo['cover'])) {
                        $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albumInfo['cover'], 460, $albumInfo['cover_time']);
                    }


                    $albumInfo['listennum'] = intval($relationInfo['albumlistennum']) > 0 ? substr($relationInfo['albumlistennum'], 0, 5) : 0;
                    $albumInfo['favnum'] = $relationInfo['albumfavnum'];
                    $albumInfo['star_level'] = $relationInfo['commentstarlevel'];
                    $relationInfo['albuminfo'] = $albumInfo;
                }
                $tagAlbumList[] = $relationInfo;
            }
        }


        $specialtaglist = array(
            array("name" => "推荐", "paramkey" => "recommend", "paramvalue" => 1),
            array("name" => "最热门", "paramkey" => "hot", "paramvalue" => 1),
            array("name" => "好评榜", "paramkey" => "goodcomment", "paramvalue" => 1),
        );
        $data = array(
            "selectfirsttagid" => $selectFirstTagId,
            "selectsecondtagid" => $selectSecondTagId,
            "firsttaglist" => $firstTagList,
            "secondtaglist" => $secondTagList,
            "tagalbumlist" => $tagAlbumList,
            "specialtaglist" => $specialtaglist
        );
        $this->showSuccJson($data);
    }
}

new getTagAlbumList();