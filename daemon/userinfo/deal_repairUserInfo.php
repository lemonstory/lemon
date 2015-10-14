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
        $userinfo = current($userobj->getUserInfo($uid));
        if (empty($userinfo)) {
            return true;
        }
        
        if ($column == 'defaultbabyid') {
            // repair baby info
            $defaultbabyid = $userinfo['defaultbabyid'];
            if (empty($defaultbabyid)) {
                $userextobj = new UserExtend();
                $newbabyid = $userextobj->addUserBabyInfo($uid);
                $userobj->setUserinfo($uid, array($column => $newbabyid));
            }
        } elseif ($column == 'defaultaddressid') {
            // repair address info
            $defaultaddressid = $userinfo['defaultaddressid'];
            if (empty($defaultaddressid)) {
                $userextobj = new UserExtend();
                $newaddressid = $userextobj->addUserAddressInfo($uid);
                $userobj->setUserinfo($uid, array($column => $newaddressid));
            }
        }
        
    }
    
    protected function checkLogPath() {}

}
new deal_repairUserInfo();