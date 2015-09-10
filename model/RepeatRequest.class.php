<?php
class RepeatRequest extends ModelBase
{
    public $CACHE_INSTANCE = 'cache';
    
    /**
     * 检测同一个IP，60秒内，同一个接口，只能请求3次
     * 暂时只限制故事、专辑列表接口
     * @param S $module    model
     * @param S $action    接口名
     */
    public function validateHostRequet($module, $action)
    {
        $userhost = $_SERVER['REMOTE_ADDR'];
        
    }
}