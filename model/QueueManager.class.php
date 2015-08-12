<?php
include_once SERVER_ROOT.'libs/mqs.sdk.class.php';
include_once SERVER_ROOT.'libs/array2xml.lib.class.php';
include_once SERVER_ROOT.'libs/xml2array.lib.class.php';
class QueueManager
{
	
	//////////新发表主题----start////////////
	/* public static function pushNewPublishTopic($topicid)
	{
		self::doPush('publishtopic', $topicid);
		return true;
	}
	public static function popNewPublishTopicid()
	{
		return self::doPop('publishtopic');	
	} */
	//////////新发表主题-----end////////////
	
	
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
						'accessKeyId'       => 'xxx',
						'accessKeySecret'   => 'xxxx',
						'accessOwnerId'     => 'crok2mdpqp',
						'accessQueue'       => $queuename,
						'accessRegion'      => 'cn-hangzhou'
				)
		);
		return $mqs;
	}
	

}