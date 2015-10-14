<?php
/*
 * 上传处理QQ联合登录的头像
 */
include_once (dirname(dirname(__FILE__)) . "/DaemonBase.php");
include_once SERVER_ROOT . "libs/qqlogin/qqConnectAPI.php";
class deal_loadUserQqavatar extends DaemonBase 
{
    protected $processnum = 1;
    protected function deal() 
    {
        $data = MnsQueueManager::popLoadUserQqavatar();
        if (empty($data)) {
            sleep(10);
            return true;
        }
        $dataar = explode("@@", $data);
        $uid = $dataar[0];
        $qqavatar = $dataar[1];
        if (empty($uid) || empty($qqavatar)) {
            return true;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $qqavatar);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, "Baiduspider+(+ http://www.baidu.com/search/spider.htm)");
        curl_setopt($ch, CURLOPT_REFERER, $qqavatar);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        curl_close($ch);
        $avatarfile = "/alidata1/tmpavatarfile/{$uid}";
        file_put_contents($avatarfile, $content);
        
        if (is_file($avatarfile)) {
            $obj = new alioss_sdk();
            // $obj->set_debug_mode(FALSE);
            $bucket = 'lemonavatar';
            $responseObj = $obj->upload_file_by_file($bucket, $uid, $avatarfile);
            
            if ($responseObj->status == 200) {
                $avatartime = time();
                $UserObj = new User();
                $UserObj->setUserinfo($uid, array('avatartime' => $avatartime));
            }
        }
        
        $dataline = $uid . "---" . $avatarfile . "\n";
        $filepath = '/alidata1/www/logs/loadqqavatar' . date('Y-m-d') . ".log";
        $fp = @fopen($filepath, 'a+');
        @fwrite($fp, $dataline . "\n");
        @fclose($fp);
    
    }
    
    protected function checkLogPath() {}

}
new deal_loadUserQqavatar();