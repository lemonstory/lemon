<?php

include_once '../controller.php';
class test extends controller
{
    function action() {
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

