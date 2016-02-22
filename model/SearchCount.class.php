<?php
class SearchCount extends ModelBase
{
    public $MAIN_DB_INSTANCE = 'share_main';
    public $SEARCH_COUNT_TABLE_NAME = 'search_content_count';
    
    public $STATUS_ONLINE = 1;
    public $STATUS_OFFLINE = 2;
    
    public $CACHE_INSTANCE = 'cache';
    public $CACHE_EXPIRE = 86400;
    
    /**
     * 热门关键词列表
     * @param I $len
     * @return array
     */
    public function getHotSearchContentList($len = 20)
    {
        if (empty($len) || $len > 50) {
            $len = 20;
        }
        
        $key = RedisKey::getHotSearchContentListKey();
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if (empty($redisData)) {
            $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
            $selectsql = "SELECT * FROM `{$this->SEARCH_COUNT_TABLE_NAME}` WHERE `status` = ? ORDER BY `count` DESC, `id` ASC LIMIT {$len}";
            $selectst = $db->prepare($selectsql);
            $selectst->execute(array($this->STATUS_ONLINE));
            $dbData = $selectst->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            
            $redisobj->setex($key, $this->CACHE_EXPIRE, serialize($dbData));
            return $dbData;
        } else {
            return unserialize($redisData);
        }
    }
    
    
    /**
     * 添加搜索关键词统计
     * @param S $searchcontent    用户搜索内容关键词
     * @return boolean
     */
    public function addSearchContentCount($searchcontent)
    {
        if (empty($searchcontent)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        
        $searchcountinfo = $this->getSearchContentCountInfoDb($searchcontent);
        if (empty($searchcountinfo)) {
            $sql = "INSERT INTO `{$this->SEARCH_COUNT_TABLE_NAME}` (`searchcontent`, `count`, `status`) VALUES ('{$searchcontent}', 1, {$this->STATUS_ONLINE})";
        } else {
            $sql = "UPDATE `{$this->SEARCH_COUNT_TABLE_NAME}` SET `count` = `count` + 1 WHERE `searchcontent` = '{$searchcontent}'";
        }
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $st = $db->prepare($sql);
        $countres = $st->execute();
        if (empty($countres)) {
            return false;
        }
        
        if (empty($searchcountinfo)) {
            $searchid = $db->lastInsertId();
        } else {
            $searchid = $searchcountinfo['id'];
        }
        
        /* if (!empty($searchcountinfo)) {
            // 用户搜索行为的更新，暂时不清cache，1天后自动更新，防止搜索更新太快，cache命中率不高
            $this->clearSearchContentCountInfoCache($searchcontent);
            $this->clearHotSearchContentListCache();
        } */
        return $searchid;
    }
    
    
    /**
     * 更新搜索关键词信息
     * @param I $id
     * @param A $updatearr
     * @return boolean
     */
    public function updateSearchContentCountInfo($id, $updatearr)
    {
        if (empty($id) || empty($updatearr)) {
            return false;
        }
    
        $setstr = "";
        if (!empty($updatearr['status'])) {
            $setstr .= "`status` = '{$updatearr['status']}',";
        }
        $setstr = rtrim($setstr, ",");
        
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $sql = "UPDATE `{$this->SEARCH_COUNT_TABLE_NAME}` SET {$setstr} WHERE `id` = ?";
        $st = $db->prepare($sql);
        $result = $st->execute(array($id));
        if (empty($result)) {
            return false;
        }
        // 更新热门搜索列表cache
        $this->clearHotSearchContentListCache();
        return true;
    }
    
    
    // 删除热门搜素关键词列表cache
    public function clearHotSearchContentListCache()
    {
        $key = RedisKey::getHotSearchContentListKey();
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($key);
    }
    // 删除搜索关键词信息cache
    public function clearSearchContentCountInfoCache($searchcontent)
    {
        $key = RedisKey::getSearchContentCountInfoKey($searchcontent);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($key);
    }
    
    
    // 搜索关键词记录
    private function getSearchContentCountInfoDb($searchcontent)
    {
        if (empty($searchcontent)) {
            return array();
        }
        
        $key = RedisKey::getSearchContentCountInfoKey($searchcontent);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if (empty($redisData)) {
            $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
            $selectsql = "SELECT * FROM `{$this->SEARCH_COUNT_TABLE_NAME}` WHERE `searchcontent` = ?";
            $selectst = $db->prepare($selectsql);
            $selectst->execute(array($searchcontent));
            $dbData = $selectst->fetch(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            
            $redisobj->setex($key, $this->CACHE_EXPIRE, serialize($dbData));
            return $dbData;
        } else {
            return unserialize($redisData);
        }
    }
}