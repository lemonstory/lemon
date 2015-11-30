<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */
date_default_timezone_set ( 'Asia/Shanghai' );

require_once realpath ( dirname ( __FILE__ ) . '/../../Sls_Autoload.php' );
require_once realpath ( dirname ( __FILE__ ) . '/requestcore.class.php' );
require_once realpath ( dirname ( __FILE__ ) . '/sls.proto.php' );
require_once realpath ( dirname ( __FILE__ ) . '/protocolbuffers.inc.php' );

if(!defined('API_VERSION'))
    define('API_VERSION', '0.4.0');
if(!defined('USER_AGENT'))
    define('USER_AGENT', 'sls-php-sdk-v-0.4.1');

/**
 * Aliyun_Sls_Client class is the main class in the SDK. It can be used to
 * communicate with SLS server to put/get data.
 *
 * @author sls_dev
 */
class Aliyun_Sls_Client {

    /**
     * @var string aliyun accessKey
     */
    protected $accessKey;
    
    /**
     * @var string aliyun accessKeyId
     */
    protected $accessKeyId;

    /**
     * @var string SLS endpoint
     */
    protected $endpoint;

    /**
     * @var string Check if the host if row ip.
     */
    protected $isRowIp;

    /**
     * @var integer Http send port. The dafault value is 80.
     */
    protected $port;

    /**
     * @var string sls sever host.
     */
    protected $slsHost;

    /**
     * @var string the local machine ip address.
     */
    protected $source;
    
    /**
     * Aliyun_Sls_Client constructor
     *
     * @param string $endpoint
     *            SLS host name, for example, http://ch-hangzhou.sls.aliyuncs.com
     * @param string $accessKeyId
     *            aliyun accessKeyId
     * @param string $accessKey
     *            aliyun accessKey
     */
    public function __construct($endpoint, $accessKeyId, $accessKey) {
        $this->setEndpoint ( $endpoint ); // set $this->slsHost
        $this->accessKeyId = $accessKeyId;
        $this->accessKey = $accessKey;
        $this->source = Aliyun_Sls_Util::getLocalIp();
    }
    private function setEndpoint($endpoint) {
        $pos = strpos ( $endpoint, "://" );
        if ($pos !== false) { // be careful, !==
            $pos += 3;
            $endpoint = substr ( $endpoint, $pos );
        }
        $pos = strpos ( $endpoint, "/" );
        if ($pos !== false) // be careful, !==
            $endpoint = substr ( $endpoint, 0, $pos );
        $pos = strpos ( $endpoint, ':' );
        if ($pos !== false) { // be careful, !==
            $this->port = ( int ) substr ( $endpoint, $pos + 1 );
            $endpoint = substr ( $endpoint, 0, $pos );
        } else
            $this->port = 80;
        $this->isRowIp = Aliyun_Sls_Util::isIp ( $endpoint );
        $this->slsHost = $endpoint;
        $this->endpoint = $endpoint . ':' . ( string ) $this->port;
    }
    
    /**
     * GMT format time string.
     * 
     * @return string
     */
    protected function getGMT() {
        return gmdate ( 'D, d M Y H:i:s' ) . ' GMT';
    }
    
    /**
     * Decodes a JSON string. 
     * Unsuccessful decode will cause an Aliyun_Sls_Exception.
     * 
     * @return string
     * @throws Aliyun_Sls_Exception
     */
    protected function loadJson($json, $requestId) {
        if (! $json)
            return NULL;
        $json = json_decode ( $json, true );
        if ($json === NULL)
            throw new Aliyun_Sls_Exception ( 'SLSBadResponse', "Bad json format: $json", $requestId );
        return $json;
    }
    
    /**
     * @return array
     */
    protected function getHttpResponse($method, $url, $body, $headers) {
        $request = new RequestCore ( $url );
        foreach ( $headers as $key => $value )
            $request->add_header ( $key, $value );
        $request->set_method ( $method );
        $request->set_useragent(USER_AGENT);
        if ($method == "POST")
            $request->set_body ( $body );
        $request->send_request ();
        $response = array ();
        $response [] = ( int ) $request->get_response_code ();
        $response [] = $request->get_response_header ();
        $response [] = $request->get_response_body ();
        return $response;
    }
    
