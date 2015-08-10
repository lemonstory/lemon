<?php
/**
 * redis 配置文件
 * 注意业务弃用之后，清理相关的持久化数据
 */
$_SERVER['redis_conf'] = array(
    // web6
    'defaultlist0' => array(
        'master'=>array(
            'host' =>'xxx',
            'port' =>'6379',
            'db' => 0,
        ),
        'slave'=>array(
            'host' =>'xxx',
            'port' =>'6379',
            'db' => 0,
        )
    ),
    
);