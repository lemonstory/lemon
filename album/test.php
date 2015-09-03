<?php

include_once '../controller.php';
class test extends controller
{
    function action() {
    	$album = new Album();
        $album_list = $album->getListByIds(array(1,2,3), 1);
        var_dump($album_list);
    }
}
new test();

