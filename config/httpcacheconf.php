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
                "cachetime" => 600,
                "params" => array()
        ),
        // 热门推荐
        "default_hotrecommendlist" => array(
                "action" => "default_hotrecommendlist",
                "cachetime" => 600,
                "params" => array("p", "len")
        ),
        // 同龄在听
        "default_sameagelist" => array(
                "action" => "default_sameagelist",
                "cachetime" => 600,
                "params" => array("p", "len")
        ),
        
        // listen
        /* "liste_getlistenlist" => array(
                "action" => "liste_getlistenlist",
                "cachetime" => 60,
                "params" => array("direction", "startid", "len")
        ), */
);
