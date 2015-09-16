<?php
class Listen extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $LISTEN_RECORD_TABLE_NAME = 'user_listen_record';
	public $LISTEN_ALBUM_TABLE_NAME = 'user_listen_album';
	public $LISTEN_USER_COUNT_TABLE_NAME = 'user_listen_count';
	public $LISTEN_ALBUM_COUNT_TABLE_NAME = 'album_listen_count';
	public $RECOMMEND_SAME_AGE_TABLE_NAME = 'recommend_same_age';
	
	public $CACHE_INSTANCE = 'cache';
	
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
		if ($len > 50) {
		    $len = 50;
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
		if ($len > 50) {
		    $len = 50;
		}
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->LISTEN_USER_COUNT_TABLE_NAME} ORDER BY `num` DESC LIMIT {$len}";
		$st = $db->prepare($sql);
		$st->execute(array($uid, $storyid));
		$reslist = $st->fetchAll(PDO::FETCH_ASSOC);
		if (empty($reslist)) {
			return array();
		}
		return $reslist;
	}
	
	
	/**
	 * 分页获取用户收听的专辑列表
	 * @param I $uid
	 * @param S $direction     up代表显示上边，down代表显示下边
	 * @param I $startalbumid  从某个专辑开始,默认为0表示从第一页获取
	 * @param I $len           获取长度
	 * @return array
	 */
	public function getUserAlbumListenList($uid, $direction = "down", $startalbumid = 0, $len = 20)
	{
		if (empty($uid)) {
		    $this->setError(ErrorConf::paramError());
			return array();
		}
		if (empty($len)) {
		    $len = 20;
		}
		if ($len > 100) {
		    $len = 100;
		}
		
		$where = " `uid` = '{$uid}'";
		if (!empty($startalbumid)) {
		    $startalbuminfo = current($this->getUserListenAlbumInfo($uid, $startalbumid));
		    $startuptime = $startalbuminfo['uptime'];
		    if (!empty($startuptime)) {
		        if ($direction == "up") {
		            $where .= " `uptime` > '{$startuptime}' AND";
		        } else {
		            $where .= " `uptime` < '{$startuptime}' AND";
		        }
		    }
		}
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->LISTEN_RECORD_TABLE_NAME} WHERE {$where} ORDER BY `uptime` DESC LIMIT {$len}";
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
	 * 获取用户收听的专辑信息
	 * @param I $uid
	 * @param I $albumid
	 * @return array
	 */
	public function getUserListenAlbumInfo($uid, $albumid)
	{
	    if (empty($uid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	     
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->LISTEN_ALBUM_TABLE_NAME} WHERE `uid` = ? and `albumid` = ?";
	    $st = $db->prepare($sql);
	    $st->execute(array($uid, $albumid));
	    $res = $st->fetch(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        return array();
	    } else {
	        return $res;
	    }
	}
	
	
	/**
	 * 获取用户，指定专辑下收听过的故事列表
	 * @param I $uid
	 * @param I $albumid
	 * @return array
	 */
	public function getUserListenStoryListByAlbumId($uid, $albumids)
	{
	    if (empty($uid) || empty($albumids)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    if (!is_array($albumids)) {
	        $albumids = array($albumids);
	    }
	    $albumidstr = implode(",", $albumids);
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->LISTEN_RECORD_TABLE_NAME} WHERE `uid` = ? and `albumid` IN ($albumidstr) ORDER BY `uptime` DESC";
	    $st = $db->prepare($sql);
	    $st->execute(array($uid));
	    $res = $st->fetch(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        return array();
	    } else {
	        return $res;
	    }
	}
	
	/**
	 * 获取用户收听的故事记录
	 * @param I $uid
	 * @param I $storyid
	 * @return array
	 */
	public function getUserListenStoryInfo($uid, $storyid)
	{
	    if (empty($uid) || empty($storyid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->LISTEN_RECORD_TABLE_NAME} WHERE `uid` = ? and `storyid` = ?";
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
	    
	    $albumidstr = implode(",", $albumids);
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->LISTEN_ALBUM_COUNT_TABLE_NAME} WHERE `albumid` IN ($albumidstr)";
	    $st = $db->prepare($sql);
	    $st->execute();
	    $res = $st->fetchAll(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        return array();
	    } else {
	        $list = array();
	        foreach ($res as $value) {
	            $list[$value['albumid']] = $value;
	        }
	        return $list;
	    }
	}
	
	
	/**
	 * 延迟队列执行，用户收听播放某个故事
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
		$sql = "REPLACE INTO {$this->LISTEN_RECORD_TABLE_NAME} 
			(`uid`, `storyid`, `albumid`, `uptime`) 
			VALUES (?, ?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uid, $storyid, $albumid, time()));
		if (empty($res)) {
		    return false;
		}
		
		// 收听专辑记录
		$this->addUserListenAlbum($uid, $albumid);
		// 更新用户收听总数
		$this->addUserListenCount($uid);
		// 更新不同年龄段的，专辑的收听次数
		$this->addAlbumListenCount($albumid, $babyagetype);
		return true;
	}
	
	
	/**
	 * 用户收听专辑记录
	 * @param I $uid
	 * @param I $albumid
	 * @return boolean
	 */
	private function addUserListenAlbum($uid, $albumid)
	{
	    if (empty($uid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    
	    $addtime = date("Y-m-d H:i:s");
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "REPLACE INTO {$this->LISTEN_ALBUM_TABLE_NAME}
    	    (`uid`, `albumid`, `uptime`)
    	    VALUES (?, ?, ?)";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uid, $albumid, time()));
	    if (empty($res)) {
	        return false;
	    }
	    return true;
	}
	
	
	/**
	 * 统计用户的收听总数
	 * @param I $uid
	 * @return boolean
	 */
	private function addUserListenCount($uid)
	{
	    if (empty($uid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    
	    $selectsql = "SELECT * FROM `{$this->LISTEN_USER_COUNT_TABLE_NAME}` WHERE `uid` = ?";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute(array($uid));
	    $selectres = $selectst->fetch(PDO::FETCH_ASSOC);
	    if (empty($selectres)) {
	        $sql = "INSERT INTO `{$this->LISTEN_USER_COUNT_TABLE_NAME}` (`uid`, `num`) VALUES ('{$uid}', 1)";
	    } else {
	        $sql = "UPDATE `{$this->LISTEN_USER_COUNT_TABLE_NAME}` SET `num` = `num` + 1 WHERE `uid` = '{$uid}'";
	    }
		$st = $db->prepare($sql);
		$usernumres = $st->execute();
		if (empty($usernumres)) {
		    return false;
		}
		return true;
	}
	
    /**
     * 统计不同年龄段的，专辑的收听总数
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
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    
	    $selectsql = "SELECT * FROM `{$this->LISTEN_ALBUM_COUNT_TABLE_NAME}` WHERE `albumid` = ?";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute(array($albumid));
	    $selectres = $selectst->fetch(PDO::FETCH_ASSOC);
	    if (empty($selectres)) {
	        $sql = "INSERT INTO `{$this->LISTEN_ALBUM_COUNT_TABLE_NAME}` (`albumid`, `agetype`, `num`) VALUES ('{$albumid}', '{$babyagetype}', 1)";
	    } else {
	        $sql = "UPDATE `{$this->LISTEN_ALBUM_COUNT_TABLE_NAME}` SET `num` = `num` + 1 WHERE `albumid` = '{$albumid}'";
	    }
		$st = $db->prepare($sql);
		$numres = $st->execute();
		if (empty($numres)) {
		    return false;
		}
		
		// cache: 某个年龄段的专辑收听次数排行队列
		$listenalbumkey = RedisKey::getRankListenAlbumKey($babyagetype);
		$redisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
		$redisObj->zIncrBy($listenalbumkey, 1, $albumid);
		return true;
	}
}