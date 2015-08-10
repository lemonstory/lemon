<?php
include_once '../controller.php';
include_once SERVER_ROOT."libs/qqlogin/qqConnectAPI.php";
class qqloginreg extends controller
{

	function action() {
	
		$accessToken = $this->getRequest('accessToken', '');
		$openId      = $this->getRequest('openId', '');
		$nickName      = trim($this->getRequest('nickName', ''));
		
		if($accessToken=="" || $openId=="" || $nickName=="")
		{
			$this->showErrorJson();
		}
		if ($openId == 'EE261EAD297BE0E6B95C214EA3ACAC9D' && $accessToken == '3B4672E1FC1CF0D165CC5F853081ADE9') {
		    // 测试QQ账号不注册
		    $this->showSuccJson();
		}
		
		$SsoObj = new Sso();
		$isfirst = $SsoObj->checkQqLoginFirst($openId);
		if($isfirst===true)
		{
		    $qc = new QC($accessToken, $openId);
			$userinfo = $SsoObj->initQqLoginUser($qc, $accessToken, $openId, $nickName);
			if (empty($userinfo)) {
			    $this->showErrorJson($SsoObj->getError());
			}
		}else{
			$userinfo = $SsoObj->qqlogin($accessToken, $openId);
		}
		if (!empty($userinfo['uid'])) {
    		$ryObj = new MessageRongyun();
    		$userinfo['rctoken'] = $ryObj->getToken($userinfo['uid']);
		}
		$this->showSuccJson($userinfo);
	}

	
}
new qqloginreg();