<?php
/**
 * 专辑故事列表
 *
 * Date: 16/10/22
 * Time: 下午3:55
 */

include_once '../../controller.php';

class storyList extends controller
{
    function action()
    {
        $result = array();
        $albumId = intval($this->getRequest("album_id", "0"));
        $page = intval($this->getRequest("page", "1"));
        $len = intval($this->getRequest("len", "50"));

        if ($albumId > 0 && $page > 0 && $len > 0) {

            $storyList = array();
            $aliossObj = new AliOss();
            $albumObj = new Album();
            $storyObj = new Story();
            $configVarObj = new ConfigVar();
            // 专辑信息
            $albumInfo = $albumObj->get_album_info($albumId);
            $storyResList = $storyObj->get_album_story_list($albumId, $page, $len);
            $storyTotal = $storyObj->get_total(" `album_id`={$albumId} and `status`=1 ");
            if (!empty($storyResList)) {
                foreach ($storyResList as $value) {

                    $storyInfo = array();
                    $storyInfo['id'] = $value['id'];
                    //部分英文故事辑里面会有多余的反斜杠
                    $storyInfo['title'] = stripslashes($value['title']);
                    //$storyInfo['intro'] = $value['intro'];
                    $storyInfo['times'] = $value['times'];
                    $storyInfo['mediapath'] = $value['mediapath'];
                    $storyInfo['view_order'] = $value['view_order'];
                    $storyInfo['playcover'] = $configVarObj->DEFAULT_STORY_COVER;
                    if (!empty($value['cover'])) {
                        $storyInfo['playcover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_STORY, $value['cover'], 460, $value['cover_time']);
                    } else if (!empty($albumInfo['cover'])) {
                        $storyInfo['playcover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_ALBUM, $albumInfo['cover'], 460, $albumInfo['cover_time']);
                    }
                    $storyList[] = $storyInfo;
                }
            }
            $result['total'] = $storyTotal;
            $result['items'] = $storyList;
        }

        // 返回成功json
        $this->showSuccJson($result);
    }
}

new storyList();