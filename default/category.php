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
        $tagInfoObj = new TagInfo();
        $data = array();
        //获取一级分类
        $firstList = $tagInfoObj->get_list(' pid = 0 and status=1','id,name','ordernum asc',100);
        $data['total'] = count($firstList);
        foreach($firstList as $key=>$val){
            $tmp['id'] = $val['id'];
            $tmp['name'] = $val['name'];
            //取二级分类
            $secondList = $tagInfoObj->get_list(' pid = '.$val['id'].' and status=1','id,name','ordernum asc',100);
            $tmp['child_total'] = count($secondList);
            $tmp['child_items'] = $secondList;
            $data['items'][] = $tmp;
        }
        echo json_encode(array('code'=>200,'data'=>$data));
    }
}
new categorylist();