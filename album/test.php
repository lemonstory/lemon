<?php

include_once '../controller.php';
class test extends controller
{
    function action() {
        // 修复封面
        $album = new Story();
        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT id,`cover`,`update_time` FROM `album` WHERE `id` >=6988 and cover != '' order by id asc ";
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $album_list = $st->fetchAll();
        foreach ($album_list as $k => $v) {
            $cover = str_replace("album/", '', $v['cover']);
            $cover_time = strtotime($v['update_time']);
            if ($cover) {
                $album->update(array('cover' => $cover, 'cover_time' => $cover_time), "`id`={$v['id']}");
                echo "{$v['id']} 更新成功<br />";
            }
        }
        exit;
        // 修复封面
        $story = new Story();
        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT id,`cover`,`upate_time` FROM `story` WHERE `id` >=112316 and cover != '' order by id asc ";
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $story_list = $st->fetchAll();
        foreach ($story_list as $k => $v) {
            $cover = str_replace("story/", '', $v['cover']);
            $cover_time = strtotime($v['upate_time']);
            if ($cover) {
                $story->update(array('cover' => $cover, 'cover_time' => $cover_time), "`id`={$v['id']}");
                echo "{$v['id']} 更新成功<br />";
            }
        }
        exit;
        $r = $this->middle_upload('http://fdfs.xmcdn.com/group2/M02/0A/04/wKgDr1GI6d7xh2RIAAWohUyGdp82513715_mobile_large', 47906, 2);
        if ($r) {
            $story = new Story();
            $story->update(array('cover' => $r), " `id`= 47906");
        }
        exit;
        set_time_limit(0);
        $story = new Story();
        $page = $this->getRequest('page');
        if (!$page) {
            exit('params error !');
        }
        $perpage = 2000;

        $limit = ($page - 1) * $perpage;
        $story_list = $story->get_list("`id`>685 and `id` <= 50000", "{$limit}, {$perpage}", '', "ORDER BY id ASC");
        if (!$story_list) {
            break;
        }
        foreach ($story_list as $k => $v) {
            MnsQueueManager::pushAlbumToSearchQueue($v['id']);
        }
        echo "{$page} =>{$limit}, {$perpage} 完成 <br />\n";
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


    /**
     * 功能：php完美实现下载远程图片保存到本地
     * 将本地文件上传到oss,删除本地文件
     * type 1 专辑封面 2 故事封面 3 故事音频
     */
    private function middle_upload($url = '', $id = '', $type = ''){
        // 默认图片不上传
        if (strstr($url, 'default/album.jpg')) {
            return '';
        }
        if (strstr($url, 'default/sound.jpg')) {
            return '';
        }
        // 控制上传频率
        sleep(1);

        if (!$url || !$id || !$type) {
            return false;
        }

        $uploadobj = new Upload();
        $aliossobj = new AliOss();

        if ($type == 3) {
            $savedir = $aliossobj->LOCAL_MEDIA_TMP_PATH;
        } else {
            $savedir = $aliossobj->LOCAL_IMG_TMP_PATH;
        }

        $ext = strtolower(ltrim(strrchr($url,'.'), '.'));

        $filename = date("Y_m_d_{$type}_{$id}");

        $savedir = $savedir.date("Y_m_d_{$type}_{$id}");

        if(!in_array($ext, array('png', 'gif', 'jpg', 'jpeg', 'mp3', 'audio'))){
            if (strstr($url, 'mobile_large')) {
                $ext = 'jpg';
            } else {
                return false;
            }
        }

        $full_file = $savedir.'.'.$ext;

        if (file_exists($full_file)) {
            @unlink($full_file);
        }
        $file = Http::download($url, $full_file);

        if ($type == 3) {
            $res = $uploadobj->uploadStoryMedia($filename, $ext, $id);
            return $res;
        } else {
            $res = $uploadobj->uploadAlbumImage($filename, $ext, $id);
            if (isset($res['path'])) {
                return $res['path'];
            }
        }

        return '';

    }
}
new test();

