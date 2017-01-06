<?php
include_once '../../controller.php';


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
        $albumObj = new Album();


        $albumRelationList = $tagNewObj->getAlbumTagRelationListFromTag($tagIds, $recommend, $hot, $goodComment,
            $direction, $startRelationId, $len);
        $albumIds = array();
        foreach ($albumRelationList as $k => $relationlist) {
            $albumIds[] = $relationlist['albumid'];
        }

        // 专辑信息
        $albumInfos = $albumObj->getListByIds($albumIds);

        // 获取推荐语
        $recommendDescObj = new RecommendDesc();
        $recommendDescList = $recommendDescObj->getAlbumRecommendDescList($albumIds);


        $albumRelationListcount = count($albumRelationList);
        if ($albumRelationListcount <= $len) {
            foreach ($albumRelationList as $relationInfo) {

                $tagAlbumItem['id'] = $relationInfo['id'];
                $tagAlbumItem['tagid'] = $relationInfo['tagid'];
                $tagAlbumItem['albumid'] = $relationInfo['albumid'];
                $tagAlbumItem['id'] = $relationInfo['id'];

                $albumid = $relationInfo['albumid'];
                if (!empty($albumInfos[$albumid])) {
                    $albumInfo = array();
                    $albumInfo['id'] = $albumInfos[$albumid]['id'];
                    $albumInfo['title'] = $albumInfos[$albumid]['title'];
                    $albumInfo['story_num'] = $albumInfos[$albumid]['story_num'];
                    $albumInfo['intro'] = $albumInfos[$albumid]['intro'];
                    $albumAgeLevelStr = $albumObj->getAgeLevelStr($albumInfo['min_age'], $albumInfo['max_age']);
                    $albumInfo['age_str'] = sprintf("适合%s岁", $albumAgeLevelStr);
                    if (!empty($albumInfos[$albumid]['cover'])) {
                        $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM,
                            $albumInfos[$albumid]['cover'], 460, $albumInfos[$albumid]['cover_time']);
                    }
                    $albumInfo['listennum'] = intval($relationInfo['albumlistennum']) > 0 ? substr($relationInfo['albumlistennum'],
                        0, 5) : 0;

                    $albumInfo['recommenddesc'] = "";
                    if (!empty($recommendDescList[$albumid])) {
                        $albumInfo['recommenddesc'] = $recommendDescList[$albumid]['desc'];
                    }

                    $albumInfo['fav'] = $albumInfos[$albumid]['fav'];
                    $albumInfo['favnum'] = $relationInfo['albumfavnum'];
                    $albumInfo['star_level'] = $relationInfo['commentstarlevel'];
                    $tagAlbumItem['albuminfo'] = $albumInfo;
                }
                $tagAlbumList[] = $tagAlbumItem;
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