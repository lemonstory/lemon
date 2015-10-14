<?php
include_once '../controller.php';
class setuserinfo extends controller 
{
    public function action() 
    {
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        $nickname = $this->getRequest('nickname');
        $avatartime = $this->getRequest('avatartime');
        $gender = $this->getRequest('gender');
        $birthday = $this->getRequest('birthday');
        $province = $this->getRequest('province');
        $city = $this->getRequest('city');
        $area = $this->getRequest('area');
        $phonenumber = $this->getRequest('phonenumber');
        $defaultbabyid = $this->getRequest('defaultbabyid');
        $defaultaddressid = $this->getRequest('defaultaddressid');
        
        $UserObj = new User();
        $userinfo = current($UserObj->getUserInfo($uid));
        if (! empty($userinfo['status']) && $userinfo['status'] < 0) {
            if ($userinfo['status'] == '-1') {
                $this->showErrorJson(ErrorConf::userFreezePost($uid));
            } elseif ($userinfo['status'] == '-2') {
                $this->showErrorJson(ErrorConf::userForbidenPost());
            }
        }
        
        $data = $babydata = array();
        if (!empty($nickname)) {
            $data['nickname'] = str_replace(",", "", strip_tags(trim($nickname)));
        }
        if(!empty($avatartime)) {
            $data['avatartime'] = time();
        }
        if (!empty($province)) {
            $data['province'] = $province;
        }
        if (!empty($city)) {
            $data['city'] = $city;
        }
        if (!empty($area)) {
            $data['area'] = $area;
        }
        if (!empty($phonenumber)) {
            $data['phonenumber'] = str_replace(",", "", strip_tags(trim($phonenumber)));
        }
        if (!empty($defaultbabyid)) {
            $data['defaultbabyid'] = $defaultbabyid;
        }
        if (!empty($defaultaddressid)) {
            $data['defaultaddressid'] = $defaultaddressid;
        }
        if (!empty($gender)) {
            $babydata['gender'] = $gender;
        }
        if (!empty($birthday)) {
            $babydata['birthday'] = $birthday;
        }
        
        if (!empty($data)) {
            $result = $UserObj->setUserinfo($uid, $data);
            if ($result === false) {
                $this->showErrorJson($UserObj->getError());
            }
        }
        if (!empty($babydata)) {
            $babyid = $userinfo['defaultbabyid'];
            $userextobj = new UserExtend();
            $babyres = $userextobj->updateUserBabyInfo($babyid, $babydata);
            if ($babyres === false) {
                $this->showErrorJson($userextobj->getError());
            }
        }
        
        $data = array_merge($data, $babydata);
        $this->showSuccJson($data);
    }
}
new setuserinfo();