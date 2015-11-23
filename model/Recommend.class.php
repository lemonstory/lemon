<?php
class Recommend extends ModelBase
{
    public $MAIN_DB_INSTANCE = 'share_main';
    public $RECOMMEND_HOT_TABLE_NAME = 'recommend_hot';
    public $RECOMMEND_SAME_AGE_TABLE_NAME = 'recommend_same_age';
    public $RECOMMEND_NEW_ONLINE_TABLE_NAME = 'recommend_new_online';
    public $FOCUS_TABLE_NAME = 'focus';
    
    /**
     * 首页获取热门推荐列表
     * @param I $currentpage   加载第几个,默认为1表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getRecommendHotList($currentpage = 1, $len = 20)
    {
        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if (empty($len)) {
            $len = 20;
        }
        if ($len > 50) {
            $len = 50;
        }
        
        $key = $currentpage . "_" . $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->RECOMMEND_HOT_TABLE_NAME, $key);
        if (empty($redisData)) {
            $where = "";
            $offset = ($currentpage - 1) * $len;
            $where .= " `status` = '{$this->RECOMMEND_STATUS_ONLIINE}'";
            
            $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
            $sql = "SELECT * FROM `{$this->RECOMMEND_HOT_TABLE_NAME}` WHERE {$where} ORDER BY `ordernum` ASC, `albumid` ASC LIMIT $offset, $len";
            $st = $db->prepare($sql);
            $st->execute();
            $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            
            $cacheobj->setListCache($this->RECOMMEND_HOT_TABLE_NAME, $key, $dbData);
            return $dbData;
        } else {
            return $redisData;
        }
    }
    
    
    /**
     * 首页最新上架的上线列表
     * 按照年龄段，展示最新上架的故事专辑
     * @param I $babyagetype
     * @param I $currentpage   加载第几个,默认为1表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getNewOnlineList($babyagetype = 0, $currentpage = 1, $len = 20)
    {
        if (!empty($babyagetype) && !in_array($babyagetype, $this->AGE_TYPE_LIST)) {
            $this->setError(ErrorConf::paramError());
            return array();
        }
        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if (empty($len)) {
            $len = 5;
        }
        if ($len > 50) {
            $len = 50;
        }
        
        $key = $babyagetype . '_' . $currentpage . "_" . $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->RECOMMEND_NEW_ONLINE_TABLE_NAME, $key);
        if (empty($redisData)) {
            $where = "";
            $offset = ($currentpage - 1) * $len;
            
            $status = $this->RECOMMEND_STATUS_ONLIINE; // 已上线状态
            $where .= "`status` = '{$status}'";
            if (!empty($babyagetype)) {
                $where .= " AND (`agetype` = '{$babyagetype}' or `agetype` = '{$this->AGE_TYPE_All}')";
            }
            $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
            $sql = "SELECT * FROM `{$this->RECOMMEND_NEW_ONLINE_TABLE_NAME}` WHERE {$where} ORDER BY `ordernum` ASC, `albumid` ASC LIMIT $offset, $len";
            $st = $db->prepare($sql);
            $st->execute();
            $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            
            $cacheobj->setListCache($this->RECOMMEND_NEW_ONLINE_TABLE_NAME, $key, $dbData);
            return $dbData;
        } else {
            return $redisData;
        }
    }
    
    
    /**
     * 获取同龄在听的上线列表
     * 按照年龄段，以及用户收听次数最多的专辑排序
     * @param I $babyagetype
     * @param I $currentpage   加载第几个,默认为1表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getSameAgeListenList($babyagetype = 0, $currentpage = 1, $len = 20)
    {
        if (!empty($babyagetype) && !in_array($babyagetype, $this->AGE_TYPE_LIST)) {
            $this->setError(ErrorConf::paramError());
            return array();
        }
        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if (empty($len)) {
            $len = 20;
        }
        if ($len > 50) {
            $len = 50;
        }
        
        $key = $babyagetype . '_' . $currentpage . "_" . $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->RECOMMEND_SAME_AGE_TABLE_NAME, $key);
        if (empty($redisData)) {
            $where = "";
            $offset = ($currentpage - 1) * $len;
            
            $status = $this->RECOMMEND_STATUS_ONLIINE; // 已上线状态
            $where .= " `status` = '{$status}'";
            
            if (!empty($babyagetype)) {
                $where .= " AND (`agetype` = '{$babyagetype}' or `agetype` = '{$this->AGE_TYPE_All}')";
            }
            $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
            $sql = "SELECT * FROM {$this->RECOMMEND_SAME_AGE_TABLE_NAME} WHERE {$where} ORDER BY `ordernum` ASC, `albumid` ASC LIMIT $offset, $len";
            $st = $db->prepare($sql);
            $st->execute();
            $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            
            $cacheobj->setListCache($this->RECOMMEND_SAME_AGE_TABLE_NAME, $key, $dbData);
            return $dbData;
        } else {
            return $redisData;
        }
    }
    
    
    /**
     * 首页获取焦点图列表
     * @param I $len
     * @return
     */
    public function getFocusList($len = 5)
    {
        if (empty($len)) {
            $len = 5;
        }
        
        $key = $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->FOCUS_TABLE_NAME, $key);
        if (empty($redisData)) {
            $db = DbConnecter::connectMysql('share_manage');
            $sql = "SELECT * FROM `{$this->FOCUS_TABLE_NAME}` WHERE `status` = '{$this->RECOMMEND_STATUS_ONLIINE}' ORDER BY `ordernum` LIMIT $len";
            $st = $db->prepare($sql);
            $st->execute();
            $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            
            $cacheobj->setListCache($this->FOCUS_TABLE_NAME, $key, $dbData);
            return $dbData;
        } else {
            return $redisData;
        }
    }
}