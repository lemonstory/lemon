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


    public function getAlbumListByTagId($where = array(), $currentPage = 1, $perPage = 4)
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
        $sql = "SELECT a.id,a.title,a.cover,a_t.albumlistennum as listen_num,a.intro,a.link_url
                FROM `album_tag_relation` AS a_t LEFT JOIN `album` AS a ON a_t.albumid=a.id {$whereStr} 
                ORDER BY `id` DESC LIMIT {$offset}, {$perPage}";

        $st = $db->prepare($sql);
        $st->execute($where);
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        return $list;
    }
}