<?php
class User extends ModelBase
{
	public $PASSPORT_DB_INSTANCE = 'share_main';
	public $USER_INFO_TABLE_NAME = 'user_info';
	public $BABY_INFO_TABLE_NAME = 'user_baby_info';
	public $ADDRESS_INFO_TABLE_NAME = 'user_address_info';
	
	public $CACHE_INSTANCE = 'main';
	
	public function initUser($uid,$addtime,$nickname)
	{
		if($uid==0)
		{
			$this->setError(ErrorConf::noUid());
			return false;
		}
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$sql = "insert into {$this->USER_INFO_TABLE_NAME} (uid,nickname,addtime) values (?,?,?)";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($uid,$nickname,$addtime));
		if($re)
		{
			$this->clearUserCache($uid);
			return true;
		}
		return false;
	}	
	
	
	public function initQQLoginUser($uid,$nickname,$avatartime,$addtime)
	{
		if($uid==0)
		{
			$this->setError(ErrorConf::noUid());
			return false;
		}
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$sql = "insert into {$this->USER_INFO_TABLE_NAME} (uid,nickname,avatartime,addtime) values (?,?,?,?)";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($uid,$nickname,$avatartime,$addtime));
		if($re)
		{
			return true;
		}
		$this->clearUserCache($uid);
		return false;
	}
	
	
	public function getUserInfo($uids)
	{
		if(!is_array($uids))
		{
			$uids = array($uids);
		}
		$data = array();
		$cacheData = CacheConnecter::get($this->CACHE_INSTANCE, $uids);
		$cacheData = array();
		$cacheIds = array();
		if (is_array($cacheData)){
			foreach ($cacheData as $onecachedata){
				$cacheIds[] = $onecachedata['uid'];
			}
		} else {
			$cacheData = array();
		}
		
		$dbIds = array_diff($uids, $cacheIds);
		$dbData = array();
		
		if(!empty($dbIds))
		{
			$result = array();
			$idlist = implode(',', $dbIds);
			$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
			$sql = "select * from {$this->USER_INFO_TABLE_NAME} where uid in($idlist)";
			
			$st = $db->prepare ( $sql );
			$st->execute ();
			$tmpDbData = $st->fetchAll ( PDO::FETCH_ASSOC );
			$db=null;
			foreach ($tmpDbData as $onedbdata){
				$dbData[$onedbdata['uid']] = $onedbdata;
				CacheConnecter::set($this->CACHE_INSTANCE, $onedbdata['uid'], $onedbdata, 2592000);
			}
		}

		foreach($uids as $uid)
		{
			if(in_array($uid, $dbIds))
			{
				$data[$uid] = @$dbData[$uid];
			}else{
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
	
	
	private function formatUserBaseInfo($one)
	{
		if($one['avatartime']+0==0)
		{
			$one['avatartime'] = strtotime($one['addtime']);
		}
		
		if($one['birthday']=="0000-00-00")
		{
			$one['birthday']="";
		}	
		
		if($one['birthday']==null)
		{
			$one['birthday']="";
		}
		if($one['province']==null)
		{
			$one['province']="";
		}
		if($one['city']==null)
		{
			$one['city']="";
		}
		if($one['area']==null)
		{
			$one['area']="";
		}
		if($one['province']=="" && $one['city']=="")
		{
			$one['area']=$_SERVER['morelanguage']['wherefrom'];
		}	
		if($one['sign']==null)
		{
			$one['sign']="";
		}
			
		if($one['gender']==0)
		{
			$one['gender']=1;
		}
		
		return $one;
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
	
	
	public function setUserinfo($uid,$data)
	{
		if($uid==0)
		{
			$this->setError(ErrorConf::noUid());
			return false;
		}
		if(empty($data))
		{
			$this->setError(ErrorConf::modifyUserInfoEmpty());
			return false;
		}
		
		
		if(@$data['nickname']!="")
		{
			$NicknameMd5Obj = new NicknameMd5();
			$existnicknameuid = $NicknameMd5Obj->checkNameIsExist($data['nickname']);
			if ($existnicknameuid>0 && $existnicknameuid!=$uid)
			{
				$this->setError(ErrorConf::nickNameIsExist());
				return false;
			}else{
				$NicknameMd5Obj->addOne($data['nickname'], $uid);
			}
			
			QueueManager::pushUserInfoToSearch($uid);
		}
		
		$setstr = "";
		foreach ($data as $attr=>$value)
		{
			$setstr = $setstr." $attr='{$value}' ,";
		}
		$setstr = rtrim($setstr,',');
		if($setstr == "")
		{
			$this->setError(ErrorConf::modifyUserInfoEmpty());
			return false;
		}
		$sql = "update `{$this->USER_INFO_TABLE_NAME}` set $setstr where uid=$uid ";
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$st = $db->prepare ( $sql );
		$st->execute ();
		$this->clearUserCache($uid);
		return true;
	}
		
	
	public function setUserNickname($uid,$nickname)
	{
		$NicknameMd5Obj = new NicknameMd5();
		$existnicknameuid = $NicknameMd5Obj->checkNameIsExist($nickname);
		if ($existnicknameuid>0 && $existnicknameuid!=$uid)
		{
			$this->setError(ErrorConf::nickNameIsExist());
			return false;
		}else{
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
		CacheConnecter::delete($this->CACHE_INSTANCE, $uid);
		//$DataSyncManagerObj = new DataSyncManager();
		//$DataSyncManagerObj->setRenewTime($DataSyncManagerObj->DATATYPEUSERINFO, $uid,time());
	}
	
}