<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

/**
 * The base request of all sls request.
 *
 * @author sls_dev
 */
class Aliyun_Sls_Models_Request {

    /**
     * @var string project name
     */
    private $project;
    
    /**
     * Aliyun_Sls_Models_Request constructor
     *
     * @param string $project
     *            project name
     */
    public function __construct($project) {
        $this->project = $project;
    }
    
    /**
     * Get project name
     *
     * @return string project name
     */
    public function getProject() {
        return $this->project;
    }
    
    /**
     * Set project name
     *
     * @param string $project
     *            project name
     */
    public function setProject($project) {
        $this->project = $project;
    }
}
