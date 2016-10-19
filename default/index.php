<?php
include_once '../controller.php';

class index extends controller
{
    public function action()
    {
        $uid = $this->getUid();
        $userinfo = array();
        $albumids = array();
        $recommendObj = new Recommend();
        $aliossobj = new AliOss();
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
                $albumids[] = $value['id'];
            }
        }

        $babyagetype = 0;
        if (!empty($uid)) {
            $userobj = new User();
            $userinfo = current($userobj->getUserInfo($uid, 1));
            if (!empty($userinfo)) {
                $userextobj = new UserExtend();
                $babyagetype = $userextobj->getBabyAgeType($userinfo['age']);
            }
        }

        // 同龄在听
        $sameageres = $recommendObj->getSameAgeListenList($configVar->MIN_AGE, $configVar->MAX_AGE, 0, 1, $albumLen);
        if (!empty($sameageres)) {
            foreach ($sameageres as $value) {
                $albumids[] = $value['id'];
            }
        }

        // 最新上架
        $newonlineres = $recommendObj->getNewOnlineList($configVar->MIN_AGE, $configVar->MAX_AGE, 0, 1, $albumLen);
        if (!empty($newonlineres)) {
            foreach ($newonlineres as $value) {
                $albumids[] = $value['id'];
            }
        }

        $albumlist = array();
        $recommenddesclist = array();
        if (!empty($albumids)) {
            $albumids = array_unique($albumids);
            // 专辑信息
            $albumobj = new Album();
            $albumlist = $albumobj->getListByIds($albumids);
            // 专辑收听数
            $listenobj = new Listen();
            $albumlistennum = $listenobj->getAlbumListenNum($albumids);

            if ($_SERVER['visitorappversion'] >= "130000") {
                // 获取推荐语
                $recommenddescobj = new RecommendDesc();
                $recommenddesclist = $recommenddescobj->getAlbumRecommendDescList($albumids);
            }
        }

        //var_dump($hotRecommendRes);
        $hotrecommendlist = array();
        $sameagealbumlist = array();
        $newalbumlist = array();
        if (!empty($hotRecommendRes)) {
            foreach ($hotRecommendRes as $value) {
                $albumid = $value['id'];
                if (!empty($albumlist[$albumid])) {
                    $albuminfo = $albumlist[$albumid];
                    if (!empty($albuminfo['cover'])) {
                        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
                    }
                    $albuminfo['listennum'] = 0;
                    if (!empty($albumlistennum[$albumid]) && intval($albumlistennum[$albumid]['num']) > 0) {
                        $albuminfo['listennum'] = substr($albumlistennum[$albumid]['num'], 0, 5);
                    }
                    $albuminfo['recommenddesc'] = "";
                    if (!empty($recommenddesclist[$albumid])) {
                        $albuminfo['recommenddesc'] = $recommenddesclist[$albumid]['desc'];
                    }
                    $hotrecommendlist[] = $albuminfo;
                }
            }
        }

        if (!empty($sameageres)) {
            foreach ($sameageres as $value) {
                $albumid = $value['id'];
                if (!empty($albumlist[$albumid])) {
                    $albuminfo = $albumlist[$albumid];
                    if (!empty($albuminfo['cover'])) {
                        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
                    }
                    $albuminfo['listennum'] = 0;
                    if (!empty($albumlistennum[$albumid]) && intval($albumlistennum[$albumid]['num'])) {
                        $albuminfo['listennum'] = substr($albumlistennum[$albumid]['num'], 0, 5);
                    }
                    $albuminfo['recommenddesc'] = "";
                    if (!empty($recommenddesclist[$albumid])) {
                        $albuminfo['recommenddesc'] = $recommenddesclist[$albumid]['desc'];
                    }
                    $sameagealbumlist[] = $albuminfo;
                }
            }
        }
        if (!empty($newonlineres)) {
            foreach ($newonlineres as $value) {
                $albumid = $value['id'];
                if (!empty($albumlist[$albumid])) {
                    $albuminfo = $albumlist[$albumid];
                    if (!empty($albuminfo['cover'])) {
                        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
                    }
                    $albuminfo['listennum'] = 0;
                    if (!empty($albumlistennum[$albumid]) && intval($albumlistennum[$albumid]['num']) > 0) {
                        $albuminfo['listennum'] = substr($albumlistennum[$albumid]['num'], 0, 5);
                    }
                    $albuminfo['recommenddesc'] = "";
                    if (!empty($recommenddesclist[$albumid])) {
                        $albuminfo['recommenddesc'] = $recommenddesclist[$albumid]['desc'];
                    }
                    $newalbumlist[] = $albuminfo;
                }
            }
        }

        // 推广位
        $focuspiclist = array();
        $focusres = $recommendObj->getFocusList(6);
        if (!empty($focusres)) {
            foreach ($focusres as $value) {
                $focusinfo['cover'] = $aliossobj->getFocusUrl($value['id'], $value['covertime'], 1);
                $focusinfo['linktype'] = $value['linktype'];
                $focusinfo['linkurl'] = $value['linkurl'];
                $focuspiclist[] = $focusinfo;
            }
        }

        // 一级标签列表
        $firsttaglist = array();
        if ($_SERVER['visitorappversion'] >= "130000") {
            $tagnewobj = new TagNew();
            $firsttagres = $tagnewobj->getFirstTagList(8);
            if (!empty($firsttagres)) {
                foreach ($firsttagres as $value) {
                    if (!empty($value['cover'])) {
                        $value['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_TAG, $value['cover'], 0, $value['covertime']);
                    }
                    $firsttaglist[] = $value;
                }
            }
        }

        // 私人订制


        $data = array(
            "focuspic" => $focuspiclist,
            "hotrecommend" => $hotrecommendlist,
            "samgeage" => $sameagealbumlist,
            "newalbum" => $newalbumlist,
            "firsttag" => $firsttaglist
        );
        $this->showSuccJson($data);
    }
}

new index();