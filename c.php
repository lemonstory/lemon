<?php
require dirname(__FILE__).'/init.php';
abstract class controller
{
	
	
    protected $actionData = array();
    
    public function __construct()
    {
        $this->getActionData();
        $this->checkFilters();
        $this->action();
    }
    
    public function checkFilters()
    {
        $filters = $this->filters();
        $httpCacheConf = @$filters['httpCache'];
        if (!empty($httpCacheConf)){
            $this->checkHttpCache($httpCacheConf);
        }
    }
    
    private function getActionData()
    {
        $script = str_replace('.php', '', @$_SERVER['SCRIPT_NAME']);
        $scriptArr = @explode('/', trim($script, '/'));
        if (!is_array($scriptArr)){
            return array();
        }
        list($module, $action) = $scriptArr;
        $data['module'] = $module;
        $data['action'] = $action;
        
        $querys = $_SERVER["QUERY_STRING"];
        $params = array();
        if (!empty($querys)){
            $queryParts = explode('&', $querys);
            foreach ($queryParts as $param)
            {
                $item = explode('=', $param);
                $params[$item[0]] = $item[1];
            }
        }
        $data['params'] = $params;
        
        $this->actionData = $data;
        
        return $data;
    }

    protected function getUid()
    {
        $SsoObj = new Sso();
        $uid = $SsoObj->getUid();
        return $uid;
    }
    
    abstract  function action();
    
    
    protected function showErrorJson($data)
    {
        if(empty($data))
        {
            $data = ErrorConf::systemError();
        }
        echo json_encode($data);
        exit;
    }
    protected function showSuccJson($data=array())
    {
        if(empty($data))
        {
            echo json_encode(array('code'=>10000));
        }else{
            echo json_encode(array('code'=>10000,'data'=>$data));
        }
        exit;
    }
    
    public function getRequest($option, $default='', $method='request')
    {
        if ($method == 'get'){
            return isset($_GET[$option]) ? $_GET[$option] : $default;
        } else if ($method == 'post'){
            return isset($_POST[$option]) ? $_POST[$option] : $default;
        } else{
            return isset($_REQUEST[$option]) ? $_REQUEST[$option] : $default;
        } 
    }
    
    public function checkHttpCache($httpCacheConf)
    {
        $httpCacheObj = new HttpCache();
        $httpCacheConf = $httpCacheObj->checkCacheConf($httpCacheConf, $this->actionData);
        if (!empty($httpCacheConf)){
            $httpCacheObj->checkHttpCache($httpCacheConf);
        }
    }
}