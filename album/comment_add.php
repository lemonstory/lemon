<?php

include_once '../controller.php';
class comment_add extends controller
{
    function action() {
    	$albumid    = (int)$this->getRequest('albumid', 0);
    	$content    = $this->getRequest('content', '');
    	$star_level = (int)$this->getRequest('star_level', 0);

        $uid = $this->getUid();

        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        if (!$content) {
        	$this->showErrorJson(ErrorConf::CommentContentIsEmpty());
        }
        if (!$star_level) {
        	$this->showErrorJson(ErrorConf::CommentStarLevelIsError());
        }
        if ($star_level > 5 || $star_level <= 0) {
        	$this->showErrorJson(ErrorConf::CommentStarLevelIsError());
        }
    	
    	$userobj = new User();
    	$userinfo = current($userobj->getUserInfo($uid));
    	if (empty($userinfo)) {
    	    $this->showErrorJson(ErrorConf::userNoExist());
    	}
    	
    	$albuminfo = array();
        $album = new Album();

        if ($albumid) {
        	$albuminfo = $album->get_album_info($albumid);
        }

        if (!$albuminfo) {
        	return $this->showErrorJson(ErrorConf::albumInfoIsEmpty());
        }
        $addtime = date('Y-m-d H:i:s');

        $comment = new Comment();
        $commentid = $comment->insert(array(
        	'userid'     => $uid,
        	'albumid'    => $albumid,
            'content'    => $content,
        	'star_level' => $star_level,
        	'addtime'    => $addtime,
        ));
        // 更新星级
        $star_level = $comment->getStarLevel($albumid);
        $album = new Album();
        $album->update(array('star_level' => $star_level), " `id`={$albumid} ");
        // 更新album_tag_relation的评论星级
        $tagnewobj = new TagNew();
        $tagnewobj->updateAlbumTagRelationCommentStarLevel($albumid, $star_level);

        // add sls comment log
        $userimsiobj = new UserImsi();
        $uimid = $userimsiobj->getUimid($uid);
        $alislsobj = new AliSlsUserActionLog();
        $alislsobj->addCommentAlbumActionLog($uimid, $uid, $commentid, $albumid, $content, getClientIp(), $addtime, $star_level);
        
        $this->showSuccJson();
    }
}
new comment_add();