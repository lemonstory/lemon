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
        $minAge = $this->getRequest('min_age', '0');
        $maxAge = $this->getRequest('max_age', '2');

        $res = array(
            'code'=>200,
            'data'=>array(
                'focus_pic'=>array(),// 焦点图
                'album_section'=>array(),
                'recommend_tags'=>array(),
            ),
        );
        //获取焦点图
        $focusObj = new ManageFocus();
        $focusList = $focusObj->get_list(' status=1 ','covertime,linkurl');
        $res['data']['focus_pic'] = array('total'=>count($focusList),'items'=>$focusList);

        //热门播放
        $albumObj = new Album();
        $albumList = $albumObj->getAlbumListOrderListenNum(array('min_age'=>$minAge,'max_age'=>$maxAge));

        $albumTagIdList = array(
            '0'=>array('id'=>23,'name'=>'睡前故事'),
            '3'=>array('id'=>23,'name'=>'睡前故事'),
            '7'=>array('id'=>23,'name'=>'睡前故事'),
            '11'=>array('id'=>23,'name'=>'睡前故事'),
        );
        $albumTagObj = new AlbumTagRelation();
        $albumTagList = $albumTagObj->getAlbumListByTagId(array('tagid'=>$albumTagIdList[$minAge]['id']));

        $res['data']['album_section'] = array('total'=>2,
            'items'=>array(
                0=>array('title'=>'热门播放','total'=>4,'items'=>$albumList),
                1=>array('title'=>$albumTagIdList[$minAge]['name'],'total'=>4,'items'=>$albumTagList)
            )
        );

        //标签
        $tagIdList = array(
            '0'=>array(13,14,15,16,17,18),
            '3'=>array(23,24,25,26,27,28),
            '7'=>array(),
            '11'=>array(),
        );
        $tagInfoObj = new TagInfo();

        $tagInfoList =array();
        foreach($tagIdList[$minAge] as $val){
            $tagInfo = $tagInfoObj->get_info("id = ".$val,'id,name,cover');
            $tagInfoList[] = $tagInfo;
        }

        $res['data']['recommend_tags'] = array('total'=>6,'items'=>$tagInfoList);
        echo json_encode($res);
    }
}
new agelevellist();