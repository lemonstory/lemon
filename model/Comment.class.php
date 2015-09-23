<?php

class Comment extends ModelBase
{

    private $table = 'album_comment';

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
        $db = DbConnecter::connectMysql('share_comment');
        $sql = "select count(*) as count from {$this->table}  where {$where}";
        $st = $db->query( $sql );
        $r = $st->fetchAll();
        return $r[0]['count'];
    }

    /**
     * 批量获取专辑评论数
     */
    public function countAlbumComment($albumid = '')
    {
        if (is_array($albumid)) {
            $albumidarr = $albumid;
        } else {
            $albumidarr = array($albumid);
        }
        $countarr = array();
        foreach($albumidarr as $k => $v) {
            $countarr[$v] = $this->get_total("`albumid`='{$v}' and `status`=1");
        }
        return $countarr;
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

        $db = DbConnecter::connectMysql('share_comment');
        $sql = "INSERT INTO {$this->table}(
                    {$tmp_filed}
                ) VALUES({$tmp_value})";
        $st = $db->query($sql);
        return $db->lastInsertId();
    }

    /**
     * 获取星级
     */
    public function getStarLevel($albumid)
    {
        $db = DbConnecter::connectMysql('share_comment');
        $sql = "SELECT sum(star_level) as star_sum from {$this->table} where `albumid`={$albumid}";
        $st = $db->query($sql);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r  = $st->fetchAll();
        $r  = array_pop($r);
        if (isset($r['star_sum'])) {
            $star_sum = $r['star_sum'];
        } else {
            $star_sum = 0;
        }
        

        $countalbumcomment = $this->countAlbumComment($albumid);
        if ($countalbumcomment) {
            $star_level = $star_sum/$countalbumcomment[$albumid];
        } else {
            $star_level = 0;
        }
        
        return floor($star_level);

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

        $db = DbConnecter::connectMysql('share_comment');
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
        $db = DbConnecter::connectMysql('share_comment');
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
        $db = DbConnecter::connectMysql('share_comment');
        if ($limit) {
            $sql = "select * from {$this->table}  where {$where} limit {$limit}";
        } else {
            $sql = "select * from {$this->table}  where {$where}";
        }
        echo $sql;echo "\n";
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
     * 获取封面信息
     */
    public function get_comment_info($album_id = 0, $filed = '')
    {
        if (!$album_id) {
            return array();
        }
        $where = "`id`={$album_id}";
        $sql = "select * from {$this->table}  where {$where} limit 1";

        $db = DbConnecter::connectMysql('share_comment');
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r  = $st->fetchAll();
        $r  = array_pop($r);;
        if ($filed) {
            if (isset($r[$filed])) {
                return $r[$filed];
            } else {
                return '';
            }
        }
        return $r;
    }

    public function get_comment_list()
    {
    	$newcommentlist = array();
    	$commentlist = $this->get_test_data();
    	foreach ($commentlist as $k => $v) {
    		$newcommentlist[] = $this->format_to_api($v);
    	}
    	return $newcommentlist;
    }

    public function get_test_data()
    {
    	$commentlist = array();
    	for($i = 1; $i <= 10; $i ++) {
    		$comment_info = array();
    		$comment_info['id'] = $i;
	        $comment_info['uid'] = 0;
	        if (!$comment_info['uid']) {
	        	$comment_info['uname'] = '匿名用户';
	        } else {
	        	$comment_info['uname'] = $comment_info['uname'];
	        }
	        $comment_info['start_level'] = mt_rand(4,5);
	        $comment_info['comment'] = '123123';
            $comment_info['avatartime'] = 1442980916;
	        $commentlist[] = $comment_info;
    	}
    	return $commentlist;
    }

    public function format_to_api($comment_info = array())
    {
        $new_comment_info['id'] = $comment_info['id'];
        $new_comment_info['uid'] = $comment_info['uid'];
        if (!$comment_info['uid']) {
        	$new_comment_info['uname'] = '匿名用户';
        } else {
        	$new_comment_info['uname'] = $comment_info['uname'];
        }
        $new_comment_info['start_level'] = mt_rand(4,5);
        $new_comment_info['comment'] = '123123';
        return $new_comment_info;
    }
}