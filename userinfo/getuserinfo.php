<?php
include_once '../controller.php';
class getuserinfo extends controller 
{
    function action() {
        // 获取当前登录用户uid
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $userobj = new User();
        $userinfo = $userobj->getUserInfo($uid);
        
        // 返回成功json
        $this->showSuccJson($userinfo);
    }
}
new getuserinfo();