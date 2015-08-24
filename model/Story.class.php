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
        	return $r;
        }
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
	 * 获取专辑的故事列表
	 */
	public function get_album_story_list($album_id = 0)
	{
		if (!$album_id) {
			return array();
		}
		$new_list   = array();
		$story_list = $this->get_list("`id`={$album_id}");
		foreach ($story_list as $k => $v) {
			if (!$v['cover']) {
				$v['cover'] = $v['s_cover'];
			}
			if (!$v['audio_url']) {
				$v['audio_url'] = $v['source_audio_url'];
			}
			unset($v['s_cover'], $v['source_audio_url']);
			$new_list[] = $v;
		}
		return $new_list;
	}

	/**
	 * 格式化成接口数据
	 */
	public function format_to_api($story_info = array())
	{
		$info = array();
		$info['albumid'] = '';
		$info['title'] = '';
		$info['intro'] = '';
		$info['cover'] = '';
		$info['times'] = '';
		$info['file_size'] = '';
	}
}