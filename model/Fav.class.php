<?php
class Fav extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $FAV_TABLE_NAME = 'user_fav';
	public $CACHE_INSTANCE = 'cache';
	
	/**
	 * 获取用户收藏列表
	 * @param I $uid
	 * @param S $direction     up代表显示上边，down代表显示下边
	 * @param I $startid       从某个id开始,默认为0表示从第一页获取
	 * @param I $len           获取长度
	 * @return array
	 */
	public function getUserFavList($uid, $direction = "down", $startid = 0, $len = 20)
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
		$sql = "SELECT * FROM {$this->FAV_TABLE_NAME} WHERE {$where} ORDER BY `addtime` DESC LIMIT {$len}";
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
	
}