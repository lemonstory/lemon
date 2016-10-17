<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/29
 * Time: 上午10:55
 */
include_once '../../controller.php';

class tagAlbumList extends controller
{
    public function action()
    {
        $configVar = new ConfigVar();
        $minAge = $this->getRequest('min_age', $configVar->MIN_AGE);
        $maxAge = $this->getRequest('max_age', $configVar->MAX_AGE);
        $tagId = $this->getRequest('tag_id', '');
        $page = $this->getRequest('page', '1');
        $len = $this->getRequest('len', '20');


        switch ($tagId) {

            case $configVar->HOT_RECOMMEND_TAG_ID: {

                echo "HOT_RECOMMEND_TAG_ID";
                include_once './recommend_list.php';
                break;
            }

            case $configVar->SAME_AGE_TAG_ID: {
                echo "SAME_AGE_TAG_ID";
                include_once './same_age_list.php';
                break;
            }

            case $configVar->NEW_ONLINE_TAG_ID: {
                echo "NEW_ONLINE_TAG_ID";
                include_once './online_list.php';
                break;
            }

            default: {
                $aliossObj = new AliOss();
                $albumObj = new Album();
                $albumTagObj = new AlbumTagRelation();
                $recommendDescObj = new RecommendDesc();
                $albumTagList = $albumTagObj->getAlbumListByAge($minAge, $maxAge, $tagId, 0, $page, $len);
                $ageLevel = array();

                if (!empty($albumTagList)) {

                    foreach ($albumTagList as $item) {
                        $albumIds[] = $item['id'];
                    }

                    // 专辑收听数
                    $listenobj = new Listen();
                    $albumListenNum = $listenobj->getAlbumListenNum($albumIds);

                    // 获取推荐语
                    $recommenddescobj = new RecommendDesc();
                    $recommendDescList = $recommenddescobj->getAlbumRecommendDescList($albumIds);

                    //格式化返回
                    foreach ($albumTagList as $key => $val) {
                        $albumInfo = array();
                        $albumInfo['id'] = $val['id'];
                        $albumInfo['title'] = $val['title'];
                        $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $val['cover'], 460, $val['cover_time']);
                        $albumInfo['listennum'] = 0;
                        if (!empty($albumListenNum[$val['id']])) {
                            $albumInfo['listennum'] = $albumListenNum[$val['id']]['num'] + 0;
                        }
                        $albumInfo['recommenddesc'] = "";
                        if (!empty($recommendDescList[$val['id']])) {
                            $albumInfo['recommenddesc'] = $recommendDescList[$val['id']]['desc'];
                        }
                        $albumAgeLevelStr = $albumObj->getAgeLevelStr($val['min_age'], $val['max_age']);
                        $albumInfo['age_str'] = sprintf("(%s)岁", $albumAgeLevelStr);

                        $albumTagList[$key] = $albumInfo;
                    }

                    //年龄段
                    $recommendObj = new Recommend();
                    $hotAgeLevelNum = $recommendObj->getAgeLevelNum("hot");
                    $ageGroupItem = array("min_age" => $minAge, "max_age" => $maxAge);
                    $selectedIndex = array_search($ageGroupItem, $configVar->AGE_LEVEL_ARR);
                    $ageLevel = $albumObj->getAgeLevelWithAlbumsFormat($hotAgeLevelNum, $selectedIndex);

                }

                $data = array('age_level' => $ageLevel, 'total' => count($albumTagList), 'items' => $albumTagList);
                $this->showSuccJson($data);
            }
        }
    }
}

new tagAlbumList();