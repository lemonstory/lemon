<?php
header("Content-type: text/html; charset=utf-8"); 
//error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('PRC');
define("SERVER_ROOT", dirname(__FILE__)."/");
define("API_LEMON_ROOT", dirname(__FILE__)."/");
if (DIRECTORY_SEPARATOR == '/'){
    define("MANAGE_ROOT", dirname(SERVER_ROOT)."/manage.xiaoningmeng.net/");
} else {
    define("MANAGE_ROOT", dirname(dirname(dirname(SERVER_ROOT)))."/www/manage.xiaoningmeng.net/");
}
define("HTTP_CACHE", true);

include dirname(__FILE__)."/config/dbconf.php";
include dirname(__FILE__)."/config/config.php";
include dirname(__FILE__)."/config/kvstoreconf.php";
include dirname(__FILE__)."/config/httpcacheconf.php";
include dirname(__FILE__)."/libs/functions.php";
include dirname(__FILE__)."/ucenter/config.inc.php";
include dirname(__FILE__)."/ucenter/uc_client/client.php";


/**
 * autoload : SERVER_ROOT.[model/lib]
 * usage : new oss_sdk() => include('lib/oss/sdk.class.php');new oss_sdk();
 */
function xiaoningmeng_autoload($className){
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
spl_autoload_register("xiaoningmeng_autoload");
