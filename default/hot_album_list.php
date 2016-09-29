<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/27
 * Time: 下午8:11
 */
include_once '../controller.php';
class hotalbumlist extends controller
{
    public function action()
    {
        $data = array();
        $this->showSuccJson($data);
    }
}
new hotalbumlist();