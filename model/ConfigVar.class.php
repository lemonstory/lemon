<?php
class ConfigVar
{
    public $AGE_TYPE_All = 0; //全部 
    public $AGE_TYPE_FIRST = 1; // 0-2岁
    public $AGE_TYPE_SECOND = 2; // 3-6岁
    public $AGE_TYPE_THIRD = 3; // 7-10岁
    public $AGE_TYPE_LIST = array(1, 2, 3);
    public $AGE_TYPE_NAME_LIST = array(
            "0" => "全部",
            "1" => "0-2岁",
            "2" => "3-6岁",
            "3" => "7-10岁"
            );
    
    public $RECOMMEND_STATUS_ONLIINE = 1; // 推荐上线状态
    public $RECOMMEND_STATUS_OFFLINE = 2; // 推荐下线状态
    
    public $OPTION_STATUS_PASS = 1; // 正常状态
    public $OPTION_STATUS_FROZEN = 2; // 冻结状态
    public $OPTION_STATUS_FORBIDDEN = 3; // 封号状态
    public $OPTION_STATUS_DELETE = 4; // 删除状态
    public $OPTION_STATUS_LIST = array(1, 2, 3, 4);
    public $OPTION_STATUS_NAME = array(
            "1" => "正常",
            "2" => "已冻结",
            "3" => "已封号",
            "4" => "已删除"
            );
    
    public $GENDER_BOY = 1;    // 男
    public $GENDER_GIRL = 2;   // 女
}