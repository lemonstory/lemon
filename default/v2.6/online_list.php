<?php
/**
 * 最新上架-更多
 *
 * Date: 16/10/11
 * Time: 下午4:36
 */

include_once '../../controller.php';

class onlineList extends controller
{
    public function action()
    {
        $configVar = new ConfigVar();
        $minAge = $this->getRequest('min_age', $configVar->MIN_AGE);
        $maxAge = $this->getRequest('max_age', $configVar->MAX_AGE);
        $page = $this->getRequest('page', '0');
        $len = $this->getRequest('len', '20');

        $albumObj = new Album();
        $recommendObj = new Recommend();
        $recommendAlbumList = $recommendObj->getNewOnlineList($minAge, $maxAge, $page, 0, $len);


        $recommendAlbumArr = array();
        $recommendAlbumArr['age_level'] = array();
        $recommendAlbumArr['total'] = 0;
        $recommendAlbumArr['items'] = array();
        if (!empty($recommendAlbumList)) {
            foreach ($recommendAlbumList as $item) {
                $albumIds[] = $item['id'];
            }

            if (!empty($albumIds)) {

                $albumIds = array_unique($albumIds);

                // 专辑收听数
                $listenobj = new Listen();
                $albumListenNum = $listenobj->getAlbumListenNum($albumIds);

                // 获取推荐语
                $recommenddescobj = new RecommendDesc();
                $recommendDescList = $recommenddescobj->getAlbumRecommendDescList($albumIds);

                //标签
                // 获取多个专辑的关联tag列表
                $tagNewObj = new TagNew();
                $albumTagRelationList = $tagNewObj->getAlbumTagRelationListByAlbumIds($albumIds);
                $tagIds = array();
                if (!empty($albumTagRelationList)) {
                    foreach ($albumTagRelationList as $albumId => $albumTagList) {
                        foreach ($albumTagList as $item) {
                            $tagIds[] = $item['tagid'];
                        }
                    }
                }
                $tagIds = array_unique($tagIds);
                // 获取标签信息
                $tagList = $tagNewObj->getTagInfoByIds($tagIds);
                $aliossObj = new AliOss();

                foreach ($recommendAlbumList as $key => $item) {

                    $albumId = $item['id'];
                    $albumInfo = array();


                    $albumInfo['id'] = $recommendAlbumList[$key]['id'];
                    $albumInfo['title'] = $recommendAlbumList[$key]['title'];
                    if (!empty($recommendAlbumList[$key]['cover'])) {
                        $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $recommendAlbumList[$key]['cover'], 460, $recommendAlbumList[$key]['cover_time']);
                    }
                    $albumInfo['listennum'] = 0;
                    if (!empty($albumListenNum[$albumId])) {
                        $albumInfo['listennum'] = $albumObj->format_album_listen_num($albumListenNum[$albumId]['num'] + 0);
                    }
                    $albumInfo['recommenddesc'] = "";
                    if (!empty($recommendDescList[$albumId])) {
                        $albumInfo['recommenddesc'] = $recommendDescList[$albumId]['desc'];
                    }
                    $albumAgeLevelStr = $albumObj->getAgeLevelStr($recommendAlbumList[$key]['min_age'], $recommendAlbumList[$key]['max_age']);
                    $albumInfo['age_str'] = sprintf("(%s)岁", $albumAgeLevelStr);

                    //tag
                    if (!empty($albumTagRelationList[$albumId])) {
                        foreach ($albumTagRelationList[$albumId] as $item) {

                            $tagID = $item['tagid'];
                            //如果一个专辑多个一级标签,只会出现第一个
                            if ($tagList[$tagID]['pid'] == 0) {
                                $tag = array();
                                $tag['id'] = $tagList[$tagID]['id'];
                                $tag['name'] = $tagList[$tagID]['name'];
                                $albumInfo['tag'] = $tag;
                                break;
                            }
                        }
                    }
                    $recommendAlbumArr['items'][] = $albumInfo;
                }
            }

            $recommendAlbumArr['total'] = count($recommendAlbumArr['items']);

            //年龄段
            $onlineAgeLevelNum = $recommendObj->getAgeLevelNum("online");
            $ageGroupItem = array("min_age" => $minAge, "max_age" => $maxAge);
            $selectedIndex = array_search($ageGroupItem, $configVar->AGE_LEVEL_ARR);
            $recommendAlbumArr['age_level'] = $albumObj->getAgeLevelWithAlbumsFormat($onlineAgeLevelNum, $selectedIndex);
        }
        $this->showSuccJson($recommendAlbumArr);
    }
}

new onlineList();
