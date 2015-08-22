<?php
class Listen extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $LISTEN_TABLE_NAME = 'user_listen';
	public $CACHE_INSTANCE = 'user_listen';
	public $KVSTORE_INSTANCE = 'main';
	
	/**
	 * 同龄在听排行列表
	 * 按照年龄段，将用户收听次数最多的专辑展示在首页
	 * @param I $babyagetype	当前用户的宝宝年龄段
	 * @param I $start			列表开始位置
	 * @param I $len			列表长度
	 * @return array
	 */
	public function getRankListSameAgeListen($babyagetype, $start = 0, $len = 20)
	{
		if (empty($babyagetype)) {
			$this->setError(ErrorConf::paramError());
			return array();
		}
		if ($start < 0) {
			$start = 0;
		}
		if ($len < 0 || $len > 200) {
			$len = 20;
		}
		
		$albumkey = RedisKey::getRankListenAlbumKey($babyagetype);
		$redisobj = AliRedisConnecter::connRedis($this->KVSTORE_INSTANCE);
		$albumidlist = $redisobj->zRevRange($albumkey, $start, $len - 1);
		if (empty($albumidlist)) {
			return array();
		}
		
		$albumlist = array();
		// 批量获取专辑信息
		
		
		return $albumlist;
	}
	
	
	/**
	 * 收听用户排行列表
	 * 每周更新一次，收听故事的用户排行
	 * @param I $babyagetype	当前用户的宝宝年龄段
	 * @param I $start			列表开始位置
	 * @param I $len			列表长度
	 * @return array
	 */
	public function getRankListUserListen($babyagetype, $start = 0, $len = 20)
	{
		if (empty($babyagetype)) {
			$this->setError(ErrorConf::paramError());
			return array();
		}
		if ($start < 0) {
			$start = 0;
		}
		if ($len < 0 || $len > 200) {
			$len = 20;
		}
		
		$key = RedisKey::getRankListenUserKey($babyagetype);
		$redisobj = AliRedisConnecter::connRedis($this->KVSTORE_INSTANCE);
		$uidlist = $redisobj->zRevRange($key, $start, $len - 1);
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
	 * @return array
	 */
	public function getUserListenList($uid)
	{
		if (empty($uid)) {
		    $this->setError(ErrorConf::paramError());
			return array();
		}
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->LISTEN_TABLE_NAME} WHERE `uid` = ?";
		$st = $db->prepare($sql);
		$st->execute(array($uid));
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