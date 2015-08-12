<?php
class User extends ModelBase
{
	public function initUser($uid,$addtime,$nickname)
	{
		if($uid==0)
		{
			$this->setError(ErrorConf::noUid());
			return false;
		}
		$db = DbConnecter::connectMysql('share_user');
		$sql = "insert into userinfo (uid,addtime,nickname) values (?,?,?)";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($uid,$addtime,$nickname));
		if($re)
		{
			$this->clearUserCache($uid);
			return true;
		}
		return false;
	}	
	
	
	public function initQQLoginUser($uid,$nickname,$avatartime,$gender,$birthday,$province,$city,$addtime)
	{
		if($uid==0)
		{
			$this->setError(ErrorConf::noUid());
			return false;
		}
		$db = DbConnecter::connectMysql('share_user');
		$sql = "insert into userinfo (uid,nickname,avatartime,gender,birthday,province,city,addtime) values (?,?,?,?,?,?,?,?)";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($uid,$nickname,$avatartime,$gender,$birthday,$province,$city,$addtime));
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
		$UserHonor = new UserHonor();
		$data = array();
		$cacheData = CacheConnecter::get('userinfo', $uids);
		#$honorcacheData = $UserHonor->getUserHonorLevel($uids);
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
			$db = DbConnecter::connectMysql('share_user');
			$sql = "select * from userinfo where uid in($idlist)";
			
			$st = $db->prepare ( $sql );
			$st->execute ();
			$tmpDbData = $st->fetchAll ( PDO::FETCH_ASSOC );
			$db=null;
			foreach ($tmpDbData as $onedbdata){
				$dbData[$onedbdata['uid']] = $onedbdata;
				CacheConnecter::set('userinfo',	$onedbdata['uid'], $onedbdata,2592000);
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
			
			if(isset($_SERVER['visitorappversion']) && $_SERVER['visitorappversion']>=168000000)
			{
				if(empty($remarks[$one['uid']]))
				{
					$remarks[$one['uid']] = "";
				}
				$one['remarkname'] = $remarks[$one['uid']];
			}else{
				if (!empty($remarks[$one['uid']])){
					$one['nickname'] = $remarks[$one['uid']];
				}
			}
			#$one['userhonorlevel'] = $honorcacheData[$one['uid']];
			$one['constellation'] = $this->getConstellationByBirthday($one['birthday']);
			if($currentuid!=$one['uid'])
			{
				$tmpsign = $one['sign'];
				$tmpsign = str_replace(" ", '', $tmpsign);
				if(preg_match("/\d{7}/", $tmpsign)){
					$one['sign'] = $_SERVER['disposeqqcontent'][$one['uid']%3];
				}
			}
			$one['age']=getAgeFromBirthDay($one['birthday']);
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
		
		if (isset($_SERVER['visitorappversion']) && $one['userhonorlevel']>5 && $_SERVER['visitorappversion']<180000000)
		{
			$one['userhonorlevel'] = 5;
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
// 		用户头像审核队列
 		QueueManager::pushUserAvatarAudit($uid);
 		
 		QueueManager::pushUserToUpdateUserSysFriendLog($uid);
		return $avatartime;
	}
	
	
	public function setUserinfo($uid,$data)
	{
		if($uid==0)
		{
			$this->setError(ErrorConf::noUid());
			return false;
		}
		unset($data['sign']);
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
		$sql = "update `userinfo` set $setstr where uid=$uid ";
		$db = DbConnecter::connectMysql('share_user');
		$st = $db->prepare ( $sql );
		$st->execute ();
		$this->clearUserCache($uid);
// 		用户信息（文字）审核队列
// 		QueueManager::pushUserInfoAudit($uid);
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
			
		
		
		$db = DbConnecter::connectMysql('share_user');
		$sql = "update `userinfo` set nickname=? where uid=$uid ";
		$st = $db->prepare ( $sql );
		$st->execute (array($nickname));
		$this->clearUserCache($uid);
		
		QueueManager::pushUserInfoToSearch($uid);
		// 添加到审核队列
		QueueManager::pushAuditTextAction($uid, 1);
		
		QueueManager::pushUserToUpdateUserSysFriendLog($uid);
		return true;
	}
	
	public function moveAvatarImage($uid)
	{
	    $nowDate = date("YmdHis");
	    $imageBak = "{$uid}_{$nowDate}";
	    $aliOssObj = new AliOss();
	    return $aliOssObj->moveAvatarOss($uid, $imageBak);
	}
	
	public function clearUserCache($uid)
	{
		CacheConnecter::delete('userinfo', $uid);
		$DataSyncManagerObj = new DataSyncManager();
		$DataSyncManagerObj->setRenewTime($DataSyncManagerObj->DATATYPEUSERINFO, $uid,time());
		
		
	}
	
}