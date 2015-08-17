<?php
class Fav extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $FAV_TABLE_NAME = 'user_fav';
	public $CACHE_INSTANCE = 'user_fav';
	
	/**
	 * 获取用户收藏列表
	 * @param I $uid
	 * @return array
	 */
	public function getUserFavList($uid)
	{
		if (empty($uid)) {
			return array();
		}
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->FAV_TABLE_NAME} WHERE `uid` = ?";
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
	
}