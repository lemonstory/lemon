<?php
die();
/*
 * 复制收听总数到标签专辑关联表
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class cron_copyListenNumToAlbumTagRelation extends DaemonBase {
    protected $isWhile = false;
	protected function deal() {
	    $db = DbConnecter::connectMysql("share_main");
	    $selectsql = "SELECT * FROM `listen_album_count` WHERE `num`>10 ORDER BY `num` DESC";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute();
	    $list = $selectst->fetchAll(PDO::FETCH_ASSOC);
	    $db = null;
	    
	    $logfile = "/alidata1/copylistennum.log";
	    $fp = @fopen($logfile, "a+");
	    $tagnewobj = new TagNew();
	    foreach ($list as $value) {
	        $albumid = $value['albumid'];
	        $num = $value['num'];
	        
	        $res = $tagnewobj->updateAlbumTagRelationListenNum($albumid, $num,true);
	        if (empty($res)) {
	            $res = 0;
	        }
	        fwrite($fp, "copy->albumid-{$albumid}##num-{$num}##res={$res}\n");
	    }
	}

	protected function checkLogPath() {}

}
new cron_copyListenNumToAlbumTagRelation ();