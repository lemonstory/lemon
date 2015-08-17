<?php
require dirname(__FILE__).'/init.php';

## log page runtime
define('SLOWPAGELOG', true);
define('SLOWPAGELOGPATH', '/alidata1/www/logs/');
define('SLOWPAGELOGTIME', '0.5');
Runtime::logRunTime();
register_shutdown_function(array('Runtime', 'logRunTime'));

abstract class controller
{
    protected $actionData = array();
    ## xhprof
    protected $xhprofRandNum = 0;
    
    public function __construct()
    {
        $this->xhprofRandNum = mt_rand(1, 1000);
        $this->getActionData();
        $this->checkFilters();
        $this->getAppVertion();
        //$this->getLanguage();
        $this->action();
    }
    
    protected function getAppVertion()
    {
    	$userAgent = @$_SERVER['HTTP_USER_AGENT'];
    	if($userAgent!="")
    	{
	    	$agentArr = explode('/', $userAgent);
	    	$version = str_pad(str_replace('.', '', @$agentArr[1]),9,0)+0;
	    	if($version==0)
	    	{
	    		$version=1610000;
	    	}
	    	if($version>0)
	    	{
	    		$_SERVER['visitorappversion'] = $version;
	    	}
    	}
    	if(isset($_GET['visitorappversion']))
    	{
    		$version = str_pad(str_replace('.', '', @$_GET['visitorappversion']),9,0)+0;
    		$_SERVER['visitorappversion'] = $version;
    	}
    }
    
    
    protected function getSmartyObj()
    {
        include_once SERVER_ROOT.'libs/smarty/Smarty.class.php';
        $smarty					 	= new Smarty();
        $smarty->template_dir   	= SERVER_ROOT."view/html/";
        $smarty->compile_dir 		= SERVER_ROOT."view/templates_c/";
        $smarty->cache_dir   		= SERVER_ROOT."view/cache/";
        return $smarty;
    }
    
    public function checkFilters()
    {
        $filters = $this->filters();
        
        $httpCacheConf = @$filters['httpCache'];
        $this->checkHttpCache($httpCacheConf);
    }
    
    public function filters()
    {
        return array();
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
            	if($param=="")
            	{
            		continue;
            	}
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
    
    
    protected function showErrorJson($data=array())
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
        if(empty($data) && $data!=0)
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
    
    protected function redirect($url, $statusCode = 302)
    {
        if(strpos($url,'/')===0 && strpos($url,'//')!==0) {
            if(isset($_SERVER['HTTP_HOST'])) {
                $hostInfo = 'http://'.$_SERVER['HTTP_HOST'];
            } else {
                $hostInfo = 'http://'.$_SERVER['SERVER_NAME'];
            }
            $url = $hostInfo . $url;
        }
        $this->endXhprof();
        header('Location: ' . $url, true, $statusCode);
    }
    
    public function checkHttpCache($httpCacheConf)
    {
        $httpCacheObj = new HttpCache();
        $httpCacheConf = $httpCacheObj->checkCacheConf($httpCacheConf, $this->actionData);
        if (!empty($httpCacheConf)){
            $httpCacheObj->checkHttpCache($httpCacheConf);
        }
    }
    
    public function startXhprof()
    {
        if (XHPROF_DEBUG == 1) {
            // 手动开启xhprof
            if ($this->getRequest('debug') == 'tutu') {
                // cpu:XHPROF_FLAGS_CPU 内存:XHPROF_FLAGS_MEMORY
                // 如果两个一起：XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY
                xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
            }
        } elseif (XHPROF_DEBUG == 2) {
            // 随机数为1的请求才开启
            if ($this->xhprofRandNum == 1) {
                xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
            }
        }
    }
    
    public function endXhprof()
    {
        include_once SERVER_ROOT . "/libs/xhprof/xhprof_lib/utils/xhprof_lib.php";
        include_once SERVER_ROOT . "/libs/xhprof/xhprof_lib/utils/xhprof_runs.php";
        $nameSpace = "tutu";
        if (XHPROF_DEBUG == 1) {
            if ($this->getRequest('debug') == 'tutu') {
                $data = xhprof_disable();   //返回运行数据
                $objXhprofRun = new XHProfRuns_Default();
                
                // 第一个参数j是xhprof_disable()函数返回的运行信息
                // 第二个参数是自定义的命名空间字符串(任意字符串),
                // 返回运行ID,用这个ID查看相关的运行结果
                $runId = $objXhprofRun->save_run($data, $nameSpace);
                echo "<br><a target='_blank' href='/libs/xhprof/xhprof_html/index.php?run={$runId}&source={$nameSpace}'>view</a>";
            }
        } elseif (XHPROF_DEBUG == 2) {
            if ($this->xhprofRandNum == 1) {
                $data = xhprof_disable();
                $objXhprofRun = new XHProfRuns_Default();
                $runId = $objXhprofRun->save_run($data, $nameSpace);
            }
        }
        
        
    }
    
    public function commonHumanTime($time)
    {
		$dur = time() - $time;
		if ($dur < 60) {
			return $dur.$_SERVER['morelanguage']['sec'];
		} elseif ($dur < 3600) {
			return floor ( $dur / 60 ) . $_SERVER['morelanguage']['mins'];
		} elseif ($time > mktime ( 0, 0, 0 )) {
			return $_SERVER['morelanguage']['today'] . date ( 'H:i', $time );
		} elseif ($time > mktime ( 0, 0, 0 )-86400) {
			return $_SERVER['morelanguage']['yesterday'] . date ( 'H:i', $time );
		} elseif ($time > mktime ( 0, 0, 0 )-172800 ){
			return $_SERVER['morelanguage']['tfyesterday'] . date ( 'H:i', $time );
		}elseif ($time > mktime ( 0, 0, 0)-86400*365){
			return date ( 'm-d H:i', $time );
		}else {
			return date ( 'Y-m-d', $time );
		}
    }
}