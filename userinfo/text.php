<?php
include_once '../controller.php';
class text extends controller 
{
    function action() {
        // 获取当前登录用户uid
        $uid = $this->getUid();
        if ($uid == 0) {
            // 返回错误json
            $this->showErrorJson(ErrorConf::noLogin());
        }
        
        // 接收参数
        $nickname = trim($this->getRequest('nickname'));
        if ($nickname == "") {
            $this->showErrorJson(ErrorConf::nickNameisEmpty());
        }
        
        // 调用类的方法，类方法在model中，底层数据操作在方法中实现
        $aliossobj = new AliOss();
        $res = $aliossobj->xxx($nickname);
        
        // 返回成功json
        $this->showSuccJson();
    }
}
new text();