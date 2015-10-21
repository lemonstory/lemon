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
        $rankres = $listenobj->getRankListUserListen($len, $uid);
        $ranklist = $rankres['list'];
        $userranknum = $rankres['userranknum'];
        $userrankuptime = $rankres['userrankuptime'];
        
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