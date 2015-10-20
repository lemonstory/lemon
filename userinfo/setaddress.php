<?php
include_once '../controller.php';
class setaddress extends controller 
{
    public function action()
    {
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        $addressid = $this->getRequest('addressid');
        $name = $this->getRequest('name');
        $province = $this->getRequest('province');
        $city = $this->getRequest('city');
        $area = $this->getRequest('area');
        $phonenumber = $this->getRequest('phonenumber');
        $address = $this->getRequest('address');
        $ecode = $this->getRequest('ecode');
        if (empty($addressid) || empty($name) || empty($province) || empty($city) || empty($phonenumber) || empty($address)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        $UserObj = new User();
        $userinfo = current($UserObj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $data = array();
        if (!empty($name)) {
            $data['name'] = str_replace(",", "", strip_tags(trim($name)));
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
        if (!empty($address)) {
            $data['address'] = $address;
        }
        if (!empty($ecode)) {
            $data['ecode'] = $ecode;
        }
        
        if (!empty($data)) {
            $userextendobj = new UserExtend();
            $result = $userextendobj->updateUserAddressInfo($addressid, $uid, $data);
            if ($result === false) {
                $this->showErrorJson($UserObj->getError());
            }
        }
        
        $this->showSuccJson($data);
    }
}
new setaddress();