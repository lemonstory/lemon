<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

require_once realpath(dirname(__FILE__) . '/Response.php');

/**
 * The response of the ListLogstores API from sls.
 *
 * @author sls_dev
 */
class Aliyun_Sls_Models_ListLogstoresResponse extends Aliyun_Sls_Models_Response {
    
    /**
     * @var integer the number of total logstores from the response
     */
    private $count;
    
    /**
     * @var array all logstore
     */
    private $logstores;
    
    /**
     * Aliyun_Sls_Models_ListLogstoresResponse constructor
     *
     * @param array $resp
     *            ListLogstores HTTP response body
     * @param array $header
     *            ListLogstores HTTP response header
     */
    public function __construct($resp, $header) {
        parent::__construct ( $header );
        $this->count = $resp ['count'];
        $this->logstores = $resp ['logstores'];
    }
    
    /**
     * Get total count of logstores from the response
     *
     * @return integer the number of total logstores from the response
     */
    public function getCount() {
        return $this->count;
    }
    
    /**
     * Get all the logstores from the response
     *
     * @return array all logstore
     */
    public function getLogstores() {
        return $this->logstores;
    }
}
