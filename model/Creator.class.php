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
                echo $content;
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

        $where = " `{$this->CREATOR_TABLE_NAME}`.`is_author` = 1 AND `{$this->CREATOR_TABLE_NAME}`.`album_num` > 0 AND `user_info`.`status` =1";
        if ($start_author_id > 0) {
            $where .= " AND `{$this->CREATOR_TABLE_NAME}`.`uid` > {$start_author_id}";
        }

        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        $sql = "SELECT `{$this->CREATOR_TABLE_NAME}`.`uid` as uid,`{$this->CREATOR_TABLE_NAME}`.`album_num` as album_num,`{$this->CREATOR_TABLE_NAME}`.`listen_num` as listen_num,`user_info`.`nickname` as nickname, `user_info`.`avatartime` as avatartime 
                from `{$this->CREATOR_TABLE_NAME}` LEFT JOIN `user_info` ON `{$this->CREATOR_TABLE_NAME}`.`uid` = `user_info`.`uid`  
                WHERE {$where} ORDER BY `{$this->CREATOR_TABLE_NAME}`.`uid` ASC  limit {$limit}";
        $st = $db->query($sql);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $ret = $st->fetchAll();
        return $ret;
    }

    /**
     * 读取系统内所有的主播
     * @return array
     */
    public function getAllAnchors($start_anchor_id, $limit = 20)
    {

        $where = " `{$this->CREATOR_TABLE_NAME}`.`is_anchor` = 1 AND `{$this->CREATOR_TABLE_NAME}`.`album_num` > 0 AND `user_info`.`status` =1";
        if ($start_anchor_id > 0) {
            $where .= " AND `{$this->CREATOR_TABLE_NAME}`.`uid` > {$start_anchor_id}";
        }

        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        $sql = "SELECT `{$this->CREATOR_TABLE_NAME}`.`uid` as uid,`{$this->CREATOR_TABLE_NAME}`.`album_num` as album_num,`{$this->CREATOR_TABLE_NAME}`.`listen_num` as listen_num,`user_info`.`nickname` as nickname, `user_info`.`avatartime` as avatartime 
                from `{$this->CREATOR_TABLE_NAME}` LEFT JOIN `user_info` ON `{$this->CREATOR_TABLE_NAME}`.`uid` = `user_info`.`uid`  
                WHERE {$where} ORDER BY `{$this->CREATOR_TABLE_NAME}`.`uid` ASC  limit {$limit}";
        $st = $db->query($sql);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $ret = $st->fetchAll();
        return $ret;
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


    /**
     * 获取总数
     */
    public function get_total($where = '')
    {
        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        $sql = "select count(*) as count from {$this->CREATOR_TABLE_NAME}  where {$where}";
        $st = $db->query($sql);
        $r = $st->fetchAll();
        return $r[0]['count'];
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

    /**
     * 获取列表
     */
    public function get_list($where = '', $limit = '', $filed = '', $orderby = '')
    {
        $db = DbConnecter::connectMysql($this->CREATOR_DB_INSTANCE);
        if ($limit) {
            $sql = "select * from {$this->CREATOR_TABLE_NAME}  where {$where} {$orderby} limit {$limit} ";
        } else {
            $sql = "select * from {$this->CREATOR_TABLE_NAME}  where {$where} {$orderby} ";
        }
        $st = $db->query($sql);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $r = $st->fetchAll();
        if ($filed) {
            $arr = array();
            foreach ($r as $k => $v) {
                $arr[] = $v[$filed];
            }
            return $arr;
        } else {
            return $r;
        }
    }
}