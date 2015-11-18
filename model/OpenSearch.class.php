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
    public $OPEN_INSTANCE = 'albumstorysearch';
    public $OPEN_TABLENAME_ALBUM = 'album';
    public $OPEN_TABLENAME_STORY = 'story';
    
    /**
     * 搜索专辑名称的专辑列表
     * @param S $keyword    关键词
     * @param I $len
     * @return array        专辑id列表
     */
    public function searchAlbum($keyword, $page = 1, $len = 20)
    {
        if (empty($keyword)) {
            return array();
        }
        if ($page < 1) {
            $page = 1;
        }
        if ($len < 1) {
            $len = 20;
        }
        if ($len > 100) {
            $len = 100;
        }
        $offset = ($page - 1) * $len;
        
        // 转化为分词
        $keywordpy = Pinyin($keyword);
        $searchtext = "";
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
            $query = "albumtitlepy:'{$keyword}'";
        }else{
            $query = "albumtitlepy:'{$searchtext}'";
        }
        $search->setQueryString($query);
        
        //$search->addAggregate("albumid", "count()");
        $search->addDistinct("albumid", 1, 1, "false"); // 每轮albumid中抽样取一个，只取一轮，实现items去重
        $search->setPair("duniqfield:albumid"); // 将totla数也去重
        $search->addSort('albumaddtime');
        $search->setStartHit($offset);
        $search->setHits($len);
        $search->setFormat('json');
        $data = json_decode($search->search(), true);
        if ($data['status'] != "OK") {
            return array();
        }
        
        $total = $data['result']['total'];
        $albumids = array();
        if (! empty($data['result']['items'])) {
            $items = $data['result']['items'];
            foreach ($items as $one) {
                $albumids[] = $one['albumid'];
            }
        }
        if (!empty($albumids)) {
            $albumids = array_unique($albumids);
        }
        
        return array("albumids" => $albumids, "total" => $total);
    }
    
    
    /**
     * 搜索故事名称的故事列表
     * @param S $keyword    关键词
     * @param I $len
     * @return array        故事id列表
     */
    public function searchStory($keyword, $page = 1, $len = 20)
    {
        if (empty($keyword)) {
            return array();
        }
        if ($page < 1) {
            $page = 1;
        }
        if ($len < 1) {
            $len = 20;
        }
        if ($len > 100) {
            $len = 100;
        }
        $offset = ($page - 1) * $len;
    
        // 转化为分词
        $keywordpy = Pinyin($keyword);
        $searchtext = "";
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
            $query = "storytitlepy:'{$keyword}'";
        }else{
            $query = "storytitlepy:'{$searchtext}'";
        }
        $search->setQueryString($query);
        $search->addSort('storyaddtime');
        $search->setStartHit($offset);
        $search->setHits($len);
        $search->setFormat('json');
        $data = json_decode($search->search(), true);
        if ($data['status'] != "OK") {
            return array();
        }
        
        $total = $data['result']['total'];
        $storyids = array();
        if (! empty($data['result']['items'])) {
            $items = $data['result']['items'];
            foreach ($items as $one) {
                $storyids[] = $one['storyid'];
            }
        }
        if (!empty($storyids)) {
            $storyids = array_unique($storyids);
        }
        
        return array("storyids" => $storyids, "total" => $total);;
    }
    
    
    /**
     * 添加数据到opensearch表
     * 只有存在故事的专辑才可以添加，空故事的专辑不添加
     * @param I $storyid        故事id
     * @param S $storytitle     故事标题
     * @param I $albumid        专辑id
     * @param S $albumtitle     专辑标题
     * @param I $addtime        故事添加时间
     * @return boolean
     */
    public function addAlbumToSearch($storyid, $storytitle, $albumid, $albumtitle) 
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
        
        
        $addtime = time();
        $client = $this->getClientinfo();
        $doc = new CloudsearchDoc($this->OPEN_INSTANCE, $client);
        $storyinfo = array(
                "cmd" => "UPDATE",
                'fields' => array(
                        'storyid' => $storyid,
                        'storytitle' => $storytitle,
                        'storytitlepy' => $storytitlepytmp,
                        'albumid' => $albumid,
                        'storyaddtime' => $addtime
                ) 
        );
        $albuminfo = array(
                "cmd" => "UPDATE",
                'fields' => array(
                        'albumid' => $albumid,
                        'albumtitle' => $albumtitle,
                        'albumtitlepy' => $albumtitlepytmp,
                        'albumaddtime' => $addtime
                ) 
        );
        
        $storydocs = json_encode(array($storyinfo));
        $albumdocs = json_encode(array($albuminfo));
        $storyresult = json_decode($doc->add($storydocs, $this->OPEN_TABLENAME_STORY), true);
        $albumresult = json_decode($doc->add($albumdocs, $this->OPEN_TABLENAME_ALBUM), true);
        if ($storyresult['status'] != 'OK' || $albumresult['status'] != 'OK') {
            return false;
        }
        return true;
    }
    
    /* public function exportstory($where)
    {
        set_time_limit(0);
        $openstorylist = array();
        $openalbumlist = array();
        
        $storyobj = new Story();
        $storylist = $storyobj->get_list($where);
        if (empty($storylist)) {
            return false;
        }
        
        $albumids = array();
        $albumlist = array();
        foreach ($storylist as $storyinfo) {
            $albumids[] = $storyinfo['album_id'];
        }
        if (!empty($albumids)) {
            $albumids = array_unique($albumids);
            $albumobj = new Album();
            $albumlist = $albumobj->getListByIds($albumids);
        }
        if (empty($albumlist)) {
            return false;
        }
        
        foreach ($storylist as $storyinfo) {
            $storytitle = $storyinfo['title'];
            // 转化为字母分词
            $storytitlepy = Pinyin($storytitle);
            $storytitlepytmp = "";
            for($i = 0; $i < strlen($storytitlepy); $i++) {
                $storytitlepytmp .= $storytitlepy[$i] . " ";
            }
            $storytitlepytmp = $storytitle . " " . $storytitlepytmp;
            
            $addtime = strtotime($storyinfo['add_time']);
            $openstorylist[] = array(
                    "fields" => array(
                            "storyid" => $storyinfo['id'],
                            "storytitle" => $storytitle,
                            "storytitlepy" => $storytitlepytmp,
                            "albumid" => $storyinfo['album_id'],
                            "storyaddtime" => $addtime
                            ),
                    "cmd" => "UPDATE"
                    );
        }
        $this->writeLog("story_{$where}", $openstorylist);
        
        foreach ($albumlist as $albuminfo) {
            $albumtitle = $albuminfo['title'];
            $albumtitlepy = Pinyin($albumtitle);
            $albumtitlepytmp = "";
            for($i = 0; $i < strlen($albumtitlepy); $i++) {
                $albumtitlepytmp .= $albumtitlepy[$i] . " ";
            }
            $albumtitlepytmp = $albumtitle . " " . $albumtitlepytmp;
            
            $addtime = strtotime($albuminfo['add_time']);
            $openalbumlist[] = array(
                    'fields' => array(
                            'albumid' => $albuminfo['id'],
                            'albumtitle' => $albumtitle,
                            'albumtitlepy' => $albumtitlepytmp,
                            'albumaddtime' => $addtime
                    ),
                    "cmd" => "UPDATE"
            );
        }
        $this->writeLog("album_{$where}", $openalbumlist);
    }
    
    private function writeLog($file, $data) {
        $data = json_encode($data);
        $filepath = "/alidata1/www/htdocs/api.xiaoningmeng.net/daemon/album/logs/{$file}.log";
        $fp = @fopen($filepath, 'a+');
        @fwrite($fp, $data."\n");
        @fclose($fp);
    } */
    
    private function getClientinfo() {
        $client = new CloudsearchClient(
                $_SERVER['CONFIG']['opensearch_accessKeyId'],
                $_SERVER['CONFIG']['opensearch_accessKeySecret'], 
                array('host' => 'http://opensearch.aliyuncs.com' ),
                'aliyun'
                );
        return $client;
    }
}