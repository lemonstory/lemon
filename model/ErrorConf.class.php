<?php
class   ErrorConf
{
    // COMMON 101xxx
    public static function systemError()
    {
        return array('code'=>'101001','desc'=>'系统错误');
    }
    public static function paramError($param=array())
    {
        return array('code'=>'101002','desc'=>'参数错误');
    }
    
    public static function paramErrorWithParam($param)
    {
        return array('code'=>'101002','desc'=>self::concatError('参数：[param]错误', $param));
    }
	public static function userNoExist()
	{
		return array('code'=>'100001','desc'=>'用户不存在');
	}
	
	public static function phonenumberIsBinded()
	{
		return array('code'=>'100018','desc'=>'手机号已经被绑定或者使用，无法绑定了');
		
	}
	public static function userFreezePost($uid)
	{
	    $frozenObj = new FrozenUserNew();
	    $unfrozentime = $frozenObj->getUserUnfrozenTime($uid);
	    if (empty($unfrozentime)) {
	        return array('code'=>'100019','desc'=>'你已经被冻结。');
	    }
	    $hours = ceil(($unfrozentime-time())/3600);
	    if ($hours<1){
	        return array('code'=>'100019','desc'=>'你已经被冻结，马上就可以解冻了。');
	    } else {
	        return array('code'=>'100019','desc'=>'你已经被冻结，距解冻还有'.$hours.'小时。');
	    }
	}
	public static function userForbidenPost()
	{
	    return array('code'=>'100020','desc'=>'你已经被封号，回火星老家种田去吧');
	}
	public static function vcodeError()
	{
		return array('code'=>'100501','desc'=>'验证码错误');
	}
	
	public static function passwordError()
	{
		return array('code'=>'100002','desc'=>'用户名或者密码错误');
	}
	
	public static function phoneNumberEmpty()
	{
		return array('code'=>'100003','desc'=>'手机号不能为空');
	}
	
	public static function vcodeEmpty()
	{
		return array('code'=>'109103','desc'=>'验证码不能为空');
	}

	
	public static function noUploadAvatarfile()
	{
		return array('code'=>'130003','desc'=>'上传头像文件不存在');
	}
	public static function uploadAvatarfileFail()
	{
		return array('code'=>'130013','desc'=>'头像上传失败');
	}
	
	
	public static function noUploadHomeCoverfile()
	{
		return array('code'=>'140003','desc'=>'上传封面文件不存在');
	}
	public static function uploadHomeCoverfileFail()
	{
		return array('code'=>'140013','desc'=>'封面上传失败');
	}
	public static function nohomeCoverfileformalway()
	{
		return array('code'=>'140014','desc'=>'非正规上传途径');
	}
	public static function noSelectedSyscover()
	{
		return array('code'=>'140015','desc'=>'未选定封面图');
	}
	
	
	public static function passwordEmpty()
	{
		return array('code'=>'100004','desc'=>'密码不能为空');
	}
	public static function phoneIsReged()
	{
		return array('code'=>'100005','desc'=>'该手机号已经注册过了。');
	}
	public static function phoneNumberIsIllegal()
	{
		return array('code'=>'100006','desc'=>'手机号格式不对');
	}
	public static function nickNameisError()
	{
		return array('code'=>'140006','desc'=>'昵称不对');
	}
	public static function nickNameisEmpty()
	{
		return array('code'=>'140016','desc'=>'昵称不能为空');
	}
	public static function nickNameIsNumber()
	{
		return array('code'=>'140026','desc'=>'昵称不能全为数字');
	}
	
	public static function nickNameIsExist()
	{
		return array('code'=>'140027','desc'=>'昵称已经存在了');
	}
	public static function noLogin()
	{
		return array('code'=>'100007','desc'=>'身份已过期，请退出后重新登录');
	}
	
	public static function qqUserInfoEmpty()
	{
		return array('code'=>'100807','desc'=>'qq用户信息获取失败');
	}
	
    public static function qqAuthInfoEmpty()
	{
		return array('code'=>'100817','desc'=>'此qq未注册');
	} 
	
	public static function loginedNoReqFindPwd()
	{
		return array('code'=>'105007','desc'=>'现在是登陆状态，请直接修改密码');
	}
	
	public static function loginedEd()
	{
		return array('code'=>'105009','desc'=>'已经是登陆状态');
	}
	
	// 微博登录
	public static function wbAuthInfoSaveFail()
	{
	    return array('code'=>'105104','desc'=>'授权信息保存失败');
	}
	public static function wbGetUserInfoFail()
	{
	    return array('code'=>'105105','desc'=>'微博用户不存在');
	}
	public static function wbAuthInfoNoReg()
	{
	    return array('code'=>'105106','desc'=>'请注册后再登录');
	}
	
