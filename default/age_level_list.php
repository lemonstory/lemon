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
            'data'=>array(),// 焦点图
            'album_section'=>array(),
            'recommend_tags'=>array(),
        );
        //获取焦点图
        $focusObj = new ManageFocus();
        $focusList = $focusObj->get_list(' status=1 ','covertime,linkurl');
        $res['data'] = $focusList;

        //热门播放
        $albumObj = new Album();
        $albumList = $albumObj->get_list_new(' age_type=3 ','id,title,cover','view_order desc',4);
        $res['album_section'] = $albumList;

        //标签
        $tagInfoObj = new TagInfo();
        $tagInfoList = $tagInfoObj->get_list("pid = 0",'id,name,cover','ordernum desc',6);
        $res['recommend_tags'] = $tagInfoList;
        echo json_encode($res);
    }
}
new agelevellist();