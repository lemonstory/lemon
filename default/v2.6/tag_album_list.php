<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/29
 * Time: 上午10:55
 */
include_once '../../controller.php';
class tagalbumlist extends controller
{
    public function action()
    {
        $configVar = new ConfigVar();
        $minAge = $this->getRequest('min_age', $configVar->MIN_AGE);
        $maxAge = $this->getRequest('max_age', $configVar->MAX_AGE);
        $tagId = $this->getRequest('tag_id', '0');
        $startAlbumId = $this->getRequest('start_album_id', '');
        $len = $this->getRequest('len', 10);
        

        $aliossObj = new AliOss();
        $albumObj = new Album();
        $albumTagObj = new AlbumTagRelation();
        $recommendDescObj = new RecommendDesc();
        $albumTagList = $albumTagObj->getAlbumListByAge($minAge,$maxAge,$tagId,$startAlbumId,1,$len);
        //格式化返回
        foreach ($albumTagList as $key=>$val){
            // 获取推荐语
            $recommendList = $recommendDescObj->getAlbumRecommendDescList($val['id']);
            $val['recommend'] = $recommendList[$val['id']]['desc'];
            $val['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $val['cover'], 460, $val['cover_time']);
            $val['linkurl'] = 'xnm://www.xiaoningmeng.net/album/info.php?albumid='.$val['id'];

            $albumTagList[$key] = $val;
        }

        //年龄段
        $recommendObj = new Recommend();
        $hotAgeLevelNum = $recommendObj->getAgeLevelNum("hot");
        $ageGroupItem = array("min_age" => $minAge, "max_age" => $maxAge);
        $selectedIndex = array_search($ageGroupItem, $configVar->AGE_LEVEL_ARR);
        $ageLevel = $albumObj->getAgeLevelWithAlbumsFormat($hotAgeLevelNum, $selectedIndex);

        $data = array('age_level'=>$ageLevel,'total'=>100,'items'=>$albumTagList);
        $this->showSuccJson($data);
    }
}
new tagalbumlist();