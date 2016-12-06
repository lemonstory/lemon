<?php

class Story extends ModelBase
{

	private $table = 'story';
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
			$tmp_filed[] .= "`{$k}`";
			$tmp_value[] .= "'{$v}'";
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

    // 获取字段列表
    public function get_filed_list($filed = '*', $where = '', $orderby = '', $limit = '')
    {
        $db = DbConnecter::connectMysql('share_story');
        if ($where) {
            $where = "where {$where}";
        }
        if ($limit) {
            $limit = "limit {$limit}";
        }
        $sql = "select {$filed} from {$this->table} {$where} {$orderby} {$limit} ";
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r = $st->fetchAll();
        if ($r) {
            return $r;
        } else {
            return array();
        }
    }

    // 获取排序
    public function get_view_order($title = '',$len = 0) {

        $title = trim($title);
        $len = intval($len);

        //简体->繁体替换
        $search = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖","拾");
        $replace = array("零","一","二","三","四","五","六","七","八","九","十");
        $title = str_replace($search,$replace,$title);

        //简体->数字替换
        $search = $replace;
        $replace = array("0","1","2","3","4","5","6","7","8","9","10");
        $title = str_replace($search,$replace,$title);

        //提取字符串中所有的数字拼成字符串
        $view_order_str = preg_replace('/[^0-9]/','',$title);
        if(0 != $len) {
            $view_order_str = substr($view_order_str,0,$len);
        }

        //字符串->数字
        $view_order = intval($view_order_str);
        return $view_order;
    }

	/**
	 * 获取列表
	 */
	public function get_list($where = '', $limit = '', $filed = '', $orderby = '')
	{
		$db = DbConnecter::connectMysql('share_story');
		if ($limit) {
			$sql = "select * from {$this->table}  where {$where} {$orderby} limit {$limit} ";
		} else {
			$sql = "select * from {$this->table}  where {$where} {$orderby} ";
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
        	$storylist = array();
            foreach ($r as $k => $v) {
                $storylist[$k] = $this->format_to_api($v);
            }
            return $storylist;
        }
	}

    /**
     * 批量获取故事信息
     */
    public function getListByIds($id = 0, $uid = 0)
    {
        return $this->getListByIdsNew($id);
        
        if (is_array($id)) {
            $idarr = $id;
        } else {
            $idarr = array($id);
        }
        // 连redis
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);

