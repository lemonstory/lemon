<?php
class ConfigVar
{
    public $STORY_DB_INSTANCE = 'share_story';
    public $MAIN_DB_INSTANCE = 'share_main';
    public $COMMENT_DB_INSTANCE = 'share_comment';
    public $ANALYTICS_DB_INSTANCE = 'share_analytics';
    public $MANAGE_DB_INSTANCE = 'share_manage';
    
    public $CACHE_REDIS_INSTANCE = 'cache';
    
    public $AGE_TYPE_All = 0; //全部 
    public $AGE_TYPE_FIRST = 1; // 0-2岁
    public $AGE_TYPE_SECOND = 2; // 3-6岁
    public $AGE_TYPE_THIRD = 3; // 7-10岁
    public $AGE_TYPE_LIST = array(0, 1, 2, 3);
    public $AGE_TYPE_NAME_LIST = array(
            "0" => "全部",
            "1" => "0-2岁",
            "2" => "3-6岁",
            "3" => "7-10岁"
            );


    public $MIN_AGE = 0;    //最小年龄
    public $MAX_AGE = 14;   //最大年龄
    public $AGE_LEVEL_ARR = array(
        //全部
        array(
            'min_age' => 0,
            'max_age' => 14,
        ),

        array(
            'min_age' => 0,
            'max_age' => 2,
        ),

        array(
            'min_age' => 3,
            'max_age' => 6,
        ),

        array(
            'min_age' => 7,
            'max_age' => 10,
        ),

        array(
            'min_age' => 11,
            'max_age' => 14,
        ),
    );

    //专辑连载状态
    public $ALBUM_SERIAL_STATUS_OFF = 0;    //完结
    public $ALBUM_SERIAL_STATUS_ON = 1;     //连载中

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

    public $FOCUS_IMG_CATEGORY_EN_NAME = "home"; //App首页焦点图英文名称


    //今日推荐tag_id
    public $HOT_RECOMMEND_TAG_ID = "10000";
    //同龄在听tag_id
    public $SAME_AGE_TAG_ID = "10001";
    //最新上架tag_id
    public $NEW_ONLINE_TAG_ID = "10002";

    public $DEFAULT_ALBUM_COVER = "http://p.xiaoningmeng.net/album/default.png";
    public $DEFAULT_STORY_COVER = "http://p.xiaoningmeng.net/album/default.png";
}