	// facebook登录
	public static function fbUidIsError()
	{
	    return array('code'=>'105201','desc'=>'uid is error');
	}
	public static function fbAuthInfoSaveFail()
	{
	    return array('code'=>'105204','desc'=>'auth info save error');
	}
	public static function fbGetUserInfoFail()
	{
	    return array('code'=>'105205','desc'=>'get user profile is failed');
	}
	public static function fbAuthInfoNoReg()
	{
	    return array('code'=>'105206','desc'=>'please regiter acount before login');
	}
	
	// twitter登录
	public static function ttUidIsError()
	{
	    return array('code'=>'105301','desc'=>'uid is error');
	}
	public static function ttAuthInfoSaveFail()
	{
	    return array('code'=>'105304','desc'=>'auth info save error');
	}
	public static function ttGetUserInfoFail()
	{
	    return array('code'=>'105305','desc'=>'get user profile is failed');
	}
	public static function ttAuthInfoNoReg()
	{
	    return array('code'=>'105306','desc'=>'please regiter acount before login');
	}
	
	
	public static function phonenumberNoReg()
	{
		return array('code'=>'105107','desc'=>'手机号尚未注册');
	}
	public static function topicIsFavEd()
	{
		return array('code'=>'160009','desc'=>'您已经收藏过了。');
	}
	
	public static function topicidEmpty()
	{
		return array('code'=>'100009','desc'=>'主题ID为空');
	}	
	public static function cannotDelOtherTopic()
	{
		return array('code'=>'110009','desc'=>'只能删除自己的主题');
	}	
	public static function topicEmpty()
	{
		return array('code'=>'110111','desc'=>'主题不存在');
	}
	
	public static function commentContentEmpty()
	{
		return array('code'=>'100009','desc'=>'评论内容为空');
	}
	
	public static function  notSelfComment()
	{
		return array('code'=>'190009','desc'=>'只能删除自己的评论');
	}
	
	
	
	public static function commentSpam()
	{
	    return array('code'=>'100029','desc'=>'您刚发过评论，休息一下再试试');
	}
	
	public static function topicNotFound()
	{
		return array('code'=>'100010','desc'=>'主题找不到了。');
	}
	
	public static function noUid()
	{
		return array('code'=>'100009','desc'=>'UID为空');
	}
	public static function modifyUserInfoEmpty()
	{
		return array('code'=>'100009','desc'=>'要修改的用户信息未空');
	}
	
	public static function addFriendUidEmpty()
	{
		return array('code'=>'120009','desc'=>'要添加的好友UID为空');
	}
	public static function friendRemarkEmpty()
	{
	    return array('code'=>'120019','desc'=>'用户备注为空');
	}
	public static function friendIsToMany()
	{
		return array('code'=>'120020','desc'=>'关注人数超过上限了。');
	}
	public static function noFriendapply()
	{
		return array('code'=>'120030','desc'=>'对方没有申请过加您为好友');
	}
	public static function addApplyMsgerror()
	{
		return array('code'=>'120031','desc'=>'回复留言失败');
	}
	
	
	// TOPIC 102xxx
	public static function topicTypeError()
	{
	    return array('code'=>'102001','desc'=>'主题类型错误');
	}
	public static function topicSaveFail()
	{
	    return array('code'=>'102002','desc'=>'主题保存失败');
	}
	public static function topicContentEmpty()
	{
	    return array('code'=>'102003','desc'=>'主题内容为空');
	}
	public static function topicVideoEmpty()
	{
	    return array('code'=>'102009','desc'=>'主题视频为空');
	}
	public static function topicContentImageInvalidateType(){
	    return array('code'=>'102004','desc'=>'主题图片格式不正确');
	}
	public static function topicContentVideoInvalidateType(){
	    return array('code'=>'102004','desc'=>'主题视频格式不正确');
	}
    public static function topicContentImageEmpty(){
	    return array('code'=>'102004','desc'=>'主题图片不存在');
	}
	public static function yunyingMediaEmpty()
	{
	    return array('code'=>'102214','desc'=>'上传文件为空');
	}
	public static function yunyingMediaError()
	{
	    return array('code'=>'102234','desc'=>'上传失败');
	}
	public static function blockUserTopicFail()
	{
	    return array('code'=>'102014','desc'=>'屏蔽失败');
	}
	public static function unblockUserTopicFail()
	{
	    return array('code'=>'102015','desc'=>'解除屏蔽失败');
	}
	public static function oldPasswordError()
	{
		return array('code'=>'142004','desc'=>'旧密码错误');
	}

