<?php
class UpdateHttpCache extends HttpCache
{
    /**
     * 更新首页的http cache
     */
    public function updateDefaultIndexModified($visituid)
    {
        $httpCacheName = "default_index";
        $cacheConf = $_SERVER['http_cache_conf'][$httpCacheName];
        $action = $cacheConf['action'];
        $cachetime = $cacheConf['cachetime'];
        
        $params['visituid'] = $visituid;
        $key = $this->getKey($action, $params);
        $this->setModifiedTime($key, time(), $cachetime);
    }
    
    /**
     * 更新收听列表的http cache
     */
    public function updateGetListenListModified($visituid, $direction, $startid, $len)
    {
        $httpCacheName = "listen_getlistenlist";
        $cacheConf = $_SERVER['http_cache_conf'][$httpCacheName];
        $action = $cacheConf['action'];
        $cachetime = $cacheConf['cachetime'];
        
        $params['visituid'] = $visituid;
        $params['direction'] = $direction;
        $params['startid'] = $startid;
        $params['len'] = $len;
        $key = $this->getKey($action, $params);
        $this->setModifiedTime($key, time(), $cachetime);
    }
    
}