<?php

include_once '../controller.php';
class comment extends controller
{
    public function action() {
        // 更新专辑封面
        $url = 'http://product.dangdang.com/23735524.html';
        $dangdang_id = Http::sub_data($url, 'com/', '.html');

        Http::$referer = $url;

        $page = 1;

        while (true) {
            $url_page = "http://product.dangdang.com/comment/comment.php?product_id={$dangdang_id}&datatype=1&page={$page}&filtertype=2&sysfilter=1&sorttype=1";

            $content = Http::ajax_get($url_page);
            $content = iconv('GBK', 'UTF-8', $content);
            $content = json_decode($content, true);

            var_dump($content);
            
            // product_id
            // review_id
            // cust_id
            // displayid
            // cust_name
            // cust_img             评论人头像 http://img38.dangdang.com/imghead/76/36/6402928654858-1_d.png
            // cust_lev             评论星级
            // reviewer_rating
            // score
            // content
            // creation_date
            // experience_pics
            // append_review
            // append_date
            // business_reply
            // business_reply_date
            // sell_attr
            // source_type          来自手机
            // cust_vip_type_txt
            // append_pics

            // fields
            //

            exit;
            $page ++;
        }



        $comment_list = Http::ajax_get($url_page);
    }

    /**
     * 获取tag
     */
    private function get_tag_from_dangda()
    {
        $content = Http::sub_data($url);

        $content =  @iconv('GBK', 'UTF-8', $content);
        $content = Http::sub_data($content, '<div class="show_info_left">所属分类</div>', '</div>');
        $content = htmlspecialchars_decode($content);
        $content = str_replace("&nbsp", '', $content);
        $r = explode("br", $content);

        $tag_list = array();

        foreach($r as $k => $v) {
            preg_match_all('/<a[\S|\s].*?a>/', $v, $result);
            foreach($result[0] as $k2 => $v2) {
                $tag_list[$k][] = Http::sub_data($v2, '>', '<');
            }
        }

        var_dump($tag_list);

        /************
        array(2) {
          [0]=>
          array(4) {
            [0]=>
            string(6) "图书"
            [1]=>
            string(6) "童书"
            [2]=>
            string(15) "平装图画书"
            [3]=>
            string(6) "欧美"
          }
          [1]=>
          array(4) {
            [0]=>
            string(6) "图书"
            [1]=>
            string(6) "童书"
            [2]=>
            string(6) "3-6岁"
            [3]=>
            string(23) "卡通/动漫/图画书"
          }
        }

        ************/


    }
}
new comment();