<?php
include_once '../controller.php';
class setavatar extends controller
{
	function action() {
		$uid = $this->getUid();
		if(empty($uid)) {
			$this->showErrorJson(ErrorConf::noLogin());
		}
		
		$UserObj = new User();
		$userinfo = current($UserObj->getUserInfo($uid));
		if (!empty($userinfo['status']) && $userinfo['status'] < 0){
			if ($userinfo['status'] == '-1') {
				$this->showErrorJson(ErrorConf::userFreezePost($uid));
			} elseif ($userinfo['status'] == '-2') {
				$this->showErrorJson(ErrorConf::userForbidenPost());
			}
		}
		
		$UserObj = new User();
		$avatartime = $UserObj->setAvatar($_FILES['avatarfile'], $uid);
		if(empty($avatartime)) {
			$this->showErrorJson($UserObj->getError());
		}
		$this->showSuccJson(array('avatartime' => $avatartime));
	}
}
new setavatar();