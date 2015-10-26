<?php
include_once '../controller.php';
class albumstorysearch extends controller
{
    public function action()
    {
        $searchcontent = $this->getRequest("searchcontent");
        $len = $this->getRequest("len", 50);
        if (empty($searchcontent)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        // add search count
        $searchcountobj = new SearchCount();
        $searchcountobj->addSearchContentCount($searchcontent);
        
        $storyids = array();
        $storycount = 0;
        $searchobj = new OpenSearch();
        // 搜索故事
        $storysearch = $searchobj->searchStory($searchcontent, $len);
        if (!empty($storysearch)) {
            $storyids = $storysearch['storyids'];
            $storycount = $storysearch['total'];
        }
        
        // 搜索专辑
        $albumids = array();
        $albumcount = 0;
        $albumsearch = $searchobj->searchAlbum($searchcontent, $len);
        if (!empty($albumsearch)) {
            $albumids = $albumsearch['albumids'];
            $albumcount = count($albumids);
        }
        
        
        $aliossobj = new AliOss();
        $searchlist = array();
        
        $storylist = array();
        if (!empty($storyids)) {
            $storyobj = new Story();
            $storyres = $storyobj->getListByIds($storyids);
            if (!empty($storyres)) {
                foreach ($storyres as $value) {
                    $info = $value;
                    $info['cover'] = $aliossobj->getImageUrlNg($value['cover']);
                    $storylist[] = $info;
                }
            }
        }
        
        $albumlist = array();
        if (!empty($albumids)) {
            // 专辑列表
            $albumobj = new Album();
            $albumres = $albumobj->getListByIds($albumids);
            if (!empty($albumres)) {
                foreach ($albumres as $value) {
                    $info = $value;
                    $info['cover'] = $aliossobj->getImageUrlNg($value['cover']);
                    $albumlist[] = $info;
                }
            }
        }
        
        $searchlist = array(
                "storylist" => $storylist,
                'storycount' => $storycount,
                'albumlist' => $albumlist,
                'albumcount' => $albumcount
                );
        $this->showSuccJson($searchlist);
    }
}
new albumstorysearch();