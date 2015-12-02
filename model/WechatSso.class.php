<?php
/*
 * 微信SSO授权
 */
class WechatSso extends Sso
{
    public $GENDER_LIST = array('m' => 1, 'f' => 2, 'n' => 0);
    
    
	public function checkWechatLoginFirst($openId) 
    {
        $key = RedisKey::getWechatLoginFirstKey($openId);
        $redisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $cacheData = $redisObj->get($key);
        if (empty($cacheData)) {
            $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
            $sql = "select count(1) from  {$this->WECHAT_RELATION_TABLE_NAME} where openid=?";
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
    
    public function getWechatRelationInfoWithUid($uid) 
    {
        if (empty($uid)) {
            return array();
        }
        
        $key = RedisKey::getWechatRelationInfoKey($uid);
        $redisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $cacheData = $redisObj->get($key);
        if (empty($cacheData)) {
            $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
            $sql = "select * from {$this->WECHAT_RELATION_TABLE_NAME} where uid = ?";
            $st = $db->prepare($sql);
            $st->execute(array(
                    $uid 
            ));
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
     * 获取开放平台信息
     * @param S $accessToken
     * @param S $openId
     * @return array
     */
    private function getWechatInfo($accessToken, $openId) 
    {
        $weixinUserUrl = "https://api.weixin.qq.com/sns/userinfo?access_token={$accessToken}&openid={$openId}";
        $getInfo = Http::request($weixinUserUrl);
    	if (empty($getInfo)) {
            $this->setError(ErrorConf::wechatAuthInfoEmpty());
            return array();
        }
        
        $wechatinfo = array();
        $jsonInfo = json_decode($getInfo, true);
        if (!empty($jsonInfo) && isset($jsonInfo['nickname'])) {
            $wechatinfo['nickname']   = $jsonInfo['nickname'];
            $wechatinfo['gender']     = $jsonInfo['sex'];
            $wechatinfo['avatarurl'] = $jsonInfo['headimgurl'];
            
            return $wechatinfo;
        } else {
            return array();
        }
    }
    
    public function initWechatLoginUser($accessToken, $openId, $nickName) 
    {
        if (empty($accessToken) || empty($openId) || empty($nickName)) {
            return false;
        }
        
        $NicknameMd5Obj = new NicknameMd5();
        if ($NicknameMd5Obj->checkNameIsExist($nickName)) {
            $nickName = "xnm_" . rand(100, 999) . time();
        }
        
        $wechatinfo = $this->getWechatInfo($accessToken, $openId);
        if (empty($wechatinfo)) {
            return false;
        }
        
        $gender = $wechatinfo['gender'];
        $avatarurl = $wechatinfo['avatarurl'];
        
        $nowtime = time();
        $addtime = date('Y-m-d H:i:s', $nowtime);
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        
        $userpasword = md5('WXL' . $nowtime);
        $sql = "insert into {$this->PASSPORT_TABLE_NAME} (username,password,addtime) values (?,?,?)";
        $st = $db->prepare($sql);
        $st->execute(array('WXL', $userpasword, $addtime));
        $uid = $db->lastInsertId() + 0;
        if ($uid == 0) {
            return false;
        }
        
        $sql = "insert into {$this->WECHAT_RELATION_TABLE_NAME} (openid,uid,accesstoken,addtime) values (?,?,?,?)";
        $st = $db->prepare($sql);
        $st->execute(array($openId, $uid, $accessToken, $addtime ));
        
        $NicknameMd5Obj->addOne($nickName, $uid);
        
        $avatartime = 0;
        if ($avatarurl != "") {
            MnsQueueManager::pushLoadUserQqavatar($uid, $avatarurl);
        }
        
        $UserObj = new User();
        $type = $UserObj->TYPE_WX;
        $UserObj->initUser($uid, $nickName, $avatartime, $type, $addtime);
        $this->setSsoCookie(array('uid' => $uid, 'password' => $userpasword), array('nickname' => $nickName));
        
        // 登录后的处理
        $actionlogobj = new ActionLog();
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid($uid);
        MnsQueueManager::pushActionLogQueue($uimid, $uid, $actionlogobj->ACTION_TYPE_LOGIN);
        
        // add login log
        $loginlogobj = new UserLoginLog();
        $loginlogobj->addUserLoginLog($uid, getImsi());
        
        $alislsobj = new AliSlsUserActionLog();
        $alislsobj->addRegisterActionLog($uimid, $uid, getClientIp(), $addtime);
        
        $return = array('uid' => $uid, 'nickname' => $nickName, 'avatartime' => time());
        return $return;
    }
    
    public function wechatLogin($accessToken, $openId) 
    {
        $db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
        $sql = "update {$this->WECHAT_RELATION_TABLE_NAME} set accesstoken=? where openid=?";
        $st = $db->prepare($sql);
        $st->execute(array($accessToken, $openId));
        
        $sql = "select * from {$this->WECHAT_RELATION_TABLE_NAME} where openid=?";
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
        $uimid = $userimsiobj->getUimid($uid);
        MnsQueueManager::pushActionLogQueue($uimid, $uid, $actionlogobj->ACTION_TYPE_LOGIN);
        
        // add login log
        $loginlogobj = new UserLoginLog();
        $loginlogobj->addUserLoginLog($uid, getImsi());
        
        return $userinfo;
    }
    
}
?>