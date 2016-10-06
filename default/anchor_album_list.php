<?php
/**
 * 主播的专辑
 *
 * Date: 16/9/30
 * Time: 下午6:36
 */

include_once '../controller.php';

class anchor_album_list extends controller
{
    public function action()
    {
        $anchorId = $this->getRequest('anchor_id', '');
        $startAlbumId = $this->getRequest('start_album_id', '0');
        $minAge = $this->getRequest('min_age', '0');
        $maxAge = $this->getRequest('max_age', '0');
        $len = $this->getRequest('len', '20');

        if (!empty($anchorId)) {

            $ret = array();
            $album = new Album();
            $creator = new Creator();
            $albums = $album->getAnchorAlbums($anchorId, $startAlbumId, $minAge, $maxAge, $len);
            $age_level_albums_num = $creator->getCreatorAgeLevelAlbumsNum($anchorId);
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

new anchor_album_list();