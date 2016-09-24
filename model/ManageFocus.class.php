<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/24
 * Time: 上午11:54
 */
class ManageFocus extends ModelBase
{
    private $table = 'focus';

    /**
     * 获取列表
     */
    public function get_list($where = '', $filed = '*', $limit = '20')
    {
        $db = DbConnecter::connectMysql('share_manage');
        $sql = "select {$filed} from {$this->table}  where {$where} limit {$limit}";
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r = $st->fetchAll();
        return $r;
    }
}