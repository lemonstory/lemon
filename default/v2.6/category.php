<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/24
 * Time: 上午9:58
 */
include_once '../../controller.php';
class categorylist extends controller
{
    public function action()
    {

        $data = array();

        //获取年龄段
        $configVar = new ConfigVar();
        $age_level_arr = $configVar->AGE_LEVEL_ARR;
        array_shift($age_level_arr);

        if (count($age_level_arr) > 0) {
            $data['age_level'] = array();
            $data['age_level']['total'] = count($age_level_arr);
            foreach ($age_level_arr as $val) {
                $item = array();
                $item['title'] = sprintf("%s-%s岁", $val['min_age'], $val['max_age']);
                $item['cover'] = sprintf("http://p.xiaoningmeng.net/age_level/%s-%s.png", $val['min_age'], $val['max_age']);
                $item['linkurl'] = sprintf("xnm://www.xiaoningmeng.net/default/v2.6/age_level_list.php?min_age=%s&min_age=%s", $val['min_age'], $val['max_age']);
                $data['age_level']['items'][] = $item;
            }
        }

        $data['tag'] = array();
        $tagInfoObj = new TagInfo();
        //获取一级分类
        $firstList = $tagInfoObj->get_list(' pid = 0 and status=1','id,name','ordernum asc',100);
        $data['tag']['total'] = count($firstList);
        foreach($firstList as $key=>$val){
            $tmp['id'] = $val['id'];
            $tmp['name'] = $val['name'];
            //取二级分类
            $secondList = $tagInfoObj->get_list(' pid = '.$val['id'].' and status=1','id,name','ordernum asc',100);
            foreach ($secondList as $key => $val) {
                $secondList[$key]['linkurl'] = sprintf("xnm://www.xiaoningmeng.net/default/v2.6/tag_album_list.php?tag_id=%s", $val['id']);
            }
            $tmp['child_total'] = count($secondList);
            $tmp['child_items'] = $secondList;

            $data['tag']['items'][] = $tmp;
        }
        $this->showSuccJson($data);
    }
}
new categorylist();