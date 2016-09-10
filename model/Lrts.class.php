<?php

/**
 * 获取专辑分类 Xmly->get_category();
 * 获取专辑列表 Xmly->get_album_list();
 * 获取故事列表 Xmly->get_story_list();
 */
class Lrts extends Http
{

    // 获取专辑分类
    public function get_category($url = '')
    {
        $content = parent::get($url);
        $content = Http::sub_data($content, '<span>少儿天地</span>', '</div>');
        preg_match_all('/<a [\s|\S]*?<\/a>/', $content, $result);
        $r = array();
        //var_dump($result);

        foreach ($result[0] as $k => $v) {

            //去掉全部
            if (strstr($v, '/book/category/6"')) {
                continue;
            }

            $url = 'http://www.lrts.me' . Http::sub_data($v, 'href="', '"');

            $title = Http::sub_data($v, '">', '</a>');
            if ($title && $url) {
                $r[$k]['title'] = $title;
                $r[$k]['cover'] = "";
                $r[$k]['link_url'] = $url;
            }
        }
        return $r;
    }

}