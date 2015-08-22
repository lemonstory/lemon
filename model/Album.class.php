<?php

class Album extends ModelBase 
{

    private $table = 'album';

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

        $db = DbConnecter::connectMysql('share_story');
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
            $tmp_data[] = "`{$k}`＝'{$v}'";
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
     * 获取年龄类型
     */
    public function get_age_type($age_str = '')
    {
        $age_type = 0;

        if (!$age_str) {
            return 0;
        }

        if (stristr($age_str, 'P')) {
            $age = (int)str_replace(array('P', 'p', '-', '+'), array('', '', '', ''), $age_str);
        } else if (stristr($age_str, '岁')) {
            $tmp_str = str_replace('岁', '', $age_str);
            if(strstr($tmp_str, '-')) {
                $tmp_arr = explode('-', $tmp_str);
                if (isset($tmp_arr[1])) {
                    $age = (int)$tmp_arr[1];
                } else {
                    $age = (int)$tmp_arr[0];
                }
            }
        }
        // 没有取到年龄处理
        if (!isset($age)) {
            return 0;
        }
        if ($age >=0 && $age <= 2) {
            $age_type = 1;
        } else if ($age >=3 && $age <= 6) {
            $age_type = 2;
        } else if ($age >=7 && $age <= 10) {
            $age_type = 3;
        }
        return $age_type;
    }
}