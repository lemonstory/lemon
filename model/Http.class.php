<?php

class Http
{
	public static $is_ajax    = false;
	public static $cookie     = '';
	public static $user_agent = 'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.124 Safari/537.36';
	public static $referer    = 'http://www.baidu.com';

	// ajax请求
	public static function ajax_get($url, $data = array())
	{
		self::$is_ajax = true;
		return self::get($url, $data);
	}

	// get 数据
	public static function get($url, $data = array())
	{
		$header = array();

		$header[] = 'CLIENT-IP:202.103.229.40';
        $header[] = 'X-FORWARDED-FOR:202.103.229.40';

        if (self::$is_ajax) {
        	$header[] = 'X-Requested-With:XMLHttpRequest';
        }

        if (self::$is_ajax) {
            $header[] = 'X-Requested-With:XMLHttpRequest';
        }
        if (self::$cookie) {
        	$header[] = 'Cookie:'.self::$cookie;
        }

        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if (self::$user_agent) {
        	curl_setopt($ch, CURLOPT_USERAGENT, self::$user_agent);
        }
        
        if (self::$referer) {
        	curl_setopt($ch, CURLOPT_REFERER, self::$referer);
        }
 
        $output = curl_exec($ch);
 
        curl_close($ch);

        return $output;
	}

	/**
     * 截字符串 
     * @return string          [description]
     */
    public static function sub_data($content = '', $start = '', $end = '')
    {
        $start_postion = 0;
        $end_postion = strlen($content);

        if (!$content) {
            return '';
        }

        if ($start) {
            $content = explode($start, $content);
            if (isset($content[1])) {
                $content = $content[1];
            } else {
                return '';
            }
        }

        if ($end) {
            $content = explode($end, $content);
            if (isset($content[0])) {
                $content = $content[0];
            } else {
                return '';
            }
        }
        return $content;

    }

}