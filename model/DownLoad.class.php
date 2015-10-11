<?php
class DownLoad extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $DOWNLOAD_TABLE_NAME = 'download_story';
	public $CACHE_INSTANCE = 'cache';
	
	public $STATUS_DOWN_ING = 1; // 下载中状态
	public $STATUS_DOWN_OVER = 2;// 已下载完状态
	public $STATUS_DOWN_LIST = array(1, 2);
	
	/**
	 * 获取uid或设备，下载的故事记录
	 * @param I $uimid
	 * @param I $albumid
	 * @return array
	 */
	/* public function getDownLoadInfoByAlbumId($uimid, $albumid, $storyid)
	{
	    if (empty($uid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->DOWNLOAD_TABLE_NAME} WHERE `uid` = ? and `albumid` = ?";
	    $st = $db->prepare($sql);
	    $st->execute(array($uid, $albumid));
	    $res = $st->fetch(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        return array();
	    } else {
	        return $res;
	    }
	}*/
	
	
	/**
	 * uid或设备号，开始下载故事任务
	 * @param I $uimid
	 * @param I $albumid    专辑Id
	 * @param I $storyid    故事id
	 * @return boolean
	 */
	public function addDownLoadStoryInfo($uimid, $albumid, $storyid, $status)
	{
		if (empty($uimid) || empty($albumid) || empty($storyid) || !in_array($status, $this->STATUS_DOWN_LIST)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		
		$addtime = date("Y-m-d H:i:s");
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->DOWNLOAD_TABLE_NAME} 
			(`uimid`, `albumid`, `storyid`, `status`, `addtime`) 
			VALUES (?, ?, ?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uimid, $albumid, $storyid, $status, $addtime));
		if (empty($res)) {
			return false;
		}
		return true;
	}
	
}