<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/27
 * Time: 下午8:11
 */
include_once '../../controller.php';
class hotalbumlist extends controller
{
    public function action()
    {
        $minAge = $this->getRequest('min_age', '0');
        $maxAge = $this->getRequest('max_age', '0');
        $startAlbumId = $this->getRequest('start_album_id', '0');
        $len = $this->getRequest('len', 10);

        $aliossObj = new AliOss();
        $albumObj = new Album();
        $albumTagList = $albumObj->getAlbumListByAge($minAge,$maxAge, $startAlbumId,1,$len);
        
        //取专辑下面对应的标签
        $tagnewobj = new TagNew();
        $tagInfoObj = new TagInfo();
        $recommendDescObj = new RecommendDesc();
        $tagInfoList = array();
        foreach ($albumTagList as $key=>$val){
            // 获取推荐语
            $recommendList = $recommendDescObj->getAlbumRecommendDescList($val['id']);
            $val['recommend'] = $recommendList[$val['id']]['desc'];

            $val['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $val['cover'], 460, $val['cover_time']);
            $tagList = $tagnewobj->getAlbumTagRelationListByAlbumIds(array($val['id']));
            foreach ($tagList[$val['id']] as $k=>$v){
                $tagInfo = $tagInfoObj->get_info("id = ".$v['tagid'],'id,pid,name,cover,covertime');
                $tagInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_TAG, $tagInfo['cover'], 460, $tagInfo['covertime']);
                $tagInfoList[] = $tagInfo;
            }
            $val['recommend_tags'] = array('total'=>count($tagInfoList),'items'=>$tagInfoList);
            $albumTagList[$key]=$val;
        }

        $data = array('age_level'=>array(),'total'=>100,'items'=>$albumTagList);
        $this->showSuccJson($data);
    }
}
new hotalbumlist();