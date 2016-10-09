<?php
class User extends ModelBase
{
	public $PASSPORT_DB_INSTANCE = 'share_main';
	public $USER_INFO_TABLE_NAME = 'user_info';
	
	public $CACHE_INSTANCE = 'cache';

	/**
	 * 示例:
	 *
	 * 通过QQ注册的普通用户:            来源,QQ. 角色,普通用户
	 * 通过后台添加手机号注册的管理员:    来源,手机号,角色,系统管理员
	 * 通过微信注册的主播用户:            来源,微信.角色,主播
	 * 通过程序注册的作者:                来源,系统.角色,作者
	 */
	//注册来源
	//QQ注册
	public $TYPE_QQ = 1;
	//微信注册
	public $TYPE_WX = 2;
	//手机号注册
	public $TYPE_PH = 3;
	#TODO:需要更改数据
	//系统注册{主播,作者....}
	public $TYPE_SYS = 4;

	//用户角色
	public $IDENTITY_NORMAR = 1; //普通用户
	//部分作者既是作者又是插图作者,所以不能单独区分(创建两个角色的uid)
	public $IDENTITY_SYSTEM_USER = 2; //系统注册用户{主播,作者,插图作者...}
	public $IDENTITY_SYSTEM_ADMIN = 4; //系统管理员


	public function getUserInfo($uids, $isgetbabay = 0)
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
		
		// 获取babyinfo
		$babylist = array();
		if ($isgetbabay == 1) {
		    $babyids = array();
		    foreach ($data as $one) {
		        if (!empty($one['defaultbabyid'])) {
		            $babyids[] = $one['defaultbabyid'];
		        } else {
		            MnsQueueManager::pushRepairUserInfo($one['uid'], "defaultbabyid", 0);
		        }
		    }
		    if (!empty($babyids)) {
		        $userextobj = new UserExtend();
		        $babylist = $userextobj->getUserBabyInfo($babyids);
		    }
		}
		
		$result = array();
		foreach ($data as $one) {
			if(empty($one)) {
				continue;
			}
			$one['birthday'] = "";
			$one['gender'] = 0;
			$one['age'] = 0;
			if ($isgetbabay == 1 && !empty($babylist[$one['defaultbabyid']])) {
			    $one['birthday'] = $babylist[$one['defaultbabyid']]['birthday'];
			    $one['gender'] = $babylist[$one['defaultbabyid']]['gender'];
			    $one['age'] = $babylist[$one['defaultbabyid']]['age'];
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
		$uploadobj->uploadAvatarImageByPost($file, $uid);
		
		$avatartime = time();
		$this->setUserinfo($uid, array('avatartime' => $avatartime));
		$this->clearUserCache($uid);
 		
		return $avatartime;
	}

	public function setAvatarWithUrl($avatar_remote_url, $uid)
	{
		$avatartime = false;
		$aliossobj = new AliOss();
		$savedir = $aliossobj->LOCAL_IMG_TMP_PATH;
		$url_arr = parse_url($avatar_remote_url);
		$pos = strrpos($url_arr['path'], "/") + 1;
		$full_file = $savedir . substr($url_arr['path'], $pos);

		if (file_exists($full_file)) {
			@unlink($full_file);
		}
		$tmp_file_name = Http::download($avatar_remote_url, $full_file);
		$size = getimagesize($tmp_file_name);
		if ($tmp_file_name) {
			$file = array();
			$file['tmp_name'] = $tmp_file_name;
			$file['type'] = $size['mime'];
			$avatartime = $this->setAvatar($file, $uid);
		}
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
	 * @param S $birthday
	 * @param I $gender
	 * @param S $province
	 * @param S $city
	 * @param I $type
	 * @param S $addtime
	 * @return boolean
	 */
	public function initUser($uid, $nickname, $avatartime, $birthday, $gender, $province, $city, $type, $indentity, $addtime)
	{
	    if(empty($uid) || empty($nickname) || empty($type)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }

		$userextobj = new UserExtend();
	    $babyid = $userextobj->addUserBabyInfo($uid, $birthday, $gender);
	    if (empty($babyid)) {
	        $this->setError($userextobj->getError());
	        return false;
	    }
	    
	    $name = "";
	    $phonenumber = "";
	    $addressid = $userextobj->addUserAddressInfo($uid, $name, $phonenumber, $province, $city);
	    if (empty($addressid)) {
	        $this->setError($userextobj->getError());
	        return false;
	    }
	    
	    $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$sql = "insert into {$this->USER_INFO_TABLE_NAME} (uid, nickname, avatartime, defaultbabyid, defaultaddressid, province, city, type,indentity, addtime)
	        values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	    $st = $db->prepare ( $sql );
		$re = $st->execute(array($uid, $nickname, $avatartime, $babyid, $addressid, $province, $city, $type, $indentity, $addtime));
	    if($re) {
	        return true;
	    }
		return false;

	}


	#TODO:需要加缓存
	public function getUsersInfo($identity)
	{

		$users_info = array();
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$sql = "select * from {$this->USER_INFO_TABLE_NAME} where `identity`='{$identity}'";
		$st = $db->prepare($sql);
		$st->execute();
		$db_data = $st->fetchAll(PDO::FETCH_ASSOC);
		foreach ($db_data as $db_data_item) {
			$users_info[$db_data_item['uid']] = $db_data_item;
		}
		$db = null;
		return $users_info;
	}

	
	private function formatUserBaseInfo($one)
	{
	    if($one['avatartime']+0 == 0) {
	        $one['avatartime'] = strtotime($one['addtime']);
	    }
	    return $one;
	}
}