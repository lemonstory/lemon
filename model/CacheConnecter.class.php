<?php
/**
 * cache 
 * @usage CacheConnecter::set('topic', 11111, 'xxxxxxxx')
 * 		CacheConnecter::get('topic', 11111, 'xxxxxxxx')
 * 		CacheConnecter::get('topic', array(11111,22222), 'xxxxxxxx')
 * 
 * cache 使用长连接（即：可多次调用get/set/delete方法，不考虑连接耗时） 2014.11.03 @wangzhitao
 * 
 */
class CacheConnecter
{
    public static $CACHE_ENABLE = true;
	private static $connectpool = array();
    
    public static function connectCache($instance, $isMater=true)
    {
    	if (!empty($_SERVER['isdaemon'])){
    	    //$isMater = true;
    	}
        if (!self::$CACHE_ENABLE){
            return false;
        }
        if (isset(self::$connectpool[$instance]) && @$_SERVER['isdaemon']==0)
        {
        	//return self::$connectpool[$instance];
        }
        $isMater = true;
        try {
            if($isMater) {
                $conf = $_SERVER['cache_conf'][$instance]['master'];
                $connect = new Memcached();
                $connect->setOption(Memcached::OPT_COMPRESSION, false);
                $connect->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
                $connect->addServer($conf['host'], $conf['port']);
                if(!in_array($instance,array('userinfo','userhonor','userdigg','userhomecover','userdiggcount','userlbsinfo','passport')))
                {
                	$connect->setSaslAuthData($conf['user'], $conf['passwd']);
                }
            }else{
                $conf = $_SERVER['cache_conf'][$instance]['slave'];
                $connect = new Memcached($instance);
                if (count($connect->getServerList()) == 0){
                    $connect->setOption(Memcached::OPT_COMPRESSION, false);
                    $connect->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
                    $connect->addServer($conf['host'], $conf['port']);
                    if(!in_array($instance,array('userinfo','userhonor','userdigg','userhomecover','userdiggcount','userlbsinfo','passport')))
                    {
                    	$connect->setSaslAuthData($conf['user'], $conf['passwd']);
                    }
                }
            }
           	self::$connectpool[$instance] = $connect;
            return $connect;
        } catch (Exception $e){
            // log $e->getMessage();
            return false;
        }
    }
    
    public static function set($instance, $id, $value, $expire)
    {
        $cacheObj = self::connectCache($instance);
        if (empty($cacheObj)){
            return false;
        }
        $key = self::keyCreater($instance, $id);
        return $cacheObj->set($key, $value, $expire);
    }
    
    /**
     * cache get/getmulti
     * @param string $instance
     * @param string/array $ids // 支持getmulti
     * @return array
     */
    public static function get($instance, $ids)
    {
        $cacheObj = self::connectCache($instance, false);
        if (empty($cacheObj)){
            return false;
        }
        if (is_array($ids)){
            $keys = self::keyCreater($instance, $ids);
            $cacheData = $cacheObj->getMulti(array_merge($keys,array('afbug')));
            if (!is_array($cacheData)){
                return array();
            }
            $result = array();
            foreach ($keys as $key){
                if (isset($cacheData[$key])){
                    $value = $cacheData[$key];
                    $id = self::getIdByKey($instance, $key);
                    $result[$id] = $value;
                }
            }
            return $result;
        } else {
            $key = self::keyCreater($instance, $ids);
            return $cacheObj->get($key);
        }
    }
    
    public static function delete($instance, $ids)
    {
        $cacheObj = self::connectCache($instance);
        if (empty($cacheObj)){
            return false;
        }
        $ret = true;
        $ids = is_array($ids) ? array_unique($ids) : array($ids);
        if (is_array($ids)){
            foreach ($ids as $id){
                $key = self::keyCreater($instance, $id);
                $ret = $ret && $cacheObj->delete($key);
            }
        }
        return $ret;
    }
    
    public static function deleteMulti($instance, $ids)
    {
        $cacheObj = self::connectCache($instance);
        if (empty($cacheObj)){
            return false;
        }
        $ret = true;
        $ids = is_array($ids) ? array_unique($ids) : array($ids);
        if (is_array($ids)){
            foreach ($ids as $id){
                $keys[] = self::keyCreater($instance, $id);
            }
            $ret = $cacheObj->deleteMulti($keys);
        }
        return $ret;
    }
    
    /**
     * 支持$id = array()
     * @param string $instance
     * @param array/string $id
     * e.g:  keyCreater('topic', array(11111,22222))  => array(topic_11111,topic_22222)
     */
    public static function keyCreater($instance, $ids)
    {
        // 数组表示同时生成多个键名
        if (is_array($ids)){
            $keys = array();
            foreach ($ids as $id){
                $keys[] = self::keyCreater($instance, $id);
            }
            return $keys;
        }
        // 字符串表示生成单个键名
        else {
            $prefix = @$_SERVER['cache_conf'][$instance]['master']['prefix'];
            return $prefix.'_'.$ids;
        }
    }
    
    public static function getIdByKey($instance, $key)
    {
        $prefix = @$_SERVER['cache_conf'][$instance]['master']['prefix'];
        return substr($key, strlen($prefix)+1);
    }
}


