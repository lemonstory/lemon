<?php
class UserActionLog
{
	private $islog = true;
	
	public function logViewUserCenter($uid,$bevisituid)
	{
		if($this->islog===false)
		{
			return true;
		}
		$time = time();
		$ua = @$_SERVER['HTTP_USER_AGENT'];
		$dataline = "viewusercenter@@{$uid}@@{$bevisituid}@@{$time}@@{$ua}";
		$this->writeLog($dataline);
	}
	public function logViewTopicDetail($uid,$topicid)
	{
		if($this->islog===false)
		{
			return true;
		}
		$time = time();
		$ua = @$_SERVER['HTTP_USER_AGENT'];
		$dataline = "viewtopicdetail@@{$uid}@@{$topicid}@@{$time}@@{$ua}";
		$this->writeLog($dataline);
	}
	
	public function logComment($uid,$topicid,$commentid)
	{
		$time = time();
		$ua = @$_SERVER['HTTP_USER_AGENT'];
		$dataline = "commenttopic@@{$uid}@@{$topicid}@@{$commentid}@@{$time}@@{$ua}";
		$this->writeLog($dataline);
	}
	
	
	public function logDiggTopic($uid,$topicid)
	{
		$time = time();
		$ua = @$_SERVER['HTTP_USER_AGENT'];
		$dataline = "diggtopic@@{$uid}@@{$topicid}@@{$time}@@{$ua}";
		$this->writeLog($dataline);
	}
	
	public function logRepostTopic($uid,$topicid)
	{
		$time = time();
		$ua = @$_SERVER['HTTP_USER_AGENT'];
		$dataline = "reposttopic@@{$uid}@@{$topicid}@@{$time}@@{$ua}";
		$this->writeLog($dataline);
	}
	
	public function writeViewUserCenterLogToDb($action,$uid,$bevisituid,$addtime,$ua)
	{
		$beactionid = $bevisituid;
		$actionid = "";
		$ret = $this->writeDataToDb($uid, $action, $beactionid, $actionid, $ua, $addtime);
		return $ret;
	}
	
	public function writeViewTopicLogToDb($action,$uid,$topicid,$addtime,$ua)
	{
		$beactionid = $topicid;
		$actionid = "";
		$ret = $this->writeDataToDb($uid, $action, $beactionid, $actionid, $ua, $addtime);
		return $ret;
	}
	
	public function logCommentTopicToDb($action,$uid,$topicid,$commentid,$addtime,$ua)
	{
		$beactionid = $topicid;
		$actionid = $commentid;
		$ret = $this->writeDataToDb($uid, $action, $beactionid, $actionid, $ua, $addtime);
		return true;
	}
	
	
	public function logLikeTopicToDb($action,$uid,$topicid,$addtime,$ua)
	{
		$beactionid = $topicid;
		$actionid = "";
		$ret = $this->writeDataToDb($uid, $action, $beactionid, $actionid, $ua, $addtime);
		return $ret;
	}
	public function logRepostTopicToDb($action,$uid,$topicid,$addtime,$ua)
	{
		$beactionid = $topicid;
		$actionid = "";
		$ret  = $this->writeDataToDb($uid, $action, $beactionid, $actionid, $ua, $addtime);
		return $ret;
	}
	
	private function  writeDataToDb($uid, $action, $beactionid, $actionid, $ua, $addtime)
	{
		$actionvalue = $this->actionNameToId($action);
		if(is_numeric($addtime))
		{
			$addtime = date('Y-m-d H:i:s');
		}
		try{
			$db = DbConnecter::connectMysql('share_tips');
			$sql = "insert into useractionlog (uid,action,beactionid,actionid,ua,addtime) values (?,?,?,?,?,?)";
			$st = $db->prepare ( $sql );
			$st->execute (array($uid, $actionvalue, $beactionid, $actionid, $ua, $addtime));
		}catch (Exception $e){
			return false;
		}
	}
	
	private function actionNameToId($action)
	{
		$ar['diggtopic'] = 1;
		$ar['commenttopic'] = 2;
		$ar['reposttopic'] = 3;
		$ar['viewusercenter'] = 4;
		$ar['viewtopicdetail'] = 5;
		return $ar[$action];
	}
	private function writeLog($dataline)
	{
		if($this->islog===false)
		{
			return true;
		}
		$filename = "useractionlog_".date("YmdHi").".log";
		$file = "/alidata1/www/logs/".$filename;
		
		$fp = @fopen($file, 'a+');
		@fwrite($fp, $dataline."\n");
		@fclose($fp);
	}
}