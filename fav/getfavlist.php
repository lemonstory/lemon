<?php
/*
 * 用户收藏列表
 */
include_once '../controller.php';
class getfavlist extends controller 
{
    public function action() 
    {
        $direction = $this->getRequest("direction", "down");
        $startid = $this->getRequest("startid", 0);
        $len = $this->getRequest("len", 20);
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $albumids = array();
        $favobj = new Fav();
        $favlist = $favobj->getUserFavList($uid, $direction, $startid, $len);
        if (!empty($favlist)) {
            $albumids = array();
            foreach ($favlist as $value) {
                $albumids[] = $value['albumid'];
            }
        }
        
        $data = array();
        if (!empty($albumids)) {
            $albumids = array_unique($albumids);
            // 批量获取专辑信息
            $albumobj = new Album();
            $albumlist = $albumobj->getListByIds($albumids);
            
            foreach ($favlist as $value) {
                $albumid = $value['albumid'];
                if (!empty($albumlist[$albumid])) {
                    $value['albuminfo'] = $albumlist[$albumid];
                } else {
                    $value['albuminfo'] = array();
                }
                
                $data[] = $value;
            }
        }
        
        $this->showSuccJson($data);
    }
}
new getfavlist();