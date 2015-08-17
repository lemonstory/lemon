<?php
class Sso extends ModelBase 
{
    private $cookies;
    private $domain = 'lemon.com';
    public $PASSPORT_DB_INSTANCE = 'share_main';
    public $PASSPORT_TABLE_NAME = 'passport';
    public $QQ_RELATION_TABLE_NAME = 'user_qq_relation';
    
    public $CACHE_INSTANCE = '';
    
    public function __construct() 
    {
        $this->cookies = $_COOKIE;
    }
    
    public function checkQqLoginFirst($openId) 
    {
        $key = 'qqrecount_' . $openId;
        $cacheData = CacheConnecter::get($this->CACHE_INSTANCE, $key);
        $cacheData = array();
        if (empty($cacheData)) {
            $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
            $sql = "select count(1) from  {$this->QQ_RELATION_TABLE_NAME} where openid=?";
            $st = $db->prepare($sql);
            $re = $st->execute(array(
                    $openId 
            ));
            $count = $st->fetch(PDO::FETCH_COLUMN);
            $db = null;
            if ($count == 1) {
                CacheConnecter::set($this->CACHE_INSTANCE, $key, $count, 30 * 86400);
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
        $cacheData = CacheConnecter::get($this->CACHE_INSTANCE, $key);
        $cacheData = array();
        if (empty($cacheData)) {
            $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
            $sql = "select * from {$this->QQ_RELATION_TABLE_NAME} where uid = ?";
            $st = $db->prepare($sql);
            $st->execute(array(
                    $uid 
            ));
            $info = $st->fetch(PDO::FETCH_ASSOC);
            $db = null;
            if (! empty($info)) {
                CacheConnecter::set($this->CACHE_INSTANCE, $key, $info, 86400);
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
        if (empty($getInfo)) {
            $this->setError(ErrorConf::qqUserInfoEmpty());
            return array();
        }
        
        $qqUserInfo = array();
        $qqUserInfo['nickName'] = $getInfo['nickname'];
        
        $gender = 0;
        $gendertxt = $getInfo['gender'];
        if ($gendertxt == '男') {
            $gender = 1;
        }
        if ($gendertxt == '女') {
            $gender = 2;
        }
        $qqUserInfo['gender'] = $gender;
        $qqUserInfo['province'] = $getInfo['province'];
        $qqUserInfo['city'] = $getInfo['city'];
        $qqUserInfo['year'] = $getInfo['year'];
        $qqUserInfo['qqAvatar'] = $getInfo['figureurl_qq_2'];
        
        return $qqUserInfo;
    }
    
    public function initQqLoginUser($qc, $accessToken, $openId, $nickName) 
    {
        if (empty($qc) || empty($accessToken) || empty($openId) || empty($nickName)) {
            return false;
        }
        
        $NicknameMd5Obj = new NicknameMd5();
        if ($NicknameMd5Obj->checkNameIsExist($nickName)) {
            $this->setError(ErrorConf::nickNameIsExist());
            return false;
        }
        
        $qqUserInfo = $this->getQqInfo($qc);
        if (empty($qqUserInfo)) {
            return false;
        }
        
        $gender = $qqUserInfo['gender'];
        //$province  = $qqUserInfo['province'];
        //$city      = $qqUserInfo['city'];
        $year = $qqUserInfo['year'];
        $qqavatar = $qqUserInfo['qqAvatar'];
        $birthday = '';
        if (! empty($year)) {
            $birthday = $year . "-01-01";
        }
        
        $addtime = date('Y-m-d H:i:s');
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        
        $qquserpasword = md5('QL' . time());
        $sql = "insert into {$this->PASSPORT_TABLE_NAME} (username,password,addtime) values (?,?,?)";
        $st = $db->prepare($sql);
        $st->execute(array(
                'QL',
                $qquserpasword,
                $addtime 
        ));
        $uid = $db->lastInsertId() + 0;
        if ($uid == 0) {
            return false;
        }
        
        $sql = "insert into {$this->QQ_RELATION_TABLE_NAME} (openid,uid,accesstoken,addtime) values (?,?,?,?)";
        $st = $db->prepare($sql);
        $st->execute(array(
                $openId,
                $uid,
                $accessToken,
                $addtime 
        ));
        
        $NicknameMd5Obj->addOne($nickName, $uid);
        
        $avatartime = 0;
        if ($qqavatar != "") {
            QueueManager::pushLoadUserQqavatar($uid, $qqavatar);
        }
        
        $UserObj = new User();
        $UserObj->initQQLoginUser($uid, $nickName, $avatartime, $gender, $birthday, $addtime);
        
        $this->setSsoCookie(array('uid' => $uid, 'pasword' => $qquserpasword), array('nickname' => $nickName));
        
        $return = array('uid' => $uid, 'nickname' => $nickName, 'avatartime' => time());
        
        QueueManager::pushAfterRegQueue($uid);
        QueueManager::pushUserInfoToSearch($uid);
        return $return;
    }
    
    public function qqlogin($accessToken, $openId) 
    {
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        $sql = "update {$this->QQ_RELATION_TABLE_NAME} set accesstoken=? where openid=?";
        $st = $db->prepare($sql);
        $st->execute(array(
                $accessToken,
                $openId 
        ));
        
        $sql = "select * from {$this->QQ_RELATION_TABLE_NAME} where openid=?";
        $st = $db->prepare($sql);
        $st->execute(array(
                $openId 
        ));
        $ar = $st->fetch(PDO::FETCH_ASSOC);
        $uid = $ar['uid'];
        
        $passportdata = $this->getInfoWithUid($uid);
        $UserObj = new User();
        //$this->setLoginType($uid, 'qq');
        $userinfo = $UserObj->getSelfInfo($uid);
        
        $this->setSsoCookie($passportdata, $userinfo);
        return $userinfo;
    }
    
    public function logout() 
    {
        $domain = $this->domain;
        $GLOBALS['_SESSION'] = array();
        unset($this->cookies['us']);
        unset($this->cookies['al']);
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
            $GLOBALS['_SESSION']['cert'] = '';
        }
        if (empty($GLOBALS['_SESSION']['uid'])) {
            $this->autoLogin();
        }
        return $_SESSION['uid'] + 0;
    }
    
    public function getInfoWithUid($uid) 
    {
        if ($uid == '') {
            return array();
        }
        
        $data = array();
        $cacheData = CacheConnecter::get($this->CACHE_INSTANCE, $uid);
        $cacheData = array();
        if (empty($cacheData)) {
            $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
            $sql = "select * from {$this->PASSPORT_TABLE_NAME} where uid=?";
            $st = $db->prepare($sql);
            $st->execute(array(
                    $uid 
            ));
            $data = $st->fetch(PDO::FETCH_ASSOC);
            $db = null;
            $data['phonenumber'] = '';
            if ($data['phonenumber'] != "") {
                $data['phonenumber'] = substr($data['username'], 2);
            }
            if (! empty($data)) {
                CacheConnecter::set($this->CACHE_INSTANCE, $uid, $data, 86400);
            }
            
            return $data;
        } else {
            return $cacheData;
        }
    }
    
    public function getInfoWithUids($uids) {
        if (empty($uids)) {
            return array();
        }
        if (! is_array($uids)) {
            $uids = array(
                    $uids 
            );
        }
        $data = array();
        $cacheData = CacheConnecter::get($this->CACHE_INSTANCE, $uids);
        $cacheData = array();
        $cacheIds = array();
        if (is_array($cacheData)) {
            foreach ($cacheData as $onecachedata) {
                $cacheIds[] = $onecachedata['uid'];
            }
        } else {
            $cacheData = array();
        }
        
        $dbIds = array_diff($uids, $cacheIds);
        $dbData = array();
        
        if (! empty($dbIds)) {
            $result = array();
            $uidStr = implode(',', $dbIds);
            
            $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
            $sql = "select * from {$this->PASSPORT_TABLE_NAME} where uid in ($uidStr)";
            $st = $db->prepare($sql);
            $st->execute();
            $tmpDbData = $st->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            foreach ($tmpDbData as $onedbdata) {
                $dbData[$onedbdata['uid']] = $onedbdata;
     			CacheConnecter::set($this->CACHE_INSTANCE, $onedbdata['uid'], $onedbdata, 864000);
            }
        }
        
        foreach ($uids as $uid) {
            if (in_array($uid, $dbIds)) {
                $data[$uid] = $dbData[$uid];
            } else {
                $data[$uid] = $cacheData[$uid];
            }
        }
        
        return $data;
    }
    
    /*public function setLoginType($uid,$logintype)
	{
		if($logintype=="")
		{
			return false;
		}
		CacheConnecter::set('passport',	"logintype:".$uid, $logintype, -1);
		return true;
	}*/
    /*public function getLoginType($uid)
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
	}*/
    
    public function clearPassportCacheByUid($uid) {
        return CacheConnecter::deleteMulti($this->CACHE_INSTANCE, $uid);
    }
    public function clearPassportCacheByUserName($userName) {
        return CacheConnecter::deleteMulti($this->CACHE_INSTANCE, $userName);
    }
    
    protected function setSsoCookie($passportdata, $userinfo) {
        $R['uid'] = $passportdata['uid'];
        $R['nickname'] = $userinfo['nickname'];
        $R['password'] = @$passportdata['password'];
        $domain = $this->domain;
        
        setcookie('us', $this->makeCookie($R, 'us'), time() + 60 * 86400, '/', $domain, false, true);
        setcookie('ui', $this->makeCookie($R, 'ui'), time() + 60 * 86400, '/', $domain, false, false);
        setcookie('al', $this->makeCookie($R, 'al'), time() + 60 * 86400, '/', $domain, false, true);
    }
    
    private function parseSession() {
        if (! isset($this->cookies['us'])) {
            return array();
        }
        
        $this->cookies['us'] = str_replace("\"", "", $this->cookies['us']);
        parse_str($this->abacaEncrypt($this->cookies['us']), $info);
        if (! isset($info['uid']) || ! isset($info['cert']) || $this->md5Together($info['uid'], $_SERVER['CONFIG']['defaultEncryptKey']) != $info['cert']) {
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
                        'cert' => $this->md5Together($R['uid'], $_SERVER['CONFIG']['defaultEncryptKey']) 
                );
                $GLOBALS['_SESSION']['uid'] = $R['uid'];
                $GLOBALS['_SESSION']['nickname'] = $R['nickname'];
                $GLOBALS['_SESSION']['cert'] = $this->md5Together($R['uid'], $_SERVER['CONFIG']['defaultEncryptKey']);
                $cookie = $this->abacaEncrypt(http_build_query($cookieInfo), 'ENCODE');
                break;
            case 'ui' :
                $cookieInfo = array(
                        'uid' => $R['uid'],
                        'username' => $R['nickname'] 
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
    
    public function autoLogin() {
        if (isset($this->cookies['us']) || ! isset($this->cookies['al'])) {
            return false;
        }
        
        $domain = $this->domain;
        $alCookieValue = $this->cookies['al'];
        parse_str($this->abacaEncrypt($alCookieValue), $info);
        if (! isset($info['uid']) || ! isset($info['cert']) || intval($info['uid']) <= 0) {
            setcookie('al', '', time() - 86400, '/', $domain);
            return false;
        }
        $uid = intval($info['uid']);
        $passportdata = $this->getInfoWithUid($uid);
        $UserObj = new User();
        $userinfo = current($UserObj->getUserInfo($uid));
        
        if (! empty($passportdata['password'])) {
            if ($this->md5Together($uid, $passportdata['password']) != $info['cert']) {
                setcookie('al', '', time() - 86400, '/', $domain);
                return false;
            }
            $this->setSsoCookie($passportdata, $userinfo);
        }
        return true;
    }
    
    /*public function getMaxUid()
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
	}*/
    
    private function md5Together($a = '', $b = '') {
        return md5(substr(md5($a), 13, 6) . substr(md5($b), 17, 6));
    }
    private function abacaEncrypt($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        $ckey_length = 4;
        
        $key = md5($key ? $key : $_SERVER['CONFIG']['defaultEncryptKey']);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), - $ckey_length)) : '';
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        
        $result = '';
        $box = range(0, 255);
        
        $rndkey = array();
        for($i = 0; $i <= 255; $i ++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        
        for($j = $i = 0; $i < 256; $i ++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        
        for($a = $j = $i = 0; $i < $string_length; $i ++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        
        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
}