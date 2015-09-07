<?php
/**
 * 喜马拉雅故事分类采集
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class deal_userListenStory extends DaemonBase {
    protected $processnum = 1;
	protected function deal() {
		$this->c_xmly_category();
	    exit;
	}

	protected function checkLogPath() {}

	protected function c_xmly_category() {
        $category = new Category();
        $xmly = new Xmly();
        $story_url = new StoryUrl();
        $current_time = date('Y-m-d H:i:s');
        // 分类
        $category_list = $xmly->get_category($this->home_url);
        foreach ($category_list as $k => $v) {
            $exist = $category->check_exists("`res_name`='xmly' and `title`='{$v['title']}'");
            if ($exist) {
                continue;
            }
        	$category_id = $category->insert(array(
        		'res_name'  => 'xmly',
        		'title'     => $v['title'],
        		'parent_id' => '0',
        		'from_url'  => $this->home_url,
        		'link_url'  => $v['link_url'],
                's_cover'   => $v['cover'],
                'add_time'   => $current_time,
        	));
            $story_url->insert(array(
                'res_name' => 'category',
                'res_id' => $category_id,
                'field_name' => 'cover',
                'source_url' => $v['cover'],
                'source_file_name' => ltrim(strrchr($v['cover'], '/'), '/'),
                'add_time' => date('Y-m-d H:i:s'),
            ));
            echo $category_id;
            echo "<br />";
        }
    }



}
new deal_userListenStory ();