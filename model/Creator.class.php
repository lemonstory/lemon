<?php

/**
 * Class Creator
 *
 * 将原著,插画,译者,主播.. 等角色统称为创作者
 *
 */
class Creator extends ModelBase
{
    public $CREATOR_DB_INSTANCE = 'share_main';
    public $CREATOR_TABLE_NAME = 'creator';
    public $CACHE_INSTANCE = 'cache';

    public function getCreatorInfo($uid)
    {
        if (empty($uid)) {
            return null;
        }
        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        $sql = "SELECT * FROM {$this->CREATOR_TABLE_NAME} WHERE `uid` = {$uid}";
        $st = $db->prepare($sql);
        $st->execute();
        $creatorInfo = $st->fetch();
        return $creatorInfo;
    }

    /**
     * 根据名称获取作者uid
     * @param $name
     * @return int|false
     */
    public function getCreatorUid($name)
    {

        $sso_obj = new Sso();
        $user_obj = new User();
        $uid_arr = array();
        $uid = false;
        $db = DbConnecter::connectMysql($sso_obj->PASSPORT_DB_INSTANCE);
        $sql = "SELECT `uid` FROM {$sso_obj->PASSPORT_TABLE_NAME} WHERE `username` LIKE '%{$name}%'";
        $st = $db->prepare($sql);
        $st->execute();
        $sso_uid_arr = $st->fetchAll(PDO::FETCH_ASSOC);

        if (is_array($sso_uid_arr) && !empty($sso_uid_arr)) {

            $sso_uid_str = "";
            foreach ($sso_uid_arr as $k => $sso_uid_item) {

                $sso_uid_str .= ", " . $sso_uid_item['uid'];
            }
            $sso_uid_str = trim($sso_uid_str, ",");
            $sql = "SELECT * FROM {$user_obj->USER_INFO_TABLE_NAME} WHERE `uid` IN ($sso_uid_str) AND `indentity` = {$user_obj->IDENTITY_SYSTEM_USER} AND `status` = 1";
            $st = $db->prepare($sql);
            $st->execute();
            $user_arr = $st->fetchAll(PDO::FETCH_ASSOC);
            unset($st);
            unset($db);
            if (is_array($user_arr) && !empty($user_arr)) {

                foreach ($user_arr as $k => $user) {
                    $uid_arr[] = $user['uid'];
                }
            }
        }

        if (!empty($uid_arr)) {

            //TODO:同名的作者处理不严谨
            if (count($uid_arr) > 1) {
                $content = sprintf("作者名称为: [%s] 共有 %d 个\r\n", $name, count($uid_arr));
                //echo $content;
            }
            $uid = intval($uid_arr[0]);
        }


        return $uid;
    }


    /**
     * @param $uname 名称
     * @param $intro 简介
     * @param $card 认证
     */
    public function addCreator($name, $intro, $card, $is_author, $is_translator, $is_illustrator, $is_anchor)
    {
        $uid = false;
        $user = new User();
        //可以重名
        //$NicknameMd5Obj = new NicknameMd5();
        //$is_exist = $NicknameMd5Obj->checkNameIsExist($name);
        //if (!$is_exist) {
        //add user
        $sso = new Sso();
        $password = md5('AU' . time());
        $user_type = $user->TYPE_SYS;
        //$indentity = $user->IDENTITY_AUTHOR;
        $uid = $sso->userReg($name, $name, $password, $user_type, $user->IDENTITY_SYSTEM_USER);
        if ($uid) {
            //add user Creator info
            //$uid = 14852;
            $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
            $sql = "insert into {$this->CREATOR_TABLE_NAME} (uid,intro,card,is_author,is_translator,is_illustrator,is_anchor) values (?,?,?,?,?,?,?)";
            $st = $db->prepare($sql);
            $ret = $st->execute(array($uid, $intro, $card, $is_author, $is_translator, $is_illustrator, $is_anchor));
            if (!$ret) {
                $log = sprintf("[{$name}]用户在creator_info表添加失败\r\n");
                echo $log;
            } else {
                $log = sprintf("[{$name}]用户在creator_info表添加成功\r\n");
                echo $log;
            }
        } else {
            $log = sprintf("[{$name}]用户在SSO注册失败\r\n");
            echo $log;
        }

//        } else {
//            $log = sprintf("[{$name}]已经在用户表中存在\r\n");
//            echo $log;
//        }

        return $uid;
    }

    //https://github.com/lemonstory/lemondocs/blob/master/docs/%E6%8E%A5%E5%8F%A3%E6%96%87%E6%A1%A3/%E7%94%A8%E6%88%B7%E5%A4%B4%E5%83%8F%E8%A7%84%E5%88%99.txt
    //更改作者头像
    //User->setUserinfo


