<?php

class Story extends ModelBase
{

	private $table = 'story';

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
        if (is_array($id)) {
            $idarr = $id;
        } else {
            $idarr = array($id);
        }
        $storylist = array();
        $fav = new Fav();
        $db = DbConnecter::connectMysql('share_story');
        foreach($idarr as $k => $v) {
            if (isset($storylist[$v])) {
                continue;
            }
            $sql = "select * from {$this->table}  where `id`='{$v}' limit 1";
            $st = $db->query( $sql );
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $r  = $st->fetchAll();
            $r  = array_pop($r);
            if ($r) {
                $storylist[$r['id']] = $this->format_to_api($r);
            }
        }
        return $storylist;
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
        // if (empty($uid)) {
        //     $this->setError(ErrorConf::paramError());
        //     return array();
        // }
        if (empty($len)) {
            $len = 20;
        }
        
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
        // $where .= " `uid` = '{$uid}'";
        
        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY `id` DESC LIMIT {$len}";
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
        $where = "`id`={$story_id}";
        $sql = "select * from {$this->table}  where {$where} limit 1";

        $db = DbConnecter::connectMysql('share_story');
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
        return $this->format_to_api($r);
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
        echo $sql;echo "\n";
        $st = $db->query($sql);
        unset($tmp_data);
        return true;
    }

	/**
	 * 获取专辑的故事列表
	 */
	public function get_album_story_list($album_id = 0)
	{
		if (!$album_id) {
			return array();
		}
		$new_list   = array();
		$story_list = $this->get_list("`album_id`={$album_id}");
		foreach ($story_list as $k => $v) {
			$new_list[] = $this->format_to_api($v);
		}
		return $new_list;
	}

	// 格式化成接口数据
	public function format_to_api($story_info = array())
	{
        if (!$story_info['cover']) {
			$story_info['cover'] = $story_info['s_cover'];
		}

		if ($story_info['mediapath']) {
            $aliossobj = new AliOss();
			$story_info['mediapath'] = $aliossobj->getMediaUrl($story_info['mediapath']);
		} else {
			$story_info['mediapath'] = $story_info['source_audio_url'];
		}

		return $story_info;
	}
}