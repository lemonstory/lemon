<?php

class Album extends ModelBase
{

    public $table = 'album';
    public $CACHE_INSTANCE = 'cache';


    /**
     * 检查是否存在
     */
    public function check_exists($where = '')
    {
        if (!$where) {
            return false;
        }
        if ($this->get_total($where)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取总数
     */
    public function get_total($where = '')
    {
        $db = DbConnecter::connectMysql('share_story');
        $sql = "select count(*) as count from {$this->table}  where {$where}";
        $st = $db->query( $sql );
        $r = $st->fetchAll();
        return $r[0]['count'];
    }

    /**
     * 插入记录
     */
    public function insert($data)
    {
        if (!$data) {
            return 0;
        }
        $tmp_filed = array();
        $tmp_value = array();
        foreach ($data as $k => $v) {
            $tmp_filed[] = "`{$k}`";
            $tmp_value[] = "'{$v}'";
        }
        $tmp_filed = implode(",", $tmp_filed);
        $tmp_value = implode(",", $tmp_value);

        $db = DbConnecter::connectMysql('share_story');
        $sql = "INSERT INTO {$this->table}(
                    {$tmp_filed}
                ) VALUES({$tmp_value})";
        $st = $db->query($sql);
        unset($tmp_value, $tmp_filed);
        return $db->lastInsertId();
    }

    /**
     * 更新
     */
    public function update($data, $where = '')
    {
        //echo "update is run";
        if (!$data) {
            return false;
        }

        $tmp_data = array();
        foreach ($data as $k => $v) {
            $tmp_data[] = "`{$k}`='{$v}'";
        }
        $tmp_data = implode(",", $tmp_data);
        $set_str  = "SET {$tmp_data} ";

        $db = DbConnecter::connectMysql('share_story');
        $sql = "UPDATE {$this->table} {$set_str} where {$where}";
        $st = $db->query($sql);
        unset($tmp_data);
        // 清缓存
        $arr = explode("=", $where);
        if (isset($arr[1]) && $arr[1]) {
            $this->clearAlbumCache(intval($arr[1]));
        }
        return true;
    }

    /**
     * 获取字段信息
     */
    public function get_filed($where = '', $filed = '')
    {
        $db = DbConnecter::connectMysql('share_story');
        $sql = "select * from {$this->table}  where {$where}";
        $st = $db->query( $sql );
        $r = $st->fetchAll();
        if ($filed) {
            return $r[0][$filed];
        } else {
            return $r[0];
        }
    }

    /**
     * 获取列表
     */
    public function get_list($where = '', $limit = '', $filed = '')
    {
        $db = DbConnecter::connectMysql('share_story');
        if ($limit) {
            $sql = "select * from {$this->table}  where {$where} limit {$limit}";
        } else {
            $sql = "select * from {$this->table}  where {$where}";
        }
        $st = $db->query( $sql );
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r = $st->fetchAll();
        if ($filed) {
            $arr = array();
            foreach($r as $k => $v) {
                $arr[] = $v[$filed];
            }
            return $arr;
        } else {
            $albumlist = array();
            foreach ($r as $k => $v) {
                $albumlist[$k] = $this->format_to_api($v);
            }
            return $albumlist;
        }
    }

    /**
     * 获取列表
     */
    public function get_list_new($where = '', $filed = '*', $orderby='id desc', $limit = '20')
    {
        $db = DbConnecter::connectMysql('share_story');
        $sql = "select {$filed} from {$this->table}  where {$where} order by {$orderby} limit {$limit}";
        //var_dump($sql);
        $st = $db->query( $sql);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r = $st->fetchAll();
        return $r;
    }

    /**
     * 批量获取专辑信息
     */
    public function getListByIds($id = 0, $uid = 0)
    {
        return $this->getListByIdsNew($id, $uid);
        
        if (is_array($id)) {
            $idarr = $id;
        } else {
            $idarr = array($id);
        }
        // 初始化Redis
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);

        $albumlist = array();
        $fav = new Fav();
        $db = DbConnecter::connectMysql('share_story');
        foreach($idarr as $k => $v) {
            if (isset($albumlist[$v])) {
                continue;
            }
            // 读缓存
            $key = RedisKey::getAlbumInfoKey($v);
            $redisData = $redisobj->get($key);
            if ($redisData) {
                $r = json_decode($redisData, true);
            } else {
                $sql = "select * from {$this->table}  where `id`='{$v}' limit 1";
                $st = $db->query( $sql );
                $st->setFetchMode(PDO::FETCH_ASSOC);
                $r  = $st->fetchAll();
                $r  = array_pop($r);
                // 写入缓存
                $redisobj->setex($key, 604800, json_encode($r));
            }

            if ($r) {
                $r = $this->format_to_api($r);
                $favinfo = $fav->getUserFavInfoByAlbumId($uid, $r['id']);
                if ($favinfo) {
                    $r['fav'] = 1;
                } else {
                    $r['fav'] = 0;
                }
                $albumlist[$r['id']] = $r;
            }
        }
        return $albumlist;
    }
    
