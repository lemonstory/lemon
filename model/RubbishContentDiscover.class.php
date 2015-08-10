<?php
class RubbishContentDiscover extends ModelBase
{
	public function setPicTopicIsRubbish($topicid)
	{
		$TopicDataObj =  new TopicData();
		$topicinfo = current($TopicDataObj->getTopicDataDb(array($topicid),'all'));
		if($topicinfo['status']!=3)
		{
			return false;
		}
		$picurl = "http://oss-cn-hangzhou-internal.aliyuncs.com/tutupic/".$topicinfo['content'].".bak";
		$md5code = $this->getPicMd5($picurl);
		if ($md5code==false) 
		{
			return false;
		}
		
		$cacheObj = CacheConnecter::connectCache('topic');
		$key = "rubbishtopic:".$md5code;
		$ret = $cacheObj->append($key, "@".$topicid);
		if($ret===false)
		{
			$ret = $cacheObj->add($key, "@".$topicid);
		}
		return $md5code;
	}
	
	
	
	
	
	public function checkPicIsRubbish($topicid)
	{
		$TopicDataObj =  new TopicData();
		$topicinfo = current($TopicDataObj->getTopicDataDb(array($topicid),'all'));
		if($topicinfo['status']==3 || $topicinfo['status']==4)
		{
			return false;
		}
		
		$picurl = "http://oss-cn-hangzhou-internal.aliyuncs.com/tutupic/".$topicinfo['content'];

		$md5code = $this->getPicMd5($picurl);
		if ($md5code==false)
		{
			return false;
		}
		
		$cacheObj = CacheConnecter::connectCache('topic');
		$key = "rubbishtopic:".$md5code;
		$cachedata = $cacheObj->getMulti(array($key,'afbug'));
		
		if(@$cachedata[$key]=="")
		{
			return false;
		}
		$betopicids = explode('@', ltrim(@$cachedata[$key],'@'));
		
		return $betopicids;
		
	}
	
	
	
	public function checkTopicIsRepeat($topicid)
	{
		$IdCreaterObj = new IdCreater();
		$publishuid = $IdCreaterObj->getUidWithTopicId($topicid);
		$md5code = $this->getPicTopicMd5Code($topicid);
		if($md5code===false)
		{
			return false;
		}
		
		
		$cacheObj = CacheConnecter::connectCache('topic');
		$key = "topicissrepeat:".$md5code.":".$publishuid;
		$cachedata = $cacheObj->getMulti(array($key,'afbug'));
		
		if(@$cachedata[$key]=="1")
		{
			return true;
		}
		$cacheObj->set($key, 1);
		return false;
		
	}
	
	public function getPicTopicMd5Code($topicid)
	{
		$TopicDataObj =  new TopicData();
		$topicinfo = current($TopicDataObj->getTopicDataDb(array($topicid),'all'));
		$picurl = "http://oss-cn-hangzhou-internal.aliyuncs.com/tutupic/".$topicinfo['content'];
		
		if($topicinfo['status']==3 || $topicinfo['status']==4)
		{
			$picurl = $picurl.".bak";
		}
		$md5code = $this->getPicMd5($picurl);
		return $md5code;
	}
	
	
	
	public  function addToObstructList($topicid, $fromtopicids)
	{
		if(!is_array($fromtopicids))
		{
			$fromtopicids = array($fromtopicids);
		}
		$addtime = date('Y-m-d H:i:s');
		$db = DbConnecter::connectMysql("share_manage");
		$sql = "insert into obstructtopic (topicid,fromtopics,addtime) values (?,?,?)";
		$st = $db->prepare($sql);
		$st->execute(array($topicid , implode('@',$fromtopicids) ,$addtime  ));
		$db= null;
	}
	
	public function getPicMd5($picurl)
	{
		$content = file_get_contents($picurl);
		if(strlen($content)<1000)
		{
			return false;
		}
		$md5code =  md5($content);
		return $md5code;
	}
}