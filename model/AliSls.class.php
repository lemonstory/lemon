<?php
/*
 * 日志服务
 */
require_once API_LEMON_ROOT . "libs/alisls/config.php";
require_once API_LEMON_ROOT . "libs/alisls/Sls_Autoload.php";

class AliSls
{
    private $client;
    private $endpoint;
    private $accesskeyid;
    private $accesskey;
    private $project; // 项目名称
    
    protected function __construct($project)
    {
        $this->project = $project;
        $this->endpoint = SLS_END_POINT;
        $this->accesskeyid = SLS_ACCESS_KEY_ID;
        $this->accesskey = SLS_ACCESS_KEY_SECRET;
        $this->client = new Aliyun_Sls_Client($this->endpoint, $this->accesskeyid, $this->accesskey);
    }
    
    
    /**
     * 查询日志列表
     * 每次查询只能扫描指定条数的日志量。如果一次请求需要处理的数据量非常大的时候，该请求会返回不完整的结果
     * 因此SLS服务可以让用户通过以相同参数反复调用该接口来获取最终完整结果。
     * 为了减少用户合并多次查询结果的工作量，SLS会把缓存命中的查询结果与本次查询新命中的结果合并返回给用户。
     * 所以需要用户通过检查每次请求的返回结果中progress成员状态值来确定是否需要继续。
     * 
     * @param S $logstore
     * @param I $starttime
     * @param I $endtime
     * @param S $topic
     * @param S $query
     * @param I $line
     * @param I $offset
     * @param B $revert
     * @return array 
     */
    protected function getLogList(
            $logstore, $starttime, $endtime, $topic = "",
            $query = "", $line = 10, $offset = 0, $revert = false)
    {
        if (empty($logstore) || empty($starttime) || empty($endtime) || empty($line)) {
            return array();
        }
        
        $response = $this->getLogs($logstore, $starttime, $endtime, $topic, $query, $line, $offset, $revert);
        if (empty($response)) {
            return array();
        }
        
        $objlist = array();
        // 当前查询结果的日志原始数据
        $objlist = $response->getLogs();
        // 当前查询结果的日志总数
        $logcount = $response->getCount();
        // 查询结果的状态。可以有true和false两个选值，当需要查询的日志数据量非常大的时候，该接口的响应结果可能并不完整
        $iscompleted = $response->isCompleted();
        
        $loglist = array();
        if (!empty($objlist)) {
            foreach ($objlist as $key => $obj) {
                $loglist[$key]["source"] = $obj->getSource();
                $loglist[$key]["time"] = $obj->getTime();
                $loglist[$key]["contents"] = $obj->getContents();
            }
        }
        
        return array("list" => $loglist, "count" => $logcount, "iscompleted" => $iscompleted);
    }
    
    
    /**
     * 查询日志总数
     * @param S $logstore
     * @param I $starttime
     * @param I $endtime
     * @param S $topic
     * @param S $query
     * @return array
     */
    protected function getLogCount($logstore, $starttime, $endtime, $topic = "", $query = "")
    {
        if (empty($logstore) || empty($starttime) || empty($endtime)) {
            return array();
        }
        
        $response = $this->getHistograms($logstore, $starttime, $endtime, $topic, $query);
        if (empty($response)) {
            return array();
        }
    
        // 符合query条件的日志总数
        $totalcount = $response->getTotalCount();
        // 查询结果的状态。可以有true和false两个选值，当需要查询的日志数据量非常大的时候，该接口的响应结果可能并不完整
        $iscompleted = $response->isCompleted();
        
        return array("totalcount" => $totalcount, "iscompleted" => $iscompleted);
    }
    
