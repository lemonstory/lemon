<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/24
 * Time: 上午10:45
 */
include_once '../controller.php';
class agelevellist extends controller
{
    public function action()
    {
        $res = array(
            'code'=>200,
            'data'=>array(),
            'album_section'=>array(),
            'recommend_tags'=>array(),
        );
        echo json_encode($res);
    }
}
new agelevellist();