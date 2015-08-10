<?php
class Sso extends ModelBase
{
	private $cookies;
	public function __construct()
	{
		$this->cookies = $_COOKIE;
	}
	public function regUser($phonenumber,$rawpassword,$nickname)
	{
		if($phonenumber=='' )
		{
			$this->setError(ErrorConf::phoneNumberEmpty());
			return false;
		}
		if($rawpassword=='')
		{
			$this->setError(ErrorConf::passwordEmpty());
			return false;
		}
		
		$NicknameMd5Obj =  new NicknameMd5();
		if($NicknameMd5Obj->checkNameIsExist($nickname))
		{
			$this->setError(ErrorConf::nickNameIsExist());
			return false;
		}
		
		$passportinfo = $this->getInfoWithPhoneNumber($phonenumber);
		if(!empty($passportinfo))
		{
			$this->setError(ErrorConf::phoneIsReged());
			return false;
		}
		$username = $this->createPhoneUserName($phonenumber);
		$time = time();
		$addtime=date('Y-m-d H:i:s',$time);
		$password = md5($rawpassword.strrev($time));
		$db = DbConnecter::connectMysql('share_passport');
		
		$sql = "insert into passport (username,password,addtime) values (?,?,?)";
		$st = $db->prepare ( $sql );
		$st->execute (array($username,$password,$addtime));
		$uid = $db->lastInsertId()+0;
		if($uid==0)
		{
			return false;
		}
		$UserObj = new User();
		$UserObj->initUser($uid, $addtime,$nickname);
		
		$userinfo = current($UserObj->getUserInfo($uid));
		$this->setSsoCookie($this->getInfoWithUid($uid),$userinfo);
		
		
		
		if($uid>0)
		{
			$NicknameMd5Obj->addOne($nickname, $uid);
		}
		
		
		
		QueueManager::pushAfterRegQueue($uid);
		QueueManager::pushUserInfoToSearch($uid);
		return $uid;
	}	
	
