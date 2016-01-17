<?php
die();
/*
 * 复制专辑评论星级到标签专辑关联表
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class cron_copyCommentStarLevelToAlbumTagRelation extends DaemonBase {
    protected $isWhile = false;
	protected function deal() {
	    $db = DbConnecter::connectMysql("share_story");
	    $selectsql = "SELECT * FROM `album`";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute();
	    $list = $selectst->fetchAll(PDO::FETCH_ASSOC);
	    $db = null;
	    
	    $logfile = "/alidata1/copystarlevel.log";
	    $fp = @fopen($logfile, "a+");
	    $tagnewobj = new TagNew();
	    foreach ($list as $value) {
	        $albumid = $value['id'];
	        $commentstarlevel = $value['star_level'];
	        
	        $res = $tagnewobj->updateAlbumTagRelationCommentStarLevel($albumid, $commentstarlevel);
	        if (empty($res)) {
	            $res = 0;
	        }
	        fwrite($fp, "copy->albumid-{$albumid}##commentstarlevel-{$commentstarlevel}##res={$res}\n");
	    }
	}

	protected function checkLogPath() {}

}
new cron_copyCommentStarLevelToAlbumTagRelation ();