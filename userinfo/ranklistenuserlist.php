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
        
        $userranknum = 0;
        $userrankuptime = 0;
        $listenobj = new Listen();
        $ranklist = $listenobj->getRankListUserListen($len);
        if (!empty($uid)) {
            $ranknum = $listenobj->getUserListenRankNum($uid);
            if (!empty($ranknum)) {
                $userranknum = $ranknum['userranknum'];
                $userrankuptime = $ranknum['userrankuptime'];
            }
        }
        
        $list = array();
        $uids = array();
        if (!empty($ranklist)) {
            foreach ($ranklist as $uid => $listennum) {
                $uids[] = $uid;
            }
            
            // 批量获取用户信息
            $userobj = new User();
            $userlist = $userobj->getUserInfo($uids);
            if (empty($userlist)) {
                $this->showErrorJson(ErrorConf::userNoExist());
            }
            
            foreach ($ranklist as $uid => $listennum) {
                if (!empty($userlist[$uid])) {
                    $info = $userlist[$uid];
                    $info['listennum'] = $listennum;
                    $list[] = $info;
                }
            }
        }
        
        $data = array("list" => $list, "userranknum" => $userranknum, "userrankuptime" => $userrankuptime);
        $this->showSuccJson($data);
    }
}
new ranklistenuserlist();