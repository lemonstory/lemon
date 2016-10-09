<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2016/9/24
 * Time: 下午3:41
 */
class AlbumTagRelation extends ModelBase
{
    private $table = 'album_tag_relation';


    public function getAlbumList($where = array(), $currentPage = 1, $perPage = 4)
    {

        if ($where) {
            $whereStr = ' WHERE 1 ';
            foreach ($where as $key=>$val){
                $whereStr .= " and `{$key}`=:{$key}";
            }
        } else {
            $whereStr = '';
        }
        $offset = ($currentPage - 1) * $perPage;

        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT a.id,a.title,a.cover,a_t.albumlistennum as listen_num,a.intro,a_t.tagid
                FROM `album_tag_relation` AS a_t LEFT JOIN `album` AS a ON a_t.albumid=a.id {$whereStr} 
                ORDER BY `id` DESC LIMIT {$offset}, {$perPage}";

        $st = $db->prepare($sql);
        $st->execute($where);
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        return $list;
    }

    public function getTagListByAlbumId($albumId, $currentPage = 1, $perPage = 4)
    {
        $offset = ($currentPage - 1) * $perPage;

        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT a_t.albumlistennum as listen_num,a_t.tagid
                FROM `album_tag_relation` AS a_t  WHERE `albumid`=:albumid 
                ORDER BY `id` DESC LIMIT {$offset}, {$perPage}";

        $st = $db->prepare($sql);
        $st->execute(array('albumid'=>$albumId));
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        return $list;
    }

    public function getAlbumListByAge($min_age, $max_age,$start_album_id, $currentPage = 1, $perPage = 4)
    {
        $where = '1';
        if ($min_age == 0 && $max_age != 0 && $max_age != 14) {
            $where .= " AND `min_age` = 0 AND `max_age` >= {$max_age}";
        } elseif ($min_age != 0 && $max_age != 0) {
            $where .= " AND `max_age` <= {$max_age}";
        }
        if ($start_album_id > 0) {
            $where .= " AND a.id < {$start_album_id} ";
        }

        $offset = ($currentPage - 1) * $perPage;

        $db = DbConnecter::connectMysql('share_story');
        $sql = "SELECT a.id,a.title,a.cover,a_t.albumlistennum as listen_num,a.intro
                FROM `album_tag_relation` AS a_t LEFT JOIN `album` AS a ON a_t.albumid=a.id 
                WHERE {$where} 
                ORDER BY `id` DESC LIMIT {$offset}, {$perPage}";

        $st = $db->prepare($sql);
        $st->execute();
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        return $list;
    }
}