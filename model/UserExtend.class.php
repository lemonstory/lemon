<?php
class UserExtend extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $BABY_INFO_TABLE_NAME = 'user_baby_info';
	public $ADDRESS_INFO_TABLE_NAME = 'user_address_info';
	
	public $CACHE_INSTANCE = 'main';
	
	/**
	 * 获取宝宝信息
	 * @param I $uid
	 * @return array
	 */
	public function getUserBabyInfo($babyid)
	{
		if (empty($babyid)) {
			return array();
		}
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->BABY_INFO_TABLE_NAME} WHERE `id` = ?";
		$st = $db->prepare($sql);
		$st->execute(array($babyid));
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
	 * @param I $storyid	故事id
	 * @return boolean
	 */
	public function addUserStory($uid, $storyid)
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