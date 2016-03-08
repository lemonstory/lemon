<?php

/**
 * 口袋故事采集
 * 1、get_parent_category 获取顶级分类
 * 2、get_children_category_list 获取子类
 * 3、get_children_category_album_list 获取子分类专辑列表
 * 4、get_album_story_list 获取专辑故事列表
 */
class Kdgs extends Http
{
    /**
     * 获取顶级分类
     */
    public function get_parent_category($url)
    {
        $content = parent::get($url);

        preg_match_all('/<ul [\s|\S]*?<\/ul>/', $content, $result);

        $r = array();
        if (!isset($result[0][0])) {
            exit('');
        }
        if (!$result[0][0]) {
            exit('');
        }
        if (!strstr($result[0][0], 'parentcatlist')) {
            exit('');
        }

        $ul_content = $result[0][0];

        preg_match_all('/<a [\s|\S]*?<\/a>/', $content, $result);

        foreach($result[0] as $k => $v) {
            $url   = http::sub_data($v, 'href="', '"');
            $cover = http::sub_data($v, 'background-image:url(', ')');
            $title = http::sub_data($v, '<span>', '</span>');
            if ($title && $url) {
                $r[$k]['link_url'] = "http://m.idaddy.cn".htmlspecialchars_decode($url);
                $r[$k]['title'] = $title;
                $r[$k]['cover'] = $cover;
                $r[$k]['s_id'] = http::sub_data($url, 'parentId=', '&'); // parent_source_category_id
                $r[$k]['s_p_id'] = 0;
            }
        }

        return $r;
    }

    /**
     * 获取二级分类
     */
    public function get_children_category_list($url)
    {
        $content = parent::get($url);

        // 首页分类
        preg_match_all('/<ul [\s|\S]*?<\/ul>/', $content, $result);

        $r = array();
        if (!isset($result[0][0])) {
            return array();
        }
        if (!$result[0][0]) {
            return array();
        }
        if (!strstr($result[0][0], 'catlist')) {
            return array();
        }

        $ul_content = $result[0][0];

        preg_match_all('/<a [\s|\S]*?<\/a>/', $content, $result);

        foreach($result[0] as $k => $v) {
            $url   = http::sub_data($v, 'href="', '"');
            $cover = http::sub_data($v, 'src="', '"');
            $title = http::sub_data($v, '<span>', '</span>');
            if ($title && $url) {
                $r[$k]['link_url'] = 'http://m.idaddy.cn'.htmlspecialchars_decode($url);
                $r[$k]['title'] = $title;
                $r[$k]['cover'] = $cover;
                $r[$k]['s_p_id'] = http::sub_data($url, 'term_taxonomy_id=', '&'); // parent_source_category_id
                $r[$k]['s_id']   = 0;
            }
        }
        return $r;

    }

    // 获取子分类的专辑列表
    // term_taxonomy_id 为该网站解析的ID
    public function get_children_category_album_list($term_taxonomy_id = 0, $page = 1)
    {
        $r   = array();
        $url = "http://m.idaddy.cn/m.php?etr=spadmin&mod=freeAudio&do=list&term_taxonomy_id={$term_taxonomy_id}&cat_id=0&keyword=&spId=0&page={$page}&rand=".mt_rand(100,400);

        if (!$term_taxonomy_id) {
            return array();
        }

        if ($page == 1) {
            $content = parent::get($url);

            preg_match_all('/<ul [\s|\S]*?<\/ul>/', $content, $result);
            $r = array();
            if (!isset($result[0][0])) {
                return array();
            }
            if (!$result[0][0]) {
                return array();
            }
            if (!strstr($result[0][0], 'list')) {
                return array();
            }

            $ul_content = $result[0][0];
        } else {
            $content = http::ajax_get($url);
            $content = json_decode($content, true);
            if ($content['recode'] != 1) {
                return array();
            }
            $ul_content = $content['context'];
        }

        preg_match_all('/<a [\s|\S]*?<\/a>/', $ul_content, $result);

        foreach($result[0] as $k => $v) {

            $url   = http::sub_data($v, 'href="', '"');
            if (!strstr($url, 'http')) {
                $url = 'http://m.idaddy.cn'.$url;
            }
            $cover = http::sub_data($v, 'src="', '"');
            $title = http::sub_data($v, '<strong>', '</strong>');
            if ($title && $url) {
                $r[$k]['url']     = $url;
                $r[$k]['title']   = $title;
                $r[$k]['cover']   = $cover;
                $r[$k]['age_str'] = http::sub_data($v, '<td style=" padding:0 0 0 10px;">', '<');
            }
        }
        return $r;
    }

    // 获取故事列表
    public function get_album_story_list($url = '')
    {
        if (!$url) {
            return array();
        }
        $content = http::get($url);

        $intro = http::sub_data($content, '<div id="video_word">', '</div>');
        $intro = http::remove_n(trim($intro));
        $cover = http::sub_data($content, '<div id="infoImg">', '</div>');
        $cover = http::sub_data($cover, 'src="', '"');

        preg_match_all('/<audio [\s|\S]*?<\/audio>/', $content, $audio_list);
        preg_match_all('/<ul [\s|\S]*?<\/ul>/', $content, $title_list);
        preg_match_all('/<li [\s|\S]*?<\/li>/', $title_list[0][0], $title_list);

        $audio_list = array_pop($audio_list);
        $title_list = array_pop($title_list);

        $r = array();

        foreach ($title_list as $k => $v) {
            if (empty($audio_list[$k])) {
                continue;
            }
            $title = http::sub_data($v, '>', '<');
            $title = http::remove_n($title);
            $source_audio_url = http::sub_data($audio_list[$k], "src='", "'");
            if ($title && $source_audio_url) {
                $r[$k]['title'] = addslashes(str_replace('&#39;', "'", $title));
                $r[$k]['intro'] = addslashes(str_replace('&#39;', "'", $intro));
                $r[$k]['cover'] = $cover;
                $r[$k]['source_audio_url'] = $source_audio_url;
            }
        }
        return $r;
    }

    /**
     * 获取age_type
     */
    public function get_age_type($age_str = '')
    {
        // 1 0~2
        // 2 3~6
        // 3 7~10
        $age_type = 0;
        if (strstr($age_str, 'P')) {
            if ($age_str == 'P+') {
                $age_type = 1;
            } else if (strstr($age_str, '-')) {
                $tmp = explode('-', $age_str);
                if (isset($tmp[1])) {
                    if ($tmp[1] <= 2) {
                        $age_type = 1;
                    } else if ($tmp[1] <= 6 ) {
                        $age_type = 2;
                    } else if ($tmp[1] <= 10 ) {
                        $age_type = 3;
                    }
                }
            }
        } else if (strstr($age_str, '岁')) {
            $str = str_replace('岁', '', $age_str);
            $tmp = explode('-', $str);
            if (isset($tmp[1])) {
                if ($tmp[1] <= 2) {
                    $age_type = 1;
                } else if ($tmp[1] <= 6 ) {
                    $age_type = 2;
                } else if ($tmp[1] <= 10 ) {
                    $age_type = 3;
                }
            }
        } else {
            echo $age_str;
        }
        return $age_type;
    }
}