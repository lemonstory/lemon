<?php
include_once '../controller.php';
include_once SERVER_ROOT . "libs/qqlogin/qqConnectAPI.php";
class qqloginreg extends controller
{
    public function action()
    {
        $accessToken = $this->getRequest('accessToken', '');
        $openId = $this->getRequest('openId', '');
        $nickName = trim($this->getRequest('nickName', ''));
        if ($accessToken == "" || $openId == "" || $nickName == "") {
            $this->showErrorJson();
        }
        
        $SsoObj = new Sso();
        $isfirst = $SsoObj->checkQqLoginFirst($openId);
        if ($isfirst === true) {
            $qc = new QC($accessToken, $openId);
            $userinfo = $SsoObj->initQqLoginUser($qc, $accessToken, $openId, $nickName);
            if (empty($userinfo)) {
                $this->showErrorJson($SsoObj->getError());
            }
        } else {
            $userinfo = $SsoObj->qqlogin($accessToken, $openId);
        }
        $this->showSuccJson($userinfo);
    }

}
new qqloginreg();