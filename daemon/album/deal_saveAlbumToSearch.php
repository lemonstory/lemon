<?php
/*
 * 守护进程，将新增的故事专辑数据，添加到opensearch
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class deal_saveAlbumToSearch extends DaemonBase {
    protected $processnum = 1;
	protected function deal() {
	    $storyid = MnsQueueManager::popAlbumToSearchQueue();
	    if (empty($storyid)) {
            sleep(2);
	        return true;
	    }
	    
        $story = new Story();
        $storyinfo = $story->get_story_info($storyid);
        if (empty($storyinfo)) {
            $this->writeLog($storyid, 0, "storyinfo is empty");
            return true;
        }
        $albumid = $storyinfo['album_id'];
        $storytitle = $storyinfo['title'];
        //$addtime = $storyinfo['add_time'];
        
        $albumobj = new Album();
        $albuminfo = $albumobj->get_album_info($albumid);
        if (empty($albuminfo)) {
            $this->writeLog($storyid, $albumid, "albuminfo is empty");
            return true;
        }
        $albumtitle = $albuminfo['title'];
        //$albumauthor = $albuminfo['author'];
        
        // add data to opensearch
        $searchobj = new OpenSearch();
        $ret = $searchobj->addAlbumToSearch($storyid, $storytitle, $albumid, $albumtitle);
        //if($ret == true) {
            $this->writeLog($storyid, $albumid, "ret={$ret}");
        //}
        // 控制opensearch写入频率
        usleep(250000);
	}

	protected function checkLogPath() {}
	
	private function writeLog($storyid, $albumid = 0, $error = "") {
	    $dataline = "---storyid---".$storyid."---albumid---{$albumid}---error---{$error}\n";
	    $filepath = dirname ( __FILE__ ).'/logs/saveAlbumToSearch'.date('Y-m-d').".log";
	    $fp = @fopen($filepath, 'a+');
	    @fwrite($fp, $dataline."\n");
	    @fclose($fp);
	}

}
new deal_saveAlbumToSearch ();