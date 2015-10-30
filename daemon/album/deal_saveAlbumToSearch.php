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
	        sleep(10);
	        return true;
	    }
	    
        $story = new Story();
        $storyinfo = $story->get_story_info($storyid);
        if (empty($storyinfo)) {
            return true;
        }
        $albumid = $storyinfo['album_id'];
        $storytitle = $storyinfo['title'];
        //$addtime = $storyinfo['add_time'];
        
        $albumobj = new Album();
        $albuminfo = $albumobj->get_album_info($albumid);
        if (empty($albuminfo)) {
            return true;
        }
        $albumtitle = $albuminfo['title'];
        //$albumauthor = $albuminfo['author'];
        
        // add data to opensearch
        $searchobj = new OpenSearch();
        $ret = $searchobj->addAlbumToSearch($storyid, $storytitle, $albumid, $albumtitle);
        //if($ret == true) {
            $dataline = "---storyid---".$storyid."---albumid---{$albumid}---ret---{$ret}\n";
            $filepath	= dirname ( __FILE__ ).'/logs/saveAlbumToSearch'.date('Y-m-d').".log";
            $fp = @fopen($filepath, 'a+');
            @fwrite($fp, $dataline."\n");
            @fclose($fp);
        //}
        usleep(10000);
	}

	protected function checkLogPath() {}

}
new deal_saveAlbumToSearch ();