    // 优化getListByIds，批量获取专辑列表
    public function getListByIdsNew($albumids, $visituid = 0)
    {
        if (empty($albumids)) {
            return array();
        }
        if (!is_array($albumids)) {
            $albumids = array($albumids);
        }
        
        // 初始化Redis
        $keys = RedisKey::getAlbumInfoKeys($albumids);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->mget($keys);
        
        $cacheData = array();
        $cacheIds = array();
        if (is_array($redisData)){
            foreach ($redisData as $oneredisdata){
                if (empty($oneredisdata)) {
                    continue;
                }
                $oneredisdata = json_decode($oneredisdata, true);
                $cacheIds[] = $oneredisdata['id'];
                $cacheData[$oneredisdata['id']] = $oneredisdata;
            }
        } else {
            $redisData = array();
        }
        $dbIds = array_diff($albumids, $cacheIds);
        $dbData = array();
        $fav = new Fav();
        
        if(!empty($dbIds)) {
            $albumidstr = implode(",", $albumids);
            $db = DbConnecter::connectMysql($this->STORY_DB_INSTANCE);
            //$sql = "SELECT * FROM {$this->table} WHERE `id` IN ($albumidstr)";
            $sql = "select id,title,age_type,category_id,star_level,view_order,story_num,author,intro,cover_time,cover,age_str,status,add_time,update_time,min_age,max_age,serial_status,online_status from {$this->table}  where `id` IN ($albumidstr)";
            $st = $db->prepare($sql);
            $st->execute();
            $tmpDbData = $st->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            if (!empty($tmpDbData)) {
                foreach ($tmpDbData as $onedbdata){
                    $favinfo = array();
                    $onedbdata = $this->format_to_api($onedbdata);
                    $favinfo = $fav->getUserFavInfoByAlbumId($visituid, $onedbdata['id']);
                    if ($favinfo) {
                        $onedbdata['fav'] = 1;
                    } else {
                        $onedbdata['fav'] = 0;
                    }
                    $dbData[$onedbdata['id']] = $onedbdata;
                    $onekey = RedisKey::getAlbumInfoApiKey($onedbdata['id']);
                    $redisobj->setex($onekey, 86400, json_encode($onedbdata));
                }
            }
        }
        
        $data = array();
        foreach($albumids as $albumid) {
            if(in_array($albumid, $dbIds)) {
                $data[$albumid] = @$dbData[$albumid];
            } else {
                $data[$albumid] = $cacheData[$albumid];
            }
        }
        
        return $data;
    }
    

    /**
     * 获取用户专辑列表
     * @param I $uid
     * @param S $direction     up代表显示上边，down代表显示下边
     * @param I $startid       从某个id开始,默认为0表示从第一页获取
     * @param I $len           获取长度
     * @return array
     */
    public function getAlbumList( $direction = "down", $startid = 0, $len = 20, $uid = 0, $order_by = '')
    {
        if (empty($len)) {
            $len = 20;
        }
        // 读缓存
        $key = RedisKey::getAlbumListKey(func_get_args());
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        if ($redisData) {
            return json_decode($redisData, true);
        }

        $where = "`online_status`=1 AND `status` = '1'";
        if (!empty($startid)) {
            if ($direction == "up") {
                $where .= " AND `id` > '{$startid}' ";
            } else {
                $where .= " AND `id` < '{$startid}' ";
            }
        }
        if (!$order_by) {
            $order_by = 'ORDER BY `id` DESC';
        }
        
        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT * FROM {$this->table} WHERE {$where} {$order_by} LIMIT {$len}";
        $st = $db->prepare($sql);
        $st->execute();
        $res = $st->fetchAll(PDO::FETCH_ASSOC);
        $albumlist = array();
        foreach ($res as $k => $v) {
            $albumlist[$k] = $this->format_to_api($v);
        }
        // 缓存
        $redisobj->setex($key, 300, json_encode($albumlist));
        return $albumlist;
    }

