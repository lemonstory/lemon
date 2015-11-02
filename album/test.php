<?php

include_once '../controller.php';
class test extends controller
{
    function action() {
        $story = new Story();
        $page = 1;
        $perpage = 2000;
        while (true) {
            $limit = ($page - 1) * $perpage;
            $story_list = $story->get_list("`id`>0 and `id` <= 50000", "{$limit}, {$perpage}");
            if (!$story_list) {
                break;
            }
            foreach ($story_list as $k => $v) {
                MnsQueueManager::pushAlbumToSearchQueue($v['id']);
                echo "{$v['id']} 调用<br />\n";
            }
            $page ++;
            echo "{$page}, {$perpage} 完成 <br />\n";
        }
        exit;
        // 修复故事数量脚本
        $album = new Album();
        $story = new Story();
        $album_list = $album->get_list("`id`>0");
        foreach ($album_list as $k => $v) {
            $story_num = $story->get_total("`album_id`={$v['id']}");
            $album->update(array('story_num' => $story_num), "`id`={$v['id']}");
            echo "{$v['id']} 已经更新<br />\n";
        }
        exit;
        $album = new Album();
        $album_list = $album->get_list("`id`>0");
        $manageobj = new ManageSystem();
        foreach ($album_list as $k => $v) {
            $manageobj->addRecommendNewOnlineDb($v['id'], $v['age_type']);
            echo "{$v['id']} 调用<br />\n";
        }
        exit;
        $useralbumlog = new UserAlbumLog();
        $r = $useralbumlog->getPlayInfoByAlbumIds(array(1,2,3));
        var_dump($r);
        exit;
        $comment = new Comment();
        $r = $comment->getStarLevel(2);
        var_dump($r);
        exit;
        
        $albumcountarr = $comment->countAlbumComment(array(1,2,3));
        var_dump($albumcountarr);
        $story = new Story();
        $storylist = $story->getListByIds(1);
        var_dump($storylist);
        exit;
     //    var_dump($album_list);
    	$album = new Album();
    	$album_list = $album->get_list("`id` > 0");
    	$p = '';
    	$s = '';
    	foreach($album_list as $k => $v) {
    		// 1 0~2
    		// 2 3~6
    		// 3 7~10
    		$age_type = 0;
    		if (strstr($v['age_str'], 'P')) {
    			if ($v['age_str'] == 'P+') {
    				$age_type = 1;
    			} else if (strstr($v['age_str'], '-')) {
    				$tmp = explode('-', $v['age_str']);
    				if (isset($tmp[1])) {
    					if ($tmp[1] <= 2) {
    						$age_type = 1;
    					} else if ($tmp[1] <= 6 ) {
    						$age_type = 2;
    					} else if ($tmp[1] <= 10 ) {
    						$age_type = 3;
    					}
    				}
    			}
    		} else if (strstr($v['age_str'], '岁')) {
    			$str = str_replace('岁', '', $v['age_str']);
    			$tmp = explode('-', $str);
    			if (isset($tmp[1])) {
					if ($tmp[1] <= 2) {
						$age_type = 1;
					} else if ($tmp[1] <= 6 ) {
						$age_type = 2;
					} else if ($tmp[1] <= 10 ) {
						$age_type = 3;
					}
				}
    		} else {
    			echo $v['age_str'];
    		}
    		$album->update(array('age_type' => $age_type), "`id`={$v['id']}");
    	}
    }
}
new test();

