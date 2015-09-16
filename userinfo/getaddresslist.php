<?php
include_once '../controller.php';
class getaddresslist extends controller
{
    public function action() 
    {
        $uid = $this->getUid();
        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        $extendobj = new UserExtend();
        $addresslist = $extendobj->getUserAddressList($uid);
        
        $this->showSuccJson($addresslist);
    }
}
new getaddresslist();