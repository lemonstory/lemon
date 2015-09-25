<?php
/**
 * 生成僵尸用户
 */
include_once '../controller.php';
class addsystemuser extends controller
{
    public function action()
    {
        die();
        
        $NicknameMd5Obj = new NicknameMd5();
        $UserObj = new User();
        $manageobj = new ManageSystemUser();
        
        $qquserpasword = md5('SYS' . time());
        $type = $UserObj->TYPE_SYS;
        $avatartime = time();
        
        $addtime = date('Y-m-d H:i:s');
        
        
        $db = DbConnecter::connectMysql("share_main");
        
        for ($i = 1; $i < 100; $i++) {
            $nickName = "萌萌_" . $i;
            
            $sql = "insert into passport (username,password,addtime) values (?,?,?)";
            $st = $db->prepare($sql);
            $st->execute(array('SYS', $qquserpasword, $addtime));
            $uid = $db->lastInsertId() + 0;
            if ($uid == 0) {
                continue;
            }
            
            $res = $NicknameMd5Obj->addOne($nickName, $uid);
            
            $initres = $UserObj->initUser($uid, $nickName, $avatartime, "", 1, "", "", $type, $addtime);
            
            $manageobj->addSystemUserInfo($uid);
        }
        echo 'success';
    }
}
new addsystemuser();