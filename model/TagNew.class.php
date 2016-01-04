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
            $len = 10;
        }
        
        $key = $len;
        $cacheobj = new CacheWrapper();
        $redisData = $cacheobj->getListCache($this->TAG_INFO_TABLE, $key);
        $redisData = array();
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
        
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "SELECT * FROM `{$this->TAG_INFO_TABLE}` WHERE `md5name` = ?";
        $selectst = $db->prepare($selectsql);
        $selectst->execute(array($md5name));
        $info = $selectst->fetch(PDO::FETCH_ASSOC);
        if (empty($info)) {
            return array();
        }
        return $info;
    }
    
    
    /**
     * 获取指定专辑、标签的关联信息
     * @param I $albumid    
     * @param I $tagid      
     * @return array
     */
    public function getAlbumTagRelationInfo($albumid, $tagid)
    {
        if (empty($albumid) || empty($tagid)) {
            return array();
        }
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $selectsql = "SELECT * FROM `{$this->ALBUM_TAG_RELATION_TABLE}` WHERE `albumid` = ? AND `tagid` = ?";
        $selectst = $db->prepare($selectsql);
        $selectst->execute(array($albumid, $tagid));
        $info = $selectst->fetch(PDO::FETCH_ASSOC);
        if (empty($info)) {
            return array();
        }
        return $info;
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
        } else {
            $tagid = $taginfo['id'];
        }
        
        $albumtagrelation = $this->getAlbumTagRelationInfo($albumid, $tagid);
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
            return false;
        }
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
        $db = DbConnecter::connectMysql($this->DB_INSTANCE);
        $sql = "INSERT INTO `{$this->ALBUM_TAG_RELATION_TABLE}` (`tagid`, `albumid`) VALUES (?, ?)";
        $st = $db->prepare($sql);
        $res = $st->execute(array($tagid, $albumid));
        if (empty($res)) {
            return false;
        }
        return true;
    }
    
    
    /**
     * 添加故事与标签关联记录
     * @param I $tagid        标签ID
     * @param I $storyid      
     * @return boolean
     */
    private function addStoryTagRelationDb($storyid, $tagid)
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
    }
}
?>