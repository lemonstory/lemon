<?php
/*
 * 按专辑名称、作者、故事名称搜索专辑
 */
include_once SERVER_ROOT . 'libs/Pinyin.php';
include_once SERVER_ROOT . "libs/opensearch/CloudsearchDoc.php";
include_once SERVER_ROOT . "libs/opensearch/CloudsearchIndex.php";
include_once SERVER_ROOT . "libs/opensearch/CloudsearchClient.php";
include_once SERVER_ROOT . "libs/opensearch/CloudsearchSearch.php";
class TopicDescSearch 
{
    public $OPEN_INSTANCE = 'albumsearch';
    
    public function searchAlbum($field, $keyword, $len = 100)
    {
        if(!in_array($field, array('albumtitle','albumauthor','storytitle'))) {
            return false;
        }
        $keywordpy = Pinyin($keyword);
        for  ($i = 0; $i < strlen($keywordpy); $i++) {
            $searchtext .= $keywordpy[$i]." ";
        }
        if ($searchtext == "") {
            $searchtext = $keyword;
        }
        
        $client = $this->getClientinfo();
        $search = new CloudsearchSearch($client);
        $search->addIndex($this->OPEN_INSTANCE);
        
        if(preg_match("/^[\x7f-\xff]+$/",$keyword)) {
            if($field == 'albumtitle') {
                $search->setQueryString("albumtitlepy:'{$keyword}'");
            }
            if($field == 'albumauthor') {
                $search->setQueryString("albumauthorpy:'{$keyword}'");
            }
            if($field == 'storytitle') {
                $search->setQueryString("storytitlepy:'{$keyword}'");
            }
        }else{
            if($field == 'albumtitle') {
                $search->setQueryString("albumtitlepy:'{$searchtext}'");
            }
            if($field == 'albumauthor') {
                $search->setQueryString("albumauthorpy:'{$searchtext}'");
            }
            if($field == 'storytitle') {
                $search->setQueryString("storytitlepy:'{$searchtext}'");
            }
        }
        
        $search->addSort('addtime');
        $search->setStartHit($start);
        $search->setHits($len);
        $search->setFormat('json');
        $data = json_decode($search->search(), true);
        if ($data['status'] != "OK") {
            return array();
        }
        $total = $data['result']['total'];
        $result = array();
        if (! empty($data['result']['items'])) {
            foreach ($data['result']['items'] as $one) {
                $result[] = array(
                        'topicid' => $one['topicid'],
                        'desc' => $one['desc'] 
                );
            }
        }
        
        return array("result" => $result, 'total' => $total);
    }
    
    /**
     * @param S $topicid
     * @param S $uid
     * @param S $desc
     * @param I $addtime
     * @return boolean
     */
    public function addTopicDescToSearch($topicid, $uid, $desc, $addtime) 
    {
        $pinyin = Pinyin($huatitext);
        $pinyintmp = "";
        for($i = 0; $i < strlen($pinyin); $i++) {
            $pinyintmp .= $pinyin[$i]." ";
        }
        $pinyintmp = $huatitext ." ".$pinyintmp;
        
        
        $client = $this->getClientinfo();
        $doc = new CloudsearchDoc($this->OPEN_INSTANCE, $client);
        $info = array(
                "cmd" => "UPDATE",
                'fields' => array(
                        'topicid' => $topicid,
                        'uid' => $uid,
                        'desc' => $desc,
                        'addtime' => $addtime,
                        'huatitxt'=>$huatitext,
                        'huatipy'=>$pinyintmp
                ) 
        );
        $docs = json_encode(array(
                $info 
        ));
        $result = json_decode($doc->add($docs, 'tututopicdesc'), true);
        if ($result['status'] != 'OK') {
            return false;
        }
        return true;
    }
    
    private function getClientinfo() {
        $client = new CloudsearchClient('84KTqRKsyBIYnVJt', 'u72cpnMTt2mykMMluafimbhv5QD3uC', array(
                'host' => 'http://opensearch.aliyuncs.com' 
        ), 'aliyun');
        return $client;
    }
}