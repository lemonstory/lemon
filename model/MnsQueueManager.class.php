<?php
include_once SERVER_ROOT . 'libs/mqs.sdk.class.php';
include_once SERVER_ROOT . 'libs/array2xml.lib.class.php';
include_once SERVER_ROOT . 'libs/xml2array.lib.class.php';
class MnsQueueManager 
{
    /**
     * 登录uid或未登录的设备imsi号，的行为日志记录
     * @param I $uimid        用户uid或设备imsi
     * @param S $actionid     行为关联id：如uid
     * @param I $actiontype   行为类型:英文字母
     * @return boolean
     */
    public static function pushActionLogQueue($uimid, $actionid, $actiontype)
    {
        $addtime = date("Y-m-d H:i:s");
        $res = self::doPush('lemon-actionlogqueue', $uimid . "@@" . $actionid . "@@" . $actiontype . "@@" . $addtime);
        return true;
    }
    public static function popActionLogQueue() 
    {
        return self::doPop('lemon-actionlogqueue');
    }
    
    
    /**
     * 收听播放故事队列
     */
    public static function pushListenStoryQueue($uimid, $storyid, $ip)
    {
        $res = self::doPush("lemon-userlistenstoryqueue", $uimid . "@@" . $storyid . "@@" . $ip);
        return true;
    }
    public static function popListenStoryQueue()
    {
        return self::doPop('lemon-userlistenstoryqueue');
    }
    
    
    /**
     * 添加专辑数据到opensearch
     */
    public static function pushAlbumToSearchQueue($storyid)
    {
        $res = self::doPush("lemon-albumtosearch", $storyid);
        return true;
    }
    public static function popAlbumToSearchQueue()
    {
        return self::doPop('lemon-albumtosearch');
    }
    
    /**
     * 上传QQ联合登录头像
     */
    public static function pushLoadUserQqavatar($uid, $qqavatarurl)
    {
        self::doPush('lemon-userqqavatar', $uid . "@@" . $qqavatarurl);
        return true;
    }
    public static function popLoadUserQqavatar()
    {
        return self::doPop('lemon-userqqavatar');
    }
    
    
    public static function pushRepairUserInfo($uid, $column, $value)
    {
        self::doPush('lemon-repairuserinfo', $uid . "@@" . $column . "@@" . $value);
        return true;
    }
    public static function popRepairUserInfo()
    {
        return self::doPop('lemon-repairuserinfo');
    }
    
    
    
    protected static function doPop($business) 
    {
        $queueobj = self::connectQueue($business);
        $data = $queueobj->receiveMessage();
        $MessageBody = @$data['Message']['MessageBody'];
        if (empty($MessageBody) || ! is_array($data)) {
            date_default_timezone_set('PRC');
            return false;
        }
        $queueobj->dropMessage(array('ReceiptHandle' => $data['Message']['ReceiptHandle']));
        date_default_timezone_set('PRC');
        return $MessageBody;
    }
    
    protected static function doPush($business, $data) {
        if ($data == "") {
            return true;
        }
        $queueobj = self::connectQueue($business);
        $data = array(
                'MessageBody' => $data,
                'DelaySeconds' => 0,
                'Priority' => 8 
        );
        $re = $queueobj->sendMessage($data);
        date_default_timezone_set('PRC');
        return true;
    }
    
    private static function connectQueue($queuename) {
        $mqs = new mqs(array(
                'accessKeyId' => $_SERVER['CONFIG']['mns_accessKeyId'],
                'accessKeySecret' => $_SERVER['CONFIG']['mns_accessKeySecret'],
                'accessOwnerId' => $_SERVER['CONFIG']['mns_accessOwnerId'],
                'accessQueue' => $queuename,
                'accessRegion' => 'cn-hangzhou' 
        ));
        return $mqs;
    }
}