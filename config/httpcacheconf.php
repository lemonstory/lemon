<?php
/*
 * http cache 模块配置
 * 规则：
 * "module_action" => array(
 *     "action" => "module_action",
 *     "cachetime" => "缓存时间",
 *     "params" => array("参数1", "参数2")
 * )
 */
$_SERVER['http_cache_conf'] = array(
        // 首页
        "default_index" => array(
                "action" => "default_index",
                "cachetime" => 3600,
                "params" => array()
        ),
        
        ######################列表############################
        // 热门推荐
        "default_hotrecommendlist" => array(
                "action" => "default_hotrecommendlist",
                "cachetime" => 3600,
                "params" => array("p", "len")
        ),
        // 同龄在听
        "default_sameagelist" => array(
                "action" => "default_sameagelist",
                "cachetime" => 3600,
                "params" => array("p", "len")
        ),
        // 最新上架
        "default_newonlinelist" => array(
                "action" => "default_newonlinelist",
                "cachetime" => 3600,
                "params" => array("p", "len")
        ),
        // 学霸排行榜
        "userinfo_ranklistenuserlist" => array(
                "action" => "userinfo_ranklistenuserlist",
                "cachetime" => 3600,
                "params" => array("len")
        ),
        
        ######################列表END############################

        ######################详情############################
        // 故事辑详情
        /* "album_info" => array(
                "action" => "album_info",
                "cachetime" => 1800,
                "params" => array("albumid", "iscommentpage", "len", "direction", "startid")
        ), */
        
        
        ######################详情End############################

        
        ######################其他############################
        "search_hotsearch" => array(
                "action" => "search_hotsearch",
                "cachetime" => 3600,
                "params" => array("len")
        ),
        ######################其他END############################
);
