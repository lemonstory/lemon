<?php
include_once '../controller.php';
class logout extends controller
{
	function action() 
	{
		$SsoObj = new Sso();
		$SsoObj->logout();
		$this->showSuccJson();
	}
}
new logout();