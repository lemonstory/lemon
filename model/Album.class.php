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
            $albumlist = array();
            foreach ($r as $k => $v) {
                $albumlist[$k] = $this->format_to_api($v);
            }
            return $albumlist;
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
        $db = DbConnecter::connectMysql('share_story');
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
                $r = $this->format_to_api($r);
                $favinfo = $fav->getUserFavInfoByAlbumId($uid, $r['id']);
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
    public function getAlbumList( $direction = "down", $startid = 0, $len = 20, $uid = 0)
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
        
        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY `id` DESC LIMIT {$len}";
        $st = $db->prepare($sql);
        $st->execute();
        $res = $st->fetchAll(PDO::FETCH_ASSOC);
        $albumlist = array();
        foreach ($res as $k => $v) {
            $albumlist[$k] = $this->format_to_api($v);
        }
        return $albumlist;
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

    /**
     * 获取封面信息
     */
    public function get_album_info($album_id = 0, $filed = '')
    {
        if (!$album_id) {
            return array();
        }
        $where = "`id`={$album_id}";
        $sql = "select * from {$this->table}  where {$where} limit 1";

        $db = DbConnecter::connectMysql('share_story');
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r  = $st->fetchAll();
        $r  = array_pop($r);
        if ($filed) {
            if (isset($r[$filed])) {
                return $r[$filed];
            } else {
                return '';
            }
        }
        return $this->format_to_api($r);
    }

    /**
     * 获取某字段值
     */
    public function get_filed_value($field = 's_cover', $value = '', $need_filed = '')
    {
        if (!$field) {
            return array();
        }
        $where = "`{$field}`='{$value}' and {$need_filed} !=''";
        $sql = "select * from {$this->table}  where {$where} limit 1";

        $db = DbConnecter::connectMysql('share_story');
        $st = $db->query( $sql );
        if ($st) {
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $r  = $st->fetchAll();
            $r  = array_pop($r);
            if (isset($r[$need_filed]) && $r[$need_filed]) {
                return $r[$need_filed];
            }
        }
        return '';
    }

    // 格式化成接口数据
    public function format_to_api($alubm_info = array())
    {
        static $aliossobj = null;
        if (!$aliossobj) {
            $aliossobj = new AliOss();
        }
        if (empty($alubm_info['cover'])) {
            $alubm_info['cover'] = $alubm_info['s_cover'];
        } else {
            $alubm_info['cover'] = $aliossobj->getImageUrlNg($alubm_info['cover'], 200);
        }
        return $alubm_info;
    }
}