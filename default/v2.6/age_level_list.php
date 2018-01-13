<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/24
 * Time: 上午10:45
 */
include_once '../../controller.php';
class agelevellist extends controller
{

    /**
     *
     * @var array
     */
    public $ageLevelArr = [

        '0-2' => [
            'min_age' => 0,
            'max_age' => 2,
            'tagList' => [
                ['id'=>18,'name'=>'韵律童谣'],
                ['id'=>35,'name'=>'睡前音乐'],
                ['id'=>36,'name'=>'音乐启蒙'],
                ['id'=>23,'name'=>'睡前故事'],
                ['id'=>234,'name'=>'宝宝学说话'],
            ],
            'focusPicCategory' => 'zero_two_years_old_home',
        ],

        '3-6' => [
            'min_age' => 3,
            'max_age' => 6,
            'tagList' => [
                ['id'=>133,'name'=>'绘本故事'],
                ['id'=>23,'name'=>'睡前故事'],
                ['id'=>140,'name'=>'生活成长'],
                ['id'=>158,'name'=>'童话寓言'],
                ['id'=>31,'name'=>'成语故事'],
                ['id'=>156,'name'=>'神话传说'],
                ['id'=>41,'name'=>'英语启蒙'],
                ['id'=>45,'name'=>'唐诗宋词'],
            ],
            'focusPicCategory' => 'three_six_years_old_home',
        ],

        '7-10' => [
            'min_age' => 7,
            'max_age' => 10,
            'tagList' => [
                ['id'=>183,'name'=>'奇幻探险'],
                ['id'=>32,'name'=>'历史故事'],
                ['id'=>49,'name'=>'传统经典'],
                ['id'=>128,'name'=>'儿童学习'],
            ],
            'focusPicCategory' => 'seven_ten_years_old_home',
        ],

        '11-14' => [
            'min_age' => 11,
            'max_age' => 14,
            'tagList' => [
                ['id'=>117,'name'=>'自然科学'],
                ['id'=>115,'name'=>'百科知识'],
                ['id'=>44,'name'=>'英语故事'],
                ['id'=>164,'name'=>'少年文学'],
            ],
            'focusPicCategory' => 'eleven_fourteen_years_old_home',
        ]
    ];


    public function action()
    {
        $configVar = new ConfigVar();
        $minAge = $this->getRequest('min_age', $configVar->MIN_AGE);
        $maxAge = $this->getRequest('max_age', $configVar->MAX_AGE);
        $albumSectionItems = array();

        $res = array(
            'focus_pic'=>array(),// 焦点图
            'album_section'=>array(),
            'recommend_tags'=>array(),
        );
        $aliossObj = new AliOss();

        //获取焦点图
        $category = 'home';//'age'.$minAge;
        $ageKey = $minAge.'-'.$maxAge;

        $ageKeyArr = array_keys($this->ageLevelArr);

        if(in_array($ageKey,$ageKeyArr)){
            $category = $this->ageLevelArr[$ageKey]['focusPicCategory'];
        }

        $focusObj = new ManageFocus();
        $focusList = $focusObj->get_list(" category ='".$category."' and status=1 ",'id,covertime,linkurl');
        foreach ($focusList as $key=>$val){
            $val['cover'] = $aliossObj->getFocusUrl($val['id'], $val['covertime'], 1);
            unset($val['id'],$val['covertime']);
            $focusList[$key] = $val;
        }
        $res['focus_pic'] = array('total'=>count($focusList),'items'=>$focusList);

        //热门播放
        $albumObj = new Album();
        $recommendDescObj = new RecommendDesc();
        $hotAlbumList = $albumObj->getAlbumListByAge($minAge,$maxAge);

        //格式化返回
        foreach ($hotAlbumList as $key=>$val){
            // 获取推荐语
            $recommendList = $recommendDescObj->getAlbumRecommendDescList($val['id']);
            $val['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $val['cover'], 460, $val['cover_time']);
            $val['recommend_desc'] = empty($recommendList[$val['id']]['desc']) ? $val['intro']  : $recommendList[$val['id']]['desc'];
            $val['linkurl'] = 'xnm://www.xiaoningmeng.net/album/info.php?albumid='.$val['id'];
            $val['listen_num'] = $albumObj->format_album_listen_num($val['listen_num'] + 0);
            unset($val['cover_time']);
            $hotAlbumList[$key] = $val;
        }

        $sectionItem = array(
            'title' => '热门播放',
            'tag_id' => 0,
            'type' => 'hot',
            'total' => count($hotAlbumList),
            'items' => $hotAlbumList,
        );
        $albumSectionItems[] = $sectionItem;
        $albumTagObj = new AlbumTagRelation();

        foreach ($this->ageLevelArr[$ageKey]['tagList'] as $item) {

            $albumTagList = $albumTagObj->getAlbumListByTagId($item['id'],1,6);
            //格式化返回
            foreach ($albumTagList as $key=>$val){
                // 获取推荐语
                $recommendList = $recommendDescObj->getAlbumRecommendDescList($val['id']);
                $val['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $val['cover'], 460, $val['cover_time']);
                $val['recommend_desc'] = empty($recommendList[$val['id']]['desc']) ? $val['intro']  : $recommendList[$val['id']]['desc'];
                $val['linkurl'] = 'xnm://www.xiaoningmeng.net/album/info.php?albumid='.$val['id'];
                $val['listen_num'] = $albumObj->format_album_listen_num($val['listen_num'] + 0);
                unset($val['tagid'],$val['cover_time']);
                $albumTagList[$key] = $val;
            }

            $sectionItem = array(
                'tag_id' => $item['id'],
                'title' => $item['name'],
                'type' => 'album',
                'total' => count($albumTagList),
                'items' => $albumTagList
            );
            $albumSectionItems[] = $sectionItem;
        }

        $res['album_section']['total'] = count($albumSectionItems);
        $res['album_section']['items'] = $albumSectionItems;


//        $res['album_section'] = array('total'=>2,
//            'items'=>array(
//                0=>array('title'=>'热门播放','total'=>4,'items'=>$albumList),
//                1=>array('title'=>$albumTagIdList[$minAge]['name'],'total'=>4,'items'=>$albumTagList)
//            )
//        );

        //标签
//        $tagIdList = array(
//            '0'=>array(13,14,15,16,17,18),
//            '3'=>array(23,24,25,26,27,28),
//            '7'=>array(13,14,15,16,17,18),
//            '11'=>array(23,24,25,26,27,28),
//        );
//        $tagInfoObj = new TagInfo();
//
//        $tagInfoList =array();
//        foreach($tagIdList[$minAge] as $val){
//            $tagInfo = $tagInfoObj->get_info("id = ".$val,'id,name,cover,covertime');
//            $tagInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_TAG, $tagInfo['cover'], 460, $tagInfo['covertime']);
//            $tagInfoList[] = $tagInfo;
//        }
//
//        $res['recommend_tags'] = array('total'=>6,'items'=>$tagInfoList);
        $this->showSuccJson($res);
    }
}
new agelevellist();