<?php

include_once '../controller.php';
class xmly_category extends controller 
{
	private $home_url = 'http://m.ximalaya.com/album-tag/kid';
    function action() {
        // 子类
        $category = new Category();
        $xmly = new Xmly();
        $story_url = new StoryUrl();
        $current_time = date('Y-m-d H:i:s');

        $category_list = $xmly->get_category($this->home_url);
        var_dump($category_list);

    }
}
new xmly_category();