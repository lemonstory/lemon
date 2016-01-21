<?php
class RecommendDesc extends ModelBase
{
    public $ALBUM_RECOMMEND_DESC_TABLE = 'album_recommend_desc';
    
    /**
     * 批量获取专辑的推荐语列表
     * @param A $albumids
     * @return array
     */
    public function getAlbumRecommendDescList($albumids)
    {
        if (empty($albumids)) {
            return array();
        }
        if (!is_array($albumids)) {
            $albumids = array($albumids);
        }
        $albumidstr = implode(",", $albumids);
        $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
        $sql = "SELECT * FROM {$this->ALBUM_RECOMMEND_DESC_TABLE} WHERE `albumid` IN ($albumidstr)";
        $st = $db->prepare($sql);
        $st->execute();
        $dbdata = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dbdata)) {
            return array();
        }
        $list = array();
        foreach ($dbdata as $value) {
            $list[$value['albumid']] = $value;
        }
        return $list;
    }
    
    
    // 添加专辑推荐语
    public function addAlbumRecommendDescDb($albumid, $desc)
    {
        if (empty($albumid) || empty($desc)) {
            return false;
        }
        $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
        $sql = "REPLACE INTO {$this->ALBUM_RECOMMEND_DESC_TABLE} (`albumid`, `desc`) VALUES (?, ?)";
        $st = $db->prepare($sql);
        $res = $st->execute(array($albumid, $desc));
        if (empty($res)) {
            return false;
        }
        return true;
    }
    
}