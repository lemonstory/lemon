<?php 
class HttpCache
{
    public function checkHttpCache($cacheConf)
    {
        $cacheAction = $cacheConf['cacheAction'];
        if (empty($cacheAction)){
            return false;
        }
        $cacheKey = $this->getKey($cacheAction, $cacheConf['cacheKeyParams']);
        $modifiedTime = $this->getModifiedTime($cacheKey, $cacheConf);
        
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
            (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $modifiedTime))
        {
            // Client's cache IS current, so we just respond '304 Not Modified'.
            header('Cache-Control: '.'max-age='.@$cacheConf['cacheTime'].', public', true);
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modifiedTime).' GMT', true, 304);
            exit;
        } else {
            // Image not cached or cache outdated, we respond '200 OK' and output the image.
            header('Cache-Control: '.'max-age='.@$cacheConf['cacheTime'].', public', true);
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modifiedTime).' GMT', true, 200);
            return;
        }
    }
    
    /**
     * 检查cache参数
     * @param array $cacheConf
     *     array(
     *         'cacheAction'=>'', // http动作表示，用于构成memory cache的key前缀
     *         'cacheTime'=>'', // 浏览器缓存时间（该值推荐设置小一点（1-60），因为次cache期间客户端是不请求服务器的）
     *         'memoryTime'=>'', // 内存缓存时间（该值可适当调到一点（1-n））
     *         'cacheKeyParams'=>'', // 构成用于构成memory cache的key的组成部分 
     *                             //如果url中不包含任何参数，此时加上cacheKeyParams中包含currentUid，则系统会自动 currentUid=当前登录用户的uid
     *     )
     * @param unknown_type $actionData
     * @return boolean|unknown
     */
    public function checkCacheConf($cacheConf, $actionData)
    {
        if (empty($cacheConf['cacheAction'])){
            $cacheConf['cacheAction'] = 'defaultAction';
        }
        if (empty($cacheConf['cacheTime'])){
            $cacheConf['cacheTime'] = 0;
        }
        
        $params = @$cacheConf['cacheKeyParams'];
        $cacheKeyParams = array();
        if (!empty($params)){
            foreach ($params as $p){
                if ($p=='currentUid'){
                    $cacheKeyParams['currentUid'] = @$_SESSION['uid'];
                    continue;
                }
                $cacheKeyParams[$p] = @$actionData['params'][$p];
            }
        }
        $cacheConf['cacheKeyParams'] = $cacheKeyParams;
        $cacheConf['memoryTime'] = @$cacheConf['memoryTime'];
        return $cacheConf;
    }
    
    public function getModifiedTime($key, $cacheConf)
    {
        return time();
        
        $now = time();
        if (empty($key)){
            return $now;
        }
        $expire = 0;
        if (empty($cacheConf['cacheTime'])){
            if (!empty($cacheConf['memoryTime'])){
                $expire = $cacheConf['memoryTime'];
            }
        } else {
            $expire = $cacheConf['cacheTime'];
        }
        $modifiedTime = CacheConnecter::get('httpcache', $key);
        if (empty($modifiedTime)){
            if (!empty($expire)){
                CacheConnecter::set('httpcache', $key, $now, $expire);
            }
            return $now;
        }
        return $modifiedTime;
    }
    
    public function getKey($cacheAction, $params)
    {
        return "{$cacheAction}_".implode('_', $params);
    }
    
    public function setModifiedTime($cacheAction, $params, $time, $expire)
    {
        if (empty($params)){
            return false;
        }
        $key = $this->getKey($cacheAction, $params);
        CacheConnecter::set('httpcache', $key, $time, $expire);
    }
    
    public function setDefaultListModified($currentUid, $starttopicid, $direction, $len)
    {
//         array('currentUid','starttopicid','direction','len')
        $params['currentUid'] = $currentUid;
        $params['starttopicid'] = $starttopicid;
        $params['direction'] = $direction;
        $params['len'] = $len;
        $this->setModifiedTime('udl', $params, time(), 100);
    }
    
    public function setUserInfoModified($uid, $gettopiclist, $len, $direction)
    {
//         array('uid', 'gettopiclist', 'len', 'direction'),
        $params['uid'] = $uid;
        $params['gettopiclist'] = $gettopiclist;
        $params['len'] = $len;
        $params['direction'] = $direction;
        $this->setModifiedTime('ui', $params, time(), 100);
    }
    
    public function setSelfUserInfoModified($uid, $gettopiclist, $len)
    {
//         array('uid', 'gettopiclist', 'len'),
        $params['uid'] = $uid;
        $params['gettopiclist'] = $gettopiclist;
        $params['len'] = $len;
        $this->setModifiedTime('sui', $params, time(), 100);
    }
    
    public function setFriendListModified($uid, $startuid, $len, $direction)
    {
//         array('uid', 'startuid', 'len', 'direction'),
        $params['uid'] = $uid;
        $params['startuid'] = $startuid;
        $params['len'] = $len;
        $params['direction'] = $direction;
        $this->setModifiedTime('fl', $params, time(), 100);
    }
    
}
