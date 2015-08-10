<?php
$_SERVER['db_conf'] = array(
               
    'share_topic' => array(
        'master'=>array(
            'host' =>'xxx.mysql.rds.aliyuncs.com',
            'port' =>'3306',
            'user'=>'xx',
            'dbname'=>'share_topic',
            'passwd'=>'xx'
        ),
        'slave'=>array(
            'host' =>'xx.mysql.rds.aliyuncs.com',
            'port' =>'3306',
            'user'=>'xx',
            'dbname'=>'share_topic',
            'passwd'=>'xx'
        )
    ),
    
		
);