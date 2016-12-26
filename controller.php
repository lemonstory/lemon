<?php
require dirname(__FILE__) . '/init.php';

## log page runtime
define('SLOWPAGELOG', true);
define('SLOWPAGELOGPATH', '/alidata1/www/logs/');
define('SLOWPAGELOGTIME', '0.5');
Runtime::logRunTime();
register_shutdown_function(array('Runtime', 'logRunTime'));

abstract class controller
{

    protected $actionData = array();
    protected $debug = false;

    const DEV_SITE = 'dev.xiaoningmeng.net';

    const API_SITE = 'api.xiaoningmeng.net';

    public function __construct()
    {
        $this->debug = isset($_GET['debug']) && $_SERVER['HTTP_HOST']== self::DEV_SITE ? true : false;

        $this->checkHostInfo();
        $this->checkImsiInfo();
        $this->getActionData();
        $this->checkFilters();
        $this->getAppVertion();
        $this->debug && $this->debugStart();
        $this->action();
        $this->debug && $this->debugEnd();
    }

    protected function debugStart()
    {
        xhprof_enable();
    }

    protected function debugEnd()
    {
        $debugData = xhprof_disable();
        include_once SERVER_ROOT . "libs/xhprof/xhprof_lib/utils/xhprof_lib.php";//从源码包中拷贝xhprof_lib这个文件夹过来直接可以调用
        include_once SERVER_ROOT . "libs/xhprof/xhprof_lib/utils/xhprof_runs.php";
        $objXhprofRun = new XHProfRuns_Default();//数据会保存在php.ini中xhprof.output_dir设置的目录去中
        $run_id = $objXhprofRun->save_run($debugData, "test");
        echo "<div><hr><hr><a target='_blank' href='http://dev.xiaoningmeng.net/libs/xhprof/xhprof_html/index.php?run={$run_id}&source=test'>查看分析</a><hr><hr></div>";
    }

    protected function checkHostInfo()
    {
        $host = $_SERVER['HTTP_HOST'];
        if ($host != self::API_SITE && $host != self::DEV_SITE) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
    }

    protected function checkImsiInfo()
    {
        $host = $_SERVER['HTTP_HOST'];
        if ($host == self::DEV_SITE) {
            return;
        }

        $imsi = getImsi();
        if (empty($imsi)) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
    }

    protected function getAppVertion()
    {
        $userAgent = @$_SERVER['HTTP_USER_AGENT'];
        if ($userAgent != "") {
            $agentArr = explode('/', $userAgent);
            $version = str_pad(str_replace('.', '', @$agentArr[1]), 6, 0) + 0;
            if ($version > 0) {
                $_SERVER['visitorappversion'] = $version;
            }
        }
        if (isset($_GET['visitorappversion'])) {
            $version = str_pad(str_replace('.', '', @$_GET['visitorappversion']), 6, 0) + 0;
            $_SERVER['visitorappversion'] = $version;
        }
    }


    protected function getSmartyObj()
    {
        include_once SERVER_ROOT . 'libs/smarty/Smarty.class.php';
        $smarty = new Smarty();
        $smarty->template_dir = SERVER_ROOT . "view/html/";
        $smarty->compile_dir = SERVER_ROOT . "view/templates_c/";
        $smarty->cache_dir = SERVER_ROOT . "view/cache/";
        return $smarty;
    }

    public function checkFilters()
    {
        if (HTTP_CACHE == true) {
            $this->checkHttpCache();
        }
    }

    public function filters()
    {
        return array();
    }

    private function getActionData()
    {
        $script = str_replace('.php', '', @$_SERVER['SCRIPT_NAME']);
        $scriptArr = @explode('/', trim($script, '/'));
        if (!is_array($scriptArr)) {
            return array();
        }
        list($module, $action) = $scriptArr;
        $data['module'] = $module;
        $data['action'] = $action;

        $querys = $_SERVER["QUERY_STRING"];
        $params = array();
        if (!empty($querys)) {
            $queryParts = explode('&', $querys);
            foreach ($queryParts as $param) {
                if ($param == "") {
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


    abstract function action();


    protected function showErrorJson($data = array())
    {
        $this->debug && $this->debugEnd();
        if (empty($data)) {
            $data = ErrorConf::systemError();
        }
        echo json_encode($data);
        exit;
    }

    protected function showSuccJson($data = array())
    {
        $this->debug && $this->debugEnd();
        if (empty($data) && $data != 0) {
            echo json_encode(array('code' => 10000));
        } else {
            echo json_encode(array('code' => 10000, 'data' => $data));
        }
        exit;
    }

    public function getRequest($option, $default = '', $method = 'request')
    {
        if ($method == 'get') {
            return isset($_GET[$option]) ? $_GET[$option] : $default;
        } else {
            if ($method == 'post') {
                return isset($_POST[$option]) ? $_POST[$option] : $default;
            } else {
                return isset($_REQUEST[$option]) ? $_REQUEST[$option] : $default;
            }
        }
    }

    protected function redirect($url, $statusCode = 302)
    {
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $hostInfo = 'http://' . $_SERVER['HTTP_HOST'];
            } else {
                $hostInfo = 'http://' . $_SERVER['SERVER_NAME'];
            }
            $url = $hostInfo . $url;
        }
        header('Location: ' . $url, true, $statusCode);
    }

    public function checkHttpCache()
    {
        $httpCacheObj = new HttpCache();
        $httpCacheObj->checkHttpCache($this->actionData);
    }

    public function commonHumanTime($time)
    {
        $dur = time() - $time;
        if ($dur < 60) {
            return $dur . $_SERVER['morelanguage']['sec'];
        } elseif ($dur < 3600) {
            return floor($dur / 60) . $_SERVER['morelanguage']['mins'];
        } elseif ($time > mktime(0, 0, 0)) {
            return $_SERVER['morelanguage']['today'] . date('H:i', $time);
        } elseif ($time > mktime(0, 0, 0) - 86400) {
            return $_SERVER['morelanguage']['yesterday'] . date('H:i', $time);
        } elseif ($time > mktime(0, 0, 0) - 172800) {
            return $_SERVER['morelanguage']['tfyesterday'] . date('H:i', $time);
        } elseif ($time > mktime(0, 0, 0) - 86400 * 365) {
            return date('m-d H:i', $time);
        } else {
            return date('Y-m-d', $time);
        }
    }
}