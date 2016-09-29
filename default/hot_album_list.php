<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/27
 * Time: 下午8:11
 */
include_once '../controller.php';
class hotalbumlist extends controller
{
    public function action()
    {
        $minAge = $this->getRequest('min_age', '0');
        $maxAge = $this->getRequest('max_age', '2');
        $startAlbumId = $this->getRequest('start_album_id', '');
        $len = $this->getRequest('len', 10);

        $where['min_age']= intval($minAge);
        $where['max_age']= intval($maxAge);

        $albumTagObj = new AlbumTagRelation();
        $albumTagList = $albumTagObj->getAlbumListByTagId($where,1,$len);
        
        //取专辑下面对应的故事
        $storyObj = new Story();
        foreach ($albumTagList as $key=>$val){
            $storyList = $storyObj->get_filed_list('title,cover',' album_id='.$val['id'],'',4);
            $val['items'] = $storyList;
            $albumTagList[$key]=$val;
        }

        $data = array('age_level'=>array(),'total'=>100,'items'=>$albumTagList);
        $this->showSuccJson($data);
    }
}
new hotalbumlist();