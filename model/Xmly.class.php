<?php

/**
 * 获取专辑分类 Xmly::get_category();
 * 获取专辑列表 Xmly::get_album_list();
 * 获取故事列表 Xmly::get_story_list();
 */
class Xmly extends Http
{

    // 获取专辑分类
    public static function get_category($url = '')
    {
        $content = parent::get($url);

        preg_match_all('/<li [\s|\S]*?<\/li>/', $content, $result);

        $r = array();

        foreach($result[0] as $k => $v) {
            $title = Http::sub_data($v, 'mgt-5">', '<');
            $cover = Http::sub_data($v, 'src="', '"');
            $url   = Http::sub_data($v, 'href="', '"');
            if ($title && $cover && $url) {
                $r[$k]['title']         = $title;
                $r[$k]['cover']         = $cover;
                $r[$k]['url']           = $url;
                $r[$k]['category_id']   = self::get_category_id($ul);
            }
        }
        return $r;
    }

    // 获取专辑的分类ID 构建分页用
    public static function get_category_id($album_url = '')
    {
        $content = parent::get($album_url);
        return Http::sub_data($content, "data-category-id='", "'");
    }

    // 获取专辑列表
    // http://m.ximalaya.com/explore/more_album?page={{page}}&per_page=10&category_id={{category_id}}&condition=rank&tag=%E5%84%BF%E6%AD%8C%E5%A4%A7%E5%85%A8
    public static function get_album_list($page_url = '', $page = 1, $category_id = 0)
    {
        $page_url = str_replace("{{page}}", $page, $page_url);
        $page_url = str_replace("{{category_id}}", $category_id, $page_url);
        if ($page == 1) {
            $content = parent::get($page_url);
        } else {
            $content = parent::ajax_get($page_url);
        }
        $content = json_decode($content, true);

        if (!isset($content['html'])) {
            return array();
        }

        preg_match_all('/<li [\s|\S]*?<\/li>/', $content['html'], $result);
        $r = array();
        foreach($result[0] as $k => $v) {
            $title = Http::sub_data($v, 'icon-album1 mgr-5"></i>', '<');
            $cover = Http::sub_data($v, 'src="', '"');
            $url   = Http::sub_data($v, 'data-url="', '"');
            $count = Http::sub_data($v, 'icon-player mgr-5"></i>', '<');
            if ($title && $url) {
                $arr[$k]['title'] = $title;
                $arr[$k]['cover'] = $cover;
                $arr[$k]['url']   = 'http://m.ximalaya.com'.$url;
                $arr[$k]['count'] = $count;
            }

        }
    }

    // 获取故事列表
    // http://m.ximalaya.com/album/more_tracks?url=%2Falbum%2Fmore_tracks&aid=159&page=1
    public static function get_story_list($album_id = 0, $page = 1)
    {
        if (!$album_id) {
            return array();
        }

        $album_url = "http://m.ximalaya.com/album/more_tracks?url=%2Falbum%2Fmore_tracks&aid={{$album_id}}&page={$page}";

        if ($page == 1) {
            $content = parent::get($album_url);
        } else {
            $content = parent::ajax_get($album_url);
        }
        

        $content = json_decode($content, true);

        if (isset($content['sound_ids']) && !$content['sound_ids']) {
            return array();
        }

        preg_match_all('/<li [\s|\S]*?<\/li>/', $content['html'], $result);

        $r = array();

        foreach ($result[0] as $k => $v) {
            $r[]= 'http://m.ximalaya.com'.Http::sub_data($v, 'data-url="', '"');
        }
        $n = array();
        foreach ($r as $k => $v) {
            $content = Http::get($v);
            $title = Http::sub_data($content, '<h1 class="name">', '</h1>');
            $sound_url = Http::sub_data($content, 'sound_url="', '"');
            $length = Http::sub_data($content, '<span class="time fr">', '</span>');
            $length = get_seconds($length);
            $intro = Http::sub_data($content, 'intro-desc', '</div>');
            $intro = Http::sub_data($intro, '>');
            $cover = Http::sub_data($content, "background-image: url('", "')");
            if ($title && $intro) {
                $n[$k]['title'] = $title;
                $n[$k]['sound_url'] = $sound_url;
                $n[$k]['length'] = $length;
                $n[$k]['intro'] = $intro;
                $n[$k]['cover'] = $cover;
            }
            return $n;
        }
        return $n;
    }
}