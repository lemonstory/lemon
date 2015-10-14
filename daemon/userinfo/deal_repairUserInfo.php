<?php
/*
 * 修复用户资料、宝宝资料、地址信息等信息
 */
include_once (dirname(dirname(__FILE__)) . "/DaemonBase.php");
class deal_repairUserInfo extends DaemonBase 
{
    protected $processnum = 1;
    protected function deal() 
    {
        $data = MnsQueueManager::popRepairUserInfo();
        if (empty($data)) {
            sleep(10);
            return true;
        }
        $dataar = explode("@@", $data);
        $uid = $dataar[0];
        $column = $dataar[1];
        $value = $dataar[2];
        if (empty($uid) || empty($column)) {
            return true;
        }
        
        $userobj = new User();
        if ($column == 'defaultbabyid') {
            // repair baby info
            if (empty($value)) {
                $userextobj = new UserExtend();
                $newbabyid = $userextobj->addUserBabyInfo($uid);
            } else {
                $newbabyid = $value;
            }
            $userobj->setUserinfo($uid, array($column => $newbabyid));
        } elseif ($column == 'defaultaddressid') {
            // repair address info
            if (empty($value)) {
                $userextobj = new UserExtend();
                $newaddressid = $userextobj->addUserAddressInfo($uid);
            } else {
                $newaddressid = $value;
            }
            $userobj->setUserinfo($uid, array($column => $newaddressid));
        }
        
    }
    
    protected function checkLogPath() {}

}
new deal_repairUserInfo();