	public function resetPassword($uid,$oldrawpassword,$newrawpassword)
	{
		$passportdata = $this->getInfoWithUid($uid);
		if (empty($passportdata)) {
		    return false;
		}
		$addtime = strtotime($passportdata['addtime']);
		
		if(md5($oldrawpassword.strrev($addtime))!=$passportdata['password'])
		{
			$this->setError(ErrorConf::oldPasswordError());
			return false;	
		}
		
		$newpassword = md5($newrawpassword.strrev($addtime));
		$db = DbConnecter::connectMysql('share_passport');
		$sql = "update passport set password=? where uid=?";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($newpassword,$uid));
		$db=null;
		if($re)
		{
		    $userName = $passportdata['username'];
		    $this->clearPassportCacheByUserName($userName);
		    $this->clearPassportCacheByUid($uid);
			return true;
		}
		return false;
	}
	
	public function checkQqLoginFirst($openId)
	{
	    $key = 'qqrecount_' . $openId;
	    $cacheData = CacheConnecter::get('passport', $key);
	    if (empty($cacheData)) {
    		$db = DbConnecter::connectMysql('share_passport');
    		$sql = "select count(1) from  qqrelation where open_id=?";
    		$st = $db->prepare ( $sql );
    		$re = $st->execute (array($openId));
    		$count = $st->fetch(PDO::FETCH_COLUMN);
    		$db=null;
    		if($count==1)
    		{
    		    CacheConnecter::set('passport', $key, $count, 30 * 86400);
    			return false;
    		}
    		return true;
	    } else {
	        if ($cacheData==1) {
	            return false;
	        }
	        return true;
	    }
		
	}
	
	
	public function getQqRelationInfoWithUid($uid)
	{
	    if (empty($uid)) {
	        return array();
	    }
	    $key = 'qqrelation_' . $uid;
	    $cacheData = CacheConnecter::get('passport', $key);
	    if (empty($cacheData)) {
    	    $db = DbConnecter::connectMysql('share_passport');
    	    $sql = "select * from qqrelation where uid=?";
    	    $st = $db->prepare ( $sql );
    	    $st->execute (array($uid));
    	    $info = $st->fetch(PDO::FETCH_ASSOC);
    	    $db=null;
    	    if (!empty($info)) {
    	        CacheConnecter::set('passport', $key, $info, 86400);
    	    }
    	    return $info;
	    } else {
	        return $cacheData;
	    }
	}
	
	
	/**
	 * 获取QQ开放平台信息
	 * @param S $accessToken
	 * @param S $openId
	 * @return array
	 */
	public function getQqInfo($qc)
	{
	    if (empty($qc)) {
	        return array();
	    }
	    
	    $getInfo = $qc->get_user_info();
	    if(empty($getInfo))
	    {
	        $this->setError(ErrorConf::qqUserInfoEmpty());
	        return array();
	    }
	    
	    $qqUserInfo = array();
	    $qqUserInfo['nickName'] = $getInfo['nickname'];
	    
	    $gender = 0;
	    $gendertxt = $getInfo['gender'];
	    if($gendertxt=='男')
	    {
	        $gender=1;
	    }
	    if($gendertxt=='女')
	    {
	        $gender=2;
	    }
	    $qqUserInfo['gender']   = $gender;
	    $qqUserInfo['province'] = $getInfo['province'];
	    $qqUserInfo['city']     = $getInfo['city'];
	    $qqUserInfo['year']     = $getInfo['year'];
	    $qqUserInfo['qqAvatar'] = $getInfo['figureurl_qq_2'];
	    
	    return $qqUserInfo;
	}
	
	public function initQqLoginUser($qc, $accessToken, $openId, $nickName)
	{
	    if (empty($qc) || empty($accessToken) || empty($openId) || empty($nickName)) {
	        return false;
	    }
	    
	    $NicknameMd5Obj = new NicknameMd5();
	    if($NicknameMd5Obj->checkNameIsExist($nickName)) {
	        //$this->showErrorJson(ErrorConf::nickNameIsExist());
	        $this->setError(ErrorConf::nickNameIsExist());
	        return false;
	    }
	    
	    $qqUserInfo = $this->getQqInfo($qc);
	    if (empty($qqUserInfo)) {
	        return false;
	    }
		
		//$nickname  = $qqUserInfo['nickName'];
		$gender    = $qqUserInfo['gender'];
		$province  = $qqUserInfo['province'];
		$city      = $qqUserInfo['city'];
		$year      = $qqUserInfo['year'];
		$qqavatar  = $qqUserInfo['qqAvatar'];
		$birthday  = '';
		if (!empty($year)) {
		    $birthday = $year . "-01-01";
		}
		
		$addtime = date('Y-m-d H:i:s');
		$db = DbConnecter::connectMysql('share_passport');
		
		$qquserpasword = md5('QL'.time());
		$sql = "insert into passport (username,password,addtime) values (?,?,?)";
		$st = $db->prepare ( $sql );
		$st->execute (array('QL',$qquserpasword,$addtime));
		$uid = $db->lastInsertId()+0;
		if($uid==0)
		{
			return false;
		}
				
		$sql = "insert into qqrelation (open_id,uid,access_token,addtime) values (?,?,?,?)";
		$st = $db->prepare ( $sql );
		$st->execute (array($openId,$uid,$accessToken,$addtime));
		
		
		$NicknameMd5Obj->addOne($nickName, $uid);
		
		$avatartime = 0;
		if($qqavatar!="")
		{
// 			$ch = curl_init();
// 			curl_setopt($ch, CURLOPT_URL, $qqavatar);	 //
// 			curl_setopt($ch, CURLOPT_TIMEOUT, 10);	 //
// 			curl_setopt($ch, CURLOPT_USERAGENT, "Baiduspider+(+ http://www.baidu.com/search/spider.htm)");
// 			curl_setopt($ch, CURLOPT_REFERER, $qqavatar);
// 			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 			$content = curl_exec($ch);
// 			curl_close($ch);
// 			$avatarfile = "/alidata/tmpavatarfile/{$uid}";
// 			file_put_contents(	$avatarfile, $content);
// 			if(is_file($avatarfile))
// 			{
// 				$obj = new alioss_sdk();
// 				//$obj->set_debug_mode(FALSE);
// 				$bucket = 'tutuavatar';
// 				$responseObj = $obj->upload_file_by_file($bucket,$uid,$avatarfile);
				
			
// 				if ($responseObj->status==200){
// 					$avatartime = time();
// 				}
// 			}

			QueueManager::pushLoadUserQqavatar($uid, $qqavatar);
			
		}
		
		$UserObj = new User();
		$UserObj->initQQLoginUser($uid,$nickName,$avatartime,$gender,$birthday,$province,$city,$addtime);
		
		
		$this->setSsoCookie(array('uid'=>$uid,'pasword'=>$qquserpasword),array('nickname'=>$nickName));
		
		$return = array('uid'=>$uid,'nickname'=>$nickName,'avatartime'=>time());

		QueueManager::pushAfterRegQueue($uid);
		QueueManager::pushUserInfoToSearch($uid);
		return $return;
	}
	
	public function qqlogin($accessToken, $openId)
	{
		$db = DbConnecter::connectMysql('share_passport');
		$sql = "update qqrelation set access_token=? where open_id=?";
		$st = $db->prepare ( $sql );
		$st->execute (array($accessToken,$openId));
		
		$sql = "select * from qqrelation where open_id=?";
		$st = $db->prepare ( $sql );
		$st->execute (array($openId));
		$ar = $st->fetch(PDO::FETCH_ASSOC);
		$uid = $ar['uid'];
		
		
		$passportdata = $this->getInfoWithUid($uid);
		$UserObj =  new User();
		$this->setLoginType($uid, 'qq');
		$userinfo = $UserObj->getSelfInfo($uid);
		// 判断是否封号账户
		/* if (!empty($userinfo['status']) && $userinfo['status'] == '-2') {
	        $this->setError(ErrorConf::userForbidenPost());
	        return false;
		} */
		
		$this->setSsoCookie($passportdata,$userinfo);
		
		if (empty($userinfo['birthday'])) {
		    // 修复qq用户年龄为空的数据
		    QueueManager::pushQqBirthday($uid);
		}
		
		return $userinfo;
	}
	
	
	public function setPhonenumberIsVerify($uid)
	{
	    if (empty($uid)) {
	        return false;
	    }
	    $passportdata = $this->getInfoWithUid($uid);
	    if (empty($passportdata)) {
	        return false;
	    }
	    
		$db = DbConnecter::connectMysql('share_passport');
		$sql = "update passport set verifyphone=1 where uid=?";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($uid));
		if($re)
		{
		    $userName = $passportdata['username'];
		    $this->clearPassportCacheByUserName($userName);
		    $this->clearPassportCacheByUid($uid);
			return true;
		}
		return false;
	}
	
	
	public function resetPasswordWithVcode($phonenumber,$newrawpassword)
	{
		$SsoObj = new PhoneVerifyCode();

		$passportdata	= $this->getInfoWithPhoneNumber($phonenumber);
		$addtime 		= strtotime($passportdata['addtime']);
		$uid     		= $passportdata['uid'];

		$newpassword = md5($newrawpassword.strrev($addtime));
		$db = DbConnecter::connectMysql('share_passport');
		$sql = "update passport set password=? where uid=?";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($newpassword,$uid));
		if(!$re)
		{
			return false;
		}
		$UserObj =  new User();
		//$userinfo = current($UserObj->getUserInfo($uid));
		$userinfo = $UserObj->getSelfInfo($uid);
		$this->setSsoCookie($passportdata,$userinfo);
		//$avatartime = $userinfo['avatartime'];
		//$return = array('uid'=>$userinfo['uid']+0,'nickname'=>$userinfo['nickname'],'avatartime'=>"$avatartime");
		
		$userName = $passportdata['username'];
		$this->clearPassportCacheByUserName($userName);
		$this->clearPassportCacheByUid($uid);
		
		return $userinfo;
		
	}
	
	
	public function userBindPhone($uid,$phonenumber,$password)
	{
		
		$passportdata = $this->getInfoWithUid($uid);
		$addtime 		= strtotime($passportdata['addtime']);
		$newpassword = md5($password.strrev($addtime));
		
		$db = DbConnecter::connectMysql('share_passport');
		$username = $this->createPhoneUserName($phonenumber);
		$sql = "update passport set username=?, password=? where uid=?";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($username,$newpassword,$uid));
		if(!$re)
		{
			return false;
		}
		
		$db = DbConnecter::connectMysql('share_user');
		$sql = "insert into userbindphone (uid,phonenumber ,addtime) values (?,?,?)";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($uid,$phonenumber,date('Y-m-d H:i:s')));
		
		
		$this->clearPassportCacheByUid($uid);
		$this->clearPassportCacheByUserName($this->createPhoneUserName($phonenumber));
		
		return true;
	}
	
	public function logout()
	{
	    $domain = 'tutuim.com';
		$GLOBALS['_SESSION'] = array();
		unset($this->cookies['us']);
		unset($this->cookies['al']);
		setcookie('us', '', time() - 86400, '/', "");
		setcookie('al', '', time() - 86400, '/', "");
		setcookie('us', '', time() - 86400, '/', $domain);
		setcookie('al', '', time() - 86400, '/', $domain);
		return true;
	}
	
	public function getUid()
	{	
		$this->cookies = $_COOKIE;	
		
		$parseInfo = $this->parseSession();
		if (! empty($parseInfo)) {
			$GLOBALS['_SESSION']['uid'] = $parseInfo['uid'];
			$GLOBALS['_SESSION']['username'] = trim(@$parseInfo['nickname']);
			$GLOBALS['_SESSION']['cert'] = $parseInfo['cert'];
		} else {
			$GLOBALS['_SESSION']['uid'] = '';
			$GLOBALS['_SESSION']['email'] = '';
			$GLOBALS['_SESSION']['cert'] = '';
		}
		if (empty($GLOBALS['_SESSION']['uid'])) {
			$this->autoLogin();
		}
		return  $_SESSION['uid']+0;
	}
	
	
	public function phoneLogin($phonenumber,$rawpassword)
	{
		if($phonenumber=='' || $rawpassword=='')
		{
			$this->setError(ErrorConf::phoneNumberEmpty());
			return false;
		}
		if($rawpassword=='')
		{
			$this->setError(ErrorConf::passwordEmpty());
			return false;
		}
		$passportdata = $this->getInfoWithPhoneNumber($phonenumber);
		if(empty($passportdata))
		{
			$this->setError(ErrorConf::userNoExist());
			return false;	
		}
		$uid = $passportdata['uid'];
		if($passportdata['password'] != md5($rawpassword.strrev(strtotime($passportdata['addtime']))))
		{
			$this->setError(ErrorConf::passwordError());
			return false;
		}
		$this->setLoginType($uid, 'phone');
		$UserObj =  new User();
		$userinfo = $UserObj->getSelfInfo($uid);
	    if (!empty($userinfo['status']) && $userinfo['status'] == '-2') {
	        $this->setError(ErrorConf::userForbidenPost());
	        return false;
		}
		
		$this->setSsoCookie($passportdata,$userinfo);
		return $userinfo;
	}
	
	
	
	
	
	
	public function getInfoWithPhoneNumber($phonenumber)
	{
		if($phonenumber=='')
		{
			return array();
		}
		
		$data = array();
		$username = $this->createPhoneUserName($phonenumber);
		$cacheData = CacheConnecter::get('passport', $username);
		if (empty($cacheData)) {
    		$db = DbConnecter::connectMysql('share_passport');
    		$sql = "select * from passport where username=?";
    		$st = $db->prepare ( $sql );
    		$st->execute (array($username));
    		$data = $st->fetch(PDO::FETCH_ASSOC);
    		$db=null;
    		if (!empty($data)) {
    		    CacheConnecter::set('passport',	$username, $data, 2592000);
    		}
    		
    		return $data;
		} else {
		    return $cacheData;
		}
	}
	
	public function getInfoWithUid($uid)
	{
		if($uid=='')
		{
			return array();
		}

		$data = array();
		$cacheData = CacheConnecter::get('passport', $uid);
		if (empty($cacheData)) {
    		$db = DbConnecter::connectMysql('share_passport');
    		$sql = "select * from passport where uid=?";
    		$st = $db->prepare ( $sql );
    		$st->execute (array($uid));
    		$data = $st->fetch(PDO::FETCH_ASSOC);
    		$db=null;
    		$data['phonenumber'] = '';
    		if($data['phonenumber']!="")
    		{
    			$data['phonenumber'] = substr($data['username'],2);
    		}
    		if (!empty($data)) {
    		    CacheConnecter::set('passport',	$uid, $data, 86400);
    		}
    		
    		return $data;
		} else {
		    return $cacheData;
		}
	}
	
	public function getInfoWithUids($uids)
	{
	    if(empty($uids)) {
	        return array();
	    }
	    if (!is_array($uids)) {
	        $uids = array($uids);
	    }
	    $data = array();
	    $cacheData = CacheConnecter::get('passport', $uids);
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
	     
	    if(!empty($dbIds)) {
	        $result = array();
	        $uidStr = implode(',', $dbIds);
	        	
	        $db = DbConnecter::connectMysql('share_passport');
	        $sql = "select * from passport where uid in ($uidStr)";
	        $st = $db->prepare ( $sql );
	        $st->execute ();
	        $tmpDbData = $st->fetchAll(PDO::FETCH_ASSOC);
	        $db=null;
	        foreach ($tmpDbData as $onedbdata){
	            $dbData[$onedbdata['uid']] = $onedbdata;
	            CacheConnecter::set('passport', $onedbdata['uid'], $onedbdata, 864000);
	        }
	    }
	    
	    foreach($uids as $uid) {
	        if(in_array($uid, $dbIds)) {
	            $data[$uid] = $dbData[$uid];
	        }else{
	            $data[$uid] = $cacheData[$uid];
	        }
	    }
	    
	    return $data;
	}
	
	public function setLoginType($uid,$logintype)
	{
		if($logintype=="")
		{
			return false;
		}
		CacheConnecter::set('passport',	"logintype:".$uid, $logintype, -1);
		return true;
	}
	public function getLoginType($uid)
	{
		$logintype=CacheConnecter::get('passport',	"logintype:".$uid);
		if($logintype=="" || $logintype==false)
		{
			$passportinfo = $this->getInfoWithUid($uid);
			if($passportinfo['username']=="QL")
			{
				$logintype='qq';
			} elseif ($passportinfo['username']=="WL") {
			    $logintype='wb';
			} elseif ($passportinfo['username']=="FL") {
			    $logintype='fb';
			} elseif ($passportinfo['username']=="TL") {
			    $logintype='tt';
			} else {
				$logintype='phone';
			}
		}
		return $logintype;
	}
	
	
	public function clearPassportCacheByUid($uid)
	{
	    return CacheConnecter::deleteMulti('passport', $uid);
	}
	public function clearPassportCacheByUserName($userName)
	{
	    return CacheConnecter::deleteMulti('passport', $userName);
	}
	
	
	protected function createPhoneUserName($phonenumber)
	{
		$username = 'CM'.$phonenumber;
		return $username;
	}
	
	protected function setSsoCookie($passportdata,$userinfo)
	{
		$R['uid'] = $passportdata['uid'];
		$R['nickname'] = $userinfo['nickname'];
		$R['password'] = @$passportdata['password'];
		
		if ($_SERVER['visitorappversion'] > 167000000 || ($R['uid'] >= 1084000 && $_SERVER['visitorappversion'] == 167000000)) {
		    $domain = 'tutuim.com';
		} else {
		    $domain = '';
		}
		setcookie('us',$this->makeCookie($R,'us'),time()+60 * 86400,'/',$domain,false,true);
		setcookie('ui',$this->makeCookie($R,'ui'),time()+60 * 86400,'/',$domain,false,false);
		setcookie('al',$this->makeCookie($R,'al'),time()+60 * 86400,'/',$domain,false,true);
	}
	
	private function parseSession() {
	    if (! isset($this->cookies['us'])) {
	        return array();
	    }
	    
	    $this->cookies['us'] = str_replace("\"", "", $this->cookies['us']);
	    parse_str($this->abacaEncrypt($this->cookies['us']), $info);
	    if (! isset($info['uid']) || ! isset($info['cert']) || $this->md5Together($info['uid'], $_SERVER['CONFIG']['defaultEncryptKey']) != $info['cert']) {
	        /* $encrypt = $this->abacaEncrypt($this->cookies['us']);
	        $isEmptyUs = 0;
	        if (empty($encrypt)) {
	            $isEmptyUs = 1;
	        }
	        QueueManager::pushCookieLog("error", $this->cookies['us'], $isEmptyUs); */
	        
	        $this->logout();
	        return array();
	    }
	    return $info;
	}
	
	private function makeCookie($R, $type = 'us') {
		switch ($type) {
			case 'us' :
				$cookieInfo = array(
					'uid' => $R['uid'],
					'nickname' => $R['nickname'],
					'cert' => $this->md5Together($R['uid'],  $_SERVER['CONFIG']['defaultEncryptKey']),
				);
				$GLOBALS['_SESSION']['uid'] = $R['uid'];
				$GLOBALS['_SESSION']['nickname'] = $R['nickname'];
				$GLOBALS['_SESSION']['cert'] = $this->md5Together($R['uid'],  $_SERVER['CONFIG']['defaultEncryptKey']);
				$cookie = $this->abacaEncrypt(http_build_query($cookieInfo), 'ENCODE');
				break;
			case 'ui' :
				$cookieInfo = array(
				'uid' => $R['uid'],
				'username' => $R['nickname'],
				);
				$cookie = http_build_query($cookieInfo);
				break;
			case 'al' :
				$cookieInfo = array(
				'uid' => $R['uid'],
				'cert' => $this->md5Together($R['uid'], $R['password'])
				);
				$cookie = $this->abacaEncrypt(http_build_query($cookieInfo), 'ENCODE');
				break;
	
			default :
				$cookie = '';
		}
		return $cookie;
	}
	
	
	public function autoLogin()
	{
		if (isset($this->cookies['us']) || ! isset($this->cookies['al'])) {
			return false;
		}
		
		$domain = 'tutuim.com';
		$alCookieValue = $this->cookies['al'];
		parse_str($this->abacaEncrypt($alCookieValue), $info);
		if (! isset($info['uid']) || ! isset($info['cert']) || intval($info['uid']) <= 0) {
		    setcookie('al', '', time() - 86400, '/', "");
		    setcookie('al', '', time() - 86400, '/', $domain);
			return false;
		}
		$uid = intval($info['uid']);
		$passportdata = $this->getInfoWithUid($uid);
		$UserObj =  new User();
		$userinfo = current($UserObj->getUserInfo($uid));
		
		if (!empty($passportdata['password'])) {
			if ($this->md5Together($uid, $passportdata['password']) != $info['cert']) {
			    setcookie('al', '', time() - 86400, '/', "");
				setcookie('al', '', time() - 86400, '/', $domain);
				return false;
			}
			$this->setSsoCookie($passportdata, $userinfo);
		}
		return true;
	}
	
	public function getMaxUid()
	{
		$db = DbConnecter::connectMysql('share_passport');
		$sql = "select max(uid) from  passport";
		$st = $db->prepare ( $sql );
		$st->execute ();
		$maxuid = $st->fetch(PDO::FETCH_COLUMN);
	
		return $maxuid;
	}
	
	public function getMaxUidWithTime($day)
	{
		$day = $day+0;
		if($day==0)
		{
			$day=1;
		}
		$db = DbConnecter::connectMysql('share_passport');
		$sql = "select * from passport where addtime>? order by uid asc limit 1";
		$st = $db->prepare ( $sql );
		$st->execute (array(date('Y-m-d H:i:s',time()-86400*$day)));
		$maxuid = $st->fetch(PDO::FETCH_COLUMN);
		
		return $maxuid;	
	}
	
	
	private function md5Together($a = '', $b = '') {
		return md5(substr(md5($a), 13, 6) . substr(md5($b), 17, 6));
	}
	private function abacaEncrypt($string, $operation = 'DECODE', $key = '', $expiry = 0) {
		$ckey_length = 4;
	
		$key = md5 ( $key ? $key : $_SERVER['CONFIG']['defaultEncryptKey']);
		$keya = md5 ( substr ( $key, 0, 16 ) );
		$keyb = md5 ( substr ( $key, 16, 16 ) );
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr ( $string, 0, $ckey_length ) : substr ( md5 ( microtime () ), - $ckey_length )) : '';
		$cryptkey = $keya . md5 ( $keya . $keyc );
		$key_length = strlen ( $cryptkey );
	
		$string = $operation == 'DECODE' ? base64_decode ( substr ( $string, $ckey_length ) ) : sprintf ( '%010d', $expiry ? $expiry + time () : 0 ) . substr ( md5 ( $string . $keyb ), 0, 16 ) . $string;
		$string_length = strlen ( $string );
	
		$result = '';
		$box = range ( 0, 255 );
	
		$rndkey = array ();
		for($i = 0; $i <= 255; $i ++) {
			$rndkey [$i] = ord ( $cryptkey [$i % $key_length] );
		}
	
		for($j = $i = 0; $i < 256; $i ++) {
			$j = ($j + $box [$i] + $rndkey [$i]) % 256;
			$tmp = $box [$i];
			$box [$i] = $box [$j];
			$box [$j] = $tmp;
		}
	
		for($a = $j = $i = 0; $i < $string_length; $i ++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box [$a]) % 256;
			$tmp = $box [$a];
			$box [$a] = $box [$j];
			$box [$j] = $tmp;
			$result .= chr ( ord ( $string [$i] ) ^ ($box [($box [$a] + $box [$j]) % 256]) );
		}
	
		if ($operation == 'DECODE') {
			if ((substr ( $result, 0, 10 ) == 0 || substr ( $result, 0, 10 ) - time () > 0) && substr ( $result, 10, 16 ) == substr ( md5 ( substr ( $result, 26 ) . $keyb ), 0, 16 )) {
				return substr ( $result, 26 );
			} else {
				return '';
			}
		} else {
			return $keyc . str_replace ( '=', '', base64_encode ( $result ) );
		}
	}
	
	
	
}