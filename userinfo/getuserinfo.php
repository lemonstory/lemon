<?php
include_once '../controller.php';
class getuserinfo extends controller 
{
    public function action() {
        // 个人资料页
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $data = array();
        $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid, 1));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $data['uid'] = $userinfo['uid'];
        $data['nickname'] = $userinfo['nickname'];
        $data['avatartime'] = $userinfo['avatartime'];
        $data['province'] = $userinfo['province'];
        $data['city'] = $userinfo['city'];
        $data['area'] = $userinfo['area'];
        $data['phonenumber'] = $userinfo['phonenumber'];
        $data['birthday'] = $userinfo['birthday'];
        $data['gender'] = $userinfo['gender'];
        $data['age'] = $userinfo['age'];
        
        $defaultaddressid = $userinfo['defaultaddressid'];
        if (empty($defaultaddressid)) {
            MnsQueueManager::pushRepairUserInfo($uid, "defaultaddressid", 0);
        }
        
        $userextobj = new UserExtend();
        $addressinfo = current($userextobj->getUserAddressInfo($defaultaddressid));
        if (empty($addressinfo)) {
            $data['addressinfo'] = (object)array();
        } else {
            $data['addressinfo'] = $addressinfo;
        }
        
        $this->showSuccJson($data);
    }
}
new getuserinfo();