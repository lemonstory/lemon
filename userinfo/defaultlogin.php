<?php
include_once '../controller.php';
class defaultlogin extends controller 
{
    function action() {
        $addtime = date('Y-m-d H:i:s');
        $db = DbConnecter::connectMysql("share_main");
        
        $qquserpasword = md5("11223344");
        $sql = "insert into passport (username,password,addtime) values (?,?,?)";
        $st = $db->prepare($sql);
        $st->execute(array('18701515649', $qquserpasword, $addtime));
        $uid = $db->lastInsertId() + 0;
        if ($uid == 0) {
            echo "@@@";
            return false;
        }
        $nickName = "Lemon";
        $UserObj = new User();
        $UserObj->initQQLoginUser($uid, $nickName, 0, $UserObj->TYPE_PH, $addtime);
        
        $ssoobj = new Sso();
        $ssoobj->setSsoCookie(array('uid' => $uid, 'pasword' => $qquserpasword), array('nickname' => $nickName));
        
        die();
        
        $userobj = new User();
        $userinfo = $userobj->getUserInfo($uid);
        
        // 返回成功json
        $this->showSuccJson($userinfo);
    }
}
new defaultlogin();