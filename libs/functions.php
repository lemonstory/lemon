<?php

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
	if ($cm>$bm || $cm==$bm && $cd>=$bd) 
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
		return $dur."秒";
	} elseif ($dur < 3600) {
		return floor ( $dur / 60 ) . "分";
	} elseif ($time > mktime ( 0, 0, 0 )) {
		return "今天" . date ( 'H:i', $time );
	} elseif ($time > mktime ( 0, 0, 0 )-86400) {
		return "昨天" . date ( 'H:i', $time );
	} elseif ($time > mktime ( 0, 0, 0 )-172800 ){
		return $_SERVER['morelanguage']['tfyesterday'] . date ( 'H:i', $time );
	}elseif ($time > mktime ( 0, 0, 0)-86400*365){
		return date ( 'm-d H:i', $time );
	}else {
		return date ( 'Y-m-d', $time );
	}
}

// 返回秒数
function get_seconds($times = '')
{
	if (!$times) {
		return 0;
	}
	$times = explode(":", $times);
	if (isset($times[2])) {
		return $times[0] * 60 * 60 + $times[1] * 60 + $times[2];
	} else if (isset($times[1])) {
		return $times[0] * 60 + $times[1];
	} else {
		return $times[0];
	}
}


function getIsAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
}

function getImsi()
{
    $userAgent = @$_SERVER['HTTP_USER_AGENT'];
    if (empty($userAgent)) {
        return '';
    }
    $agentArr = explode(',', $userAgent); // agent头
    if (empty($agentArr)) {
        return '';
    }
    // 设备唯一标识imsi
    $imsi = $agentArr[1];
    $imsilen = strlen($imsi);
    //Mi 3C imsi只有14位, ios 36位，其余15位
    if (!in_array($imsilen, array(14, 15, 36))) {
        return '';
    }
    return $imsi;
}

function getFileExtByMime($mime = '')
{
	if (empty($mime)) {
		return '';
	}
	$mime = strtolower($mime);
	if ($mime == 'image/jpeg') {
		return 'jpg';
	} else if ($mime == 'image/jpg') {
		return 'jpg';
	} else if ($mime == 'image/png') {
		return 'png';
	} else if ($mime == 'image/gif') {
		return 'gif';
	} else if ($mime == 'audio/mpeg') {
		return 'mp3';
	}
	return '';
}

/**
 * 获取客户端ip
 *
 */
function getClientIp()
{
    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $onlineip = getenv('HTTP_CLIENT_IP');
    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $onlineip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $onlineip = getenv('REMOTE_ADDR');
    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $onlineip = $_SERVER['REMOTE_ADDR'];
    }
    return $onlineip;
}

function errorLog($error_msg) {

	$error_msg = __FILE__." : ".__LINE__." ".$error_msg."\r\n\r\n";
	//测试使用
	//error_log($error_msg, 3, UC_ERROR_FILE);
}