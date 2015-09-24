<?php
class UserImsi extends ModelBase
{
    public $PASSPORT_DB_INSTANCE = 'share_main';
    public $USER_IMSI_INFO_TABLE_NAME = 'user_imsi_info';
    
    public $USER_IMSI_INFO_RESTYPE_UID = 1;
    public $USER_IMSI_INFO_RESTYPE_IMSI = 2;
    
    /**
     * 获取uid或者设备号的uimid
     */
    public function getUimid($uid = 0)
    {
        if (empty($uid)) {
            $ssoobj = new Sso();
            $uid = $ssoobj->getUid();
        }
        
        if (empty($uid)) {
            $imsi = getImsi();
            if (empty($imsi)) {
                return 0;
            }
    
            $uiminfo = $this->getUserImsiInfo($imsi, $this->USER_IMSI_INFO_RESTYPE_IMSI);
            if (empty($uiminfo)) {
                $uimid = $this->addUserImsiInfo($imsi, $this->USER_IMSI_INFO_RESTYPE_IMSI);
            } else {
                $uimid = $uiminfo['uimid'];
            }
        } else {
            $uiminfo = $this->getUserImsiInfo($uid, $this->USER_IMSI_INFO_RESTYPE_UID);
            if (empty($uiminfo)) {
                $uimid = $this->addUserImsiInfo($uid, $this->USER_IMSI_INFO_RESTYPE_UID);
            } else {
                $uimid = $uiminfo['uimid'];
            }
        }
        
        return $uimid;
    }
    
    
    public function getUserImsiInfoByUimid($uimid)
    {
        if (empty($uimid)) {
            return array();
        }
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        $sql = "select * from {$this->USER_IMSI_INFO_TABLE_NAME} where `uimid` = ?";
        $st = $db->prepare ( $sql );
        $st->execute (array($uimid));
        $list = $st->fetch( PDO::FETCH_ASSOC );
        if (empty($list)) {
            return array();
        }
        return $list;
    }
    
    
    /**
     * 获取指定imsi的最近登录账户的关联记录
     * @param S $resid
     * @param I $restype    1为uid, 2为imsi
     * @return array
     */
    public function getUserImsiInfo($resid, $restype)
    {
        if (empty($resid) || !in_array($restype, array($this->USER_IMSI_INFO_RESTYPE_UID, $this->USER_IMSI_INFO_RESTYPE_IMSI))) {
            return array();
        }
         
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        $sql = "select * from {$this->USER_IMSI_INFO_TABLE_NAME} where `resid` = ? and `restype` = ?";
        $st = $db->prepare ( $sql );
        $st->execute (array($resid, $restype));
        $list = $st->fetch( PDO::FETCH_ASSOC );
        if (empty($list)) {
            return array();
        }
        return $list;
    }
    
    
    /**
     * 添加用户的imsi或uid的唯一记录
     * @param S $resid       手机设备唯一标识或uid
     * @param I $restype     1为uid, 2为imsi
     * @return boolean
     */
    public function addUserImsiInfo($resid, $restype)
    {
        if (empty($resid) || !in_array($restype, array($this->USER_IMSI_INFO_RESTYPE_UID, $this->USER_IMSI_INFO_RESTYPE_IMSI))) {
            return false;
        }
         
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        $sql = "INSERT INTO `{$this->USER_IMSI_INFO_TABLE_NAME}` (`resid`, `restype`) VALUES (?, ?)";
        $st = $db->prepare ( $sql );
        $res = $st->execute (array($resid, $restype));
        if (empty($res)) {
            return false;
        }
        $uimid = $db->lastInsertId() + 0;
        return $uimid;
    }
}