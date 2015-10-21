<?php
class Listen extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $LISTEN_RECORD_TABLE_NAME = 'listen_story';    // 用户收听故事记录
	public $LISTEN_ALBUM_TABLE_NAME = 'listen_album';     // 用户收听的故事，所属的专辑列表
	public $LISTEN_ALBUM_COUNT_TABLE_NAME = 'listen_album_count';  // 专辑被收听总数
	public $RECOMMEND_SAME_AGE_TABLE_NAME = 'recommend_same_age';  // 同龄在听推荐表
	
	public $CACHE_INSTANCE = 'cache';
	public $RANK_INSTANCE = 'rank';
	
	/**
	 * 获取同龄在听的上线列表
	 * 按照年龄段，以及用户收听次数最多的专辑排序
	 * @param I $babyagetype
	 * @param S $direction     up代表显示上边，down代表显示下边
	 * @param I $startalbumid  从某个albumid开始,默认为0表示从第一页获取
	 * @param I $len           获取长度
	 * @return array
	 */
	public function getSameAgeListenList($babyagetype = 0, $direction = "down", $startalbumid = 0, $len = 20)
	{
		if (!empty($babyagetype) && !in_array($babyagetype, $this->AGE_TYPE_LIST)) {
			$this->setError(ErrorConf::paramError());
			return array();
		}
		if (empty($len)) {
			$len = 20;
		}
		if ($len > 50) {
		    $len = 50;
		}
		
		$where = "";
		if (!empty($startalbumid)) {
		    if ($direction == "up") {
		        $where .= " `albumid` > '{$startalbumid}' AND";
		    } else {
		        $where .= " `albumid` < '{$startalbumid}' AND";
		    }
		}
		
		$status = $this->RECOMMEND_STATUS_ONLIINE; // 已上线状态
		$where .= " `status` = '{$status}'";
		if (!empty($babyagetype)) {
			$where .= " AND `agetype` = '{$babyagetype}'";
		}
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->RECOMMEND_SAME_AGE_TABLE_NAME} WHERE {$where} ORDER BY `ordernum` DESC LIMIT $len";
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
	 * @param I $len			列表长度
	 * @return array
	 */
	public function getRankListUserListen($len = 20, $uid = 0)
	{
		if (empty($len)) {
			$len = 20;
		}
		if ($len > 200) {
		    $len = 200;
		}
		
		$list = array();
		$rankkey = RedisKey::getRankListenUserKey();
		$redisobj = AliRedisConnecter::connRedis($this->RANK_INSTANCE);
		$list = $redisobj->zRevRange($rankkey, 0, 19, true);
		
		$userranknum = 0;
		if (!empty($uid) && !empty($list)) {
		    $userranknum = $redisobj->zRevRank($rankkey, $uid) + 1;
		}
		$userrankuptime = date("Y-m-d H:i:s");
		
		return array("list" => $list, "userranknum" => $userranknum, "userrankuptime" => $userrankuptime);
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
		    $startalbuminfo = current($this->getUserListenAlbumInfo($uimid, $startalbumid));
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
		//$sql = "SELECT * FROM {$this->LISTEN_RECORD_TABLE_NAME} WHERE {$where} ORDER BY `uptime` DESC LIMIT {$len}";
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
	     
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->LISTEN_ALBUM_TABLE_NAME} WHERE `uimid` = ? and `albumid` = ?";
	    $st = $db->prepare($sql);
	    $st->execute(array($uimid, $albumid));
	    $res = $st->fetch(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        return array();
	    } else {
	        return $res;
	    }
	}
	
	
	/**
	 * 获取uid或设备，指定专辑下收听过的故事列表
	 * @param I $uimid
	 * @param I $albumid
	 * @return array
	 */
	public function getUserListenStoryListByAlbumId($uimid, $albumids)
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
	}
	
	/**
	 * 获取uid或设备收听的故事记录
	 * @param I $uimid
	 * @param I $storyid
	 * @return array
	 */
	public function getUserListenStoryInfo($uimid, $storyid)
	{
	    if (empty($uimid) || empty($storyid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->LISTEN_RECORD_TABLE_NAME} WHERE `uimid` = ? and `storyid` = ?";
	    $st = $db->prepare($sql);
	    $st->execute(array($uimid, $storyid));
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
		
		$addtime = date("Y-m-d H:i:s");
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "REPLACE INTO {$this->LISTEN_RECORD_TABLE_NAME} 
			(`uimid`, `storyid`, `albumid`, `uptime`) 
			VALUES (?, ?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uimid, $storyid, $albumid, time()));
		if (empty($res)) {
		    return false;
		}
		
		// 收听专辑记录，若重复收听则更新时间
		$this->addUserListenAlbum($uimid, $albumid);
	    // 更新专辑的收听次数
	    $this->addAlbumListenCount($albumid, $babyagetype);
		
		if (!empty($uid)) {
		    // 登录账户：更新用户收听总数
		    $this->addUserListenCount($uid);
		}
		return true;
	}
	
	
	/**
	 * 添加uid或设备收听专辑记录
	 * @param I $uimid
	 * @param I $albumid
	 * @return boolean
	 */
	private function addUserListenAlbum($uimid, $albumid)
	{
	    if (empty($uimid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    
	    $addtime = date("Y-m-d H:i:s");
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "REPLACE INTO {$this->LISTEN_ALBUM_TABLE_NAME}
    	    (`uimid`, `albumid`, `uptime`)
    	    VALUES (?, ?, ?)";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uimid, $albumid, time()));
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
     * @param I $babyagetype    当前登录用户的宝宝年龄段，未登录为0
     * @return boolean
     */
    private function addAlbumListenCount($albumid, $babyagetype = 0)
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
		
		if (!empty($babyagetype)) {
    		// list: 更新某个年龄段的专辑收听次数排行队列
    		$listenalbumkey = RedisKey::getRankListenAlbumKey($babyagetype);
    		$redisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
    		$redisObj->zIncrBy($listenalbumkey, 1, $albumid);
		}
		
		return true;
	}
}