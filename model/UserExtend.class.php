<?php
class UserExtend extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $BABY_INFO_TABLE_NAME = 'user_baby_info';
	public $ADDRESS_INFO_TABLE_NAME = 'user_address_info';
	public $CACHE_INSTANCE = 'cache';
	
	/**
	 * 获取宝宝信息
	 * @param I $uid
	 * @return array
	 */
	public function getUserBabyInfo($babyids)
	{
		if (empty($babyids)) {
		    $this->setError(ErrorConf::paramError());
			return array();
		}
		if(!is_array($babyids)) {
		    $babyids = array($babyids);
		}
		$data = array();
		$keys = RedisKey::getBabyInfoKeys($babyids);
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
		        $cacheIds[] = $oneredisdata['id'];
		        $cacheData[$oneredisdata['id']] = $oneredisdata;
		    }
		} else {
		    $redisData = array();
		}
		
		$dbIds = array_diff($babyids, $cacheIds);
		$dbData = array();
		if(!empty($dbIds)) {
		    $idlist = implode(',', $dbIds);
    		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
    		$sql = "SELECT * FROM {$this->BABY_INFO_TABLE_NAME} WHERE `id` IN ($idlist)";
    		$st = $db->prepare($sql);
    		$st->execute();
    		$tmpDbData = $st->fetchAll(PDO::FETCH_ASSOC);
    		$db = null;
    		if (!empty($tmpDbData)) {
    		    foreach ($tmpDbData as $onedbdata){
    		        $dbData[$onedbdata['id']] = $onedbdata;
    		        $bikey = RedisKey::getBabyInfoKey($onedbdata['id']);
    		        $redisobj->setex($bikey, 604800, serialize($onedbdata));
    		    }
    		}
		}
		
		foreach($babyids as $id) {
		    if(in_array($id, $dbIds)) {
		        $data[$id] = @$dbData[$id];
		    } else {
		        $data[$id] = $cacheData[$id];
		    }
		}
		
		$result = array();
		foreach ($data as $one) {
		    if(empty($one)) {
		        continue;
		    }
		    $one = $this->formatUserBaseInfo($one);
		    $result[$one['id']] = $one;
		}
		
		return $result;
	}
	
	
	/**
	 * 获取指定用户的收货地址列表
	 * @param I $uid
	 * @return array
	 */
	public function getUserAddressList($uid)
	{
	    if (empty($uid)) {
	        return array();
	    }
	    
	    $key = RedisKey::getUserAddressListKey($uid);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    $redisData = $redisobj->get($key);
	    if (empty($redisData)) {
	        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	        $sql = "SELECT * FROM {$this->ADDRESS_INFO_TABLE_NAME} WHERE `uid` = ?";
	        $st = $db->prepare($sql);
	        $st->execute(array($uid));
	        $dbData = $st->fetchAll(PDO::FETCH_ASSOC);
	        $db = null;
	        if (empty($dbData)) {
	            return array();
	        }
	        $redisobj->setex($key, 86400, serialize($dbData));
	        return $dbData;
	    } else {
	        return unserialize($redisData);
	    }
	}
	
	/**
	 * 获取用户地址信息
	 * @param I $addressids
	 * @return array()
	 */
	public function getUserAddressInfo($addressids)
	{
	    if (empty($addressids)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    if(!is_array($addressids)) {
	        $addressids = array($addressids);
	    }
	    $data = array();
	    $keys = RedisKey::getAddressInfoKeys($addressids);
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
	            $cacheIds[] = $oneredisdata['id'];
	            $cacheData[$oneredisdata['id']] = $oneredisdata;
	        }
	    } else {
	        $redisData = array();
	    }
	    
	    $dbIds = array_diff($addressids, $cacheIds);
	    $dbData = array();
	    if(!empty($dbIds)) {
	        $idlist = implode(',', $dbIds);
	        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	        $sql = "SELECT * FROM {$this->ADDRESS_INFO_TABLE_NAME} WHERE `id` IN ($idlist)";
	        $st = $db->prepare($sql);
	        $st->execute();
	        $tmpDbData = $st->fetchAll(PDO::FETCH_ASSOC);
	        $db = null;
	        if (!empty($tmpDbData)) {
	            foreach ($tmpDbData as $onedbdata){
	                $dbData[$onedbdata['id']] = $onedbdata;
	                $aikey = RedisKey::getAddressInfoKey($onedbdata['id']);
	                $redisobj->setex($aikey, 604800, serialize($onedbdata));
	            }
	        }
	    }
	    
	    foreach($addressids as $id) {
	        if(in_array($id, $dbIds)) {
	            $data[$id] = @$dbData[$id];
	        } else {
	            $data[$id] = $cacheData[$id];
	        }
	    }
	    
	    $result = array();
	    foreach ($data as $one) {
	        if(empty($one)) {
	            continue;
	        }
	        $result[$one['id']] = $one;
	    }
	    
	    return $result;
	}
	
	/**
	 * 通过年龄获取年龄段
	 * @param I $age
	 * @return I
	 */
	public function getBabyAgeType($age)
	{
		if ($age <= 2) {
			$agetype = 1;
		} elseif ($age > 2 && $age <= 6) {
			$agetype = 2;
		} elseif ($age > 6 && $age <= 10) {
			$agetype = 3;
		} else {
			$agetype = 3;
		}
		return $agetype;
	}
	
	
	/**
	 * 用户添加宝宝资料
	 * @param I $uid
	 * @param S $birthday
	 * @param I $gender
	 * @return boolean
	 */
	public function addUserBabyInfo($uid, $birthday = "", $gender = 0)
	{
		if (empty($uid)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		$age = 0;
		if (!empty($birthday)) {
		    $age = getAgeFromBirthDay($birthday);
		}
		
		$addtime = date("Y-m-d H:i:s");
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->BABY_INFO_TABLE_NAME} 
			(`uid`, `birthday`, `gender`, `age`, `addtime`) 
			VALUES (?, ?, ?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uid, $birthday, $gender, $age, $addtime));
		if (empty($res)) {
		    return false;
		}
		$lastbabyid = $db->lastInsertId() + 0;
		return $lastbabyid;
	}
	
	public function updateUserBabyInfo($babyid, $uid, $updatedata)
	{
	    if (empty($babyid) || empty($uid) || empty($updatedata)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    if (!is_array($updatedata)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    if (!empty($updatedata['birthday'])) {
	        $updatedata['age'] = getAgeFromBirthDay($updatedata['birthday']);
	    }
	    
	    $babyinfo = current($this->getUserBabyInfo($babyid));
	    if (empty($babyinfo)) {
	        $this->setError(ErrorConf::userBabyInfoEmpty());
	        return false;
	    }
	    if ($babyinfo['uid'] != $uid) {
	        $this->setError(ErrorConf::userBabyInfoEmpty());
	        return false;
	    }
	    
	    $setstr = "";
	    foreach ($updatedata as $column => $value) {
	        $setstr .= "`{$column}` = '{$value}',";
	    }
	    $setstr = rtrim($setstr, ",");
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "UPDATE {$this->BABY_INFO_TABLE_NAME} SET {$setstr} WHERE `id` = ? AND `uid` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($babyid, $uid));
	    if ($res) {
	        $this->clearBabyinfoCache($babyid);
	        return true;
	    } else {
	        return false;
	    }
	}
	
	
	public function addUserAddressInfo($uid, $name = "", $phonenumber = "", $province = "", $city = "", $area = "", $address = "", $ecode = "")
	{
	    if (empty($uid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	
	    $addtime = date("Y-m-d H:i:s");
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "INSERT INTO {$this->ADDRESS_INFO_TABLE_NAME}
    	    (`uid`, `name`, `phonenumber`, `province`, `city`, `area`, `address`, `ecode`, `addtime`)
    	    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uid, $name, $phonenumber, $province, $city, $area, $address, $ecode, $addtime));
	    if (empty($res)) {
	        return false;
	    }
	    $lastaddressid = $db->lastInsertId() + 0;
	    return $lastaddressid;
	}
	
	public function updateUserAddressInfo($addressid, $uid, $updatedata)
	{
	    if (empty($addressid) || empty($uid) || empty($updatedata)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    if (!is_array($updatedata)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $addressinfo = current($this->getUserAddressInfo($addressid));
	    if (empty($addressinfo)) {
	        $this->setError(ErrorConf::userAddressInfoEmpty());
	        return false;
	    }
	    if ($addressinfo['uid'] != $uid) {
	        $this->setError(ErrorConf::userAddressInfoEmpty());
	        return false;
	    }
	    
	    $setstr = "";
	    foreach ($updatedata as $column => $value) {
	        $setstr .= "`{$column}` = '{$value}',";
	    }
	    $setstr = rtrim($setstr, ",");
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "UPDATE {$this->ADDRESS_INFO_TABLE_NAME} SET {$setstr} WHERE `id` = ? AND `uid` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($addressid, $uid));
	    if ($res) {
	        $this->clearAddressinfoCache($addressid);
	        return true;
	    } else {
	        return false;
	    }
	}
	
	public function delUserAddressInfo($addressid, $uid)
	{
	    if (empty($addressid) || empty($uid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $addressinfo = current($this->getUserAddressInfo($addressid));
	    if (empty($addressinfo)) {
	        $this->setError(ErrorConf::userAddressInfoEmpty());
	        return false;
	    }
	    if ($addressinfo['uid'] != $uid) {
	        $this->setError(ErrorConf::userAddressInfoEmpty());
	        return false;
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "DELETE FROM {$this->ADDRESS_INFO_TABLE_NAME} WHERE `id` = ? AND `uid` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($addressid, $uid));
	    if ($res) {
	        $this->clearAddressinfoCache($addressid);
	        return true;
	    } else {
	        return false;
	    }
	}
	
	public function clearBabyinfoCache($babyid)
	{
	    $key = RedisKey::getBabyInfoKey($babyid);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    $redisobj->delete($key);
	    return true;
	}
	public function clearAddressinfoCache($addressid)
	{
	    $key = RedisKey::getAddressInfoKey($addressid);
	    $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
	    $redisobj->delete($key);
	    return true;
	}
	
	
	private function formatUserBaseInfo($one)
	{
	    if($one['birthday'] == "0000-00-00") {
	        $one['birthday'] = "";
	    }
	    if(empty($one['birthday'])) {
	        $one['birthday'] = "";
	    }
	    if($one['gender'] == 0) {
	        $one['gender'] = 1;
	    }
	    return $one;
	}
}