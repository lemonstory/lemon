<?php

class UserAlbumLastlog extends ModelBase
{

    private $table = 'user_album_lastlog';

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
        $db = DbConnecter::connectMysql('share_story');
        $sql = "select count(*) as count from {$this->table}  where {$where}";
        $st = $db->query( $sql );
        $r = $st->fetchAll();
        return $r[0]['count'];
    }

    /**
     * 插入记录
     */
    public function replace($data)
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

        $db = DbConnecter::connectMysql('share_story');
        $sql = "REPLACE INTO {$this->table}(
                    {$tmp_filed}
                ) VALUES({$tmp_value})";
        $st = $db->query($sql);
        unset($tmp_value, $tmp_filed);
        return $st;
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

        $db = DbConnecter::connectMysql('share_story');
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
        $db = DbConnecter::connectMysql('share_story');
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
        $db = DbConnecter::connectMysql('share_story');
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
     * 获取最后一条记录
     */
    public function get_last_record($where = '')
    {
        $db = DbConnecter::connectMysql('share_story');
        
        $sql = "select * from {$this->table}  where {$where} order by id DESC limit 1";
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r = $st->fetchAll();
        $r  = array_pop($r);
        return $r;
    }

    /**
     * 获取播放信息通过专辑ID
     */
    public function getPlayInfoByAlbumIds($uid = 0, $albumid = array())
    {
        $albumplayinfo = array();

        if (!$albumid) {
            return array();
        }
        if (!is_array($albumid)) {
            $albumid = array($albumid);
        }
        if (!$uid) {
            
        }

        $db = DbConnecter::connectMysql('share_story');
        
        foreach ($albumid as $k => $v) {
            $sql = "select * from {$this->table}  where `uid`={$uid} and `albumid`={$v} limit 1";
            $st = $db->query( $sql );
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $r = $st->fetchAll();
            $r  = array_pop($r);
            if ($r) {
                $sql = "select * from user_album_log  where `logid`={$r['lastlogid']} limit 1";
                $st = $db->query( $sql );
                $st->setFetchMode(PDO::FETCH_ASSOC);
                $r = $st->fetchAll();
                $r  = array_pop($r);
                $albumplayinfo[$v] = $r;
            } else {
                $albumplayinfo[$v] = array();
            }
            
        }

        return $albumplayinfo;
        
    }

    public function format_to_api($info = array())
    {
    	unset($info['id']);
    	unset($info['userid']);
    	return $info;
    }
}