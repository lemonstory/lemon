<?php
//die();
/*
 * 复制热门推荐专辑数据到标签专辑关联表
 */
include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class cron_copyRecommendHotToAlbumTagRelation extends DaemonBase {
    protected $isWhile = false;
	protected function deal() {
	    $db = DbConnecter::connectMysql("share_main");
	    $selectsql = "SELECT * FROM `recommend_hot`";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute();
	    $recommendhotlist = $selectst->fetchAll(PDO::FETCH_ASSOC);
	    $db = null;
	    
	}

	protected function checkLogPath() {}

}
new cron_copyRecommendHotToAlbumTagRelation ();