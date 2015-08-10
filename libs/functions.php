<?php

function getMqsLength($queuename)
{
	include SERVER_ROOT."libs/alimqs.class.php";
	$QueueObj = new Queue('84KTqRKsyBIYnVJt', 'u72cpnMTt2mykMMluafimbhv5QD3uC', 'crok2mdpqp', 'mqs-cn-hangzhou.aliyuncs.com');
	$info = $QueueObj->Getqueueattributes($queuename);
	if($info['state']=='ok')
	{
		return $info['msg']['ActiveMessages'];
	}
	return false;
}


function checkPhoneNumberFormat($phonenumber)
{
	if(strlen($phonenumber)!=11 || $phonenumber+0==0 || substr($phonenumber, 0,1)!=1)
	{
		return false;
	}
	return true;
}


function getAgeFromBirthDay($birthday)
{
	$age = 0;
	if($birthday=="")
	{
		return $age;
	}	
	
	
	list($by,$bm,$bd)=explode('-',$birthday);
	$cm=date('n');
	$cd=date('j');
	$age=date('Y')-$by-1;
	if ($cm>$bm || $cm==$bm && $cd>$bd) 
	{
		$age++;
	}
	if($age<0)
	{
		$age=0;
	}
	if ($age>100)
	{
	    $age=100;
	}
	return $age;
}


function humanCommentTime($time)
{
	$dur = time() - $time;
	if ($dur < 60) {
		return $dur.$_SERVER['morelanguage']['sec'];
	} elseif ($dur < 3600) {
		return floor ( $dur / 60 ) . $_SERVER['morelanguage']['mins'];
	} elseif ($time > mktime ( 0, 0, 0 )) {
		return $_SERVER['morelanguage']['today'] . date ( 'H:i', $time );
	} elseif ($time > mktime ( 0, 0, 0 )-86400) {
		return $_SERVER['morelanguage']['yesterday'] . date ( 'H:i', $time );
	} elseif ($time > mktime ( 0, 0, 0 )-172800 ){
		return $_SERVER['morelanguage']['tfyesterday'] . date ( 'H:i', $time );
	}elseif ($time > mktime ( 0, 0, 0)-86400*365){
		return date ( 'm-d H:i', $time );
	}else {
		return date ( 'Y-m-d', $time );
	}
}



function humanTopicListTime($time){
	$dur = time() - $time;
	if ($dur < 60) {
		$show = $dur.$_SERVER['morelanguage']['secs'];
		return $show;
	} elseif ($dur < 3600) {
		$show =  floor ( $dur / 60 ) . $_SERVER['morelanguage']['mins'];
		return $show;
	} elseif ($time > mktime ( 0, 0, 0 )) {
		return $_SERVER['morelanguage']['today'] . date ( 'H:i', $time );
	} elseif ($time > mktime ( 0, 0, 0 )-86400) {
		return $_SERVER['morelanguage']['yesterday'] . date ( 'H:i', $time );
	} elseif ($time > mktime ( 0, 0, 0 )-172800 ){
		return $_SERVER['morelanguage']['tfyesterday'] . date ( 'H:i', $time );
	}elseif ($time > mktime ( 0, 0, 0)-86400*365){
		return date ( 'm-d H:i', $time );
	}else {
		return date ( 'Y-m-d', $time );
	}
}


function getDownLoadUrl()
{
    $agent         = strtolower($_SERVER['HTTP_USER_AGENT']);
    $is_pc         = (strpos($agent, 'windows nt')!=false) ? true : false;
    $is_mac        = (strpos($agent, 'macintosh')!=false) ? true : false;
    $is_ubt        = (strpos($agent, 'x11')!=false) ? true : false;
    $is_iphone     = (strpos($agent, 'iphone')!=false) ? true : false;
    $is_ipad       = (strpos($agent, 'ipad')!=false) ? true : false;
    $is_android    = (strpos($agent, 'android')!=false) ? true : false;
    $is_weixin     = (strpos($agent, 'micromessenger')!=false) ? true : false;
    $downLoadUrl = "";
    if($is_pc || $is_mac || $is_ubt){
        $downLoadUrl = "http://www.tutuim.com/downapk.php";
    }elseif($is_iphone || $is_ipad){
        //$downLoadUrl = "https://itunes.apple.com/cn/app/tutu-90hou-00hou-dan-mu-jiao/id934862300?mt=8";
        $downLoadUrl = "http://um0.cn/4kQfTr";
        if ($is_weixin) {
            $downLoadUrl = "http://mp.weixin.qq.com/mp/redirect?url=" . $downLoadUrl;
        }
    }elseif($is_android){
        if ($is_weixin) {
            $downLoadUrl = "http://a.app.qq.com/o/simple.jsp?pkgname=com.tutuim.mobile";
        } else {
            $downLoadUrl = "http://www.tutuim.com/downapk.php";
        }
    }

    return $downLoadUrl;
}