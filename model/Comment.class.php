<?php

class Comment extends ModelBase
{

    private $table = 'album_comment';
    public  $CACHE_INSTANCE = 'cache';

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
        if (isset($data['albumid'])) {
            $this->clearAlbumCommentListCache($data['albumid']);
        }
        return $db->lastInsertId();
    }

    /**
     * 获取星级
     */
    public function getStarLevel($albumid)
    {
        $db = DbConnecter::connectMysql('share_comment');
        $sql = "SELECT sum(star_level) as star_sum from {$this->table} where `albumid`={$albumid} and status=1";
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
        // 清缓存
        $arr = explode("=", $where);
        if (isset($arr[1]) && $arr[1]) {
            $this->clearCommentCache(intval($arr[1]));
        }
        $commentinfo = $this->get_comment_info($arr[1]);
        if ($commentinfo && $commentinfo['albumid']) {
            $this->clearAlbumCommentListCache($commentinfo['albumid']);
        }
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
    public function get_list($where = '', $limit = '', $order_by = '')
    {
        $db = DbConnecter::connectMysql('share_comment');
        if ($limit) {
            $sql = "select * from {$this->table}  where {$where} {$order_by} limit {$limit}";
        } else {
            $sql = "select * from {$this->table}  where {$where}  {$order_by}";
        }
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        return $st->fetchAll();
    }

    /**
     * 获取评论信息
     */
    public function get_comment_info($comment_id = 0, $filed = '')
    {
        if (!$comment_id) {
            return array();
        }

        // 读缓存
        $key = RedisKey::getCommentInfoKey($comment_id);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if ($redisData) {
            $r =  json_decode($redisData, true);
        } else {
            $where = "`id`={$comment_id}";
            $sql = "select * from {$this->table}  where {$where} limit 1";

            $db = DbConnecter::connectMysql('share_comment');
            $st = $db->query( $sql );
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $r  = $st->fetchAll();
            $r  = array_pop($r);
            $redisobj->setex($key, 86400, json_encode($r));
        }

        if ($filed) {
            if (isset($r[$filed])) {
                return $r[$filed];
            } else {
                return '';
            }
        }
        return $r;
    }

    // 获取评论列表
    public function get_comment_list($where = '', $order_by = '', $direction = "down", $startid = 0, $len = 20)
    {
        $arr = explode('=', $where);
        $albumid = 0;
        if (is_numeric($arr[1])) {
            $albumid = $arr[1];
        }
        // 读缓存
        $key = RedisKey::getAlbumCommentListKey(func_get_args());
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if ($redisData) {
            return json_decode($redisData, true);
        }

    	$newcommentlist = array();
        $where .= " and `status`=1";
        if (!empty($startid)) {
            if ($direction == "up") {
                $where .= " AND `id` > '{$startid}'";
            } else {
                $where .= " AND `id` < '{$startid}'";
            }
        }
    	$commentlist = $this->get_list($where, $len, $order_by);
    	foreach ($commentlist as $k => $v) {
    		$newcommentlist[] = $this->format_to_api($v);
    	}
        // 写入缓存
        if ($albumid && $newcommentlist) {
            $redisobj->setex($key, 86400, json_encode($newcommentlist));
        }
    	return $newcommentlist;
    }

    // 格式化评论数据
    public function format_to_api($comment_info = array())
    {
        $user = new User();
        $user_info = $user->getUserInfo($comment_info['userid']);
        $new_comment_info['id'] = $comment_info['id'];
        $new_comment_info['uid'] = $comment_info['userid'];
        if ($user_info) {
            $new_comment_info['uname'] = $user_info[$comment_info['userid']]['nickname'];
        	$new_comment_info['avatartime'] = $user_info[$comment_info['userid']]['avatartime'];
        } else {
            $new_comment_info['uname'] = '匿名用户';
            $new_comment_info['avatartime'] = 0;
        }
        $new_comment_info['start_level'] = $comment_info['star_level'];
        $new_comment_info['addtime'] = $comment_info['addtime'];
        $new_comment_info['comment'] = $comment_info['content'];
        return $new_comment_info;
    }

    // 清除评论缓存
    public function clearCommentCache($commentId = 0)
    {
        $commentIdKey = RedisKey::getCommentInfoKey($commentId);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisobj->delete($commentIdKey);
        return true;
    }

    public function clearAlbumCommentListCache($albumId)
    {
        $commentListKey = RedisKey::getAlbumCommentListKey($albumId);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisobj->delete($commentListKey);
    }
}