    /**
     * 读取某个作者下的所有专辑
     * @param $author_id
     * @param $min_age
     * @param $max_age
     * @return array
     */
    public function getAuthorAlbums($author_id, $start_album_id, $limit = 20)
    {

        #TODO,此处可缓存作者album_id列表
        #TODO,在对每个album做缓存
        #TODO,SQL执行效率很低,需要加索引
        #TODO:没有对其做相关性排序,理想情况下，和作者越相关的排在前面，然后用户口碑越好的拍在前面（例如：阅读量越大）

        $albums = array();
        $where = " ( FIND_IN_SET({$author_id},`author_uid`) OR FIND_IN_SET({$author_id},`translator_uid`) OR FIND_IN_SET({$author_id},`illustrator_uid`) ) AND `online_status` = 1 AND `status` = 1";

        if ($start_album_id > 0) {
            $where .= " AND id < {$start_album_id} ";
        }

        $filed = "`id`,`title`,`cover`,`cover_time`,`min_age`,`max_age`,`intro`";
        $order_by = "`id` desc";
        $albums = $this->get_list_new($where, $filed, $order_by, $limit);
        return $albums;
    }

    /**
     * 读取某个主播下的所有专辑
     * @param $anchor_id
     * @param $start_album_id
     * @param int $min_age
     * @param int $max_age
     * @param int $limit
     * @return array
     */
    public function getAnchorAlbums($anchor_id, $start_album_id, $min_age = 0, $max_age = 0, $limit = 20)
    {
        $albums = array();
        $where = " FIND_IN_SET('{$anchor_id}',`anchor_uid`) AND `online_status` = 1 AND `status` = 1";

        //和getAgeLevelWithAlbums的年龄比较的规则相同
        if ($min_age == 0 && $max_age != 0 && $max_age != 14) {
            $where .= " AND `min_age` = 0 AND `max_age` >= {$max_age}";
        } elseif ($min_age != 0 && $max_age != 0) {
            $where .= " AND `min_age` >= {$min_age} AND `max_age` <= {$max_age}";
        }
        if ($start_album_id > 0) {
            $where .= " AND id < {$start_album_id} ";
        }

        $filed = "`id` ,`title`,`cover`,`cover_time`,`star_level`,`story_num`,`intro`,`min_age`,`max_age`";
        $order_by = "id desc";
        $albums = $this->get_list_new($where, $filed, $order_by, $limit);
        return $albums;
    }


    /**
     * 计算专辑所属年龄段的数量
     * @param $albums_arr
     */
    public function getAgeLevelWithAlbums($albums_arr)
    {

        $data = array();
        $data['total'] = 0;
        $data['items'] = array();
        $num_arr = array();

        if (!empty($albums_arr)) {

            foreach ($albums_arr as $album_item) {
                $min_age = $album_item['min_age'];
                $max_age = $album_item['max_age'];

                foreach ($this->AGE_LEVEL_ARR as $k => $age_level_item) {

                    /**
                     * 年龄比较:
                     *  min_age = 0 && max_age = 0  ==> 全部(所有的年龄段都出现该专辑)
                     *  min_age = 0 && max_age = 14 ==> 全部(所有的年龄段都出现该专辑)
                     *  max_age = 0 && max_age > 0  ==> max_age >= age_level_max_age
                     *  max_age > 0 && max_age > 0  ==> max_age <= age_level_max_age
                     */
                    if ((($min_age == 0 && $max_age == 0) || ($min_age == 0 && $max_age == 14)) && ($age_level_item['max_age'] == 14 && $age_level_item['min_age'] == 0)) {

                        $num_arr[$k] = isset($num_arr[$k]) ? $num_arr[$k] + 1 : 1;
                        //break;

                    } elseif ($min_age == 0 && $max_age > 0) {

                        if ($max_age >= $age_level_item['max_age'] || ($age_level_item['max_age'] == 14 && $age_level_item['min_age'] == 0)) {
                            $num_arr[$k] = isset($num_arr[$k]) ? $num_arr[$k] + 1 : 1;
                            //break;
                        }
                    } elseif ($min_age > 0 && $max_age > 0) {
                        if ($max_age <= $age_level_item['max_age'] && $min_age >= $age_level_item['min_age']) {
                            $num_arr[$k] = isset($num_arr[$k]) ? $num_arr[$k] + 1 : 1;
                            //break;
                        }
                    }
                }
            }
        }

        foreach ($num_arr as $k => $num_item) {

            $age_level_item = $this->AGE_LEVEL_ARR[$k];
            $age_level_item['album_num'] = $num_item;
            $data['items'][] = $age_level_item;
        }

        $data['total'] = count($data['items']);
        return $data;
    }

