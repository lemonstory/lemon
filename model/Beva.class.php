<?php


class Beva extends Http
{


    /**
     * 获取专辑列表
     * @param $url
     * @param $page
     * @return array
     */
    public function get_album_list($url, $page)
    {

        //http://g.beva.com/mp3/topic/10001--0--1.html
        $pos = strpos($url, '.html');
        $subUrl = substr($url, 0, $pos);
        $completeUrl = sprintf("%s--0--%s.html", $subUrl, $page);
        //var_dump($completeUrl);
        $content = parent::get($completeUrl);

        $liContent = http::sub_data($content, "<div class=\"box\">", "</ul>");
        preg_match_all('/<li>[\s|\S]*?<\/li>/', $liContent, $result);
        $albumList = array();
        if (is_array($result[0]) && !empty($result[0])) {

            foreach ($result[0] as $item) {
                $url = http::sub_data($item, '<li><a href="', '"');
                $cover = http::sub_data($item, 'src="', '"');
                $title = http::sub_data($item, 'title="', '"><img');

                $album['url'] = $url;
                $album['cover'] = $cover;
                $album['title'] = $title;
                $albumList[] = $album;
            }
        }
        return $albumList;
    }

    /**
     * 获取专辑声音信息
     * @param string $url
     * @return
     *      $album_story_list['album']['intro'] = string
     *      $album_story_list['story'] = array()
     *      $album_story_list['story'][$key]['title']
     *      $album_story_list['story'][$key]['source_audio_url']
     *      $album_story_list['story'][$key]['view_order']
     *      $album_story_list['story'][$key]['listen_num']
     */
    public function get_album_story_list($url = '')
    {

        $album_story_list = array();
        $album_story_list['album']['intro'] = '';
        $album_story_list['story'] = array();
        $content = parent::get($url);
        $divContent = http::sub_data($content, "<div class=\"cont\" id=\"ac-fm-albuminfo\"", "<div class=\"mod\">");
        $intro = http::sub_data($divContent, "<p>", "</p>");
        if (!empty($intro)) {
            $album_story_list['album']['intro'] = $intro;
        }

        $liContent = http::sub_data($content, "<ol id=\"ac-fm-alist\">", "</ol>");
        preg_match_all('/<li data-item="[\s|\S]*?">/', $liContent, $liResult);
        preg_match_all('/<span>[\S]+<\/span>/', $liContent, $spanResult);

        if (is_array($liResult[0]) && !empty($liResult[0]) && is_array($spanResult[0]) && !empty($spanResult[0]) && count($liResult[0]) == count($spanResult[0])) {

            $story_info = array();
            foreach ($liResult[0] as $key => $item) {
                $str = http::sub_data($item, '<li data-item="', '">');
                $strArr = explode("|", $str);
                $id = $strArr[0];
                $title = $strArr[1];
                $listenNum = intval(str_replace(",", "", http::sub_data($spanResult[0][$key], '<span>', '人听过</span>')));
                //http://g.beva.com/mp3/action/get?t=r&l=722

                $ajax_url = sprintf("http://g.beva.com/mp3/action/get?t=r&l=%s", $id);
                $ajax_content = json_decode(parent::ajax_get($ajax_url));
                if (0 == $ajax_content->errorCode) {
                    $story_info['source_audio_url'] = sprintf("http://ss.beva.cn%s", $ajax_content->data[0]->url);
                    $story_info['title'] = $title;
                    $story_info['view_order'] = $key + 1;
                    $story_info['listen_num'] = $listenNum;
                    $album_story_list['story'][] = $story_info;
                }
            }
        }
        return $album_story_list;
    }
}