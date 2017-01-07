<?php
/**
 * 首页接口2.6.4
 * Date: 16/10/9
 * Time: 下午3:51
 */

include_once '../../controller.php';

class index extends controller
{
    const SECTION_ALBUM = 'album';
    const SECTION_AUTHOR = 'author';
    const CACHE_INSTANCE = 'cache';
    const CACHE_EXPIRE = 3600;

    public function action()
    {
        $uid = $this->getUid();
        $babyagetype = 0;
        if (!empty($uid)) {
            $userobj = new User();
            $userInfo = current($userobj->getUserInfo($uid, 1));
            if (!empty($userInfo)) {
                $userextobj = new UserExtend();
                $babyagetype = $userextobj->getBabyAgeType($userInfo['age']);
            }
        }
        $redisobj = AliRedisConnecter::connRedis(self::CACHE_INSTANCE);
        $indexDataKey = RedisKey::getIndexDataKey($babyagetype);
        $data = array();
        $redisData = $redisobj->get($indexDataKey);
        if ($redisData) {
            $data = unserialize($redisData);
        }
        else {
            $albumIds = array();
            $recommendObj = new Recommend();
            $aliossObj = new AliOss();
            $configVar = new ConfigVar();

            $minAge = $configVar->AGE_LEVEL_ARR[$babyagetype]['min_age'];
            $maxAge = $configVar->AGE_LEVEL_ARR[$babyagetype]['max_age'];

            //焦点图
            $data['focus'] = array();
            $focuspiclist = array();
            $focusres = $recommendObj->getFocusList(6);
            if (!empty($focusres)) {
                foreach ($focusres as $value) {
                    $focusinfo['cover'] = $aliossObj->getFocusUrl($value['id'], $value['covertime'], 1);
                    //$focusinfo['linktype'] = $value['linktype'];
                    $focusinfo['linkurl'] = $value['linkurl'];
                    $focuspiclist[] = $focusinfo;
                }
                $data['focus']['total'] = count($focuspiclist);
                $data['focus']['items'] = $focuspiclist;
            }

            //内容分类
            $total = 8;
            $data['category']['total'] = $total;
            $tagNewObj = new TagNew();
            $firstTagRes = $tagNewObj->getFirstTagList($total - 1);
            if (!empty($firstTagRes)) {
                foreach ($firstTagRes as $value) {

                    $title = $value['name'];
                    if (!empty($value['cover'])) {
                        $cover = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_TAG, $value['cover'], 0,
                            $value['covertime']);
                    }
                    $linkurl = "xnm://www.xiaoningmeng.net/default/v2.6/tag_album_list.php?tag_id={$value['id']}";
                    $data['category']['items'][] = array(
                        "title" => $title,
                        "cover" => $cover,
                        "linkurl" => $linkurl,
                    );
                }
            }
            //全部分类
            $data['category']['items'][] = array(
                "title" => "全部分类",
                "cover" => "http://p.xiaoningmeng.net/tag/all_1080.png",
                "linkurl" => "xnm://www.xiaoningmeng.net/default/v2.6/category.php"
            );

            //个性化专辑

            $albumLen = 6;
            $currentPage = 1;
            // 今日精选
            $hotRecommendRes = $recommendObj->getRecommendHotList($minAge, $maxAge, 0, $currentPage,
                $albumLen);
            if (!empty($hotRecommendRes)) {
                foreach ($hotRecommendRes as $value) {
                    $albumIds[] = $value['id'];
                }
            }

            // 同龄在听
            $sameAgeRes = $recommendObj->getSameAgeListenList($minAge, $maxAge, 0, $currentPage,
                $albumLen);
            if (!empty($sameAgeRes)) {
                foreach ($sameAgeRes as $value) {
                    $albumIds[] = $value['id'];
                }
            }

            // 最新上架
            $newOnlineRes = $recommendObj->getNewOnlineList($minAge, $maxAge, 0, $currentPage,
                $albumLen);
            if (!empty($newOnlineRes)) {
                foreach ($newOnlineRes as $value) {
                    $albumIds[] = $value['id'];
                }
            }

            $albumList = array();
            $recommendDescList = array();
            $albumListenNum = array();
            if (!empty($albumIds)) {
                $albumIds = array_unique($albumIds);
                // 专辑信息
                $albumObj = new Album();
                $albumList = $albumObj->getListByIds($albumIds);
                // 专辑收听数
                $listenobj = new Listen();
                $albumListenNum = $listenobj->getAlbumListenNum($albumIds);

                // 获取推荐语
                $recommenddescobj = new RecommendDesc();
                $recommendDescList = $recommenddescobj->getAlbumRecommendDescList($albumIds);
            }


            $hotRecommendList = array();
            $sameAgeAlbumList = array();
            $newAlbumList = array();
            $albumInfo = array();
            if (!empty($hotRecommendRes)) {
                foreach ($hotRecommendRes as $value) {

                    $albumInfo = $this->getAlbumInfo($albumList, $albumListenNum, $recommendDescList, $value['id']);
                    if (is_array($albumInfo) && !empty($albumInfo)) {
                        $hotRecommendList[] = $albumInfo;
                    }
                }
            }

            $sectionData[] = array(
                'type' => self::SECTION_ALBUM,
                'tag_id' => $configVar->HOT_RECOMMEND_TAG_ID,
                'title' => "今日精选",
                'total' => count($hotRecommendList),
                'linkurl' => "xnm://api.xiaoningmeng.net/default/v2.6/recommend_list.php",
                'items' => $hotRecommendList,
            );

            //热门作者
            $authorNum = 8;
            $creator = new Creator();
            $hotAuthors = $creator->getHotAuthors($authorNum);
            $sectionData[] = array(
                'type' => self::SECTION_AUTHOR,
                'title' => '热门作者',
                'total' => count($hotAuthors),
                'linkurl' => 'xnm://api.xiaoningmeng.net/default/v2.6/authors.php',
                'items' => $hotAuthors
            );


            if (!empty($sameAgeRes)) {
                foreach ($sameAgeRes as $value) {

                    $albumInfo = $this->getAlbumInfo($albumList, $albumListenNum, $recommendDescList, $value['id']);
                    if (is_array($albumInfo) && !empty($albumInfo)) {
                        $sameAgeAlbumList[] = $albumInfo;
                    }
                }
            }
            $sectionData[] = array(
                'type' => self::SECTION_ALBUM,
                'tag_id' => $configVar->SAME_AGE_TAG_ID,
                'title' => "同龄在听",
                'total' => count($sameAgeAlbumList),
                'linkurl' => "xnm://api.xiaoningmeng.net/default/v2.6/same_age_list.php",
                'items' => $sameAgeAlbumList,
            );

            if (!empty($newOnlineRes)) {
                foreach ($newOnlineRes as $value) {
                    $albumInfo = $this->getAlbumInfo($albumList, $albumListenNum, $recommendDescList, $value['id']);
                    if (is_array($albumInfo) && !empty($albumInfo)) {
                        $newAlbumList[] = $albumInfo;
                    }
                }
            }
            $sectionData[] = array(
                'type' => self::SECTION_ALBUM,
                'tag_id' => $configVar->NEW_ONLINE_TAG_ID,
                'title' => "最新上架",
                'total' => count($newAlbumList),
                'linkurl' => "xnm://api.xiaoningmeng.net/default/v2.6/online_list.php",
                'items' => $newAlbumList,
            );

            //以及标签
            $tagNewObj = new TagNew();
            $firstTagRes = $tagNewObj->getFirstTagList(8);
            $len = 4;
            $tagAlbumIdArr = array();
            if (!empty($firstTagRes)) {

                $tagAlbumRelArr = array();
                foreach ($firstTagRes as $item) {
                    $tagAlbumRelArr = $tagNewObj->getAlbumTagRelationListFromTag($item['id'], 1, 0, 0, "down", 0, $len);
                    if (!empty($tagAlbumRelArr)) {
                        foreach ($tagAlbumRelArr as $tagAlbumRelItem) {
                            $tagAlbumIdArr[$item['id']][] = $tagAlbumRelItem['albumid'];
                        }
                    }
                }

                $albumIdArr = array();
                if (!empty($tagAlbumIdArr)) {

                    foreach ($tagAlbumIdArr as $tag_id => $albumIdArrItem) {
                        $albumIdArr = array_merge($albumIdArr, $albumIdArrItem);
                    }
                }

                $albumList = array();
                $albumListenNum = array();
                $recommendDescList = array();
                if (!empty($albumIdArr)) {
                    $albumIdArr = array_unique($albumIdArr);
                    // 专辑信息
                    $albumObj = new Album();
                    $albumList = $albumObj->getListByIds($albumIdArr);
                    // 专辑收听数
                    $listenObj = new Listen();
                    $albumListenNum = $listenObj->getAlbumListenNum($albumIdArr);

                    // 获取推荐语
                    $recommendDescObj = new RecommendDesc();
                    $recommendDescList = $recommendDescObj->getAlbumRecommendDescList($albumIdArr);
                }


                foreach ($firstTagRes as $tagItem) {

                    $albumSectionItem = array();
                    $albumSectionItem['type'] = self::SECTION_ALBUM;
                    $albumSectionItem['tag_id'] = $tagItem['id'];
                    $albumSectionItem['title'] = $tagItem['name'];
                    $albumSectionItem['total'] = 0;
                    $albumSectionItem['linkurl'] = "xnm://www.xiaoningmeng.net/default/v2.6/tag_album_list.php?tag_id={$tagItem['id']}";
                    $albumSectionItem['items'] = array();

                    $id = $tagItem['id'];
                    foreach ($tagAlbumIdArr[$id] as $albumId) {

                        $albumInfo = $this->getAlbumInfo($albumList, $albumListenNum, $recommendDescList, $albumId);
                        if (is_array($albumInfo) && !empty($albumInfo)) {
                            $albumSectionItem['items'][] = $albumInfo;
                        }
                    }
                    $albumSectionItem['total'] = count($albumSectionItem['items']);
                    $sectionData[] = $albumSectionItem;
                }
            }
            $data['section']['items'] = $sectionData;

            //广告
            $data['ad'] = array(
                "total" => 5,
                "items" => array(
                    //牛牛福袋
                    array(
                        "cover" => "http://p.xiaoningmeng.net/static/niuniufudai.jpg",
                        "linkurl" => "https://gw3.mouchina.com/static/H5-3.0/share.html?key=22fa13c8a0f4ef8a1323c84356f892568fe0bcfb_3&userId=1000000483",
                    ),
                    //高人汇
//                    array(
//                        "cover" => "http://p.xiaoningmeng.net/static/gaorenhui.png",
//                        "linkurl" => "http://wap.gaoren.net/index.php?m=ZTM&c=Appwx&a=oneDollar&source=xiaoningmeng",
//                    ),
                    //唯彩会-彩票中奖助你中500万
                    array(
                        "cover" => "http://p.xiaoningmeng.net/static/wei_cai_hui.jpg",
                        "linkurl" => "http://dl2.vipc.cn/Vipc-APPxnmteg.apk",
                    ),
                    //投票大王
                    array(
                        "cover" => "http://p.xiaoningmeng.net/static/vote.jpg",
                        "linkurl" => "http://wechat.iuqiancheng.com/download/vote?c=xiaoningmeng",
                    ),
                    //免费电子书
                    array(
                        "cover" => "http://p.xiaoningmeng.net/static/xiaoshuo.jpg",
                        "linkurl" => "http://jic.xiaoningmeng.net/apk/FreeBook_V41_1910_FreeShu_baomdbhl_V2.7.0_9245_118_2016-12-15-06-23-14.apk",
                    ),
                )
            );

            $redisobj->setex($indexDataKey, self::CACHE_EXPIRE, serialize($data));
        }
        $this->showSuccJson($data);
    }


    public function getAlbumInfo($albumList, $albumListenNum, $recommendDescList, $albumId)
    {

        $aliossObj = new AliOss();
        $albumObj = new Album();

        $albumInfo = array();
        if (!empty($albumList[$albumId])) {
            $albumInfo['id'] = $albumList[$albumId]['id'];
            $albumInfo['title'] = $albumList[$albumId]['title'];
            $albumInfo['star_level'] = $albumList[$albumId]['star_level'];
            $albumInfo['intro'] = $albumList[$albumId]['intro'];
            if (!empty($albumList[$albumId]['cover'])) {
                $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM,
                    $albumList[$albumId]['cover'], 460, $albumList[$albumId]['cover_time']);
            }
            $albumInfo['listennum'] = 0;
            if (!empty($albumListenNum[$albumId])) {
                $albumInfo['listennum'] = $albumObj->format_album_listen_num($albumListenNum[$albumId]['num'] + 0);
            }
            $albumInfo['recommenddesc'] = "";
            if (!empty($recommendDescList[$albumId])) {
                $albumInfo['recommenddesc'] = $recommendDescList[$albumId]['desc'];
            }
            //$albumSectionItem['items'][] = $albumInfo;
        }
        return $albumInfo;
    }
}

new index();