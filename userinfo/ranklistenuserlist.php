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
        
        $userobj = new User();
		$userinfo = current($userobj->getUserInfo($uid));
		if (empty($userinfo)) {
			$this->showErrorJson(ErrorConf::userNoExist());
		}
		
		$defaultbabyid = $userinfo['defaultbabyid'];
		$userextinfo = new UserExtend();
		$babyinfo = $userextinfo->getUserBabyInfo($defaultbabyid);
		if (empty($babyinfo)) {
			$this->showErrorJson(ErrorConf::userBabyInfoEmpty());
		}
		$babyagetype = $userextinfo->getBabyAgeType($babyinfo['age']);
		
        $listenobj = new Listen();
        $userlist = $listenobj->getRankListUserListen($babyagetype, 0, $len);
        
        $this->showSuccJson($userlist);
    }
}
new ranklistenuserlist();