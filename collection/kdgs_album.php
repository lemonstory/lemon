<?php

include_once '../controller.php';
class kdgs_album extends controller 
{
    function action() {
        $category = new Category();
        $kdgs = new Kdgs();
        $album = new Album();
        $story_url = new StoryUrl();
        $category_list = $category->get_list("`res_name`='kdgs' and `parent_id`>0");
        $count = 1;

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
                    $count++;
                    if ($count > 25) {
                        exit;
                    }
                    $album_id = $album->insert(array(
                        'title'       => $v2['title'],
                        'category_id' => $v['id'],
                        'from'        => 'kdgs',
                        'intro'       => '',
                        's_cover'     => $v2['cover'],
                        'link_url'    => $v2['url'],
                        'age_str'     => $v2['age_str'],
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

new kdgs_album();