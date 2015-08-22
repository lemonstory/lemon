<?php
/*
 * 微信SSO授权登录后的注册接口
 */
include_once '../controller.php';
class wechatreg extends controller 
{
    public function action() 
    {
        $accessToken = $this->getRequest('accessToken', '');
        $openId = $this->getRequest('openId', '');
        $nickName = trim($this->getRequest('nickName', ''));
    	if (empty($accessToken) || empty($openId) || empty($nickName)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        $wechatobj = new WechatSso();
        $isfirst = $wechatobj->checkWechatLoginFirst($openId);
        if($isfirst === true) {
            $userinfo = $wechatobj->initWechatLoginUser($accessToken, $openId, $nickName);
        } else {
            $userinfo = $wechatobj->wechatLogin($accessToken, $openId);
        }
        if (empty($userinfo)) {
            $this->showErrorJson($wechatobj->getError());
        }
        
        $this->showSuccJson($userinfo);
    }
}
new wechatreg();