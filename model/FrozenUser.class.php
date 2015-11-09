<?php
// 冻结屏蔽
class FrozenUser extends ModelBase
{
    public $FROZEN_DB_INSTANCE = 'share_manage';
    public $FROZEN_TABLE_NAME = 'frozen_user';
    public $FROZEN_LOG_TABLE_NAME = 'frozen_user_log';
    
    public $FROZENUSER_REASONS = array(
        'sqnr' => '色情内容',
        'zzyl' => '政治言论',
        'ggnr' => '广告内容',
        'rsgj' => '人身攻击',
        'eysp' => '恶意刷屏',
        'rlgz' => '违反用户协议',
    );
    
    public $FROZENUSER_STATUS_LIST = array(1, 2);
    public $FROZENUSER_STATUS_FROZEN = 1; // 已冻结状态
    public $FROZENUSER_STATUS_UNFROZEN = 2; // 解冻状态
    
    public function addFrozen($uid, $duration=-1, $reasontype='', $admininput='')
    {
        if (empty($uid) || empty($duration)){
            $this->setError(ErrorConf::paramError());
            return false;
        }
        
        $userObj = new User();
        $userinfo = current($userObj->getUserInfo($uid));
    
        $data = array('status'=>$this->OPTION_STATUS_FROZEN);
        $ret = $userObj->setUserinfo($uid, $data);
        if (empty($ret)){
            return false;
        }
        $status = $this->FROZENUSER_STATUS_FROZEN;
        $addtime = date('Y-m-d H:i:s');
        $db = DbConnecter::connectMysql($this->FROZEN_DB_INSTANCE);
        
        $sql = "replace into {$this->FROZEN_TABLE_NAME}".
                " (uid,duration,reasontype,admininput,status,addtime)".
                " values (?,?,?,?,?,?)";
        $st = $db->prepare ( $sql );
        $ret = $st->execute (array($uid, $duration, $reasontype, $admininput, $status, $addtime));
        if (!$ret){
            return false;
        }
        
        /*$logsql = "insert into {$this->FROZEN_LOG_TABLE_NAME}".
                " (uid,duration,reasontype,admininput,addtime)".
                " values (?,?,?,?,?)";
        $logst = $db->prepare ( $logsql );
        $logret = $logst->execute (array($uid, $duration, $reasontype, $admininput, $addtime));
        if (!$logret){
            return false;
        }*/
        
        return true;
    }
    
    
    /**
     * 更新冻结表记录状态
     * @param I $uid
     * @param I $status
     * @return boolean
     */
    public function updateFrozenUserStatus($uid, $status)
    {
        if (empty($uid) || !in_array($status, $this->FROZENUSER_STATUS_LIST)) {
            return false;
        }
        
        $db = DbConnecter::connectMysql($this->FROZEN_DB_INSTANCE);
        $sql = "update {$this->FROZEN_TABLE_NAME} set `status` = ? where `uid` = ?";
        $st = $db->prepare ( $sql );
        $ret = $st->execute (array($status, $uid));
        return $ret;
    }
    
    /**
     * 获取指定时段内，被冻结的用户列表
     * @param S $start
     * @param S $end
     * @param I $length
     * @return array
     */
    /*public function getFrozenUserByTime($start, $end, $length=1000)
    {
        $db = DbConnecter::connectMysql($this->FROZEN_DB_INSTANCE);
        $sql = "select * from {$this->FROZEN_TABLE_NAME}".
                " where status='{$this->FROZENUSER_STATUS_FROZEN}' and addtime between '{$start}' and '{$end}'".
                " limit {$length}";
        $st = $db->prepare ( $sql );
        $st->execute();
        $data = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($data)){
            return array();
        }
        return $data;
    }*/
    
    
    /**
     * 获取用户还有多长时间被解冻
     * @param I $uid
     * @return number
     */
    public function getUserUnfrozenTime($uid)
    {
        if (empty($uid)){
            return 0;
        }
        $db = DbConnecter::connectMysql($this->FROZEN_DB_INSTANCE);
        $sql = "select * from {$this->FROZEN_TABLE_NAME}".
                " where uid={$uid}";
        $st = $db->prepare ( $sql );
        $st->execute();
        $data = $st->fetch(PDO::FETCH_ASSOC);
        if (!is_array($data)){
            return 0;
        }
        $frozentime = strtotime($data['addtime']);
        $duration = $data['duration']*3600*24;
        $unfronzentime = $frozentime+$duration;
        return $unfronzentime;
    }
    
    
    /**
     * 批量获取多个用户的冻结log列表
     * @param A $uids
     * @return array
     */
    /*public function getFrozenLogListByUids($uids)
    {
        if (empty($uids)) {
            return array();
        }
        $uidstr = '';
        foreach ($uids as $id) {
            $uidstr .= "'{$id}',";
        }
        $uidstr = rtrim($uidstr, ",");
        
        $db = DbConnecter::connectMysql($this->FROZEN_DB_INSTANCE);
        $sql = "select * from {$this->FROZEN_LOG_TABLE_NAME}".
                " where uid IN ($uidstr)";
        $st = $db->prepare ( $sql );
        $st->execute();
        $result = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($result)) {
            return array();
        }
        return $result;
    }*/
}