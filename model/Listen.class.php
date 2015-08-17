<?php
class Listen extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $LISTEN_TABLE_NAME = 'user_listen';
	
	/**
	 * 获取用户收听列表
	 * @param I $uid
	 * @return array
	 */
	public function getUserListenList($uid)
	{
		if (empty($uid)) {
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
			return $res;
		}
	}
	
	/**
	 * 用户添加收听故事
	 * @param I $uid
	 * @param I $storyid	故事id
	 * @return boolean
	 */
	public function addUserLisenStory($uid, $storyid)
	{
		if (empty($uid) || empty($storyid)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		$addtime = date("Y-m-d H:i:s");
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->LISTEN_TABLE_NAME} 
			(`uid`, `storyid`, `addtime`) 
			VALUES (?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uid, $storyid, $addtime));
		return $res;
	}
	
}