    /**
     * 声音内容添加作者信息(多个)
     * @param $author_id
     * @param $story_id
     * @return bool
     */
    public function addAuthorsInStory($authors_id, $story_id)
    {

        $is_add_success = false;
        $story = new Story();
        //UPDATE `story` SET `author_id`=concat_ws(',',`author_id`,6 ) WHERE `id` = 491150
        $data = array('author_id' => "concat_ws(',',`author_id`,{$authors_id} )");
        $where = "`id`={$story_id}";
        $is_add_success = $story->update($data, $where);
        return $is_add_success;
    }


    /**
     * 读取系统内所有的作者[原著]
     * @return array
     */
    public function getAllAuthors($start_author_id, $limit = 20)
    {
        $whereArr = array(
            "is_author" => 1,
            "album_num" => 0,
            "user_info_status" => 1,
        );

        if ($start_author_id > 0) {
            $whereArr["start_uid_id"] = $start_author_id;
        }

        //按照View order排序
        $order = "{$this->CREATOR_TABLE_NAME}.view_order desc";
        //选择输出
        $select = "`{$this->CREATOR_TABLE_NAME}`.`uid` as uid,
                  `{$this->CREATOR_TABLE_NAME}`.`album_num` as album_num,
                  `{$this->CREATOR_TABLE_NAME}`.`card` as card,
                  `{$this->CREATOR_TABLE_NAME}`.`intro` as intro,
                  `user_info`.`nickname` as nickname, 
                  `user_info`.`avatartime` as avatartime ";
        $arr = $this->getCreatorList($whereArr, 1, $limit, $order, $select);
        return $arr;
    }

    /**
     * 读取热门作者[原著]
     * @return array
     */
    public function getHotAuthors($limit = 8)
    {
        $whereArr = array(
            "is_author" => 1,
            "album_num" => 0,
            "user_info_status" => 1,
        );

        //按照View order排序
        $order = "{$this->CREATOR_TABLE_NAME}.view_order desc";
        //选择输出
        $select = "`{$this->CREATOR_TABLE_NAME}`.`uid` as uid,
                  `user_info`.`nickname` as nickname, 
                  `user_info`.`avatartime` as avatartime ";
        $arr = $this->getCreatorList($whereArr, 1, $limit, $order, $select);
        return $arr;
    }

    /**
     * 读取系统内所有的主播
     * @return array
     */
    public function getAllAnchors($start_anchor_id, $limit = 20)
    {

        $whereArr = array(
            "is_anchor" => 1,
            "album_num" => 0,
            "user_info_status" => 1,
        );

        if ($start_anchor_id > 0) {
            $whereArr["start_uid_id"] = $start_anchor_id;
        }

        $arr = $this->getCreatorList($whereArr, 1, $limit);
        return $arr;
    }

    /**
     * 获取所有作者总数
     */
    public function getTotalCreator()
    {
        $whereStr = ' 1 ';
        $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`album_num` > 0";
        $whereStr .= " and `user_info`.`status` = 1";
        $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`is_author` = 1";

        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        $sql = "SELECT COUNT(*) total
                from `{$this->CREATOR_TABLE_NAME}` LEFT JOIN `user_info` ON `{$this->CREATOR_TABLE_NAME}`.`uid` = `user_info`.`uid`  
                WHERE {$whereStr}";
        $st = $db->prepare($sql);
        $st->execute();
        $m = $st->fetch(PDO::FETCH_OBJ);
        return $m->total;
    }

