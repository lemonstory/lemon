<?php

include_once '../controller.php';
class kdgs_album extends controller 
{
    
    function action() {
        $category = new Category();
        $kdgs = new Kdgs();
        $album = new Album();
        $story_url = new StoryUrl();
        $category_list = $category->get_list("`res_name`='kdgs' and `s_id`='0'");
        foreach($category_list as $k => $v) {
            $page = 1;
            while(true) {
                $album_list = $kdgs->get_children_category_album_list($v['s_p_id'], $page);
                if (!$album_list) {
                    break;
                }
                foreach ($album_list as $k2 => $v2) {
                    $exists = $album->check_exists("`link_url` = '{$v2['url']}'");
                    if ($exists) {
                        continue;
                    }
                    $album_id = $album->insert(array(
                        'title'      => $v2['title'],
                        'min_age'    => $v2['min_age'],
                        'max_age'    => $v2['max_age'],
                        'intro'      => '',
                        's_cover'    => $v2['cover'],
                        'link_url'   => $v2['url'],
                        'add_time'   => date('Y-m-d H:i:s'),
                    ));
                    $story_url->insert(array(
                        'res_name' => 'album',
                        'res_id' => $album_id,
                        'field_name' => 'cover',
                        'source_url' => $v2['cover'],
                        'source_file_name' => ltrim(strrchr($v2['cover'], '/'), '/'),
                        'add_time' => date('Y-m-d H:i:s'),
                    ));
                }
                $page ++;
            }
        }
    }
}
new kdgs_album();