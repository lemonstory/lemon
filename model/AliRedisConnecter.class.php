<?php
class AliRedisConnecter
{
	private static $CONN_TIMEOUT = 3;
	public static function connRedis($instance)
	{
		$host = 'xx.m.cnhza.kvstore.aliyuncs.com';
		$port = '6379';
		$user = 'xxx';
		$pwd = 'xxx';
	
		$redisObj = new Redis();
		$tmp_count = 0;
		$conn_sec = FALSE;
		while ($tmp_count < 3 and $conn_sec === FALSE) {
			$tmp_count = $tmp_count + 1;
			try {
				if ($redisObj->connect($host, $port, self::$CONN_TIMEOUT)) {
					$conn_sec = TRUE;
					$redisObj->auth($user . ":" . $pwd);
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