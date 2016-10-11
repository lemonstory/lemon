<?php
/**
 * 列出所有的主播
 *
 * Date: 16/9/30
 * Time: 下午6:31
 */

include_once '../../controller.php';

class anchors extends controller
{
    public function action()
    {
        $startAnchorId = intval($this->getRequest('start_anchor_id', '0'));
        $len = intval($this->getRequest('len', '10000'));
        $ret = array();
        $creator = new Creator();
        $allAnchors = $creator->getAllAnchors($startAnchorId, $len);
        $ret['total'] = count($allAnchors);
        $ret['items'] = $allAnchors;
        $this->showSuccJson($ret);
        echo json_encode($ret);
    }
}

new anchors();