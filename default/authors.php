<?php
/**
 * 列出所有的作者(原著),存储里面将原著,翻译,插画 统称为Creator
 * Date: 16/9/27
 * Time: 上午10:35
 */

include_once '../controller.php';

class authors extends controller
{
    public function action()
    {
        $startAuthorId = intval($this->getRequest('start_author_id', '0'));
        $len = intval($this->getRequest('len', '10000'));
        $ret = array();
        $creator = new Creator();
        $allAuthors = $creator->getAllAuthors($startAuthorId, $len);
        $ret['total'] = count($allAuthors);
        $ret['items'] = $allAuthors;
        $this->showSuccJson($ret);
        echo json_encode($ret);
    }
}

new authors();