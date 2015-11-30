<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

require_once realpath(dirname(__FILE__) . '/Response.php');

/**
 * The response of the PutLogs API from sls.
 *
 * @author sls_dev
 */
class Aliyun_Sls_Models_PutLogsResponse extends Aliyun_Sls_Models_Response {
    /**
     * Aliyun_Sls_Models_PutLogsResponse constructor
     *
     * @param array $header
     *            PutLogs HTTP response header
     */
    public function __construct($headers) {
        parent::__construct ( $headers );
    }
}
