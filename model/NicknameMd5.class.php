<?php
class NicknameMd5 extends ModelBase
{
	public $PASSPORT_DB_INSTANCE = 'share_main';
	public $NICKNAME_TABLE_NAME = 'user_nicknamemd5';
	
	public function checkNameIsExist($nickname)
	{
		$nickname = trim($nickname);
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$md5 = md5($nickname);
		$sql = "select uid from {$this->NICKNAME_TABLE_NAME} where nicknamemd5=?";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($md5));
		$uid = $st->fetch(PDO::FETCH_COLUMN);
		$db=null;
		if($uid>0)
		{
			return $uid;
		}
		return false;
	}
	
	public function addOne($nickname,$uid)
	{
		$nickname = trim($nickname);
		
		$db = DbConnecter::connectMysql($this->PASSPORT_DB_INSTANCE);
		$md5 = md5($nickname);
		$sql = "replace into {$this->NICKNAME_TABLE_NAME} (nicknamemd5,uid,nickname,addtime) values (?,?,?,?)";
		$st = $db->prepare ( $sql );
		$re = $st->execute (array($md5,$uid,$nickname,date('Y-m-d H:i:s')));
		
		if($re)
		{
			$sql = "delete from {$this->NICKNAME_TABLE_NAME} where uid=? and nicknamemd5!=?";
			$st = $db->prepare ( $sql );
			$re = $st->execute (array($uid,$md5));
		}
		
		$db=null;
		return true;
	}
}