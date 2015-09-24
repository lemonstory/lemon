<?php
/**
 * 用户登录的log记录，包括设备信息
 * 用于查询用户登录过的设备信息，或某个设备上登录过的用户信息
 * @author Huqq
 *
 */
class UserLoginLog extends ModelBase
{
    public $MAIN_DB_INSTANCE = 'share_main';
    public $TABLE_NAME_LOGIN_LOG = 'user_login_log';
    
    /**
     * 添加登录日志
     * @param I $uid
     * @param S $imsi
     * @return boolean
     */
    public function addUserLoginLog($uid, $imsi = "")
    {
        if (empty($uid)) {
            return false;
        }
        
        $addtime = date("Y-m-d H:i:s");
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $sql = "INSERT INTO `{$this->TABLE_NAME_LOGIN_LOG}` (`uid`, `imsi`, `addtime`) VALUES (?, ?, ?)";
        $st = $db->prepare($sql);
        $res = $st->execute(array($uid, $imsi, $addtime));
        if (empty($res)) {
            return false;
        }
        return true;
    }
    
    
}