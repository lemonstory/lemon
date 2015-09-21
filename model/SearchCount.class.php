<?php
class SearchCount extends ModelBase
{
    public $MAIN_DB_INSTANCE = 'share_main';
    public $SEARCH_COUNT_TABLE_NAME = 'search_content_count';
    
    public function getSearchContentList()
    {
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $selectsql = "SELECT * FROM `{$this->SEARCH_COUNT_TABLE_NAME}` ORDER BY `count` DESC";
        $selectst = $db->prepare($selectsql);
        $selectst->execute();
        $selectres = $selectst->fetchAll(PDO::FETCH_ASSOC);
        if (empty($selectres)) {
            return array();
        }
        return $selectres;
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
        
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $selectsql = "SELECT * FROM `{$this->SEARCH_COUNT_TABLE_NAME}` WHERE `searchcontent` = ?";
        $selectst = $db->prepare($selectsql);
        $selectst->execute(array($searchcontent));
        $selectres = $selectst->fetch(PDO::FETCH_ASSOC);
        if (empty($selectres)) {
            $sql = "INSERT INTO `{$this->SEARCH_COUNT_TABLE_NAME}` (`searchcontent`, `count`) VALUES ('{$searchcontent}', 1)";
        } else {
            $sql = "UPDATE `{$this->SEARCH_COUNT_TABLE_NAME}` SET `count` = `count` + 1 WHERE `searchcontent` = '{$searchcontent}'";
        }
        $st = $db->prepare($sql);
        $countres = $st->execute();
        if (empty($countres)) {
            return false;
        }
        return true;
    }
}