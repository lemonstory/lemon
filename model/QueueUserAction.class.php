<?php
/**
 * 用户行为log记录
 * @author Huqq
 *
 */
class QueueUserAction extends QueueManager
{
    public static $implodeChar = "@@";
    
    public static function pushUserActionTopic($uid, $topicId, $addTime, $ext1 = ' ', $ext2 = ' ', $ext3 = ' ')
    {
        if (empty($uid) || empty($topicId) || empty($addTime)) {
            return false;
        }
        
        $logContent = "publishtopic" . 
            self::$implodeChar . $uid . 
            self::$implodeChar . $topicId . 
            self::$implodeChar . $addTime . 
            self::$implodeChar . $ext1 . 
            self::$implodeChar . $ext2 . 
            self::$implodeChar . $ext3;
        
        self::doPush('useraction', $logContent);
        return true;
    }
    
    public static function pushUserActionComment($uid, $topicId, $commentId, $addTime, $ext1 = ' ', $ext2 = ' ', $ext3 = ' ')
    {
        if (empty($uid) || empty($topicId) || empty($commentId) || empty($addTime)) {
            return false;
        }
        
        $logContent = "publishcomment" . 
            self::$implodeChar . $uid . 
            self::$implodeChar . $topicId .
            self::$implodeChar . $commentId . 
            self::$implodeChar . $addTime . 
            self::$implodeChar . $ext1 . 
            self::$implodeChar . $ext2 . 
            self::$implodeChar . $ext3;
        
        self::doPush('useraction', $logContent);
        return true;
    }
    
    
    public static function popUserActionQueue()
    {
        return self::doPop('useraction');
    }
}