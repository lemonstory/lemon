<?php

include_once '../controller.php';
class xmly_album extends controller 
{
	private $home_url = 'http://m.ximalaya.com/album-tag/kid';
    function action() {

        $category = new Category();
        $xmly = new Xmly();
        $story_url = new StoryUrl();
        $album = new Album();
        $current_time = date('Y-m-d H:i:s');
        // 分类
        $category_list = $category->get_list("`res_name`='xmly'");

        foreach ($category_list as $k => $v) {
            $page = 1;
            while(true) {
                $album_list = $xmly->get_album_list($page, $v['title']);
                if (!$album_list) {
                    break;
                }
                foreach ($album_list as $k2 => $v2) {
                    $exists = $album->check_exists("`link_url` = '{$v2['url']}'");
                    if ($exists) {
                        continue;
                    }

                    $album_id = $album->insert(array(
                        'title'       => $v2['title'],
                        'from'        => 'xmly',
                        'intro'       => '',
                        'category_id' => $v['id'],
                        's_cover'     => $v2['cover'],
                        'link_url'    => $v2['url'],
                        'add_time'    => date('Y-m-d H:i:s'),
                    ));
                    $story_url->insert(array(
                        'res_name' => 'album',
                        'res_id' => $album_id,
                        'field_name' => 'cover',
                        'source_url' => $v2['cover'],
                        'source_file_name' => ltrim(strrchr($v2['cover'], '/'), '/'),
                        'add_time' => date('Y-m-d H:i:s'),
                    ));
                    echo $album_id;
                    echo "<br />";
                }
                $page ++;
            }

        }

    }
}
new xmly_album();