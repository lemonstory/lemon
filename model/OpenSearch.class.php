<?php
/*
 * 按专辑名称、作者、故事名称搜索专辑
 */
include_once SERVER_ROOT . 'libs/Pinyin.php';
include_once SERVER_ROOT . "libs/opensearch/CloudsearchDoc.php";
include_once SERVER_ROOT . "libs/opensearch/CloudsearchIndex.php";
include_once SERVER_ROOT . "libs/opensearch/CloudsearchClient.php";
include_once SERVER_ROOT . "libs/opensearch/CloudsearchSearch.php";
class OpenSearch 
{
    public $OPEN_INSTANCE = 'album';
    
    /**
     * 搜索专辑名称、专辑作者、故事名称的专辑列表
     * @param S $keyword    关键词
     * @param I $len
     * @return array        专辑id列表
     */
    public function searchAlbum($keyword, $len = 140)
    {
        if (empty($keyword)) {
            return array();
        }
        
        // 转化为分词
        $keywordpy = Pinyin($keyword);
        for  ($i = 0; $i < strlen($keywordpy); $i++) {
            $searchtext .= $keywordpy[$i] . " ";
        }
        if ($searchtext == "") {
            $searchtext = $keyword;
        }
        
        $client = $this->getClientinfo();
        $search = new CloudsearchSearch($client);
        $search->addIndex($this->OPEN_INSTANCE);
        
        if(preg_match("/^[\x7f-\xff]+$/",$keyword)) {
            $query = "albumtitlepy:'{$keyword}' OR albumauthorpy:'{$keyword}' OR storytitlepy:'{$keyword}'";
        }else{
            $query = "albumtitlepy:'{$searchtext}' OR albumauthorpy:'{$searchtext}' OR storytitlepy:'{$searchtext}'";
        }
        $search->setQueryString($query);
        
        $search->addAggregate("albumid", "count()");
        $search->addSort('addtime');
        //$search->setStartHit($start);
        $search->setHits($len);
        $search->setFormat('json');
        $data = json_decode($search->search(), true);
        if ($data['status'] != "OK") {
            return array();
        }
        
        /*
        $total = $data['result']['total'];
        if(!empty($data['result']['items'])) {
            foreach ($data['result']['items'] as $one) {
                $result[] = array('htid'=>$one['htid'],'huatitxt'=>$one['huatitxt']);
            }
        } */
        
        $albumids = array();
        if (! empty($data['result']['facet'])) {
            $items = $data['result']['facet'][0]['items'];
            foreach ($items as $one) {
                $albumids[] = $one['value'];
            }
        }
        
        return $albumids;
    }
    
    
    /**
     * 添加数据到opensearch表
     * 只有存在故事的专辑才可以添加，空故事的专辑不添加
     * @param I $storyid        故事id
     * @param S $storytitle     故事标题
     * @param I $albumid        专辑id
     * @param S $albumtitle     专辑标题
     * @param S $albumauthor    专辑作者
     * @param I $addtime        故事添加时间
     * @return boolean
     */
    public function addAlbumToSearch($storyid, $storytitle, $albumid, $albumtitle, $albumauthor, $addtime) 
    {
        if (empty($storyid) || empty($storytitle) || empty($albumid) || empty($albumtitle)) {
            return false;
        }
        
        // 转化为字母分词
        $storytitlepy = Pinyin($storytitle);
        $storytitlepytmp = "";
        for($i = 0; $i < strlen($storytitlepy); $i++) {
            $storytitlepytmp .= $storytitlepy[$i] . " ";
        }
        $storytitlepytmp = $storytitle . " " . $storytitlepytmp;
        
        $albumtitlepy = Pinyin($albumtitle);
        $albumtitlepytmp = "";
        for($i = 0; $i < strlen($albumtitlepy); $i++) {
            $albumtitlepytmp .= $albumtitlepy[$i] . " ";
        }
        $albumtitlepytmp = $albumtitle . " " . $albumtitlepytmp;
        
        
        $albumauthorpy = Pinyin($albumauthor);
        $albumauthorpytmp = "";
        for($i = 0; $i < strlen($albumauthorpy); $i++) {
            $albumauthorpytmp .= $albumauthorpy[$i] . " ";
        }
        $albumauthorpytmp = $albumauthor . " " . $albumauthorpytmp;
        
        
        $client = $this->getClientinfo();
        $doc = new CloudsearchDoc($this->OPEN_INSTANCE, $client);
        $info = array(
                "cmd" => "UPDATE",
                'fields' => array(
                        'storyid' => $storyid,
                        'storytitle' => $storytitle,
                        'storytitlepy' => $storytitlepytmp,
                        'albumid' => $albumid,
                        'albumtitle' => $albumtitle,
                        'albumtitlepy' => $albumtitlepytmp,
                        'albumauthor' => $albumauthor,
                        'albumauthorpy' => $albumauthorpytmp,
                        'addtime' => $addtime,
                ) 
        );
        $docs = json_encode(array($info));
        $result = json_decode($doc->add($docs, $this->OPEN_INSTANCE), true);
        if ($result['status'] != 'OK') {
            return false;
        }
        return true;
    }
    
    private function getClientinfo() {
        $client = new CloudsearchClient('QHzux6QVXjQgfBNM', 'diWfijmBbiGlwle1s9KyAL8BQhB3Qc', array(
                'host' => 'http://opensearch.aliyuncs.com' 
        ), 'aliyun');
        return $client;
    }
}