    public function getCreatorList($whereArr = array(), $currentPage = 1, $perPage = 50, $order = '', $select = '')
    {

        if (empty($currentPage) || $currentPage <= 0) {
            $currentPage = 1;
        }

        if (empty($perPage) || $perPage <= 0) {
            $perPage = 50;
        }

        $whereStr = ' 1 ';
        if (!empty($whereArr)) {

            foreach ($whereArr as $key => $val) {
                if ($key == 'nickname') {
                    $whereStr .= " and `user_info`.`nickname` like :{$key}";
                } elseif ($key == 'creator_uid') {
                    $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`uid` = :{$key}";
                } elseif ($key == 'start_uid_id') {
                    $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`uid` > :{$key}";
                } elseif ($key == 'album_num') {
                    $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`album_num` > :{$key}";
                } elseif ($key == 'user_info_status') {
                    $whereStr .= " and `user_info`.`status` = :{$key}";
                } elseif ($key == 'is_author') {
                    $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`is_author` = :{$key}";
                } elseif ($key == 'online_status') {
                    $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`online_status` = :{$key}";
                } else {
                    $whereStr .= " and `{$key}` = :{$key}";
                }
            }
        }

        $offset = ($currentPage - 1) * $perPage;

        if ($order) {
            $order = $order . ', ';
        }
        if (empty($select)) {
            $select = "`{$this->CREATOR_TABLE_NAME}`.`uid` as uid,
                  `{$this->CREATOR_TABLE_NAME}`.`album_num` as album_num,
                  `{$this->CREATOR_TABLE_NAME}`.`listen_num` as listen_num,
                  `{$this->CREATOR_TABLE_NAME}`.`add_time` as add_time,
                  `{$this->CREATOR_TABLE_NAME}`.`view_order` as view_order,
                  `{$this->CREATOR_TABLE_NAME}`.`online_status` as online_status,
                  `user_info`.`nickname` as nickname, 
                  `user_info`.`avatartime` as avatartime ";
        }

        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        $sql = "SELECT {$select}
                from `{$this->CREATOR_TABLE_NAME}` LEFT JOIN `user_info` ON `{$this->CREATOR_TABLE_NAME}`.`uid` = `user_info`.`uid`  
                WHERE {$whereStr} ORDER BY {$order}`{$this->CREATOR_TABLE_NAME}`.`album_num` DESC LIMIT {$offset}, {$perPage}";
        $st = $db->prepare($sql);
        $st->execute($whereArr);
        $arr = $st->fetchAll(PDO::FETCH_ASSOC);

        //头像处理
        if (is_array($arr) && !empty($arr)) {
            foreach ($arr as $key => $item) {
                $uid = $item['uid'];
                $avatartime = $item['avatartime'];
                $arr[$key]['avatar'] = sprintf("http://a.xiaoningmeng.net/avatar/%s/%s/180", $uid, $avatartime);
                unset($arr[$key]['avatartime']);
                //增加wiki_URL
                $arr[$key]['wiki_url'] = sprintf("http://www.xiaoningmeng.net/author/detail.php?uid=%s&author=%s", $uid,
                    $item['nickname']);
            }
        }

        return $arr;
    }

    //读取某个作者下的所有专辑
    //Album->getAuthorAlbums

    public function getCreatorAgeLevelAlbumsNum($creator_uid)
    {

        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        $sql = "SELECT `age_level_album_num` FROM {$this->CREATOR_TABLE_NAME}  where `uid` = {$creator_uid}";
        $st = $db->query($sql);
        $r = $st->fetchAll();
        $age_level_album_num = $r[0]['age_level_album_num'];
        if (!empty($age_level_album_num)) {
            $age_level_album_num = json_decode($age_level_album_num, true);
        }
        return $age_level_album_num;
    }


    public function getCreatorCount($whereArr = array())
    {
        $whereStr = ' 1 ';
        if (!empty($whereArr)) {

            foreach ($whereArr as $key => $val) {
                if ($key == 'nickname') {
                    $whereStr .= " and `user_info`.`nickname` like :{$key}";
                } else {
                    if ($key == 'creator_uid') {
                        $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`uid` = :{$key}";
                    } else {
                        if ($key == 'start_uid_id') {
                            $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`uid` > :{$key}";
                        } else {
                            if ($key == 'album_num') {
                                $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`album_num` > :{$key}";
                            } else {
                                if ($key == 'user_info_status') {
                                    $whereStr .= " and `user_info`.`status` = :{$key}";
                                } else {
                                    if ($key == 'is_author') {
                                        $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`is_author` = :{$key}";
                                    } else {
                                        if ($key == 'online_status') {
                                            $whereStr .= " and `{$this->CREATOR_TABLE_NAME}`.`online_status` = :{$key}";
                                        } else {
                                            $whereStr .= " and `{$key}` = :{$key}";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        $sql = "SELECT COUNT(*) as count
                from `{$this->CREATOR_TABLE_NAME}` LEFT JOIN `user_info` ON `{$this->CREATOR_TABLE_NAME}`.`uid` = `user_info`.`uid`  
                WHERE {$whereStr}";

        $st = $db->prepare($sql);
        $st->execute($whereArr);
        $count = $st->fetch(PDO::FETCH_COLUMN);
        return $count;
    }

    /**
     * 更新
     */
    public function update($data, $where = '')
    {
        if (!$data) {
            return false;
        }

        $tmp_data = array();
        foreach ($data as $k => $v) {
            $tmp_data[] = "`{$k}`='{$v}'";
        }
        $tmp_data = implode(",", $tmp_data);
        $set_str = "SET {$tmp_data} ";

        static $db;
        if (!isset($db)) {
            $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        }
        $sql = "UPDATE {$this->CREATOR_TABLE_NAME} {$set_str} where {$where}";
        $st = $db->query($sql);
        unset($tmp_data);
        unset($st);
        #TODO清缓存
//        $arr = explode("=", $where);
//        if (isset($arr[1]) && $arr[1]) {
//            $this->clearStoryCache(intval($arr[1]));
//        }
        //$db = null;
        return true;
    }

}