<?php

include_once '../controller.php';
class xmly_story extends controller 
{
    function action() {
        $album = new Album();
        $story = new Story();
        $xmly  = new Xmly();
        $story_url = new StoryUrl();
        
        $album_list = $album->get_list("`from`='xmly'", ' 2,10');
        
        foreach($album_list as $k => $v) {
        	$album_id = Http::sub_data($v['link_url'], 'album/');
        	$page = 1;
        	$story_list = $xmly->get_story_list($album_id, $page);

        	while (true) {
        		$story_list = $xmly->get_story_list($album_id, $page);
        		if (!$story_list) {
        			break;
        		}

                foreach ($story_list as $k2 => $v2) {
                	$exists = $story->check_exists("`source_audio_url`='{$v2['source_audio_url']}'");
	                if ($exists) {
	                    continue;
	                }
	                $story_id = $story->insert(array(
	                    'album_id' => $v['id'],
	                    'title' => $v2['title'],
	                    'intro' => $v2['intro'],
	                    's_cover' => $v2['s_cover'],
	                    'source_audio_url' => $v2['source_audio_url'],
	                    'add_time' => date('Y-m-d H:i:s'),
	                ));
	                $story_url->insert(array(
	                    'res_name'         => 'story',
	                    'res_id'           => $story_id,
	                    'field_name'       => 'cover',
	                    'source_url'       => $v2['s_cover'],
	                    'source_file_name' => ltrim(strrchr($v2['s_cover'], '/'), '/'),
	                    'add_time'         => date('Y-m-d H:i:s'),
	                ));
                }
                $page ++;
                echo $story_id;
                echo "<br />";
        	}
        	
        }
    }
}
new xmly_story();