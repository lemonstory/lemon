<?php
include_once SERVER_ROOT.'libs/mqs.sdk.class.php';
include_once SERVER_ROOT.'libs/array2xml.lib.class.php';
include_once SERVER_ROOT.'libs/xml2array.lib.class.php';
class QueueManager
{
	
	//////////新发表主题----start////////////
	public static function pushNewPublishTopic($topicid)
	{
		self::doPush('publishtopic', $topicid);
		return true;
	}
	public static function popNewPublishTopicid()
	{
		return self::doPop('publishtopic');	
	}
	//////////新发表主题-----end////////////
	
	
	//////////新发表主题加入到新发布列表－－v2----start////////////
	public static function pushAddTopicToPreDefaultList($topicid)
	{
		self::doPush('addtopictopredefaultlist', $topicid);
		return true;
	}
	public static function popAddTopicToPreDefaultList()
	{
		return self::doPop('addtopictopredefaultlist');
	}
	//////////新发表主题加入到新发布列表－－v2----start////////////
	
	
	///发表后，推送lbs用户
	public static function pushFeedSysDistributeToLbsuser($topicid)
	{
		self::doPush('feedsys-distributetolbsuser', $topicid);
		return true;
	}
	public static function popFeedSysDistributeToLbsuser()
	{
		return self::doPop('feedsys-distributetolbsuser');
	}
	///发表后，推送lbs用户
	
	
	public static function pushFeedSysPushNewTopicToTestUser($topicid)
	{
		self::doPush('feedsys-pushnewtopictotestuser', $topicid);
		return true;
	}
	public static function popFeedSysPushNewTopicToTestUser()
	{
		return self::doPop('feedsys-pushnewtopictotestuser');
	}
	

	

	public static function pushFeedSysDelNoGoodTopicFromPreList($uid)
	{
		self::doPush('feedsys-delnogoodtopicfromprelist', $uid);
		return true;
	}
	public static function popFeedSysDelNoGoodTopicFromPreList()
	{
		return self::doPop('feedsys-delnogoodtopicfromprelist');
	}
	
	
	
	public static function pushFeedSysUpdateHotUserPreLists($uid)
	{
		self::doPush('feedsys-updatehotuserprelists', $uid);
		return true;
	}
	public static function popFeedSysUpdateHotUserPreLists()
	{
		return self::doPop('feedsys-updatehotuserprelists');
	}
	
	
	public static function pushFeedSysSplitDistributJobQueue($dataline)
	{
		$redisObj = RedisConnecter::connRedis('useronlinelist');
		$redisObj->lPush('feedsys-splitdistributjobqueue',$dataline);
		$redisObj->close();
		
		//self::doPush('feedsys-splitdistributjobqueue', $dataline);
		return true;
	}
	public static function popFeedSysSplitDistributJobQueue()
	{
		$redisObj = RedisConnecter::connRedis('useronlinelist');
		$dataline = $redisObj->lpop('feedsys-splitdistributjobqueue');
		$redisObj->close();
		return $dataline;
	}
	
	
	
	public static function pushFeedSysDelHotUserNoGoodTopicFromPreList($dataline)
	{
		$redisObj = RedisConnecter::connRedis('useronlinelist');
		$redisObj->lPush('feedsys-delhotusernogoodtopicfromprelist',$dataline);
		$redisObj->close();
	
		//self::doPush('feedsys-splitdistributjobqueue', $dataline);
		return true;
	}
	public static function popFeedSysDelHotUserNoGoodTopicFromPreList()
	{
		$redisObj = RedisConnecter::connRedis('useronlinelist');
		$dataline = $redisObj->lpop('feedsys-delhotusernogoodtopicfromprelist');
		$redisObj->close();
		return $dataline;
	}
	
	
	
