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

    private function get_url_rtrim_id($link_url)
    {
        $id = intval(ltrim(strrchr($link_url, "/"), '/'));
        return $id;
    }

    private function get_lrts_category_id($link_url)
    {

        $lrts_category_id = $this->get_url_rtrim_id($link_url);
        return $lrts_category_id;
    }

    private function get_lrts_book_id($link_url)
    {

        $lrts_book_id = $this->get_url_rtrim_id($link_url);
        return $lrts_book_id;
    }

    /**
     * @param $link_url
     * @param $page
     * @return array
     *
     *  能获取到:
     *      封面图
     *      标题
     *      故事页url
     */
    public function get_album_list($link_url, $page)
    {

        $album_list = array();
        $lrts_category_id = $this->get_lrts_category_id($link_url);
        $page = intval($page);
        if ($lrts_category_id > 0 && $page > 0) {
            $content_url = sprintf("http://www.lrts.me/book/category/%d/latest/%d/20", $lrts_category_id, $page);
            $content = parent::get($content_url);
            preg_match_all('/<li class="book-item">[\s|\S]*?<\/li>/', $content, $result);
            foreach ($result[0] as $k => $v) {

                $cover = trim(Http::sub_data($v, 'src="', '"'));
                //lrts最大的图是这中180x254的图
                $cover = str_replace(".jpg", "_180x254.jpg", $cover);
                $title = trim(Http::sub_data($v, 'book-item-name">', '</a>'));
                //简介被截取了,无法使用
                //$intro = trim(Http::sub_data($v, 'book-item-desc weaken">', '</p>'));
                $url = 'http://www.lrts.me' . trim(Http::sub_data($v, '<a href="', '"'));
                if ($cover && $title && $url) {
                    $album_list[$k]['cover'] = $cover;
                    $album_list[$k]['title'] = $title;
                    $album_list[$k]['url'] = $url;
                }
            }
        }
        return $album_list;
    }

    /**
     * @param $link_url 专辑url
     *
     *  没有故事封面(有专辑封面)
     *  没有故事简介(有专辑简介)
     *  可以得到:
     *      音频地址  $story_info[$k]['source_audio_url']
     *      排序信息(section)  $story_info[$k]['view_order']
     *      标题  $story_info[$k]['title']
     *      时长  $story_info[$k]['times']
     */

    public function get_album_story_info_list($link_url)
    {

        $album_story_info = array();
        $album_story_info['album']['intro'] = '';
        $album_story_info['album']['story_total_count'] = 0;
        $album_story_info['album']['status'] = '';
        $album_story_info['story'] = array();
        if (!empty($link_url)) {
            $album_page_content = parent::get($link_url);
            $story_total_count = intval(Http::sub_data($album_page_content, 'totalCount=\'', '\''));
            $story_page_size = intval(Http::sub_data($album_page_content, 'pageSize=\'', '\''));
            $story_page_count = intval(ceil($story_total_count / $story_page_size));

            if ($story_page_count > 0) {
                for ($page = 0; $page <= $story_page_count - 1; $page++) {
                    $book_id = intval($this->get_lrts_book_id($link_url));
                    //第一页
                    //http://www.lrts.me/ajax/book/1022/0/10
                    $ajax_url = sprintf("http://www.lrts.me/ajax/book/%d/%d/%d", $book_id, $page, $story_page_size);
                    //$ajax_url;
                    $story_page_content = json_decode(parent::ajax_get($ajax_url));
                    if (0 == strcmp("success", $story_page_content->status)) {
                        $story_count_with_page = count($story_page_content->data->data);

                        for ($i = 0; $i < $story_count_with_page; $i++) {
                            $first_section_with_page = intval($story_page_content->data->data[0]->section);
                            $lrts_playlist_url = sprintf("http://www.lrts.me/ajax/playlist/2/%d/%d", $book_id, $first_section_with_page);
                            $story_info_with_playlist = parent::ajax_get($lrts_playlist_url);

                            if (empty($album_story_info['album']['intro']) || empty($album_story_info['album']['story_total_count']) || empty($album_story_info['album']['status'])) {

                                $album_story_info['album']['intro'] = html_entity_decode(trim(Http::sub_data($story_info_with_playlist, '<p>', '</p>')));
                                $album_story_info['album']['story_total_count'] = intval(trim(Http::sub_data($story_info_with_playlist, '章节：<span>', '</span>')));

                                if ($story_total_count != $album_story_info['album']['story_total_count']) {
                                    echo sprintf("[{$link_url}]js里面的故事数量[%d]和页面展示的故事数量[%d]不一致 \r\n", $story_total_count, $album_story_info['album']['story_total_count']);
                                }
                                //专辑状态,目前没有使用
                                $album_story_info['album']['status'] = trim(Http::sub_data($story_info_with_playlist, '状态：<span>', '</span>'));
                            }
                            preg_match_all('/<li [\s|\S]*?<\/li>/', $story_info_with_playlist, $result);
                            if (!empty($result[0]) && count($result[0]) > 0) {
                                foreach ($result[0] as $index => $story_content) {
                                    $k = $page * $story_page_size + $index;
                                    $album_story_info['story'][$k]['source_audio_url'] = trim(Http::sub_data($story_content, 'value="', '" name="source'));
                                    $album_story_info['story'][$k]['view_order'] = trim(Http::sub_data($story_content, 'section-number">', '</span>'));
                                    $title = trim(Http::sub_data($story_content, '<span>', '</span>'));
                                    $album_story_info['story'][$k]['title'] = html_entity_decode($title);
                                    preg_match_all('/<div class=\"column3 nowrap\">([\s|\S]*?)<\/div>/', $story_content, $divs);
                                    if (isset($divs[1][0]) && !empty($divs[1][0])) {
                                        $times_str = trim($divs[1][0]);
                                        $album_story_info['story'][$k]['times'] = get_seconds($times_str);
                                    } else {
                                        $album_story_info['story'][$k]['times'] = 0;
                                    }

                                }

                            }
                        }
                    }
                }

            }
        }
        return $album_story_info;
    }

    /**
     * 从故事页获取专辑简介
     * @param $link_url
     * @return string
     */
    public function get_album_intro_from_story($link_url)
    {

        $intro = "";
        if (!empty($link_url)) {
            $album_page_content = parent::get($link_url);
            $story_total_count = intval(Http::sub_data($album_page_content, '<p style="display: block;">', '\''));
            $story_page_size = intval(Http::sub_data($album_page_content, 'pageSize=\'', '\''));
            $story_page_count = ceil($story_total_count / $story_page_size);
        }
        return $intro;

    }
    
}