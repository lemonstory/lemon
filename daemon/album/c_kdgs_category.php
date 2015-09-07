<?php
/**
 * 口袋故事分类采集
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class deal_userListenStory extends DaemonBase {
    protected $processnum = 1;
	protected function deal() {
		$this->c_kdgs_category();
	    exit;
	}

	protected function checkLogPath() {}


	protected function c_kdgs_category() {
        // 子类
        $category = new Category();
        $kdgs = new Kdgs();
        $story_url = new StoryUrl();
        $current_time = date('Y-m-d H:i:s');

        $parent_category_list = $kdgs->get_parent_category($this->home_url);

        foreach ($parent_category_list as $k => $v) {
            $exists = $category->check_exists("`res_name`='kdgs' and `s_id`='{$v['s_id']}'");
            if ($exists) {
                continue;
            } else {
                $category_id = $category->insert(array(
                    'res_name' => 'kdgs',
                    'parent_id' => 0,
                    'title' => $v['title'],
                    's_id' => $v['s_id'],
                    's_p_id' => 0,
                    'from_url' => $this->home_url,
                    'link_url' => $v['link_url'],
                    's_cover' =>  $v['cover'],
                    'add_time' => date('Y-m-d H:i:s')
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
        // 子类
        $category_list = $category->get_list("`res_name`='kdgs' and `s_p_id`=0");
        foreach($category_list as $k => $v) {
            $children_category_list = $kdgs->get_children_category_list($v['link_url']);
            foreach($children_category_list as $k2 => $v2) {
                $exists = $category->check_exists("`res_name`='kdgs' and `s_id`='{$v['s_id']}' and `s_p_id`='{$v2['s_p_id']}'");
                if ($exists) {
                    continue;
                } else {
                    $category_id = $category->insert(array(
                        'res_name' => 'kdgs',
                        'parent_id' => $v['id'],
                        'title' => $v2['title'],
                        's_id' => $v['s_id'],
                        's_p_id' => $v2['s_p_id'],
                        'from_url' => $v['link_url'],
                        'link_url' => $v2['link_url'],
                        's_cover' =>  $v2['cover'],
                        'add_time' => date('Y-m-d H:i:s')
                    ));
                    $story_url->insert(array(
                        'res_name' => 'category',
                        'res_id' => $category_id,
                        'field_name' => 'cover',
                        'source_url' => $v2['cover'],
                        'source_file_name' => ltrim(strrchr($v2['cover'], '/'), '/'),
                        'add_time' => date('Y-m-d H:i:s'),
                    ));
                    echo "children：".$category_id;
                    echo "<br />";
                }
            }
        }
    }


}
new deal_userListenStory ();