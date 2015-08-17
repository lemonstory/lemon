<?php
class UserExtend extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $BABY_INFO_TABLE_NAME = 'user_baby_info';
	public $ADDRESS_INFO_TABLE_NAME = 'user_address_info';
	
	public $CACHE_INSTANCE = 'main';
	
	/**
	 * 获取宝宝信息
	 * @param I $uid
	 * @return array
	 */
	public function getUserBabyInfo($babyid)
	{
		if (empty($babyid)) {
		    $this->setError(ErrorConf::paramError());
			return array();
		}
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "SELECT * FROM {$this->BABY_INFO_TABLE_NAME} WHERE `id` = ?";
		$st = $db->prepare($sql);
		$st->execute(array($babyid));
		$res = $st->fetch(PDO::FETCH_ASSOC);
		if (empty($res)) {
		    $this->setError(ErrorConf::userBabyInfoEmpty());
			return array();
		} else {
			return $res;
		}
	}
	
	
	/**
	 * 获取用户地址信息
	 * @param I $addressid
	 * @return array()
	 */
	public function getUserAddressInfo($addressid)
	{
	    if (empty($addressid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->BABY_INFO_TABLE_NAME} WHERE `id` = ?";
	    $st = $db->prepare($sql);
	    $st->execute(array($addressid));
	    $res = $st->fetch(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        $this->setError(ErrorConf::userAddressInfoEmpty());
	        return array();
	    } else {
	        return $res;
	    }
	}
	
	
	/**
	 * 用户添加宝宝资料
	 * @param I $uid
	 * @param S $birthday
	 * @param I $gender
	 * @param I $age
	 * @return boolean
	 */
	public function addUserBabyInfo($uid, $birthday, $gender, $age)
	{
		if (empty($uid) || empty($birthday) || empty($gender) || empty($age)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		
		$addtime = date("Y-m-d H:i:s");
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->BABY_INFO_TABLE_NAME} 
			(`uid`, `birthday`, `gender`, `age`, `addtime`) 
			VALUES (?, ?, ?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uid, $birthday, $gender, $age, $addtime));
		return $res;
	}
	
	public function updateUserBabyInfo($babyid, $updatedata)
	{
	    if (empty($babyid) || empty($updatedata)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    if (!is_array($updatedata)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $setstr = "";
	    foreach ($updatedata as $column => $value) {
	        if (!in_array($column, array("uid", "birthday", "gender", "age"))) {
	            $this->setError(ErrorConf::systemError());
	            return false;
	        }
	        $setstr .= "`{$column}` = '{$value}', ";
	    }
	    $setstr = rtrim($setstr, ",");
	     
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "UPDATE {$this->BABY_INFO_TABLE_NAME} SET {$setstr} WHERE `id` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($babyid));
	    return $res;
	}
	
	
	public function addUserAddressInfo($uid, $name, $phonenumber, $address, $ecode = "")
	{
	    if (empty($uid) || empty($name) || empty($phonenumber) || empty($address)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	
	    $addtime = date("Y-m-d H:i:s");
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "INSERT INTO {$this->ADDRESS_INFO_TABLE_NAME}
	    (`uid`, `name`, `phonenumber`, `address`, `ecode`, `addtime`)
	    VALUES (?, ?, ?, ?, ?, ?)";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uid, $name, $phonenumber, $address, $ecode, $addtime));
	    return $res;
	}
	
	public function updateUserAddressInfo($addressid, $updatedata)
	{
	    if (empty($addressid) || empty($updatedata)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    if (!is_array($updatedata)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    $setstr = "";
	    foreach ($updatedata as $column => $value) {
	        if (!in_array($column, array("uid", "name", "phonenumber", "address", "ecode"))) {
	            $this->setError(ErrorConf::systemError());
	            return false;
	        }
	        $setstr .= "`{$column}` = '{$value}', ";
	    }
	    $setstr = rtrim($setstr, ",");
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "UPDATE {$this->ADDRESS_INFO_TABLE_NAME} SET {$setstr} WHERE `id` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($addressid));
	    return $res;
	}
	
}