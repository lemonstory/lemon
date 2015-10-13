<?php
/*
 * 收听次数的用户排行榜
 */
include_once '../controller.php';
class ranklistenuserlist extends controller 
{
    public function action() 
    {
        $len = $this->getRequest("len", 20);
        $uid = $this->getUid();
        if (empty($uid)) {
        	$this->showErrorJson(ErrorConf::noLogin());
        }
        
        $listenobj = new Listen();
        $ranklist = $listenobj->getRankListUserListen($len);
        if (empty($ranklist)) {
            $this->showErrorJson(ErrorConf::rankListenUserListIsEmpty());
        }
        
        $uids = array();
        foreach ($ranklist as $value) {
            $uids[] = $value['uid'];
        }
        
        // 批量获取用户信息
        $userobj = new User();
        $userlist = $userobj->getUserInfo($uids);
        if (empty($userlist)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $list = array();
        foreach ($ranklist as $value) {
            $uid = $value['uid'];
            if (!empty($userlist[$uid])) {
                $info = $userlist[$uid];
                $info['listennum'] = $value['num'];
                $list[] = $info;
            }
        }
        
        $this->showSuccJson($list);
    }
}
new ranklistenuserlist();