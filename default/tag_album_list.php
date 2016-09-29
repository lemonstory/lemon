<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/29
 * Time: 上午10:55
 */
include_once '../controller.php';
class tagalbumlist extends controller
{
    public function action()
    {
        $data = array();
        $this->showSuccJson($data);
    }
}
new tagalbumlist();