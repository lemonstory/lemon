<?php
class Listen extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $LISTEN_TABLE_NAME = 'user_listen';
	public $LISTEN_USER_NUM_TABLE_NAME = 'user_listen_num';
	public $LISTEN_ALBUM_NUM_TABLE_NAME = 'album_listen_num';
	public $RECOMMEND_SAME_AGE_TABLE_NAME = 'recommend_same_age';
	
	public $CACHE_INSTANCE = 'user_listen';
	public $KVSTORE_INSTANCE = 'user_listen';
	
	/**
	 * 获取同龄在听的上线列表
	 * 按照年龄段，以及用户收听次数最多的专辑排序
	 * @param I $babyagetype
	 * @param I $len
	 * @return array
	 */
	public function getSameAgeListenList($babyagetype = 0, $len = 20)
	{
		if (!empty($babyagetype) && !in_array($babyagetype, $this->AGE_TYPE_LIST)) {
			$this->setError(ErrorConf::paramError());
			return array();
		}
		$start = 0;
		if (empty($len)) {
			$len = 20;
		}
		
		$status = $this->RECOMMEND_STATUS_ONLIINE; // 已上线状态
		$where = "`status` = '{$status}'";
		if (!empty($babyagetype)) {
			$where .= " AND `agetype` = '{$babyagetype}'";
		}
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->RECOMMEND_SAME_AGE_TABLE_NAME} 
				WHERE {$where} ORDER BY `ordernum` DESC LIMIT $len";
		$st = $db->prepare($sql);
		$st->execute();
		$list = $st->fetchAll(PDO::FETCH_ASSOC);
		if (empty($list)) {
			return array();
		}
		return $list;
	}
	
	
	/**
	 * 收听用户排行列表
	 * 每周更新一次，收听故事的用户排行
	 * @param I $len			列表长度
	 * @return array
	 */
	public function getRankListUserListen($len = 20)
	{
		if (empty($babyagetype)) {
			$this->setError(ErrorConf::paramError());
			return array();
		}
		if (empty($len)) {
			$len = 20;
		}
		
		/* $key = RedisKey::getRankListenUserKey($babyagetype);
		$redisobj = AliRedisConnecter::connRedis($this->KVSTORE_INSTANCE);
		$uidlist = $redisobj->zRevRange($key, $startpos, $len - 1); */
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->LISTEN_USER_NUM_TABLE_NAME} ORDER BY `num` DESC LIMIT {$len}";
		$st = $db->prepare($sql);
		$st->execute(array($uid, $storyid));
		$reslist = $st->fetchAll(PDO::FETCH_ASSOC);
		if (empty($reslist)) {
			return array();
		}
		return $reslist;
	}
	
	
	/**
	 * 获取用户收听列表
	 * @param I $uid
	 * @param S $direction     up代表显示上边，down代表显示下边
	 * @param I $startid       从某个id开始,默认为0表示从第一页获取
	 * @param I $len           获取长度
	 * @return array
	 */
	public function getUserListenList($uid, $direction, $startid, $len)
	{
		if (empty($uid)) {
		    $this->setError(ErrorConf::paramError());
			return array();
		}
		if (empty($len)) {
		    $len = 20;
		}
		
		$where = "";
		if (!empty($startid)) {
		    if ($direction == "up") {
		        $where .= " `id` > '{$startid}' AND";
		    } else {
		        $where .= " `id` < '{$startid}' AND";
		    }
		}
		$where .= " `uid` = '{$uid}'";
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->LISTEN_TABLE_NAME} WHERE {$where} ORDER BY `addtime` DESC LIMIT {$len}";
		$st = $db->prepare($sql);
		$st->execute();
		$res = $st->fetchAll(PDO::FETCH_ASSOC);
		if (empty($res)) {
			return array();
		} else {
		    $list = array();
		    foreach ($res as $value) {
		        $list[$value['id']] = $value;
		    }
			return $list;
		}
	}
	
	
	/**
	 * 获取用户收听的故事记录
	 * @param I $uid
	 * @param I $storyid
	 * @return array
	 */
	public function getUserListenInfoByStoryId($uid, $storyid)
	{
	    if (empty($uid) || empty($storyid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->LISTEN_TABLE_NAME} WHERE `uid` = ? and `storyid` = ?";
	    $st = $db->prepare($sql);
	    $st->execute(array($uid, $storyid));
	    $res = $st->fetch(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        return array();
	    } else {
	        return $res;
	    }
	}
	
	
	/**
	 * 用户添加收听故事
	 * @param I $uid
	 * @param I $albumid    	专辑Id
	 * @param I $storyid		故事id
	 * @param I $babyagetype	收听用户的宝宝年龄段类型
	 * @return boolean
	 */
	public function addUserListenStory($uid, $albumid, $storyid, $babyagetype)
	{
		if (empty($uid) || empty($albumid) || empty($storyid) || empty($babyagetype)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		
		$addtime = date("Y-m-d H:i:s");
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->LISTEN_TABLE_NAME} 
			(`uid`, `albumid`, `storyid`, `addtime`) 
			VALUES (?, ?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uid, $storyid, $addtime));
		if (empty($res)) {
		    return false;
		}
		
		// 更新用户收听总数
		$this->addUserListenCount($uid);
		// 更新不同年龄段的，专辑的收听次数
		$this->addAlbumListenCount($albumid, $babyagetype);
		return true;
	}
	
	
	/**
	 * 更新用户收听总数
	 * @param I $uid
	 * @return boolean
	 */
	private function addUserListenCount($uid)
	{
	    if (empty($uid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $tablename = 'user_listen_num';
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    
	    $selectsql = "SELECT * FROM `{$tablename}` WHERE `uid` = ?";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute(array($uid));
	    $selectres = $selectst->fetch(PDO::FETCH_ASSOC);
	    if (empty($selectres)) {
	        $sql = "INSERT INTO `{$tablename}` (`uid`, `num`) VALUES ('{$uid}', 1)";
	    } else {
	        $sql = "UPDATE `{$tablename}` SET `num` = `num` + 1 WHERE `uid` = '{$uid}'";
	    }
		$st = $db->prepare($sql);
		$usernumres = $st->execute();
		if (empty($usernumres)) {
		    return false;
		}
		return true;
	}
	
    /**
     * 不同年龄段的，专辑的收听总数
     * @param I $albumid
     * @param I $babyagetype
     * @return boolean
     */
    private function addAlbumListenCount($albumid, $babyagetype)
	{
	    if (empty($albumid) || empty($babyagetype)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $tablename = 'album_listen_num';
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    
	    $selectsql = "SELECT * FROM `{$tablename}` WHERE `albumid` = ?";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute(array($uid));
	    $selectres = $selectst->fetch(PDO::FETCH_ASSOC);
	    if (empty($selectres)) {
	        $sql = "INSERT INTO `{$tablename}` (`albumid`, `agetype`, `num`) VALUES ('{$albumid}', '{$babyagetype}', 1)";
	    } else {
	        $sql = "UPDATE `{$tablename}` SET `num` = `num` + 1 WHERE `albumid` = '{$albumid}'";
	    }
		$st = $db->prepare($sql);
		$numres = $st->execute();
		if (empty($numres)) {
		    return false;
		}
		
		// cache: 某个年龄段的专辑收听次数排行队列
		$listenalbumkey = RedisKey::getRankListenAlbumKey($babyagetype);
		$redisObj->zIncrBy($listenalbumkey, 1, $albumid);
		return true;
	}
}