	//////////更新用户对预加载列表排序－－v2----start////////////
	public static function pushPreListUidToUpdateScore($uid)
	{
		//self::doPush('prelistuidtoupdatescore', $uid);
		
		$redisObj = RedisConnecter::connRedis('useronlinelist');
		$redisObj->lPush('prelistuidtoupdatescore',$uid);
		$redisObj->close();
		
		
		return true;
	}
	public static function popPreListUidToUpdateScore()
	{
		//return self::doPop('prelistuidtoupdatescore');
		
		
		
		$redisObj = RedisConnecter::connRedis('useronlinelist');
		$dataline = $redisObj->lpop('prelistuidtoupdatescore');
		$redisObj->close();
		return $dataline;
		
	}
	//////////更新用户对预加载列表排序－－v2----start////////////
	
	
	
	
	//////////新发表主题----start////////////ss
	public static function pushPublishTopicPushFriend($topicid)
	{
		self::doPush('publishtopicpushfriend', $topicid);
		return true;
	}
	public static function popPublishTopicPushFriend()
	{
		return self::doPop('publishtopicpushfriend');
	}
	//////////新发表主题-----end////////////
	
	
	///////////分发热门主题-----start/////////////////////
	public static function pushHotTopicToWaitDistribute($topicid)
	{
		self::doPush('distributehottopic', $topicid);
		return true;
	}
	
	public static function popHotTopicToWaitDistribute()
	{
		return self::doPop('distributehottopic');
		
	}
	///////////分发热门主题-----end/////////////////////
	
	
	///////////分发热门主题-----start/////////////////////
	public static function pushWeekHotTopicToWaitDistribute($topicid)
	{
		self::doPush('distributeweekhottopic', $topicid);
		return true;
	}
	
	public static function popWeekHotTopicToWaitDistribute()
	{
		return self::doPop('distributeweekhottopic');
	
	}
	///////////分发热门主题-----end/////////////////////
	
	
	///////////发表评论-------start////////////////////////////////
	
	public static function pushCommentTopicAction($topicid,$commentid,$replycommentid)
	{
		self::doPush('publishcomment', $topicid.":".$commentid.":".$replycommentid);
		return true;
	}
	
	
	public static function popCommentTopicAction()
	{
		return self::doPop('publishcomment');
		
	}
	
	
	
	public static function pushAddCommentToManage($commentid)
	{
		self::doPush('comment-addtomanage', $commentid);
		return true;
	}
	
	
	public static function popAddCommentToManage()
	{
		return self::doPop('comment-addtomanage');
	
	}
	
	
	public static function pushCommentToUpdateHonour($topicid, $commentid, $flag)
	{
		self::doPush('comment-updatehonour', $topicid.":".$commentid.":".$flag);
		return true;
	}
	
	
	public static function popCommentToUpdateHonour()
	{
		return self::doPop('comment-updatehonour');
	
	}
	
	public static function pushTopicToUpdateHonour($topicid)
	{
		self::doPush('topic-updatehonour', $topicid);
		return true;
	}
	
	
	public static function popTopicToUpdateHonour()
	{
		return self::doPop('topic-updatehonour');
	
	}
	
	//新人求罩队列----START //
	public static function pushTopicNewPersonWall($topicid)
	{
		self::doPush('topic-newpersonwall', $topicid);
		return true;
	}
	
	
	public static function popTopicNewPersonWall()
	{
		return self::doPop('topic-newpersonwall');
	}
	//新人求罩队列----END //
	
	//主题topic包含话题huati数量----start//
	public static function pushTopicHaveHtNum($topicid)
	{
		self::doPush('topic-havehtnumber', $topicid);
		return true;
	}
	
	public static function popTopicHaveHtNum()
	{
		return self::doPop('topic-havehtnumber');
	}
	//主题topic包含话题huati数量----end//
	
	public static function pushReTopicToUpdateHonour($topicid, $retopicid, $status)
	{
		self::doPush('repost-updatehonour', $topicid.':'.$retopicid.':'.$status);
		return true;
	}
	
	
	public static function popReTopicToUpdateHonour()
	{
		return self::doPop('repost-updatehonour');
	
	}
	
	
	
