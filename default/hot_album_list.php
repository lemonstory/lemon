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
        $len = $this->getRequest('len', '');


        $data = array('age_level'=>array(),'total'=>100,'items'=>array());
        $this->showSuccJson($data);
    }
}
new hotalbumlist();