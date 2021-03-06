<?php
/**
 * 作者的专辑
 * Date: 16/9/27
 * Time: 下午6:59
 */

include_once '../../controller.php';

class author_album_list extends controller
{
    public function action()
    {
        $authorId = $this->getRequest('author_id', '');
        $startAlbumId = $this->getRequest('start_album_id', '0');
        $len = $this->getRequest('len', '20');
        $avatarSize = 120;

        if (!empty($authorId)) {

            $ret = array();
            $albumObj = new Album();
            $aliossObj = new AliOss();

            //作者信息
            $userObj = new User();
            $userInfo = current($userObj->getUserInfo($authorId, 0));

            $creatorObj = new Creator();
            $creatorInfo = $creatorObj->getCreatorInfo($authorId);

            $authorInfo = array();
            $authorInfo['avator'] = $aliossObj->getAvatarUrl($authorId, $userInfo['avatartime'], $avatarSize);
            $authorInfo['nickname'] = $userInfo['nickname'];
            if (empty($creatorInfo['intro'])) {
                $creatorInfo['intro'] = "我们的工作失误,所以没有信息";
            }
            $authorInfo['intro'] = $creatorInfo['intro'];
            $authorInfo['wiki_url'] = "https://www.xiaoningmeng.net/author/detail.php?uid={$authorId}";
            $ret['info'] = $authorInfo;

            //作者的专辑信息
            $albums = $albumObj->getAuthorAlbums($authorId, $startAlbumId, $len);
            $ret['total'] = count($albums);
            if (!empty($albums)) {
                foreach ($albums as $item) {
                    $albumIds[] = $item['id'];
                }

                if (!empty($albumIds)) {

                    $albumIds = array_unique($albumIds);

                    // 专辑收听数
                    $listenobj = new Listen();
                    $albumListenNum = $listenobj->getAlbumListenNum($albumIds);

                    // 获取推荐语
                    $recommenddescobj = new RecommendDesc();
                    $recommendDescList = $recommenddescobj->getAlbumRecommendDescList($albumIds);
                }
            }

            foreach ($albums as $key => $item) {

                $albumId = $item['id'];
                $albumInfo = array();
                $albumInfo['id'] = $albums[$key]['id'];
                $albumInfo['title'] = $albums[$key]['title'];

                if (!empty($albums[$key]['cover'])) {
                    $albumInfo['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albums[$key]['cover'], 460, $albums[$key]['cover_time']);
                }

                $albumInfo['listennum'] = 0;
                if (!empty($albumListenNum[$albumId])) {
                    $albumInfo['listennum'] = $albumObj->format_album_listen_num($albumListenNum[$albumId]['num'] + 0);
                }

                $albumInfo['recommenddesc'] = "";
                if (!empty($recommendDescList[$albumId])) {
                    $albumInfo['recommenddesc'] = $recommendDescList[$albumId]['desc'];
                } else {
                    //没有推荐语,则使用个人简介
                    $albumInfo['recommenddesc'] = $albums[$key]['intro'];
                }
                $albumInfo['recommenddesc'] = trim($albumInfo['recommenddesc']);
                $albumAgeLevelStr = $albumObj->getAgeLevelStr($albums[$key]['min_age'], $albums[$key]['max_age']);
                $albumInfo['age_str'] = sprintf("适合%s岁", $albumAgeLevelStr);

                $ret['items'][] = $albumInfo;
            }

            $this->showSuccJson($ret);

        } else {
            $this->showErrorJson(ErrorConf::paramError());
        }
    }
}

new author_album_list();