	public static function pushTopicToMakeRubbishData($topicid)
	{
		self::doPush('topic-makerubbishdata', $topicid);
		return true;
	}
	
	
	public static function popTopicToFavoriteDel()
	{
		return self::doPop('deltopictofavorite');
	
	}
	
	
	public static function pushTopicToFavoriteDel($topicid)
	{
		self::doPush('deltopictofavorite', $topicid);
		return true;
	}
	
	
	public static function popTopicToMakeRubbishData()
	{
		return self::doPop('topic-makerubbishdata');
	
	}
	
	
	
	
	public static function pushHomecoverToUpdateHonour($uid,$actionuid,$flag)
	{
		self::doPush('homecover-updatehonour', $uid.":".$actionuid.":".$flag);
		return true;
	}
	
	
	public static function popHomecoverToUpdateHonour()
	{
		return self::doPop('homecover-updatehonour');
		
	}
	
	
	
	
	public static function pushFriendToUpdateHonour($uid, $actionuid, $flag)
	{
		self::doPush('friend-updatehonour', $uid.":".$actionuid.":".$flag);
		return true;
	}
	
	
	public static function popFriendToUpdateHonour()
	{
		return self::doPop('friend-updatehonour');
	
	}
	
	///////////发表评论-------end////////////////////////////////
	
	
	
	
	
	
	
	
	
	
	///////////发表评论-------start////////////////////////////////
	public static function pushLikeTopicAction($topicid,$uid)
	{
		self::doPush('liketopic', $topicid.":".$uid);
		return true;
	}
	
	
	public static function popLikeTopicAction()
	{
	return 	self::doPop('liketopic');
	
	}
	
	public static function pushLikeCommentAction($commentid,$uid)
	{
		self::doPush('digg-likecomment', $commentid.":".$uid);
		return true;
	}
	
	
	public static function popLikeCommentAction()
	{
		return 	self::doPop('digg-likecomment');
	
	}
	
	public static function pushLikeToUpdateHonour($topicid,$uid,$flag)
	{
		self::doPush('like-updatehonour', $topicid.":".$uid.":".$flag);
		return true;
	}
	
	
	public static function popLikeToUpdateHonour()
	{
		return 	self::doPop('like-updatehonour');
	
	}
	
	
	///////////发表评论-------end////////////////////////////////
	
	
	///////////发表评论-------start////////////////////////////////
	public static function pushMakeHotTopicAction($topicid)
	{
		self::doPush('makehottopic', $topicid);
		return true;
	}
	
	
	public static function popMakeHotTopicAction()
	{
	return 	self::doPop('makehottopic');
		
	}
	///////////发表评论-------end//////////////////////////////// 
	
	
	
	public static function pushUserPushAction($uid,$action,$content,$customeData)
	{
		self::doPush('userpushqueue', $uid."@@@".$action."@@@".$content."@@@".$customeData);
		return true;
	}
	
	
	public static function popUserPushAction()
	{
		return 	self::doPop('userpushqueue');
	}
	
		
	
	public static function pushUserToUpdateUserSysFriendLog($uid)
	{
		self::doPush('usertoupdateusersysfriendLog', $uid);
		return true;
	}
	
	
	public static function popUserToUpdateUserSysFriendLog()
	{
		return 	self::doPop('usertoupdateusersysfriendLog');
	}
	
	
	
	///////////审核主题-------start////////////////////////////////
	public static function pushAuditTopicAction($topicId)
	{
	    self::doPush('topicpicaudit', $topicId);
	    return true;
	}
	
	public static function popAuditTopicAction()
	{
	    return self::doPop('topicpicaudit');
	}
	///////////审核主题-------end////////////////////////////////
	
	///////////审核文字-------start////////////////////////////////
	/**
	 * 文字审核队列
	 * @param I $resId        资源ID
	 * @param I $resType      资源类型：1-昵称，2-评论，3-签名，4-主题描述内容
	 * @return boolean
	 */
	public static function pushAuditTextAction($resId, $resType)
	{
	    self::doPush('textaudit', $resId . ":" . $resType);
	    
	    return true;
	}
	
	public static function popAuditTextAction()
	{
	    return self::doPop('textaudit');
	}
	///////////审核文字-------end////////////////////////////////
	
