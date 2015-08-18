<?php
class Listen extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $LISTEN_TABLE_NAME = 'user_listen';
	public $CACHE_INSTANCE = 'user_listen';
	
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
	 * @param I $albumid    专辑Id
	 * @param I $storyid	故事id
	 * @return boolean
	 */
	public function addUserLisenStory($uid, $albumid, $storyid)
	{
		if (empty($uid) || empty($albumid) || empty($storyid)) {
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
		return $res;
	}
	
	
	/**
	 * 用户取消收听
	 * @param I $uid
	 * @param I $storyid    故事Id
	 * @return boolean
	 */
	public function delUserListenStory($uid, $storyid)
	{
	    if (empty($uid) || empty($storyid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "DELETE FROM {$this->LISTEN_TABLE_NAME} WHERE `uid` = ? AND `storyid` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uid, $storyid));
	    return $res;
	}
}