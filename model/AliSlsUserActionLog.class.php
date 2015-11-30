<?php
/*
 * sls日志数据写入、查询
 */

class AliSlsUserActionLog extends AliSls
{
    // project name
    public $PROJECT_ACTION_LOG = 'lemon-action-log';
    // logstore name
    public $LOGSTORE_ACTION = 'action-log';
    
    // action fields
    public $ACTION_COMMENT_ALBUM = 'commentalbum';
    public $ACTION_FAV_ALBUM = 'favalbum';
    public $ACTION_DOWNLOAD_STORY = 'downloadstory';
    public $ACTION_LISTEN_STORY = 'listenstory';
    
    public $ACTION_LIST = array(
            "commentalbum", "favalbum", "downloadstory", "listenstory"
            );
    
    public function __construct()
    {
        parent::__construct($this->PROJECT_ACTION_LOG);
    }
    
    
    /**
     * 统计单小时收藏量
     * @param S $day    "2015-01-01"
     * @param S $hour   "03"
     */
    public function favAlbumNumHour($day, $hour)
    {
        $starttime = strtotime("{$day} {$hour}:00:00");
        $endtime = strtotime("{$day} {$hour}:59:59");
        $topic = "";
        $query = "action:{$this->ACTION_FAV_ALBUM}";
        $res = $this->getActionLogCount($starttime, $endtime, $topic, $query);
        return $res;
    }
    
    
    
    /**
     * 统计单日评论总量、被评论主题数、评论人数
     * @param S $day
     * @return array    
     */
    /* public function commentDayToUserTopicCount($day)
    {
        set_time_limit(0);
        $starttime = strtotime("{$day} 00:00:00");
        $endtime = strtotime("{$day} 23:59:59");
        $topic = "";
        $query = "action:{$this->ACTION_COMMENT_ALBUM}";
        $count = $this->getActionLogCount($starttime, $endtime, $topic, $query);
        
        $topiccount = 0;
        $list = array();
        $actionuids = array();
        $topicids = array();
        if (!empty($count)) {
            $line = 100;
            for ($offset = 0; $offset <= $count; $offset += $line) {
                $list = $this->getActionLogList($starttime, $endtime, $topic, $query, $line, $offset);
                if (!empty($list)) {
                    foreach ($list as $value) {
                        $actionuids[] = $value['contents']['actionuid'];
                        $topicids[] = $value['contents']['beactionid'];
                    }
                    $actionuids = array_unique($actionuids);
                    $topicids = array_unique($topicids);
                }
            }
            if (!empty($actionuids)) {
                $usercount = count($actionuids);
            }
            if (!empty($topicids)) {
                $topiccount = count($topicids);
            }
        }
        return array("commentcount" => $count, "topiccount" => $topiccount, "usercount" => $usercount);
    } */
    
    
    // 添加评论专辑log
    public function addCommentAlbumActionLog($uimid, $uid, $commentid, $albumid, $content, $ip, $addtime)
    {
        return $this->putActionLog($uimid, $uid, $this->ACTION_COMMENT_ALBUM, $commentid, $albumid, "", $content, $ip, $addtime);
    }
    // 添加收藏专辑log
    public function addFavAlbumActionLog($uimid, $uid, $favid, $albumid, $ip, $addtime)
    {
        return $this->putActionLog($uimid, $uid, $this->ACTION_FAV_ALBUM, $favid, $albumid, "", "", $ip, $addtime);
    }
    // 添加收听故事Log
    public function addListenStoryActionLog($uimid, $uid, $listenid, $storyid, $albumid, $ip, $addtime)
    {
        $res = $this->putActionLog($uimid, $uid, $this->ACTION_LISTEN_STORY, $listenid, $storyid, $albumid, "", $ip, $addtime);
        $filepath = '/alidata1/www/logs/listenstorysls.log';
        $fp = @fopen($filepath, 'a+');
        @fwrite($fp, "uimid=>{$uimid}##uid=>{$uid}##listenid={$listenid}##storyid={$storyid}##albumid={$albumid}##ip={$ip}##addtime={$addtime}##res=" . serialize($res) . "\n");
        @fclose($fp);
        return $res;
    }
    // 添加下载故事Log
    public function addDownloadStoryActionLog($uimid, $uid, $downloadid, $storyid, $albumid, $ip, $addtime)
    {
        return $this->putActionLog($uimid, $uid, $this->ACTION_DOWNLOAD_STORY, $downloadid, $storyid, $albumid, "", $ip, $addtime);
    }
    
    
    /**
     * 写入action-log
     * column
     *     actionuimid  行为uid与设备关联id
     *     actionuid    行为发起者uid
     *     action       行为类型：如comment
     *     actionid     行为产生的自增id
     *     beactionid   行为被操作的对象Id
     *     routeid      行为被操作对象的所属对象id
     *     content      行为内容
     *     ip           客户端ip
     *     addtime      行为发生的时间datetime
     *     ext1
     *     ext2
     *     ext3
     *     
     * commentalbum:
     *     value: empty|uid|commentalbum|commentid|albumid|empty|content|ip|addtime
     * favalbum:
     *     value: empty|uid|favalbum|favid|albumid|empty|empty|ip|addtime
     * listenstory
     *     value: uimid|uid|listenstory|listenid|storyid|albumid|empty|ip|addtime
     * downloadstory
     *     value: uimid|uid|downloadstory|downloadid|storyid|albumid|empty|ip|addtime
     * 
     */
    private function putActionLog(
            $actionuimid = "", $actionuid = "", $action, $actionid = "", $beactionid = "", $routeid = "", $content = "", $ip = "", $addtime = "",
            $ext1 = "", $ext2 = "", $ext3 = "")
    {
        if (empty($actionuimid) && empty($actionuid)) {
            return false;
        }
        if (empty($action) || !in_array($action, $this->ACTION_LIST)) {
            return false;
        }
        
        $logcontents = array(
                "actionuimid" => $actionuimid,
                "actionuid" => $actionuid,
                "action" => $action,
                "actionid" => $actionid,
                "beactionid" => $beactionid,
                "routeid" => $routeid,
                "content" => $content,
                "ip" => $ip,
                "addtime" => $addtime,
                "ext1" => $ext1,
                "ext2" => $ext2,
                "ext3" => $ext3
                );
        $res = $this->putLogs($this->LOGSTORE_ACTION, $logcontents);
        return $res;
    }
    
    
    /**
     * 获取指定条件的日志总数
     */
    private function getActionLogCount($starttime, $endtime, $topic, $query)
    {
        if (empty($starttime) || empty($endtime)) {
            return false;
        }
        
        $reslist = $this->getLogCount($this->LOGSTORE_ACTION, $starttime, $endtime, $topic, $query);
        if (empty($reslist)) {
            return false;
        }
        
        $iscompleted = $reslist['iscompleted'];
        if ($iscompleted == true) {
            return $reslist['totalcount'];
        } else {
            return false;
        }
    }
    
    /**
     * 获取指定条件的日志列表
     */
    private function getActionLogList($starttime, $endtime, $topic, $query, $line, $offset = 0, $revert = false)
    {
        if (empty($starttime) || empty($endtime) || empty($line)) {
            return array();
        }
        
        $reslist = $this->getLogList($this->LOGSTORE_ACTION, $starttime, $endtime, $topic, $query, $line, $offset, $revert);
        if (empty($reslist)) {
            return array();
        }
        
        $iscompleted = $reslist['iscompleted'];
        if ($iscompleted == true) {
            return $reslist['list'];
        } else {
            return array();
        }
    }
}