	///////////审核图片-------start////////////////////////////////
	/**
	 * 图片审核队列
	 * @param I $resId        资源ID
	 * @param I $resType      资源类型：1-封面
	 * @return boolean
	 */
	 public static function pushAuditPicAction($resId, $resType)
	 {
	     self::doPush('picaudit', $resId . ":" . $resType);
	     return true;
	}
	
	public static function popAuditPicAction()
	{
	    return self::doPop('picaudit');
	}
	///////////审核图片-------end////////////////////////////////
	
	///////////审核视频-------start////////////////////////////////
	public static function pushAuditTopicVideoAction($topicId)
	{
	    self::doPush('topicvideoaudit', $topicId);
	    return true;
	}
	
	public static function popAuditTopicVideoAction()
	{
	    return self::doPop('topicvideoaudit');
	}
	///////////审核视频-------end////////////////////////////////
	
	///////////审核用户头像-------start////////////////////////////////
	public static function pushUserAvatarAudit($uid)
	{
	    self::doPush('useravataraudit', $uid);
	    return true;
	}
	public static function popUserAvatarAudit()
	{
	    return self::doPop('useravataraudit');
	}
	///////////审核用户头像-------end////////////////////////////////
	
	///////////审核用户信息-------start////////////////////////////////
	public static function pushUserInfoAudit($uid)
	{
	    self::doPush('userinfoaudit', $uid);
	    return true;
	}
	public static function popUserInfoAudit()
	{
	    return self::doPop('userinfoaudit');
	}
	///////////审核用户信息-------end////////////////////////////////
	
	
	///////////用户搜索-------start////////////////////////////////
	public static function pushUserInfoToSearch($uid)
	{
		self::doPush('userinfotosearch', $uid);
		return true;
	}
	
	public static function popUserInfoToSearch()
	{
		return self::doPop('userinfotosearch');
	}
	///////////用户搜索-------end////////////////////////////////
	
	
	///////////lbs-------start////////////////////////////////
	public static function pushUserLbsToSearch($uid)
	{
		//self::doPush('userlbstosearch', $uid);
		$redisObj = RedisConnecter::connRedis('useronlinelist');
		$redisObj->lPush('userlbstosearch',$uid);
		$redisObj->close();
		return true;
	}
	
	public static function popUserLbsToSearch()
	{
// 		return self::doPop('userlbstosearch');

		$redisObj = RedisConnecter::connRedis('useronlinelist');
		$dataline = $redisObj->lpop('userlbstosearch');
		$redisObj->close();
		return $dataline;
		
	}
	///////////lbs-------end////////////////////////////////
	
	
 
	
	
	
	
	
	///////////deleteTopic-------start////////////////////////////////
	public static function pushDeleteTopic($topicid)
	{
	    self::doPush('deleteTopic', $topicid);
	    return true;
	}
	
	public static function popDeleteTopic()
	{
	    return self::doPop('deleteTopic');
	}
	///////////deleteTopic-------end////////////////////////////////
	

	///////////xxxxx-------start////////////////////////////////
	public static function pushUserDefault2Db($topicid)
	{
	    self::doPush('userdefaultlist2db', $topicid);
	    return true;
	}
	
	public static function popUserDefault2Db()
	{
	    return self::doPop('userdefaultlist2db');
	}
	///////////xxxx-------end////////////////////////////////
	
	
	///////////contactupload-------start////////////////////////////////
	public static function pushContactUpload($id)
	{
		self::doPush('contactupload', $id);
		return true;
	}
	
	public static function popContactUpload()
	{
		return self::doPop('contactupload');
	}
	///////////contactupload-------end////////////////////////////////
	
	///////////contactpushapply-------start////////////////////////////////
	public static function pushContactApply($uid)
	{
		self::doPush('contactpushapply', $uid);
		return true;
	}
	
