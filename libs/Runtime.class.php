<?php
class Runtime {
    static private $START_TIME = array();//程序运行开始时间  
    static private $STOP_TIME  = array();//程序运行结束时间  
    static private $TIME_SPENT = array();//程序运行花费时间
    // register_shutdown_function(array('Debug', 'show'));
    
    public static function log($sign, $showspent=false)
    {
        if (isset(self::$START_TIME[$sign])){
            $spent = self::spent($sign);
            if ($showspent){
                echo "{$spent['spent']}:::{$spent['start']} - {$spent['stop']}";
            }
            return $spent;
        } else {
            self::$START_TIME[$sign] = microtime(true);
            return true;
        }
    }
    
    public static function logRunTime()
    {
        if (!defined('SLOWPAGELOG') || !SLOWPAGELOG){
            return;
        }
        if (!defined('SLOWPAGELOGPATH') || !is_dir(SLOWPAGELOGPATH)){
            return;
        }
        if (!defined('SLOWPAGELOGTIME') || !SLOWPAGELOGTIME){
            return;
        }
        $logRunTime = self::log('pageruntime');
        if (@$logRunTime['spent']<SLOWPAGELOGTIME){
            return;
        }
        if (is_array($logRunTime))
        {
//             $log=date('Y-m-d H:i:s').' Slow page '.":\n";
// 			$log.= "File : " . $_SERVER['SCRIPT_FILENAME'] . "\n";
// 			$log.= "GET : " . var_export($_GET,true) . "\n";
// 			$log.= "Post : " . var_export($_POST,true) . "\n";
// 			$log.='Time Spent : '.$logRunTime['spent']."\n\n";
			
			
			$log = date('Y-m-d H:i:s')."@@".$_SERVER['SCRIPT_FILENAME']."@@".$logRunTime['spent']."@@"."GET--".json_encode($_GET)."@@"."POST--".json_encode($_POST)."\n";
			
			
			$logfile = 'api.lemon.com.slowpage.'.date('Ymd').'.log';
			@file_put_contents(SLOWPAGELOGPATH.$logfile, $log, FILE_APPEND);
        }
    }
    
    private static function spent($sign)
    {
        $ret['sign'] = $sign;
        $ret['stop'] = microtime(true);
        $ret['start'] = self::$START_TIME[$sign];
        $ret['spent'] = $ret['stop']-$ret['start'];
        return $ret;
    }
}