<?php
class User extends ModelBase
{
	public $PASSPORT_DB_INSTANCE = 'share_main';
	public $USER_INFO_TABLE_NAME = 'user_info';
	
	public $CACHE_INSTANCE = 'cache';
	
	public $TYPE_QQ = 1;
	public $TYPE_WX = 2;
	public $TYPE_PH = 3;
	
	public $STATUS_NORMAL = 1;
	public $STATUS_FORZEN = -1; // 冻结
	public $STATUS_FORBITEN = -2; // 封号
	public $STATUS_DELETE = -3; // 删除
	
	
	public function initQQLoginUser($uid, $nickname, $avatartime, $birthday, $gender, $province, $city, $type, $addtime)
	{
		if(empty($uid) || empty($nickname) || empty($type)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		$res = $this->initUser($uid, $nickname, $avatartime, $birthday, $gender, $province, $city, $type, $addtime);
		return $res;
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
			    if (empty($oneredisdata)) {
			        continue;
			    }
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
			$idlist = implode(',', $dbIds);
			$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
			$sql = "select * from {$this->USER_INFO_TABLE_NAME} where uid in ($idlist)";
			$st = $db->prepare ( $sql );
			$st->execute ();
			$tmpDbData = $st->fetchAll ( PDO::FETCH_ASSOC );
			$db=null;
			if (!empty($tmpDbData)) {
    			foreach ($tmpDbData as $onedbdata){
    				$dbData[$onedbdata['uid']] = $onedbdata;
    				$uikey = RedisKey::getUserInfoKey($onedbdata['uid']);
    				$redisobj->setex($uikey, 604800, serialize($onedbdata));
    			}
			}
		}

		foreach($uids as $uid) {
			if(in_array($uid, $dbIds)) {
				$data[$uid] = @$dbData[$uid];
			} else {
				$data[$uid] = $cacheData[$uid];
			}
		}
		
		$result = array();
		foreach ($data as $one) {
			if(empty($one)) {
				continue;
			}
			$one = $this->formatUserBaseInfo($one);
			$result[$one['uid']] = $one;
		}
		return $result;
	}
	
	
	public function setAvatar($file, $uid)
	{
		if(empty($file)) {
			$this->setError(ErrorConf::noUploadAvatarfile());
			return false;
		}
		
		$uploadobj = new Upload();
		$uploadobj->uploadAvatarImage($file, $uid);
		
		$avatartime = time();
		$this->setUserinfo($uid, array('avatartime' => $avatartime));
		$this->clearUserCache($uid);
 		
		return $avatartime;
	}
	
	
	public function setUserinfo($uid, $data)
	{
		if(empty($uid)) {
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
	    $uikey = RedisKey::getUserInfoKey($uid);
		$redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
		$redisobj->delete($uikey);
		return true;
	}

	/**
	 * 初始化user_info、user_baby_info、user_address_info表
	 * @param I $uid
	 * @param S $nickname
	 * @param I $avatartime
	 * @param I $type
	 * @param S $addtime
	 * @return boolean
	 */
	protected function initUser($uid, $nickname, $avatartime, $birthday, $gender, $province, $city, $type, $addtime)
	{
	    if(empty($uid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $userextobj = new UserExtend();
	    $babyid = $userextobj->addUserBabyInfo($uid, $birthday, $gender);
	    if (empty($babyid)) {
	        return false;
	    }
	    
	    $name = "";
	    $phonenumber = "";
	    $addressid = $userextobj->addUserAddressInfo($uid, $name, $phonenumber, $province, $city);
	    if (empty($addressid)) {
	        return false;
	    }
	    
	    $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
	    $sql = "insert into {$this->USER_INFO_TABLE_NAME} (uid, nickname, avatartime, defaultbabyid, defaultaddressid, type, addtime)
	        values (?, ?, ?, ?, ?, ?, ?)";
	    $st = $db->prepare ( $sql );
	    $re = $st->execute (array($uid, $nickname, $avatartime, $babyid, $addressid, $type, $addtime));
	    if($re) {
	        return true;
	    }
		return false;
	}
	
	private function formatUserBaseInfo($one)
	{
	    if($one['avatartime']+0 == 0) {
	        $one['avatartime'] = strtotime($one['addtime']);
	    }
	    return $one;
	}
}