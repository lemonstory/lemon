<?php
class RedisConnecter {

    private static $CONN_TIMEOUT = 3;
    
    public static function connRedis($instance, $ismaster=true)
    {
        if ($ismaster){
            $conf = $_SERVER['redis_conf'][$instance]['master'];
        } else {
            $conf = $_SERVER['redis_conf'][$instance]['slave'];
        }
        $host = $conf['host'];
        $db = $conf['db'];
        $port = $conf['port'];
        
        
        $redisObj = new Redis();
        $tmp_count = 0;
        $conn_sec = FALSE;
        while ($tmp_count < 3 and $conn_sec === FALSE) {
            $tmp_count = $tmp_count + 1;
            try {
                if ($redisObj->connect($host, $port, self::$CONN_TIMEOUT)) {
                    if ($db > 0) {
                        $conn_sec = $redisObj->SELECT($db);
                    } else {
                        $conn_sec = TRUE;
                    }
                } else {
                    $conn_sec = FALSE;
                }
            } catch (RedisException $e) {
                $conn_sec = FALSE;
            }
        }
        if ($redisObj instanceof Redis and isset($redisObj->socket)) {
            return $redisObj;
        }
        return FALSE;
        
    }
}

?>
