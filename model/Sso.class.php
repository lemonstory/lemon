<?php
class Sso extends ModelBase 
{
    private $cookies;
    private $domain = '120.26.52.211';
    public $PASSPORT_DB_INSTANCE = 'share_main';
    public $PASSPORT_TABLE_NAME = 'passport';
    public $QQ_RELATION_TABLE_NAME = 'user_qq_relation';
    public $WECHAT_RELATION_TABLE_NAME = 'user_wechat_relation';
    
    public $CACHE_INSTANCE = 'cache';
    
    public function __construct() 
    {
        $this->cookies = $_COOKIE;
    }
    
    public function checkQqLoginFirst($openId) 
    {
        $key = RedisKey::getQqLoginFirstKey($openId);
        $redisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $cacheData = $redisObj->get($key);
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
                $redisObj->setex($key, 604800, 1);
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
        $key = RedisKey::getQqRelationInfoKey($uid);
        $redisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $cacheData = $redisObj->get($key);
        if (empty($cacheData)) {
            $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
            $sql = "select * from {$this->QQ_RELATION_TABLE_NAME} where uid = ?";
            $st = $db->prepare($sql);
            $st->execute(array($uid));
            $info = $st->fetch(PDO::FETCH_ASSOC);
            $db = null;
            if (! empty($info)) {
                $redisObj->setex($key, 86400, serialize($info));
            }
            return $info;
        } else {
            return unserialize($cacheData);
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
        $province  = $qqUserInfo['province'];
        $city      = $qqUserInfo['city'];
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
        $st->execute(array('QL', $qquserpasword, $addtime));
        $uid = $db->lastInsertId() + 0;
        if ($uid == 0) {
            return false;
        }
        
        $sql = "insert into {$this->QQ_RELATION_TABLE_NAME} (openid,uid,accesstoken,addtime) values (?,?,?,?)";
        $st = $db->prepare($sql);
        $st->execute(array($openId, $uid, $accessToken, $addtime ));
        
        $NicknameMd5Obj->addOne($nickName, $uid);
        
        $avatartime = 0;
        $UserObj = new User();
        $type = $UserObj->TYPE_QQ;
        $UserObj->initUser($uid, $nickName, $avatartime, $birthday, $gender, $province, $city, $type, $addtime);
        $this->setSsoCookie(array('uid' => $uid, 'password' => $qquserpasword), array('nickname' => $nickName));
        
        if ($qqavatar != "") {
            MnsQueueManager::pushLoadUserQqavatar($uid, $qqavatar);
        }
        
        // 登录后的处理
        $actionlogobj = new ActionLog();
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid();
        MnsQueueManager::pushActionLogQueue($uimid, $uid, $actionlogobj->ACTION_TYPE_LOGIN);
        
        // add login log
        $loginlogobj = new UserLoginLog();
        $loginlogobj->addUserLoginLog($uid, getImsi());
        
        $return = array('uid' => $uid, 'nickname' => $nickName, 'avatartime' => time());
        return $return;
    }
    
    public function qqlogin($accessToken, $openId) 
    {
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        $sql = "update {$this->QQ_RELATION_TABLE_NAME} set accesstoken=? where openid=?";
        $st = $db->prepare($sql);
        $st->execute(array($accessToken, $openId));
        
        $sql = "select * from {$this->QQ_RELATION_TABLE_NAME} where openid=?";
        $st = $db->prepare($sql);
        $st->execute(array($openId));
        $ar = $st->fetch(PDO::FETCH_ASSOC);
        $uid = $ar['uid'];
        
        $passportdata = $this->getInfoWithUid($uid);
        $UserObj = new User();
        $userinfo = current($UserObj->getUserInfo($uid, 1));
        
        $this->setSsoCookie($passportdata, $userinfo);
        
        // 登录后的处理
        $actionlogobj = new ActionLog();
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid();
        MnsQueueManager::pushActionLogQueue($uimid, $uid, $actionlogobj->ACTION_TYPE_LOGIN);
        
        // add login log
        $loginlogobj = new UserLoginLog();
        $loginlogobj->addUserLoginLog($uid, getImsi());
        
        return $userinfo;
    }
    
    
    /**
     * 用于报警规则测试
     * @param S $username
     * @param S $password
     * @return boolean|mixed
     */
    public function phonelogin($username, $password)
    {
        if (empty($username) || empty($password)) {
            $this->setError(ErrorConf::paramError());
            return false;
        }
        
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        $sql = "select * from {$this->PASSPORT_TABLE_NAME} where username = ?";
        $st = $db->prepare ( $sql );
        $st->execute (array($username));
        $passportdata = $st->fetch(PDO::FETCH_ASSOC);
        if(empty($passportdata)) {
            $this->setError(ErrorConf::userNoExist());
            return false;
        }
        
        $uid = $passportdata['uid'];
        if($passportdata['password'] != md5($password . strrev(strtotime($passportdata['addtime'])))){
            $this->setError(ErrorConf::userPasswordIsError());
            return false;
        }
        
        $UserObj = new User();
        $userinfo = current($UserObj->getUserInfo($uid, 1));
        if (!empty($userinfo['status']) && $userinfo['status'] == '-2') {
            $this->showErrorJson(ErrorConf::userForbidenPost());
        }
        
        $ssoobj = new Sso();
        $ssoobj->setSsoCookie($passportdata, $userinfo);
        
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
        $key = RedisKey::getUserInfoKey($uid);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $cacheData = $redisobj->get($key);
        if (empty($cacheData)) {
            $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
            $sql = "select * from {$this->PASSPORT_TABLE_NAME} where uid=?";
            $st = $db->prepare($sql);
            $st->execute(array($uid));
            $data = $st->fetch(PDO::FETCH_ASSOC);
            $db = null;
            if (! empty($data)) {
                $redisobj->setex($key, 86400, serialize($data));
            }
            
            return $data;
        } else {
            return unserialize($cacheData);
        }
    }
    
    public function getInfoWithUids($uids) 
    {
        if (empty($uids)) {
            return array();
        }
        if (! is_array($uids)) {
            $uids = array($uids);
        }
        $data = array();
        $getkeys = RedisKey::getUserInfoKeys($uids);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $cacheData = $redisobj->mget($getkeys);
        if (is_array($cacheData)) {
            foreach ($cacheData as $onecachedata) {
                if (empty($onecachedata)) {
                    continue;
                }
                $onecachedata = unserialize($onecachedata);
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
            
            $setkeys = array();
            foreach ($tmpDbData as $onedbdata) {
                $dbData[$onedbdata['uid']] = $onedbdata;
                $redisobj->setex($onedbdata['uid'], 86400, serialize($onedbdata));
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
    
    
    public function autoLogin()
    {
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
        $userinfo = current($UserObj->getUserInfo($uid, 1));
    
        if (! empty($passportdata['password'])) {
            if ($this->md5Together($uid, $passportdata['password']) != $info['cert']) {
                setcookie('al', '', time() - 86400, '/', $domain);
                return false;
            }
            $this->setSsoCookie($passportdata, $userinfo);
        }
        return true;
    }
    
    
    public function clearPassportCacheByUid($uid) 
    {
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($uid);
    }
    public function clearPassportCacheByUserName($userName) 
    {
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        return $redisobj->delete($userName);
    }
    
    public function setSsoCookie($passportdata, $userinfo) 
    {
        $R['uid'] = $passportdata['uid'];
        $R['nickname'] = $userinfo['nickname'];
        $R['password'] = @$passportdata['password'];
        $domain = $this->domain;
        
        setcookie('us', $this->makeCookie($R, 'us'), time() + 60 * 86400, '/', $domain, false, true);
        setcookie('ui', $this->makeCookie($R, 'ui'), time() + 60 * 86400, '/', $domain, false, false);
        setcookie('al', $this->makeCookie($R, 'al'), time() + 60 * 86400, '/', $domain, false, true);
    }
    
    public function setCsrfCookie($csrftoken)
    {
        // 设置csrf密钥到cookie,有效时间是60秒
        setcookie('csrftoken', $csrftoken, time() + 60, '/', $this->domain, false, true);
        return true;
    }
    
    private function parseSession() 
    {
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
    
    private function makeCookie($R, $type = 'us') 
    {
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
    
    
    private function md5Together($a = '', $b = '') 
    {
        return md5(substr(md5($a), 13, 6) . substr(md5($b), 17, 6));
    }
    private function abacaEncrypt($string, $operation = 'DECODE', $key = '', $expiry = 0) 
    {
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