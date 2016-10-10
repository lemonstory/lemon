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
        $minAge = $this->getRequest('min_age', '0');
        $maxAge = $this->getRequest('max_age', '0');
        $tagId = $this->getRequest('tag_id', '');
        $startAlbumId = $this->getRequest('start_album_id', '');
        $len = $this->getRequest('len', 10);
        if(!empty($tagId)){
            $where['tagid']= intval($tagId);
        }

        $where['min_age']= intval($minAge);
        $where['max_age']= intval($maxAge);


        $albumTagObj = new AlbumTagRelation();
        $recommendDescObj = new RecommendDesc();
        $albumTagList = $albumTagObj->getAlbumList($where,1,$len);
        //格式化返回
        foreach ($albumTagList as $key=>$val){
            // 获取推荐语
            $recommendList = $recommendDescObj->getAlbumRecommendDescList($val['id']);
            $val['recommend'] = $recommendList[$val['id']]['desc'];
            $val['cover'] = 'http://lemonpic.oss-cn-hangzhou.aliyuncs.com/'.$val['cover'];
            $val['linkurl'] = 'xnm://www.xiaoningmeng.net/album/info.php?albumid='.$val['id'];

            $albumTagList[$key] = $val;
        }

        $data = array('age_level'=>array(),'total'=>100,'items'=>$albumTagList);
        $this->showSuccJson($data);
    }
}
new tagalbumlist();