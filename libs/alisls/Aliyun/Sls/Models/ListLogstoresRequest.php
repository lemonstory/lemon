<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

require_once realpath(dirname(__FILE__) . '/Request.php');

/**
 * The request used to list logstore from sls.
 *
 * @author sls_dev
 */
class Aliyun_Sls_Models_ListLogstoresRequest extends Aliyun_Sls_Models_Request{
    
    /**
     * Aliyun_Sls_Models_ListLogstoresRequest constructor
     * 
     * @param string $project project name
     */
    public function __construct($project=null) {
        parent::__construct($project);
    }
}
