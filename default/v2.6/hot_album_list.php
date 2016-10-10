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

        $albumObj = new Album();
        $albumTagList = $albumObj->getAlbumListByAge($minAge,$maxAge, $startAlbumId,1,$len);
        
        //取专辑下面对应的标签
        $albumTagObj = new AlbumTagRelation();
        $tagInfoObj = new TagInfo();
        $recommendDescObj = new RecommendDesc();
        $tagInfoList = array();
        foreach ($albumTagList as $key=>$val){
            // 获取推荐语
            $recommendList = $recommendDescObj->getAlbumRecommendDescList($val['id']);
            $val['recommend'] = $recommendList[$val['id']]['desc'];

            $val['cover'] = 'http://p.xiaoningmeng.net/'.$val['cover'];
            $tagList = $albumTagObj->getTagListByAlbumId($val['id'],'1',10);
            foreach ($tagList as $k=>$v){
                $tagInfo = $tagInfoObj->get_info("id = ".$v['tagid'],'id,pid,name,cover');
                $tagInfo['cover'] = 'http://p.xiaoningmeng.net/'.$tagInfo['cover'];
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