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
        $userinfo = current($userobj->getUserInfo($uid));
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
        
        $defaultbabyid = $userinfo['defaultbabyid'];
        $defaultaddressid = $userinfo['defaultaddressid'];
        if (empty($defaultbabyid)) {
            MnsQueueManager::pushRepairUserInfo($uid, "defaultbabyid", 0);
        }
        if (empty($defaultaddressid)) {
            MnsQueueManager::pushRepairUserInfo($uid, "defaultaddressid", 0);
        }
        
        $userextobj = new UserExtend();
        $babyinfo = current($userextobj->getUserBabyInfo($defaultbabyid));
        if (empty($babyinfo)) {
            $data['gender'] = 0;
            $data['age'] = 0;
        } else {
            $data['gender'] = $babyinfo['gender'];
            $data['age'] = $babyinfo['age'];
        }
        
        $addressinfo = current($userextobj->getUserAddressInfo($defaultaddressid));
        if (empty($addressinfo)) {
            $data['addressinfo'] = array();
        } else {
            $data['addressinfo'] = $addressinfo;
        }
        
        $this->showSuccJson($data);
    }
}
new getuserinfo();