    /**
     * @return array
     * @throws Aliyun_Sls_Exception
     */
    private function sendRequest($method, $url, $body, $headers) {
        try {
            list ( $responseCode, $header, $exJson ) = 
                    $this->getHttpResponse ( $method, $url, $body, $headers );
        } catch ( Exception $ex ) {
            throw new Aliyun_Sls_Exception ( $ex->getMessage (), $ex->__toString () );
        }
        
        $requestId = isset ( $header ['x-sls-requestid'] ) ? $header ['x-sls-requestid'] : '';
        $exJson = $this->loadJson ( $exJson, $requestId );
        if ($responseCode == 200) {
            return array (
                    $exJson,
                    $header
            );
        } else {
            if (isset($exJson ['error_code']) && isset($exJson ['error_message'])) {
                throw new Aliyun_Sls_Exception ( $exJson ['error_code'], 
                        $exJson ['error_message'], $requestId );
            } else {
                if ($exJson) {
                    $exJson = ' The return json is ' . json_encode($exJson);
                } else {
                    $exJson = '';
                }
                throw new Aliyun_Sls_Exception ( 'SLSRequestError',
                        "Request is failed. Http code is $responseCode.$exJson", $requestId );
            }
        }
    }
    
    /**
     * @return array
     * @throws Aliyun_Sls_Exception
     */
    private function send($method, $project, $body, $resource, $params, $headers) {
        if ($body) {
            $headers ['Content-Length'] = strlen ( $body );
            $headers ['Content-MD5'] = Aliyun_Sls_Util::calMD5 ( $body );
            $headers ['Content-Type'] = 'application/x-protobuf';
        } else {
            $headers ['Content-Length'] = 0;
            $headers ["x-sls-bodyrawsize"] = 0;
            $headers ['Content-Type'] = ''; // If not set, http request will add automatically.
        }
        
        $headers ['x-sls-apiversion'] = API_VERSION;
        $headers ['x-sls-signaturemethod'] = 'hmac-sha1';
        $headers ['Host'] = "$project.$this->slsHost";
        $headers ['Date'] = $this->GetGMT ();
        
        $signature = Aliyun_Sls_Util::getRequestAuthorization ( $method, $resource, $this->accessKey, $params, $headers );
        $headers ['Authorization'] = "SLS $this->accessKeyId:$signature";
        
        $url = $resource;
        if ($params)
            $url .= '?' . Aliyun_Sls_Util::urlEncode ( $params );
        if ($this->isRowIp)
            $url = "http://$this->endpoint$url";
        else
            $url = "http://$project.$this->endpoint$url";
        return $this->sendRequest ( $method, $url, $body, $headers );
    }
    
    /**
     * Put logs to SLS.
     * Unsuccessful opertaion will cause an Aliyun_Sls_Exception.
     *
     * @param Aliyun_Sls_Models_PutLogsRequest $request the PutLogs request parameters class
     * @throws Aliyun_Sls_Exception
     * @return Aliyun_Sls_Models_PutLogsResponse
     */
    public function putLogs(Aliyun_Sls_Models_PutLogsRequest $request) {
        if (count ( $request->getLogitems () ) > 4096)
            throw new Aliyun_Sls_Exception ( 'InvalidLogSize', "logItems' length exceeds maximum limitation: 4096 lines." );
        
        $logGroup = new LogGroup ();
        $topic = $request->getTopic () !== null ? $request->getTopic () : '';
        $logGroup->setTopic ( $request->getTopic () );
        $source = $request->getSource ();
        if ( ! $source )
            $source = $this->source;
        $logGroup->setSource ( $source );
        $logitems = $request->getLogitems ();
        foreach ( $logitems as $logItem ) {
            $log = new Log ();
            $log->setTime ( $logItem->getTime () );
            $content = $logItem->getContents ();
            foreach ( $content as $key => $value ) {
                $content = new Log_Content ();
                $content->setKey ( $key );
                $content->setValue ( $value );
                $log->addContents ( $content );
            }
            $logGroup->addLogs ( $log );
        }
        $body = Aliyun_Sls_Util::toBytes ( $logGroup );
        unset ( $logGroup );
        
        $bodySize = strlen ( $body );
        if ($bodySize > 3 * 1024 * 1024) // 3 MB
            throw new Aliyun_Sls_Exception ( 'InvalidLogSize', "logItems' size exceeds maximum limitation: 3 MB." );
        $params = array ();
        $headers = array ();
        $headers ["x-sls-bodyrawsize"] = $bodySize;
        $headers ['x-sls-compresstype'] = 'deflate';
        $body = gzcompress ( $body, 6 );
        
        $logstore = $request->getLogstore () !== null ? $request->getLogstore () : '';
        $project = $request->getProject () !== null ? $request->getProject () : '';
        $resource = "/logstores/" . $logstore;
        list ( $resp, $header ) = $this->send ( "POST", $project, $body, $resource, $params, $headers );
        return new Aliyun_Sls_Models_PutLogsResponse ( $header );
    }
    
