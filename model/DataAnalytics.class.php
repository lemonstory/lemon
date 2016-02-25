<?php
/*
 * 数据分析：专辑中的相关推荐
 */
class DataAnalytics extends ModelBase
{
    public $ALBUM_TAG_RELATION_TABLE = 'album_tag_relation';
    
    
    /**
     * 出现在相关推荐列表的专辑
     * @param A $tagids         感兴趣的标签id数组，可为空
     * @param I $len            推荐的专辑数量
     * @return array            专辑id列表
     */
    public function getRecommendAlbumTagRelationListByInterestTag($tagids = array(), $len = 20)
    {
        if (empty($len)) {
            $this->setError(ErrorConf::paramError());
            return array();
        }
        if ($len < 0 || $len > 1000) {
            $len = 20;
        }
        $where = "";
        if (!empty($tagids)) {
            $tagidstr = implode(",", $tagids);
            $where = "WHERE `tagid` IN ($tagidstr)";
        }
        
        $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
        // 按推荐倒序、收听量倒序
        $sql = "SELECT * FROM {$this->ALBUM_TAG_RELATION_TABLE} {$where} ORDER BY `isrecommend` DESC, `albumlistennum` DESC LIMIT {$len}";
        $st = $db->prepare($sql);
        $st->execute();
        $reslist = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($reslist)) {
            return array();
        }
        foreach ($reslist as $value) {
            $list[$value['albumid']] = $value;
        }
        return $list;
    }
    
    
    /**
     * 获取登录用户搜索过的关键词中，所关联的专辑，出现在相关推荐的专辑
     * @param I $uimid
     * @param I $len
     */
    public function getRecommendAlbumListBySearchContent($uimid, $len)
    {
        if (empty($uimid) || empty($len)) {
            $this->setError(ErrorConf::paramError());
            return array();
        }
        if ($len < 0 || $len > 100) {
            $len = 10;
        }
        
        
    }
}