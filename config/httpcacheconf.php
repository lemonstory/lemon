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
                "cachetime" => 86400,
                "params" => array()
        ),
        // 热门推荐
        "default_hotrecommendlist" => array(
                "action" => "default_hotrecommendlist",
                "cachetime" => 86400,
                "params" => array("p", "len")
        ),
        // 同龄在听
        "default_sameagelist" => array(
                "action" => "default_sameagelist",
                "cachetime" => 86400,
                "params" => array("p", "len")
        ),
        // 最新上架
        "default_newonlinelist" => array(
                "action" => "default_newonlinelist",
                "cachetime" => 86400,
                "params" => array("p", "len")
        ),
        
);
