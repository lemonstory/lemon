<?php 
/*
 * http cache类
 * 
 * cacheConf => 
 * array(
 *         'action'=>'',     // http动作表示，用于构成http cache的key前缀
 *         'cachetime'=>'',  // cache control的缓存时间，以及lastmodified的缓存时间，单位秒
 *         'params'=>array('key1', "key2")  // 动作参数
 *     )
 * 
 * actionData =>
 * array(
 *     "module" => "default",
 *     "action" => "index",
 *     "params" => array("uid" => 1, "xx" => xx)
 * )
 */
class HttpCache
{
    public $CACHE_INSTANCE = 'httpcache';
    
    public function checkHttpCache($actionData)
    {
        $cacheConf = $this->getCacheConfByAction($actionData);
        if (empty($cacheConf)) {
            return false;
        }
        // @huqq
        $logFile = '/alidata1/rc.log';
        $fp = @fopen($logFile, 'a+');
        $logContent = '';
        $now = time();
        $a = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        
        $cacheKey = $this->getKey($cacheConf['action'], $cacheConf['params']);
        $modifiedTime = $this->getModifiedTime($cacheKey, $cacheConf);
        
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
            (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $modifiedTime))
        {
            @fwrite($fp, "304 => httptime_{$a} => now_$now \n");
            // Client's cache IS current, so we just respond '304 Not Modified'.
            header('Cache-Control: '.'max-age='.@$cacheConf['cachetime'].', public', true);
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modifiedTime).' GMT', true, 304);
            exit;
        } else {
            @fwrite($fp, "200 => httptime_{$a} => now_$now \n");
            // Image not cached or cache outdated, we respond '200 OK' and output the image.
            header('Cache-Control: '.'max-age='.@$cacheConf['cachetime'].', public', true);
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modifiedTime).' GMT', true, 200);
            return;
        }
    }
    
    
    /**
     * 通过action访问请求，获取http_cache_conf的配置
     * @param array $actionData
     * @return array
     */
    private function getCacheConfByAction($actionData)
    {
        if (empty($actionData['module']) || empty($actionData['action'])) {
            return array();
        }
        
        $httpCacheConf = $_SERVER['http_cache_conf'];
        $httpCacheName = $actionData['module'] . '_' . $actionData['action'];
        if (empty($httpCacheConf[$httpCacheName])) {
            return array();
        }
        
        $cacheConf = array();
        $cacheConf = $httpCacheConf[$httpCacheName];
        if (empty($cacheConf['action'])) {
            return array();
        }
        
        if (empty($cacheConf['cachetime'])){
            $cacheConf['cachetime'] = 0;
        }
        
        // 所有url请求都需要携带visituid=当前访问Uid, 未登录为空
        array_unshift($cacheConf['params'], "visituid");
        
        foreach ($cacheConf['params'] as $value){
            $actionParams[$value] = $actionData['params'][$value];
        }
        $cacheConf['params'] = $actionParams;
        
        return $cacheConf;
    }
    
    /**
     * 获取指定action key的最后更新时间
     * key存在，则返回最后更新时间，不存在，则返回当前时间
     * @param S $key            getkey生成的，指定action的httpcache key
     * @param A $cacheConf      指定action的httpcache配置数组
     * @return I
     */
    protected function getModifiedTime($key, $cacheConf)
    {
        $modifiedtime = time();
        if (empty($key)){
            return $modifiedtime;
        }
        
        $expire = 0;
        if (empty($cacheConf['cachetime'])){
            $expire = 0;
        } else {
            $expire = $cacheConf['cachetime'];
        }
        
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $modifiedTime = $redisobj->get($key);
        if (empty($modifiedTime)){
            if (!empty($expire)){
                $this->setModifiedTime($key, $modifiedtime, $expire);
            }
        }
        return $modifiedTime;
    }
    
    
    /**
     * 设置httpcache请求的lastModified最后修改时间
     * @param S $key             getkey的key
     * @param I $time            请求的最后修改时间
     * @param I $expire          lastmodified最后更新时间的缓存时间，默认为半小时
     * @return boolean
     */
    protected function setModifiedTime($key, $modifiedtime, $expire = 1800)
    {
        if (empty($key) || empty($modifiedtime) || empty($expire)){
            return false;
        }
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisobj->setex($key, $expire, $modifiedtime);
        return true;
    }
    
    /**
     * 获取指定action请求的httpcache key
     * @param S $action        module_action，如"default_index"
     * @param A $params        参数数组：如array("startid" => 1, "len" => 20)
     * @return string
     */
    protected function getKey($action, $params = array())
    {
        $key = "";
        if (empty($params)) {
            $key = $action;
        } else {
            $key = $action . '_' . implode('_', $params);
        }
        
        return $key;
    }
}