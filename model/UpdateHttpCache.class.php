<?php
class UpdateHttpCache extends HttpCache
{
    /**
     * 更新首页的http cache
     */
    public function updateDefaultIndexModified()
    {
        $httpCacheName = "default_index";
        $cacheConf = $_SERVER['http_cache_conf'][$httpCacheName];
        $action = $cacheConf['action'];
        $cachetime = $cacheConf['cachetime'];
        $params = $cacheConf['params'];
        
        $key = $this->getKey($action, $params);
        $this->setModifiedTime($key, time(), $cachetime);
    }
    
    /**
     * 更新热门推荐列表的http cache
     */
    public function updateDefaultHotRecommendListModified()
    {
        $httpCacheName = "default_hotrecommendlist";
        $cacheConf = $_SERVER['http_cache_conf'][$httpCacheName];
        $action = $cacheConf['action'];
        $cachetime = $cacheConf['cachetime'];
        $params = $cacheConf['params'];
        
        $key = $this->getKey($action, $params);
        $this->setModifiedTime($key, time(), $cachetime);
    }
    
    /**
     * 更新同龄在听列表的http cache
     */
    public function updateDefaultSameAgeListModified()
    {
        $httpCacheName = "default_sameagelist";
        $cacheConf = $_SERVER['http_cache_conf'][$httpCacheName];
        $action = $cacheConf['action'];
        $cachetime = $cacheConf['cachetime'];
        $params = $cacheConf['params'];
        
        $key = $this->getKey($action, $params);
        $this->setModifiedTime($key, time(), $cachetime);
    }
    
    /**
     * 更新最新上架列表的http cache
     */
    public function updateDefaultNewOnlineListModified()
    {
        $httpCacheName = "default_newonlinelist";
        $cacheConf = $_SERVER['http_cache_conf'][$httpCacheName];
        $action = $cacheConf['action'];
        $cachetime = $cacheConf['cachetime'];
        $params = $cacheConf['params'];
        
        $key = $this->getKey($action, $params);
        $this->setModifiedTime($key, time(), $cachetime);
    }
    
}