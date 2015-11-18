<?php
include_once '../controller.php';
class albumstorysearch extends controller
{
    public function action()
    {
        $searchtype = $this->getRequest("searchtype", "");
        $searchcontent = $this->getRequest("searchcontent");
        $page = $this->getRequest("page", 1);
        $len = $this->getRequest("len", 10);
        $len = 50;
        if (!empty($searchtype) && !in_array($searchtype, array("story", "album"))) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        if (empty($searchcontent)) {
            $this->showErrorJson(ErrorConf::paramError());
        }
        
        if (empty($page) || $page == 1) {
            // add search count
            $searchcountobj = new SearchCount();
            $searchcountobj->addSearchContentCount($searchcontent);
        }
        
        $storyids = array();
        $storycount = 0;
        $searchobj = new OpenSearch();
        // 搜索故事
        if (empty($searchtype) || $searchtype == 'story') {
            $storysearch = $searchobj->searchStory($searchcontent, $page, $len);
            if (!empty($storysearch)) {
                $storyids = $storysearch['storyids'];
                $storycount = $storysearch['total'];
            }
        }
        
        // 搜索专辑
        if (empty($searchtype) || $searchtype == 'album') {
            $albumids = array();
            $albumcount = 0;
            $albumsearch = $searchobj->searchAlbum($searchcontent, $page, $len);
            if (!empty($albumsearch)) {
                $albumids = $albumsearch['albumids'];
                $albumcount = count($albumids);
            }
        }
        
        $aliossobj = new AliOss();
        $searchlist = array();
        
        $storylist = array();
        if (!empty($storyids)) {
            $storyobj = new Story();
            $storyres = $storyobj->getListByIds($storyids);
            if (!empty($storyres)) {
                foreach ($storyres as $value) {
                    if (empty($value['mediapath'])) {
                        continue;
                    }
                    $info = $value;
                    if (!empty($value['cover'])) {
                        $info['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_STORY, $value['cover'], 100, $value['cover_time']);
                        $info['playcover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_STORY, $value['cover'], 230, $value['cover_time']);
                    } else {
                        $info['cover'] = "";
                        $info['playcover'] = "";
                    }
                    $storylist[] = $info;
                }
            }
        }
        
        $albumlist = array();
        if (!empty($albumids)) {
            // 过滤掉没有音频的故事，所属的专辑
            $havemediaalbumids = array();
            $albumidstr = implode(",", $albumids);
            $storyobj = new Story();
            $havemediaalbumids = $storyobj->get_list("album_id IN ($albumidstr) and mediapath != ''", "", "album_id");
            if (!empty($havemediaalbumids)) {
                $havemediaalbumids = array_unique($havemediaalbumids);
                // 专辑列表
                $albumobj = new Album();
                $albumres = $albumobj->getListByIds($havemediaalbumids);
                if (!empty($albumres)) {
                    foreach ($albumres as $value) {
                        $info = $value;
                        if (!empty($value['cover'])) {
                            $info['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $value['cover'], 100, $value['cover_time']);
                        } else {
                            $info['cover'] = "";
                        }
                        $albumlist[] = $info;
                    }
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