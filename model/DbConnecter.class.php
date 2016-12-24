<?php

class DbConnecter
{
    private static $dbs = array();

    private static function getDb($dbname, $ismaster = true)
    {
        if ($ismaster) {
            $conf = $_SERVER['db_conf'][$dbname]['master'];
        } else {
            $conf = $_SERVER['db_conf'][$dbname]['slave'];
        }
        $dbname = $conf['dbname'];
        try {
            $db = new Pdo("mysql:host=" . $conf['host'] . ";port=" . $conf['port'] . ";dbname=" . $dbname,
                $conf['user'], $conf['passwd']);
        } catch (PDOException $e) {
            sleep(1);
            $e->getMessage();
            $db = new Pdo("mysql:host=" . $conf['host'] . ";port=" . $conf['port'] . ";dbname=" . $dbname,
                $conf['user'], $conf['passwd']);
        }
        self::$dbs[$dbname] = $db;
        return $db;
    }

    public static function connectMysql($dbname, $ismaster = true)
    {
        if(isset(self::$dbs[$dbname])){
            return self::$dbs[$dbname];
        }
        return self::getDb($dbname, $ismaster);

    }
}


