<?php

include_once '../controller.php';
class kdgs_story extends controller 
{
    function action() {
        $album = new Album();
        $story = new Story();
        $kdgs  = new Kdgs();
        $story_url = new StoryUrl();
        $album_list = $album->get_list("id>0", 10);
        foreach ($album_list as $k => $v) {
            if (!$v['age_type']) {
                $v['age_type'] = $album->get_age_type($v['age_str']);
                $album->update(array('age_type' => $v['age_type']), "`id`={$v['id']}");
            }
            $story_list = $kdgs->get_album_story_list($v['link_url']);
            foreach($story_list as $k2 => $v2) {
                $exists = $story->check_exists("`source_audio_url`='{$v2['source_audio_url']}'");
                if ($exists) {
                    continue;
                }
                $story_id = $story->insert(array(
                    'album_id' => $v['id'],
                    'title' => $v2['title'],
                    'intro' => $v2['intro'],
                    's_cover' => $v2['cover'],
                    'source_audio_url' => $v2['source_audio_url'],
                    'add_time' => date('Y-m-d H:i:s'),
                ));
                $story_url->insert(array(
                    'res_name'         => 'story',
                    'res_id'           => $story_id,
                    'field_name'       => 'cover',
                    'source_url'       => $v2['cover'],
                    'source_file_name' => ltrim(strrchr($v2['cover'], '/'), '/'),
                    'add_time'         => date('Y-m-d H:i:s'),
                ));
                echo $story_id;
                echo "<br />";
            }
        }
    }
}
new kdgs_story();