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