	// message
	public static function messageEmpty()
	{
	    return array('code'=>'103001','desc'=>'信息内容为空');
	}
	public static function toUserNotExists()
	{
	    return array('code'=>'103002','desc'=>'聊天用户不存在');
	}
	public static function cannotMessageSelf()
	{
	    return array('code'=>'103003','desc'=>'不能给自己发送信息');
	}
	public static function sessionCreateFail(){
	    return array('code'=>'103004','desc'=>'对话创建失败');
	}
	public static function messageIdEmpty()
	{
	    return array('code'=>'103005','desc'=>'messageid 为空');
	}
	public static function blockUidEmpty()
	{
	    return array('code'=>'103006','desc'=>'屏蔽用户不存在');
	}
	public static function blockUidFail()
	{
	    return array('code'=>'103007','desc'=>'屏蔽用户失败');
	}
	public static function unBlockUidFail()
	{
	    return array('code'=>'103008','desc'=>'取消屏蔽失败');
	}
	public static function delSessionFail()
	{
	    return array('code'=>'103009','desc'=>'删除会话失败');
	}
	public static function delMessageFail()
	{
	    return array('code'=>'103010','desc'=>'删除聊天失败');
	}
	public static function messagePicTextSaveFail()
	{
	    return array('code'=>'103001','desc'=>'聊天图文信息保存失败');
	}
	public static function sendRongyunFail()
	{
	    return array('code'=>'103002','desc'=>'发送聊天到融云失败。');
	}
	
	// tip
	public static function actionError()
	{
	    return array('code'=>'104001','desc'=>'不合法的动作');
	}
    public static function tipDataError()
	{
	    return array('code'=>'104002','desc'=>'无法生成提示内容');
	}
    public static function tipDeleteFail()
	{
	    return array('code'=>'104003','desc'=>'删除动态提示失败');
	}
    public static function noTipid()
	{
	    return array('code'=>'104004','desc'=>'没有需要删除的动态');
	}
    public static function tipReadFail()
	{
	    return array('code'=>'104005','desc'=>'删除动态提示失败');
	}
	
	public static function smsSendFailed()
	{
		return array('code'=>'107005','desc'=>'验证码发送失败');
	}
	public static function todayIschouJiang()
	{
		return array('code'=>'107705','desc'=>'今天已经抽过奖了。');
	}
	
	public static function reportuserempty()
	{
	    return array('code'=>'108001','desc'=>'被举报人信息为空。'); 
	}
	
	// export msg
	public static function userExportMsgEmpty()
	{
	    return array('code'=>'108101','desc'=>'任务不存在。');
	}
	public static function userExportMsgStatusError()
	{
	    return array('code'=>'109102','desc'=>'任务状态错误。');
	}
	public static function userExportFileNameError()
	{
	    return array('code'=>'109103','desc'=>'文件名格式错误。');
	}
	public static function userCopyFileError()
	{
	    return array('code'=>'109104','desc'=>'生成下载文件错误。');
	}
	public static function userGetFileContentError()
	{
	    return array('code'=>'109104','desc'=>'获取下载文件内容错误。');
	}
	public static function userExportDownloadError()
	{
	    return array('code'=>'109105','desc'=>'下载文件失败。');
	}
	public static function userMessageDataEmpty()
	{
	    return array('code'=>'109106','desc'=>'用户没有历史聊天数据。');
	}
	
	// follow 
	public static function userRepeatFollow()
	{
	    return array('code'=>'110001','desc'=>'你已经关注过了。');
	}
	public static function userFollowEmpty()
	{
	    return array('code'=>'110002','desc'=>'你还没有关注哦。');
	}
	public static function userFollowError()
	{
	    return array('code'=>'110003','desc'=>'添加关注错误。');
	}
	public static function userDelFollowError()
	{
	    return array('code'=>'110004','desc'=>'取消关注错误。');
	}
	public static function followUserEmpty()
	{
	    return array('code'=>'110005','desc'=>"还没有人关注。");
	}
	public static function huatiDataEmpty()
	{
	    return array('code'=>'110006','desc'=>"话题不存在。");
	}
	public static function poiDataEmpty()
	{
	    return array('code'=>'110007','desc'=>"位置不存在。");
	}
	
	// 转发主题
	public static function repostTopicIsMyself()
	{
	    return array('code'=>'111001','desc'=>"不可以转发自己的主题。");
	}
	public static function userRepostTopicIsEmpty()
	{
	    return array('code'=>'111002','desc'=>"未转发过该主题。");
	}
	public static function userIsRepostTopic()
	{
	    return array('code'=>'111003','desc'=>"请不要重复转发。");
	}
	
	// 陌生人消息设置
	public static function addstrangeFail()
	{
	    return array('code'=>'112001','desc'=>"拒绝陌生人消息失败。");
	}
	public static function delstrangeFail()
	{
	    return array('code'=>'112002','desc'=>"开启陌生人消息失败。");
	}
	
	/**
	 * 拼接错误信息
	 * @param string $msg
	 * @param array $param
	 * @usage 'desc'=>self::concatError('参数[param]错误', $param) 
	 * E.g
	 * 		$msg = '参数[param]错误';
	 * 		$param = array('param'=>'value');
	 * 	@return string '参数value错误'
	 */
    public static function concatError($msg, $param)
    {
        if (!empty($param)){
            foreach ($param as $param=>$value){
                $msg = str_replace("[$param]", $value, $msg);
            }
        }
        return $msg;
    }
}