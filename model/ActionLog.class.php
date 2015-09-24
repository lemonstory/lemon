<?php
/**
 * 用户或设备的行为日志
 * @author Huqq
 *
 */
class ActionLog extends ModelBase
{
    public $MAIN_DB_INSTANCE = 'share_main';
    public $ACTION_LOG_TABLE_NAME = 'user_imsi_action_log';
    
    public $ACTION_TYPE_LOGIN = 'login'; // 登录
    
    
    
    public function addUserImsiActionLog($uimid, $actionid, $actiontype, $addtime = "")
    {
        if (empty($uimid) || empty($actionid) || empty($actiontype)) {
            return false;
        }
        if (empty($addtime)) {
            $addtime = date("Y-m-d H:i:s");
        }
        
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $sql = "INSERT INTO `{$this->ACTION_LOG_TABLE_NAME}` (`uimid`, `actionid`, `actiontype`, `addtime`) VALUES (?, ?, ?, ?)";
        $st = $db->prepare($sql);
        $res = $st->execute(array($uimid, $actionid, $actiontype, $addtime));
        if (empty($res)) {
            return false;
        }
        return true;
    }
}