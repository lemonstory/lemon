<?php

class UserFeedback extends ModelBase
{

    private $table = 'user_feed_back';

    /**
     * 检查是否存在
     */
    public function check_exists($where = '')
    {
        if (!$where) {
            return false;
        }
        if ($this->get_total($where)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取总数
     */
    public function get_total($where = '')
    {
        $db = DbConnecter::connectMysql('share_main');
        $sql = "select count(*) as count from {$this->table}  where {$where}";
        $st = $db->query( $sql );
        $r = $st->fetchAll();
        return $r[0]['count'];
    }

    /**
     * 插入记录
     */
    public function insert($data)
    {
        if (!$data) {
            return 0;
        }
        $tmp_filed = array();
        $tmp_value = array();
        foreach ($data as $k => $v) {
            $tmp_filed[] = "`{$k}`";
            $tmp_value[] = "'{$v}'";
        }
        $tmp_filed = implode(",", $tmp_filed);
        $tmp_value = implode(",", $tmp_value);

        $db = DbConnecter::connectMysql('share_main');
        $sql = "INSERT INTO {$this->table}(
                    {$tmp_filed}
                ) VALUES({$tmp_value})";
        $st = $db->query($sql);
        unset($tmp_value, $tmp_filed);
        return $db->lastInsertId();
    }

    /**
     * 更新
     */
    public function update($data, $where = '')
    {
        if (!$data) {
            return false;
        }

        $tmp_data = array();
        foreach ($data as $k => $v) {
            $tmp_data[] = "`{$k}`='{$v}'";
        }
        $tmp_data = implode(",", $tmp_data);
        $set_str  = "SET {$tmp_data} ";

        $db = DbConnecter::connectMysql('share_main');
        $sql = "UPDATE {$this->table} {$set_str} where {$where}";
        $st = $db->query($sql);
        unset($tmp_data);
        return true;
    }

    /**
     * 获取字段信息
     */
    public function get_filed($where = '', $filed = '')
    {
        $db = DbConnecter::connectMysql('share_main');
        $sql = "select * from {$this->table}  where {$where}";
        $st = $db->query( $sql );
        $r = $st->fetchAll();
        if ($filed) {
            return $r[0][$filed];
        } else {
            return $r[0];
        }
    }

    /**
     * 获取列表
     */
    public function get_list($where = '', $limit = '', $filed = '')
    {
        $db = DbConnecter::connectMysql('share_main');
        if ($limit) {
            $sql = "select * from {$this->table}  where {$where} limit {$limit}";
        } else {
            $sql = "select * from {$this->table}  where {$where}";
        }
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r = $st->fetchAll();
        if ($filed) {
            $arr = array();
            foreach($r as $k => $v) {
                $arr[] = $v[$filed];
            }
            return $arr;
        } else {
            return $r;
        }
    }

    /**
     * 批量获取专辑信息
     */
    public function getListByIds($id = 0, $uid = 0)
    {
        if (is_array($id)) {
            $idarr = $id;
        } else {
            $idarr = array($id);
        }
        $albumlist = array();
        $fav = new Fav();
        $db = DbConnecter::connectMysql('share_main');
        foreach($idarr as $k => $v) {
            if (isset($albumlist[$v])) {
                continue;
            }
            $sql = "select * from {$this->table}  where `id`='{$v}' limit 1";
            $st = $db->query( $sql );
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $r  = $st->fetchAll();
            $r  = array_pop($r);
            if ($r) {
                if (!$r['cover']) {
                    $r['cover'] = $r['s_cover'];
                }
                $favinfo = $fav->getUserFavInfoByFeedbackId($uid, $r['id']);
                if ($favinfo) {
                    $r['fav'] = 1;
                } else {
                    $r['fav'] = 0;
                }
                $albumlist[$r['id']] = $r;
            }
        }
        return $albumlist;
    }

    /**
     * 获取用户专辑列表
     * @param I $uid
     * @param S $direction     up代表显示上边，down代表显示下边
     * @param I $startid       从某个id开始,默认为0表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getFeedbackList( $direction = "down", $startid = 0, $len = 20, $uid = 0)
    {
        // if (empty($uid)) {
        //     $this->setError(ErrorConf::paramError());
        //     return array();
        // }
        if (empty($len)) {
            $len = 20;
        }
        
        $where = "`is_show`=1 AND `status` = '1'";
        if (!empty($startid)) {
            if ($direction == "up") {
                $where .= " AND `id` > '{$startid}' ";
            } else {
                $where .= " AND `id` < '{$startid}' ";
            }
        }
        // $where .= " `uid` = '{$uid}'";
        
        $db = DbConnecter::connectMysql('share_main');
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY `id` DESC LIMIT {$len}";
        $st = $db->prepare($sql);
        $st->execute();
        $res = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($res)) {
            return array();
        } else {
            return $res;
        }
    }
}