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