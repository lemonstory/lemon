<?php
class Fav extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $FAV_TABLE_NAME = 'fav_album';    // 用户收藏专辑记录表
	public $FAV_ALBUM_COUNT_TABLE_NAME = 'fav_album_count';    // 专辑被收藏的总数
	public $CACHE_INSTANCE = 'cache';
	
	/**
	 * 获取用户收藏列表
	 * @param I $uid
	 * @param S $direction     up代表显示上边，down代表显示下边
	 * @param I $startfavid    从某个收藏id开始,默认为0表示从第一页获取
	 * @param I $len           获取长度
	 * @return array
	 */
	public function getUserFavList($uid, $direction = "down", $startfavid = 0, $len = 20)
	{
		if (empty($uid)) {
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
		if (!empty($startfavid)) {
		    if ($direction == "up") {
		        $where .= " `id` > '{$startfavid}' AND";
		    } else {
		        $where .= " `id` < '{$startfavid}' AND";
		    }
		}
		$where .= " `uid` = '{$uid}'";
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->FAV_TABLE_NAME} WHERE {$where} ORDER BY `id` DESC LIMIT {$len}";
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
	 * 获取用户收藏的专辑记录
	 * @param I $uid
	 * @param I $albumid
	 * @return array
	 */
	public function getUserFavInfoByAlbumId($uid, $albumid)
	{
	    if (empty($uid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->FAV_TABLE_NAME} WHERE `uid` = ? and `albumid` = ?";
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
	 * 批量获取专辑的收藏总量
	 * @param A $albumids
	 * @return array
	 */
	public function getAlbumFavCount($albumids)
	{
	    if (empty($albumids)) {
	        return array();
	    }
	    if (!is_array($albumids)) {
	        $albumids = array($albumids);
	    }
	     
	    $albumidstr = implode(",", $albumids);
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->FAV_ALBUM_COUNT_TABLE_NAME} WHERE `albumid` IN ($albumidstr)";
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
	 * 获取用户的收藏专辑总数
	 * @param I $uid
	 * @return I
	 */
	public function getUserFavCount($uid)
	{
	    if (empty($uid)) {
	        return 0;
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT COUNT(*) FROM {$this->FAV_TABLE_NAME} WHERE `uid` = ?";
	    $st = $db->prepare($sql);
	    $st->execute(array($uid));
	    $res = $st->fetch(PDO::FETCH_COLUMN);
	    if (empty($res)) {
	        return 0;
	    } else {
	        return $res;
	    }
	}
	
	
	/**
	 * 用户添加收藏
	 * @param I $uid
	 * @param I $albumid	专辑id
	 * @return boolean
	 */
	public function addUserFavAlbum($uid, $albumid)
	{
		if (empty($uid) || empty($albumid)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		$addtime = date("Y-m-d H:i:s");
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->FAV_TABLE_NAME} 
			(`uid`, `albumid`, `addtime`) 
			VALUES (?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uid, $albumid, $addtime));
		if (empty($res)) {
		    return false;
		}
		
		// 更新专辑的收藏总数
		$this->addAlbumFavNum($albumid);
		
		// 收藏行为log
		$actionlogobj = new ActionLog();
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid($uid);
        MnsQueueManager::pushActionLogQueue($uimid, $albumid, $actionlogobj->ACTION_TYPE_FAV_ALBUM);
		return $res;
	}
	
	
	/**
	 * 用户取消收藏
	 * @param I $uid
	 * @param I $albumid	专辑id
	 * @return boolean
	 */
	public function delUserFavAlbum($uid, $albumid)
	{
	    if (empty($uid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "DELETE FROM {$this->FAV_TABLE_NAME} WHERE `uid` = ? AND `albumid` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uid, $albumid));
	    return $res;
	}
	
	
	/**
	 * 统计，专辑的收藏总数
	 * @param I $albumid
	 * @return boolean
	 */
	private function addAlbumFavNum($albumid)
	{
	    if (empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	     
	    $selectsql = "SELECT * FROM `{$this->FAV_ALBUM_COUNT_TABLE_NAME}` WHERE `albumid` = ?";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute(array($albumid));
	    $selectres = $selectst->fetch(PDO::FETCH_ASSOC);
	    if (empty($selectres)) {
	        $sql = "INSERT INTO `{$this->FAV_ALBUM_COUNT_TABLE_NAME}` (`albumid`, `num`) VALUES ('{$albumid}', 1)";
	    } else {
	        $sql = "UPDATE `{$this->FAV_ALBUM_COUNT_TABLE_NAME}` SET `num` = `num` + 1 WHERE `albumid` = '{$albumid}'";
	    }
	    $st = $db->prepare($sql);
	    $numres = $st->execute();
	    if (empty($numres)) {
	        return false;
	    }
	    return true;
	}
}