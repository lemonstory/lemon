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
        
        $db = DbConnecter::connectMysql('share_main');
        $sql = "select * from passport where username=?";
        $st = $db->prepare ( $sql );
        $st->execute (array($username));
        $passportdata = $st->fetch(PDO::FETCH_ASSOC);
        if(empty($passportdata)) {
            $this->showErrorJson(ErrorConf::userNoExist());
        }
        
        $uid = $passportdata['uid'];
        if($passportdata['password'] != md5($password . strrev(strtotime($passportdata['addtime'])))){
            $this->showErrorJson(array('code'=>'100002','desc'=>'用户名或者密码错误'));
        }
        
        $UserObj = new User();
        $userinfo = current($UserObj->getUserInfo($uid));
        if (!empty($userinfo['status']) && $userinfo['status'] == '-2') {
            $this->showErrorJson(ErrorConf::userForbidenPost());
        }
        
        $ssoobj = new Sso();
        $ssoobj->setSsoCookie($passportdata, $userinfo);
        
        // 返回成功json
        $this->showSuccJson($userinfo);
    }
}
new defaultlogin();