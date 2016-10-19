<?php
class Recommend extends ModelBase
{
    public $MAIN_DB_INSTANCE = 'share_main';
    public $STORY_DB_INSTANCE = 'share_story';
    public $RECOMMEND_HOT_TABLE_NAME = 'recommend_hot';
    public $RECOMMEND_SAME_AGE_TABLE_NAME = 'recommend_same_age';
    public $RECOMMEND_NEW_ONLINE_TABLE_NAME = 'recommend_new_online';
    public $FOCUS_TABLE_NAME = 'focus';
    public $RECOMMEND_AGE_LEVEL_TABLE_NAME = 'recommend_age_level';
    public $ALBUM_TABLE_NAME = 'album';

    /**
     * 首页获取热门推荐列表
     * @param I $currentPage 加载第几个,默认为1表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getRecommendHotList($minAge, $maxAge, $startAlbumId = 0, $currentPage = 1, $len = 20)
    {
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if (empty($len)) {
            $len = 20;
        }
        if ($len > 50) {
            $len = 50;
        }

        if ($minAge > $this->MAX_AGE) {
            $minAge = $this->MIN_AGE;
        }
        if (!empty($maxAge) && $maxAge > $this->MAX_AGE) {
            $maxAge = $this->MAX_AGE;
        }

        $key = $minAge . "_" . $maxAge . "_" . $currentPage . "_" . $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->RECOMMEND_HOT_TABLE_NAME, $key);
        if (!empty($redisData)) {

            $where = "1";
            $offset = 0;
            if ($startAlbumId > 0) {
                $where .= " AND `albumid` > {$startAlbumId} ";
            }
            if ($currentPage > 0) {
                $offset = ($currentPage - 1) * $len;
            }
            $where .= " AND `{$this->RECOMMEND_HOT_TABLE_NAME}`.`status` = '{$this->RECOMMEND_STATUS_ONLIINE}'";

            if ($minAge == 0 && $maxAge != 0 && $maxAge != $this->MAX_AGE) {
                $where .= " AND `min_age` = 0 AND `max_age` >= {$maxAge}";
            } elseif ($minAge != 0 && $maxAge != 0) {
                $where .= " AND `min_age` >= {$minAge} AND `max_age` <= {$maxAge}";
            }

            $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
            $sql = "SELECT `id`,`title`,`cover`,`cover_time`,`min_age`,`max_age` 
                      FROM `{$this->RECOMMEND_HOT_TABLE_NAME}` 
                      LEFT JOIN `{$this->ALBUM_TABLE_NAME}` 
                      ON `{$this->RECOMMEND_HOT_TABLE_NAME}`.`albumid` = `{$this->ALBUM_TABLE_NAME}`.`id`
                      WHERE {$where} ORDER BY `ordernum` ASC, `albumid` ASC LIMIT $offset, $len";
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
     * @param I $currentPage 加载第几个,默认为1表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getNewOnlineList($minAge, $maxAge, $startAlbumId = 0, $currentPage = 1, $len = 20)
    {

        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if (empty($len)) {
            $len = 20;
        }
        if ($len > 50) {
            $len = 50;
        }

        if ($minAge > $this->MAX_AGE) {
            $minAge = $this->MIN_AGE;
        }
        if (!empty($maxAge) && $maxAge > $this->MAX_AGE) {
            $maxAge = $this->MAX_AGE;
        }

        $key = $minAge . "_" . $maxAge . "_" . $currentPage . "_" . $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->RECOMMEND_NEW_ONLINE_TABLE_NAME, $key);
        if (empty($redisData)) {

            $where = "1";
            $offset = 0;
            if ($startAlbumId > 0) {
                $where .= " AND `albumid` > {$startAlbumId} ";
            }
            if ($currentPage > 0) {
                $offset = ($currentPage - 1) * $len;
            }
            $where .= " AND `{$this->RECOMMEND_NEW_ONLINE_TABLE_NAME}`.`status` = '{$this->RECOMMEND_STATUS_ONLIINE}'";

            if ($minAge == 0 && $maxAge != 0 && $maxAge != $this->MAX_AGE) {
                $where .= " AND `min_age` = 0 AND `max_age` >= {$maxAge}";
            } elseif ($minAge != 0 && $maxAge != 0) {
                $where .= " AND `min_age` >= {$minAge} AND `max_age` <= {$maxAge}";
            }

            $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
            $sql = "SELECT `id`,`title`,`cover`,`cover_time`,`min_age`,`max_age` 
                      FROM `{$this->RECOMMEND_NEW_ONLINE_TABLE_NAME}` 
                      LEFT JOIN `{$this->ALBUM_TABLE_NAME}` 
                      ON `{$this->RECOMMEND_NEW_ONLINE_TABLE_NAME}`.`albumid` = `{$this->ALBUM_TABLE_NAME}`.`id`
                      WHERE {$where} ORDER BY `ordernum` ASC, `albumid` ASC LIMIT $offset, $len";
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
     * @param I $currentPage 加载第几个,默认为1表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getSameAgeListenList($minAge, $maxAge, $startAlbumId = 0, $currentPage = 1, $len = 20)
    {
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if (empty($len)) {
            $len = 20;
        }
        if ($len > 50) {
            $len = 50;
        }

        if ($minAge > $this->MAX_AGE) {
            $minAge = $this->MIN_AGE;
        }
        if (!empty($maxAge) && $maxAge > $this->MAX_AGE) {
            $maxAge = $this->MAX_AGE;
        }

        $key = $minAge . "_" . $maxAge . "_" . $currentPage . "_" . $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->RECOMMEND_SAME_AGE_TABLE_NAME, $key);
        if (empty($redisData)) {

            $where = "1";
            $offset = 0;
            if ($startAlbumId > 0) {
                $where .= " AND `albumid` > {$startAlbumId} ";
            }
            if ($currentPage > 0) {
                $offset = ($currentPage - 1) * $len;
            }
            $where .= " AND `{$this->RECOMMEND_SAME_AGE_TABLE_NAME}`.`status` = '{$this->RECOMMEND_STATUS_ONLIINE}'";

            if ($minAge == 0 && $maxAge != 0 && $maxAge != $this->MAX_AGE) {
                $where .= " AND `min_age` = 0 AND `max_age` >= {$maxAge}";
            } elseif ($minAge != 0 && $maxAge != 0) {
                $where .= " AND `min_age` >= {$minAge} AND `max_age` <= {$maxAge}";
            }

            $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
            $sql = "SELECT `id`,`title`,`cover`,`cover_time`,`min_age`,`max_age`  
                      FROM `{$this->RECOMMEND_SAME_AGE_TABLE_NAME}` 
                      LEFT JOIN `{$this->ALBUM_TABLE_NAME}` 
                      ON `{$this->RECOMMEND_SAME_AGE_TABLE_NAME}`.`albumid` = `{$this->ALBUM_TABLE_NAME}`.`id`
                      WHERE {$where} ORDER BY `ordernum` ASC, `albumid` ASC LIMIT $offset, $len";

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
            $sql = "SELECT * FROM `{$this->FOCUS_TABLE_NAME}` WHERE `status` = '{$this->RECOMMEND_STATUS_ONLIINE}' AND `category` = '{$this->FOCUS_IMG_CATEGORY_EN_NAME}'  ORDER BY `ordernum` LIMIT $len";
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

    public function getAgeLevelNum($name)
    {

        $dbData = array();
        $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
        $sql = "SELECT * FROM `{$this->RECOMMEND_AGE_LEVEL_TABLE_NAME}` WHERE `name` = '{$name}'";
        $st = $db->prepare($sql);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $db = null;
        if (!empty($row) && isset($row['age_level_album_num'])) {

            $dbData = json_decode($row['age_level_album_num'], true);
        }
        return $dbData;
    }
}