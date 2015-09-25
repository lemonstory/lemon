<?php
$uid = $_GET['uid'];
$size = $_GET['size'];
$avatartime = $_GET['avatartime'];

//$domain = "http://aoss.xiaoningmeng.me/"; // oss中lemonavatar的cdn域名
$domain = "http://lemonavatar.oss-cn-hangzhou.aliyuncs.com/";
$osspath = $domain . $uid . "?v=" . $avatartime;
if($size > 0) {
    if(in_array($size ,array(100, 140))) {
        $osspath = $domain . $uid . "@!s" . $size . "?v=" . $avatartime;
    }
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $osspath);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_REFERER, $osspath);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$content = curl_exec($ch);
curl_close($ch);

header("content-type: image/jpeg");
if(strlen($content) < 1000) {
    $defaultfile = $size . "-" . ($uid % 7 + 1) . ".png";
    echo file_get_contents(dirname(__FILE__) . "/" . $defaultfile);
}else{
    echo $content;
}