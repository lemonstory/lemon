<?php
class Listen extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $LISTEN_RECORD_TABLE_NAME = 'listen_story';    // 用户收听故事记录
	public $LISTEN_ALBUM_TABLE_NAME = 'listen_album';     // 用户收听的故事，所属的专辑列表
	public $LISTEN_ALBUM_COUNT_TABLE_NAME = 'listen_album_count';  // 专辑被收听总数
	
	public $CACHE_INSTANCE = 'cache';
	public $CACHE_EXPIRE = 604800;
	public $RANK_INSTANCE = 'rank';
	
	
	/**
	 * 收听用户排行列表
	 * @param I $len			列表长度
	 * @return array
	 */
	public function getRankListUserListen($len = 20)
	{
		if ($len < 1) {
			$len = 20;
		}
		if ($len > 200) {
		    $len = 200;
		}
		
		$list = array();
		$rankkey = RedisKey::getRankListenUserKey();
		$redisobj = AliRedisConnecter::connRedis($this->RANK_INSTANCE);
		$list = $redisobj->zRevRange($rankkey, 0, $len - 1, true);
		
		return $list;
	}
	
	/**
	 * 获取用户的收听用户榜单排名，及排名最后更新时间
	 * @param I $uid
	 * @return array
	 */
	public function getUserListenRankNum($uid)
	{
	    if (empty($uid)) {
	        return array();
	    }
	    
	    $userranknum = 0;
	    if (!empty($uid)) {
	        $rankkey = RedisKey::getRankListenUserKey();
	        $redisobj = AliRedisConnecter::connRedis($this->RANK_INSTANCE);
	        $userranknum = $redisobj->zRevRank($rankkey, $uid);
	        if ($userranknum === false) {
	            // 不在排行榜内
	            $userranknum = 0;
	        } else {
	            // 索引从0开始，排名数自增1
	            $userranknum += 1;
	        }
	    }
	    $userrankuptime = date("Y-m-d H:i:s");
	    
	    return array("userranknum" => $userranknum, "userrankuptime" => $userrankuptime);
	}
	
	
	/**
	 * 分页获取当前uid或设备，收听的专辑列表
	 * @param I $uimid
	 * @param S $direction     up代表显示上边，down代表显示下边
	 * @param I $startalbumid  从某个专辑开始,默认为0表示从第一页获取
	 * @param I $len           获取长度
	 * @return array
	 */
	public function getUserAlbumListenList($uimid, $direction = "down", $startalbumid = 0, $len = 20)
	{
		if (empty($uimid)) {
		    $this->setError(ErrorConf::paramError());
			return array();
		}
		if (empty($len)) {
		    $len = 20;
		}
		if ($len > 100) {
		    $len = 100;
		}
		
		$where = " `uimid` = '{$uimid}'";
		if (!empty($startalbumid)) {
		    $startalbuminfo = $this->getUserListenAlbumInfo($uimid, $startalbumid);
		    $startuptime = $startalbuminfo['uptime'];
		    if (!empty($startuptime)) {
		        if ($direction == "up") {
		            $where .= " AND `uptime` > '{$startuptime}'";
		        } else {
		            $where .= " AND `uptime` < '{$startuptime}'";
		        }
		    }
		}
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->LISTEN_ALBUM_TABLE_NAME} WHERE {$where} ORDER BY `uptime` DESC LIMIT {$len}";
		$st = $db->prepare($sql);
		$st->execute();
		$list = $st->fetchAll(PDO::FETCH_ASSOC);
		if (empty($list)) {
			return array();
		} else {
		    return $list;
		}
	}
	
	
	/**
	 * 获取当前uid或设备，收听的专辑信息
	 * @param I $uimid
	 * @param I $albumid
	 * @return array
	 */
	public function getUserListenAlbumInfo($uimid, $albumid)
	{
	    if (empty($uimid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $key = RedisKey::getUserListenAlbumInfoKey($uimid, $albumid);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    $redisData = $redisobj->get($key);
	    if (empty($redisData)) {
    	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
    	    $sql = "SELECT * FROM {$this->LISTEN_ALBUM_TABLE_NAME} WHERE `uimid` = ? and `albumid` = ?";
    	    $st = $db->prepare($sql);
    	    $st->execute(array($uimid, $albumid));
    	    $dbData = $st->fetch(PDO::FETCH_ASSOC);
    	    $db = null;
    	    if (empty($dbData)) {
    	        return array();
    	    }
    	    
    	    $redisobj->setex($key, $this->CACHE_EXPIRE, serialize($dbData));
    	    return $dbData;
	    } else {
	        return unserialize($redisData);
	    }
	}
	
	
	/**
	 * 获取uid或设备，指定专辑下收听过的故事列表
	 * @param I $uimid
	 * @param I $albumid
	 * @return array
	 */
	/* public function getUserListenStoryListByAlbumId($uimid, $albumids)
	{
	    if (empty($uimid) || empty($albumids)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    if (!is_array($albumids)) {
	        $albumids = array($albumids);
	    }
	    $albumidstr = implode(",", $albumids);
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->LISTEN_RECORD_TABLE_NAME} WHERE `uimid` = ? and `albumid` IN ($albumidstr) ORDER BY `uptime` DESC";
	    $st = $db->prepare($sql);
	    $st->execute(array($uimid));
	    $res = $st->fetchAll(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        return array();
	    } else {
	        return $res;
	    }
	} */
	
	/**
	 * 获取uid或设备收听的故事记录
	 * @param I $uimid
	 * @param I $storyid
	 * @return array
	 */
	/* public function getUserListenStoryInfo($uimid, $storyid)
	{
	    if (empty($uimid) || empty($storyid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $key = RedisKey::getUserListenStoryInfoKey($uimid, $storyid);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    $redisData = $redisobj->get($key);
	    if (empty($redisData)) {
    	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
    	    $sql = "SELECT * FROM {$this->LISTEN_RECORD_TABLE_NAME} WHERE `uimid` = ? and `storyid` = ?";
    	    $st = $db->prepare($sql);
    	    $st->execute(array($uimid, $storyid));
    	    $dbData = $st->fetch(PDO::FETCH_ASSOC);
    	    $db = null;
    	    if (empty($dbData)) {
    	        return array();
    	    }
    	    
    	    $redisobj->setex($key, $this->CACHE_EXPIRE, serialize($dbData));
    	    return $dbData;
	    } else {
	        return unserialize($redisData);
	    }
	} */
	
	
	/**
	 * 批量获取专辑的收听总量
	 * @param A $albumids    
	 * @return array
	 */
	public function getAlbumListenNum($albumids)
	{
	    if (empty($albumids)) {
	        return array();
	    }
	    if (!is_array($albumids)) {
	        $albumids = array($albumids);
	    }
	    
	    $keys = RedisKey::getAlbumListenCountKeys($albumids);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    $redisData = $redisobj->mget($keys);
	     
	    $cacheData = array();
	    $cacheIds = array();
	    if (is_array($redisData)){
	        foreach ($redisData as $oneredisdata){
	            if (empty($oneredisdata)) {
	                continue;
	            }
	            $oneredisdata = unserialize($oneredisdata);
	            $cacheIds[] = $oneredisdata['albumid'];
	            $cacheData[$oneredisdata['albumid']] = $oneredisdata;
	        }
	    } else {
	        $redisData = array();
	    }
	     
	    $dbIds = array_diff($albumids, $cacheIds);
	    $dbData = array();
	    
	    if(!empty($dbIds)) {
    	    $albumidstr = implode(",", $albumids);
    	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
    	    $sql = "SELECT * FROM {$this->LISTEN_ALBUM_COUNT_TABLE_NAME} WHERE `albumid` IN ($albumidstr)";
    	    $st = $db->prepare($sql);
    	    $st->execute();
    	    $tmpDbData = $st->fetchAll(PDO::FETCH_ASSOC);
    	    $db = null;
    	    if (!empty($tmpDbData)) {
    	        foreach ($tmpDbData as $onedbdata){
    	            $dbData[$onedbdata['albumid']] = $onedbdata;
    	            $onekey = RedisKey::getAlbumListenCountKey($onedbdata['albumid']);
    	            $redisobj->setex($onekey, $this->CACHE_EXPIRE, serialize($onedbdata));
    	        }
    	    }
	    }
	    
	    $data = array();
	    foreach($albumids as $albumid) {
	        if(in_array($albumid, $dbIds)) {
	            $data[$albumid] = @$dbData[$albumid];
	        } else {
	            $data[$albumid] = $cacheData[$albumid];
	        }
	    }
	    
	    return $data;
	}
	
	
	/**
	 * 延迟队列执行，uid或设备收听播放某个故事
	 * @param I $uimid
	 * @param I $uid            未登录收听为0
	 * @param I $albumid    	专辑Id
	 * @param I $storyid		故事id
	 * @param I $babyagetype	收听用户的宝宝年龄段类型，未登录为0
	 * @return boolean
	 */
	public function addUserListenStory($uimid, $uid, $albumid, $storyid, $babyagetype)
	{
		if (empty($uimid) || empty($albumid) || empty($storyid)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		
		// 收听故事记录，若重复收听则更新时间
		$this->addUserListenStoryDb($uimid, $storyid, $albumid);
		
		// 收听专辑记录，若重复收听则更新时间
		$this->addUserListenAlbumDb($uimid, $albumid);
	    
		// 更新专辑的收听次数
	    $this->addAlbumListenCountDb($albumid);
	    
	    if (!empty($babyagetype)) {
	        // list: 更新某个年龄段的专辑收听次数排行队列
	        $listenalbumkey = RedisKey::getRankListenAlbumKey($babyagetype);
	        $redisObj = AliRedisConnecter::connRedis($this->RANK_INSTANCE);
	        $redisObj->zIncrBy($listenalbumkey, 1, $albumid);
	    }
	    
		if (!empty($uid)) {
		    // 登录账户：更新用户收听总数
		    $this->addUserListenCount($uid);
		}
		
		//$this->clearUserListenStoryInfoCache($uimid, $storyid);
		$this->clearUserListenAlbumInfoCache($uimid, $albumid);
		$this->clearAlbumListenCountCache($albumid);
		return true;
	}
	
	
	// 删除用户收听的专辑信息cache
	public function clearUserListenAlbumInfoCache($uimid, $albumid)
	{
	    $key = RedisKey::getUserListenAlbumInfoKey($uimid, $albumid);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    return $redisobj->delete($key);
	}
	// 删除用户收听的故事信息cache
	public function clearUserListenStoryInfoCache($uimid, $storyid)
	{
	    $key = RedisKey::getUserListenStoryInfoKey($uimid, $storyid);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    return $redisobj->delete($key);
	}
	// 删除专辑收听总数cache
	public function clearAlbumListenCountCache($albumid)
	{
	    $key = RedisKey::getAlbumListenCountKey($albumid);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    return $redisobj->delete($key);
	}
	
	
	/**
	 * 添加uid或设备收听专辑记录
	 * @param I $uimid
	 * @param I $albumid
	 * @return boolean
	 */
	private function addUserListenAlbumDb($uimid, $albumid)
	{
	    if (empty($uimid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    
	    $addtime = date("Y-m-d H:i:s");
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "REPLACE INTO {$this->LISTEN_ALBUM_TABLE_NAME} (`uimid`, `albumid`, `uptime`) VALUES (?, ?, ?)";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uimid, $albumid, time()));
	    if (empty($res)) {
	        return false;
	    }
	    return true;
	}
	
	// 添加uid或设备收听故事记录
	private function addUserListenStoryDb($uimid, $storyid, $albumid)
	{
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "REPLACE INTO {$this->LISTEN_RECORD_TABLE_NAME} (`uimid`, `storyid`, `albumid`, `uptime`) VALUES (?, ?, ?, ?)";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uimid, $storyid, $albumid, time()));
	    if (empty($res)) {
	        return false;
	    }
	    return true;
	}
	
	
	/**
	 * 统计用户的收听总数，排行榜不统计设备的收听总数
	 * @param I $uid
	 * @return boolean
	 */
	private function addUserListenCount($uid)
	{
	    if (empty($uid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    
	    $rankkey = RedisKey::getRankListenUserKey();
	    $redisobj = AliRedisConnecter::connRedis($this->RANK_INSTANCE);
	    $redisobj->zIncrBy($rankkey, 1, $uid);
	    return true;
	}
	
    /**
     * 统计专辑的收听总数
     * Redis:更新某个年龄段的专辑收听次数排行队列，用于同龄在听的后台自动生成数据cron
     * @param I $albumid
     * @return boolean
     */
    private function addAlbumListenCountDb($albumid)
	{
	    if (empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    
	    $selectsql = "SELECT * FROM `{$this->LISTEN_ALBUM_COUNT_TABLE_NAME}` WHERE `albumid` = ?";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute(array($albumid));
	    $selectres = $selectst->fetch(PDO::FETCH_ASSOC);
	    if (empty($selectres)) {
	        $sql = "INSERT INTO `{$this->LISTEN_ALBUM_COUNT_TABLE_NAME}` (`albumid`, `num`) VALUES ('{$albumid}', 1)";
	    } else {
	        $sql = "UPDATE `{$this->LISTEN_ALBUM_COUNT_TABLE_NAME}` SET `num` = `num` + 1 WHERE `albumid` = '{$albumid}'";
	    }
		$st = $db->prepare($sql);
		$numres = $st->execute();
		if (empty($numres)) {
		    return false;
		}
		
		return true;
	}
}