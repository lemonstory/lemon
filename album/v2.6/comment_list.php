<?php

include_once '../../controller.php';

class commentList extends controller
{
    function action()
    {
        $result = array();
        $albumId = $this->getRequest("album_id", "0");
        $direction = $this->getRequest("direction", "down");
        $startCommentId = $this->getRequest("start_comment_id", 0);
        $len = $this->getRequest("len", 10);
        $commentObj = new Comment();
        $sizeSize = 120;
        // 长度限制
        if ($len > 50) {
            $len = 50;
        }
        // 评论分页

        $totalArr = $commentObj->countAlbumComment($albumId);
        $result['total'] = $totalArr[$albumId];

        // 评论列表
        $commentList = $commentObj->get_comment_list(
            "`albumid`={$albumId}",
            "ORDER BY `id` DESC ",
            $direction,
            $startCommentId,
            $len
        );

        foreach ($commentList as $key => $item) {

            $commentInfo = array();
            $commentInfo['id'] = $item['id'];
            $commentInfo['uid'] = $item['uid'];
            $commentInfo['uname'] = $item['uname'];
            $commentInfo['avatar'] = sprintf("http://a.xiaoningmeng.net/avatar/%s/%s/%s", $item['uid'], $item['avatartime'], $sizeSize);
            $commentInfo['start_level'] = $item['start_level'];
            $commentInfo['addtime'] = $item['addtime'];
            $commentInfo['comment'] = $item['comment'];
            $result['items'][] = $commentInfo;
        }

        $this->showSuccJson($result);

    }
}

new commentList();