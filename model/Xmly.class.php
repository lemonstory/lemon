<?php

/**
 * 获取专辑分类 Xmly->get_category();
 * 获取专辑列表 Xmly->get_album_list();
 * 获取故事列表 Xmly->get_story_list();
 */
class Xmly extends Http
{

    // 获取专辑分类
    public function get_category($url = '')
    {
        $content = parent::get($url);

        preg_match_all('/<li [\s|\S]*?<\/li>/', $content, $result);

        $r = array();

        foreach($result[0] as $k => $v) {
            $url   = 'http://m.ximalaya.com'.Http::sub_data($v, 'href="', '"');
            if (strstr($url, 'kid/rank')) {
                continue;
            }
            $title = Http::sub_data($v, 'mgt-5">', '<');
            $cover = Http::sub_data($v, 'src="', '"');
            if ($title && $cover && $url) {
                $r[$k]['title']    = $title;
                $r[$k]['cover']    = $cover;
                $r[$k]['link_url'] = 'http://m.ximalaya.com/explore/more_album?page=1&per_page=10&category_id=6&condition=rank&tag='.$title;
            }
        }
        return $r;
    }

    // 获取专辑列表
    public function get_album_list($page = 1, $tag = '')
    {
        $page_url = "http://m.ximalaya.com/explore/more_album?page={{page}}&per_page=10&category_id=6&condition=recent&tag={{tag}}";
        $page_url = str_replace("{{page}}", $page, $page_url);
        $page_url = str_replace("{{tag}}", urlencode($tag), $page_url);
        // 设置referer
        Http::$referer = $page_url;
        if ($page == 1) {
            $content = Http::get($page_url);
        } else {
            $content = Http::ajax_get($page_url);
        }

        $content = json_decode($content, true);

        if (!isset($content['html'])) {
            return array();
        }
        if (!$content['next_page']) {
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
                // http://fdfs.xmcdn.com/group7/M05/53/22/wKgDX1W24DjS695KAApQxFCdpw4454_web_meduim.jpg
                $arr[$k]['cover'] = str_replace("_meduim", "_large", $cover);
                $arr[$k]['url']   = 'http://m.ximalaya.com'.$url;
                $arr[$k]['count'] = $count;
            }

        }
        return $arr;
    }

    // 获取故事列表
    // http://m.ximalaya.com/album/more_tracks?url=%2Falbum%2Fmore_tracks&aid=159&page=1
    public function get_story_list($album_id = 0, $page = 1)
    {
        if (!$album_id) {
            return array();
        }

        $album_url = "http://m.ximalaya.com/album/more_tracks?url=%2Falbum%2Fmore_tracks&aid={$album_id}&page={$page}";


        Http::$referer = $album_url;

        if ($page == 1) {
            $content = Http::get($album_url);
        } else {
            $content = Http::ajax_get($album_url);
        }
        // 内不存在直接返回空
        if (strstr($content, '您查看的内容不存在')) {
            return array();
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
            sleep(1);
            $title = Http::sub_data($content, '<h1 class="name">', '</h1>');
            $source_audio_url = Http::sub_data($content, 'sound_url="', '"');
            $times = Http::sub_data($content, '<span class="time fr">', '</span>');
            $times = $this->get_seconds($times);
            $intro = htmlspecialchars_decode(Http::sub_data($content, 'data-text="', '"'));
            $intro = preg_replace('/<a[\s|\S].*?a>/', '', $intro);
            $cover = Http::sub_data($content, "background-image: url('", "')");
            if ($title && $source_audio_url) {
                $n[$k]['title'] = $title;
                $n[$k]['source_audio_url'] = $source_audio_url;
                $n[$k]['times'] = $times;
                $n[$k]['intro'] = $intro;
                $n[$k]['s_cover'] = $cover;
            }
        }
        return $n;
    }

    // 返回秒数
    public function get_seconds($times = '')
    {
        if (!$times) {
            return 0;
        }
        $times = explode(":", $times);
        if (isset($times[2])) {
            return $times[2] * 60 * 60 + $times[1] * 60 + $times[0];
        } else if (isset($times[1])) {
            return $times[1] * 60 + $times[0];
        } else {
            return $times[0];
        }
    }
}