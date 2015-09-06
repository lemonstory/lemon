<?php
class Listen extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $LISTEN_TABLE_NAME = 'user_listen';
	public $RECOMMEND_SAME_AGE_TABLE_NAME = 'recommend_same_age';
	
	public $CACHE_INSTANCE = 'user_listen';
	public $KVSTORE_INSTANCE = 'user_listen';
	
	public $AGE_TYPE_FIRST = 1; // 0-2岁
	public $AGE_TYPE_SECOND = 2; // 3-6岁
	public $AGE_TYPE_THIRD = 3; // 7-10岁
	public $AGE_TYPE_LIST = array(1, 2, 3);
	
	public $RECOMMEND_STATUS_ONLIINE = 1; // 推荐上线状态
	public $RECOMMEND_STATUS_OFFLINE = 2; // 推荐下线状态
	
	
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
	 * cron进程执行
	 * 添加同龄在听到推荐表
	 * 按照年龄段，将用户收听次数最多的专辑展示在首页
	 * @param I $babyagetype	当前用户的宝宝年龄段
	 * @param I $start			列表开始位置
	 * @param I $len			列表长度
	 * @return array
	 */
	public function cronSaveSameAgeToDb()
	{
		$start = 0;
		$len = 20;
		$addtime = date("Y-m-d H:i:s");
		
		$redisobj = AliRedisConnecter::connRedis($this->KVSTORE_INSTANCE);
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->RECOMMEND_SAME_AGE_TABLE_NAME} 
				(`albumid`, `agetype`, `order`, `status`, `addtime`) 
				VALUES (?, ?, ?, ?, ?)";
		
		foreach ($this->AGE_TYPE_LIST as $babyagetype) {
			$albumkey = RedisKey::getRankListenAlbumKey($babyagetype);
			$albumidlist = $redisobj->zRevRange($albumkey, $start, $len - 1);
			if (empty($albumidlist)) {
				continue;
			}
			
			foreach ($albumidlist as $albumid) {
				$st = $db->prepare($sql);
				$res = $st->execute(array($albumid, $babyagetype, 100, $this->RECOMMEND_STATUS_OFFLINE, $addtime));
				if (empty($res)) {
				    continue;
				}
			}
		}
		
		return true;
	}
	
	
	/**
	 * 收听用户排行列表
	 * 每周更新一次，收听故事的用户排行
	 * @param I $babyagetype	当前用户的宝宝年龄段
	 * @param I $startpos		列表开始位置
	 * @param I $len			列表长度
	 * @return array
	 */
	public function getRankListUserListen($babyagetype, $startpos = 0, $len = 20)
	{
		if (empty($babyagetype)) {
			$this->setError(ErrorConf::paramError());
			return array();
		}
		if ($startpos < 0) {
			$startpos = 0;
		}
		if ($len < 0 || $len > 500) {
			$len = 20;
		}
		
		$key = RedisKey::getRankListenUserKey($babyagetype);
		$redisobj = AliRedisConnecter::connRedis($this->KVSTORE_INSTANCE);
		$uidlist = $redisobj->zRevRange($key, $startpos, $len - 1);
		if (empty($uidlist)) {
			return array();
		}
		
		$userlist = array();
		// 批量获取用户信息
		$userobj = new User();
		$userlist = $userobj->getUserInfo($uidlist);
		
		return $userlist;
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
	 * @param I $babyagetype	宝宝年龄段类型
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
		
		// 更新收听的用户排行
		$listenuserkey = RedisKey::getRankListenUserKey($babyagetype);
		$redisObj = AliRedisConnecter::connRedis($this->KVSTORE_INSTANCE);
		$redisObj->zIncrBy($listenuserkey, 1, $uid);
		
		// 更新收听的专辑排行
		$listenalbumkey = RedisKey::getRankListenAlbumKey($babyagetype);
		$redisObj->zIncrBy($listenalbumkey, 1, $albumid);
		
		return true;
	}
	
	
	/**
	 * 用户取消收听故事
	 * @param I $uid
	 * @param I $albumid
	 * @param I $storyid    	故事Id
	 * @param I $babyagetype	宝宝年龄段类型
	 * @return boolean
	 */
	public function delUserListenStory($uid, $albumid, $storyid, $babyagetype)
	{
	    if (empty($uid) || empty($albumid) || empty($storyid) || empty($babyagetype)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "DELETE FROM {$this->LISTEN_TABLE_NAME} WHERE `uid` = ? AND `storyid` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uid, $storyid));
	    if (empty($res)) {
	        return false;
	    }
	    
	    // 更新用户的收听排行
	    $listenuserkey = RedisKey::getRankListenUserKey($babyagetype);
	    $redisObj = AliRedisConnecter::connRedis($this->KVSTORE_INSTANCE);
	    $redisObj->zIncrBy($listenuserkey, -1, $uid);
	    
	    $listenalbumkey = RedisKey::getRankListenAlbumKey($babyagetype);
		$redisObj->zIncrBy($listenalbumkey, 1, $albumid);
	    return true;
	}
}