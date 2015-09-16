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
        
        $userobj = new User();
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        $defaultbabyid = $userinfo['defaultbabyid'];
        $defaultaddressid = $userinfo['defaultaddressid'];
        
        $userextobj = new UserExtend();
        $babyinfo = current($userextobj->getUserBabyInfo($defaultbabyid));
        if (empty($babyinfo)) {
            $this->showErrorJson(ErrorConf::userBabyInfoEmpty());
        }
        
        $addressinfo = current($userextobj->getUserAddressInfo($defaultaddressid));
        if (empty($addressinfo)) {
            $this->showErrorJson(ErrorConf::userAddressInfoEmpty());
        }
        
        $data = array();
        $aliossobj = new AliOss();
        $data['uid'] = $userinfo['uid'];
        $data['nickname'] = $userinfo['nickname'];
        $data['avatar'] = $aliossobj->getAvatarUrl($uid, $data['avatartime']);
        $data['province'] = $userinfo['province'];
        $data['city'] = $userinfo['city'];
        $data['area'] = $userinfo['area'];
        $data['phonenumber'] = $userinfo['phonenumber'];
        $data['gender'] = $babyinfo['gender'];
        $data['age'] = $babyinfo['age'];
        $data['address'] = $addressinfo['address'];
        
        $this->showSuccJson($data);
    }
}
new getuserinfo();