<?php 
require_once 'TopSdk.php';

$c = new AliyunClient;
$c->accessKeyId = "<accessKeyId>";
$c->accessKeySecret = "<accessKeySecret>";
$c->serverUrl="<serverUrl>";//根据不同产品选择相应域名，例如：ECS  http://cdn.aliyuncs.com/

//开通cdn
$req = new Cdn20141111OpenCdnRequest();
$req->setInternetChargeType("PayByTraffic"); // or PayByBandwidth

try {
	$resp = $c->execute($req);
	if(!isset($resp->Code))
	{
		//开通成功
		echo($resp->RequestId);
		print_r($resp);
	}
	else 
	{
		//开通失败
		$code = $resp->Code;
		$message = $resp->Message;
	}
}
catch (Exception $e)
{
	// TODO: handle exception
}

//刷新缓存
$req = new Cdn20141111RefreshObjectCachesRequest();
$req->setObjectType("File"); // or Directory
$req->setObjectPath("www.yourdomain.com/path/filename.ext");
try {
	$resp = $c->execute($req);
	if(!isset($resp->Code))
	{	
		//刷新成功
		echo($resp->RequestId);
		print_r($resp);
	}
	else 
	{
		//刷新失败
		$code = $resp->Code;
		$message = $resp->Message;
	}
}
catch (Exception $e)
{
	// TODO: handle exception
}
?> 