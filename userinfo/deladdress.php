<?php
include_once '../controller.php';
class deladdress extends controller 
{
    public function action()
    {
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        $addressid = $this->getRequest('addressid');
        if (empty($addressid)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        $UserObj = new User();
        $userinfo = current($UserObj->getUserInfo($uid));
        if (empty($userinfo)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $userextendobj = new UserExtend();
        $result = $userextendobj->delUserAddressInfo($addressid, $uid);
        if ($result === false) {
            $this->showErrorJson($UserObj->getError());
        }
        
        $this->showSuccJson();
    }
}
new deladdress();