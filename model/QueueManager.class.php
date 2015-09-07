<?php
class QueueManager
{
    public static $QUEUE_INSTANCE = 'queue';
    // 收听播放故事
    public static function pushListenStoryQueue($uid, $storyid)
    {
        $key = RedisKey::getUserListenStoryQueueKey();
        $redisobj = AliRedisConnecter::connRedis(self::$QUEUE_INSTANCE);
        $redisobj->lpush($key, $uid . ":" . $storyid);
        return true;
    }
    public static function popListenStoryQueue()
    {
        $key = RedisKey::getUserListenStoryQueueKey();
        $redisobj = AliRedisConnecter::connRedis(self::$QUEUE_INSTANCE);
        return $redisobj->rpop($key);
    }
}
?>