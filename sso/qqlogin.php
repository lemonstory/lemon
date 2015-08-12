<?php
include_once '../controller.php';
include_once SERVER_ROOT . "libs/qqlogin/qqConnectAPI.php";
class qqlogin extends controller
{
    public function action() 
    {
        $accessToken = $this->getRequest('accessToken', '');
        $openId = $this->getRequest('openId', '');
        
        if ($accessToken == "" || $openId == "") {
            $this->showErrorJson();
        }
        
        $SsoObj = new Sso();
        $isfirst = $SsoObj->checkQqLoginFirst($openId);
        if ($isfirst === true) {
            $errorInfo = ErrorConf::qqAuthInfoEmpty();
            $this->showErrorJson($errorInfo);
        } else {
            $userinfo = $SsoObj->qqlogin($accessToken, $openId);
        }
        
        $this->showSuccJson($userinfo);
    }
}
new qqlogin();