<?php
class DownLoad extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $DOWNLOAD_TABLE_NAME = 'user_download';
	public $CACHE_INSTANCE = '';
	
	public $STATUS_DOWN_ING = 1;
	public $STATUS_DOWN_OVER = 2;
	
	public function downLoadAlbum()
	{
	    
	}
	
	/**
	 * 获取用户下载列表
	 * @param I $uid
	 * @return array
	 */
	public function getUserDownLoadList($uid, $status = 0)
	{
		if (empty($uid)) {
		    $this->setError(ErrorConf::paramError());
			return array();
		}
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->DOWNLOAD_TABLE_NAME} WHERE `uid` = '{$uid}'";
		if (!empty($status)) {
		    $sql .= " AND `status` = '{$status}'";
		}
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
	 * 获取用户下载的专辑记录
	 * @param I $uid
	 * @param I $albumid
	 * @return array
	 */
	public function getUserDownLoadInfoByAlbumId($uid, $albumid)
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
	}
	
	
	/**
	 * 用户开始下载专辑任务
	 * @param I $uid
	 * @param I $albumid    专辑Id
	 * @return boolean
	 */
	public function addUserDownLoadAlbum($uid, $albumid)
	{
		if (empty($uid) || empty($albumid)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		$taskid = time();
		$taskstatus = $this->STATUS_DOWN_ING;
		$addtime = date("Y-m-d H:i:s");
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->DOWNLOAD_TABLE_NAME} 
			(`uid`, `albumid`, `taskid`, `taskstatus`, `addtime`) 
			VALUES (?, ?, ?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uid, $albumid, $taskid, $taskstatus, $addtime));
		return $res;
	}
	
	
	/**
	 * 专辑任务下载完成，更新任务状态
	 * @param I $uid
	 * @param I $albumid   专辑id
	 * @param I $taskid    任务id
	 * @return boolean
	 */
	public function updateUserDownLoadAlbumOver($uid, $albumid, $taskid)
	{
	    if (empty($uid) || empty($albumid) || empty($taskid)) {
	        return false;
	    }
	    $status = $this->STATUS_DOWN_OVER;
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "UPDATE {$this->DOWNLOAD_TABLE_NAME} SET `status` = ? WHERE `uid` = ? and `exportid` = ?";
	    $st = $db->prepare ( $sql );
	    $st->execute (array($status, $uid, $exportId));
	    return true;
	}
	
	/**
	 * 用户删除下载任务
	 * @param I $uid
	 * @param I $albumid    
	 * @param I $taskid
	 * @return boolean
	 */
	public function delUserDownLoadAlbum($uid, $albumid, $taskid)
	{
	    if (empty($uid) || empty($albumid) || empty($taskid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "DELETE FROM {$this->DOWNLOAD_TABLE_NAME} WHERE `uid` = ? AND `albumid` = ? AND `taskid` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uid, $albumid, $taskid));
	    return $res;
	}
}