    public function getAgeLevelWithAlbumsFormat($age_level_album_num_arr, $selectedIndex = 0)
    {

        $data_format = array();
        $data_format['total'] = 0;
        $data_format['items'] = array();
        if (isset($age_level_album_num_arr['total']) && intval($age_level_album_num_arr['total']) > 0) {

            $data_format['total'] = $age_level_album_num_arr['total'];
            if (is_array($age_level_album_num_arr['items']) && !empty($age_level_album_num_arr['items'])) {

                $arr = array();
                foreach ($age_level_album_num_arr['items'] as $key => $item) {

                    if ($item['min_age'] == 0 && $item['max_age'] == 14) {
                        $arr['age_level_str'] = "全部";
                    } else {
                        $arr['age_level_str'] = sprintf("%d-%d岁", $item['min_age'], $item['max_age']);
                    }
                    $arr['min_age'] = $item['min_age'];
                    $arr['max_age'] = $item['max_age'];
                    $arr['album_num'] = $item['album_num'];
                    if ($selectedIndex == $key) {
                        $arr['selected'] = 1;
                    } else {
                        $arr['selected'] = 0;
                    }
                    $data_format['items'][] = $arr;
                }
            }
        }
        return $data_format;
    }

    public function getAgeLevelStr($min_age, $max_age)
    {

        $ageLevelStr = "";
        if ($min_age >= $this->MIN_AGE && $max_age <= $this->MAX_AGE && $max_age != $min_age) {
            $ageLevelStr = sprintf("%s-%s", $min_age, $max_age);
        } else {
            $ageLevelStr = sprintf("%s-%s", $this->MIN_AGE, $this->MAX_AGE);
        }
        return $ageLevelStr;
    }



    /**
     * 获取年龄类型
     */
    public function get_age_type($age_str = '')
    {
        $age_type = 0;

        if (!$age_str) {
            return 0;
        }
        $age_str_arr = $this->get_age_arr($age_str);
        $age = intval($age_str_arr[0]);
        // 没有取到年龄处理
        if (!isset($age)) {
            return 0;
        }
        if ($age >=0 && $age <= 2) {
            $age_type = 1;
        } else if ($age >=3 && $age <= 6) {
            $age_type = 2;
        } else if ($age >=7 && $age <= 10) {
            $age_type = 3;
        }
        return $age_type;
    }

    /**
     * 根据年龄字符串返回数组$arr[0]=album_min_age,$arr[1]=album_max_age
     * @param string $age_str
     * @return array
     */
    public function get_age_arr($age_str = '')
    {

        $age_str_arr = array(0, 14);
        $min_age_exist = false;
        $max_age_exist = false;
        if (!empty($age_str)) {

            if (stristr($age_str, '+')) {
                $min_age_exist = true;
            } else if (stristr($age_str, '-')) {
                $max_age_exist = true;
            }

            if (stristr($age_str, 'P')) {
                $tmp_str = str_replace(array('P', 'p', '-', '+'), array('', '', '', ''), $age_str);
            } else if (stristr($age_str, '岁')) {
                $tmp_str = str_replace('岁', '', $age_str);
            }
            $age_str_arr = explode('-', $tmp_str);

            if (count($age_str_arr) == 1) {
                $album_min_age = 0;
                $album_max_age = 14;

                if ($min_age_exist) {
                    $album_min_age = $age_str_arr[0];
                    $album_max_age_arr = array(2, 6, 10, 14);
                    foreach ($album_max_age_arr as $value) {
                        if ($album_min_age < $value) {
                            $album_max_age = $value;
                            break;
                        }
                    }
                } else if ($max_age_exist) {
                    $album_max_age = $age_str_arr[0];
                    $album_min_age_arr = array(0, 3, 7, 11);
                    foreach ($album_min_age_arr as $value) {
                        if ($album_max_age > $value) {
                            $album_min_age = $value;
                        }
                    }
                }

                $age_str_arr[0] = $album_min_age;
                $age_str_arr[1] = $album_max_age;
            }
        }
        return $age_str_arr;
    }

