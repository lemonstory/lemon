<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/24
 * Time: 上午9:58
 */
include_once '../controller.php';
class categorylist extends controller
{
    public function action()
    {
        $categoryObj = new Category();
        $data = array();
        //获取一级分类
        $firstList = $categoryObj->get_list(' parent_id = 0 ');
        $data['total'] = count($firstList);
        foreach($firstList as $key=>$val){
            $tmp['id'] = $val['id'];
            $tmp['title'] = $val['title'];
            //取二级分类
            $secondList = $categoryObj->get_list(' parent_id = '.$val['id']);
            $tmp['child_total'] = count($secondList);
            foreach($secondList as $val1){
                $tmp1['id'] = $val1['id'];
                $tmp1['title'] = $val1['title'];
                $tmp['child_items'][] = $tmp1;
            }
            $data['items'][] = $tmp;
        }
        echo json_encode($data);
    }
}
new categorylist();