    /**
     * 写入日志
     * 每次可以写入的日志数据量上限为3MB或者4096条。只要日志数据量超过这两条上限中的任意一条则整个请求失败，且无任何日志数据成功写入。
     * @param S $logstore        LogStore名称
     * @param A $contents        日志内容Key-value数组，如array('TestKey'=>'TestContent');
     * @param S $topic           日志主题，用以标记一批日志，可以为空
     * @return boolean
     */
    protected function putLogs($logstore, $contents, $topic = "")
    {
        if (empty($logstore) || empty($contents)) {
            return false;
        }
        if (!is_array($contents)) {
            $contents = array($contents);
        }
        
        $logItem = new Aliyun_Sls_Models_LogItem();
        $logItem->setTime(time());
        $logItem->setContents($contents);
        $logitems = array($logItem);
        $request = new Aliyun_Sls_Models_PutLogsRequest($this->project, $logstore, $topic, null, $logitems);
        
        try {
            $response = $this->client->putLogs($request);
            $headerinfo = $response->getHeader("_info");
            if (!empty($headerinfo['http_code']) && $headerinfo['http_code'] == 200) {
                return true;
            } else {
                return false;
            }
        } catch (Aliyun_Sls_Exception $ex) {
            var_dump($ex);
        } catch (Exception $ex) {
            var_dump($ex);
        }
    }
    
    /**
     * 查看所有的logStore
     */
    protected function listLogstores()
    {
        try{
            $request = new Aliyun_Sls_Models_ListLogstoresRequest($this->project);
            $response = $this->client->listLogstores($request);
            $headerinfo = $response->getHeader("_info");
            if (!empty($headerinfo['http_code']) && $headerinfo['http_code'] == 200) {
                return $response;
            } else {
                return array();
            }
        } catch (Aliyun_Sls_Exception $ex) {
            var_dump($ex);
        } catch (Exception $ex) {
            var_dump($ex);
        }
    }
    
    
    /**
     * 查看某个logStore中的主题
     */
    protected function listTopics($logstore) 
    {
        if (empty($logstore)) {
            return array();
        }
        $request = new Aliyun_Sls_Models_ListTopicsRequest($this->project, $logstore);
        try {
            $response = $this->client->listTopics($request);
            $headerinfo = $response->getHeader("_info");
            if (!empty($headerinfo['http_code']) && $headerinfo['http_code'] == 200) {
                return $response;
            } else {
                return array();
            }
        } catch (Aliyun_Sls_Exception $ex) {
            var_dump($ex);
        } catch (Exception $ex) {
            var_dump($ex);
        }
    }
    
    
    /**
     * 获取某个logStore的指定主题的某个时间段内的日志列表
     * @param S $logstore
     * @param I $starttime    起始时间戳
     * @param I $endtime      结束时间戳
     * @param S $topic        主题
     * @param I $query        查询query表达式
     * @param I $line         读取行数
     * @param I $offset       偏移量
     * @param B $revert       是否从最后一条开始
     * @return 
     */
    private function getLogs($logstore, $starttime, $endtime, $topic = "", $query = "", $line = 10, $offset = 0, $revert = false)
    {
        if (empty($logstore) || empty($starttime) || empty($endtime)) {
            return array();
        }
        $request = new Aliyun_Sls_Models_GetLogsRequest($this->project, $logstore, $starttime, $endtime, $topic, $query, $line, $offset, $revert);
    
        try {
            $response = $this->client->getLogs($request);
            // $response->isCompleted()
            // $response->getLogs()
            // $response->getCount()
            $headerinfo = $response->getHeader("_info");
            if (!empty($headerinfo['http_code']) && $headerinfo['http_code'] == 200) {
                return $response;
            } else {
                return array();
            }
        } catch (Aliyun_Sls_Exception $ex) {
            var_dump($ex);
        } catch (Exception $ex) {
            var_dump($ex);
        }
    }
    
    
    /**
     * 查询指定Project下某个Logstore中日志的分布情况
     * @param S $logstore
     * @param I $starttime
     * @param I $endtime
     * @param I $topic
     * @param I $query
     * @return 
     */
    private function getHistograms($logstore, $starttime, $endtime, $topic = "", $query = "")
    {
        if (empty($logstore) || empty($starttime) || empty($endtime)) {
            return array();
        }
        
        $request = new Aliyun_Sls_Models_GetHistogramsRequest($this->project, $logstore, $starttime, $endtime, $topic, $query);
        
        try {
            $response = $this->client->getHistograms($request);
            // $response->getHistograms()
            // $response->getTotalCount();
            $headerinfo = $response->getHeader("_info");
            if (!empty($headerinfo['http_code']) && $headerinfo['http_code'] == 200) {
                return $response;
            } else {
                return array();
            }
        } catch (Aliyun_Sls_Exception $ex) {
            var_dump($ex);
        } catch (Exception $ex) {
            var_dump($ex);
        }
    }
}