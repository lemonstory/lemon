<?php
class TagNew extends ModelBase
{
    public $DB_INSTANCE = 'share_story';
    // 标签表
    public $TAG_INFO_TABLE = 'tag_info';
    // 专辑标签关联
    public $ALBUM_TAG_RELATION_TABLE = 'album_tag_relation';
    // 故事标签关联
    public $STORY_TAG_RELATION_TABLE = 'story_tag_relation';
    // CACHE
    public $CACHE_INSTANCE = 'cache';
    
    
    /**
     * 获取一级标签列表
     * @param I $len
     * @return array
     */
    public function getFirstTagList($len)
    {
        if (empty($len) || $len < 0) {
            $len = 20;
        }
        
        $key = "firsttaglist_" . $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->TAG_INFO_TABLE, $key);
        if (empty($redisData)) {
            $db = DbConnecter::connectMysql($this->DB_INSTANCE);
            $selectsql = "SELECT * FROM `{$this->TAG_INFO_TABLE}` WHERE `pid` = ? AND `status` = ? ORDER BY `ordernum` ASC, `id` ASC LIMIT {$len}";
            $selectst = $db->prepare($selectsql);
            $selectst->execute(array(0, $this->RECOMMEND_STATUS_ONLIINE));
            $dbdata = $selectst->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbdata)) {
                return array();
            }
            
            $cacheobj->setListCache($this->TAG_INFO_TABLE, $key, $dbdata);
            return $dbdata;
        } else {
            return $redisData;
        }
    }

    public function getFirstTagIds($len) {

        $list = $this->getFirstTagList($len);
        $ids = $this->getTagIdsWithTagList($list);
        return $ids;
    }

    public function getSecondTagIds($pid, $len)
    {

        $list = $this->getSecondTagList($pid, $len);
        $ids = $this->getTagIdsWithTagList($list);
        return $ids;
    }
    
    
    /**
     * 获取二级标签列表
     * @param I $pid        父级标签id
     * @param I $len
     */
    public function getSecondTagList($pid, $len)
    {
        if (empty($pid)) {
            $this->setError(ErrorConf::paramError());
            return array();
        }
        if (empty($len) || $len < 0) {
            $len = 20;
        }
        
        $key = "secondtaglist_" . $pid . "_" . $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->TAG_INFO_TABLE, $key);
        if (empty($redisData)) {
            $db = DbConnecter::connectMysql($this->DB_INSTANCE);
            $selectsql = "SELECT * FROM `{$this->TAG_INFO_TABLE}` WHERE `pid` = ? AND `status` = ? ORDER BY `ordernum` ASC, `id` ASC LIMIT {$len}";
            $selectst = $db->prepare($selectsql);
            $selectst->execute(array($pid, $this->RECOMMEND_STATUS_ONLIINE));
            $dbdata = $selectst->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbdata)) {
                return array();
            }
        
            $cacheobj->setListCache($this->TAG_INFO_TABLE, $key, $dbdata);
            return $dbdata;
        } else {
            return $redisData;
        }
    }
    
    
    /**
     * 获取标签信息
     * @param I $tagid
     * @return array
     */
    public function getTagInfoByIds($tagids)
    {
        if (empty($tagids)) {
            return array();
        }
        if (!is_array($tagids)) {
            $tagids = array($tagids);
        }
        
        $keys = RedisKey::getTagInfoKeyByIds($tagids);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->mget($keys);
        $cacheData = array();
        $cacheIds = array();
        if (is_array($redisData)){
            foreach ($redisData as $oneredisdata){
                if (empty($oneredisdata)) {
                    continue;
                }
                $oneredisdata = json_decode($oneredisdata, true);
                $cacheIds[] = $oneredisdata['id'];
                $cacheData[$oneredisdata['id']] = $oneredisdata;
            }
        } else {
            $redisData = array();
        }
        // @huqq
        //$cacheIds = array();
        $dbIds = array_diff($tagids, $cacheIds);
        $dbData = array();
        
        if(!empty($dbIds)) {
            $tagidstr = implode(",", $tagids);
            $db = DbConnecter::connectMysql($this->DB_INSTANCE);
            $selectsql = "SELECT * FROM `{$this->TAG_INFO_TABLE}` WHERE `id` IN ($tagidstr)";
            $selectst = $db->prepare($selectsql);
            $selectst->execute();
            $tmpDbData = $selectst->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (!empty($tmpDbData)) {
                foreach ($tmpDbData as $onedbdata){
                    $dbData[$onedbdata['id']] = $onedbdata;
                    $taginfokey = RedisKey::getTagInfoKeyById($onedbdata['id']);
                    $redisobj->setex($taginfokey, 604800, json_encode($onedbdata));
                }
            }
        }
        
        foreach($tagids as $tagid) {
            if(in_array($tagid, $dbIds)) {
                $data[$tagid] = @$dbData[$tagid];
            } else {
                $data[$tagid] = $cacheData[$tagid];
            }
        }
        return $data;
    }
    
    
    /**
     * 根据标签名，获取标签信息
     * @param S $name
     * @return array
     */
    public function getTagInfoByName($name)
    {
        if (empty($name)) {
            return array();
        }
        $md5name = md5($name);
        
        $key = RedisKey::getTagInfoKeyByName($md5name);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if (empty($redisData)) {
            $db = DbConnecter::connectMysql($this->DB_INSTANCE);
            $selectsql = "SELECT * FROM `{$this->TAG_INFO_TABLE}` WHERE `md5name` = ?";
            $selectst = $db->prepare($selectsql);
            $selectst->execute(array($md5name));
            $dbData = $selectst->fetch(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            $redisobj->setex($key, 604800, json_encode($dbData));
            return $dbData;
        } else {
            return json_decode($redisData, true);
        }
    }
    
    
    /**
     * 标签专辑列表：获取指定标签下的专辑列表
     * @param A $tagids        指定标签id数组
     * @param I $isrecommend   是否为一级标签下的推荐标签
     * @param I $ishot         是否为一级标签下的热门标签
     * @param I $isgoodcomment 是否为一级标签下的好评榜标签
     * @param S $direction    
     * @param I $startrelationid   获取更多时，album_tag_relation的id
     * @param I $len
     */
    public function getAlbumTagRelationListFromTag($tagids, $isrecommend = 0, $ishot = 0, $isgoodcomment = 0, $direction = "down", $startrelationid = 0, $len = 20)
    {
        if (empty($tagids)) {
            return array();
        }
        if (!is_array($tagids)) {
            $tagids = array($tagids);
        }
        if (empty($len) || $len < 0 || $len > 1000) {
            $len = 20;
        }
        
        $tagidstr = "";
        foreach ($tagids as $tagid) {
            $tagidstr .= "'{$tagid}',";
        }
        $tagidstr = rtrim($tagidstr, ",");
        $where = "`tagid` IN ($tagidstr)";
        
        $albumtagrelationinfo = array();
        if (!empty($startrelationid)) {
            $albumtagrelationinfo = $this->getAlbumTagRelationInfoById($startrelationid);
        }

        $orderby = "";
        if ($isrecommend == 1) {
            // 推荐
            $where .= " AND `isrecommend` = 1 AND `recommendstatus` = '{$this->RECOMMEND_STATUS_ONLIINE}'";
            if (!empty($albumtagrelationinfo)) {
                if (!empty($albumtagrelationinfo['uptime'])) {
                    if ($direction == "up") {
                        $where .= " AND `uptime` >= '{$albumtagrelationinfo['uptime']}' AND `id` > '{$startrelationid}'";
                    } else {
                        $where .= " AND `uptime` <= '{$albumtagrelationinfo['uptime']}' AND `id` < '{$startrelationid}'";
                    }
                } else {
                    if ($direction == "up") {
                        $where .= " AND `id` > '{$startrelationid}'";
                    } else {
                        $where .= " AND `id` < '{$startrelationid}'";
                    }
                }
            }
            
            $orderby = "ORDER BY `uptime` DESC, `id` DESC";
        } elseif ($ishot == 1) {
            // 最热门
            if (!empty($albumtagrelationinfo)) {
                if (!empty($albumtagrelationinfo['albumlistennum'])) {
                    if ($direction == "up") {
                        $where .= " AND `albumlistennum` >= '{$albumtagrelationinfo['albumlistennum']}' AND `id` > '{$startrelationid}'";
                    } else {
                        $where .= " AND `albumlistennum` <= '{$albumtagrelationinfo['albumlistennum']}' AND `id` < '{$startrelationid}'";
                    }
                } else {
                    if ($direction == "up") {
                        $where .= " AND `id` > '{$startrelationid}'";
                    } else {
                        $where .= " AND `id` < '{$startrelationid}'";
                    }
                }
            }
            $orderby = "ORDER BY `albumlistennum` DESC, `id` DESC";
        } elseif ($isgoodcomment == 1) {
            // 好评榜
            if (!empty($albumtagrelationinfo)) {
                    if ($direction == "up") {
                        $where .= " AND `commentstarlevel` >= '{$albumtagrelationinfo['commentstarlevel']}' AND `id` > '{$startrelationid}'";
                    } else {
                        $where .= " AND `commentstarlevel` <= '{$albumtagrelationinfo['commentstarlevel']}' AND `id` < '{$startrelationid}'";
                    }
            }
            $orderby = "ORDER BY `commentstarlevel` DESC, `id` DESC";
        } else {
            // 全部、其他标签
            if (!empty($albumtagrelationinfo)) {
//                if (!empty($albumtagrelationinfo['uptime'])) {
//                    if ($direction == "up") {
//                        $where .= " AND `uptime` >= '{$albumtagrelationinfo['uptime']}' AND `id` > '{$startrelationid}'";
//                    } else {
//                        $where .= " AND `uptime` <= '{$albumtagrelationinfo['uptime']}' AND `id` < '{$startrelationid}'";
//                    }
//                } else {
                    if ($direction == "up") {
                        $where .= " AND `id` > '{$startrelationid}'";
                    } else {
                        $where .= " AND `id` < '{$startrelationid}'";
//                    }
                }
            }
            $orderby = "ORDER BY `uptime` DESC, `id` DESC";
        }

        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "SELECT * FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE {$where} $orderby LIMIT {$len}";
        $selectst = $db->prepare($selectsql);
        $selectst->execute();
        $dbdata = $selectst->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        if (empty($dbdata)) {
            return array();
        }
        return $dbdata;
    }
    
    
    /**
     * 获取热门推荐、最新上架、同龄在听，指定标签的专辑列表
     * @param I $tagids        指定标签下的热门推荐列表，若为"全部"时,tagids为空
     * @param I $isrecommend   是否热门推荐
     * @param I $issameage     是否同龄在听
     * @param I $isnewonline   是否最新上架
     * @param I $currentpage   加载第几个,默认为1表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getAlbumTagRelationListFromRecommend($tagids, $isrecommend = 0, $issameage = 0, $isnewonline = 0, $currentpage = 1, $len = 20)
    {
       if(!is_array($tagids)) {
            $tagids = array($tagids);
        }
        if (empty($isrecommend) && empty($issameage) && empty($isnewonline)) {
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
        
        $redisData = array();
        if (empty($redisData)) {
            $where = "";
            if (!empty($tagids)) {
                $tagidstr = "";
                foreach ($tagids as $tagid) {
                    if(!empty($tagid)) {
                        $tagidstr .= "'{$tagid}',";
                    }
                }
                if(!empty($tagidstr)) {
                    $tagidstr = rtrim($tagidstr, ",");
                    $where .= "`tagid` IN ($tagidstr) AND ";
                }
            }
            
            $onlinestatus = $this->RECOMMEND_STATUS_ONLIINE;
            if ($isrecommend == 1) {
                $where .= "`isrecommend` = 1 AND `recommendstatus` = $onlinestatus";
            } elseif ($issameage == 1) {
                $where .= "`issameage` = 1 AND `sameagestatus` = $onlinestatus";
            } elseif ($isnewonline == 1) {
                $where .= "`isnewonline` = 1 AND `newonlinestatus` = $onlinestatus";
            }
            $orderby = "ORDER BY `uptime` DESC, `id` DESC";
            $offset = ($currentpage - 1) * $len;
            
            $db = DbConnecter::connectMysql($this->DB_INSTANCE);
            $sql = "SELECT * FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE {$where} {$orderby} LIMIT $offset, $len";
            $st = $db->prepare($sql);
            $st->execute();
            $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            return $dbData;
        } else {
            return $redisData;
        }
    }
    
    
    /**
     * 获取单个或多个专辑下的所有关联列表
     * @param I $albumids
     * @return array
     */
    public function getAlbumTagRelationListByAlbumIds($albumids)
    {
        if (empty($albumids)) {
            return array();
        }
        if (!is_array($albumids)) {
            $albumids = array($albumids);
        }
        $keys = RedisKey::getAlbumTagRelationKeyByAlbumIds($albumids);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->mget($keys);
        // @huqq
        //$redisData = array();
        
        $cacheData = array();
        $cacheIds = array();
        if (is_array($redisData)){
            foreach ($redisData as $listredisdata){
                if (empty($listredisdata)) {
                    continue;
                }
                $listredisdata = json_decode($listredisdata, true);
                foreach ($listredisdata as $tagidkey => $albumtagrelationinfo) {
                    $albumid = $albumtagrelationinfo['albumid'];
                    $cacheIds[] = $albumid;
                    $cacheData[$albumid][$tagidkey] = $albumtagrelationinfo;
                }
            }
        } else {
            $redisData = array();
        }
        if (!empty($cacheIds)) {
            $cacheIds = array_unique($cacheIds);
        }
        $dbIds = array_diff($albumids, $cacheIds);
        $dbData = array();
        
        if(!empty($dbIds)) {
            $idlist = implode(',', $dbIds);
            $db = DbConnecter::connectMysql($this->DB_INSTANCE);
            $selectsql = "SELECT * FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE `albumid` IN ($idlist)";
            $selectst = $db->prepare($selectsql);
            $selectst->execute();
            $tmpDbData = $selectst->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (!empty($tmpDbData)) {
                foreach ($tmpDbData as $onedbdata){
                    $dbData[$onedbdata['albumid']][$onedbdata['tagid']] = $onedbdata;
                }
                if (!empty($dbData)) {
                    foreach ($dbData as $setdata) {
                        $value = current($setdata);
                        $albumid = $value['albumid'];
                        $relationkey = RedisKey::getAlbumTagRelationKeyByAlbumId($albumid);
                        $redisobj->setex($relationkey, 604800, json_encode($setdata));
                    }
                }
            }
        }
        
        foreach($albumids as $albumid) {
            if(in_array($albumid, $dbIds)) {
                $data[$albumid] = @$dbData[$albumid];
            } else {
                $data[$albumid] = $cacheData[$albumid];
            }
        }
        
        return $data;
    }
    
    
    /**
     * 添加专辑标签
     * @param I $albumid    专辑ID
     * @param S $name       标签名称
     * @param I $pid        若存在父级标签，则pid=父级标签ID
     * @return boolean
     */
    public function addAlbumTag($albumid, $name, $pid = 0)
    {
        if (empty($albumid) || empty($name)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        $taginfo = $this->getTagInfoByName($name);
        if (empty($taginfo)) {
            $tagid = $this->addTagDb($pid, $name);
            $parenttagid = $pid;
        } else {
            $tagid = $taginfo['id'];
            $parenttagid = $taginfo['pid'];
        }
        
        $albumtagrelation = $this->getAlbumTagRelationInfoByAlbumIdTagId($albumid, $tagid);
        if (empty($albumtagrelation)) {
            $this->addAlbumTagRelationDb($albumid, $tagid);
        }
        
        // 新增的标签若为二级标签时，则检测父级标签与专辑albumid的关联记录是否存在
        if (!empty($parenttagid)) {
            $parentalbumtagrelation = $this->getAlbumTagRelationInfoByAlbumIdTagId($albumid, $parenttagid);
            if (empty($parentalbumtagrelation)) {
                $this->addAlbumTagRelationDb($albumid, $parenttagid);
            }
        }
        
        return true;
    }
    
    
    /**
     * 添加专辑与标签的关联记录
     * @param I $albumid
     * @param I $tagid
     * @return boolean
     */
    public function addAlbumTagRelationInfo($albumid, $tagid)
    {
        if (empty($albumid) || empty($tagid)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        
        $albumtagrelation = $this->getAlbumTagRelationInfoByAlbumIdTagId($albumid, $tagid);
        if (empty($albumtagrelation)) {
            $this->addAlbumTagRelationDb($albumid, $tagid);
        }
        return true;
    }
    
    
    /**
     * 添加标签
     * @param S $name
     * @param I $pid    若存在父级标签，则pid=父级标签ID
     * @return I
     */
    public function addTag($name, $pid = 0)
    {
        if (empty($name)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        $taginfo = $this->getTagInfoByName($name);
        if (empty($taginfo)) {
            $tagid = $this->addTagDb($pid, $name);
            return $tagid;
        } else {
            $this->setError(ErrorConf::TagInfoIsExist());
            return false;
        }
    }
    
    
    /**
     * 更新标签信息
     * @param I $tagid
     * @param I $tagname
     * @param A $updatedata
     * @return boolean
     */
    public function updateTagInfo($tagid, $tagname, $updatedata)
    {
        if (empty($tagid) || empty($tagname) || empty($updatedata)) {
            return false;
        }
        $updatestr = "";
        foreach ($updatedata as $key => $value) {
            $updatestr .= "`{$key}` = '{$value}',";
        }
        $updatestr = rtrim($updatestr, ",");
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "UPDATE `{$this->TAG_INFO_TABLE}` SET {$updatestr} WHERE `id` = ?";
        $selectst = $db->prepare($selectsql);
        $updateres = $selectst->execute(array($tagid));
        if (empty($updateres)) {
            return false;
        }
        
        // clear cache
        $this->clearTagInfoCacheById($tagid);
        $this->clearTagInfoCacheByName($tagname);
        
        $cacheobj = new CacheWrapper();
        $cacheobj->deleteNSCache($this->TAG_INFO_TABLE);
        return true;
    }
    
    
    /**
     * 累加所有标签中，指定专辑的收听总数
     * @param I $albumid
     * @param I $num
     * @return boolean
     */
    public function updateAlbumTagRelationListenNum($albumid, $num, $isforce = false)
    {
        if (empty($albumid) || empty($num)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        $relationlist = $this->getAlbumTagRelationListByAlbumIds($albumid);
        if (empty($relationlist)) {
            return false;
        }
        $relationids = array();
        foreach ($relationlist as $value) {
            $relationids[] = $value['id'];
        }
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        if ($isforce) {
            $field = " `albumlistennum` =  {$num} ";
        } else {
            $field = " `albumlistennum` = `albumlistennum` + {$num} ";
        }
        $selectsql = "UPDATE `{$this->ALBUM_TAG_RELATION_TABLE}` SET {$field} WHERE `albumid` = ?";
        $selectst = $db->prepare($selectsql);
        $updateres = $selectst->execute(array($albumid));
        if (empty($updateres)) {
            return false;
        }
        // clear cache
        $this->clearAlbumTagRelationCacheByAlbumIds($albumid);
        if (!empty($relationids)) {
            $this->clearAlbumTagRelationCacheById($relationids);
        }
        return true;
    }
    
    
    /**
     * 更新所有标签中，指定专辑的评论星级
     * @param I $albumid
     * @param I $commentstarlevel
     * @return boolean
     */
    public function updateAlbumTagRelationCommentStarLevel($albumid, $commentstarlevel)
    {
        if (empty($albumid) || empty($commentstarlevel)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        $relationlist = $this->getAlbumTagRelationListByAlbumIds($albumid);
        if (empty($relationlist)) {
            return false;
        }
        $relationids = array();
        foreach ($relationlist as $value) {
            $relationids[] = $value['id'];
        }
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "UPDATE `{$this->ALBUM_TAG_RELATION_TABLE}` SET `commentstarlevel` = ? WHERE `albumid` = ?";
        $selectst = $db->prepare($selectsql);
        $updateres = $selectst->execute(array($commentstarlevel, $albumid));
        if (empty($updateres)) {
            return false;
        }
        
        // clear cache
        $this->clearAlbumTagRelationCacheByAlbumIds($albumid);
        if (!empty($relationids)) {
            $this->clearAlbumTagRelationCacheById($relationids);
        }
        return true;
    }

    public function updateAlbumTagRelationInfo($album_id, $tag_id, $update_data)
    {


        if (empty($album_id) || empty($tag_id) || empty($update_data)) {
            return false;
        }

        $update_str = "";
        foreach ($update_data as $key => $value) {
            $update_str .= "`{$key}` = '{$value}',";
        }
        $update_str = rtrim($update_str, ",");

        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "UPDATE `{$this->ALBUM_TAG_RELATION_TABLE}` SET {$update_str} WHERE `albumid` = ? and  `tagid` = ?";
        $selectst = $db->prepare($selectsql);
        $updateres = $selectst->execute(array($album_id, $tag_id));
        if (empty($updateres)) {
            return false;
        }

        // clear cache
        $this->clearAlbumTagRelationCacheByAlbumIds($album_id);
        return true;
    }
    
    
    /**
     * 删除专辑的某个标签
     * @param I $albumid
     * @param I $tagid
     * @return boolean
     */
    public function deleteAlbumTagRelationByAlbumIdTagId($albumid, $tagid)
    { 
        if (empty($albumid) || empty($tagid)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        
        $relationinfo = $this->getAlbumTagRelationInfoByAlbumIdTagId($albumid, $tagid);
        if (empty($relationinfo)) {
            return false;
        }
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "DELETE FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE `albumid` = ? AND `tagid` = ?";
        $selectst = $db->prepare($selectsql);
        $updateres = $selectst->execute(array($albumid, $tagid));
        if (empty($updateres)) {
            return false;
        }
        
        // clear cache
        $this->clearAlbumTagRelationCacheByAlbumIds($albumid);
        $this->clearAlbumTagRelationCacheById($relationinfo['id']);
        return true;
    }
    
    
    /**
     * 删除指定标签的，所有专辑标签关联记录
     * @param I $tagid
     * @return boolean
     */
    public function deleteAlbumTagRelationByTagId($tagid)
    {
        if (empty($tagid)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        
        // 获取标签的所有专辑列表
        $relationlist = $this->getAlbumTagRelationInfoByTagId($tagid);
        if (empty($relationlist)) {
            return false;
        }
        $albumids = array();
        $relationids = array();
        foreach ($relationlist as $value) {
            $albumids[] = $value['albumid'];
            $relationids[] = $value['id'];
        }
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "DELETE FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE `tagid` = ?";
        $selectst = $db->prepare($selectsql);
        $updateres = $selectst->execute(array($tagid));
        if (empty($updateres)) {
            return false;
        }
        
        // clear cache
        if (!empty($albumids)) {
            $albumids = array_unique($albumids);
            $this->clearAlbumTagRelationCacheByAlbumIds($albumids);
        }
        if (!empty($relationids)) {
            $this->clearAlbumTagRelationCacheById($relationids);
        }
        return true;
    }
    
    
    /**
     * 删除指定标签信息
     * @param I $tagid
     * @param S $tagname
     * @return boolean
     */
    public function deleteTagInfo($tagid, $tagname)
    {
        if (empty($tagid) || empty($tagname)) {
            return false;
        }
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "DELETE FROM `{$this->TAG_INFO_TABLE}` WHERE `id` = ?";
        $selectst = $db->prepare($selectsql);
        $res = $selectst->execute(array($tagid));
        if (empty($res)) {
            return false;
        }
        
        // clear cache
        $this->clearTagInfoCacheById($tagid);
        $this->clearTagInfoCacheByName($tagname);
        
        $cacheobj = new CacheWrapper();
        $cacheobj->deleteNSCache($this->TAG_INFO_TABLE);
        return true;
    }
    
    
    // 通过tagid清除标签cache
    public function clearTagInfoCacheById($tagid)
    {
        if (empty($tagid)) {
            return false;
        }
        $key = RedisKey::getTagInfoKeyById($tagid);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($key);
    }
    // 通过tagname清除标签cache
    public function clearTagInfoCacheByName($name)
    {
        if (empty($name)) {
            return false;
        }
        $md5name = md5($name);
        $key = RedisKey::getTagInfoKeyByName($md5name);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($key);
    }
    
    
    // 清除指定albumid、tagid的专辑、标签关联信息cache
    /* public function clearAlbumTagRelationCacheByAlbumIdTagId($albumid, $tagid)
    {
        if (empty($albumid) || empty($tagid)) {
            return false;
        }
        $key = RedisKey::getAlbumTagRelationKeyByAlbumIdTagId($albumid, $tagid);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($key);
    } */
    // 清除指定id的专辑、标签关联cache
    public function clearAlbumTagRelationCacheById($ids)
    {
        if (empty($ids)) {
            return false;
        }
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $keys = RedisKey::getAlbumTagRelationKeyByIds($ids);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($keys);
    }
    // 清除指定专辑id的关联cache
    public function clearAlbumTagRelationCacheByAlbumIds($albumids)
    {
        if (empty($albumids)) {
            return false;
        }
        if (!is_array($albumids)) {
            $albumids = array($albumids);
        }
        $keys = RedisKey::getAlbumTagRelationKeyByAlbumIds($albumids);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($keys);
    }
    
    
    private function getTagIdsWithTagList($tagList)
    {
        $ids = array();
        if (!empty($tagList)) {
            foreach ($tagList as $taginfo) {
                $ids[] = $taginfo['id'];
            }
        }
        return $ids;
    }
    
    
    // 获取指定id的专辑、标签的关联信息
    private function getAlbumTagRelationInfoById($albumtagrelationid)
    {
        if (empty($albumtagrelationid)) {
            return array();
        }
        $redisData = array();
        $key = RedisKey::getAlbumTagRelationKeyById($albumtagrelationid);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if (empty($redisData)) {
            $db = DbConnecter::connectMysql($this->DB_INSTANCE);
            $selectsql = "SELECT * FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE `id` = ?";
            $selectst = $db->prepare($selectsql);
            $selectst->execute(array($albumtagrelationid));
            $dbData = $selectst->fetch(PDO::FETCH_ASSOC);
            $db = null;
            if (empty($dbData)) {
                return array();
            }
            $redisobj->setex($key, 604800, json_encode($dbData));
            return $dbData;
        } else {
            return json_decode($redisData, true);
        }
    }
    
    // 获取指定tagid的专辑、标签的关联信息
    private function getAlbumTagRelationInfoByTagId($tagid)
    {
        if (empty($tagid)) {
            return array();
        }
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "SELECT * FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE `tagid` = ?";
        $selectst = $db->prepare($selectsql);
        $selectst->execute(array($tagid));
        $dbData = $selectst->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    }
    
    /**
     * 获取指定专辑、标签的关联信息
     * @param I $albumid
     * @param I $tagid
     * @return array
     */
    private function getAlbumTagRelationInfoByAlbumIdTagId($albumid, $tagid)
    {
        if (empty($albumid) || empty($tagid)) {
            return array();
        }
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "SELECT * FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE `albumid` = ? AND `tagid` = ?";
        $selectst = $db->prepare($selectsql);
        $selectst->execute(array($albumid, $tagid));
        $dbData = $selectst->fetch(PDO::FETCH_ASSOC);
        $db = null;
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    }
    
    
    /**
     * 添加标签记录
     * @param I $pid    是否有父级标签id
     * @param S $name   标签名称
     * @return boolean
     */
    private function addTagDb($pid, $name)
    {
        if (empty($name)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        if (empty($pid)) {
            $pid = 0;
        }
        $md5name = md5($name);
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $sql = "INSERT INTO `{$this->TAG_INFO_TABLE}` (`pid`, `name`, `md5name`, `status`) VALUES (?, ?, ?, ?)";
        $st = $db->prepare($sql);
        $res = $st->execute(array($pid, $name, $md5name, $this->RECOMMEND_STATUS_OFFLINE));
        if (empty($res)) {
            return false;
        }
        return $db->lastInsertId();
    }
    
    
    /**
     * 添加专辑与标签关联记录
     * @param I $albumid      专辑ID
     * @param I $tagid        标签ID
     * @return boolean
     */
    private function addAlbumTagRelationDb($albumid, $tagid)
    {
        if (empty($albumid) || empty($tagid)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        $nowtime = time();
        $addtime = date("Y-m-d H:i:s", $nowtime);
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $sql = "INSERT INTO `{$this->ALBUM_TAG_RELATION_TABLE}` (`tagid`, `albumid`, `uptime`, `addtime`) VALUES (?, ?, ?, ?)";
        $st = $db->prepare($sql);
        $res = $st->execute(array($tagid, $albumid, $nowtime, $addtime));
        if (empty($res)) {
            return false;
        }
        
        // 清除专辑标签列表cache
        $this->clearAlbumTagRelationCacheByAlbumIds($albumid);
        return true;
    }
    
    
    /**
     * 添加故事与标签关联记录
     * @param I $tagid        标签ID
     * @param I $storyid      
     * @return boolean
     */
    /* private function addStoryTagRelationDb($storyid, $tagid)
    {
        if (empty($storyid) || empty($tagid)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $sql = "INSERT INTO `{$this->STORY_TAG_RELATION_TABLE}` (`tagid`, `storyid`) VALUES (?, ?)";
        $st = $db->prepare($sql);
        $res = $st->execute(array($tagid, $storyid));
        if (empty($res)) {
            return false;
        }
        return true;
    } */
}
?>