	public static function popContactApply()
	{
		return self::doPop('contactpushapply');
	}
	///////////contactpushapply-------end////////////////////////////////
	
	
	//////////修复QQ用户年龄----start////////////
	public static function pushQqBirthday($uid)
	{
	    self::doPush('repairuserbirthday', $uid);
	    return true;
	}
	public static function popQqBirthday()
	{
	    return self::doPop('repairuserbirthday');
	}
	//////////修复QQ用户年龄-----end////////////
	
	//////////主题阅读量----start////////////
	public static function pushTopicViews($uid,$topicids,$type='view')
	{
	    $topicidstr = implode(',', $topicids);
	    $now = date('YmdHis');
	    
	    if($type=="")
	    {
	    	$type = 'view';
	    }
	    
	    self::doPush('topicviews', "{$uid};{$topicidstr};{$now};{$type}");
	    return true;
	}
	public static function popTopicViews()
	{
	    return self::doPop('topicviews');
	}
	//////////主题阅读量-----end////////////
	
	
	
	
	
	
	public function pushToViewdDefaultList($uid,$topicids)
	{
		self::doPush('addtoviewddefaultlist', "{$uid}@@".implode(',',$topicids));
	}
	
	public function popToViewdDefaultList()
	{
		return self::doPop('addtoviewddefaultlist');
	}
	
	//////////记录用户cookie日志----start////////////
	public static function pushCookieLog($re, $us, $isEmptyUs)
	{
	    $dateTime = date("Y-m-d H:i:s"); 
	    self::doPush('cookielog', "$re:$us:$isEmptyUs:$dateTime");
	    return true;
	}
	public static function popCookieLog()
	{
	    return self::doPop('cookielog');
	}
	//////////记录用户cookie日志-----end////////////
	
	//////////记录融云图片地址和名称----start////////////
	public static function pushMessagePicUpload($imageUri, $messageId)
	{
	    $dateTime = date("Y-m-d H:i:s");
	    self::doPush('messagepicupload', "$imageUri@@$messageId");
	    return true;
	}
	public static function popMessagePicUpload()
	{
	    return self::doPop('messagepicupload');
	}
	//////////记录用户cookie日志-----end////////////
	
	//////////用于导出聊天数据队列，存储每条聊天ID----start////////////
	public static function pushAddUserMsgFile($uid, $messageId)
	{
	    $queueNum = $uid % 5;
        self::doPush('addusermsgfile-' . $queueNum, "$uid@@$messageId");
	    return true;
	}
	public static function popAddUserMsgFile($num)
	{
        return self::doPop('addusermsgfile-' . $num);
	}
	//////////用于导出聊天数据队列，存储每条聊天ID-----end////////////
	
	
	public static function pushTopicBeDelMakeBadUser($topicid)
	{
		self::doPush('topic-topicdelmakebaduser', $topicid);
		return true;
	}
	
	
	public static function popTopicDelMakeBadUser()
	{
		return self::doPop('topic-topicdelmakebaduser');
	
	}
	
	
	public static function pushPublishMessage($messageid, $systemType = 0)
	{
		self::doPush('msg-publishmessage', $messageid . "@@" . $systemType);
		return true;
	}
	public static function popPublishMessage()
	{
		return self::doPop('msg-publishmessage');
	}
	
	// 视频转码队列
	public static function pushTopicvideoEncode($messageid)
	{
	    self::doPush('topicvideoencode', $messageid);
	    return true;
	}
	public static function popTopicvideoEncode()
	{
	    return self::doPop('topicvideoencode');
	}
	
	// 视频封面验证队列，封面为空则取视频第一帧为封面
	public static function pushTopicvideoCoverVerify($messageid)
	{
	    self::doPush('topicvideoCoverVerify', $messageid);
	    return true;
	}
	public static function popTopicvideoCoverVerify()
	{
	    return self::doPop('topicvideoCoverVerify');
	}
	
	public static function pushLoadUserQqavatar($uid,$qqavatarurl)
	{
		self::doPush('loaduserqqavatar', $uid."@@".$qqavatarurl);
		return true;
	}
	public static function popLoadUserQqavatar()
	{
		return self::doPop('loaduserqqavatar');
	}
	
	
	public static function pushParseTopicDescAndPoiAndAtqueue($topicid)
	{
		self::doPush('parseTopicDescAndPoiAndAtqueue', $topicid);
		return true;
	}
	public static function popParseTopicDescAndPoiAndAtqueue()
	{
		return self::doPop('parseTopicDescAndPoiAndAtqueue');
	}
	
	
	
