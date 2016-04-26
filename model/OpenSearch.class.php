<?php
/*
 * 按专辑名称、作者、故事名称搜索专辑
 */
include_once API_LEMON_ROOT . 'libs/Pinyin.php';
include_once API_LEMON_ROOT . "libs/opensearch/CloudsearchDoc.php";
include_once API_LEMON_ROOT . "libs/opensearch/CloudsearchIndex.php";
include_once API_LEMON_ROOT . "libs/opensearch/CloudsearchClient.php";
include_once API_LEMON_ROOT . "libs/opensearch/CloudsearchSearch.php";
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
        $search->addSummary('albumtitle', 50, 'em', '...', 1, "<font color='#F5A623'>", "</font>");
        $search->addSummary('storytitle', 50, 'em', '...', 1, "<font color='#F5A623'>", "</font>");
        $data = json_decode($search->search(), true);
        if ($data['status'] != "OK") {
            return array();
        }

        $total = $data['result']['total'];
        $albumids = array();
        $albumsummarytitles = array();
        if (! empty($data['result']['items'])) {
            $items = $data['result']['items'];
            foreach ($items as $one) {
                $albumids[] = $one['albumid'];
                $albumsummarytitles[$one['albumid']] = $one['albumtitle'];
            }
        }
        if (!empty($albumids)) {
            $albumids = array_unique($albumids);
        }
        return array("albumids" => $albumids, "total" => $total, 'albumsummarytitles' => $albumsummarytitles);
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
        $search->addSummary('albumtitle', 50, 'em', '...', 1, "<font color='#F5A623'>", "</font>");
        $search->addSummary('storytitle', 50, 'em', '...', 1, "<font color='#F5A623'>", "</font>");
        $data = json_decode($search->search(), true);
        if ($data['status'] != "OK") {
            return array();
        }
        
        $total = $data['result']['total'];
        $storyids = array();
        $storysummarytitles = array();
        if (! empty($data['result']['items'])) {
            $items = $data['result']['items'];
            foreach ($items as $one) {
                $storyids[] = $one['storyid'];
                $storysummarytitles[$one['storyid']] = $one['storytitle'];
            }
        }
        if (!empty($storyids)) {
            $storyids = array_unique($storyids);
        }
        return array("storyids" => $storyids, "total" => $total, 'storysummarytitles' => $storysummarytitles);
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
        $storyinfo = $this->storyDoc("UPDATE", $storyid, $storytitle, $storytitlepytmp, $albumid, $addtime);
        $albuminfo = $this->albumDoc("UPDATE", $albumid, $albumtitle, $albumtitlepytmp, $addtime);
        $storydocs = json_encode(array($storyinfo));
        $albumdocs = json_encode(array($albuminfo));
        $storyresult = json_decode($doc->add($storydocs, $this->OPEN_TABLENAME_STORY), true);
        $albumresult = json_decode($doc->add($albumdocs, $this->OPEN_TABLENAME_ALBUM), true);
        if ($storyresult['status'] != 'OK' || $albumresult['status'] != 'OK') {
            return false;
        }
        return true;
    }

    public function removeAlbumFromSearch($albumid)
    {

        $client = $this->getClientinfo();
        $search = new CloudsearchSearch($client);
        $doc = new CloudsearchDoc($this->OPEN_INSTANCE, $client);
        $search->addIndex($this->OPEN_INSTANCE);
        $query = "albumid:'{$albumid}'";
        $search->setQueryString($query);
        $search->setFormat('json');
        $search->setHits('100');
        $data = json_decode($search->search(), true);
        $albumItems = array();
        $storyItems = array();
        while ($data['status'] == "OK" && $data['result']['total'] > 0) {
            foreach ($data['result']['items'] as $item) {
                $albumItems[] = $this->albumDoc("DELETE", $item['albumid'], $item['albumtitle'], $item['albumtitlepy'], $item['albumaddtime']);
                $storyItems[] = $this->storyDoc("DELETE", $item['storyid'], $item['storytitle'], $item['storytitlepy'], $item['albumid'], $item['storyaddtime']);
            }
            $albumDocs = json_encode($albumItems);
            $storyDocs = json_encode($storyItems);
            $storyresult = json_decode($doc->remove($storyDocs, $this->OPEN_TABLENAME_STORY), true);
            $albumresult = json_decode($doc->remove($albumDocs, $this->OPEN_TABLENAME_ALBUM), true);
            if ($storyresult['status'] == "OK" && $albumresult['status'] == "OK") {
                $data = json_decode($search->search(), true);
                usleep(100000);
            } else {
                //TODO:wirte log
                return false;
            }
        }
        return true;
    }

    public function addAlbumToSearchWithAlbumid($albumid)
    {

        $albumObj = new Album();
        $storyObj = new Story();
        $albumInfo = $albumObj->get_album_info($albumid);
        $albumtitle = "";
        if (!empty($albumInfo['title'])) {
            $albumtitle = $albumInfo['title'];
        }
        $storyArr = $storyObj->get_album_story_list($albumid);
        if (count($storyArr) > 0 && !empty($albumtitle)) {
            foreach ($storyArr as $story) {
                $storyid = $story['id'];
                MnsQueueManager::pushAlbumToSearchQueue($storyid);
            }
            return true;
        }
        return false;
    }

    public function removeStroyFromSearch($storyid)
    {

        $client = $this->getClientinfo();
        $search = new CloudsearchSearch($client);
        $doc = new CloudsearchDoc($this->OPEN_INSTANCE, $client);
        $search->addIndex($this->OPEN_INSTANCE);
        $query = "id:'{$storyid}'";
        $search->setQueryString($query);
        $search->setFormat('json');
        $data = json_decode($search->search(), true);
        $storyItems = array();
        if ($data['status'] == "OK" && $data['result']['total'] > 0) {
            foreach ($data['result']['items'] as $item) {
                $storyItems[] = $this->storyDoc("DELETE", $item['storyid'], $item['storytitle'], $item['storytitlepy'], $item['albumid'], $item['storyaddtime']);
            }
            $storyDocs = json_encode($storyItems);
            $storyresult = json_decode($doc->remove($storyDocs, $this->OPEN_TABLENAME_STORY), true);
            if ($storyresult['status'] == "OK") {
                return true;
            } else {
                //TODO:wirte log
                return false;
            }
        }
        return true;
    }

    public function addStoryoSearchWithStoryid($storyid)
    {

        if (!empty($storyid)) {
            MnsQueueManager::pushAlbumToSearchQueue($storyid);
            return true;
        }
        return false;
    }

    private function albumDoc($cmd, $albumid, $albumtitle, $albumtitlepy, $albumaddtime)
    {
        return array(
            "cmd" => $cmd,
            'fields' => array(
                'albumid' => $albumid,
                'albumtitle' => $albumtitle,
                'albumtitlepy' => $albumtitlepy,
                'albumaddtime' => $albumaddtime,
            )
        );
    }

    private function storyDoc($cmd, $storyid, $storytitle, $storytitlepy, $albumid, $storyaddtime)
    {
        return array(
            "cmd" => $cmd,
            'fields' => array(
                'storyid' => $storyid,
                'storytitle' => $storytitle,
                'storytitlepy' => $storytitlepy,
                'albumid' => $albumid,
                'storyaddtime' => $storyaddtime,
            )
        );
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