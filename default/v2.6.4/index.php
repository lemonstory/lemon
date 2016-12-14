<?php
/**
 * 首页接口
 * Date: 16/10/9
 * Time: 下午3:51
 */

include_once '../../controller.php';

class index extends controller
{
    const SECTION_ALBUM = 'album';
    const SECTION_AUTHOR = 'author';

    public function action()
    {
        $uid = $this->getUid();
        $userInfo = array();
        $albumIds = array();
        $recommendObj = new Recommend();
        $aliossObj = new AliOss();
        $data = array();
        $configVar = new ConfigVar();

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

        $albumLen = 6;
        $currentPage = 1;
        // 热门推荐
        $hotRecommendRes = $recommendObj->getRecommendHotList($configVar->MIN_AGE, $configVar->MAX_AGE, 0, $currentPage,
            $albumLen);
        if (!empty($hotRecommendRes)) {
            foreach ($hotRecommendRes as $value) {
                $albumIds[] = $value['id'];
            }
        }

        $babyagetype = 0;
        if (!empty($uid)) {
            $userobj = new User();
            $userInfo = current($userobj->getUserInfo($uid, 1));
            if (!empty($userInfo)) {
                $userextobj = new UserExtend();
                $babyagetype = $userextobj->getBabyAgeType($userInfo['age']);
            }
        }

        // 同龄在听
        $sameAgeRes = $recommendObj->getSameAgeListenList($configVar->MIN_AGE, $configVar->MAX_AGE, 0, $currentPage,
            $albumLen);
        if (!empty($sameAgeRes)) {
            foreach ($sameAgeRes as $value) {
                $albumIds[] = $value['id'];
            }
        }

        // 最新上架
        $newOnlineRes = $recommendObj->getNewOnlineList($configVar->MIN_AGE, $configVar->MAX_AGE, 0, $currentPage,
            $albumLen);
        if (!empty($newOnlineRes)) {
            foreach ($newOnlineRes as $value) {
                $albumIds[] = $value['id'];
            }
        }

        $albumList = array();
        $recommendDescList = array();
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

        $data['section']['items'][] = array(
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
        $data['section']['items'][] = array(
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
        $data['section']['items'][] = array(
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
        $data['section']['items'][] = array(
            'type' => self::SECTION_ALBUM,
            'tag_id' => $configVar->NEW_ONLINE_TAG_ID,
            'title' => "最新上架",
            'total' => count($newAlbumList),
            'linkurl' => "xnm://api.xiaoningmeng.net/default/v2.6/online_list.php",
            'items' => $newAlbumList,
        );

        //一级标签
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
                $data['section']['items'][] = $albumSectionItem;
            }
        }

        //广告
        $data['ad'] = array(
            "total" => 0,
            "items" => array(
//                array(
//                    "cover" => "http://p.xiaoningmeng.net/focus/banner.png",
//                    "linkurl" => "http://www.mizhuan.me/ ",
//                ),
//                array(
//                    "cover" => "https://img3.doubanio.com/view/dale-online/dale_ad/public/18a3cc696cf9561.jpg",
//                    "linkurl" => "http://www.douban.com/",
//                ),
            )
        );

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