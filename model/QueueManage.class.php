<?php
class QueueManager
{
    // 收听播放故事
    public static function pushListenStoryQueue($uid, $storyid)
    {
        $key = RedisKey::getUserListenStoryQueueKey();
        $redisobj = AliRedisConnecter::connRedis("user_listen");
        $redisobj->lpush($key, $uid . ":" . $storyid);
        return true;
    }
    public static function popListenStoryQueue()
    {
        $key = RedisKey::getUserListenStoryQueueKey();
        $redisobj = AliRedisConnecter::connRedis("user_listen");
        return $redisobj->rpop($key);
    }
}
?>