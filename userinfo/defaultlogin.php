<?php
include_once '../controller.php';
class defaultlogin extends controller 
{
    function action() {
        $username = $this->getRequest("username");
        $password = $this->getRequest("password");
        if (empty($username) || empty($password)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        $ssoobj = new Sso();
        $userinfo = $ssoobj->phonelogin($username, $password);
        if($userinfo === false) {
            $this->showErrorJson($ssoobj->getError());
        }
        
        // 返回成功json
        $this->showSuccJson($userinfo);
    }
}
new defaultlogin();