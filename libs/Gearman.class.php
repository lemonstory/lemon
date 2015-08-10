<?php 
/**
 * gearman 封装类
 * @author wangzhitao
 * @date 20150122
 * 
 */
class Gearman {
    private static $syncclient = null;
    private static $asyncclient = null;
    
    private static $syncworker = null;
    private static $asyncworker = null;
    
    private static $defaultclient = null;
    private static $defaultworker = null;
    
    private static $taskHandlers = null;
    
    private function __construct(){
        
    }
    
    private function addServers($hosts, $handler)
    {
        if (empty($hosts)){
            return false;
        }
        if (!is_array($hosts)){
            $hosts = array($hosts);
        }
        foreach ($hosts as $host){
            $serverArr = "{$host['host']}:{$host['port']}";
        }
        $servers = implode(',', $serverArr);
        $handler->addServers($servers);
    }
    
    public static function getClient($sign)
    {
        $signclient = "{$sign}client";
        if (self::$$signclient){
            return self::$$signclient;
        }
        $servers = $_SERVER['gearman_conf']['group1'];
        self::$$signclient = new GearmanClient();
        self::addServers($servers, self::$$signclient);
        return self::$$signclient;
    }
    
    public static function getWorker($sign)
    {
        $signworker = "{$sign}worker";
        if (self::$$signworker){
            return self::$$signworker;
        }
        $servers = $_SERVER['gearman_conf']['group1'];
        self::$$signworker = new GearmanWorker();
        self::addServers($servers, self::$$signworker);
        return self::$$signworker;
    }
    
    /**
     * 异步调用
     * 
     */
    public static function async ($func, $data, $id=null, &$context, $priority='')
    {
        if (empty($func) || empty($data)){
            return false;
        }
        $data = self::encodeData($data);
        $client = self::getClient('async');
        if ($priority=='hight') {
            $result = $client->addTaskHighBackground($func, $data, null, $id);
        } else if ($priority=='low') {
            $result = $client->addTaskLowBackground($func, $data, null, $id);
        } else {
            $result = $client->addTaskBackground($func, $data, $id);
        }
        
        return $result;
    }
    
    /**
     * 同步调用
     * 有返回值
     */
    public static function sync ($func, $data, $id=null, $priority='')
    {
        if (empty($func) || empty($data)){
            return false;
        }
        $data = self::encodeData($data);
        $client = self::getClient('sync');
        if ($priority=='hight') {
            $result = $client->doHighBackground($func, $data, $id);
        } else if ($priority=='low') {
            $result = $client->doLowBackground($func, $data, $id);
        } else {
            $result = $client->doNormal($func, $data, $id);
        }
        
        $retCode = $client->returnCode();
        if ($retCode == GEARMAN_SUCCESS){
            return self::decodeData($result);
        } else {
//             $retCode;
//             $errormsg = $client->error;
            return false;
        }
    }
    
    public static function runSync ()
    {
        $client = self::getClient('sync');
        self::$taskHandlers = $client->runTasks();
    }
    
    public static function runAsync ()
    {
        $client = self::getClient('async');
        return $client->runTasks();
    }
    
    public static function encodeData ($data)
    {
        return json_encode($data);
    }
    
    public static function decodeData ($data)
    {
        return json_decode($data, true);
    }
}


?>