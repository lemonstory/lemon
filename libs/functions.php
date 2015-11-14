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
    if (strlen($imsi) != 15) {
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