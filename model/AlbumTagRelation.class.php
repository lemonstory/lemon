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

    public function getAlbumListByTagId($tag_id=0, $currentPage = 1, $perPage = 4)
    {
        $where = ' online_status=1 ';

        if ($tag_id >0) {
            $where .= " AND a_t.tagid = {$tag_id} ";
        }

        $offset = ($currentPage - 1) * $perPage;

        $db = DbConnecter::connectMysql('share_story');
        $select = 'a.id,a.title,a.intro,a.cover,a.cover_time,a_t.albumlistennum as listen_num';
        $sql = "SELECT {$select}
                FROM `album_tag_relation` AS a_t LEFT JOIN `album` AS a ON a_t.albumid=a.id 
                WHERE {$where} 
                ORDER BY `id` DESC LIMIT {$offset}, {$perPage}";

        $st = $db->prepare($sql);
        $st->execute();
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        return $list;
    }

    public function getAlbumListByAge($min_age, $max_age,$tag_id=0, $start_album_id=0, $currentPage = 1, $perPage = 4)
    {
        $where = ' online_status=1 ';
        if ($min_age == 0 && $max_age != 0 && $max_age != 14) {
            $where .= " AND `min_age` = 0 AND `max_age` >= {$max_age}";
        } elseif ($min_age != 0 && $max_age != 0) {
            $where .= " AND `min_age` >= {$min_age} AND `max_age` <= {$max_age}";
        }
        if ($tag_id >0) {
            $where .= " AND a_t.tagid = {$tag_id} ";
        }
        if ($start_album_id > 0) {
            $where .= " AND a.id > {$start_album_id} ";
        }

        $offset = 0;
        if ($currentPage > 0) {
            $offset = ($currentPage - 1) * $perPage;
        }


        $db = DbConnecter::connectMysql('share_story');
        $select = 'a.id,a.title,a.intro,a.category_id,a.star_level,a.view_order,a.story_num,a.author,
        a.age_str,a.cover,a.cover_time,a_t.albumfavnum as fav,a_t.albumlistennum as listen_num';
        $sql = "SELECT {$select}
                FROM `album_tag_relation` AS a_t LEFT JOIN `album` AS a ON a_t.albumid=a.id 
                WHERE {$where} 
                ORDER BY `id` DESC LIMIT {$offset}, {$perPage}";

        $st = $db->prepare($sql);
        $st->execute();
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        return $list;
    }
}