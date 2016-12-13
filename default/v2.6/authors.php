<?php
/**
 * 列出所有的作者(原著),存储里面将原著,翻译,插画 统称为Creator
 * Date: 16/9/27
 * Time: 上午10:35
 */

include_once '../../controller.php';

class authors extends controller
{
    public function action()
    {
        $page = intval($this->getRequest('p', '1'));
        $page < 1 && $page = 1;
        $len = intval($this->getRequest('len', '50'));
        $ret = array();
        $creator = new Creator();
        $allAuthors = $creator->getAllAuthors($page, $len);
        $ret['total'] = $creator->getTotalCreator();
        $ret['items'] = $allAuthors;
        $this->showSuccJson($ret);
        echo json_encode($ret);
    }
}

new authors();