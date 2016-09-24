<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/24
 * Time: 下午12:40
 */
class TagInfo extends ModelBase
{
    private $table = 'tag_info';

    /**
     * 获取列表
     */
    public function get_list($where = '', $filed = '*', $orderby='id desc', $limit = '20')
    {
        $db = DbConnecter::connectMysql('share_story');
        $sql = "select {$filed} from {$this->table}  where {$where} order by {$orderby} limit {$limit}";
        $st = $db->query( $sql);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r = $st->fetchAll();
        return $r;
    }
}