	public static function pushUpdateHuatiAndPoiListHotValuequeue($topicid,$hotvalue)
	{
		self::doPush('updateHuatiAndPoiListHotValuequeue', $topicid."@".$hotvalue);
		return true;
	}
	public static function popUpdateHuatiAndPoiListHotValuequeue()
	{
		return self::doPop('updateHuatiAndPoiListHotValuequeue');
	}
	
	// new info notice
	public static function pushNewInfoNoticeQueue($touid)
	{
		self::doPush('newinfonotice', $touid);
		return true;
	}
	public static function popNewInfoNoticeQueue()
	{
		return self::doPop('newinfonotice');
	}
	
	
	public static function pushDistributeFansTopic($topicid)
	{
		self::doPush('distributefanstopic', $topicid);
		return true;
	}
	public static function popDistributeFansTopic()
	{
		return self::doPop('distributefanstopic');
	}
	
	// huati and poi 最新和热门列表的处理队列
	public static function pushNewHotResList($uid, $restype, $resid)
	{
	    self::doPush('newhotreslist', "$uid:$restype:$resid");
	    return true;
	}
	public static function popNewHotResList()
	{
	    return self::doPop('newhotreslist');
	}
	
	
	// 加关注
	public static function pushAfterFollowQueue($uid, $followuid)
	{
	    self::doPush('afterfollowqueue', "{$uid}:{$followuid}");
	    return true;
	}
	public static function popAfterFollowQueue()
	{
	    return self::doPop('afterfollowqueue');
	}
	
	
	
	// 发帖后，机器人工作
	public static function pushRobotJobNewTopic($topicid)
	{
		self::doPush('robotjobnewtopic', "{$topicid}");
		return true;
	}
	public static function popRobotJobNewTopic()
	{
		return self::doPop('robotjobnewtopic');
	}
	
	
	
	
	// 注册
	public static function pushAfterRegQueue($uid)
	{
		self::doPush('afterregqueue', "{$uid}");
		return true;
	}
	public static function popAfterRegQueue()
	{
		return self::doPop('afterregqueue');
	}
	
	
	// 删除队列
	public static function pushAfterDeleteTopicQueue($topicid)
	{
		self::doPush('afterdeletetopicqueue', "{$topicid}");
		return true;
	}
	public static function popAfterDeleteTopicQueue()
	{
		return self::doPop('afterdeletetopicqueue');
	}
	
	
	protected static function doPop($business)
	{
		$queueobj = self::connectQueue($business);
		$data = $queueobj->receiveMessage();
		$MessageBody = @$data['Message']['MessageBody'];
		if(empty($MessageBody) || !is_array($data))
		{
			date_default_timezone_set('PRC');
			return false;
		}
		$queueobj->dropMessage(
				array(
						'ReceiptHandle' => $data['Message']['ReceiptHandle']
				)
		);
		date_default_timezone_set('PRC');
		return $MessageBody;
	}
	
	protected static function doPush($business,$data)
	{
		if($data=="")
		{
			return true;
		}	
		$queueobj = self::connectQueue($business);
		$data = array(
				'MessageBody' => $data,
				'DelaySeconds' => 0,
				'Priority' => 8
		);
		$re = $queueobj->sendMessage($data);
		date_default_timezone_set('PRC');
		return true;
	}
	
	
	private static function connectQueue($queuename)
	{
		$mqs = new mqs(
				array(
						'accessKeyId'       => '84KTqRKsyBIYnVJt',
						'accessKeySecret'   => 'u72cpnMTt2mykMMluafimbhv5QD3uC',
						'accessOwnerId'     => 'crok2mdpqp',
						'accessQueue'       => $queuename,
						'accessRegion'      => 'cn-hangzhou'
				)
		);
		return $mqs;
	}
	

}