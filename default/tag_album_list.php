<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/29
 * Time: 上午10:55
 */
include_once '../controller.php';
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
        $albumTagList = $albumTagObj->getAlbumListByTagId($where,1,$len);

        $data = array('age_level'=>array(),'total'=>100,'items'=>$albumTagList);
        $this->showSuccJson($data);
    }
}
new tagalbumlist();