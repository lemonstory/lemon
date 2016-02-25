<?php
/*
 * uimid_interest_tag    设备感兴趣的标签
 * uimid_interest_album  从设备感兴趣的标签中，筛选出标签相似度【行为出现频率num】高的标签。再从这些标签中，按一定规则筛选出推荐给用户的专辑，并设置推荐度【recommendscore】
 */
class UimidInterest extends ModelBase
{
    public $UIMID_INTEREST_ALBUM_TABLE_NAME = 'uimid_interest_album';
    public $UIMID_INTEREST_TAG_TABLE_NAME = 'uimid_interest_tag';
    
    
    /**
     * 获取指定时间内的记录
     * @param S $starttime    开始时间，如2016-02-22 10:00:00
     * @param S $endtime
     * @return array
     */
    /* public function getUimidInterestTagListByTime($starttime, $endtime)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "select * from {$this->UIMID_INTEREST_TAG_TABLE_NAME} where `addtime` >= ? and `addtime` <= ? order by `num` desc";
        $st = $db->prepare ( $sql );
        $st->execute (array($starttime, $endtime));
        $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    } */
    
    /**
     * 获取指定设备喜好的标签列表
     * @param S $uimid
     * @return array
     */
    public function getUimidInterestTagListByUimid($uimid, $len)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "select * from {$this->UIMID_INTEREST_TAG_TABLE_NAME} where `uimid` = ? order by `num` desc limit {$len}";
        $st = $db->prepare ( $sql );
        $st->execute (array($uimid));
        $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    }
    /* public function getUimidInterestTagListByTagid($tagid, $len)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "select * from {$this->UIMID_INTEREST_TAG_TABLE_NAME} where `tagid` = ? order by `num` desc limit {$len}";
        $st = $db->prepare ( $sql );
        $st->execute (array($tagid));
        $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    } */
    
    /**
     * 获取指定设备喜好的专辑列表
     * @param S $uimid
     * @return array
     */
    /* public function getUimidInterestAlbumListByUimid($uimid, $len)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "select * from {$this->UIMID_INTEREST_ALBUM_TABLE_NAME} where `uimid` = ? order by `recommendscore` desc limit {$len}";
        $st = $db->prepare ( $sql );
        $st->execute (array($uimid));
        $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    } */
    
    
    /**
     * 新增设备感兴趣的标签，或更新感兴趣的标签的次数
     * @param S $uimid
     * @param I $tagid
     * @return boolean
     */
    public function updateUimidInterestTag($uimid, $tagid)
    {
        if (empty($uimid) || empty($tagid)) {
            return false;
        }
        $interestinfo = $this->getUimidInterestTagInfoDb($uimid, $tagid);
        if (empty($interestinfo)) {
            $this->addUimidInterestTagDb($uimid, $tagid);
        } else {
            $this->updateUimidInterestTagNumDb($interestinfo['id']);
        }
        return true;
    }
    
    /**
     * 新增设备感兴趣的标签，或更新感兴趣的专辑，以及推荐值
     * @param S $uimid
     * @param I $albumid
     * @return boolean
     */
    /* public function updateUimidInterestAlbum($uimid, $albumid)
    {
        if (empty($uimid) || empty($albumid)) {
            return false;
        }
        $interestinfo = $this->getUimidInterestAlbumInfoDb($uimid, $albumid);
        if (empty($interestinfo)) {
            $this->addUimidInterestAlbumDb($uimid, $albumid);
        } else {
            $this->updateUimidInterestAlbumRecommendScoreDb($interestinfo['id']);
        }
        return true;
    } */
    
    
    /**
     * 获取指定设备、标签的标签喜好记录
     * @param S $uimid
     * @param I $tagid
     * @return array
     */
    private function getUimidInterestTagInfoDb($uimid, $tagid)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "select * from {$this->UIMID_INTEREST_TAG_TABLE_NAME} where `uimid` = ? and `tagid` = ?";
        $st = $db->prepare ( $sql );
        $st->execute (array($uimid, $tagid));
        $dbData = $st->fetch(PDO::FETCH_ASSOC);
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    }
    
    /* private function getUimidInterestAlbumInfoDb($uimid, $albumid)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "select * from {$this->UIMID_INTEREST_ALBUM_TABLE_NAME} where `uimid` = ? and `albumid` = ?";
        $st = $db->prepare ( $sql );
        $st->execute (array($uimid, $albumid));
        $dbData = $st->fetch(PDO::FETCH_ASSOC);
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    } */
    
    
    /**
     * 添加用户设备，感兴趣的标签
     * @param S $uimid
     * @param I $tagid
     * @return boolean
     */
    private function addUimidInterestTagDb($uimid, $tagid)
    {
        if (empty($uimid) || empty($tagid)) {
            return false;
        }
        $addtime = date("Y-m-d H:i:s");
        
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "INSERT INTO `{$this->UIMID_INTEREST_TAG_TABLE_NAME}` (`uimid`, `tagid`, `num`, `addtime`) VALUES (?, ?, ?, ?)";
        $st = $db->prepare ( $sql );
        $res = $st->execute (array($uimid, $tagid, 1, $addtime));
        if (empty($res)) {
            return false;
        }
        return true;
    }
    
    /* private function addUimidInterestAlbumDb($uimid, $albumid)
    {
        if (empty($uimid) || empty($albumid)) {
            return false;
        }
        $addtime = date("Y-m-d H:i:s");
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "INSERT INTO `{$this->UIMID_INTEREST_ALBUM_TABLE_NAME}` (`uimid`, `albumid`, `recommendscore`) VALUES (?, ?, ?)";
        $st = $db->prepare ( $sql );
        $res = $st->execute (array($uimid, $albumid, 1));
        if (empty($res)) {
            return false;
        }
        return true;
    } */
    
    
    // 更新感兴趣的标签次数
    private function updateUimidInterestTagNumDb($id)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "UPDATE `{$this->UIMID_INTEREST_TAG_TABLE_NAME}` SET `num` = `num` + 1 WHERE `id` = ?";
        $st = $db->prepare ( $sql );
        $st->execute (array($id));
        return true;
    }
    
    /* private function updateUimidInterestAlbumRecommendScoreDb($id)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "UPDATE `{$this->UIMID_INTEREST_ALBUM_TABLE_NAME}` SET `recommendscore` = `recommendscore` + 1 WHERE `id` = ?";
        $st = $db->prepare ( $sql );
        $st->execute (array($id));
        return true;
    } */
}