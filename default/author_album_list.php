<?php
/**
 * 作者的专辑
 * Date: 16/9/27
 * Time: 下午6:59
 */

include_once '../controller.php';

class author_album_list extends controller
{
    public function action()
    {
        $authorId = $this->getRequest('author_id', '');
        $startAlbumId = $this->getRequest('start_album_id', '0');
        $minAge = $this->getRequest('min_age', '0');
        $maxAge = $this->getRequest('max_age', '0');
        $len = $this->getRequest('len', '20');

        if (!empty($authorId)) {

            $ret = array();
            $album = new Album();
            $author = new Author();
            $albums = $album->getAuthorAlbums($authorId, $startAlbumId, $minAge, $maxAge, $len);
            $age_level_albums_num = $author->getAuthorAgeLevelAlbumsNum($authorId);
            $age_level_albums_num = $album->getAgeLevelWithAlbumsFormat($age_level_albums_num);

            $ret['age_level'] = $age_level_albums_num;
            $ret['total'] = count($albums);
            $ret['items'] = $albums;
            $this->showSuccJson($ret);

        } else {
            $this->showErrorJson(ErrorConf::paramError());
        }
    }
}

new author_album_list();