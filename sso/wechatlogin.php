<?php
include_once '../controller.php';

class wechatlogin extends controller
{
    public function action()
    {
        $accessToken = $this->getRequest('accessToken', '');
        $openId = $this->getRequest('openId', '');
        if (empty($accessToken) || empty($openId)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
    	$wechatobj = new WechatSso();
        $isfirst = $wechatobj->checkWechatLoginFirst($openId);
        if ($isfirst === true) {
            $errorInfo = ErrorConf::wechatAuthInfoEmpty();
            $this->showErrorJson($errorInfo);
        } else {
            $userinfo = $wechatobj->wechatLogin($accessToken, $openId);
        }
        $this->showSuccJson($userinfo);
    }
}
new wechatlogin();