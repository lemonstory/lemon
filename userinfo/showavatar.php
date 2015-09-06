<?php
$uid = $_GET['uid'];
$size = $_GET['size'];
$avatartime = $_GET['avatartime'];
header("content-type: image/jpeg");
$osspath = "http://aoss.tutuim.com/".$uid."?v=".$avatartime;
if($size>0) {
    if(in_array($size ,array(100,176,80))) {
        $osspath = "http://aoss.tutuim.com/".$uid."@!s".$size."?v=".$avatartime;
    }
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $osspath);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_REFERER, $osspath);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$content = curl_exec($ch);
curl_close($ch);

if(strlen($content)<1000) {
        $defaultfile = $size."-".($uid%7+1).".png";
        echo file_get_contents(dirname(__FILE__)."/".$defaultfile);
}else{
        echo $content;
}