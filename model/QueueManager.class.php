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
    
    // 添加专辑数据到opensearch
    public static function pushAlbumToSearchQueue($storyid)
    {
        $key = RedisKey::getAlbumToSearchQueueKey();
        $redisobj = AliRedisConnecter::connRedis(self::$QUEUE_INSTANCE);
        $redisobj->lpush($key, $storyid);
        return true;
    }
    public static function popAlbumToSearchQueue()
    {
        $key = RedisKey::getAlbumToSearchQueueKey();
        $redisobj = AliRedisConnecter::connRedis(self::$QUEUE_INSTANCE);
        return $redisobj->rpop($key);
    } 
}
?>