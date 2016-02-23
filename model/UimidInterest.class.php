<?php
/*
 * 用户设备，感兴趣的专辑
 * 从感兴趣的专辑，所属的所有标签中，筛选出公共标签
 */
class UimidInterest extends ModelBase
{
    public $UIMID_INTEREST_ALBUM_TABLE_NAME = 'uimid_interest_album';
    public $UIMID_INTEREST_TAG_TABLE_NAME = 'uimid_interest_tag';
    
    /**
     * 获取指定设备喜好的公共标签列表
     * @param S $uimid
     * @return array
     */
    public function getUimidInterestTagList($uimid, $len)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "select * from {$this->UIMID_INTEREST_TAG_TABLE_NAME} where `uimid` = ? order by id desc limit {$len}";
        $st = $db->prepare ( $sql );
        $st->execute (array($uimid));
        $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dbData)) {
            return array();
        }
        return $dbData;
    }
    
    
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
    // 更新感兴趣的标签次数
    private function updateUimidInterestTagNumDb($id)
    {
        $db = DbConnecter::connectMysql($this->ANALYTICS_DB_INSTANCE);
        $sql = "UPDATE `{$this->UIMID_INTEREST_TAG_TABLE_NAME}` SET `num` = `num` + 1 WHERE `id` = ?";
        $st = $db->prepare ( $sql );
        $st->execute (array($id));
        return true;
    }
}