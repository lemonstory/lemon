<?php
/*
 * 数据分析：专辑中的相关推荐
 */
class DataAnalytics extends ModelBase
{
    public $ALBUM_TAG_RELATION_TABLE = 'album_tag_relation';
    
    
    /**
     * 指定专辑的多个标签中，出现在相关推荐列表的专辑
     * @param A $albumtagids    标签id数组
     * @param I $len            推荐的专辑数量
     * @return array            专辑id列表
     */
    public function getRecommendAlbumTagRelationListByAlbumTagIds($albumtagids, $len)
    {
        if (empty($albumtagids) || empty($len)) {
            $this->setError(ErrorConf::paramError());
            return array();
        }
        if ($len < 0 || $len > 100) {
            $len = 10;
        }
        $albumtagidstr = implode(",", $albumtagids);
        
        $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
        // 按推荐倒序、收听量倒序
        $sql = "SELECT * FROM {$this->ALBUM_TAG_RELATION_TABLE} WHERE `tagid` IN ($albumtagidstr) ORDER BY `isrecommend` DESC, `albumlistennum` DESC LIMIT {$len}";
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
    
    
}