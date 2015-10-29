<?php
$uid = $_GET['uid'];
$size = $_GET['size'];
$avatartime = $_GET['avatartime'];

$domain = "http://aoss.xiaoningmeng.net/"; // oss中lemonavatar的cdn域名
$osspath = $domain . $uid . "?v=" . $avatartime;
if($size > 0) {
    if(in_array($size ,array(80, 100, 120))) {
        $osspath = $domain . $uid . "@!{$size}x{$size}" . "?v=" . $avatartime;
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
    $defaultfile = "default.png";
    echo file_get_contents(dirname(dirname(__FILE__)) . "/static/" . $defaultfile);
}else{
    echo $content;
}