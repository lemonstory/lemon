<?php

class Category extends ModelBase 
{

	private $table = 'category';

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
	public function get_list($where = '', $filed = '', $limit = '')
	{
		$db = DbConnecter::connectMysql('share_story');
		if ($limit) {
			$sql = "select * from {$this->table}  where {$where} limit {$limit}";
		} else {
			$sql = "select * from {$this->table}  where {$where} ";
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
     * 获取某字段值
     */
    public function get_filed_value($field = 's_cover', $value = '', $need_filed = '')
    {
        if (!$field) {
            return array();
        }
        $where = "`{$field}`='{$value}'";
        $sql = "select * from {$this->table}  where {$where} limit 1";

        $db = DbConnecter::connectMysql('share_story');
        $st = $db->query( $sql );
        if (!$st) {
        	echo $sql;exit;
        }
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r  = $st->fetchAll();
        $r  = array_pop($r);
        if (isset($r[$need_filed]) && $r[$need_filed]) {
            return $r[$need_filed];
        } else {
            return '';
        }
    }
}