<?php
class User extends ModelBase
{
	public $PASSPORT_DB_INSTANCE = 'share_main';
	public $USER_INFO_TABLE_NAME = 'user_info';
	public $CACHE_INSTANCE = 'user_info';
	
	public $TYPE_QQ = 1;
	public $TYPE_WX = 2;
	public $TYPE_PH = 3;
	
	public $STATUS_NORMAL = 1;
	public $STATUS_FORZEN = -1; // 冻结
	public $STATUS_FORBITEN = -2; // 封号
	public $STATUS_DELETE = -3; // 删除
	
	public function initUser($uid,$addtime,$nickname)
	{
		if(empty($uid)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$sql = "insert into {$this->USER_INFO_TABLE_NAME} (uid,nickname,addtime) values (?,?,?)";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($uid,$nickname,$addtime));
		if($re) {
			$this->clearUserCache($uid);
			return true;
		}
		return false;
	}	
	
	
	public function initQQLoginUser($uid,$nickname,$avatartime,$type,$addtime)
	{
		if(empty($uid) || empty($nickname) || empty($type)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$sql = "insert into {$this->USER_INFO_TABLE_NAME} (uid,nickname,avatartime,type,addtime) values (?,?,?,?,?)";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($uid,$nickname,$avatartime,$type,$addtime));
		if($re) {
			return true;
		}
		$this->clearUserCache($uid);
		return false;
	}
	
	
	public function getUserInfo($uids)
	{
		if(!is_array($uids)) {
			$uids = array($uids);
		}
		$data = array();
		$keys = RedisKey::getUserInfoKeys($uids);
		$redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
		$redisData = $redisobj->mget($keys);
		
		$cacheData = array();
		$cacheIds = array();
		if (is_array($redisData)){
			foreach ($redisData as $oneredisdata){
			    $oneredisdata = unserialize($oneredisdata);
				$cacheIds[] = $oneredisdata['uid'];
				$cacheData[$oneredisdata['uid']] = $oneredisdata;
			}
		} else {
			$redisData = array();
		}
		
		$dbIds = array_diff($uids, $cacheIds);
		$dbData = array();
		
		if(!empty($dbIds)) {
			$result = array();
			$idlist = implode(',', $dbIds);
			$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
			$sql = "select * from {$this->USER_INFO_TABLE_NAME} where uid in ($idlist)";
			$st = $db->prepare ( $sql );
			$st->execute ();
			$tmpDbData = $st->fetchAll ( PDO::FETCH_ASSOC );
			$db=null;
			foreach ($tmpDbData as $onedbdata){
				$dbData[$onedbdata['uid']] = $onedbdata;
				$redisobj->setex($onedbdata['uid'], 2592000, serialize($onedbdata));
			}
		}

		foreach($uids as $uid) {
			if(in_array($uid, $dbIds)) {
				$data[$uid] = @$dbData[$uid];
			} else {
				$data[$uid] = $cacheData[$uid];
			}
		}
		
		$currentuid = @$_SESSION['uid'];
		$result = array();
		foreach ($data as $one)
		{
			if(empty($one))
			{
				continue;
			}
			$one = $this->formatUserBaseInfo($one);
			
			//$one['age']=getAgeFromBirthDay($one['birthday']);
			$result[$one['uid']] = $one;
		}
		return $result;
	}
	
	
	public function setAvatar($avatarfile,$uid)
	{
		if(!is_file($avatarfile))
		{
			$this->setError(ErrorConf::noUploadAvatarfile());
			return false;
		}
		$obj = new alioss_sdk();
		//$obj->set_debug_mode(FALSE);
		$bucket = 'tutuavatar';
		$responseObj = $obj->upload_file_by_file($bucket,$uid,$avatarfile);
		if ($responseObj->status!=200){
			$this->setError(ErrorConf::uploadAvatarfileFail());
		}
		$avatartime = time();
		$this->setUserinfo($uid, array('avatartime'=>$avatartime));
		$this->clearUserCache($uid);
 		
 		//QueueManager::pushUserToUpdateUserSysFriendLog($uid);
		return $avatartime;
	}
	
	
	public function setUserinfo($uid, $data)
	{
		if($uid==0) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		if(empty($data)) {
			$this->setError(ErrorConf::modifyUserInfoEmpty());
			return false;
		}
		
		if(!empty($data['nickname'])) {
			$NicknameMd5Obj = new NicknameMd5();
			$existnicknameuid = $NicknameMd5Obj->checkNameIsExist($data['nickname']);
			if ($existnicknameuid > 0 && $existnicknameuid != $uid) {
				$this->setError(ErrorConf::nickNameIsExist());
				return false;
			} else {
				$NicknameMd5Obj->addOne($data['nickname'], $uid);
			}
			
			//QueueManager::pushUserInfoToSearch($uid);
		}
		
		$setstr = "";
		foreach ($data as $attr => $value) {
			$setstr = $setstr." $attr='{$value}' ,";
		}
		$setstr = rtrim($setstr,',');
		if($setstr == "") {
			$this->setError(ErrorConf::modifyUserInfoEmpty());
			return false;
		}
		$sql = "update `{$this->USER_INFO_TABLE_NAME}` set $setstr where uid = $uid ";
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$st = $db->prepare ( $sql );
		$st->execute ();
		$this->clearUserCache($uid);
		return true;
	}
	
	
	public function setUserNickname($uid, $nickname)
	{
		$NicknameMd5Obj = new NicknameMd5();
		$existnicknameuid = $NicknameMd5Obj->checkNameIsExist($nickname);
		if ($existnicknameuid > 0 && $existnicknameuid != $uid) {
			$this->setError(ErrorConf::nickNameIsExist());
			return false;
		} else {
			$NicknameMd5Obj->addOne($nickname, $uid);
		}
		
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$sql = "update `{$this->USER_INFO_TABLE_NAME}` set nickname=? where uid=$uid ";
		$st = $db->prepare ( $sql );
		$st->execute (array($nickname));
		$this->clearUserCache($uid);
		
		//QueueManager::pushUserInfoToSearch($uid);
		// 添加到审核队列
		//QueueManager::pushAuditTextAction($uid, 1);
		
		//QueueManager::pushUserToUpdateUserSysFriendLog($uid);
		return true;
	}
	
	/*public function moveAvatarImage($uid)
	{
	    $nowDate = date("YmdHis");
	    $imageBak = "{$uid}_{$nowDate}";
	    $aliOssObj = new AliOss();
	    return $aliOssObj->moveAvatarOss($uid, $imageBak);
	}*/
	
	public function clearUserCache($uid)
	{
		//CacheConnecter::delete($this->CACHE_INSTANCE, $uid);
		$redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
		return $redisobj->delete($uid);
		//$DataSyncManagerObj = new DataSyncManager();
		//$DataSyncManagerObj->setRenewTime($DataSyncManagerObj->DATATYPEUSERINFO, $uid,time());
	}
	

	private function formatUserBaseInfo($one)
	{
	    if($one['avatartime']+0 == 0) {
	        $one['avatartime'] = strtotime($one['addtime']);
	    }
	    if($one['birthday'] == "0000-00-00") {
	        $one['birthday'] = "";
	    }
	    if($one['birthday'] == null) {
	        $one['birthday'] = "";
	    }
	    if($one['gender'] == 0) {
	        $one['gender'] = 1;
	    }
	    return $one;
	}
}