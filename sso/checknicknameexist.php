<?php
include_once '../controller.php';
class checknicknameexist extends controller
{
	function action() {
		
		$uid = $this->getUid();
		if($uid==0)
		{
			$this->showErrorJson(ErrorConf::noLogin());
		}
		$nickname    = trim($this->getRequest('nickname'));
		if($nickname=="")
		{
			$this->showErrorJson(ErrorConf::nickNameisEmpty());
		}
		
		$NicknameMd5Obj =  new NicknameMd5();
		$existnicknameuid = $NicknameMd5Obj->checkNameIsExist($nickname);
		
		if($existnicknameuid>0 && $existnicknameuid!=$uid)
		{
			$this->showErrorJson(ErrorConf::nickNameIsExist());
		}
		$this->showSuccJson();
	}
}
new checknicknameexist();