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
	    return array('code'=>'100020','desc'=>'你已经被封号.');
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