<?php
include_once '../controller.php';
class albumsearch extends controller
{
    public function action()
    {
        $searchcontent = $this->getRequest("searchcontent");
        $searchobj = new OpenSearch();
        //$searchobj->addAlbumToSearch("4", "storytitle4", "3", "otheralbum3", "ggg", time());
        $albumids = $searchobj->searchAlbum($searchcontent);
        if (empty($albumids)) {
            $this->showErrorJson(ErrorConf::searchAlbumIsEmpty());
        }
        
        $albumobj = new Album();
        $albumlist = $albumobj->getListByIds($albumids);
        if (empty($albumlist)) {
            $this->showErrorJson(ErrorConf::albumInfoIsEmpty());
        }
        
        $aliossobj = new AliOss();
        $searchlist = array();
        foreach ($albumlist as $value) {
            $info['id'] = $value['id'];
            $info['title'] = $value['title'];
            $info['cover'] = $aliossobj->getImageUrlNg($value['cover']);
            $searchlist[] = $info;
        }
        $this->showSuccJson($searchlist);
    }
}
new albumsearch();