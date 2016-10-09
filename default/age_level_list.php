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
        $maxAge = $this->getRequest('max_age', '0');

        $res = array(
            'focus_pic'=>array(),// 焦点图
            'album_section'=>array(),
            'recommend_tags'=>array(),
        );
        //获取焦点图
        $category = 'age'.$minAge;
        $focusObj = new ManageFocus();
        $focusList = $focusObj->get_list(" category ='".$category."' and status=1 ",'covertime,linkurl');
        $res['focus_pic'] = array('total'=>count($focusList),'items'=>$focusList);

        //热门播放
        $albumObj = new Album();
        $albumList = $albumObj->getAlbumListByAge($minAge,$maxAge);
        //格式化返回
        foreach ($albumList as $key=>$val){
            $val['cover'] = 'http://p.xiaoningmeng.net/'.$val['cover'];
            $val['linkurl'] = 'xnm://www.xiaoningmeng.net/album/info.php?albumid='.$val['id'];

            $albumList[$key] = $val;
        }

        $albumTagIdList = array(
            '0'=>array('id'=>23,'name'=>'睡前故事'),
            '3'=>array('id'=>23,'name'=>'睡前故事'),
            '7'=>array('id'=>23,'name'=>'睡前故事'),
            '11'=>array('id'=>23,'name'=>'睡前故事'),
        );
        $albumTagObj = new AlbumTagRelation();
        $albumTagList = $albumTagObj->getAlbumList(array('tagid'=>$albumTagIdList[$minAge]['id']));

        $res['album_section'] = array('total'=>2,
            'items'=>array(
                0=>array('title'=>'热门播放','total'=>4,'items'=>$albumList),
                1=>array('title'=>$albumTagIdList[$minAge]['name'],'total'=>4,'items'=>$albumTagList)
            )
        );

        //标签
        $tagIdList = array(
            '0'=>array(13,14,15,16,17,18),
            '3'=>array(23,24,25,26,27,28),
            '7'=>array(13,14,15,16,17,18),
            '11'=>array(23,24,25,26,27,28),
        );
        $tagInfoObj = new TagInfo();

        $tagInfoList =array();
        foreach($tagIdList[$minAge] as $val){
            $tagInfo = $tagInfoObj->get_info("id = ".$val,'id,name,cover');
            $tagInfo['cover'] = 'http://p.xiaoningmeng.net/'.$tagInfo['cover'];
            $tagInfoList[] = $tagInfo;
        }

        $res['recommend_tags'] = array('total'=>6,'items'=>$tagInfoList);
        $this->showSuccJson($res);
    }
}
new agelevellist();