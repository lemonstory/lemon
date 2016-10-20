<?php
include_once '../controller.php';

class index extends controller
{
    public function action()
    {
        $uid = $this->getUid();
        $userInfo = array();
        $albumIds = array();
        $recommendObj = new Recommend();
        $aliossObj = new AliOss();
        $configVar = new ConfigVar();
        $albumObj = new Album();

        if ($_SERVER['visitorappversion'] >= "130000") {
            $albumLen = 8;
        } else {
            $albumLen = 9;
        }
        $currentPage = 1;

        // 热门推荐
        $hotRecommendRes = $recommendObj->getRecommendHotList($configVar->MIN_AGE, $configVar->MAX_AGE, 0, $currentPage, $albumLen);

        if (!empty($hotRecommendRes)) {
            foreach ($hotRecommendRes as $value) {
                $albumIds[] = $value['id'];
            }
        }

        $babyAge = 0;
        $userExtObj = new UserExtend();
        if (!empty($uid)) {
            $userObj = new User();
            $userInfo = current($userObj->getUserInfo($uid, 1));
            if (!empty($userInfo)) {
                $babyAge = $userInfo['age'];
            }
        }
        $babyAgeLevel = $userExtObj->getBabyAgeGroup($babyAge);

        // 同龄在听
        $sameAgeRes = $recommendObj->getSameAgeListenList($babyAgeLevel['min_age'], $babyAgeLevel['max_age'], 0, 1, $albumLen);

        if (!empty($sameAgeRes)) {
            foreach ($sameAgeRes as $value) {
                $albumIds[] = $value['id'];
            }
        }

        // 最新上架
        $newOnlineRes = $recommendObj->getNewOnlineList($configVar->MIN_AGE, $configVar->MAX_AGE, 0, 1, $albumLen);
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
            $albumList = $albumObj->getListByIds($albumIds);
            // 专辑收听数
            $listenObj = new Listen();
            $albumListenNum = $listenObj->getAlbumListenNum($albumIds);

            if ($_SERVER['visitorappversion'] >= "130000") {
                // 获取推荐语
                $recommendDescObj = new RecommendDesc();
                $recommendDescList = $recommendDescObj->getAlbumRecommendDescList($albumIds);
            }
        }

        //var_dump($hotRecommendRes);
        $hotRecommendList = array();
        $sameAgeAlbumList = array();
        $newAlbumList = array();
        if (!empty($hotRecommendRes)) {
            foreach ($hotRecommendRes as $value) {
                $albumInfo = $this->getAlbumInfo($albumList, $albumListenNum, $recommendDescList, $value['id']);
                if (is_array($albumInfo) && !empty($albumInfo)) {
                    $hotRecommendList[] = $albumInfo;
                }
            }
        }

        if (!empty($sameAgeRes)) {
            foreach ($sameAgeRes as $value) {
                $albumInfo = $this->getAlbumInfo($albumList, $albumListenNum, $recommendDescList, $value['id']);
                if (is_array($albumInfo) && !empty($albumInfo)) {
                    $sameAgeAlbumList[] = $albumInfo;
                }
            }
        }
        if (!empty($newOnlineRes)) {
            foreach ($newOnlineRes as $value) {
                $albumInfo = $this->getAlbumInfo($albumList, $albumListenNum, $recommendDescList, $value['id']);
                if (is_array($albumInfo) && !empty($albumInfo)) {
                    $newAlbumList[] = $albumInfo;
                }
            }
        }

        // 推广位
        $focusPicList = array();
        $focusRes = $recommendObj->getFocusList(6);
        if (!empty($focusRes)) {
            foreach ($focusRes as $value) {
                $focusinfo['cover'] = $aliossObj->getFocusUrl($value['id'], $value['covertime'], 1);
                $focusinfo['linktype'] = $value['linktype'];
                $focusinfo['linkurl'] = $value['linkurl'];
                $focusPicList[] = $focusinfo;
            }
        }

        // 一级标签列表
        $firstTagList = array();
        if ($_SERVER['visitorappversion'] >= "130000") {
            $tagNewObj = new TagNew();
            $firstTagRes = $tagNewObj->getFirstTagList(8);
            if (!empty($firstTagRes)) {
                foreach ($firstTagRes as $value) {

                    $tagInfo = array();
                    $tagInfo['id'] = $value['id'];
                    $tagInfo['pid'] = $value['pid'];
                    $tagInfo['name'] = $value['name'];
                    if (!empty($value['cover'])) {
                        $tagInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_TAG, $value['cover'], 0, $value['covertime']);
                    }
                    $firstTagList[] = $tagInfo;
                }
            }
        }

        // 私人订制
        $data = array(
            "focuspic" => $focusPicList,
            "hotrecommend" => $hotRecommendList,
            "samgeage" => $sameAgeAlbumList,
            "newalbum" => $newAlbumList,
            "firsttag" => $firstTagList
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
            //$albumInfo['intro'] = $albumList[$albumId]['intro'];
            if (!empty($albumList[$albumId]['cover'])) {
                $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albumList[$albumId]['cover'], 460, $albumList[$albumId]['cover']);
            }
            $albumInfo['listennum'] = 0;
            if (!empty($albumListenNum[$albumId])) {
                $albumInfo['listennum'] = $albumListenNum[$albumId]['num'] + 0;
                $albumInfo['listennum'] = substr($albumInfo['listennum'], 0, 5);
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