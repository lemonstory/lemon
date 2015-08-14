<?php
header("Content-type: text/html; charset=utf-8"); 
//error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('PRC');
define("SERVER_ROOT", dirname(__FILE__)."/");
if (DIRECTORY_SEPARATOR == '/'){
    define("MANAGE_ROOT", dirname(SERVER_ROOT)."/manage.lemon.com/");
} else {
    define("MANAGE_ROOT", dirname(dirname(dirname(SERVER_ROOT)))."/manage.git/trunk/");
}
define("XHPROF_DEBUG", 2); // xhprof是否开启：0关闭，1开启手工模式，2开启取样模式

include dirname(__FILE__)."/config/dbconf.php";
include dirname(__FILE__)."/config/cacheconf.php";
include dirname(__FILE__)."/config/config.php";
include dirname(__FILE__)."/libs/functions.php";

/**
 * autoload : SERVER_ROOT.[model/lib]
 * usage : new oss_sdk() => include('lib/oss/sdk.class.php');new oss_sdk();
 */
function __autoload($className){
	$className = (str_replace("_", DIRECTORY_SEPARATOR, $className));

	$incFile = SERVER_ROOT."model/$className.class.php";
	if (file_exists($incFile)){
		include_once $incFile;
		return;
	}
	
	$incFile = SERVER_ROOT."libs/$className.class.php";
	if (file_exists($incFile)){
		include_once $incFile;
		return;
	}
	
	$incFile = MANAGE_ROOT."model/$className.class.php";
	if (file_exists($incFile)){
	    include_once $incFile;
	    return;
	}
	
	$incFile = MANAGE_ROOT."libs/$className.class.php";
	if (file_exists($incFile)){
	    include_once $incFile;
	    return;
	}
}

