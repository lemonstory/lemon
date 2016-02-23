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
    
    // 用户登录
    public $ACTION_TYPE_LOGIN = 'login';
    // 收听故事
    public $ACTION_TYPE_LISTEN_STORY = 'listenstory';
    // 收藏专辑
    public $ACTION_TYPE_FAV_ALBUM = 'favalbum';
    // 下载故事
    public $ACTION_TYPE_DOWNLOAD_STORY = 'downloadstory';
    // 下载整个专辑
    //public $ACTION_TYPE_DOWNLOAD_ALBUM = 'downloadalbum';
    // 搜索关键词
    public $ACTION_TYPE_SEARCH_CONTENT = 'searchcontent';
    
    public $ACTION_TYPE_LIST = array(
            "login" => "登录",
            "listenstory" => "收听故事",
            "favalbum" => "收藏故事辑",
            "downloadstory" => "下载故事",
            "searchcontent" => "搜索关键词",
            );
    
    
    /**
     * 获取指定时间内的行为记录
     * @param S $starttime    开始时间，如2016-02-22 10:00:00
     * @param S $endtime
     * @return array
     */
    public function getUserImsiActionLogListByTime($starttime, $endtime)
    {
        if (empty($starttime) || empty($endtime)) {
            return array();
        }
        $month = date("Ym", $starttime);
        $monthtablename = $this->getUserImsiActionLogTableName($month);
        $list = array();
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $sql = "SELECT * FROM `{$monthtablename}` WHERE `addtime` > ? and `addtime` < ?";
        $st = $db->prepare($sql);
        $st->execute(array($starttime, $endtime));
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($list)) {
            return array();
        }
        return $list;
    }
    
    
    /**
     * 添加uimid行为日志记录
     * @param I $uimid
     * @param S $actionid
     * @param S $actiontype
     * @param S $addtime
     * @return boolean
     */
    public function addUserImsiActionLog($uimid, $actionid, $actiontype, $addtime = "")
    {
        if (empty($uimid) || empty($actionid) || empty($actiontype)) {
            return false;
        }
        if (empty($addtime)) {
            $addtime = date("Y-m-d H:i:s");
        }
        $month = date("Ym");
        $monthtablename = $this->getUserImsiActionLogTableName($month);
        
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $sql = "INSERT INTO `{$monthtablename}` (`uimid`, `actionid`, `actiontype`, `addtime`) VALUES (?, ?, ?, ?)";
        $st = $db->prepare($sql);
        $res = $st->execute(array($uimid, $actionid, $actiontype, $addtime));
        if (empty($res)) {
            return false;
        }
        return true;
    }
    
    // $month = 201601
    public function getUserImsiActionLogTableName($month)
    {
        return "user_imsi_action_log_" . $month;
    }
}