    /**
     * List all logstores of requested project.
     * Unsuccessful opertaion will cause an Aliyun_Sls_Exception.
     *
     * @param Aliyun_Sls_Models_ListLogstoresRequest $request the ListLogstores request parameters class.
     * @throws Aliyun_Sls_Exception
     * @return Aliyun_Sls_Models_ListLogstoresResponse
     */
    public function listLogstores(Aliyun_Sls_Models_ListLogstoresRequest $request) {
        $headers = array ();
        $params = array ();
        $resource = '/logstores';
        $project = $request->getProject () !== null ? $request->getProject () : '';
        list ( $resp, $header ) = $this->send ( "GET", $project, NULL, $resource, $params, $headers );
        return new Aliyun_Sls_Models_ListLogstoresResponse ( $resp, $header );
    }
    
    /**
     * List all topics in a logstore.
     * Unsuccessful opertaion will cause an Aliyun_Sls_Exception.
     *
     * @param Aliyun_Sls_Models_ListTopicsRequest $request the ListTopics request parameters class.
     * @throws Aliyun_Sls_Exception
     * @return Aliyun_Sls_Models_ListTopicsResponse
     */
    public function listTopics(Aliyun_Sls_Models_ListTopicsRequest $request) {
        $headers = array ();
        $params = array ();
        if ($request->getToken () !== null)
            $params ['token'] = $request->getToken ();
        if ($request->getLine () !== null)
            $params ['line'] = $request->getLine ();
        $params ['type'] = 'topic';
        $logstore = $request->getLogstore () !== null ? $request->getLogstore () : '';
        $project = $request->getProject () !== null ? $request->getProject () : '';
        $resource = "/logstores/$logstore";
        list ( $resp, $header ) = $this->send ( "GET", $project, NULL, $resource, $params, $headers );
        return new Aliyun_Sls_Models_ListTopicsResponse ( $resp, $header );
    }
    
    /**
     * Get histograms of requested query from SLS.
     * Unsuccessful opertaion will cause an Aliyun_Sls_Exception.
     *
     * @param Aliyun_Sls_Models_GetHistogramsRequest $request the GetHistograms request parameters class.
     * @throws Aliyun_Sls_Exception
     * @return Aliyun_Sls_Models_GetHistogramsResponse
     */
    public function getHistograms(Aliyun_Sls_Models_GetHistogramsRequest $request) {
        $headers = array ();
        $params = array ();
        if ($request->getTopic () !== null)
            $params ['topic'] = $request->getTopic ();
        if ($request->getFrom () !== null)
            $params ['from'] = $request->getFrom ();
        if ($request->getTo () !== null)
            $params ['to'] = $request->getTo ();
        if ($request->getQuery () !== null)
            $params ['query'] = $request->getQuery ();
        $params ['type'] = 'histogram';
        $logstore = $request->getLogstore () !== null ? $request->getLogstore () : '';
        $project = $request->getProject () !== null ? $request->getProject () : '';
        $resource = "/logstores/$logstore";
        list ( $resp, $header ) = $this->send ( "GET", $project, NULL, $resource, $params, $headers );
        return new Aliyun_Sls_Models_GetHistogramsResponse ( $resp, $header );
    }
    
    /**
     * Get logs from SLS.
     * Unsuccessful opertaion will cause an Aliyun_Sls_Exception.
     *
     * @param Aliyun_Sls_Models_GetLogsRequest $request the GetLogs request parameters class.
     * @throws Aliyun_Sls_Exception
     * @return Aliyun_Sls_Models_GetLogsResponse
     */
    public function getLogs(Aliyun_Sls_Models_GetLogsRequest $request) {
        $headers = array ();
        $params = array ();
        if ($request->getTopic () !== null)
            $params ['topic'] = $request->getTopic ();
        if ($request->getFrom () !== null)
            $params ['from'] = $request->getFrom ();
        if ($request->getTo () !== null)
            $params ['to'] = $request->getTo ();
        if ($request->getQuery () !== null)
            $params ['query'] = $request->getQuery ();
        $params ['type'] = 'log';
        if ($request->getLine () !== null)
            $params ['line'] = $request->getLine ();
        if ($request->getOffset () !== null)
            $params ['offset'] = $request->getOffset ();
        if ($request->getOffset () !== null)
            $params ['reverse'] = $request->getReverse () ? 'true' : 'false';
        $logstore = $request->getLogstore () !== null ? $request->getLogstore () : '';
        $project = $request->getProject () !== null ? $request->getProject () : '';
        $resource = "/logstores/$logstore";
        list ( $resp, $header ) = $this->send ( "GET", $project, NULL, $resource, $params, $headers );
        return new Aliyun_Sls_Models_GetLogsResponse ( $resp, $header );
    }
}

