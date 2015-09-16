<?php
include_once '../controller.php';
class addaddress extends controller
{
    public function action() 
    {
        $uid = $this->getUid();
        $name = $this->getRequest("name");
        $phonenumber = $this->getRequest("phonenumber");
        $province = $this->getRequest("province");
        $city = $this->getRequest("city");
        $area = $this->getRequest("area");
        $address = $this->getRequest("address");
        $ecode = $this->getRequest("ecode");
        if (empty($name) || empty($phonenumber) || empty($province) || empty($city) || empty($area) || empty($address) || empty($ecode)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        if (strlen($phonenumber) != 11) {
            $this->showErrorJson(ErrorConf::phoneNumberIsIllegal());
        }
        if (!empty($phonenumber)) {
            $phonenumber = str_replace(",", "", strip_tags(trim($phonenumber)));
        }
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $extendobj = new UserExtend();
        $lastaddressid = $extendobj->addUserAddressInfo($uid, $name, $phonenumber, $province, $city, $area, $address, $ecode);
        if (empty($lastaddressid)) {
            $this->showErrorJson(ErrorConf::userAddAddressFail());
        }
        
        $data = array(
                'addressid' => $lastaddressid,
                'name' => $name,
                'phonenumber' => $phonenumber,
                'province' => $province,
                'city' => $city,
                'area' => $area,
                'address' => $address,
                'ecode' => $ecode
                );
        $this->showSuccJson($data);
    }
}
new addaddress();