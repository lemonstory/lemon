<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/24
 * Time: 上午9:58
 */
include_once '../../controller.php';
class categorylist extends controller
{
    const CACHE_INSTANCE = 'cache';
    const CACHE_EXPIRE = 3600;

    public function action()
    {

        $data = array();

        //获取年龄段
        $configVar = new ConfigVar();
        $age_level_arr = $configVar->AGE_LEVEL_ARR;
        array_shift($age_level_arr);

        if (count($age_level_arr) > 0) {
            $data['age_level'] = array();
            $data['age_level']['total'] = count($age_level_arr);
            foreach ($age_level_arr as $val) {
                $item = array();
                $item['title'] = sprintf("%s-%s岁", $val['min_age'], $val['max_age']);
                $item['cover'] = sprintf("http://p.xiaoningmeng.net/age_level/%s-%s.png", $val['min_age'], $val['max_age']);
                //$item['linkurl'] = sprintf("xnm://www.xiaoningmeng.net/default/v2.6/age_level_list.php?min_age=%s&min_age=%s", $val['min_age'], $val['max_age']);
                //TODO:暂时把跳转地址更改到最新上架,不过上线后这里不能更改。否则线上2.6版客户端全部分类的年龄跳转会出现错误。
                $item['linkurl'] = sprintf("xnm://api.xiaoningmeng.net/default/v2.6/online_list.php?&min_age=%s&max_age=%s", $val['min_age'], $val['max_age']);
                $data['age_level']['items'][] = $item;
            }
        }
        $redisobj = AliRedisConnecter::connRedis(self::CACHE_INSTANCE);
        $categoryTagsKey = RedisKey::getCategoryTagsKey();
        $data = array();
        $redisData = $redisobj->get($categoryTagsKey);
        if ($redisData) {
            $data['tag'] = unserialize($redisData);
        }
        else {
            $data['tag'] = array();
            $tagInfoObj = new TagInfo();
            $aliossObj = new AliOss();
            //获取一级分类
            $firstList = $tagInfoObj->get_list(' pid = 0 and status=1', 'id,name', 'ordernum desc', 100);
            $data['tag']['total'] = count($firstList);
            foreach ($firstList as $key => $val) {
                $tmp['id'] = $val['id'];
                $tmp['name'] = $val['name'];

                //取二级分类
                $secondList = $tagInfoObj->get_list(' pid = ' . $val['id'] . ' and status=1', 'id,name,cover,covertime',
                    'ordernum desc', 100);
                foreach ($secondList as $key => $val) {
                    $secondList[$key]['id'] = $val['id'];
                    $secondList[$key]['name'] = $val['name'];
                    $secondList[$key]['cover'] = "";
                    if (!empty($val['cover'])) {
                        $secondList[$key]['cover'] = $aliossObj->getImageUrlNg($aliossObj->IMAGE_TYPE_TAG,
                            $val['cover'], 0, $val['covertime']);
                    }
                    $secondList[$key]['linkurl'] = sprintf("xnm://www.xiaoningmeng.net/default/v2.6/tag_album_list.php?tag_id=%s",
                        $val['id']);
                    unset($secondList[$key]['covertime']);
                }
                $tmp['child_total'] = count($secondList);
                $tmp['child_items'] = $secondList;

                $data['tag']['items'][] = $tmp;
            }
            $redisobj->setex($categoryTagsKey, self::CACHE_EXPIRE, serialize($data['tag']));
        }
        $this->showSuccJson($data);
    }
}
new categorylist();