        $storylist = array();
        $db = DbConnecter::connectMysql('share_story');
        foreach($idarr as $k => $v) {
            if (isset($storylist[$v])) {
                continue;
            }

            $key = RedisKey::getStoryInfoKey($v);
            $redisData = $redisobj->get($key);
            if ($redisData) {
                $r = json_decode($redisData, true);
            } else {
                $sql = "select * from {$this->table}  where `id`='{$v}' limit 1";
                $st = $db->query( $sql );
                $st->setFetchMode(PDO::FETCH_ASSOC);
                $r  = $st->fetchAll();
                $r  = array_pop($r);
                // 写入缓存
                $redisobj->setex($key, 604800, json_encode($r));
            }
            if ($r) {
                $storylist[$r['id']] = $this->format_to_api($r);
            }
        }
        return $storylist;
    }
    
    
    // 优化getListByIds，批量获取专辑列表
    public function getListByIdsNew($storyids)
    {
        if (empty($storyids)) {
            return array();
        }
        if (!is_array($storyids)) {
            $storyids = array($storyids);
        }
        
        $keys = RedisKey::getStoryInfoKeys($storyids);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->mget($keys);
        
        $cacheData = array();
        $cacheIds = array();
        if (is_array($redisData)){
            foreach ($redisData as $oneredisdata){
                if (empty($oneredisdata)) {
                    continue;
                }
                $oneredisdata = json_decode($oneredisdata, true);
                $cacheIds[] = $oneredisdata['id'];
                $cacheData[$oneredisdata['id']] = $oneredisdata;
            }
        } else {
            $redisData = array();
        }
        $dbIds = array_diff($storyids, $cacheIds);
        $dbData = array();
        
        if(!empty($dbIds)) {
            $storyidstr = implode(",", $storyids);
            $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
            $sql = "SELECT * FROM {$this->table} WHERE `id` IN ($storyidstr)";
            $st = $db->prepare($sql);
            $st->execute();
            $tmpDbData = $st->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (!empty($tmpDbData)) {
                foreach ($tmpDbData as $onedbdata){
                    $onedbdata = $this->format_to_api($onedbdata);
                    $dbData[$onedbdata['id']] = $onedbdata;
                    $onekey = RedisKey::getStoryInfoKey($onedbdata['id']);
                    $redisobj->setex($onekey, 86400, json_encode($onedbdata));
                }
            }
        }
        
        $data = array();
        foreach($storyids as $storyid) {
            if(in_array($storyid, $dbIds)) {
                $data[$storyid] = @$dbData[$storyid];
            } else {
                $data[$storyid] = $cacheData[$storyid];
            }
        }
        
        return $data;
    }

    /**
     * 获取用户故事列表
     * @param I $uid
     * @param S $direction     up代表显示上边，down代表显示下边
     * @param I $startid       从某个id开始,默认为0表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getStoryList($albumid = 0, $direction = "down", $startid = 0, $len = 20, $uid = 0)
    {
        if (empty($len)) {
            $len = 20;
        }

        // 读缓存
        $key = RedisKey::getStoryListKey(func_get_args());
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if ($redisData) {
            return json_decode($redisData, true);
        }
        $where = "";
        $where .= " `status` = '1'";
        if (!empty($startid)) {
            if ($direction == "up") {
                $where .= " AND `id` > '{$startid}'";
            } else {
                $where .= " AND `id` < '{$startid}'";
            }
        }
        if ($albumid) {
        	$where .= " AND `album_id` = {$albumid} ";
        }
        
        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY `view_order` ASC , `id` DESC LIMIT {$len}";
        $st = $db->prepare($sql);
        $st->execute();
        $res = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($res)) {
            return array();
        } else {
            $storylist = array();
            foreach ($res as $k => $v) {
                $storylist[$k] = $this->format_to_api($v);
            }
            // 缓存
            $redisobj->setex($key, 300, json_encode($storylist));
            return $storylist;
        }
    }

	/**
     * 获取故事信息
     */
    public function get_story_info($story_id = 0, $filed = '')
    {
        if (!$story_id) {
            return array();
        }
        $r = array();
        // 读缓存
        $key = RedisKey::getStoryInfoKey($story_id);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if ($redisData) {
            $r = json_decode($redisData, true);
        } else {
            $where = "`id`={$story_id}";
            $sql = "select * from {$this->table}  where {$where} limit 1";

            $db = DbConnecter::connectMysql('share_story');
            $st = $db->query( $sql );
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $r  = $st->fetchAll();
            $r  = array_pop($r);
            // 缓存
            $redisobj->setex($key, 604800, json_encode($r));
        }

        
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
        // 清缓存
        $arr = explode("=", $where);
        if (isset($arr[1]) && $arr[1]) {
            $this->clearStoryCache(intval($arr[1]));
        }
        return true;
    }

	/**
	 * 获取专辑的故事列表
	 */
    public function get_album_story_list($album_id, $page = 1, $len = 50)
	{
        $story_list = array();
        $album_id = intval($album_id);
        $page = intval($page);
        $len = intval($len);
        if ($album_id > 0 && $page > 0 && $len > 0) {
            // 读缓存
            $key = RedisKey::getAlbumStoryListKey($album_id, $page, $len);
            $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
            $redisData = $redisobj->get($key);
            $offset = ($page - 1) * $len;
            $limit = " {$offset},{$len} ";
            if ($redisData) {
                $story_list = json_decode($redisData, true);
            } else {
                $story_list = $this->get_list("`album_id`='{$album_id}' and status=1 and `mediapath` != '' ", $limit, '', ' ORDER BY `view_order` ASC,`id` ASC ');
                // 缓存
                if ($story_list) {
                    $redisobj->setex($key, 86400, json_encode($story_list));
                }
            }
		}
		return $story_list;
	}

	// 格式化成接口数据
	public function format_to_api($story_info = array())
	{
        static $aliossobj = null;
        if (!$aliossobj) {
            $aliossobj = new AliOss();
        }
		if ($story_info['mediapath']) {
			$story_info['mediapath'] = $aliossobj->getMediaUrl($story_info['mediapath']);
		} else {
			//$story_info['mediapath'] = $story_info['source_audio_url'];
		}

		return $story_info;
	}

    // 清故事缓存
    public function clearStoryCache($storyId)
    {
        if (!$storyId) {
            return false;
        }
        $storyIdKey = RedisKey::getStoryInfoKey($storyId);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $storyInfo = $this->get_story_info($storyId);
        // 清除故事列表缓存
        if (isset($storyInfo['album_id']) && $storyInfo['album_id']) {
            $this->clearAlbumStoryListCache($storyInfo['album_id']);
        }
        $redisobj->delete($storyIdKey);
        return true;
    }

    // 清除故事列表缓存
    public function clearAlbumStoryListCache($album_id, $page = 1, $len = 10000)
    {
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $album_story_list_key = RedisKey::getAlbumStoryListKey($album_id, $page, $len);
        $redisobj->delete($album_story_list_key);
    }

    public function coverNotUploadOssCount()
    {
        $default_story_cover_list = "http://s1.xmcdn.com/wap/css/img/default/bg_player.jpg?v=20160202125906,http://s1.xmcdn.com/wap/css/img/default/sound.jpg";
        //SELECT * FROM `story` WHERE cover='' and find_in_set(`s_cover`,'http://s1.xmcdn.com/wap/css/img/default/bg_player.jpg?v=20160202125906,http://s1.xmcdn.com/wap/css/img/default/sound.jpg')=0 and `status` = 1 LIMIT 20
        $count = $this->get_total("cover='' and find_in_set(`s_cover`,'{$default_story_cover_list}')=0 and `status` = 1");
        return $count;
    }

    public function mediaNotUploadOssCount()
    {
        $count = $this->get_total("`mediapath`='' and `status`=1 AND `source_audio_url` !=''");
        return $count;
    }
}