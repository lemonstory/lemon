<?php
class   ErrorConf
{
    // COMMON 101xxx
    public static function systemError()
    {
        return array('code'=>'100001','desc'=>'系统错误');
    }
    public static function paramError($param=array())
    {
        return array('code'=>'100002','desc'=>'参数错误');
    }
    public static function paramErrorWithParam($param)
    {
        return array('code'=>'100002','desc'=>self::concatError('参数：[param]错误', $param));
    }
    
    
    // 用户
	public static function userNoExist()
	{
		return array('code'=>'100101','desc'=>'用户不存在');
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
	    return array('code'=>'100120','desc'=>'你已经被封号.');
	}
	public static function modifyUserInfoEmpty()
	{
	    return array('code'=>'100009','desc'=>'要修改的用户信息为空');
	}
	public static function noLogin()
	{
		return array('code'=>'100107','desc'=>'身份已过期，请退出后重新登录');
	}
	public static function nickNameIsExist()
	{
	    return array('code'=>'100109','desc'=>'昵称已经存在了');
	}
	public static function userBabyInfoEmpty()
	{
	    return array('code'=>'100108','desc'=>'用户宝宝资料为空');
	}
	public static function userAddressInfoEmpty()
	{
	    return array('code'=>'100109','desc'=>'用户地址信息为空');
	}
	public static function userPasswordIsError()
	{
	    return array('code'=>'100110','desc'=>'用户名或者密码错误');
	}
	public static function phoneNumberIsIllegal()
	{
	    return array('code'=>'100111','desc'=>'手机号格式不正确');
	}
	
	
	// 上传
	public static function noUploadAvatarfile()
	{
	    return array('code'=>'100201','desc'=>'上传头像文件不存在');
	}
	public static function uploadAvatarfileFail()
	{
	    return array('code'=>'100202','desc'=>'头像上传失败');
	}
	public static function uploadImgfileFail()
	{
	    return array('code'=>'100203','desc'=>'上传图片失败');
	}
	public static function uploadMediaInvalidateType(){
	    return array('code'=>'100204','desc'=>'上传的媒体文件格式不正确');
	}
	public static function uploadMediafileFail()
	{
	    return array('code'=>'100205','desc'=>'上传媒体文件失败');
	}
	public static function noUploadTmpfile()
	{
	    return array('code'=>'100206','desc'=>'上传临时文件不存在');
	}
	
	
	// qq联合登录
	public static function qqUserInfoEmpty()
	{
		return array('code'=>'100301','desc'=>'qq用户信息获取失败');
	}
    public static function qqAuthInfoEmpty()
	{
		return array('code'=>'100302','desc'=>'此qq未注册');
	}
	// 微信登录
	public static function wechatUserInfoEmpty()
	{
		return array('code'=>'100311','desc'=>'微信用户信息获取失败');
	}
    public static function wechatAuthInfoEmpty()
	{
		return array('code'=>'100312','desc'=>'此微信账户未注册');
	}
	
	
	// 收藏
	public static function userFavAlbumIsExist()
	{
	    return array('code'=>'100401','desc'=>'你已经收藏过了');
	}
	public static function userFavIsEmpty()
	{
	    return array('code'=>'100402','desc'=>'你还没有收藏专辑');
	}
	public static function userFavDataError()
	{
	    return array('code'=>'100403','desc'=>'收藏数据错误');
	}
	
	
	// 收听
	public static function userListenStoryIsExist()
	{
	    return array('code'=>'100501','desc'=>'你已经收听过了');
	}
	public static function userListenIsEmpty()
	{
	    return array('code'=>'100502','desc'=>'你还没有收听故事');
	}
	public static function userListenDataError()
	{
	    return array('code'=>'100503','desc'=>'收听数据错误');
	}

	public static function userListenStoryNotExists()
	{
		return array('code'=>'100504','desc'=>'还没有收听该音乐');
	}
	
	// 专辑
	public static function albumStoryListIsEmpty()
	{
	    return array('code'=>'100601','desc'=>'专辑中没有故事哦');
	}
	public static function albumInfoIsEmpty()
	{
	    return array('code'=>'100602','desc'=>'专辑信息不存在');
	}
	
	// 故事
	public static function storyInfoIsEmpty()
	{
	    return array('code'=>'100701','desc'=>'故事信息不存在');
	}
	
	// rank 排行榜
	public static function rankListenUserListIsEmpty()
	{
	    return array('code'=>'100801','desc'=>'用户收听排行榜为空');
	}
	
	
	// search
	public static function searchAlbumIsEmpty()
	{
	    return array('code'=>'100901','desc'=>'搜索专辑结果为空');
	}

	// 评论
	public static function CommentContentIsEmpty()
	{
	    return array('code'=>'101001','desc'=>'评论内容不能为空');
	}

	public static function CommentStarLevelIsError()
	{
	    return array('code'=>'101002','desc'=>'评论星级错误');
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