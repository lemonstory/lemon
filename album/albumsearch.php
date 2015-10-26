<?php
include_once '../controller.php';
class albumsearch extends controller
{
    public function action()
    {
        die();
        $searchcontent = $this->getRequest("searchcontent");
        if (empty($searchcontent)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        // add search count
        $searchcountobj = new SearchCount();
        $searchcountobj->addSearchContentCount($searchcontent);
        
        $searchobj = new OpenSearch();
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
            $info = $value;
            $info['cover'] = $aliossobj->getImageUrlNg($value['cover']);
            $searchlist[] = $info;
        }
        $this->showSuccJson($searchlist);
    }
}
new albumsearch();