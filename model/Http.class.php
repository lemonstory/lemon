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
     * @param $content 截取的内容
     * @param $start   开始位置
     * @param $end     结速位置
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

    // 删除换行
    public static function remove_n($str)
    {
        $patten = array("\r\n", "\n", "\r", "\t");
        //先替换掉\r\n,然后是否存在\n,最后替换\r
        $str=str_replace($patten, "", $str);
        return $str;
    }

    /**
     * 下载远程文件
     */
    public static function download($url = '', $dest = '', $filename = '', $file_ext = '')
    {
        if (!$url) {
            return false;
        }
        $dest = rtrim($dest, '/').'/'.$filename.'.'.ltrim($file_ext, '.');
        //获取远程文件
        $ch=curl_init();
        $timeout = 30;
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, $timeout);
        $content = curl_exec($ch);
        curl_close($ch);
        //文件大小
        $fp2=@fopen($dest, 'w');
        fwrite($fp2, $content);
        fclose($fp2);
        unset($fp2, $content, $timeout, $ch);
        return $dest;
    }

}