    /**
     * 获取封面信息
     */
    public function get_album_info($album_id = 0, $filed = '')
    {
        if (!$album_id) {
            return array();
        }
        // 读缓存
        $key = RedisKey::getAlbumInfoKey($album_id);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisData = $redisobj->get($key);
        // 是否读到
        if ($redisData) {
            $r = json_decode($redisData, true);
        } else {

            $where = "`id`={$album_id}";
            $sql = "select * from {$this->table}  where {$where} limit 1";

            $db = DbConnecter::connectMysql('share_story');
            $st = $db->query( $sql );
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $r  = $st->fetch();
            $redisobj->setex($key, 604800, json_encode($r));
        }
        
        if ($filed) {
            if (isset($r[$filed])) {
                return $r[$filed];
            } else {
                return '';
            }
        }
        return $this->format_to_api($r);
    }

    /**
     * 格式化播放量
     * 示例:
     *      100000 返回 10万
     * @param $listen_num
     */
    public function format_album_listen_num($listen_num)
    {

        $listen_num = intval($listen_num);
        $listen_num_str = (string)$listen_num;

        if ($listen_num >= 100000) {
            $listen_num_str = substr($listen_num_str, 0, -4) . "万";
        }
        return $listen_num_str;
    }

    /**
     * 获取某字段值
     */
    public function get_filed_value($field = 's_cover', $value = '', $need_filed = '')
    {
        if (!$field) {
            return array();
        }
        $where = "`{$field}`='{$value}' and {$need_filed} !=''";
        $sql = "select * from {$this->table}  where {$where} limit 1";

        $db = DbConnecter::connectMysql('share_story');
        $st = $db->query( $sql );
        if ($st) {
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $r  = $st->fetchAll();
            $r  = array_pop($r);
            if (isset($r[$need_filed]) && $r[$need_filed]) {
                return $r[$need_filed];
            }
        }
        return '';
    }

    /**
     * 更新故事数量
     */
    public function update_story_num($album_id = 0)
    {
        if (!$album_id) {
            return false;
        }
        $story = new Story();
        $story_num = $story->get_total(" `album_id`={$album_id} and `status` = 1 ");
        $this->update(array('story_num' => $story_num), " `id`={$album_id} ");
    }

    // 格式化成接口数据
    public function format_to_api($alubm_info = array())
    {
        /* if (empty($alubm_info['cover'])) {
            $alubm_info['cover'] = $alubm_info['s_cover'];
        } */
        return $alubm_info;
    }

    public function clearAlbumCache($albumId)
    {
        if (!$albumId) {
            return false;
        }
        $albumIdKey = RedisKey::getAlbumInfoKey($albumId);
        $redisobj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $redisobj->delete($albumIdKey);
        return true;
    }

    public function coverNotUploadOssCount()
    {

        $count = $this->get_total("s_cover!='' AND cover='' AND LOCATE ('default/bg_player.jpg',`s_cover`) = 0 AND LOCATE ('default/sound.jpg',`s_cover`) = 0 AND `status` = 1");
        return $count;
    }
    

    public function getAlbumListByAge($min_age,$max_age,$start_album_id=0,$select='a.id,a.title,a.cover,a.cover_time,a_t.albumlistennum as listen_num',$offset=0,$perPage=4){
        $where = ' online_status=1 ';
        //和getAgeLevelWithAlbums的年龄比较的规则相同
        if ($min_age == 0 && $max_age != 0 && $max_age != 14) {
            $where .= " AND `min_age` = 0 AND `max_age` >= {$max_age}";
        } elseif ($min_age != 0 && $max_age != 0) {
            $where .= " AND `min_age` >= {$min_age} AND `max_age` <= {$max_age}";
        }

        if ($start_album_id > 0) {
            $where .= " AND a.id >= {$start_album_id} ";
        }

        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT {$select}
                FROM `album` AS a LEFT JOIN `album_tag_relation` AS a_t ON a.id=a_t.albumid 
                WHERE {$where} GROUP BY a.id
                ORDER BY a_t.`albumlistennum` DESC LIMIT {$offset}, {$perPage}";
        $st = $db->prepare($sql);
        $st->execute();
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        return $list;
    }
}