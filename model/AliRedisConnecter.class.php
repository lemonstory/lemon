<?php

class AliRedisConnecter
{
    private static $CONN_TIMEOUT = 3;
    private static $redis = array();

    private static function getRedis($instance)
    {
        if (isset($_SERVER['kvstore_conf'][$instance])) {
            $conf = $_SERVER['kvstore_conf'][$instance];
        } else {
            return false;
        }

        $host = $conf['host'];
        $port = $conf['port'];
        $user = $conf['user'];
        $pwd = $conf['passwd'];
        $database = $conf['db'];

        $redisObj = new Redis();
        $tmp_count = 0;
        $conn_sec = false;
        while ($tmp_count < 3 and $conn_sec === false) {
            $tmp_count = $tmp_count + 1;
            try {
                if ($redisObj->connect($host, $port, self::$CONN_TIMEOUT)) {
                    $redisObj->auth($user . ":" . $pwd);
                    if ($database > 0) {
                        $conn_sec = $redisObj->SELECT($database);
                    } else {
                        $conn_sec = true;
                    }
                } else {
                    $conn_sec = false;
                }
            } catch (RedisException $e) {
                $conn_sec = false;
            }
        }
        if ($redisObj instanceof Redis and isset($redisObj->socket)) {
            self::$redis[$instance] = $redisObj;
            return $redisObj;
        }
        return false;
    }


    public static function connRedis($instance)
    {
        if(empty(self::$redis[$instance])){
            return self::getRedis($instance);
        }
        return self::$redis[$instance];
    }
}