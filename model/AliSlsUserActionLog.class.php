<?php
/*
 * sls日志数据写入、查询
 */

class AliSlsUserActionLog extends AliSls
{
    // project name
    public $PROJECT_USER_ACTION_LOG = 'tutu-user-action-log';
    // logstore name
    public $LOGSTORE_ACTION = 'action-log';
    // action fields
    public $ACTION_LIST = array(
            "comment", "topic", "message", "liketopic", "register", "follow"
            );
    
    public function __construct()
    {
        parent::__construct($this->PROJECT_USER_ACTION_LOG);
    }
    
    
    /**
     * 统计单小时评论发布量
     * @param S $day    "2015-01-01"
     * @param S $hour   "03"
     */
    public function commentHourPublishNum($day, $hour)
    {
        $starttime = strtotime("{$day} {$hour}:00:00");
        $endtime = strtotime("{$day} {$hour}:59:59");
        $topic = "";
        $query = "action:comment";
        $res = $this->getActionLogCount($starttime, $endtime, $topic, $query);
        return $res;
    }
    public function likeTopicHourPublishNum($day, $hour)
    {
        $starttime = strtotime("{$day} {$hour}:00:00");
        $endtime = strtotime("{$day} {$hour}:59:59");
        $topic = "";
        $query = "action:liketopic";
        $res = $this->getActionLogCount($starttime, $endtime, $topic, $query);
        return $res;
    }
    public function topicHourPublishNum($day, $hour)
    {
        $starttime = strtotime("{$day} {$hour}:00:00");
        $endtime = strtotime("{$day} {$hour}:59:59");
        $topic = "";
        $query = "action:topic";
        $res = $this->getActionLogCount($starttime, $endtime, $topic, $query);
        return $res;
    }
    
    
    /**
     * 统计单日评论总量、被评论主题数、评论人数
     * @param S $day
     * @return array    
     */
    public function commentDayToUserTopicCount($day)
    {
        set_time_limit(0);
        $starttime = strtotime("{$day} 00:00:00");
        $endtime = strtotime("{$day} 23:59:59");
        $topic = "";
        $query = "action:comment";
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
    }
    
    /**
     * 统计单日赞主题总量、赞人数、被赞主题数
     */
    public function likeTopicDayToUserTopicCount($day)
    {
        set_time_limit(0);
        $starttime = strtotime("{$day} 00:00:00");
        $endtime = strtotime("{$day} 23:59:59");
        $topic = "";
        $query = "action:liketopic";
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
        return array("likecount" => $count, "topiccount" => $topiccount, "usercount" => $usercount);
    }
    
    /**
     * 统计单日主题发布量、发布主题人数
     */
    public function topicDayToUserCount($day)
    {
        set_time_limit(0);
        $starttime = strtotime("{$day} 00:00:00");
        $endtime = strtotime("{$day} 23:59:59");
        $topic = "";
        $query = "action:topic";
        $count = $this->getActionLogCount($starttime, $endtime, $topic, $query);
        
        $topiccount = 0;
        $list = array();
        $actionuids = array();
        if (!empty($count)) {
            $line = 100;
            for ($offset = 0; $offset <= $count; $offset += $line) {
                $list = $this->getActionLogList($starttime, $endtime, $topic, $query, $line, $offset);
                if (!empty($list)) {
                    foreach ($list as $value) {
                        $actionuids[] = $value['contents']['actionuid'];
                    }
                    $actionuids = array_unique($actionuids);
                }
            }
            if (!empty($actionuids)) {
                $usercount = count($actionuids);
            }
        }
        return array("topiccount" => $count, "usercount" => $usercount);
    }
    
    
    
    
    // 添加主题发布log
    public function addTopicActionLog($uid, $topicid, $content, $ip, $addtime)
    {
        return $this->putActionLog($uid, "topic", $topicid, "", "", $content, $ip, $addtime);
    }
    // 添加评论log
    public function addCommentActionLog($uid, $commentid, $topicid, $replycommentid, $content, $ip, $addtime)
    {
        return $this->putActionLog($uid, "comment", $commentid, $topicid, $replycommentid, $content, $ip, $addtime);
    }
    // 添加赞主题log
    public function addDiggActionLog($uid, $topicid, $topicuid, $ip, $addtime)
    {
        return $this->putActionLog($uid, "liketopic", "", $topicid, $topicuid, "", $ip, $addtime);
    }
    
    /**
     * 写入action-log
     * column
     *     actionuid    行为发起者uid
     *     action       行为类型：如comment
     *     actionid     行为产生的自增id
     *     beactionid   行为被操作的对象Id
     *     routeid      行为被操作对象的所属对象id
     *     content      行为内容
     *     ip           客户端ip
     *     addtime      行为发生的时间
     *     ext1
     *     ext2
     *     ext3
     *     
     * comment:
     *     value: uid|comment|commentid|topicid|replycommentid|content|ip|addtime
     * topic:
     *     value: uid|topic|topicid|empty|empty|imgcontent|ip|addtime
     * message:
     *     value: uid|message|messageid|touid|relationid|content|ip|addtime
     * liketopic:
     *     value: uid|liketopic|empty|topicid|empty|empty|ip|addtime
     * register:
     *     value: uid|regiter|uid|empty|empty|regtime|ip|addtime
     * follow:
     *     value: uid|follow|empty|followuid|empty|empty|ip|addtime
     * 
     * 
     */
    private function putActionLog(
            $actionuid, $action, $actionid = "", $beactionid = "", $routeid = "", $content = "", $ip = "", $addtime = "",
            $ext1 = "", $ext2 = "", $ext3 = "")
    {
        if (empty($actionuid) || empty($action)) {
            return false;
        }
        if (!in_array($action, $this->ACTION_LIST)) {
            return false;
        }
        
        $logcontents = array(
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