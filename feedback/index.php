<?php

include_once '../controller.php';
class index extends controller
{
    function action() {
        $content = $this->getRequest('content', '');
        $tel     = $this->getRequest('tel', '');
    	$qq      = $this->getRequest('qq', '');

        $uid = $this->getUid();

        if (empty($uid)) {
            $this->showErrorJson(ErrorConf::noLogin());
        }
        if (!$content) {
        	$this->showErrorJson(ErrorConf::FeedbackContentIsEmpty());
        }
    	
    	$userobj = new User();
    	$userinfo = current($userobj->getUserInfo($uid));
    	if (empty($userinfo)) {
    	    $this->showErrorJson(ErrorConf::userNoExist());
    	}

        $userFeedback = new UserFeedback();
        $userFeedbackId = $userFeedback->insert(array(
            'uid'       => $uid,
            'qq'        => $qq,
        	'tel'       => $tel,
        	'content'   => $content,
            'status'    => 1,
        	'addtime'   => date('Y-m-d H:i:s'),
        ));

        $this->showSuccJson();
    }
}
new index();